import PrimaryButton from "@/Components/PrimaryButton";
import { TenantApplicationFormData } from "@/Pages/TenantApplication/types";
import { FormEventHandler } from "react";

interface PreviewStepProps {
    data: TenantApplicationFormData;
    businessTypeLabel: string;
    onBack: () => void;
    onSubmit: FormEventHandler<HTMLFormElement>;
    processing: boolean;
}

export default function PreviewStep({ data, businessTypeLabel, onBack, onSubmit, processing }: PreviewStepProps) {
    return (
        <div>
            <div className="mb-6">
                <h2 className="text-lg font-semibold text-ink">入力内容の確認</h2>
                <p className="mt-1 text-sm text-muted">
                    以下の内容で申し込みを送信します。内容に誤りがないかご確認ください。
                </p>
            </div>

            <div className="space-y-6">
                <div className="border border-edge bg-white p-6">
                    <h3 className="mb-4 text-base font-semibold text-ink">店舗情報</h3>
                    <dl className="space-y-3">
                        <div className="flex flex-col sm:flex-row">
                            <dt className="w-full text-sm font-medium text-muted sm:w-1/3">店舗名</dt>
                            <dd className="mt-1 text-sm text-ink sm:ml-4 sm:mt-0">{data.tenant_name}</dd>
                        </div>
                        <div className="flex flex-col sm:flex-row">
                            <dt className="w-full text-sm font-medium text-muted sm:w-1/3">業種</dt>
                            <dd className="mt-1 text-sm text-ink sm:ml-4 sm:mt-0">{businessTypeLabel}</dd>
                        </div>
                        {data.tenant_address && (
                            <div className="flex flex-col sm:flex-row">
                                <dt className="w-full text-sm font-medium text-muted sm:w-1/3">住所</dt>
                                <dd className="mt-1 text-sm text-ink sm:ml-4 sm:mt-0">{data.tenant_address}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                <div className="border border-edge bg-white p-6">
                    <h3 className="mb-4 text-base font-semibold text-ink">申請者情報</h3>
                    <dl className="space-y-3">
                        <div className="flex flex-col sm:flex-row">
                            <dt className="w-full text-sm font-medium text-muted sm:w-1/3">お名前</dt>
                            <dd className="mt-1 text-sm text-ink sm:ml-4 sm:mt-0">{data.applicant_name}</dd>
                        </div>
                        <div className="flex flex-col sm:flex-row">
                            <dt className="w-full text-sm font-medium text-muted sm:w-1/3">メールアドレス</dt>
                            <dd className="mt-1 text-sm text-ink sm:ml-4 sm:mt-0">{data.applicant_email}</dd>
                        </div>
                        <div className="flex flex-col sm:flex-row">
                            <dt className="w-full text-sm font-medium text-muted sm:w-1/3">電話番号</dt>
                            <dd className="mt-1 text-sm text-ink sm:ml-4 sm:mt-0">{data.applicant_phone}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <form onSubmit={onSubmit}>
                <div className="absolute left-[-9999px]" aria-hidden="true">
                    <input type="text" name="website" value={data.website} tabIndex={-1} autoComplete="off" readOnly />
                </div>

                <div className="mt-8 flex flex-col gap-3 sm:flex-row-reverse">
                    <PrimaryButton
                        type="submit"
                        className="flex-1 justify-center sm:flex-initial"
                        disabled={processing}
                        isBusy={processing}
                    >
                        送信する
                    </PrimaryButton>
                    <button
                        type="button"
                        onClick={onBack}
                        disabled={processing}
                        className="flex-1 border border-edge-strong bg-white px-4 py-2 text-sm font-medium text-ink-light hover:bg-surface focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 sm:flex-initial"
                    >
                        修正する
                    </button>
                </div>
            </form>

            <p className="mt-4 text-center text-sm text-muted">審査には通常1〜3営業日程度お時間をいただきます。</p>
        </div>
    );
}
