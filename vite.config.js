import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";

export default defineConfig(({ mode }) => {
    const isProduction = mode === "production";

    return {
        plugins: [
            laravel({
                input: "resources/js/app.tsx",
                refresh: true,
            }),
            react(),
        ],
        build: {
            // 本番ビルド時のミニファイ設定
            minify: "esbuild",
            ...(isProduction && {
                esbuildOptions: {
                    // debugger 文を除去
                    drop: ["debugger"],
                    // console.error / console.warn は本番障害調査用に残す
                    // console.log / console.debug / console.info のみ除去
                    pure: ["console.log", "console.debug", "console.info"],
                },
            }),
            rollupOptions: {
                output: {
                    manualChunks: {
                        "vendor-react": ["react", "react-dom"],
                        "vendor-headlessui": ["@headlessui/react"],
                        "vendor-recharts": ["recharts"],
                        "vendor-dnd-kit": ["@dnd-kit/core", "@dnd-kit/sortable", "@dnd-kit/utilities"],
                    },
                },
            },
        },
    };
});
