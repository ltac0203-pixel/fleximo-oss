import "@testing-library/jest-dom";
import { cleanup } from "@testing-library/react";
import { afterEach } from "vitest";
import { resetChartMockState } from "./helpers/chartMock";

// テスト間のDOM汚染を防ぎ、ケース順序に依存しない実行結果を担保する。
afterEach(() => {
    cleanup();
    resetChartMockState();
});

// ルーティング依存のUIテストを軽量に回すため、最小限のroute関数だけ差し込む。
global.route = (name: string, params?: Record<string, string>) => {
    return `/${name}${params ? "?" + new URLSearchParams(params).toString() : ""}`;
};
