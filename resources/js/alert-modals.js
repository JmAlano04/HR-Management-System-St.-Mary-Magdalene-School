function createAlertDialog() {
    const dialog = document.createElement('dialog');
    dialog.className = 'hrms-alert-dialog';
    dialog.setAttribute('aria-labelledby', 'hrms-alert-title');
    dialog.setAttribute('aria-describedby', 'hrms-alert-message');

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
    message.id = 'hrms-alert-message';

    const actions = document.createElement('div');
    actions.className = 'hrms-alert-dialog__actions';

    const cancel = document.createElement('button');
    cancel.className = 'hrms-alert-dialog__cancel';
    cancel.type = 'button';
    cancel.textContent = 'Cancel';
    cancel.addEventListener('click', () => dialog.close('cancel'));

    const confirm = document.createElement('button');
    confirm.className = 'hrms-alert-dialog__confirm';
    confirm.type = 'button';
    confirm.textContent = 'Confirm';
    confirm.addEventListener('click', () => dialog.close('confirm'));

    const close = document.createElement('button');
    close.className = 'hrms-alert-dialog__close';
    close.type = 'button';
    close.textContent = 'Close';
    close.addEventListener('click', () => dialog.close('close'));

    actions.append(cancel, confirm, close);
    card.append(icon, title, message, actions);
    dialog.append(card);
    document.body.append(dialog);

    return { dialog, icon, title, message, cancel, confirm, close };
}

const alertDialog = createAlertDialog();
const alertQueue = [];
let currentDialogAction = null;

function showNextAlert() {
    if (alertDialog.dialog.open || alertQueue.length === 0) {
        return;
    }

    const alert = alertQueue.shift();
    const isError = alert.type === 'error' || alert.type === 'confirm';

    alertDialog.dialog.dataset.type = isError ? 'error' : 'success';
    alertDialog.icon.textContent = isError ? '!' : '\u2713';
    alertDialog.title.textContent = alert.type === 'confirm'
        ? (alert.title || 'Please confirm')
        : (isError ? 'Something went wrong' : 'Success');
    alertDialog.message.textContent = alert.message;
    alertDialog.cancel.hidden = alert.type !== 'confirm';
    alertDialog.confirm.hidden = alert.type !== 'confirm';
    alertDialog.close.hidden = alert.type === 'confirm';
    alertDialog.confirm.textContent = alert.confirmText || 'Confirm';
    alertDialog.cancel.textContent = alert.cancelText || 'Cancel';
    currentDialogAction = alert.resolve || null;
    alertDialog.dialog.showModal();
    (alert.type === 'confirm' ? alertDialog.cancel : alertDialog.close).focus();
}

function queueAlert(message, type = 'success') {
    const text = String(message ?? '').trim();

    if (!text) {
        return;
    }

    alertQueue.push({ message: text, type });
    showNextAlert();
}

function confirmWithModal(message, options = {}) {
    const text = String(message ?? '').trim();

    if (!text) {
        return Promise.resolve(false);
    }

    return new Promise((resolve) => {
        alertQueue.push({
            message: text,
            type: 'confirm',
            title: options.title,
            confirmText: options.confirmText,
            cancelText: options.cancelText,
            resolve,
        });
        showNextAlert();
    });
}

window.hrmsConfirm = confirmWithModal;

document.addEventListener('submit', (event) => {
    const form = event.target instanceof HTMLFormElement ? event.target : null;

    if (!form?.hasAttribute('data-confirm') || form.dataset.confirmBypass === 'true') {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    confirmWithModal(form.dataset.confirm, {
        confirmText: form.dataset.confirmButton || 'Confirm',
    }).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        form.dataset.confirmBypass = 'true';
        const submitter = event.submitter instanceof HTMLElement ? event.submitter : undefined;
        if (submitter && submitter.isConnected) {
            form.requestSubmit(submitter);
        } else {
            form.requestSubmit();
        }
        delete form.dataset.confirmBypass;
    });
}, true);

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

window.alert = (message) => {
    queueAlert(message, 'error');
};

alertDialog.dialog.addEventListener('close', () => {
    const resolve = currentDialogAction;
    currentDialogAction = null;
    resolve?.(alertDialog.dialog.returnValue === 'confirm');
    showNextAlert();
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', promotePageAlerts, { once: true });
} else {
    promotePageAlerts();
}
