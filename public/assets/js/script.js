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
