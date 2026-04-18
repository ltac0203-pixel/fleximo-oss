import { Dialog, DialogPanel, Transition, TransitionChild } from "@headlessui/react";
import { PropsWithChildren } from "react";
import { createPortal } from "react-dom";

export default function Modal({
    children,
    show = false,
    maxWidth = "2xl",
    closeable = true,
    variant = "center",
    onClose = () => {},
}: PropsWithChildren<{
    show: boolean;
    maxWidth?: "sm" | "md" | "lg" | "xl" | "2xl";
    closeable?: boolean;
    variant?: "center" | "bottom-sheet";
    onClose: () => void;
}>) {
    const close = () => {
        if (closeable) {
            onClose();
        }
    };

    const maxWidthClass = {
        sm: "sm:max-w-sm",
        md: "sm:max-w-md",
        lg: "sm:max-w-lg",
        xl: "sm:max-w-xl",
        "2xl": "sm:max-w-2xl",
    }[maxWidth];

    const isBottomSheet = variant === "bottom-sheet";

    return createPortal(
        <Transition show={show}>
            <Dialog
                as="div"
                id="modal"
                className={`fixed inset-0 z-[100] overflow-y-auto ${isBottomSheet ? "sm:px-0" : "px-4 py-6 sm:px-0"}`}
                onClose={close}
            >
                <TransitionChild
                    enter="ease-out duration-200"
                    leave="ease-in duration-150"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="absolute inset-0 bg-ink/75" />
                </TransitionChild>

                <TransitionChild
                    as="div"
                    className={`relative z-10 flex w-full ${
                        isBottomSheet ? "items-end justify-center min-h-screen" : "items-center justify-center"
                    }`}
                    enter="ease-out duration-200"
                    leave="ease-in duration-150"
                    enterFrom={
                        isBottomSheet
                            ? "opacity-0 translate-y-full"
                            : "opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95"
                    }
                    enterTo={isBottomSheet ? "opacity-100 translate-y-0" : "opacity-100 translate-y-0 sm:scale-100"}
                    leaveFrom={isBottomSheet ? "opacity-100 translate-y-0" : "opacity-100 translate-y-0 sm:scale-100"}
                    leaveTo={
                        isBottomSheet
                            ? "opacity-0 translate-y-full"
                            : "opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95"
                    }
                >
                    <DialogPanel
                        className={`overflow-hidden bg-white sm:mx-auto sm:w-full ${maxWidthClass} ${
                            isBottomSheet ? "w-full rounded-t-2xl sm:rounded-xl" : "border border-edge mb-6"
                        }`}
                    >
                        {children}
                    </DialogPanel>
                </TransitionChild>
            </Dialog>
        </Transition>,
        document.body,
    );
}
