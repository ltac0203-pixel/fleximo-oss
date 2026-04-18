import { Dialog, DialogPanel, Transition, TransitionChild } from "@headlessui/react";
import { withStableKeys } from "@/Utils/stableKeys";
import { Fragment } from "react";
import { HelpSection } from "@/data/tenantHelpContent";

interface HelpPanelProps {
    open: boolean;
    onClose: () => void;
    content: HelpSection;
}

export default function HelpPanel({ open, onClose, content }: HelpPanelProps) {
    const tips = withStableKeys(content.tips, (tip) => `${content.title}|${tip}`);

    return (
        <Transition show={open} as={Fragment}>
            <Dialog as="div" className="relative z-40" onClose={onClose}>
                <TransitionChild
                    as={Fragment}
                    enter="ease-in-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in-out duration-300"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-black/30" />
                </TransitionChild>

                <div className="fixed inset-0 overflow-hidden">
                    <div className="absolute inset-0 overflow-hidden">
                        <div className="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                            <TransitionChild
                                as={Fragment}
                                enter="transform transition ease-in-out duration-300"
                                enterFrom="translate-x-full"
                                enterTo="translate-x-0"
                                leave="transform transition ease-in-out duration-300"
                                leaveFrom="translate-x-0"
                                leaveTo="translate-x-full"
                            >
                                <DialogPanel className="pointer-events-auto w-screen max-w-md">
                                    <div className="flex h-full flex-col bg-white shadow-xl">
                                        <div className="flex items-center justify-between border-b px-4 py-3">
                                            <h2 className="text-lg font-semibold text-ink">{content.title}</h2>
                                            <button
                                                type="button"
                                                onClick={onClose}
                                                className="text-muted-light hover:text-ink-light"
                                                aria-label="閉じる"
                                            >
                                                <svg
                                                    className="h-5 w-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    strokeWidth="2"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        d="M6 18L18 6M6 6l12 12"
                                                    />
                                                </svg>
                                            </button>
                                        </div>

                                        <div className="flex-1 overflow-y-auto px-4 py-6">
                                            <p className="text-sm text-ink-light leading-relaxed">
                                                {content.description}
                                            </p>

                                            <div className="mt-6">
                                                <h3 className="text-sm font-medium text-ink mb-3">ポイント</h3>
                                                <ul className="space-y-2">
                                                    {tips.map(({ item: tip, key }) => (
                                                        <li key={key} className="flex items-start gap-2 text-sm text-ink-light">
                                                            <span className="mt-1 block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-primary" />
                                                            {tip}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </div>
            </Dialog>
        </Transition>
    );
}
