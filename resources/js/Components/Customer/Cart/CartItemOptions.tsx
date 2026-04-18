import { CartOption } from "@/types";

interface CartItemOptionsProps {
    options: CartOption[];
}

export default function CartItemOptions({ options }: CartItemOptionsProps) {
    if (options.length === 0) {
        return null;
    }

    return (
        <ul className="mt-2 flex flex-wrap gap-1.5">
            {options.map((option) => (
                <li
                    key={option.id}
                    className="inline-flex items-center gap-1 border border-cyan-200 bg-cyan-50 px-1.5 py-0.5 text-[11px] text-cyan-700"
                >
                    <span>+ {option.name}</span>
                    {option.price > 0 && <span>+{option.price.toLocaleString()}円</span>}
                </li>
            ))}
        </ul>
    );
}
