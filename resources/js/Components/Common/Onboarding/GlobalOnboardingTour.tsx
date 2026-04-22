import { usePage } from "@inertiajs/react";
import { useEffect, useMemo, useRef } from "react";
import { PageProps } from "@/types";
import {
    adminOnboardingSteps,
    customerOnboardingSteps,
    tenantAdminOnboardingSteps,
    tenantStaffOnboardingSteps,
} from "@/data/onboardingContent";
import { useOnboardingStore } from "@/stores/onboardingStore";
import OnboardingTour from "./OnboardingTour";
import { OnboardingStep } from "./onboarding.types";

// 認証済みユーザーに対してロール別オンボーディングツアーを一元的にマウントする。
// 各ページのレイアウト（Admin/Tenant/Customer…）から独立して動くため、
// どのレイアウトを使っているページでも初回表示が保証される。
export default function GlobalOnboardingTour() {
    const user = usePage<PageProps>().props.auth?.user;
    const { isOpen, persistOnClose, openAuto, close } = useOnboardingStore();
    const autoOpenedRef = useRef(false);

    const steps = useMemo<OnboardingStep[]>(() => {
        if (!user) return [];
        if (user.is_admin) return adminOnboardingSteps;
        if (user.is_tenant_admin) return tenantAdminOnboardingSteps;
        if (user.is_tenant_staff) return tenantStaffOnboardingSteps;
        if (user.is_customer) return customerOnboardingSteps;
        return [];
    }, [user]);

    // 初回ログイン時のみ自動で開く。Inertia ナビゲーション間で props が更新されても、
    // 一度きり起動すればよいので autoOpenedRef でガードする。
    useEffect(() => {
        if (!user) return;
        if (autoOpenedRef.current) return;
        if (user.should_show_onboarding === true && steps.length > 0) {
            autoOpenedRef.current = true;
            openAuto();
        }
    }, [user, steps.length, openAuto]);

    if (!user || steps.length === 0) {
        return null;
    }

    return (
        <OnboardingTour
            open={isOpen}
            onClose={close}
            steps={steps}
            persistOnClose={persistOnClose}
        />
    );
}
