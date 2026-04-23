import { loadingStore } from "./stores/loadingStore";
import { attachGlobalErrorHandlers } from "./Utils/logger";

const startLoading = () => loadingStore.increment();
const stopLoading = () => loadingStore.decrement();
type FetchRequestInit = RequestInit & { suppressGlobalLoading?: boolean };

const originalFetch = window.fetch.bind(window);
window.fetch = async (input, init) => {
    const suppressGlobalLoading = (init as FetchRequestInit | undefined)?.suppressGlobalLoading === true;
    if (!suppressGlobalLoading) {
        startLoading();
    }
    try {
        return await originalFetch(input, init);
    } finally {
        if (!suppressGlobalLoading) {
            stopLoading();
        }
    }
};

attachGlobalErrorHandlers();
