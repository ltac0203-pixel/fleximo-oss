const jpyCurrencyFormatter = new Intl.NumberFormat("ja-JP", {
    style: "currency",
    currency: "JPY",
});

const jaNumberFormatter = new Intl.NumberFormat("ja-JP");

export function formatPrice(price: number): string {
    return jpyCurrencyFormatter.format(price);
}

export function formatCurrency(price: number): string {
    return formatPrice(price).replace("￥", "¥");
}

export function formatNumber(value: number): string {
    return jaNumberFormatter.format(value);
}
