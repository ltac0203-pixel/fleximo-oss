import { Popover, PopoverButton, PopoverPanel, Transition } from "@headlessui/react";
import { Fragment } from "react";
import { inlineHelpContent } from "@/data/inlineHelpContent";

interface InlineHelpProps {
    contentKey: keyof typeof inlineHelpContent;
    ariaLabel?: string;
}

export default function InlineHelp({ contentKey, ariaLabel }: InlineHelpProps) {
    const text = inlineHelpContent[contentKey];

    return (
        <Popover className="relative inline-block align-middle">
            <PopoverButton
                type="button"
                aria-label={ariaLabel ?? "この項目のヘルプを表示"}
                className="inline-flex items-center justify-center w-4 h-4 rounded-full border border-gray-300 bg-white text-[10px] font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                ?
            </PopoverButton>
            <Transition
                as={Fragment}
                enter="transition ease-out duration-100"
                enterFrom="opacity-0 translate-y-1"
                enterTo="opacity-100 translate-y-0"
                leave="transition ease-in duration-75"
                leaveFrom="opacity-100 translate-y-0"
                leaveTo="opacity-0 translate-y-1"
            >
                <PopoverPanel className="absolute left-0 z-30 mt-2 w-64 rounded-md border border-gray-200 bg-white p-3 text-xs leading-relaxed text-gray-700 shadow-lg">
                    {text}
                </PopoverPanel>
            </Transition>
        </Popover>
    );
}
