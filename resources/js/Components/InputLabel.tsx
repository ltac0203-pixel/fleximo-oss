import { LabelHTMLAttributes } from "react";

export default function InputLabel({
    value,
    className = "",
    children,
    required,
    ...props
}: LabelHTMLAttributes<HTMLLabelElement> & {
    value?: string;
    required?: boolean;
}) {
    return (
        <label {...props} className={`block text-sm font-medium text-ink ` + className}>
            {value ? value : children}
            {required && (
                <span className="text-red-500 ml-0.5" aria-hidden="true">
                    *
                </span>
            )}
        </label>
    );
}
