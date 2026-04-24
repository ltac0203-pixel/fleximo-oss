<?php

declare(strict_types=1);

namespace App\Domain\Tenant\BusinessHours;

use App\Models\TenantBusinessHour;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class BusinessHoursSchedule
{
    /**
     * @param  Collection<int, TenantBusinessHour>  $hours
     */
    public function __construct(
        private readonly Collection $hours,
    ) {}

    public function isOpenAt(Carbon $time): bool
    {
        return $this->statusAt($time)->isOpen;
    }

    // 複数の営業時間帯と日またぎ（深夜帯）に対応するため、前日の営業時間も考慮し1回の走査で完結させる
    public function statusAt(?Carbon $time = null): BusinessStatus
    {
        $time = $time?->copy() ?? Carbon::now();

        // 未設定は「営業時間を決めていない＝開店していない」と解釈し、安全側に倒す
        if ($this->hours->isEmpty()) {
            return new BusinessStatus(isOpen: false, todayBusinessHours: []);
        }

        $currentTime = $time->format('H:i');
        $weekday = $time->dayOfWeek;
        $previousWeekday = ($weekday + 6) % 7;

        $isOpen = false;
        $todayHours = [];

        // 前日の深夜営業チェック
        foreach ($this->hours->where('weekday', $previousWeekday)->sortBy('sort_order') as $hour) {
            $openTime = $this->formatTime($hour->open_time);
            $closeTime = $this->formatTime($hour->close_time);
            if ($this->isOvernight($openTime, $closeTime)) {
                $todayHours[] = ['open_time' => '00:00', 'close_time' => $closeTime];
                if (! $isOpen && $currentTime < $closeTime) {
                    $isOpen = true;
                }
            }
        }

        // 当日の営業時間チェック
        foreach ($this->hours->where('weekday', $weekday)->sortBy('sort_order') as $hour) {
            $openTime = $this->formatTime($hour->open_time);
            $closeTime = $this->formatTime($hour->close_time);
            $todayHours[] = ['open_time' => $openTime, 'close_time' => $closeTime];
            if (! $isOpen && $this->isWithinTodayTimeRange($currentTime, $openTime, $closeTime)) {
                $isOpen = true;
            }
        }

        return new BusinessStatus(isOpen: $isOpen, todayBusinessHours: $todayHours);
    }

    /**
     * @return array<int, array{open_time: string, close_time: string}>
     */
    public function todayBusinessHoursAt(?Carbon $time = null): array
    {
        return $this->statusAt($time)->todayBusinessHours;
    }

    private function formatTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i');
    }

    private function isOvernight(string $openTime, string $closeTime): bool
    {
        return $openTime > $closeTime;
    }

    private function isWithinTodayTimeRange(
        string $currentTime,
        string $openTime,
        string $closeTime,
    ): bool {
        if ($openTime <= $closeTime) {
            return $currentTime >= $openTime && $currentTime < $closeTime;
        }

        // 日またぎ営業のclose側は前日分として別途判定されるため、当日分はopen以降のみ対象とする
        return $currentTime >= $openTime;
    }
}
