import { forwardRef, InputHTMLAttributes, useEffect, useImperativeHandle, useRef } from "react";

type TextInputProps = InputHTMLAttributes<HTMLInputElement> & {
    isFocused?: boolean;
};

export default forwardRef<HTMLInputElement, TextInputProps>(function TextInput(
    {
        type = "text",
        className = "",
        isFocused = false,
        id,
        name,
        placeholder,
        ["aria-label"]: ariaLabel,
        ["aria-labelledby"]: ariaLabelledBy,
        ["aria-describedby"]: ariaDescribedBy,
        ...props
    },
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);
    const fallbackAriaLabel =
        typeof placeholder === "string" ? placeholder : typeof name === "string" ? name : undefined;
    const resolvedAriaLabel = ariaLabel ?? (ariaLabelledBy || id ? undefined : fallbackAriaLabel);

    useImperativeHandle(ref, () => localRef.current as HTMLInputElement);

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            id={id}
            name={name}
            placeholder={placeholder}
            type={type}
            aria-label={resolvedAriaLabel}
            aria-labelledby={ariaLabelledBy}
            aria-describedby={ariaDescribedBy}
            className={
                "border border-edge bg-white px-4 py-2 text-sm text-ink focus:border-primary focus:outline-none " +
                className
            }
            ref={localRef}
        />
    );
});
