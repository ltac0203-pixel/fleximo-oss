import ApplicationLogo from "@/Components/ApplicationLogo";
import { Link } from "@inertiajs/react";
import { TEXT_LINK_BUTTON } from "@/constants/buttonStyles";
import { serviceLinks, supportLinks } from "@/Components/Welcome/data";

function WelcomeFooter() {
    return (
        <footer className="relative mt-32">
            <div className="geo-public-divider absolute left-0 right-0 top-0 h-px" />

            <div className="geo-public-shell-soft mt-8 overflow-hidden px-6 pb-8 pt-12">
                <div className="absolute inset-0 bg-grid-pattern opacity-[0.03]" />
                <div className="geo-public-orb-sky absolute -right-16 top-0 h-52 w-52 blur-3xl" />
                <div className="relative grid gap-12 md:grid-cols-2 lg:grid-cols-[minmax(0,1.1fr)_repeat(2,minmax(0,0.45fr))]">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center">
                                <ApplicationLogo className="h-12 w-12" />
                            </div>
                            <span className="text-2xl font-bold text-ink">Fleximo</span>
                        </div>
                        <p className="mt-4 max-w-md text-sm leading-relaxed text-ink-light">
                            日本の飲食店・学食・フードコート向けマルチテナント モバイルオーダー。
                            QRコード注文とPayPay・クレジットカード決済で、注文体験をスマートに変えます。
                        </p>
                        <div className="mt-6 flex flex-wrap gap-2">
                            {["アプリ不要", "QRコード注文", "キャッシュレス対応"].map((item) => (
                                <span
                                    key={item}
                                    className="rounded-full border border-sky-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-sky-700 shadow-sm"
                                >
                                    {item}
                                </span>
                            ))}
                        </div>
                    </div>

                    <div>
                        <h4 className="text-sm font-semibold uppercase tracking-widest text-muted-light">
                            サービス
                        </h4>
                        <ul className="mt-4 space-y-3">
                            {serviceLinks.map((link) => (
                                <li key={link.label}>
                                    <a href={link.href} className={TEXT_LINK_BUTTON}>
                                        {link.label}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div>
                        <h4 className="text-sm font-semibold uppercase tracking-widest text-muted-light">
                            サポート
                        </h4>
                        <ul className="mt-4 space-y-3">
                            {supportLinks.map((link) => (
                                <li key={link.label}>
                                    <Link
                                        href={route(link.routeName!)}
                                        className={TEXT_LINK_BUTTON}
                                    >
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                <div className="relative mt-12 flex flex-col items-center justify-between gap-4 border-t border-surface-dim pt-8 sm:flex-row">
                    <p className="text-sm text-muted">&copy; 2026 Fleximo. All rights reserved.</p>
                    <div className="flex items-center gap-6">
                        <span className="flex items-center gap-2 text-sm text-muted">
                            <span className="flex h-2 w-2 rounded-full bg-green-500" />
                            All systems operational
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    );
}

export default WelcomeFooter;
