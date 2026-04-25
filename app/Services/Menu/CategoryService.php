<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\DTOs\Menu\CreateCategoryData;
use App\DTOs\Menu\UpdateCategoryData;
use App\Enums\AuditAction;
use App\Exceptions\CategoryHasItemsException;
use App\Models\MenuCategory;
use App\Services\Menu\Concerns\MenuServiceHelpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    use MenuServiceHelpers;

    // カテゴリ一覧取得
    public function getList(int $tenantId): Collection
    {
        return MenuCategory::where('tenant_id', $tenantId)
            ->ordered()
            ->get();
    }

    // カテゴリ作成
    public function create(int $tenantId, CreateCategoryData $data): MenuCategory
    {
        return $this->withMenuMutation(
            AuditAction::MenuCategoryCreated,
            $tenantId,
            function () use ($tenantId, $data): MenuCategory {
                $sortOrder = $this->resolveSortOrder(
                    $data->sort_order,
                    fn () => MenuCategory::where('tenant_id', $tenantId)->max('sort_order')
                );

                return MenuCategory::create([
                    'tenant_id' => $tenantId,
                    'name' => $data->name,
                    'slug' => $this->generateUniqueSlug($tenantId, $data->name),
                    'sort_order' => $sortOrder,
                    'is_active' => $data->is_active,
                ]);
            },
        );
    }

    // カテゴリ更新
    public function update(MenuCategory $category, UpdateCategoryData $data): MenuCategory
    {
        $oldAttributes = $category->getAttributes();

        return $this->withMenuMutation(
            AuditAction::MenuCategoryUpdated,
            $category->tenant_id,
            function () use ($category, $data): MenuCategory {
                $updateData = $data->toArray();

                if (isset($updateData['name'])) {
                    $updateData['slug'] = $this->generateUniqueSlug($category->tenant_id, $updateData['name'], $category->id);
                }

                $category->update($updateData);

                return $category;
            },
            fn (MenuCategory $updated) => [
                'old' => $oldAttributes,
                'new' => $updated->getAttributes(),
            ],
        );
    }

    // カテゴリ削除
    public function delete(MenuCategory $category): void
    {
        // 削除前に子要素の存在チェックは transaction の外で先に行う
        // （withMenuMutation が transaction を開始する前に弾く）
        if ($category->menuItems()->exists()) {
            throw new CategoryHasItemsException($category);
        }

        $tenantId = $category->tenant_id;
        $oldSnapshot = $category->toArray();

        $this->withMenuMutation(
            AuditAction::MenuCategoryDeleted,
            $tenantId,
            function () use ($category): MenuCategory {
                $category->delete();

                return $category;
            },
            fn () => ['old' => $oldSnapshot],
        );
    }

    // カテゴリ並び順更新
    public function reorder(int $tenantId, array $orderedIds): void
    {
        $this->withMenuMutation(
            AuditAction::MenuCategoryReordered,
            $tenantId,
            function () use ($tenantId, $orderedIds): void {
                if (! empty($orderedIds)) {
                    $cases = [];
                    $bindings = [];
                    foreach ($orderedIds as $index => $id) {
                        $cases[] = 'WHEN id = ? THEN ?';
                        $bindings[] = $id;
                        $bindings[] = $index + 1;
                    }

                    $caseSql = implode(' ', $cases);
                    $idPlaceholders = implode(',', array_fill(0, count($orderedIds), '?'));
                    $bindings = array_merge($bindings, $orderedIds, [$tenantId]);

                    DB::update(
                        "UPDATE menu_categories SET sort_order = CASE {$caseSql} END "
                        ."WHERE id IN ({$idPlaceholders}) AND tenant_id = ?",
                        $bindings
                    );
                }
            },
            fn () => ['metadata' => ['ordered_ids' => $orderedIds]],
        );
    }

    // テナント内でユニークなカテゴリslugを生成
    private function generateUniqueSlug(int $tenantId, string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 1;

        while (
            MenuCategory::where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
