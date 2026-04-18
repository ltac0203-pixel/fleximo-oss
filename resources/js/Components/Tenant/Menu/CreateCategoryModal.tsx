import Modal from "@/Components/Modal";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import { api } from "@/api";
import { useApiFormSubmission } from "@/Hooks/useApiFormSubmission";
import { router } from "@inertiajs/react";
import { FormEvent, FormEventHandler, useState } from "react";
import CategoryForm from "./CategoryForm";

interface CreateCategoryModalProps {
    show: boolean;
    onClose: () => void;
    onSuccess?: (message: string) => void;
}

interface FormErrors {
    name?: string;
    is_active?: string;
}

const INITIAL_FORM_DATA = {
    name: "",
    is_active: true,
};

export default function CreateCategoryModal({ show, onClose, onSuccess }: CreateCategoryModalProps) {
    const { processing, errors, generalError, submit } = useApiFormSubmission<FormErrors>();
    const [formData, setFormData] = useState(INITIAL_FORM_DATA);

    const handleSubmit: FormEventHandler = (e: FormEvent) => {
        e.preventDefault();
        void submit(
            () => api.post<unknown, { errors?: FormErrors }>("/api/tenant/menu/categories", formData),
            {
                logMessage: "カテゴリ追加に失敗",
                onSuccess: () => {
                    router.reload({ only: ["categories"] });
                    onSuccess?.("カテゴリを追加しました");
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
                <h2 className="text-lg font-medium text-ink">カテゴリを追加</h2>

                {generalError && <p className="mt-4 text-sm text-red-600">{generalError}</p>}

                <div className="mt-6">
                    <CategoryForm formData={formData} errors={errors} onChange={setFormData} />
                </div>

                <div className="mt-6 flex justify-end">
                    <SecondaryButton onClick={handleClose}>キャンセル</SecondaryButton>

                    <PrimaryButton className="ms-3" disabled={processing} isBusy={processing}>
                        追加
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
