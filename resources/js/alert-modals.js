function createAlertDialog() {
    const dialog = document.createElement('dialog');
    dialog.className = 'hrms-alert-dialog';
    dialog.setAttribute('aria-labelledby', 'hrms-alert-title');

    const card = document.createElement('div');
    card.className = 'hrms-alert-dialog__card';

    const icon = document.createElement('span');
    icon.className = 'hrms-alert-dialog__icon';
    icon.setAttribute('aria-hidden', 'true');

    const title = document.createElement('h2');
    title.className = 'hrms-alert-dialog__title';
    title.id = 'hrms-alert-title';

    const message = document.createElement('p');
    message.className = 'hrms-alert-dialog__message';

    const close = document.createElement('button');
    close.className = 'hrms-alert-dialog__close';
    close.type = 'button';
    close.textContent = 'Close';
    close.addEventListener('click', () => dialog.close());

    card.append(icon, title, message, close);
    dialog.append(card);
    document.body.append(dialog);

    return { dialog, icon, title, message, close };
}

const alertDialog = createAlertDialog();
const alertQueue = [];

function showNextAlert() {
    if (alertDialog.dialog.open || alertQueue.length === 0) {
        return;
    }

    const alert = alertQueue.shift();
    const isError = alert.type === 'error';

    alertDialog.dialog.dataset.type = isError ? 'error' : 'success';
    alertDialog.icon.textContent = isError ? '!' : '\u2713';
    alertDialog.title.textContent = isError ? 'Something went wrong' : 'Success';
    alertDialog.message.textContent = alert.message;
    alertDialog.dialog.showModal();
    alertDialog.close.focus();
}

function queueAlert(message, type = 'success') {
    const text = String(message ?? '').trim();

    if (!text) {
        return;
    }

    alertQueue.push({ message: text, type });
    showNextAlert();
}

function promotePageAlerts() {
    const alertSelectors = [
        'div[class*="bg-green-50"]',
        'div[class*="bg-emerald-50"]',
        'div[class*="bg-red-50"]',
        'div[class*="bg-rose-50"]',
        'p.text-green-600',
    ];

    const candidates = new Set(document.querySelectorAll(alertSelectors.join(',')));

    document.querySelectorAll('p[role="status"]').forEach((element) => {
        if (element.className.includes('text-[#26734d]')) {
            candidates.add(element);
        }
    });
    document.querySelectorAll('p[x-init]').forEach((element) => {
        if (element.innerText.trim() === 'Saved.') {
            candidates.add(element);
        }
    });

    candidates.forEach((element) => {
        const classes = element.className;
        const hasPadding = classes.split(/\s+/).some((name) => name.startsWith('px-') || name.startsWith('p-'));
        const hasAlertStyling = classes.includes('text-sm')
            && hasPadding
            && classes.includes('rounded');
        const isSuccessStatus = element.matches('p[role="status"]')
            && classes.includes('text-[#26734d]');
        const isSuccessNotice = element.tagName === 'P'
            && (classes.includes('text-green-600') || element.innerText.trim() === 'Saved.');

        if ((!hasAlertStyling && !isSuccessStatus && !isSuccessNotice) || element.querySelector('button, a, form')) {
            return;
        }

        const message = element.innerText.trim();

        if (!message) {
            return;
        }

        queueAlert(message, classes.includes('bg-red-50') || classes.includes('bg-rose-50') ? 'error' : 'success');
        element.remove();
    });
}

alertDialog.dialog.addEventListener('close', showNextAlert);

const nativeAlert = window.alert.bind(window);
window.alert = (message) => {
    if (!alertDialog.dialog.isConnected) {
        nativeAlert(message);
        return;
    }

    queueAlert(message, 'error');
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', promotePageAlerts, { once: true });
} else {
    promotePageAlerts();
}
