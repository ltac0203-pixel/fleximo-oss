<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $weekday
 * @property string $open_time
 * @property string $close_time
 * @property int $sort_order
 */
class TenantBusinessHour extends Model
{
    protected $fillable = [
        'tenant_id',
        'weekday',
        'open_time',
        'close_time',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
