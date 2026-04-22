import { create } from "zustand";

interface OnboardingState {
    // ツアーが開いているか
    isOpen: boolean;
    // 初回自動起動時は true、ユーザーが「オンボーディングを見る」から開き直した場合は false。
    // 閉じる時にサーバー側の onboarding_completed_at を更新するかを決定する。
    persistOnClose: boolean;
    // 初回自動起動用（サーバーの should_show_onboarding フラグを受けて開く）
    openAuto: () => void;
    // 手動再閲覧用（再度サーバーに記録する必要はない）
    openManual: () => void;
    close: () => void;
}

export const useOnboardingStore = create<OnboardingState>((set) => ({
    isOpen: false,
    persistOnClose: false,
    openAuto: () => set({ isOpen: true, persistOnClose: true }),
    openManual: () => set({ isOpen: true, persistOnClose: false }),
    close: () => set({ isOpen: false }),
}));
