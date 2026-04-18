import { getElapsedTextClass } from "@/Utils/kdsHelpers";

interface ElapsedTimeProps {
    display: string;
    isWarning: boolean;
}

export default function ElapsedTime({ display, isWarning }: ElapsedTimeProps) {
    const className = `text-sm ${getElapsedTextClass(isWarning)}`;

    if (isWarning) {
        return (
            <span className={className} aria-label={`警告: ${display}経過`}>
                <span aria-hidden="true">⚠ </span>
                {display}
            </span>
        );
    }

    return <span className={className}>{display}</span>;
}
