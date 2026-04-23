import { ButtonHTMLAttributes } from "react";
import Button from "@/Components/UI/Button";
import { type ButtonSize } from "@/constants/buttonStyles";

interface DangerButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    isBusy?: boolean;
    size?: ButtonSize;
}

export default function DangerButton(props: DangerButtonProps) {
    return <Button variant="danger" {...props} />;
}
