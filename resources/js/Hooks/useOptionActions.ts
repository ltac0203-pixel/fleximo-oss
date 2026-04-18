import { api } from "@/api";
import { ENDPOINTS } from "@/api/endpoints";
import { useState } from "react";

interface UseOptionActionsParams {
    optionGroupId: number;
    onSuccess?: () => void;
    onError?: (message: string) => void;
}

interface UseOptionActionsReturn {
    addOption: (data: { name: string; price: number | "" }) => Promise<boolean>;
    updateOption: (optionId: number, data: { name: string; price: number | "" }) => Promise<boolean>;
    deleteOption: (optionId: number) => Promise<boolean>;
    processing: boolean;
    activeAction: "add" | "save" | "delete" | null;
    activeOptionId: number | null;
}

export function useOptionActions({ optionGroupId, onSuccess, onError }: UseOptionActionsParams): UseOptionActionsReturn {
    const [processing, setProcessing] = useState(false);
    const [activeAction, setActiveAction] = useState<"add" | "save" | "delete" | null>(null);
    const [activeOptionId, setActiveOptionId] = useState<number | null>(null);

    const addOption = async (data: { name: string; price: number | "" }): Promise<boolean> => {
        if (!data.name.trim()) return false;

        setProcessing(true);
        setActiveAction("add");
        setActiveOptionId(null);
        try {
            const { error } = await api.post(ENDPOINTS.tenant.options(optionGroupId), {
                ...data,
                price: data.price === "" ? 0 : data.price,
            });

            if (error) {
                onError?.(error ?? "オプションの追加に失敗しました。");
                return false;
            }

            onSuccess?.();
            return true;
        } finally {
            setProcessing(false);
            setActiveAction(null);
            setActiveOptionId(null);
        }
    };

    const updateOption = async (optionId: number, data: { name: string; price: number | "" }): Promise<boolean> => {
        if (!data.name.trim()) return false;

        setProcessing(true);
        setActiveAction("save");
        setActiveOptionId(optionId);
        try {
            const { error } = await api.patch(ENDPOINTS.tenant.option(optionGroupId, optionId), {
                ...data,
                price: data.price === "" ? 0 : data.price,
            });

            if (error) {
                onError?.(error ?? "オプションの更新に失敗しました。");
                return false;
            }

            onSuccess?.();
            return true;
        } finally {
            setProcessing(false);
            setActiveAction(null);
            setActiveOptionId(null);
        }
    };

    const deleteOption = async (optionId: number): Promise<boolean> => {
        setProcessing(true);
        setActiveAction("delete");
        setActiveOptionId(optionId);
        try {
            const { error } = await api.delete(ENDPOINTS.tenant.option(optionGroupId, optionId));

            if (error) {
                onError?.(error ?? "オプションの削除に失敗しました。");
                return false;
            }

            onSuccess?.();
            return true;
        } finally {
            setProcessing(false);
            setActiveAction(null);
            setActiveOptionId(null);
        }
    };

    return { addOption, updateOption, deleteOption, processing, activeAction, activeOptionId };
}
