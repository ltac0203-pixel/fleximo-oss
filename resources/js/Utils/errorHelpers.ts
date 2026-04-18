// catch 節で unknown を直接扱う事故を防ぎ、エラー処理を型安全に統一する。

// 呼び出し側で型分岐を重複させないため、表示用メッセージ抽出を集約する。
export function getErrorMessage(error: unknown, fallback = "エラーが発生しました"): string {
    if (error instanceof Error) return error.message;
    if (typeof error === "string") return error;
    if (error && typeof error === "object" && "message" in error) {
        const msg = (error as { message: unknown }).message;
        if (typeof msg === "string") return msg;
    }
    return fallback;
}

// ログ収集で必要な最小構造を固定し、出力フォーマットのばらつきを防ぐ。
export interface ErrorDetails {
    message: string;
    name?: string;
    stack?: string;
}

// 監視基盤へ送る情報量を担保しつつ、非Error値でも欠損なく扱えるようにする。
export function getErrorDetails(error: unknown): ErrorDetails {
    if (error instanceof Error) {
        return { message: error.message, name: error.name, stack: error.stack };
    }
    return { message: getErrorMessage(error) };
}

// 例外経路を Error に正規化し、上位の共通ハンドラで一貫して処理できるようにする。
export function toError(error: unknown, fallback = "エラーが発生しました"): Error {
    if (error instanceof Error) return error;
    return new Error(getErrorMessage(error, fallback));
}

const japaneseTextPattern = /[\u3005\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9faf]/;

// APIレスポンスのメッセージが日本語であればそのまま表示し、
// それ以外（英語エラーや空文字等）はフォールバックに差し替えてUIの統一感を保つ。
export const normalizeErrorMessage = (message: string | null | undefined, fallback: string): string => {
    if (!message) {
        return fallback;
    }

    return japaneseTextPattern.test(message) ? message : fallback;
};
