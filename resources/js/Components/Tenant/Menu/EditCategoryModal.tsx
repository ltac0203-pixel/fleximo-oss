import Modal from "@/Components/Modal";
import Button from "@/Components/UI/Button";
import { api } from "@/api";
import { useApiFormSubmission } from "@/Hooks/useApiFormSubmission";
import { FormModalProps, MenuCategory } from "@/types";
import { router } from "@inertiajs/react";
import { FormEvent, FormEventHandler, useEffect, useState } from "react";
import CategoryForm from "./CategoryForm";

interface EditCategoryModalProps extends FormModalProps<string> {
    category: MenuCategory | null;
}

interface FormErrors {
    name?: string;
    is_active?: string;
}

const INITIAL_FORM_DATA = {
    name: "",
    is_active: true,
};

export default function EditCategoryModal({ show, category, onClose, onSuccess }: EditCategoryModalProps) {
    const { processing, errors, generalError, submit } = useApiFormSubmission<FormErrors>();
    const [formData, setFormData] = useState(INITIAL_FORM_DATA);

    useEffect(() => {
        if (category) {
            setFormData({
                name: category.name,
                is_active: category.is_active,
            });
        }
    }, [category]);

    const handleSubmit: FormEventHandler = (e: FormEvent) => {
        e.preventDefault();
        if (!category) return;

        void submit(
            () => api.patch<unknown, { errors?: FormErrors }>(`/api/tenant/menu/categories/${category.id}`, formData),
            {
                logMessage: "カテゴリ更新に失敗",
                onSuccess: () => {
                    router.reload({ only: ["categories"] });
                    onSuccess?.("カテゴリを更新しました");
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
                <h2 className="text-lg font-medium text-ink">カテゴリを編集</h2>

                {generalError && <p className="mt-4 text-sm text-red-600">{generalError}</p>}

                <div className="mt-6">
                    <CategoryForm formData={formData} errors={errors} onChange={setFormData} />
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
                        更新
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
