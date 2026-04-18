import { StepNumber } from "@/Pages/TenantApplication/types";

interface ProgressIndicatorProps {
    currentStep: StepNumber;
}

export default function ProgressIndicator({ currentStep }: ProgressIndicatorProps) {
    return (
        <div className="mb-8" aria-label="申し込みプロセスの進行状況">
            <div className="flex items-center justify-center">
                <div className="flex items-center">
                    <div className="flex items-center">
                        <div
                            className={`flex h-10 w-10 items-center justify-center rounded-full border-2 ${
                                currentStep === 1
                                    ? "border-primary-dark bg-primary-dark text-white"
                                    : "border-primary-dark bg-white text-primary-dark"
                            }`}
                        >
                            <span className="font-semibold">1</span>
                        </div>
                        <div className="ml-3">
                            <p
                                className={`text-sm font-medium ${currentStep === 1 ? "text-primary-dark" : "text-muted"}`}
                            >
                                入力
                            </p>
                        </div>
                    </div>

                    <div className="mx-4 h-0.5 w-16 bg-edge-strong"></div>

                    <div className="flex items-center">
                        <div
                            className={`flex h-10 w-10 items-center justify-center rounded-full border-2 ${
                                currentStep === 2
                                    ? "border-primary-dark bg-primary-dark text-white"
                                    : "border-edge-strong bg-white text-muted-light"
                            }`}
                        >
                            <span className="font-semibold">2</span>
                        </div>
                        <div className="ml-3">
                            <p
                                className={`text-sm font-medium ${currentStep === 2 ? "text-primary-dark" : "text-muted"}`}
                            >
                                確認
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
