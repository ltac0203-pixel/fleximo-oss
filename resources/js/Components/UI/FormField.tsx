import { ReactNode } from "react";
import InputLabel from "@/Components/InputLabel";
import InputError from "@/Components/InputError";

interface FormFieldProps {
    label: string;
    htmlFor: string;
    error?: string;
    errorId?: string;
    required?: boolean;
    className?: string;
    children: ReactNode;
}

export default function FormField({
    label,
    htmlFor,
    error,
    errorId,
    required,
    className = "",
    children,
}: FormFieldProps) {
    const resolvedErrorId = errorId ?? `${htmlFor}-error`;
    return (
        <div className={className}>
            <InputLabel htmlFor={htmlFor} value={label} required={required} />
            {children}
            <InputError id={resolvedErrorId} message={error} className="mt-2" />
        </div>
    );
}
