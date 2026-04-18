import { useRef, useCallback } from "react";

// 画面外でも新規注文を気づけるよう、聴覚通知をUIから分離して再利用可能にする。
export function useNotificationSound() {
    const audioRef = useRef<HTMLAudioElement | null>(null);

    // Audio インスタンスを再利用して遅延を抑え、連続通知でも即時再生できるようにする。
    const playNewOrderSound = useCallback(() => {
        if (!audioRef.current) {
            audioRef.current = new Audio("/sounds/new-order.mp3");
        }
        audioRef.current.currentTime = 0;
        audioRef.current.play().catch(() => {
            // 音声失敗は業務継続を阻害しないため、UIエラーに昇格させない。
        });
    }, []);

    return { playNewOrderSound };
}
