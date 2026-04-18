import GeoSurface from "@/Components/GeoSurface";
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";

describe("GeoSurface", () => {
    it("デフォルトで基本クラスを描画する", () => {
        render(<GeoSurface data-testid="surface">content</GeoSurface>);
        const surface = screen.getByTestId("surface");
        expect(surface).toHaveClass("geo-surface");
    });

    it("tone と interactive のクラスが反映される", () => {
        render(
            <GeoSurface data-testid="surface" tone="sky" interactive>
                content
            </GeoSurface>,
        );
        const surface = screen.getByTestId("surface");
        expect(surface).toHaveClass("border-sky-200");
        expect(surface).toHaveClass("geo-surface-interactive");
    });

    it("as を指定すると別タグで描画できる", () => {
        render(
            <GeoSurface as="section" data-testid="surface">
                content
            </GeoSurface>,
        );
        expect(screen.getByTestId("surface").tagName).toBe("SECTION");
    });
});
