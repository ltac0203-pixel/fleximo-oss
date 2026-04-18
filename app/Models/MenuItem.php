<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Allergen;
use App\Enums\AllergenAdvisory;
use App\Exceptions\ItemNotAvailableException;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class MenuItem extends Model
{
    use BelongsToTenant, HasFactory;

    // available_days は 1 カラムで曜日条件を持ち回すため、曜日はビットマスクで表現する。
    public const SUNDAY = 1;      // 0b0000001

    public const MONDAY = 2;      // 0b0000010

    public const TUESDAY = 4;     // 0b0000100

    public const WEDNESDAY = 8;   // 0b0001000

    public const THURSDAY = 16;   // 0b0010000

    public const FRIDAY = 32;     // 0b0100000

    public const SATURDAY = 64;   // 0b1000000

    public const ALL_DAYS = 127;  // 0b1111111

    public const WEEKDAYS = 62;   // 月〜金 (2+4+8+16+32)

    public const WEEKENDS = 65;   // 日・土 (1+64)

    // price はMass Assignment攻撃による価格改ざんを防止するため$fillableから除外し、
    // Service層で直接属性代入する
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
        'is_sold_out',
        'available_from',
        'available_until',
        'available_days',
        'sort_order',
        'allergens',
        'allergen_advisories',
        'allergen_note',
        'nutrition_info',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_sold_out' => 'boolean',
            'price' => 'integer',
            'available_days' => 'integer',
            'sort_order' => 'integer',
            'allergens' => 'integer',
            'allergen_advisories' => 'integer',
        ];
    }

    // 栄養成分の表示項目は運用で増減しやすいため、固定カラムにせず JSON で持つ。
    protected function nutritionInfo(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value !== null ? json_decode($value, true) : null,
            set: fn ($value) => $value !== null ? json_encode($value) : null,
        );
    }

    public function hasAllergen(Allergen $allergen): bool
    {
        return ($this->allergens & $allergen->value) !== 0;
    }

    public function getAllergenLabels(): array
    {
        return Allergen::labels($this->allergens);
    }

    public function getAdvisoryLabels(): array
    {
        return AllergenAdvisory::labels($this->allergen_advisories);
    }

    public function hasAnyAllergenInfo(): bool
    {
        return $this->allergens > 0
            || $this->allergen_advisories > 0
            || $this->allergen_note !== null;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            MenuCategory::class,
            'menu_item_categories',
            'menu_item_id',
            'menu_category_id'
        );
    }

    public function optionGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            OptionGroup::class,
            'menu_item_option_groups',
            'menu_item_id',
            'option_group_id'
        )->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('is_sold_out', false);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function isAvailableOn(int $dayOfWeek): bool
    {
        $dayFlag = 1 << $dayOfWeek;

        return ($this->available_days & $dayFlag) !== 0;
    }

    // メニュー提供時間は同日内を前提にしているため、日跨ぎ提供が必要になったらここを拡張する。
    public function isAvailableNow(): bool
    {
        if (! $this->is_active || $this->is_sold_out) {
            return false;
        }

        $now = Carbon::now();

        if (! $this->isAvailableOn($now->dayOfWeek)) {
            return false;
        }

        if ($this->available_from && $this->available_until) {
            $currentTime = $now->format('H:i:s');

            if (
                $currentTime < $this->available_from ||
                $currentTime > $this->available_until
            ) {
                return false;
            }
        }

        return true;
    }

    public function ensureAvailableNow(): void
    {
        if (! $this->isAvailableNow()) {
            throw new ItemNotAvailableException($this);
        }
    }
}
