import { Head, usePage } from "@inertiajs/react";
import LegalLayout from "@/Layouts/LegalLayout";
import LegalSection from "@/Components/Legal/LegalSection";
import SeoHead from '@/Components/SeoHead';
import { useSeo } from '@/Hooks/useSeo';
import type { PageProps } from '@/types/common';

export default function Transactions() {
    const { generateMetadata } = useSeo();
    const { legal } = usePage<PageProps>().props;

    const metadata = generateMetadata({
        title: '特定商取引法に基づく表記',
        description: '特定商取引法に基づく表記ページです。事業者情報、返品・交換についての情報を掲載しています。',
    });

    return (
        <>
            <SeoHead metadata={metadata} />
            <Head />

            <LegalLayout title="特定商取引法に基づく表記 -プラットフォーム運営者-" lastUpdated="2026年3月13日">
                <LegalSection title="販売業者" id="section-1">
                    <p>{legal.companyName}</p>
                </LegalSection>

                <LegalSection title="運営統括責任者:代表取締役" id="section-2">
                    <p>{legal.representative}</p>
                </LegalSection>

                <LegalSection title="所在地" id="section-3">
                    <p>
                        {legal.postalCode}
                        <br />
                        {legal.address}
                        {legal.addressExtra ? (
                            <>
                                <br />
                                {legal.addressExtra}
                            </>
                        ) : null}
                    </p>
                </LegalSection>

                <LegalSection title="お問い合わせ先" id="section-4">
                    <p>
                        メールアドレス:{legal.contactEmail}
                        <br />
                        電話番号:請求があった場合、遅滞なく開示いたします。
                        <br />
                        受付時間:{legal.businessHours}
                    </p>
                </LegalSection>

                <LegalSection title="販売価格について" id="section-5">
                    <p>各商品ページに表示された価格は、消費税およびシステム利用料を含んだ金額です。</p>
                </LegalSection>

                <LegalSection title="販売数量の制限等" id="section-6">
                    <p>特に記載がない場合、販売数量の制限は設けておりません。</p>
                </LegalSection>

                <LegalSection title="商品のお渡し時間" id="section-7">
                    <p>
                        フードオーダーについては、ご注文確定後、テナントでの調理時間を含めてのお渡しとなります。
                        <br />
                        混雑状況によって前後する場合がありますので、ご了承ください。
                        <br />
                        注文番号呼び出しから5分以内の受取を推奨します。
                        <br />
                        注文確定後1時間以内に受取がない場合はキャンセル扱いとなります。
                    </p>
                </LegalSection>

                <LegalSection title="お支払い方法" id="section-8">
                    <p>現在、以下のお支払い方法がご利用いただけます。</p>
                    <p>クレジットカード: Visa、Mastercard、JCB、American Express、Diners Club、Discover</p>
                    <p>PayPay</p>
                    <p>決済代行:fincode by GMO（GMOイプシロン株式会社提供・PCI DSS完全準拠・ISO27001認証）</p>
                </LegalSection>

                <LegalSection title="お支払い時期" id="section-9">
                    <p>ご注文確定時に決済確定となります。</p>
                </LegalSection>

                <LegalSection title="返金について" id="section-10">
                    <p>以下の場合に該当取引の全額を返金いたします。</p>
                    <ul className="list-disc list-inside space-y-2 ml-4">
                        <li>システム不良によりサービスが利用できなかった場合</li>
                        <li>店舗都合により商品の提供が不可能となった場合(食材切れ、設備故障等)</li>
                        <li>その他当社の責に帰すべき事由により商品をお渡しできない場合</li>
                    </ul>
                    <p className="font-semibold">テナント責任による返金の場合</p>
                    <p>
                        テナント側に責任がある問題(異物混入、商品の品質問題、注文内容との相違、食中毒等)が発生した場合は、当社が仲裁を行い、適切な対応を実施いたします。
                    </p>
                    <ul className="list-disc list-inside space-y-2 ml-4">
                        <li>お客様からのご連絡を受け、当社がテナントと事実確認を行います</li>
                        <li>テナント責任が認められた場合、当社がお客様への返金処理を代行いたします</li>
                        <li>返金決定後、テナントに対して適切な指導・改善要求を行います</li>
                        <li>重大な問題が繰り返される場合は、契約解除等の措置を講じることがあります</li>
                    </ul>
                    <p>返金処理は原則として7営業日以内に実施いたします。</p>
                </LegalSection>

                <LegalSection title="キャンセルについて" id="section-11">
                    <p>
                        ご注文確定後はキャンセルできませんので、ご注意ください。
                        <br />
                        ただし、当社又は店舗に帰責事由がある場合はキャンセル可能です。詳細は「返金について」の項目をご参照ください。
                    </p>
                </LegalSection>

                <LegalSection title="返品・交換について" id="section-12">
                    <p>
                        食品という商品の性質上、原則として返品・交換はお受けしておりません。
                        <br />
                        万が一商品に問題があった場合は、お受け取り時にテナントスタッフにお申し出ください。
                    </p>
                </LegalSection>

                <LegalSection title="推奨ブラウザ" id="section-13">
                    <p>Google Chrome、Safari、Firefox</p>
                </LegalSection>
            </LegalLayout>
        </>
    );
}
