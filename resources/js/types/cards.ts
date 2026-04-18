import type { PageProps } from "./common";
import type { Tenant } from "./tenant";

export interface SavedCard {
    id: number;
    card_no_display: string;
    brand: string | null;
    expire: string;
    is_default: boolean;
}

export interface CardsIndexProps extends PageProps {
    tenant: Pick<Tenant, "id" | "name" | "slug">;
    cards: SavedCard[];
    fincodePublicKey: string;
    isProduction: boolean;
}
