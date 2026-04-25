import { SalesPeriod } from "@/types";
import SegmentedControl from "@/Components/UI/SegmentedControl";

interface PeriodSelectorProps {
    selected: SalesPeriod;
    onChange: (period: SalesPeriod) => void;
}

const periods: { value: SalesPeriod; label: string }[] = [
    { value: "daily", label: "日次" },
    { value: "weekly", label: "週次" },
    { value: "monthly", label: "月次" },
];

export default function PeriodSelector({ selected, onChange }: PeriodSelectorProps) {
    return <SegmentedControl options={periods} selected={selected} onChange={onChange} />;
}
