import { api } from "@/api";
import { ENDPOINTS } from "@/api/endpoints";
import { useCallback, useRef, useState } from "react";

interface UseFavoritesOptions {
    initialFavoriteIds?: number[];
}

interface UseFavoritesReturn {
    isFavorited: (tenantId: number) => boolean;
    toggleFavorite: (tenantId: number) => Promise<void>;
    isToggling: boolean;
    favoriteIds: Set<number>;
}

export function useFavorites(options?: UseFavoritesOptions): UseFavoritesReturn {
    const { initialFavoriteIds = [] } = options ?? {};
    const [favoriteIds, setFavoriteIds] = useState<Set<number>>(() => new Set(initialFavoriteIds));
    const [isToggling, setIsToggling] = useState(false);
    const favoriteIdsRef = useRef<Set<number>>(new Set(initialFavoriteIds));

    const isFavorited = useCallback(
        (tenantId: number): boolean => {
            return favoriteIds.has(tenantId);
        },
        [favoriteIds],
    );

    const toggleFavorite = useCallback(async (tenantId: number): Promise<void> => {
        setIsToggling(true);

        // 楽観的更新
        const previousIds = new Set(favoriteIdsRef.current);
        const nextIds = new Set(favoriteIdsRef.current);
        if (nextIds.has(tenantId)) {
            nextIds.delete(tenantId);
        } else {
            nextIds.add(tenantId);
        }
        favoriteIdsRef.current = nextIds;
        setFavoriteIds(nextIds);

        try {
            const { data, error } = await api.post<{ is_favorited: boolean }>(
                ENDPOINTS.customer.favorites.toggle(tenantId),
            );

            if (error || !data) {
                // ロールバック
                favoriteIdsRef.current = previousIds;
                setFavoriteIds(previousIds);
                return;
            }

            // サーバーの返却値に合わせて同期
            const serverIds = new Set(favoriteIdsRef.current);
            if (data.is_favorited) {
                serverIds.add(tenantId);
            } else {
                serverIds.delete(tenantId);
            }
            favoriteIdsRef.current = serverIds;
            setFavoriteIds(serverIds);
        } catch {
            // ロールバック
            favoriteIdsRef.current = previousIds;
            setFavoriteIds(previousIds);
        } finally {
            setIsToggling(false);
        }
    }, []);

    return {
        isFavorited,
        toggleFavorite,
        isToggling,
        favoriteIds,
    };
}
