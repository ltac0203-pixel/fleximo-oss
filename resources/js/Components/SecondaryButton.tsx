import { ButtonHTMLAttributes } from "react";
import Button from "@/Components/UI/Button";
import { type ButtonSize } from "@/constants/buttonStyles";

interface SecondaryButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    size?: ButtonSize;
}

export default function SecondaryButton({
    type = "button",
    ...props
}: SecondaryButtonProps) {
    return <Button variant="secondary" type={type} {...props} />;
}
