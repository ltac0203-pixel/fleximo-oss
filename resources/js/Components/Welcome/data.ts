import type {
    FaqItem,
    FeatureItem,
    FooterLink,
    HowItWorksStep,
    StatItem,
    UseSceneItem,
} from "./types";

export const stats: ReadonlyArray<StatItem> = [
    {
        value: "8分",
        label: "平均待ち時間の短縮",
        description: "列に並ぶ時間を抑えて、食事の時間をもっと自由に。",
    },
    {
        value: "30秒",
        label: "かんたん会員登録",
        description: "アプリ不要で、思い立ったその場から使い始められます。",
    },
];

export const useScenes: ReadonlyArray<UseSceneItem> = [
    {
        title: "スキマ時間にさっと注文",
        body: "次の予定まで時間がなくても、移動しながらメニュー確認とPayPay・カード決済まで完了できます。",
        status: "スキマ時間に最適",
        iconKey: "between-classes",
    },
    {
        title: "混雑時でも席からオーダー",
        body: "お店の列に離脱せず、空いている席から落ち着いてメニューを選べます。",
        status: "行列ストレスを軽減",
        iconKey: "from-seat",
    },
    {
        title: "受け取りだけに集中できる",
        body: "現金のやり取りを挟まず、出来上がりを確認したらそのまま受け取りへ進めます。",
        status: "キャッシュレス対応",
        iconKey: "pickup-fast",
    },
];

export const howItWorksSteps: ReadonlyArray<HowItWorksStep> = [
    {
        step: 1,
        title: "QRコードをスキャン",
        body: "店内のQRコードをスマホで読み取るだけ。アプリのインストールは不要です。",
        iconKey: "qr-scan",
    },
    {
        step: 2,
        title: "メニューを選ぶ",
        body: "スマホ画面でメニューを見ながらタップで注文。写真付きで迷わず選べます。",
        iconKey: "menu-select",
    },
    {
        step: 3,
        title: "決済して受け取り",
        body: "PayPay・クレジットカードでスマホ決済。出来上がったら通知でお知らせします。",
        iconKey: "checkout",
    },
];

export const features: ReadonlyArray<FeatureItem> = [
    {
        eyebrow: "Order from anywhere",
        title: "並ばず注文",
        body: "QRコードを読み取れば、スマホからすぐに注文。混んでる時間帯でも、席で注文完了できます。",
        meta: "待ち時間短縮",
        iconKey: "order",
    },
    {
        eyebrow: "Track your order",
        title: "注文状況がわかる",
        body: "注文した料理がいま調理中なのか、もうすぐ出来上がるのか、スマホでリアルタイムに確認できます。",
        meta: "リアルタイム通知",
        iconKey: "status",
    },
    {
        eyebrow: "Cashless checkout",
        title: "スマホで決済完了",
        body: "PayPayやクレジットカードでキャッシュレス決済。小銭を数える手間も、おつりを待つ時間もゼロに。",
        meta: "キャッシュレス対応",
        iconKey: "payment",
    },
];

export const serviceLinks: ReadonlyArray<FooterLink> = [
    { label: "機能一覧", href: "/for-business#features" },
    { label: "料金プラン", href: "/for-business#pricing" },
    { label: "導入事例", href: "/for-business#proof" },
    { label: "よくある質問", href: "/for-business#faq" },
];

export const faqItems: ReadonlyArray<FaqItem> = [
    {
        question: "利用料はかかりますか？",
        answer: "会員登録と注文サービスの利用は無料です。お支払いいただくのは商品代金のみで、月額費用や手数料は発生しません。",
    },
    {
        question: "スマホアプリのインストールは必要ですか？",
        answer: "不要です。お店のQRコードを読み取れば、そのままブラウザ上でメニューの閲覧から決済まで完結できます。",
    },
    {
        question: "支払い方法は何が使えますか？",
        answer: "PayPayとクレジットカード（Visa / Mastercard / JCB / American Express / Diners）に対応しています。",
    },
    {
        question: "会員登録せずに使えますか？",
        answer: "メニューの閲覧は会員登録なしでご利用いただけます。注文と決済に進むには、無料の会員登録が必要です。",
    },
    {
        question: "登録にはどれくらい時間がかかりますか？",
        answer: "メールアドレスとパスワードの入力だけで、約30秒で完了します。1つのアカウントで対応する全ての店舗をご利用いただけます。",
    },
    {
        question: "利用できる店舗はどこですか？",
        answer: "導入店舗は順次拡大中です。ご利用いただける店舗では、店内やテーブルにQRコードが設置されています。",
    },
];

export const supportLinks: ReadonlyArray<FooterLink> = [
    { label: "お問い合わせ", routeName: "contact.index" },
    { label: "事業者様向け", routeName: "for-business.index" },
    { label: "利用規約", routeName: "legal.terms" },
    { label: "プライバシーポリシー", routeName: "legal.privacy-policy" },
];
