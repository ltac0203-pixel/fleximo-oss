import { ReactNode } from "react";

interface EmptyRowProps {
    colSpan: number;
    children: ReactNode;
}

export default function EmptyRow({ colSpan, children }: EmptyRowProps) {
    return (
        <tr>
            <td colSpan={colSpan} className="px-6 py-12 text-center text-muted">
                {children}
            </td>
        </tr>
    );
}
