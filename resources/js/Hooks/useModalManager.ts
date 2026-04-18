import { useState, useCallback } from "react";

export interface UseModalManagerReturn<T = unknown> {
    showCreateModal: boolean;
    showEditModal: boolean;
    showDeleteModal: boolean;
    selectedItem: T | null;
    openCreate: () => void;
    openEdit: (item: T) => void;
    openDelete: (item: T) => void;
    closeCreate: () => void;
    closeEdit: () => void;
    closeDelete: () => void;
    closeAll: () => void;
}

/**
 * Create/Edit/Delete のモーダル状態を一元管理し、ボイラープレート削減を図る。
 * 各ページで useState を複数書く必要がなくなり、統一された命名と操作で保守性が向上する。
 */
export function useModalManager<T = unknown>(): UseModalManagerReturn<T> {
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<T | null>(null);

    const openCreate = useCallback(() => {
        setShowCreateModal(true);
    }, []);

    const openEdit = useCallback((item: T) => {
        setSelectedItem(item);
        setShowEditModal(true);
    }, []);

    const openDelete = useCallback((item: T) => {
        setSelectedItem(item);
        setShowDeleteModal(true);
    }, []);

    const closeCreate = useCallback(() => {
        setShowCreateModal(false);
    }, []);

    const closeEdit = useCallback(() => {
        setShowEditModal(false);
        setSelectedItem(null);
    }, []);

    const closeDelete = useCallback(() => {
        setShowDeleteModal(false);
        setSelectedItem(null);
    }, []);

    const closeAll = useCallback(() => {
        setShowCreateModal(false);
        setShowEditModal(false);
        setShowDeleteModal(false);
        setSelectedItem(null);
    }, []);

    return {
        showCreateModal,
        showEditModal,
        showDeleteModal,
        selectedItem,
        openCreate,
        openEdit,
        openDelete,
        closeCreate,
        closeEdit,
        closeDelete,
        closeAll,
    };
}
