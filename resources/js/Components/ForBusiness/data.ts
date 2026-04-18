import type {
    FaqEntry,
    FlowStep,
    ImpactStat,
    PricingItem,
    PrimaryFeature,
    ProblemItem,
    SecondaryFeature,
} from "@/Components/ForBusiness/types";

export const primaryFeatures: ReadonlyArray<PrimaryFeature> = [
    {
        title: "QRコード注文",
        body: "お客様のスマホがそのまま注文端末に。アプリ不要・QRコードを読み取るだけで注文完了。レジ待ちの行列を解消します。",
        iconKey: "qr-order",
    },
    {
        title: "KDS（キッチンディスプレイ）",
        body: "注文をリアルタイムでキッチンに表示。紙伝票の読み間違いや伝達ミスをゼロにし、調理効率を大幅に向上させます。",
        iconKey: "kds",
    },
    {
        title: "キャッシュレス決済",
        body: "PayPay・クレジットカード（VISA, Mastercard, JCB, AMEX, Diners Club, Discover）対応。現金管理の手間を削減し、会計業務を効率化します。",
        iconKey: "cashless",
    },
];

export const secondaryFeatures: ReadonlyArray<SecondaryFeature> = [
    {
        title: "売上分析",
        body: "リアルタイムダッシュボードでメニュー別・時間帯別の売上を分析。データに基づいた経営判断を支援します。",
    },
    {
        title: "スタッフ管理",
        body: "管理者とスタッフの権限分離で安全に運用。複数スタッフでの同時オペレーションにも対応。",
    },
    {
        title: "初期費用0円",
        body: "専用端末不要。既存のスマホ・タブレットで導入可能。リスクゼロで始められます。",
    },
];

export const impactStats: ReadonlyArray<ImpactStat> = [
    { value: "40%", label: "回転率 向上" },
    { value: "90%", label: "注文ミス 削減" },
    { value: "0円", label: "初期費用" },
];

export const problemItems: ReadonlyArray<ProblemItem> = [
    {
        question: "ピーク時の注文に限界を感じていませんか？",
        solution: "QRコード注文で、お客様自身のスマホから同時注文。ボトルネックを解消します。",
    },
    {
        question: "注文ミスや伝達ミスが起きていませんか？",
        solution: "デジタル注文＋KDS表示で、聞き間違い・書き間違いをゼロに。",
    },
    {
        question: "現金管理に時間を取られていませんか？",
        solution: "キャッシュレス決済で現金管理の手間を削減。締め作業も効率化。",
    },
];

export const faqs: ReadonlyArray<FaqEntry> = [
    {
        question: "初期費用や月額固定費はかかりますか？",
        answer: "いいえ、初期費用・月額固定費は一切かかりません。売上に応じた手数料のみでご利用いただけます。",
    },
    {
        question: "専用の端末は必要ですか？",
        answer: "いいえ、専用端末は不要です。既存のスマートフォンやタブレットでご利用いただけます。インターネット接続があれば、すぐに始められます。",
    },
    {
        question: "どのような決済方法に対応していますか？",
        answer: "クレジットカード（VISA、Mastercard、JCB、American Express、Diners Club、Discover）とPayPayに対応しています。3Dセキュア認証にも対応し、安全な決済環境を提供しています。",
    },
    {
        question: "導入までどのくらいかかりますか？",
        answer: "お申し込みから審査完了まで通常数営業日です。承認後は、メニュー登録などの初期設定を行えばすぐに運用を開始できます。",
    },
    {
        question: "複数店舗での運用は可能ですか？",
        answer: "はい、可能です。店舗ごとにテナントを作成し、それぞれ独立して運用できます。複数店舗の売上も一元管理できます。",
    },
    {
        question: "解約はいつでもできますか？",
        answer: "はい、解約はいつでも可能です。最低契約期間や解約金はありません。お気軽にお試しいただけます。",
    },
];

export const trustedBrands: ReadonlyArray<string> = [
    "VISA",
    "Mastercard",
    "JCB",
    "AMEX",
    "Diners Club",
    "Discover",
    "PayPay",
];

export const conventionalPricingItems: ReadonlyArray<PricingItem> = [
    { label: "初期費用", value: "10万〜50万円" },
    { label: "月額固定費", value: "1万〜5万円" },
    { label: "専用端末", value: "必要" },
];

export const fleximoPricingItems: ReadonlyArray<PricingItem> = [
    { label: "初期費用", value: "0円" },
    { label: "月額固定費", value: "0円" },
    { label: "専用端末", value: "不要" },
    { label: "導入サポート", value: "無料" },
];

export const flowSteps: ReadonlyArray<FlowStep> = [
    {
        number: 1,
        title: "お申し込み",
        body: "Webフォームから必要事項を入力。",
        time: "約5分",
    },
    {
        number: 2,
        title: "審査",
        body: "申請内容を確認し、結果をご連絡。",
        time: "数営業日",
    },
    {
        number: 3,
        title: "初期設定",
        body: "メニュー登録や店舗設定を実施。",
        time: "約1日",
    },
    {
        number: 4,
        title: "運用開始",
        body: "QRコードを設置して注文受付開始。",
        time: "即日",
    },
];

export const ctaHighlights: ReadonlyArray<string> = ["初期費用0円", "月額0円", "解約いつでも可能"];
