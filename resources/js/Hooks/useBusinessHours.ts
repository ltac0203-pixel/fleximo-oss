import { TenantBusinessHour } from "@/types";
import { useState } from "react";

let nextBusinessHourClientId = 0;

function createBusinessHourClientId(): string {
    const clientId = `business-hour-${nextBusinessHourClientId}`;
    nextBusinessHourClientId += 1;
    return clientId;
}

export interface BusinessHourForm extends TenantBusinessHour {
    client_id: string;
}

export function toBusinessHourForm(hour: TenantBusinessHour): BusinessHourForm {
    return {
        ...hour,
        client_id: createBusinessHourClientId(),
    };
}

export function toTenantBusinessHourPayload({ client_id: _clientId, ...hour }: BusinessHourForm): TenantBusinessHour {
    return hour;
}

type TimeField = "open_time" | "close_time";

interface UseBusinessHoursParams {
    businessHours: BusinessHourForm[];
    errors: Record<string, string | undefined>;
    onChange: (next: BusinessHourForm[]) => void;
}

const ALL_WEEKDAYS = [0, 1, 2, 3, 4, 5, 6] as const;

export function useBusinessHours({ businessHours, errors, onChange }: UseBusinessHoursParams) {
    const [copySourceDay, setCopySourceDay] = useState<number | null>(null);
    const [copyTargetDays, setCopyTargetDays] = useState<number[]>([]);

    const addTimeRange = (weekday: number) => {
        const dayCount = businessHours.filter((hour) => hour.weekday === weekday).length;

        onChange([
            ...businessHours,
            {
                client_id: createBusinessHourClientId(),
                weekday,
                open_time: "",
                close_time: "",
                sort_order: dayCount,
            },
        ]);
    };

    const updateTimeRange = (index: number, changes: Partial<BusinessHourForm>) => {
        const next = [...businessHours];
        next[index] = { ...next[index], ...changes };
        onChange(next);
    };

    const removeTimeRange = (index: number) => {
        onChange(businessHours.filter((_, i) => i !== index));
    };

    const getDayEntries = (weekday: number) =>
        businessHours
            .map((hour, index) => ({ hour, index }))
            .filter(({ hour }) => hour.weekday === weekday)
            .toSorted((a, b) => a.hour.sort_order - b.hour.sort_order || a.index - b.index);

    const getError = (index: number, field: TimeField) => errors[`business_hours.${index}.${field}`];

    const toggleCopyTarget = (weekday: number) => {
        setCopyTargetDays((prev) => (prev.includes(weekday) ? prev.filter((d) => d !== weekday) : [...prev, weekday]));
    };

    const selectWeekdays = () => {
        if (copySourceDay === null) return;
        setCopyTargetDays([1, 2, 3, 4, 5].filter((d) => d !== copySourceDay));
    };

    const selectAllDays = () => {
        if (copySourceDay === null) return;
        setCopyTargetDays(ALL_WEEKDAYS.filter((d) => d !== copySourceDay));
    };

    const applyCopyToTargetDays = () => {
        if (copySourceDay === null || copyTargetDays.length === 0) return;

        const sourceEntries = businessHours.filter((h) => h.weekday === copySourceDay);
        const remaining = businessHours.filter((h) => !copyTargetDays.includes(h.weekday));
        const copied = copyTargetDays.flatMap((targetDay) =>
            sourceEntries.map((entry, index) => ({
                ...entry,
                client_id: createBusinessHourClientId(),
                weekday: targetDay,
                sort_order: index,
            })),
        );

        onChange([...remaining, ...copied]);
        setCopySourceDay(null);
        setCopyTargetDays([]);
    };

    const openCopyUI = (weekday: number) => {
        if (copySourceDay === weekday) {
            setCopySourceDay(null);
            setCopyTargetDays([]);
            return;
        }

        setCopySourceDay(weekday);
        setCopyTargetDays([]);
    };

    return {
        businessHours,
        copySourceDay,
        copyTargetDays,
        addTimeRange,
        updateTimeRange,
        removeTimeRange,
        getDayEntries,
        getError,
        toggleCopyTarget,
        selectWeekdays,
        selectAllDays,
        applyCopyToTargetDays,
        openCopyUI,
    };
}
