import React from "react";

interface MockChartProps {
    children?: React.ReactNode;
    data?: unknown;
    dataKey?: string;
    content?: unknown;
    [key: string]: unknown;
}

let tooltipContentRefs = new WeakMap<object, number>();
let nextTooltipContentRefId = 1;
const renderErrorTargets = new Set<string>();

export function setChartMockRenderError(componentName: string | null): void {
    renderErrorTargets.clear();

    if (componentName) {
        renderErrorTargets.add(componentName);
    }
}

/** テスト間の状態リークを防ぐためにモック内部状態をリセットする */
export function resetChartMockState(): void {
    tooltipContentRefs = new WeakMap<object, number>();
    nextTooltipContentRefId = 1;
    renderErrorTargets.clear();
}

function getTooltipContentRefId(value: unknown): string {
    if (value && (typeof value === "object" || typeof value === "function")) {
        const ref = value as object;
        const existingId = tooltipContentRefs.get(ref);
        if (existingId) return String(existingId);

        const id = nextTooltipContentRefId;
        nextTooltipContentRefId += 1;
        tooltipContentRefs.set(ref, id);
        return String(id);
    }

    return `primitive:${String(value)}`;
}

function createMockComponent(name: string) {
    return function MockComponent(props: MockChartProps) {
        if (renderErrorTargets.has(name)) {
            throw new Error(`[chartMock] ${name} render failed`);
        }

        return React.createElement("div", { "data-testid": `mock-${name}`, ...filterProps(props) }, props.children);
    };
}

function filterProps(props: MockChartProps) {
    const safe: Record<string, string> = {};
    if (props.data) safe["data-chart-data"] = JSON.stringify(props.data);
    if (props.dataKey) safe["data-key"] = props.dataKey;
    if (props.content !== undefined) {
        safe["data-tooltip-content-ref"] = getTooltipContentRefId(props.content);
    }
    return safe;
}

export const ResponsiveContainer = createMockComponent("ResponsiveContainer");
export const PieChart = createMockComponent("PieChart");
export const Pie = createMockComponent("Pie");
export const Cell = createMockComponent("Cell");
export const BarChart = createMockComponent("BarChart");
export const Bar = createMockComponent("Bar");
export const LineChart = createMockComponent("LineChart");
export const Line = createMockComponent("Line");
export const XAxis = createMockComponent("XAxis");
export const YAxis = createMockComponent("YAxis");
export const CartesianGrid = createMockComponent("CartesianGrid");
export const Tooltip = createMockComponent("Tooltip");
export const Legend = createMockComponent("Legend");
