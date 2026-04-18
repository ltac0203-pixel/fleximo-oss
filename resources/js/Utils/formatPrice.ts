const jpyCurrencyFormatter = new Intl.NumberFormat("ja-JP", {
    style: "currency",
    currency: "JPY",
});

export function formatPrice(price: number): string {
    return jpyCurrencyFormatter.format(price);
}

export function formatCurrency(price: number): string {
    return formatPrice(price).replace("￥", "¥");
}
