import { ReactNode } from "react";

interface FormActionsProps {
    children: ReactNode;
    leftSlot?: ReactNode;
    className?: string;
}

export default function FormActions({ children, leftSlot, className = "" }: FormActionsProps) {
    return (
        <div className={`mt-8 flex items-center gap-3 ${leftSlot ? "justify-between" : "justify-end"} ${className}`}>
            {leftSlot ? <div className="flex items-center gap-3">{leftSlot}</div> : null}
            <div className="flex items-center gap-3">{children}</div>
        </div>
    );
}
