import { ComponentPropsWithoutRef, ElementType, createElement } from "react";

type GeoSurfaceTone = "default" | "sky" | "cyan";
type GeoHoverEffect = "default" | "frame" | "brackets" | "none";

type GeoSurfaceOwnProps<T extends ElementType> = {
    as?: T;
    className?: string;
    tone?: GeoSurfaceTone;
    elevated?: boolean;
    interactive?: boolean;
    topAccent?: boolean;
    hoverEffect?: GeoHoverEffect;
};

type GeoSurfaceProps<T extends ElementType> = GeoSurfaceOwnProps<T> &
    Omit<ComponentPropsWithoutRef<T>, keyof GeoSurfaceOwnProps<T>>;

const TONE_CLASSES: Record<GeoSurfaceTone, string> = {
    default: "",
    sky: "border-sky-200 bg-sky-50/30 shadow-geo-sky",
    cyan: "border-cyan-200 bg-cyan-50/30 shadow-geo-cyan",
};

const HOVER_CLASSES: Record<GeoHoverEffect, string> = {
    default: "geo-surface-interactive",
    frame: "geo-hover-frame",
    brackets: "geo-hover-brackets",
    none: "",
};

export default function GeoSurface<T extends ElementType = "div">({
    as,
    className = "",
    tone = "default",
    elevated = false,
    interactive = false,
    topAccent = false,
    hoverEffect,
    ...props
}: GeoSurfaceProps<T>) {
    const Component = as ?? "div";
    const resolvedHover = interactive
        ? HOVER_CLASSES[hoverEffect ?? "default"]
        : "";
    const classes = [
        "geo-surface",
        TONE_CLASSES[tone],
        elevated ? "geo-surface-elevated" : "",
        resolvedHover,
        topAccent ? "geo-top-accent" : "",
        className,
    ]
        .filter(Boolean)
        .join(" ");

    return createElement(Component, {
        ...(props as ComponentPropsWithoutRef<T>),
        className: classes,
    });
}
