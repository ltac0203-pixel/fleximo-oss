export interface AllergenItem {
    bit: number;
    label: string;
}

// 特定原材料8品目（表示義務）
export const MANDATORY_ALLERGENS: AllergenItem[] = [
    { bit: 1, label: "えび" },
    { bit: 2, label: "かに" },
    { bit: 4, label: "くるみ" },
    { bit: 8, label: "小麦" },
    { bit: 16, label: "そば" },
    { bit: 32, label: "卵" },
    { bit: 64, label: "乳" },
    { bit: 128, label: "落花生" },
];

// 推奨表示20品目
export const ADVISORY_ALLERGENS: AllergenItem[] = [
    { bit: 1, label: "アーモンド" },
    { bit: 2, label: "あわび" },
    { bit: 4, label: "いか" },
    { bit: 8, label: "いくら" },
    { bit: 16, label: "オレンジ" },
    { bit: 32, label: "カシューナッツ" },
    { bit: 64, label: "キウイフルーツ" },
    { bit: 128, label: "牛肉" },
    { bit: 256, label: "ごま" },
    { bit: 512, label: "さけ" },
    { bit: 1024, label: "さば" },
    { bit: 2048, label: "大豆" },
    { bit: 4096, label: "鶏肉" },
    { bit: 8192, label: "バナナ" },
    { bit: 16384, label: "豚肉" },
    { bit: 32768, label: "まつたけ" },
    { bit: 65536, label: "もも" },
    { bit: 131072, label: "やまいも" },
    { bit: 262144, label: "りんご" },
    { bit: 524288, label: "ゼラチン" },
];

export interface NutritionField {
    key: string;
    label: string;
    unit: string;
}

export const NUTRITION_FIELDS: NutritionField[] = [
    { key: "energy", label: "エネルギー", unit: "kcal" },
    { key: "protein", label: "たんぱく質", unit: "g" },
    { key: "fat", label: "脂質", unit: "g" },
    { key: "carbohydrate", label: "炭水化物", unit: "g" },
    { key: "salt", label: "食塩相当量", unit: "g" },
];

// ビットマスクから該当するラベル配列を取得
export function getLabelsFromBitmask(bitmask: number, items: AllergenItem[]): string[] {
    return items.filter((item) => (bitmask & item.bit) !== 0).map((item) => item.label);
}

// ビットマスクの特定ビットをトグル
export function toggleBit(current: number, bit: number): number {
    return current ^ bit;
}

// ビットマスクに特定ビットが含まれるか判定
export function hasBit(bitmask: number, bit: number): boolean {
    return (bitmask & bit) !== 0;
}
