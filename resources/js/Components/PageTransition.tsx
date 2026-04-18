import { PropsWithChildren, useEffect, useRef } from "react";
import { router } from "@inertiajs/react";

export default function PageTransition({ children }: PropsWithChildren) {
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const removeListener = router.on("navigate", () => {
            const el = ref.current;
            if (el) {
                el.classList.remove("animate-fade-in");
                void el.offsetWidth; // reflow で animation 再発火
                el.classList.add("animate-fade-in");
            }
        });
        return removeListener;
    }, []);

    return (
        <div ref={ref} className="animate-fade-in">
            {children}
        </div>
    );
}
