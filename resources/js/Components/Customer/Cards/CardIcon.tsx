interface CardIconProps {
    brand: string | null;
    className?: string;
}

// ブランドを即判別できる視覚キーを出し、カード選択ミスを減らす。
export default function CardIcon({ brand, className = "w-8 h-5" }: CardIconProps) {
    const brandUpper = brand?.toUpperCase() || "";

    // 未知ブランドでも破綻しないよう、常にフォールバック表示を返す。
    const getBrandStyle = (): { bg: string; text: string; label: string } => {
        switch (brandUpper) {
            case "VISA":
                return { bg: "bg-blue-700", text: "text-white", label: "VISA" };
            case "MASTERCARD":
            case "MASTER":
                return { bg: "bg-red-600", text: "text-white", label: "MC" };
            case "JCB":
                return { bg: "bg-green-700", text: "text-white", label: "JCB" };
            case "AMEX":
            case "AMERICAN EXPRESS":
                return { bg: "bg-blue-500", text: "text-white", label: "AMEX" };
            case "DINERS":
            case "DINERS CLUB":
                return { bg: "bg-ink-light", text: "text-white", label: "DC" };
            case "DISCOVER":
                return { bg: "bg-orange-500", text: "text-white", label: "DIS" };
            default:
                return { bg: "bg-muted-light", text: "text-white", label: "CARD" };
        }
    };

    const style = getBrandStyle();

    return (
        <div
            className={`
                ${className}
                ${style.bg}
                ${style.text}
                rounded
                flex
                items-center
                justify-center
                text-xs
                font-bold
                tracking-tighter
            `}
        >
            {style.label}
        </div>
    );
}
