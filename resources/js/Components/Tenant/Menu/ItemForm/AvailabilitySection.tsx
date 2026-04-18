import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import { withStableKeys } from "@/Utils/stableKeys";
import DayOfWeekSelector from "../DayOfWeekSelector";
import TimeRangeInput from "../TimeRangeInput";
import { BusinessHourRange, TenantBusinessHour } from "@/types";
import { FormData, FormErrors } from "./types";

interface AvailabilitySectionProps {
    formData: FormData;
    errors: FormErrors;
    onChange: (data: FormData) => void;
    todayBusinessHours?: BusinessHourRange[] | null;
    businessHours?: TenantBusinessHour[] | null;
}

export default function AvailabilitySection({
    formData,
    errors,
    onChange,
    todayBusinessHours,
    businessHours,
}: AvailabilitySectionProps) {
    const businessHourRanges = todayBusinessHours
        ? withStableKeys(todayBusinessHours, (range) => `${range.open_time}-${range.close_time}`)
        : [];

    const applyBusinessHours = () => {
        if (!businessHours || businessHours.length === 0) return;

        // 営業日のビットフラグをOR結合
        const uniqueWeekdays = [...new Set(businessHours.map((h) => h.weekday))];
        const availableDays = uniqueWeekdays.reduce((flags, weekday) => flags | (1 << weekday), 0);

        // 全時間帯の最早open_time
        const availableFrom = businessHours.reduce(
            (earliest, h) => (h.open_time < earliest ? h.open_time : earliest),
            businessHours[0].open_time,
        );

        // 深夜営業（open > close）を除外してlatest close_timeを計算
        const normalHours = businessHours.filter((h) => h.open_time <= h.close_time);
        const availableUntil =
            normalHours.length > 0
                ? normalHours.reduce(
                      (latest, h) => (h.close_time > latest ? h.close_time : latest),
                      normalHours[0].close_time,
                  )
                : businessHours.reduce(
                      (latest, h) => (h.close_time > latest ? h.close_time : latest),
                      businessHours[0].close_time,
                  );

        onChange({
            ...formData,
            available_days: availableDays,
            available_from: availableFrom,
            available_until: availableUntil,
        });
    };

    const hasBusinessHours = businessHours && businessHours.length > 0;

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium text-ink">販売時間設定</h3>
                {hasBusinessHours && (
                    <button
                        type="button"
                        onClick={applyBusinessHours}
                        className="rounded-md bg-surface-dim px-3 py-1.5 text-sm font-medium text-ink-light hover:bg-edge"
                    >
                        店舗の営業時間に合わせる
                    </button>
                )}
            </div>

            <div>
                <InputLabel value="販売時間帯" />
                {todayBusinessHours && todayBusinessHours.length > 0 && (
                    <div className="mt-1 flex flex-wrap gap-2">
                        {businessHourRanges.map(({ item: range, key }) => (
                            <button
                                key={key}
                                type="button"
                                className="text-sm text-blue-600 hover:text-blue-800"
                                onClick={() =>
                                    onChange({
                                        ...formData,
                                        available_from: range.open_time,
                                        available_until: range.close_time,
                                    })
                                }
                            >
                                営業時間を適用（{range.open_time}〜{range.close_time}）
                            </button>
                        ))}
                    </div>
                )}
                <div className="mt-2">
                    <TimeRangeInput
                        fromValue={formData.available_from}
                        untilValue={formData.available_until}
                        fromId="available_from"
                        untilId="available_until"
                        fromAriaDescribedBy={errors.available_from ? "available_from-error" : undefined}
                        untilAriaDescribedBy={errors.available_until ? "available_until-error" : undefined}
                        fromInvalid={!!errors.available_from}
                        untilInvalid={!!errors.available_until}
                        onFromChange={(value) => onChange({ ...formData, available_from: value })}
                        onUntilChange={(value) =>
                            onChange({
                                ...formData,
                                available_until: value,
                            })
                        }
                    />
                </div>
                <InputError id="available_from-error" message={errors.available_from} className="mt-2" />
                <InputError id="available_until-error" message={errors.available_until} className="mt-2" />
            </div>

            <div>
                <InputLabel value="販売曜日" />
                <div className="mt-2">
                    <DayOfWeekSelector
                        value={formData.available_days}
                        ariaInvalid={!!errors.available_days}
                        ariaDescribedBy={errors.available_days ? "available_days-error" : undefined}
                        onChange={(value) => onChange({ ...formData, available_days: value })}
                    />
                </div>
                <InputError id="available_days-error" message={errors.available_days} className="mt-2" />
            </div>
        </div>
    );
}
