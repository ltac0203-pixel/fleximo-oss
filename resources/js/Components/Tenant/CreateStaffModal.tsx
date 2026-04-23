import { api } from "@/api";
import InputError from "@/Components/InputError";
import Modal from "@/Components/Modal";
import Button from "@/Components/UI/Button";
import StaffForm from "@/Components/Tenant/StaffForm";
import { useApiFormSubmission } from "@/Hooks/useApiFormSubmission";
import { router } from "@inertiajs/react";
import { FormEvent, FormEventHandler, useState } from "react";

interface CreateStaffModalProps {
    show: boolean;
    onClose: () => void;
    onSuccess?: (message: string) => void;
}

interface FormErrors {
    name?: string;
    email?: string;
    password?: string;
    password_confirmation?: string;
    phone?: string;
}

const INITIAL_FORM_DATA = {
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    phone: "",
    is_active: true,
};

export default function CreateStaffModal({ show, onClose, onSuccess }: CreateStaffModalProps) {
    const { processing, errors, generalError, submit } = useApiFormSubmission<FormErrors>();
    const [formData, setFormData] = useState(INITIAL_FORM_DATA);

    const handleSubmit: FormEventHandler = (e: FormEvent) => {
        e.preventDefault();
        void submit(
            () => api.post<unknown, { errors?: FormErrors }>("/api/tenant/staff", formData),
            {
                logMessage: "スタッフ追加に失敗",
                onSuccess: () => {
                    router.reload({ only: ["staff"] });
                    onSuccess?.("スタッフを追加しました");
                    handleClose();
                },
            },
        );
    };

    const handleClose = () => {
        setFormData(INITIAL_FORM_DATA);
        onClose();
    };

    return (
        <Modal show={show} onClose={handleClose} maxWidth="md">
            <form onSubmit={handleSubmit} className="p-6">
                <h2 className="text-lg font-medium text-ink">スタッフを追加</h2>

                {generalError && <InputError id="create-staff-error" message={generalError} className="mt-4" />}

                <div className="mt-6">
                    <StaffForm formData={formData} errors={errors} onChange={setFormData} />
                </div>

                <div className="mt-6 flex justify-end">
                    <Button variant="secondary" type="button" onClick={handleClose}>
                        キャンセル
                    </Button>

                    <Button
                        variant="primary"
                        className="ms-3"
                        disabled={processing}
                        isBusy={processing}
                    >
                        追加
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
