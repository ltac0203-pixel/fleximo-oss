import { ButtonHTMLAttributes } from "react";
import Button from "@/Components/UI/Button";
import { type ButtonSize } from "@/constants/buttonStyles";

interface PrimaryButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    isBusy?: boolean;
    size?: ButtonSize;
    tone?: "solid" | "outline";
}

export default function PrimaryButton(props: PrimaryButtonProps) {
    return <Button variant="primary" {...props} />;
}
