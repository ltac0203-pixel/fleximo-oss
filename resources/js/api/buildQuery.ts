// URL クエリ文字列の組み立てを 1 箇所に集約し、null/undefined/空文字を一貫して除外する。
// 戻り値は `?` 接頭辞を含む完全な suffix（または空文字列）。

type QueryValue = string | number | boolean | null | undefined;

export function buildQuery(params: Record<string, QueryValue>): string {
    const usp = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
        if (value === null || value === undefined || value === "") {
            continue;
        }
        usp.set(key, String(value));
    }
    const query = usp.toString();
    return query ? `?${query}` : "";
}
