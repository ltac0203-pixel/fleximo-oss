interface GradientBackgroundProps {
    variant?: "default" | "hero" | "business" | "app" | "dashboard" | "customer";
}

function GradientBackground({ variant = "default" }: GradientBackgroundProps) {
    // ユーザー画面での視認性を優先し、背景は図形装飾を使わずに構成する。
    if (variant === "hero") {
        return (
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-sky-50 via-white to-cyan-50" />
                <div className="geo-public-mesh absolute inset-0 opacity-90" />
                <div className="geo-grid-overlay absolute inset-0 opacity-20" />
                <div className="absolute inset-0 bg-dots-pattern opacity-25" />
                <div className="geo-public-orb-sky animate-pulse-soft absolute -left-20 top-10 h-[26rem] w-[26rem] blur-3xl" />
                <div className="geo-public-orb-cyan animate-float-wide absolute right-[-4rem] top-[-3rem] h-[32rem] w-[32rem] blur-3xl" />
                <div className="geo-public-orb-ice absolute bottom-8 left-[18%] h-56 w-56 blur-3xl" />
                <div className="absolute left-[12%] top-24 h-32 w-32 rotate-12 border border-white/70 bg-white/40 shadow-sm backdrop-blur-sm" />
                <div className="absolute left-1/2 top-20 h-40 w-40 -translate-x-1/2 rotate-12 border border-white/80 bg-white/40 shadow-sm backdrop-blur-sm" />
                <div className="absolute right-[8%] top-28 h-52 w-52 -rotate-6 border border-sky-200/80 bg-white/55 shadow-md backdrop-blur-sm" />
                <div className="absolute bottom-24 left-[10%] h-32 w-32 rotate-12 border border-cyan-200/70 bg-cyan-50/50 shadow-sm backdrop-blur-sm" />
                <div className="absolute bottom-28 right-[18%] h-24 w-24 border border-sky-200/80 bg-white/60 shadow-sm backdrop-blur-sm" />
                <div className="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-b from-transparent via-white/30 to-white" />

                {/* 下セクションへの接続を自然にし、画面遷移感を減らす。 */}
                <svg
                    className="absolute bottom-0 left-0 w-full text-white"
                    viewBox="0 0 1440 120"
                    preserveAspectRatio="none"
                    aria-hidden="true"
                >
                    <path
                        d="M0,60 C360,120 720,0 1080,60 C1260,90 1380,75 1440,60 L1440,120 L0,120 Z"
                        fill="currentColor"
                    />
                </svg>
            </div>
        );
    }

    // Business variant
    if (variant === "business") {
        return (
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-white via-surface to-sky-50/60" />
                <div className="geo-public-mesh absolute inset-0 opacity-65" />
                <div className="geo-grid-overlay absolute inset-0 opacity-[0.08]" />
                <div className="geo-public-orb-sky absolute -left-20 top-20 h-72 w-72 blur-3xl" />
                <div className="geo-public-orb-cyan absolute right-[-6rem] top-16 h-96 w-96 blur-3xl" />
                <div className="absolute left-[18%] top-32 h-24 w-24 rotate-12 border border-white/80 bg-white/70 shadow-sm backdrop-blur-sm" />
                <div className="absolute right-[12%] top-24 h-40 w-40 -rotate-6 border border-sky-100 bg-white/65 shadow-sm backdrop-blur-sm" />
                <div className="absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-white via-white/70 to-transparent" />
            </div>
        );
    }

    if (variant === "dashboard") {
        return (
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-b from-surface-dim/80 via-surface to-white" />
                <div className="geo-grid-overlay absolute inset-0 opacity-40" />
                <div className="absolute -right-20 top-10 h-56 w-56 border border-sky-200/80 bg-white/60" />
                <div className="absolute left-10 top-24 h-16 w-16 border border-cyan-200/80 bg-white/70" />
            </div>
        );
    }

    if (variant === "customer") {
        return (
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-b from-sky-50/70 via-white to-white" />
                <div className="absolute inset-0 bg-dots-pattern opacity-40" />
                <div className="absolute -left-12 top-16 h-40 w-40 rotate-12 border border-sky-200/70 bg-white/60" />
                <div className="absolute -right-10 top-28 h-24 w-24 border border-cyan-200/70 bg-cyan-50/40" />
            </div>
        );
    }

    if (variant === "app") {
        return (
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-b from-surface/80 to-white" />
                <div className="geo-grid-overlay absolute inset-0 opacity-25" />
            </div>
        );
    }

    // デフォルトバリアント
    return (
        <div className="pointer-events-none absolute inset-0 overflow-hidden">
            <div className="absolute inset-0 bg-gradient-to-b from-surface/70 to-white" />
        </div>
    );
}

export default GradientBackground;
