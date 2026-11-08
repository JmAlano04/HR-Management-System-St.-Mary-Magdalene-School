const updateStatus = document.querySelector('[data-system-updates]');

if (updateStatus) {
    const updateNotice = document.querySelector('[data-system-update-notice]');
    const refreshButton = document.querySelector('[data-system-update-refresh]');
    let version = Number(updateStatus.dataset.currentVersion) || 0;
    let checking = false;
    let updateAvailable = false;
    const dirtyForms = new Set();
    let dirtyOutsideForm = false;

    const hasUnsavedChanges = () => {
        for (const form of dirtyForms) {
            if (form.isConnected) {
                return true;
            }

            dirtyForms.delete(form);
        }

        return dirtyOutsideForm;
    };

    const showUpdateNotice = () => {
        updateNotice?.removeAttribute('hidden');
    };

    const checkForUpdates = async () => {
        if (checking) {
            return;
        }

        checking = true;

        try {
            const response = await fetch(updateStatus.dataset.updateUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`System update check failed with HTTP ${response.status}.`);
            }

            const data = await response.json();
            const currentVersion = Number(data.version);

            if (!Number.isSafeInteger(currentVersion)) {
                throw new Error('The system update check returned an invalid version.');
            }

            if (currentVersion > version) {
                version = currentVersion;
                updateAvailable = true;
            }

            if (updateAvailable) {
                if (hasUnsavedChanges() || document.visibilityState !== 'visible') {
                    showUpdateNotice();
                } else {
                    window.location.reload();
                }
            }
        } catch (error) {
            console.warn('Unable to check for HRMS updates. The next check will retry.', error);
        } finally {
            checking = false;
        }
    };

    document.addEventListener('input', (event) => {
        const form = event.target.closest?.('form');

        if (form) {
            dirtyForms.add(form);
        } else if (event.target.isContentEditable) {
            dirtyOutsideForm = true;
        }
    }, true);

    document.addEventListener('change', (event) => {
        const form = event.target.closest?.('form');

        if (form) {
            dirtyForms.add(form);
        }
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target;

        window.setTimeout(() => {
            if (!event.defaultPrevented) {
                dirtyForms.delete(form);
            }
        }, 0);
    }, true);

    document.addEventListener('reset', (event) => {
        dirtyForms.delete(event.target);
    }, true);

    document.addEventListener('visibilitychange', () => {
        if (updateAvailable && !hasUnsavedChanges() && document.visibilityState === 'visible') {
            window.location.reload();
        }
    });

    refreshButton?.addEventListener('click', () => window.location.reload());

    window.setInterval(checkForUpdates, 5000);
}
