import { InputHTMLAttributes } from "react";

export default function Checkbox({ className = "", ...props }: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                "w-5 h-5 border border-edge text-primary focus:border-primary focus:outline-none " + className
            }
        />
    );
}
