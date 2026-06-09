/**
 * Expériences premium : intro cinématique, carte 3D, RSVP animé, livre d'or, musique
 */
(function () {
    const initAmbientMusic = () => {
        const audio = document.getElementById('ambientMusic');
        const floatToggle = document.getElementById('siteMusicToggle');
        if (!audio) return;

        audio.volume = 0.48;

        const getToggles = () => document.querySelectorAll('[data-music-toggle]');

        const syncUi = () => {
            const playing = !audio.paused && !audio.ended;
            getToggles().forEach((btn) => {
                btn.classList.toggle('is-playing', playing);
                btn.setAttribute('aria-pressed', playing ? 'true' : 'false');
                const label = btn.querySelector('.music-btn-label');
                if (!label) return;
                label.textContent = playing
                    ? (btn.dataset.labelPlaying || 'Musique en cours')
                    : (btn.dataset.labelIdle || 'Activer la musique');
            });
        };

        const markUnavailable = () => {
            getToggles().forEach((btn) => {
                btn.disabled = true;
                btn.classList.add('is-unavailable');
                btn.setAttribute('aria-label', 'Musique indisponible');
                const label = btn.querySelector('.music-btn-label');
                if (label) label.textContent = 'Musique indisponible';
            });
        };

        const toggleMusic = async (btn) => {
            if (btn.disabled) return;

            if (audio.paused) {
                try {
                    await audio.play();
                } catch {
                    /* Autoplay bloqué ou erreur — l’UI reste synchronisée */
                }
            } else {
                audio.pause();
            }
            syncUi();
        };

        getToggles().forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                toggleMusic(btn);
            });
        });

        audio.addEventListener('play', syncUi);
        audio.addEventListener('pause', syncUi);
        audio.addEventListener('ended', syncUi);
        audio.addEventListener('error', markUnavailable);

        const revealToggle = () => {
            floatToggle?.classList.add('is-visible');
        };

        if (document.readyState === 'complete') {
            setTimeout(revealToggle, 400);
        } else {
            window.addEventListener('load', () => setTimeout(revealToggle, 400), { once: true });
        }

        syncUi();
        return { revealToggle, syncUi };
    };

    const initIntro = (music) => {
        const intro = document.getElementById('cinematicIntro');
        const cardOverlay = document.getElementById('invitationOverlay');
        const mainSite = document.getElementById('mainSite');
        if (!intro || !cardOverlay || !mainSite) {
            music?.revealToggle();
            return;
        }

        document.body.classList.add('intro-active');
        mainSite.classList.add('is-hidden');
        music?.revealToggle();

        // Phase 1 : écran cinématographique (~5.5s pour laisser le temps d’activer la musique)
        setTimeout(() => {
            intro.classList.add('is-done');
            setTimeout(() => intro.remove(), 1200);
        }, 5500);

        // Phase 2 : carte 3D
        const card = document.getElementById('inviteCard');
        const enterBtn = document.getElementById('inviteEnterBtn');
        let cardOpened = false;

        card?.addEventListener('click', () => {
            if (cardOpened) return;
            cardOpened = true;
            card.classList.add('is-open');
        });

        setTimeout(() => {
            cardOverlay.classList.add('is-ready');
        }, 5600);

        const revealSite = () => {
            cardOverlay.classList.add('is-hidden');
            mainSite.classList.remove('is-hidden');
            mainSite.classList.add('is-visible');
            document.body.classList.remove('intro-active');
            setTimeout(() => cardOverlay.remove(), 1000);
            window.dispatchEvent(new Event('siteReady'));
        };

        enterBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            revealSite();
        });

        setTimeout(() => {
            if (!cardOpened && card) {
                card.classList.add('is-open');
                cardOpened = true;
            }
        }, 8500);
    };

    const initRsvpModern = () => {
        const yesBtn = document.querySelector('.rsvp-big-yes');
        const noBtn = document.querySelector('.rsvp-big-no');
        const confirmPanel = document.getElementById('rsvpConfirmPanel');
        const declinePanel = document.getElementById('rsvpDeclinePanel');
        const stage = document.querySelector('.rsvp-forms-stage');

        if (!yesBtn || !noBtn || !confirmPanel || !declinePanel) return;

        const showPanel = (panel) => {
            confirmPanel.classList.remove('is-active');
            declinePanel.classList.remove('is-active');
            panel.classList.add('is-active');
            stage?.classList.add('has-form');
        };

        yesBtn.addEventListener('click', () => {
            yesBtn.classList.add('active');
            noBtn.classList.remove('active');
            showPanel(confirmPanel);
        });

        noBtn.addEventListener('click', () => {
            noBtn.classList.add('active');
            yesBtn.classList.remove('active');
            showPanel(declinePanel);
        });
    };

    const initGuestbookInteractive = () => {
        const textarea = document.querySelector('#guestbookForm textarea[name="message"]');
        document.querySelectorAll('.guestbook-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                if (textarea) {
                    textarea.value = chip.dataset.message || chip.textContent.trim();
                    textarea.focus();
                }
            });
        });

        document.querySelectorAll('.guestbook-float-card').forEach((card, i) => {
            card.style.setProperty('--delay', `${i * 0.15}s`);
        });
    };

    const initGalleryPremium = () => {
        document.querySelectorAll('.gallery-masonry .gallery-item').forEach(item => {
            item.addEventListener('click', () => {
                item.classList.add('is-opening');
                setTimeout(() => item.classList.remove('is-opening'), 350);
            });
        });
    };

    const addGuestbookFloatCard = (entry) => {
        const cloud = document.getElementById('guestbookCloud');
        if (!cloud) return;

        const empty = cloud.querySelector('.guestbook-empty-state');
        empty?.remove();

        const card = document.createElement('div');
        card.className = 'guestbook-float-card';
        card.style.setProperty('--delay', '0s');
        card.innerHTML = `
            <div class="guestbook-card-header">
                <span class="guestbook-name">${escapeHtml(entry.first_name)} ${escapeHtml(entry.last_name)}</span>
            </div>
            <p class="guestbook-message">${escapeHtml(entry.message)}</p>
        `;
        cloud.insertBefore(card, cloud.firstChild);
    };

    const escapeHtml = (str) => {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    };

    window.addGuestbookFloatCard = addGuestbookFloatCard;

    document.addEventListener('DOMContentLoaded', () => {
        const music = initAmbientMusic();
        initIntro(music);
        initRsvpModern();
        initGuestbookInteractive();
        initGalleryPremium();
    });
})();
