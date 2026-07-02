document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert-success').forEach(el => {
        setTimeout(() => el.remove(), 4000);
    });

    initAdminSidebar();
    initCouplePreview();
    initCountdownPreview();
    initInvitationShare();
    initMusicPresetPreview();
});

function initAdminSidebar() {
    const toggle = document.getElementById('adminMenuToggle');
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('adminSidebarBackdrop');
    const closeBtn = document.getElementById('adminSidebarClose');
    const toggleLabel = toggle?.querySelector('.admin-menu-toggle-label');
    const desktopQuery = window.matchMedia('(min-width: 1200px)');
    if (!toggle || !sidebar) {
        return;
    }

    let scrollLockY = 0;

    const openMenu = () => {
        scrollLockY = window.scrollY;
        sidebar.classList.add('is-open');
        backdrop?.classList.add('is-visible');
        document.body.classList.add('admin-nav-open');
        document.body.style.top = `-${scrollLockY}px`;
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        toggle.classList.add('is-active');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Fermer le menu');
        if (toggleLabel) toggleLabel.textContent = 'Fermer';
        backdrop?.setAttribute('aria-hidden', 'false');
    };

    const closeMenu = () => {
        sidebar.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        document.body.classList.remove('admin-nav-open');
        document.body.style.top = '';
        document.body.style.position = '';
        document.body.style.width = '';
        window.scrollTo(0, scrollLockY);
        toggle.classList.remove('is-active');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Ouvrir le menu');
        if (toggleLabel) toggleLabel.textContent = 'Menu';
        backdrop?.setAttribute('aria-hidden', 'true');
    };

    const syncMenuMode = () => {
        if (desktopQuery.matches) {
            closeMenu();
        }
    };

    toggle.addEventListener('click', () => {
        if (sidebar.classList.contains('is-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    closeBtn?.addEventListener('click', closeMenu);
    backdrop?.addEventListener('click', closeMenu);

    sidebar.querySelectorAll('.sidebar-link').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
            closeMenu();
        }
    });

    desktopQuery.addEventListener('change', syncMenuMode);
    window.addEventListener('resize', syncMenuMode);
}

async function copyToClipboard(text) {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const helper = document.createElement('textarea');
    helper.value = text;
    helper.setAttribute('readonly', '');
    helper.style.position = 'fixed';
    helper.style.opacity = '0';
    document.body.appendChild(helper);
    helper.select();
    document.execCommand('copy');
    helper.remove();
}

function showCopyFeedback(key) {
    const feedback = document.querySelector(`[data-copy-feedback="${key}"]`);
    if (!feedback) {
        return;
    }
    feedback.classList.remove('d-none');
    window.setTimeout(() => feedback.classList.add('d-none'), 2200);
}

function initInvitationShare() {
    const linkInput = document.getElementById('invitationLink');
    const messageInput = document.getElementById('invitationMessage');
    const copyLinkBtn = document.getElementById('copyInvitationLink');
    const copyMessageBtn = document.getElementById('copyInvitationMessage');
    const copyFullBtn = document.getElementById('copyInvitationFull');

    if (!linkInput || !messageInput) {
        return;
    }

    const buildFullMessage = () => {
        const link = linkInput.value.trim();
        const message = messageInput.value.trim();
        if (!message) {
            return link;
        }
        if (message.includes(link)) {
            return message;
        }
        return `${message}\n\n🔗 ${link}`;
    };

    copyLinkBtn?.addEventListener('click', async () => {
        await copyToClipboard(linkInput.value.trim());
        showCopyFeedback('invitationLink');
    });

    copyMessageBtn?.addEventListener('click', async () => {
        await copyToClipboard(messageInput.value.trim());
        showCopyFeedback('invitationMessage');
    });

    copyFullBtn?.addEventListener('click', async () => {
        await copyToClipboard(buildFullMessage());
        showCopyFeedback('invitationFull');
    });
}

function initCouplePreview() {
    const brideInput = document.getElementById('bride_name');
    const groomInput = document.getElementById('groom_name');
    const previewBride = document.getElementById('couplePreviewBride');
    const previewGroom = document.getElementById('couplePreviewGroom');
    const previewMonogram = document.getElementById('couplePreviewMonogram');

    if (!brideInput || !groomInput || !previewBride || !previewGroom || !previewMonogram) {
        return;
    }

    const initialFromName = (name) => {
        const trimmed = name.trim();
        return trimmed ? trimmed.charAt(0).toUpperCase() : '';
    };

    const updateCouplePreview = () => {
        const bride = brideInput.value.trim();
        const groom = groomInput.value.trim();

        previewGroom.textContent = groom || '…';
        previewBride.textContent = bride || '…';

        const groomInitial = initialFromName(groom);
        const brideInitial = initialFromName(bride);
        previewMonogram.textContent = groomInitial || brideInitial
            ? `${groomInitial}&${brideInitial}`
            : '…';
    };

    brideInput.addEventListener('input', updateCouplePreview);
    groomInput.addEventListener('input', updateCouplePreview);
}

function initCountdownPreview() {
    const titleInput = document.getElementById('countdown_title');
    const dateInput = document.getElementById('wedding_date');
    const timeInput = document.getElementById('start_time');
    const pastInput = document.getElementById('countdown_message_past');
    const previewTitle = document.getElementById('countdownPreviewTitle');
    const previewDate = document.getElementById('countdownPreviewDate');
    const previewDays = document.getElementById('countdownPreviewDays');
    const previewHours = document.getElementById('countdownPreviewHours');
    const previewMinutes = document.getElementById('countdownPreviewMinutes');
    const previewSeconds = document.getElementById('countdownPreviewSeconds');

    if (!titleInput || !dateInput || !timeInput || !previewTitle || !previewDays) {
        return;
    }

    const pad = (value) => String(value).padStart(2, '0');

    const formatFrenchDate = (isoDate) => {
        if (!isoDate) {
            return '—';
        }
        const parts = isoDate.split('-').map(Number);
        if (parts.length !== 3) {
            return isoDate;
        }
        const months = [
            'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
        ];
        return `${parts[2]} ${months[parts[1] - 1]} ${parts[0]}`;
    };

    const getTargetDate = () => {
        const date = dateInput.value || '2026-09-15';
        const time = timeInput.value || '14:00';
        return new Date(`${date}T${time}:00`);
    };

    const updateCountdownPreview = () => {
        previewTitle.textContent = titleInput.value.trim() || 'Le grand jour approche';
        previewDate.textContent = formatFrenchDate(dateInput.value);

        const target = getTargetDate();
        const diff = target - new Date();

        if (Number.isNaN(target.getTime()) || diff <= 0) {
            previewTitle.textContent = (pastInput?.value.trim()) || "C'est aujourd'hui — le grand jour est arrivé !";
            previewDays.textContent = '0';
            previewHours.textContent = '00';
            previewMinutes.textContent = '00';
            previewSeconds.textContent = '00';
            return;
        }

        previewDays.textContent = String(Math.floor(diff / 86400000));
        previewHours.textContent = pad(Math.floor((diff / 3600000) % 24));
        previewMinutes.textContent = pad(Math.floor((diff / 60000) % 60));
        previewSeconds.textContent = pad(Math.floor((diff / 1000) % 60));
    };

    document.querySelectorAll('[data-countdown-preview]').forEach((el) => {
        el.addEventListener('input', updateCountdownPreview);
        el.addEventListener('change', updateCountdownPreview);
    });

    updateCountdownPreview();
    window.setInterval(updateCountdownPreview, 1000);
}

function initMusicPresetPreview() {
    const presetInputs = document.querySelectorAll('.js-music-preset');
    const customFields = document.getElementById('customMusicFields');
    const preview = document.getElementById('ambientMusicPreview');
    if (!presetInputs.length || !preview) {
        return;
    }

    const updatePreview = (input) => {
        const source = preview.querySelector('source');
        if (!source || !input.dataset.previewUrl) {
            return;
        }
        preview.pause();
        source.src = input.dataset.previewUrl;
        source.type = input.dataset.previewMime || 'audio/mpeg';
        preview.load();
    };

    presetInputs.forEach((input) => {
        input.addEventListener('change', () => {
            if (input.value === 'custom') {
                customFields?.classList.remove('d-none');
            } else {
                customFields?.classList.add('d-none');
            }
            if (input.checked) {
                updatePreview(input);
            }
        });
    });
}
