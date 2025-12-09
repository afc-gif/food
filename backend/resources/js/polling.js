export function createPoller(task, intervalMs, options = {}) {
    const { immediate = true, runWhileHidden = false, onError = null } = options;
    let timer = null;
    let running = false;

    const shouldRun = () => {
        if (runWhileHidden) return true;
        if (typeof document === 'undefined') return true;
        return document.visibilityState !== 'hidden';
    };

    const tick = async () => {
        if (running || !shouldRun()) return;
        running = true;
        try {
            await task();
        } catch (error) {
            if (onError) {
                onError(error);
            } else {
                console.warn('Poller task failed', error);
            }
        } finally {
            running = false;
        }
    };

    const start = () => {
        if (timer) return;
        if (immediate) tick();
        timer = setInterval(tick, intervalMs);
    };

    const stop = () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    };

    if (typeof document !== 'undefined') {
        document.addEventListener('visibilitychange', () => {
            if (timer && shouldRun()) tick();
        });
    }

    return { start, stop, isRunning: () => !!timer };
}
