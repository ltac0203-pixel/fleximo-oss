import SeoHead from "@/Components/SeoHead";
import { useSeo } from "@/Hooks/useSeo";
import WideGuestLayout from "@/Layouts/WideGuestLayout";
import InputFormStep from "@/Pages/TenantApplication/Partials/InputFormStep";
import PreviewStep from "@/Pages/TenantApplication/Partials/PreviewStep";
import ProgressIndicator from "@/Pages/TenantApplication/Partials/ProgressIndicator";
import {
    BusinessTypeOption,
    StepNumber,
    TenantApplicationFormData,
    TenantApplicationFormErrors,
    TenantApplicationFormField,
} from "@/Pages/TenantApplication/types";
import { getBusinessTypeLabel, validateTenantApplicationStep1 } from "@/Pages/TenantApplication/validation";
import { PageProps } from "@/types";
import type { SeoMetadata, StructuredData } from "@/types/seo";
import { Link, useForm } from "@inertiajs/react";
import { FormEventHandler, useEffect, useMemo, useState } from "react";

interface Props extends PageProps {
    businessTypes: BusinessTypeOption[];
    seo?: Partial<SeoMetadata>;
    structuredData?: StructuredData | StructuredData[];
}

const INITIAL_FORM_DATA: TenantApplicationFormData = {
    applicant_name: "",
    applicant_email: "",
    applicant_phone: "",
    tenant_name: "",
    tenant_address: "",
    business_type: "",
    password: "",
    password_confirmation: "",
    website: "",
};

export default function Create({ businessTypes, seo, structuredData }: Props) {
    const { generateMetadata } = useSeo();
    const { data, setData, post, processing, errors } = useForm<TenantApplicationFormData>(INITIAL_FORM_DATA);

    const [currentStep, setCurrentStep] = useState<StepNumber>(1);
    const [validationErrors, setValidationErrors] = useState<TenantApplicationFormErrors>({});

    useEffect(() => {
        if (Object.keys(errors).length > 0 && currentStep === 2) {
            setCurrentStep(1);
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    }, [errors, currentStep]);

    const handleNextStep = () => {
        const validationResult = validateTenantApplicationStep1(data);

        if (validationResult.isValid) {
            setValidationErrors({});
            setCurrentStep(2);
            window.scrollTo({ top: 0, behavior: "smooth" });
            return;
        }

        setValidationErrors(validationResult.errors);

        if (validationResult.firstErrorField) {
            const element = document.getElementById(validationResult.firstErrorField);
            element?.focus();
        }
    };

    const handlePreviousStep = () => {
        setCurrentStep(1);
        setValidationErrors({});
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const setFormData = (key: TenantApplicationFormField, value: string) => {
        setData(key, value);
    };

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        post(route("tenant-application.store"));
    };

    const allErrors: TenantApplicationFormErrors = {
        ...validationErrors,
        ...(errors as TenantApplicationFormErrors),
    };

    const businessTypeLabel = useMemo(
        () => getBusinessTypeLabel(data.business_type, businessTypes),
        [data.business_type, businessTypes],
    );

    const metadata = generateMetadata(
        seo ?? {
            title: "加盟店申し込み",
            description: "Fleximoの加盟店申し込みページです。モバイルオーダー導入をオンラインで申請できます。",
        },
    );

    return (
        <>
            <SeoHead metadata={metadata} structuredData={structuredData} />

            <WideGuestLayout>
                <div className="mb-6">
                    <h1 className="text-xl font-bold text-ink">テナント申し込み</h1>
                    <p className="mt-2 text-sm text-ink-light">
                        Fleximoへのテナント登録をご希望の方は、以下のフォームにご記入ください。
                    </p>
                </div>

                <ProgressIndicator currentStep={currentStep} />

                {currentStep === 1 ? (
                    <InputFormStep
                        data={data}
                        setData={setFormData}
                        errors={allErrors}
                        businessTypes={businessTypes}
                        onNext={handleNextStep}
                        processing={processing}
                    />
                ) : (
                    <PreviewStep
                        data={data}
                        businessTypeLabel={businessTypeLabel}
                        onBack={handlePreviousStep}
                        onSubmit={submit}
                        processing={processing}
                    />
                )}

                <div className="mt-8 border-t border-edge pt-6">
                    <p className="text-center text-sm text-ink-light">
                        既にアカウントをお持ちですか？{" "}
                        <Link href={route("login")} className="font-medium text-primary-dark hover:text-primary-dark">
                            ログイン
                        </Link>
                    </p>
                </div>
            </WideGuestLayout>
        </>
    );
}
