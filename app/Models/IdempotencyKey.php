<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// 冪等性キーのモデル。
class IdempotencyKey extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'route_name',
        'request_method',
        'request_hash',
        'response_body',
        'response_status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    // ユーザーリレーション。
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
