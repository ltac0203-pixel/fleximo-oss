import { router } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";

const SHOW_DELAY_MS = 150;
const COMPLETION_DELAY_MS = 300;

type NavigationPhase = "idle" | "pending" | "completing";

export default function NavigationProgressBar() {
    const [phase, setPhase] = useState<NavigationPhase>("idle");
    const showTimerRef = useRef<number | undefined>(undefined);
    const completeTimerRef = useRef<number | undefined>(undefined);
    const phaseRef = useRef<NavigationPhase>("idle");

    useEffect(() => {
        const clearTimers = () => {
            if (showTimerRef.current !== undefined) {
                window.clearTimeout(showTimerRef.current);
                showTimerRef.current = undefined;
            }
            if (completeTimerRef.current !== undefined) {
                window.clearTimeout(completeTimerRef.current);
                completeTimerRef.current = undefined;
            }
        };

        const updatePhase = (nextPhase: NavigationPhase) => {
            phaseRef.current = nextPhase;
            setPhase(nextPhase);
        };

        const removeStart = router.on("start", () => {
            clearTimers();
            updatePhase("idle");
            showTimerRef.current = window.setTimeout(() => {
                updatePhase("pending");
            }, SHOW_DELAY_MS);
        });

        const removeFinish = router.on("finish", () => {
            clearTimers();

            if (phaseRef.current === "pending") {
                updatePhase("completing");
                completeTimerRef.current = window.setTimeout(() => {
                    updatePhase("idle");
                }, COMPLETION_DELAY_MS);
            } else {
                updatePhase("idle");
            }
        });

        return () => {
            clearTimers();
            removeStart();
            removeFinish();
        };
    }, []);

    if (phase === "idle") {
        return null;
    }

    return (
        <div
            className="fixed top-0 left-0 right-0 z-[60] h-[3px]"
            role="progressbar"
            aria-label="ページ遷移中"
            aria-valuemin={0}
            aria-valuemax={100}
        >
            <div
                className={`h-full bg-gradient-to-r from-sky-500 to-cyan-500 ${
                    phase === "completing"
                        ? "w-full animate-nav-progress-complete"
                        : "animate-nav-progress-grow"
                }`}
            />
        </div>
    );
}
