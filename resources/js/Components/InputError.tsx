import { HTMLAttributes } from "react";

export default function InputError({
    message,
    className = "",
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p {...props} role="alert" aria-live="polite" className={"text-sm text-red-600 " + className}>
            {message}
        </p>
    ) : null;
}
