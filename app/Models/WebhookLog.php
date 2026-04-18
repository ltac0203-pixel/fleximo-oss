<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'provider',
        'fincode_id',
        'event_type',
        'payload',
        'processed',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    // 処理完了としてマークする
    public function markAsProcessed(): bool
    {
        return $this->update([
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    // エラーを記録する
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'processed' => false,
            'error_message' => $errorMessage,
        ]);
    }

    // 未処理のログに絞り込むスコープ
    public function scopeUnprocessed(Builder $query): Builder
    {
        return $query->where('processed', false)
            ->whereNull('error_message')
            ->orderBy('created_at');
    }

    // 未処理のログを取得
    public static function unprocessed(): Collection
    {
        return static::query()->unprocessed()->get();
    }
}
