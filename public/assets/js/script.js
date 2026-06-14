const header = document.querySelector('.site-header');
const menuToggle = document.querySelector('.menu-toggle');
const menuLinks = document.querySelectorAll('.primary-nav a, .header-actions a');

if (header && menuToggle) {
    menuToggle.addEventListener('click', () => {
        const isOpen = header.classList.toggle('is-open');

        document.body.classList.toggle('menu-open', isOpen);
        menuToggle.setAttribute('aria-expanded', String(isOpen));
    });

    menuLinks.forEach((link) => {
        link.addEventListener('click', () => {
            header.classList.remove('is-open');
            document.body.classList.remove('menu-open');
            menuToggle.setAttribute('aria-expanded', 'false');
        });
    });
}

const passwordToggles = document.querySelectorAll('[data-password-toggle]');

passwordToggles.forEach((toggle) => {
    const inputId = toggle.getAttribute('aria-controls');
    const input = inputId ? document.getElementById(inputId) : null;

    if (!input) {
        return;
    }

    toggle.addEventListener('click', () => {
        const shouldShowPassword = input.type === 'password';

        input.type = shouldShowPassword ? 'text' : 'password';
        toggle.classList.toggle('is-visible', shouldShowPassword);
        toggle.setAttribute('aria-label', shouldShowPassword ? 'Ocultar senha' : 'Mostrar senha');
    });
});

const logoutModal = document.querySelector('[data-logout-modal]');
const logoutOpenButtons = document.querySelectorAll('[data-logout-open]');
const logoutCloseButtons = document.querySelectorAll('[data-logout-close]');
let lastLogoutOpen = null;

if (logoutModal && logoutOpenButtons.length) {
    const openLogoutModal = (trigger) => {
        lastLogoutOpen = trigger;
        logoutModal.classList.add('is-open');
        logoutModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeLogoutModal = () => {
        logoutModal.classList.remove('is-open');
        logoutModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');

        if (lastLogoutOpen) {
            lastLogoutOpen.focus();
        }
    };

    logoutOpenButtons.forEach((button) => {
        button.addEventListener('click', () => openLogoutModal(button));
    });

    logoutCloseButtons.forEach((button) => {
        button.addEventListener('click', closeLogoutModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && logoutModal.classList.contains('is-open')) {
            closeLogoutModal();
        }
    });
}

const deleteAccountModal = document.querySelector('[data-delete-account-modal]');
const deleteAccountOpen = document.querySelector('[data-delete-account-open]');
const deleteAccountCloseButtons = document.querySelectorAll('[data-delete-account-close]');

if (deleteAccountModal && deleteAccountOpen) {
    const openDeleteAccountModal = () => {
        deleteAccountModal.classList.add('is-open');
        deleteAccountModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeDeleteAccountModal = () => {
        deleteAccountModal.classList.remove('is-open');
        deleteAccountModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        deleteAccountOpen.focus();
    };

    deleteAccountOpen.addEventListener('click', openDeleteAccountModal);

    deleteAccountCloseButtons.forEach((button) => {
        button.addEventListener('click', closeDeleteAccountModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && deleteAccountModal.classList.contains('is-open')) {
            closeDeleteAccountModal();
        }
    });
}

const snacks = document.querySelectorAll('[data-snack]');

snacks.forEach((snack) => {
    window.setTimeout(() => {
        snack.classList.add('is-hiding');

        window.setTimeout(() => {
            snack.remove();
        }, 220);
    }, 3000);
});
