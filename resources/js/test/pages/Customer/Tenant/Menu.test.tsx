import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import Menu from "@/Pages/Customer/Tenant/Menu";

const addToCartMock = vi.hoisted(() => vi.fn());
const getTotalItemCountMock = vi.hoisted(() => vi.fn(() => 0));
const showToastMock = vi.hoisted(() => vi.fn());
const hideToastMock = vi.hoisted(() => vi.fn());

vi.mock("@/Components/Customer/Common/FavoriteButton", () => ({
    default: () => null,
}));

vi.mock("@/Components/Customer/Menu/CategoryTabs", () => ({
    default: () => null,
}));

vi.mock("@/Components/Customer/Menu/VirtualizedMenuList", () => ({
    default: ({
        categories,
        onItemClick,
    }: {
        categories: Array<{ id: number; items: Array<{ id: number; name: string }> }>;
        onItemClick: (item: { id: number; name: string }) => void;
    }) => (
        <div>
            {categories.flatMap((category) =>
                category.items.map((item) => (
                    <button key={item.id} type="button" onClick={() => onItemClick(item)}>
                        {item.name}
                    </button>
                )),
            )}
        </div>
    ),
}));

vi.mock("@/Components/Customer/Menu/ItemDetailModal", () => ({
    default: ({
        show,
        item,
        onClose,
    }: {
        show: boolean;
        item: { name: string } | null;
        onClose: () => void;
    }) =>
        show && item ? (
            <div>
                <div>modal:{item.name}</div>
                <button type="button" onClick={onClose}>
                    close-modal
                </button>
            </div>
        ) : null,
}));

vi.mock("@/Components/SeoHead", () => ({
    default: () => null,
}));

vi.mock("@/Components/UI/ToastContainer", () => ({
    default: () => null,
}));

vi.mock("@/Layouts/CustomerLayout", () => ({
    default: ({ children }: React.PropsWithChildren) => <div>{children}</div>,
}));

vi.mock("@/Hooks/useCart", () => ({
    useCart: () => ({
        addToCart: addToCartMock,
        getTotalItemCount: getTotalItemCountMock,
    }),
}));

vi.mock("@/Hooks/useFavorites", () => ({
    useFavorites: () => ({
        isFavorited: () => false,
        toggleFavorite: vi.fn(),
        isToggling: false,
    }),
}));

vi.mock("@/Hooks/useMenuCategorySync", () => ({
    useMenuCategorySync: () => ({
        activeCategoryId: null,
        scrollToCategoryId: null,
        onCategoryTabChange: vi.fn(),
        onActiveCategoryChange: vi.fn(),
        onScrollComplete: vi.fn(),
    }),
}));

vi.mock("@/Hooks/useSeo", () => ({
    useSeo: () => ({
        generateMetadata: (metadata: unknown) => metadata,
    }),
}));

vi.mock("@/Hooks/useToast", () => ({
    useToast: () => ({
        toasts: [],
        showToast: showToastMock,
        hideToast: hideToastMock,
    }),
}));

vi.mock("@inertiajs/react", () => ({
    Link: ({ children }: React.PropsWithChildren) => <>{children}</>,
    router: {
        visit: vi.fn(),
    },
    usePage: () => ({
        props: {
            auth: {
                user: null,
            },
        },
    }),
}));

function createProps(): Parameters<typeof Menu>[0] {
    return {
        tenant: {
            id: 1,
            slug: "test-shop",
            name: "テスト店舗",
            is_favorited: false,
        },
        menu: {
            categories: [
                {
                    id: 1,
                    name: "カレー",
                    items: [
                        {
                            id: 10,
                            name: "スパイスカレー",
                            is_sold_out: false,
                            is_available: true,
                        },
                    ],
                },
            ],
        },
    } as Parameters<typeof Menu>[0];
}

describe("Customer tenant menu page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("opens and closes the item detail modal based on selectedItem", async () => {
        const user = userEvent.setup();

        render(<Menu {...createProps()} />);

        expect(screen.queryByText("modal:スパイスカレー")).not.toBeInTheDocument();

        await user.click(screen.getByRole("button", { name: "スパイスカレー" }));
        expect(screen.getByText("modal:スパイスカレー")).toBeInTheDocument();

        await user.click(screen.getByRole("button", { name: "close-modal" }));
        expect(screen.queryByText("modal:スパイスカレー")).not.toBeInTheDocument();
    });
});
