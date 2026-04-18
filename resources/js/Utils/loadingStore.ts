type LoadingListener = (activeCount: number) => void;

let activeCount = 0;
const listeners = new Set<LoadingListener>();

const notify = () => {
    for (const listener of listeners) {
        listener(activeCount);
    }
};

export const loadingStore = {
    increment() {
        activeCount += 1;
        notify();
    },
    decrement() {
        activeCount = Math.max(0, activeCount - 1);
        notify();
    },
    subscribe(listener: LoadingListener) {
        listeners.add(listener);
        listener(activeCount);
        return () => {
            listeners.delete(listener);
        };
    },
    getCount() {
        return activeCount;
    },
};
