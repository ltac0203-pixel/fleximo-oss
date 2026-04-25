import { act, renderHook } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { ENDPOINTS } from "@/api/endpoints";
import { useCardManagement } from "@/Hooks/useCardManagement";

const apiPostMock = vi.hoisted(() => vi.fn());
const apiDeleteMock = vi.hoisted(() => vi.fn());
const loggerErrorMock = vi.hoisted(() => vi.fn());
const loggerWarnMock = vi.hoisted(() => vi.fn());

vi.mock("@/api", async (importOriginal) => {
    const actual = await importOriginal<typeof import("@/api")>();
    return {
        ...actual,
        api: {
            post: apiPostMock,
            delete: apiDeleteMock,
        },
    };
});

vi.mock("@/Utils/logger", () => ({
    logger: {
        error: loggerErrorMock,
        warn: loggerWarnMock,
    },
}));

describe("useCardManagement", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("passes the selected isDefault flag to the registration payload", async () => {
        const createTokenMock = vi.fn().mockResolvedValue("tok_test_123");
        const clearFormMock = vi.fn();

        apiPostMock.mockResolvedValueOnce({
            data: {
                data: {
                    id: 1,
                    card_no_display: "**** **** **** 4242",
                    brand: "VISA",
                    expire: "12/30",
                    is_default: false,
                },
            },
            error: null,
            status: 201,
        });

        const { result } = renderHook(() =>
            useCardManagement({
                tenantId: 5,
                initialCards: [],
                createToken: createTokenMock,
                clearForm: clearFormMock,
            }),
        );

        await act(async () => {
            await result.current.registerCard({ isDefault: false });
        });

        expect(apiPostMock).toHaveBeenCalledWith(ENDPOINTS.customer.cards(5), {
            token: "tok_test_123",
            is_default: false,
        });
        expect(clearFormMock).toHaveBeenCalledTimes(1);
    });
});
