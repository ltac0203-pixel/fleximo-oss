import { Dialog, DialogPanel, DialogTitle, Transition, TransitionChild } from "@headlessui/react";
import { router } from "@inertiajs/react";
import { Fragment, useState } from "react";
import { withStableKeys } from "@/Utils/stableKeys";
import { OnboardingStep } from "./onboarding.types";

interface OnboardingTourProps {
    open: boolean;
    onClose: () => void;
    steps: OnboardingStep[];
    // 初回自動起動の場合は true、ユーザーが手動で開き直した場合は false。
    // true のときのみサーバーに完了状態を記録する（手動再閲覧では再記録不要）。
    persistOnClose?: boolean;
}

export default function OnboardingTour({ open, onClose, steps, persistOnClose = true }: OnboardingTourProps) {
    const [currentStep, setCurrentStep] = useState(0);
    const [submitting, setSubmitting] = useState(false);

    if (steps.length === 0) {
        return null;
    }

    const total = steps.length;
    const step = steps[Math.min(currentStep, total - 1)];
    const isFirst = currentStep === 0;
    const isLast = currentStep === total - 1;

    const close = () => {
        setCurrentStep(0);
        onClose();
    };

    const finish = () => {
        if (submitting) return;
        if (!persistOnClose) {
            close();
            return;
        }
        setSubmitting(true);
        router.post(
            route("onboarding.complete"),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setSubmitting(false),
                onSuccess: () => close(),
            },
        );
    };

    const tips = step.tips ? withStableKeys(step.tips, (tip) => `${step.title}|${tip}`) : [];
    const dotKeys = withStableKeys(steps, (s) => s.title);

    return (
        <Transition show={open} as={Fragment}>
            <Dialog as="div" className="relative z-50" onClose={finish}>
                <TransitionChild
                    as={Fragment}
                    enter="ease-out duration-200"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-150"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-black/50" />
                </TransitionChild>

                <div className="fixed inset-0 overflow-y-auto">
                    <div className="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as={Fragment}
                            enter="ease-out duration-200"
                            enterFrom="opacity-0 scale-95"
                            enterTo="opacity-100 scale-100"
                            leave="ease-in duration-150"
                            leaveFrom="opacity-100 scale-100"
                            leaveTo="opacity-0 scale-95"
                        >
                            <DialogPanel className="w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl">
                                <div className="flex items-center justify-between border-b px-6 py-4">
                                    <span className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                        ガイド {currentStep + 1} / {total}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={finish}
                                        disabled={submitting}
                                        className="text-sm text-slate-500 hover:text-slate-700 disabled:opacity-50"
                                    >
                                        スキップ
                                    </button>
                                </div>

                                <div className="px-6 py-6">
                                    <DialogTitle className="text-xl font-semibold text-slate-900">
                                        {step.title}
                                    </DialogTitle>
                                    <p className="mt-3 text-sm leading-relaxed text-slate-600">
                                        {step.description}
                                    </p>

                                    {tips.length > 0 && (
                                        <ul className="mt-5 space-y-2">
                                            {tips.map(({ item: tip, key }) => (
                                                <li key={key} className="flex items-start gap-2 text-sm text-slate-700">
                                                    <span className="mt-1.5 block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-primary" />
                                                    <span>{tip}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>

                                <div className="flex items-center justify-between border-t bg-slate-50 px-6 py-4">
                                    <div className="flex items-center gap-1.5" aria-hidden="true">
                                        {dotKeys.map(({ key }, i) => (
                                            <span
                                                key={key}
                                                className={`h-2 w-2 rounded-full ${
                                                    i === currentStep ? "bg-primary" : "bg-slate-300"
                                                }`}
                                            />
                                        ))}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setCurrentStep((s) => Math.max(0, s - 1))}
                                            disabled={isFirst || submitting}
                                            className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            戻る
                                        </button>
                                        {isLast ? (
                                            <button
                                                type="button"
                                                onClick={finish}
                                                disabled={submitting}
                                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60"
                                            >
                                                始める
                                            </button>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() => setCurrentStep((s) => Math.min(total - 1, s + 1))}
                                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
                                            >
                                                次へ
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </Transition>
    );
}
