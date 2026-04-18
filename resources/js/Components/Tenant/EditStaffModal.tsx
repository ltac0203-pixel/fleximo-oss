import { api } from "@/api";
import InputError from "@/Components/InputError";
import Modal from "@/Components/Modal";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import StaffForm from "@/Components/Tenant/StaffForm";
import { useApiFormSubmission } from "@/Hooks/useApiFormSubmission";
import { Staff } from "@/types";
import { router } from "@inertiajs/react";
import { FormEvent, FormEventHandler, useEffect, useState } from "react";

interface EditStaffModalProps {
    show: boolean;
    staff: Staff | null;
    onClose: () => void;
    onSuccess?: (message: string) => void;
}

interface FormErrors {
    name?: string;
    email?: string;
    password?: string;
    phone?: string;
    is_active?: string;
}

const INITIAL_FORM_DATA = {
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    phone: "",
    is_active: true,
};

export default function EditStaffModal({ show, staff, onClose, onSuccess }: EditStaffModalProps) {
    const { processing, errors, generalError, submit } = useApiFormSubmission<FormErrors>();
    const [formData, setFormData] = useState(INITIAL_FORM_DATA);

    useEffect(() => {
        if (staff) {
            setFormData({
                name: staff.name,
                email: staff.email,
                password: "",
                password_confirmation: "",
                phone: staff.phone || "",
                is_active: staff.is_active,
            });
        }
    }, [staff]);

    const handleSubmit: FormEventHandler = (e: FormEvent) => {
        e.preventDefault();
        if (!staff) return;

        const payload: Record<string, unknown> = {
            name: formData.name,
            email: formData.email,
            phone: formData.phone || null,
            is_active: formData.is_active,
        };

        if (formData.password) {
            payload.password = formData.password;
        }

        void submit(
            () => api.patch<unknown, { errors?: FormErrors }>(`/api/tenant/staff/${staff.id}`, payload),
            {
                logMessage: "スタッフ更新に失敗",
                onSuccess: () => {
                    router.reload({ only: ["staff"] });
                    onSuccess?.("スタッフ情報を更新しました");
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
                <h2 className="text-lg font-medium text-ink">スタッフを編集</h2>

                {generalError && <InputError id="edit-staff-error" message={generalError} className="mt-4" />}

                <div className="mt-6">
                    <StaffForm formData={formData} errors={errors} onChange={setFormData} isEdit={true} />
                </div>

                <div className="mt-6 flex justify-end">
                    <SecondaryButton onClick={handleClose}>キャンセル</SecondaryButton>

                    <PrimaryButton className="ms-3" disabled={processing} isBusy={processing}>
                        更新
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
