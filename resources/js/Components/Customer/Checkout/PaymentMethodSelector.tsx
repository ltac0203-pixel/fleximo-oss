import { PaymentMethod } from "@/types";
import GeoSurface from "@/Components/GeoSurface";

interface PaymentMethodSelectorProps {
    selected: PaymentMethod | null;
    onChange: (method: PaymentMethod) => void;
    disabled?: boolean;
}

interface PaymentOption {
    value: PaymentMethod;
    label: string;
    icon: React.ReactNode;
    description: string;
}

const cardIcon = (
    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
        />
    </svg>
);

const paypayIcon = (
    <svg className="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
        <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" />
    </svg>
);

const PAYMENT_OPTIONS: PaymentOption[] = [
    {
        value: "new_card",
        label: "新しいカードで支払う",
        icon: cardIcon,
        description: "Visa, Mastercard, JCB, AMEX",
    },
    {
        value: "saved_card",
        label: "保存済みカードで支払う",
        icon: cardIcon,
        description: "登録済みのカードを使用",
    },
    {
        value: "paypay",
        label: "PayPay",
        icon: paypayIcon,
        description: "PayPayアプリで決済",
    },
];

// 決済手段の違いを同一UIで比較させ、誤選択による離脱を減らす。
export default function PaymentMethodSelector({
    selected,
    onChange,
    disabled = false,
}: PaymentMethodSelectorProps) {
    const paymentOptions = PAYMENT_OPTIONS;

    return (
        <GeoSurface topAccent elevated className="p-4">
            <h2 className="text-lg font-semibold text-ink mb-4">決済方法</h2>

            <div className="space-y-4">
                {paymentOptions.map((option) => (
                    <label
                        key={option.value}
                        className={`
                            geo-surface flex items-center gap-4 p-4 border-2 cursor-pointer transition
                            ${
                                selected === option.value
                                    ? "border-sky-500 bg-sky-50 shadow-geo-sky"
                                    : "border-edge hover:border-sky-300 hover:bg-surface"
                            }
                            ${disabled ? "opacity-50 cursor-not-allowed" : ""}
                        `}
                    >
                        <input
                            type="radio"
                            name="payment_method"
                            value={option.value}
                            checked={selected === option.value}
                            onChange={() => onChange(option.value)}
                            disabled={disabled}
                            className="sr-only"
                        />
                        <div
                            className={`
                            flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center
                            ${selected === option.value ? "bg-sky-100 text-sky-600" : "bg-surface-dim text-muted"}
                        `}
                        >
                            {option.icon}
                        </div>
                        <div className="flex-1">
                            <div
                                className={`font-medium ${selected === option.value ? "text-sky-900" : "text-ink"}`}
                            >
                                {option.label}
                            </div>
                            <div className="text-sm text-muted">{option.description}</div>
                        </div>
                        <div
                            className={`
                            w-5 h-5 rounded-full border-2 flex items-center justify-center
                            ${selected === option.value ? "border-sky-500 bg-sky-500" : "border-edge-strong"}
                        `}
                        >
                            {selected === option.value && <div className="w-2 h-2 rounded-full bg-white" />}
                        </div>
                    </label>
                ))}
            </div>
        </GeoSurface>
    );
}
