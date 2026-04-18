<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class OptionGroup extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'required',
        'min_select',
        'max_select',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'is_active' => 'boolean',
            'min_select' => 'integer',
            'max_select' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // このオプショングループに属するオプション
    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    // このオプショングループが紐付く商品
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(
            MenuItem::class,
            'menu_item_option_groups',
            'option_group_id',
            'menu_item_id'
        )->withPivot('sort_order');
    }

    // 選択されたオプションが有効かどうかを検証する
    public function validateOptionSelection(array $selectedOptionIds): bool
    {
        $count = count($selectedOptionIds);

        // 必須オプションの未選択は注文の不整合を招くため、早期にバリデーションで弾く
        if ($this->required && $count === 0) {
            throw new InvalidArgumentException(
                "オプショングループ「{$this->name}」は必須です。"
            );
        }

        // ビジネスルールで定めた最低選択数を強制し、不完全な注文を防止する
        if ($count < $this->min_select) {
            throw new InvalidArgumentException(
                "オプショングループ「{$this->name}」は最低{$this->min_select}個選択してください。"
            );
        }

        // オペレーション上の制約（調理工程や価格計算の整合性）を超えないよう制限する
        if ($count > $this->max_select) {
            throw new InvalidArgumentException(
                "オプショングループ「{$this->name}」は最大{$this->max_select}個までです。"
            );
        }

        // 他グループや無効化済みオプションのIDが混入した不正リクエストを検知する
        if ($count > 0) {
            $validOptionIds = $this->options()
                ->active()
                ->pluck('id')
                ->toArray();

            $invalidIds = array_diff($selectedOptionIds, $validOptionIds);
            if (! empty($invalidIds)) {
                throw new InvalidArgumentException(
                    "オプショングループ「{$this->name}」に無効なオプションが含まれています。"
                );
            }
        }

        return true;
    }
}
