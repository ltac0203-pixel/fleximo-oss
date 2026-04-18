interface BusinessDateProps {
    date: string;
}

export default function BusinessDate({ date }: BusinessDateProps) {
    const formatDate = (dateString: string) => {
        const d = new Date(dateString);
        const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
        const dayOfWeek = dayNames[d.getDay()];

        return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日（${dayOfWeek}）`;
    };

    return <div className="text-lg text-ink-light">営業日: {formatDate(date)}</div>;
}
