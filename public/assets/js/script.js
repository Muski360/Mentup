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

const finishChampionshipModal = document.querySelector('[data-finish-championship-modal]');
const finishChampionshipOpen = document.querySelector('[data-finish-championship-open]');
const finishChampionshipCloseButtons = document.querySelectorAll('[data-finish-championship-close]');

if (finishChampionshipModal && finishChampionshipOpen) {
    const openFinishChampionshipModal = () => {
        finishChampionshipModal.classList.add('is-open');
        finishChampionshipModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeFinishChampionshipModal = () => {
        finishChampionshipModal.classList.remove('is-open');
        finishChampionshipModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        finishChampionshipOpen.focus();
    };

    finishChampionshipOpen.addEventListener('click', openFinishChampionshipModal);

    finishChampionshipCloseButtons.forEach((button) => {
        button.addEventListener('click', closeFinishChampionshipModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && finishChampionshipModal.classList.contains('is-open')) {
            closeFinishChampionshipModal();
        }
    });
}

const championshipEditModal = document.querySelector('[data-championship-edit-modal]');
const championshipEditOpen = document.querySelector('[data-championship-edit-open]');
const championshipEditCloseButtons = document.querySelectorAll('[data-championship-edit-close]');

if (championshipEditModal && championshipEditOpen) {
    const openChampionshipEditModal = () => {
        championshipEditModal.classList.add('is-open');
        championshipEditModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeChampionshipEditModal = () => {
        championshipEditModal.classList.remove('is-open');
        championshipEditModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        championshipEditOpen.focus();
    };

    championshipEditOpen.addEventListener('click', openChampionshipEditModal);

    championshipEditCloseButtons.forEach((button) => {
        button.addEventListener('click', closeChampionshipEditModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && championshipEditModal.classList.contains('is-open')) {
            closeChampionshipEditModal();
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

const championshipForm = document.querySelector('[data-championship-form]');

if (championshipForm) {
    const teamCountInput = championshipForm.querySelector('[data-team-count]');
    const teamList = championshipForm.querySelector('[data-team-list]');

    const createTeamRow = (index) => {
        const row = document.createElement('div');
        row.className = 'champ-team-row';
        row.innerHTML = `
            <label class="champ-field">
                <span>Time ${index + 1}:</span>
                <input name="team_names[]" type="text" required>
            </label>
            <label class="champ-field">
                <span>Jogadores:</span>
                <input name="team_players[]" type="text" placeholder="Ex: Ana, Bruno">
            </label>
        `;

        return row;
    };

    const syncTeamRows = () => {
        const count = Math.max(2, Math.min(64, Number.parseInt(teamCountInput.value, 10) || 2));
        const rows = Array.from(teamList.querySelectorAll('.champ-team-row'));

        if (String(count) !== teamCountInput.value) {
            teamCountInput.value = String(count);
        }

        while (rows.length > count) {
            rows.pop().remove();
        }

        for (let index = rows.length; index < count; index += 1) {
            teamList.appendChild(createTeamRow(index));
        }
    };

    teamCountInput.addEventListener('input', syncTeamRows);
    championshipForm.addEventListener('reset', () => {
        window.setTimeout(syncTeamRows, 0);
    });
}
