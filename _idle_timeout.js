(() => {
    const csrfToken = document.querySelector('meta[name="idle-csrf-token"]')?.content;
    if (!csrfToken) return;

    const timeoutMs = 5 * 60 * 1000;
    const heartbeatMs = 60 * 1000;
    const storageKey = `salesnudge:last-activity:${csrfToken}`;
    let lastActivity = Date.now();
    let lastStoredActivity = 0;
    let isLoggingOut = false;
    let timer;

    try {
        const storedActivity = Number(localStorage.getItem(storageKey));
        if (Number.isFinite(storedActivity)) lastActivity = Math.max(lastActivity, storedActivity);
        localStorage.setItem(storageKey, String(lastActivity));
    } catch (error) {
        console.error('Unable to synchronize idle activity across tabs.', error);
    }

    function scheduleTimeout() {
        window.clearTimeout(timer);
        const remainingMs = timeoutMs - (Date.now() - lastActivity);
        if (remainingMs <= 0) {
            endSession();
            return;
        }
        timer = window.setTimeout(endSession, remainingMs);
    }

    function recordActivity() {
        if (isLoggingOut) return;

        lastActivity = Date.now();
        if (lastActivity - lastStoredActivity >= 1000) {
            lastStoredActivity = lastActivity;
            try {
                localStorage.setItem(storageKey, String(lastActivity));
            } catch (error) {
                console.error('Unable to save idle activity.', error);
            }
        }
        scheduleTimeout();
    }

    async function sendAction(action) {
        const body = new URLSearchParams({ action, csrf_token: csrfToken });
        const response = await fetch('../_idle_timeout.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
            keepalive: true
        });
        return response.ok;
    }

    async function endSession() {
        if (isLoggingOut) return;
        isLoggingOut = true;

        try {
            await sendAction('logout');
        } catch (error) {
            console.error('Unable to end the idle session.', error);
        }
        window.location.replace('../index.php?expired=1');
    }

    window.addEventListener('storage', (event) => {
        if (event.key !== storageKey) return;
        const sharedActivity = Number(event.newValue);
        if (Number.isFinite(sharedActivity) && sharedActivity > lastActivity) {
            lastActivity = sharedActivity;
            scheduleTimeout();
        }
    });

    ['pointerdown', 'pointermove', 'keydown', 'touchstart', 'wheel', 'input', 'focus'].forEach((eventName) => {
        document.addEventListener(eventName, recordActivity, { passive: true });
    });

    window.setInterval(async () => {
        if (isLoggingOut || Date.now() - lastActivity >= timeoutMs) {
            scheduleTimeout();
            return;
        }

        try {
            const active = await sendAction('activity');
            if (!active) window.location.replace('../index.php?expired=1');
        } catch (error) {
            console.error('Unable to refresh the idle session.', error);
        }
    }, heartbeatMs);

    scheduleTimeout();
})();
