import { useEffect, useState } from "react";

export default function CurrentTime() {
    const [time, setTime] = useState(new Date());

    useEffect(() => {
        let timer: ReturnType<typeof setInterval> | null = null;

        const startTimer = () => {
            setTime(new Date());
            timer = setInterval(() => {
                setTime(new Date());
            }, 1000);
        };

        const stopTimer = () => {
            if (timer !== null) {
                clearInterval(timer);
                timer = null;
            }
        };

        const handleVisibilityChange = () => {
            if (document.visibilityState === "visible") {
                startTimer();
            } else {
                stopTimer();
            }
        };

        if (document.visibilityState === "visible") {
            startTimer();
        }

        document.addEventListener("visibilitychange", handleVisibilityChange);

        return () => {
            stopTimer();
            document.removeEventListener("visibilitychange", handleVisibilityChange);
        };
    }, []);

    const formatTime = (date: Date) => {
        return date.toLocaleTimeString("ja-JP", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
    };

    return <div className="text-2xl font-mono text-ink">{formatTime(time)}</div>;
}
