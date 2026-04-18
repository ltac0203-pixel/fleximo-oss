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
        return DB::transaction(function () use ($tenantId, $data) {
            $sortOrder = $this->resolveSortOrder(
                $data->sort_order,
                fn () => MenuCategory::where('tenant_id', $tenantId)->max('sort_order')
            );

            $category = MenuCategory::create([
                'tenant_id' => $tenantId,
                'name' => $data->name,
                'slug' => $this->generateUniqueSlug($tenantId, $data->name),
                'sort_order' => $sortOrder,
                'is_active' => $data->is_active,
            ]);

            $this->safeAuditLog(AuditAction::MenuCategoryCreated, $category);
            $this->invalidateMenuCache($tenantId);

            return $category;
        });
    }

    // カテゴリ更新
    public function update(MenuCategory $category, UpdateCategoryData $data): MenuCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $oldAttributes = $category->getAttributes();

            $updateData = $data->toArray();

            if (isset($updateData['name'])) {
                $updateData['slug'] = $this->generateUniqueSlug($category->tenant_id, $updateData['name'], $category->id);
            }

            $category->update($updateData);

            $this->safeAuditLog(AuditAction::MenuCategoryUpdated, $category, [
                'old' => $oldAttributes,
                'new' => $category->getAttributes(),
            ]);
            $this->invalidateMenuCache($category->tenant_id);

            return $category;
        });
    }

    // カテゴリ削除
    public function delete(MenuCategory $category): void
    {
        DB::transaction(function () use ($category) {
            if ($category->menuItems()->exists()) {
                throw new CategoryHasItemsException($category);
            }

            $tenantId = $category->tenant_id;

            $this->safeAuditLog(AuditAction::MenuCategoryDeleted, $category, [
                'old' => $category->toArray(),
            ]);

            $category->delete();
            $this->invalidateMenuCache($tenantId);
        });
    }

    // カテゴリ並び順更新
    public function reorder(int $tenantId, array $orderedIds): void
    {
        DB::transaction(function () use ($tenantId, $orderedIds) {
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

            $this->safeAuditLog(AuditAction::MenuCategoryReordered, null, [
                'metadata' => [
                    'ordered_ids' => $orderedIds,
                ],
            ], $tenantId);

            $this->invalidateMenuCache($tenantId);
        });
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
