import { Head, usePage } from "@inertiajs/react";
import LegalLayout from "@/Layouts/LegalLayout";
import LegalSection from "@/Components/Legal/LegalSection";
import SeoHead from '@/Components/SeoHead';
import { useSeo } from '@/Hooks/useSeo';
import type { LegalConfig, SiteConfig } from '@/types/common';

export default function PrivacyPolicy() {
    const { generateMetadata } = useSeo();
    const { legal, siteConfig } = usePage<{ legal: LegalConfig; siteConfig: SiteConfig }>().props;

    const metadata = generateMetadata({
        title: 'プライバシーポリシー',
        description: 'プライバシーポリシーページです。個人情報の取り扱いについて説明しています。',
    });

    return (
        <>
            <SeoHead metadata={metadata} />
            <Head />

            <LegalLayout title="プライバシーポリシー" lastUpdated="2026年3月13日">
                <LegalSection title="第1条　はじめに" id="section-1">
                    <p>
                        {legal.companyName}(以下「当社」)は、{siteConfig.name}サービスにおいて、皆様の大切な個人情報を適切に保護し、安全に管理することをお約束します。このポリシーでは、当社がどのように個人情報を取り扱うかについて説明しています。
                    </p>
                </LegalSection>

                <LegalSection title="第2条　お預かりする個人情報" id="section-2">
                    <p>当社は、以下の個人情報をお預かりすることがあります:</p>
                    <ul className="list-disc list-inside space-y-2 ml-4">
                        <li>お名前</li>
                        <li>メールアドレス</li>
                        <li>IPアドレス</li>
                        <li>お支払い情報</li>
                        <li>ご利用履歴</li>
                        <li>お使いのデバイス情報</li>
                    </ul>
                </LegalSection>

                <LegalSection title="第3条　個人情報の利用目的" id="section-3">
                    <p>お預かりした個人情報は、以下の目的でのみ利用させていただきます:</p>
                    <ul className="list-disc list-inside space-y-2 ml-4">
                        <li>サービスの提供・運営</li>
                        <li>ユーザー認証</li>
                        <li>ご注文処理およびお支払い</li>
                        <li>サービス改善のための分析</li>
                        <li>
                            重要なお知らせの送信(利用規約改定、システム障害情報、セキュリティに関する緊急通知など、サービス利用に必要不可欠な情報のみ。マーケティング目的では使用いたしません)
                        </li>
                        <li>お問い合わせへの対応</li>
                    </ul>
                </LegalSection>

                <LegalSection title="第4条　クッキー(Cookie)の利用について" id="section-4">
                    <p>当社は、{siteConfig.name}サービスの運営において、以下の目的でクッキー(Cookie)を利用します:</p>
                    <ul className="list-disc list-inside space-y-2 ml-4">
                        <li>ユーザーのログイン状態の維持</li>
                        <li>サービスの安全性確保(セキュリティ機能の提供)</li>
                        <li>ユーザー体験の向上(設定情報の保存)</li>
                    </ul>

                    <h3 className="text-xl font-semibold text-slate-800 mt-6 mb-3">セッションクッキーの保存期間</h3>
                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse border border-slate-300 my-6">
                            <thead>
                                <tr className="bg-slate-100">
                                    <th className="border border-slate-300 px-4 py-2 text-left font-semibold text-slate-900">
                                        クッキーの種類
                                    </th>
                                    <th className="border border-slate-300 px-4 py-2 text-left font-semibold text-slate-900">
                                        目的
                                    </th>
                                    <th className="border border-slate-300 px-4 py-2 text-left font-semibold text-slate-900">
                                        保存期間
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td className="border border-slate-300 px-4 py-2">セッションクッキー</td>
                                    <td className="border border-slate-300 px-4 py-2">
                                        ログイン状態の維持、セキュリティ機能
                                    </td>
                                    <td className="border border-slate-300 px-4 py-2">ブラウザ終了時まで</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 className="text-xl font-semibold text-slate-800 mt-6 mb-3">クッキーの拒否方法</h3>
                    <p>
                        お客様は、ブラウザの設定によりクッキーを拒否することができます。ただし、クッキーを無効にされた場合、サービスの一部機能が正常に動作しない可能性があります。
                    </p>
                    <p>詳細な設定方法については、各ブラウザのヘルプページをご参照ください。</p>
                </LegalSection>

                <LegalSection title="第5条　個人情報の第三者提供について" id="section-5">
                    <p>当社は、以下の場合を除き、お預かりした個人情報を第三者に提供することはありません:</p>
                    <ul className="list-disc list-inside space-y-2 ml-4">
                        <li>お客様の同意をいただいた場合</li>
                        <li>法律で必要とされる場合</li>
                        <li>人命や財産の保護のために必要な場合</li>
                        <li>公衆衛生や児童の健全育成のために特に必要な場合</li>
                        <li>国や地方公共団体の法令に基づく業務に協力する必要がある場合</li>
                        <li>
                            サービスの提供に必要な範囲で、業務委託先に個人情報を提供することがあります。例えば、決済処理のためにGMOイプシロン株式会社が提供する決済代行サービス「fincode
                            by
                            GMO」（PCI DSS完全準拠・ISO27001認証・プライバシーマーク取得）に、また、サービスの安定的な提供のためにサーバー管理会社「xserver」に必要な情報を提供する場合があります。これらの委託先に対しては、契約により適切な安全管理措置を義務付けます。
                        </li>
                    </ul>
                    <p>
                        なお、クレジットカード情報については、当社では一切保持いたしません。決済処理は全てGMOイプシロン株式会社が提供するPCI DSS完全準拠の決済代行サービス「fincode
                        by
                        GMO」にて安全に処理され、クレジットカード番号はトークン化されて管理されます。また、クレジットカード決済においてはEMV 3-Dセキュア（本人認証サービス）による認証を実施しています。これにより、お客様のクレジットカード情報の安全性を最大限に確保しています。
                    </p>
                </LegalSection>

                <LegalSection title="第6条　個人情報の共同利用について" id="section-6">
                    <p>
                        現在、当社では個人情報の共同利用は行っておりません。将来的に共同利用を行う場合は、以下の事項を事前に通知いたします:
                    </p>
                    <ul className="list-disc list-inside space-y-2 ml-4">
                        <li>共同利用する個人情報の項目</li>
                        <li>共同利用者の範囲</li>
                        <li>共同利用の目的</li>
                        <li>個人情報の管理について責任を有する者の氏名または名称</li>
                    </ul>
                </LegalSection>

                <LegalSection title="第7条　外部解析ツールの利用について" id="section-7">
                    <p>
                        当社は、お客様の利便性向上およびサービス改善を目的としたアクセス状況の把握、または広告効果の測定といった目的で外部のアクセス解析ツールや広告効果測定ツールを使用しておりません。お客様の個人情報をこれらの外部ツールと連携させることはありません。
                    </p>
                </LegalSection>

                <LegalSection title="第8条　個人情報の安全管理" id="section-8">
                    <p>
                        当社は、お預かりした個人情報を漏洩や紛失から守るため、適切なセキュリティ対策を講じています。具体的には、通信の暗号化のために「クラウドセキュア
                        企業認証SSL」を導入し、不正アクセスを防ぐために「WAF(Web Application
                        Firewall)」を利用しています。また、クレジットカード決済においてはEMV 3-Dセキュア（本人認証サービス）による認証を実施し、不正利用の防止に努めています。常に最新の技術と管理体制で、お客様の情報を大切に保護します。
                    </p>
                </LegalSection>

                <LegalSection title="第9条　個人情報の保存期間" id="section-9">
                    <p>
                        当社は、個人情報を以下の期間保存いたします。法令により保存が義務付けられている情報については、法定保存期間に従い適切に管理いたします。
                    </p>

                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse border border-slate-300 my-6">
                            <thead>
                                <tr className="bg-slate-100">
                                    <th className="border border-slate-300 px-4 py-2 text-left font-semibold text-slate-900">
                                        情報の種類
                                    </th>
                                    <th className="border border-slate-300 px-4 py-2 text-left font-semibold text-slate-900">
                                        保存期間
                                    </th>
                                    <th className="border border-slate-300 px-4 py-2 text-left font-semibold text-slate-900">
                                        根拠・理由
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td className="border border-slate-300 px-4 py-2">
                                        アカウント情報(お名前、メールアドレス等)
                                    </td>
                                    <td className="border border-slate-300 px-4 py-2">最終ログインから2年間</td>
                                    <td className="border border-slate-300 px-4 py-2">サービス利用のため</td>
                                </tr>
                                <tr>
                                    <td className="border border-slate-300 px-4 py-2">決済・取引記録</td>
                                    <td className="border border-slate-300 px-4 py-2">7年間</td>
                                    <td className="border border-slate-300 px-4 py-2">
                                        法人税法、消費税法等の法定保存義務
                                    </td>
                                </tr>
                                <tr>
                                    <td className="border border-slate-300 px-4 py-2">アクセスログ・システムログ</td>
                                    <td className="border border-slate-300 px-4 py-2">5年間</td>
                                    <td className="border border-slate-300 px-4 py-2">
                                        セキュリティ確保・不正利用防止のため
                                    </td>
                                </tr>
                                <tr>
                                    <td className="border border-slate-300 px-4 py-2">お問い合わせ履歴</td>
                                    <td className="border border-slate-300 px-4 py-2">3年間</td>
                                    <td className="border border-slate-300 px-4 py-2">
                                        サポート品質向上・紛争解決のため
                                    </td>
                                </tr>
                                <tr>
                                    <td className="border border-slate-300 px-4 py-2">IPアドレス・デバイス情報</td>
                                    <td className="border border-slate-300 px-4 py-2">セッション終了まで（アクセスログとしては5年間保存）</td>
                                    <td className="border border-slate-300 px-4 py-2">
                                        セキュリティ確保・不正利用防止のため
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p>
                        ※
                        上記期間経過後、またはお客様からアカウント削除のお申し出があった場合は、法令で保存が義務付けられている情報を除き、個人を特定できない形で統計データとして利用するか、速やかに削除いたします。
                    </p>
                </LegalSection>

                <LegalSection title="第10条　個人情報の開示・訂正・利用停止" id="section-10">
                    <p>
                        お客様は、ご自身の個人情報について、開示や訂正、利用停止、削除などを求めることができます。ご希望の場合は、お問い合わせフォームよりご連絡ください。迅速に対応いたします。
                    </p>
                    <p>
                        ただし、法令により保存が義務付けられている情報については、法定保存期間中は削除できない場合がございますので、あらかじめご了承ください。
                    </p>
                </LegalSection>

                <LegalSection title="第11条　プライバシーポリシーの更新" id="section-11">
                    <p>
                        当社は、必要に応じてこのポリシーを更新することがあります。更新した場合は、このページでお知らせし、更新された内容はお知らせ時点から有効となります。定期的にご確認いただくことをおすすめします。
                    </p>
                </LegalSection>

                <LegalSection title="第12条　お問い合わせ先" id="section-12">
                    <p>このポリシーに関するご質問やご不明点は、以下の窓口までお気軽にお問い合わせください。</p>
                    <address className="not-italic text-slate-700 mt-4 pl-4 border-l-2 border-slate-300">
                        {legal.companyName}
                        <br />
                        住所:〒{legal.postalCode} {legal.address}
                        {legal.addressExtra ? ` ${legal.addressExtra}` : ''}
                        <br />
                        メール:{legal.contactEmail}
                    </address>
                </LegalSection>
            </LegalLayout>
        </>
    );
}
