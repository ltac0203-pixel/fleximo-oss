import { useState, useCallback, useEffect } from "react";

type NotificationPermission = "default" | "granted" | "denied";

interface UseBrowserNotificationReturn {
    permission: NotificationPermission;
    isSupported: boolean;
    requestPermission: () => Promise<NotificationPermission>;
    showNotification: (title: string, options?: NotificationOptions) => void;
}

export function useBrowserNotification(): UseBrowserNotificationReturn {
    const isSupported = typeof window !== "undefined" && "Notification" in window;

    const [permission, setPermission] = useState<NotificationPermission>(
        isSupported ? (Notification.permission as NotificationPermission) : "denied",
    );

    useEffect(() => {
        if (isSupported) {
            setPermission(Notification.permission as NotificationPermission);
        }
    }, [isSupported]);

    const requestPermission = useCallback(async (): Promise<NotificationPermission> => {
        if (!isSupported) {
            return "denied";
        }

        try {
            const result = await Notification.requestPermission();
            const normalizedResult = result as NotificationPermission;
            setPermission(normalizedResult);
            return normalizedResult;
        } catch {
            return "denied";
        }
    }, [isSupported]);

    const showNotification = useCallback(
        (title: string, options?: NotificationOptions) => {
            if (!isSupported || permission !== "granted") {
                return;
            }

            try {
                new Notification(title, options);
            } catch {
                // iOS Safari等でNotificationコンストラクタが使えない場合は静かに失敗
            }
        },
        [isSupported, permission],
    );

    return { permission, isSupported, requestPermission, showNotification };
}
