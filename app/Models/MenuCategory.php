<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class MenuCategory extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (MenuCategory $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name) ?: 'category';
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'image_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    // このカテゴリに紐付く商品
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(
            MenuItem::class,
            'menu_item_categories',
            'menu_category_id',
            'menu_item_id'
        );
    }
}
