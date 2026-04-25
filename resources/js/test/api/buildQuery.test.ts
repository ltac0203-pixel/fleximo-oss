import { describe, expect, it } from "vitest";
import { buildQuery } from "@/api/buildQuery";

describe("buildQuery", () => {
    it("空オブジェクトは空文字列を返す", () => {
        expect(buildQuery({})).toBe("");
    });

    it("値を URLSearchParams としてエンコードし `?` 接頭辞を付ける", () => {
        expect(buildQuery({ period: "weekly", limit: 10 })).toBe("?period=weekly&limit=10");
    });

    it("null / undefined / 空文字を除外する", () => {
        expect(
            buildQuery({
                a: "x",
                b: null,
                c: undefined,
                d: "",
                e: 0,
                f: false,
            }),
        ).toBe("?a=x&e=0&f=false");
    });

    it("除外後にキーが残らない場合は空文字列", () => {
        expect(buildQuery({ a: null, b: undefined, c: "" })).toBe("");
    });

    it("特殊文字をエンコードする", () => {
        expect(buildQuery({ q: "a b&c" })).toBe("?q=a+b%26c");
    });
});
