export const ENDPOINTS = {
    customer: {
        cart: "/api/customer/cart",
        cartById: (cartId: number) => `/api/customer/cart/${cartId}`,
        cartItems: "/api/customer/cart/items",
        cartItem: (id: number) => `/api/customer/cart/items/${id}`,
        checkout: "/api/customer/checkout",
        payments: {
            finalize: "/api/customer/payments/finalize",
            threeDsCallback: "/api/customer/payments/3ds-callback",
        },
        orders: {
            status: (orderId: number) => `/api/customer/orders/${orderId}/status`,
            reorder: (orderId: number) => `/api/customer/orders/${orderId}/reorder`,
        },
        favorites: {
            index: "/api/customer/favorites",
            toggle: (tenantId: number) => `/api/customer/favorites/tenants/${tenantId}`,
        },
        cards: (tenantId: number) => `/api/customer/tenants/${tenantId}/cards`,
        card: (tenantId: number, cardId: number) => `/api/customer/tenants/${tenantId}/cards/${cardId}`,
    },
    tenant: {
        menu: {
            items: "/api/tenant/menu/items",
            item: (id: number) => `/api/tenant/menu/items/${id}`,
            categories: "/api/tenant/menu/categories",
            category: (id: number) => `/api/tenant/menu/categories/${id}`,
            categoriesReorder: "/api/tenant/menu/categories/reorder",
            itemSoldOut: (id: number) => `/api/tenant/menu/items/${id}/sold-out`,
        },
        optionGroups: "/api/tenant/option-groups",
        optionGroup: (id: number) => `/api/tenant/option-groups/${id}`,
        options: (groupId: number) => `/api/tenant/option-groups/${groupId}/options`,
        option: (groupId: number, optionId: number) => `/api/tenant/option-groups/${groupId}/options/${optionId}`,
        staff: "/api/tenant/staff",
        staffMember: (id: number) => `/api/tenant/staff/${id}`,
        orderPause: {
            status: "/api/tenant/order-pause/status",
            toggle: "/api/tenant/order-pause/toggle",
        },
        kds: {
            orders: "/api/tenant/kds/orders",
            orderStatus: (orderId: number) => `/api/tenant/kds/orders/${orderId}/status`,
        },
        dashboard: {
            sales: "/api/tenant/dashboard/sales",
            topItems: "/api/tenant/dashboard/top-items",
            orders: "/api/tenant/dashboard/orders",
            hourly: "/api/tenant/dashboard/hourly",
            paymentMethods: "/api/tenant/dashboard/payment-methods",
            operationMetrics: "/api/tenant/dashboard/operation-metrics",
            customerInsights: "/api/tenant/dashboard/customer-insights",
            exportCsv: "/api/tenant/dashboard/export/csv",
        },
    },
} as const;
