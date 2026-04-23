import { HTMLAttributes } from "react";

export type BadgeTone =
    | "green"
    | "yellow"
    | "red"
    | "sky"
    | "cyan"
    | "gray"
    | "neutral"
    | "muted";

export type BadgeSize = "xs" | "sm" | "md";

export type BadgeShape = "none" | "rounded" | "pill";

export interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
    tone?: BadgeTone;
    size?: BadgeSize;
    shape?: BadgeShape;
}

const TONE_CLASSES: Record<BadgeTone, string> = {
    green: "bg-green-100 text-green-800",
    yellow: "bg-yellow-100 text-yellow-800",
    red: "bg-red-100 text-red-800",
    sky: "bg-sky-100 text-sky-700",
    cyan: "bg-cyan-100 text-cyan-700",
    gray: "bg-gray-100 text-gray-800",
    neutral: "bg-surface-dim text-ink",
    muted: "bg-surface-dim text-ink-light",
};

const SIZE_CLASSES: Record<BadgeSize, string> = {
    xs: "px-2 py-0.5 text-xs",
    sm: "px-2 py-1 text-xs",
    md: "px-3 py-1 text-sm",
};

const SHAPE_CLASSES: Record<BadgeShape, string> = {
    none: "",
    rounded: "rounded",
    pill: "rounded-full",
};

export default function Badge({
    tone = "neutral",
    size = "xs",
    shape = "rounded",
    className = "",
    children,
    ...props
}: BadgeProps) {
    const composed = `inline-flex items-center font-medium ${SIZE_CLASSES[size]} ${SHAPE_CLASSES[shape]} ${TONE_CLASSES[tone]} ${className}`;
    return (
        <span {...props} className={composed.trim().replace(/\s+/g, " ")}>
            {children}
        </span>
    );
}
