import { create } from "zustand";

interface LoadingState {
    activeCount: number;
    increment: () => void;
    decrement: () => void;
    reset: () => void;
}

export const useLoadingStore = create<LoadingState>()((set) => ({
    activeCount: 0,
    increment: () => set((state) => ({ activeCount: state.activeCount + 1 })),
    decrement: () => set((state) => ({ activeCount: Math.max(0, state.activeCount - 1) })),
    reset: () => set({ activeCount: 0 }),
}));

export const useLoadingActive = (): boolean => useLoadingStore((state) => state.activeCount > 0);

export const loadingStore = {
    increment: () => useLoadingStore.getState().increment(),
    decrement: () => useLoadingStore.getState().decrement(),
    reset: () => useLoadingStore.getState().reset(),
    getCount: () => useLoadingStore.getState().activeCount,
};
