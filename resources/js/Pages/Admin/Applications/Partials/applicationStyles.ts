export type ActionButtonColor = "blue" | "green" | "red";

export function getActionButtonClass(enabled: boolean, color: ActionButtonColor): string {
    const colorMap: Record<ActionButtonColor, { base: string; hover: string }> = {
        blue: { base: "bg-sky-600", hover: "hover:bg-sky-700" },
        green: { base: "bg-green-600", hover: "hover:bg-green-700" },
        red: { base: "bg-red-600", hover: "hover:bg-red-700" },
    };
    const { base, hover } = colorMap[color];

    return [
        "w-full px-4 py-2 text-sm font-medium text-white",
        base,
        enabled ? hover : "cursor-not-allowed opacity-50",
    ].join(" ");
}
