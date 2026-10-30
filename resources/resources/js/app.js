const toast = document.querySelector('#toast');
const toastMessage = document.querySelector('#toast-message');
let toastTimer;

function showToast(message) {
    if (!toast || !toastMessage) return;
    toastMessage.textContent = message;
    toast.classList.add('show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('show'), 2600);
}

document.querySelectorAll('[data-toast]').forEach((element) => {
    element.addEventListener('click', (event) => {
        event.preventDefault();
        showToast(element.dataset.toast || 'This action is coming soon.');
    });
});

const modal = document.querySelector('#shipment-modal');
const openShipmentButton = document.querySelector('#create-shipment');
const closeModal = () => {
    if (modal) modal.hidden = true;
};

openShipmentButton?.addEventListener('click', () => {
    if (modal) modal.hidden = false;
    window.setTimeout(() => modal?.querySelector('input')?.focus(), 80);
});
document.querySelector('#modal-close')?.addEventListener('click', closeModal);
document.querySelector('#modal-cancel')?.addEventListener('click', closeModal);
modal?.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
});

document.querySelectorAll('.filter-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.filter-tab').forEach((item) => item.classList.remove('active'));
        tab.classList.add('active');
        const filter = tab.dataset.filter;
        document.querySelectorAll('#shipments-body tr').forEach((row) => {
            row.hidden = filter !== 'all' && row.dataset.status !== filter;
        });
    });
});

const searchInput = document.querySelector('#global-search');
searchInput?.addEventListener('input', () => {
    const query = searchInput.value.trim().toLowerCase();
    document.querySelectorAll('#shipments-body tr').forEach((row) => {
        row.hidden = Boolean(query) && !row.textContent.toLowerCase().includes(query);
    });
});

document.querySelectorAll('[data-nav]').forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        document.querySelectorAll('.nav-item').forEach((item) => item.classList.remove('active'));
        document.querySelectorAll(`[data-nav="${link.dataset.nav}"]`).forEach((item) => item.classList.add('active'));
        const target = document.querySelector(link.getAttribute('href'));
        if (target && target !== document.querySelector('#overview')) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        showToast(`${link.textContent.trim()} view selected.`);
        document.querySelector('#sidebar')?.classList.remove('open');
    });
});

document.querySelector('#export-button')?.addEventListener('click', () => showToast('Report export is being prepared.'));
document.querySelectorAll('.pagination .page').forEach((page) => {
    page.addEventListener('click', () => {
        document.querySelectorAll('.pagination .page').forEach((item) => item.classList.remove('active'));
        page.classList.add('active');
        showToast(`Showing page ${page.textContent.trim()}.`);
    });
});

document.querySelector('#mobile-menu')?.addEventListener('click', () => document.querySelector('#sidebar')?.classList.toggle('open'));

const togglePassword = document.querySelector('#toggle-password');
togglePassword?.addEventListener('click', () => {
    const password = document.querySelector('#password');
    if (!password) return;
    const visible = password.type === 'text';
    password.type = visible ? 'password' : 'text';
    togglePassword.textContent = visible ? 'Show' : 'Hide';
    togglePassword.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
});

// Reusable modal behavior for standalone operations modules.
document.querySelectorAll('[data-open-modal]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = document.querySelector(`#${button.dataset.openModal}`);
        if (!target) return;
        target.hidden = false;
        window.setTimeout(() => target.querySelector('input, select, textarea')?.focus(), 80);
    });
});

document.querySelectorAll('[data-close-modal]').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('.modal-backdrop')?.setAttribute('hidden', 'hidden');
    });
});

document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
    backdrop.addEventListener('click', (event) => {
        if (event.target === backdrop) backdrop.hidden = true;
    });
});
