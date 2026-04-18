import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
    plugins: [react()],
    test: {
        globals: true,
        environment: "jsdom",
        setupFiles: ["./resources/js/test/setup.ts"],
        exclude: ["node_modules", "tests/e2e/**"],
        coverage: {
            provider: "v8",
            reporter: ["text", "json", "html", "clover"],
            exclude: ["node_modules/", "resources/js/test/", "**/*.d.ts", "**/*.config.*", "**/mockData/", "dist/"],
            reportsDirectory: "./coverage",
        },
    },
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./resources/js"),
        },
    },
});
