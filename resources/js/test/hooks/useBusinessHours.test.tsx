import { renderHook, act } from "@testing-library/react";
import { useState } from "react";
import { describe, expect, it } from "vitest";
import { BusinessHourForm, useBusinessHours } from "@/Hooks/useBusinessHours";

const baseBusinessHours: BusinessHourForm[] = [
    { weekday: 1, open_time: "13:00", close_time: "18:00", sort_order: 1 },
    { weekday: 1, open_time: "09:00", close_time: "12:00", sort_order: 0 },
    { weekday: 2, open_time: "10:00", close_time: "16:00", sort_order: 0 },
    { weekday: 3, open_time: "11:00", close_time: "14:00", sort_order: 0 },
    { weekday: 5, open_time: "15:00", close_time: "20:00", sort_order: 0 },
];

function renderUseBusinessHours(
    initialBusinessHours: BusinessHourForm[] = baseBusinessHours,
    errors: Record<string, string | undefined> = {},
) {
    return renderHook(
        ({ initial, errors }) => {
            const [businessHours, setBusinessHours] = useState(initial);
            return useBusinessHours({
                businessHours,
                errors,
                onChange: setBusinessHours,
            });
        },
        {
            initialProps: {
                initial: initialBusinessHours,
                errors,
            },
        },
    );
}

describe("useBusinessHours", () => {
    it("getDayEntries: 同一曜日の時間帯をsort_order順で返す", () => {
        const { result } = renderUseBusinessHours();
        const mondayEntries = result.current.getDayEntries(1);

        expect(mondayEntries).toHaveLength(2);
        expect(mondayEntries.map(({ hour }) => hour.open_time)).toEqual(["09:00", "13:00"]);
    });

    it("addTimeRange: 同一曜日の末尾sort_orderで空の時間帯を追加する", () => {
        const { result } = renderUseBusinessHours();

        act(() => {
            result.current.addTimeRange(1);
        });

        const mondayHours = result.current.businessHours.filter((h) => h.weekday === 1);
        const added = mondayHours.find((h) => h.sort_order === 2);

        expect(mondayHours).toHaveLength(3);
        expect(added).toMatchObject({
            weekday: 1,
            open_time: "",
            close_time: "",
            sort_order: 2,
        });
    });

    it("updateTimeRange: 指定indexの時間帯だけ更新する", () => {
        const { result } = renderUseBusinessHours();
        const beforeSecond = result.current.businessHours[1].open_time;

        act(() => {
            result.current.updateTimeRange(0, { open_time: "14:00" });
        });

        expect(result.current.businessHours[0].open_time).toBe("14:00");
        expect(result.current.businessHours[1].open_time).toBe(beforeSecond);
    });

    it("removeTimeRange: 指定indexの時間帯を削除する", () => {
        const { result } = renderUseBusinessHours();

        act(() => {
            result.current.removeTimeRange(3);
        });

        expect(result.current.businessHours).toHaveLength(baseBusinessHours.length - 1);
        expect(result.current.businessHours.some((h) => h.weekday === 3 && h.open_time === "11:00")).toBe(false);
    });

    it("getError: useFormエラーキーから対象フィールドのエラーを返す", () => {
        const { result } = renderUseBusinessHours(baseBusinessHours, {
            "business_hours.2.open_time": "開店時間は必須です。",
        });

        expect(result.current.getError(2, "open_time")).toBe("開店時間は必須です。");
        expect(result.current.getError(2, "close_time")).toBeUndefined();
    });

    it("コピーUI: コピー元曜日のトグルとコピー先曜日選択を管理する", () => {
        const { result } = renderUseBusinessHours();

        act(() => {
            result.current.openCopyUI(1);
        });
        expect(result.current.copySourceDay).toBe(1);
        expect(result.current.copyTargetDays).toEqual([]);

        act(() => {
            result.current.toggleCopyTarget(3);
            result.current.toggleCopyTarget(5);
        });
        expect(result.current.copyTargetDays).toEqual([3, 5]);

        act(() => {
            result.current.toggleCopyTarget(3);
        });
        expect(result.current.copyTargetDays).toEqual([5]);

        act(() => {
            result.current.openCopyUI(1);
        });
        expect(result.current.copySourceDay).toBeNull();
        expect(result.current.copyTargetDays).toEqual([]);
    });

    it("selectWeekdays/selectAllDays: コピー先曜日を一括選択できる", () => {
        const { result } = renderUseBusinessHours();

        act(() => {
            result.current.openCopyUI(2);
        });

        act(() => {
            result.current.selectWeekdays();
        });
        expect(result.current.copyTargetDays).toEqual([1, 3, 4, 5]);

        act(() => {
            result.current.selectAllDays();
        });
        expect(result.current.copyTargetDays).toEqual([0, 1, 3, 4, 5, 6]);
    });

    it("applyCopyToTargetDays: コピー適用後に対象曜日を置換し、コピー状態をリセットする", () => {
        const { result } = renderUseBusinessHours();

        act(() => {
            result.current.openCopyUI(1);
        });

        act(() => {
            result.current.toggleCopyTarget(3);
            result.current.toggleCopyTarget(5);
        });

        act(() => {
            result.current.applyCopyToTargetDays();
        });

        const day3Hours = result.current
            .getDayEntries(3)
            .map(({ hour }) => ({
                open_time: hour.open_time,
                close_time: hour.close_time,
                sort_order: hour.sort_order,
            }));
        const day5Hours = result.current
            .getDayEntries(5)
            .map(({ hour }) => ({
                open_time: hour.open_time,
                close_time: hour.close_time,
                sort_order: hour.sort_order,
            }));

        expect(day3Hours).toEqual([
            { open_time: "13:00", close_time: "18:00", sort_order: 0 },
            { open_time: "09:00", close_time: "12:00", sort_order: 1 },
        ]);
        expect(day5Hours).toEqual([
            { open_time: "13:00", close_time: "18:00", sort_order: 0 },
            { open_time: "09:00", close_time: "12:00", sort_order: 1 },
        ]);
        expect(result.current.copySourceDay).toBeNull();
        expect(result.current.copyTargetDays).toEqual([]);
    });
});
