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

    const createFireworksShow = (canvas) => {
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return { play: () => Promise.resolve() };
        }

        const colors = ['#C9A227', '#E8C96A', '#FFFFFF', '#FF8C42', '#2ECC71', '#F4D03F', '#FF6B9D'];
        let width = 0;
        let height = 0;
        let frameId = null;
        let running = false;
        let rockets = [];
        let particles = [];
        let sparks = [];
        let launchTimers = [];

        const resize = () => {
            width = window.innerWidth;
            height = window.innerHeight;
            canvas.width = width;
            canvas.height = height;
        };

        const rand = (min, max) => min + Math.random() * (max - min);

        const pickColor = () => colors[Math.floor(Math.random() * colors.length)];

        const launchRocket = (targetX, targetY, color = pickColor()) => {
            rockets.push({
                x: rand(width * 0.15, width * 0.85),
                y: height + 12,
                targetX,
                targetY,
                color,
                speed: rand(7.5, 10.5),
                trail: [],
            });
        };

        const explode = (x, y, color, size = 1) => {
            const count = Math.floor(rand(55, 85) * size);
            for (let i = 0; i < count; i += 1) {
                const angle = (Math.PI * 2 * i) / count + rand(-0.08, 0.08);
                const speed = rand(2.2, 7.5) * size;
                particles.push({
                    x,
                    y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed,
                    color,
                    alpha: 1,
                    decay: rand(0.012, 0.022),
                    size: rand(1.4, 2.8),
                    gravity: rand(0.04, 0.07),
                    friction: rand(0.96, 0.985),
                });
            }

            for (let i = 0; i < 18; i += 1) {
                const angle = rand(0, Math.PI * 2);
                const speed = rand(0.5, 2.2);
                sparks.push({
                    x,
                    y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed,
                    color: '#FFFFFF',
                    alpha: rand(0.6, 1),
                    decay: rand(0.02, 0.04),
                    size: rand(0.8, 1.6),
                });
            }
        };

        const draw = () => {
            ctx.globalCompositeOperation = 'destination-out';
            ctx.fillStyle = 'rgba(0, 0, 0, 0.18)';
            ctx.fillRect(0, 0, width, height);
            ctx.globalCompositeOperation = 'lighter';

            rockets = rockets.filter((rocket) => {
                const dx = rocket.targetX - rocket.x;
                const dy = rocket.targetY - rocket.y;
                const dist = Math.hypot(dx, dy) || 1;
                const vx = (dx / dist) * rocket.speed;
                const vy = (dy / dist) * rocket.speed;

                rocket.trail.push({ x: rocket.x, y: rocket.y, alpha: 1 });
                if (rocket.trail.length > 14) {
                    rocket.trail.shift();
                }

                rocket.x += vx;
                rocket.y += vy;

                rocket.trail.forEach((point, index) => {
                    point.alpha = index / rocket.trail.length;
                    ctx.beginPath();
                    ctx.fillStyle = rocket.color;
                    ctx.globalAlpha = point.alpha * 0.55;
                    ctx.arc(point.x, point.y, 2.2, 0, Math.PI * 2);
                    ctx.fill();
                });

                ctx.beginPath();
                ctx.fillStyle = '#FFFFFF';
                ctx.globalAlpha = 0.95;
                ctx.arc(rocket.x, rocket.y, 2.5, 0, Math.PI * 2);
                ctx.fill();
                ctx.globalAlpha = 1;

                if (dist < rocket.speed + 4) {
                    explode(rocket.x, rocket.y, rocket.color);
                    if (Math.random() > 0.35) {
                        const burstX = rocket.x + rand(-30, 30);
                        const burstY = rocket.y + rand(-20, 20);
                        launchTimers.push(window.setTimeout(() => {
                            if (running) {
                                explode(burstX, burstY, pickColor(), 0.65);
                            }
                        }, rand(180, 320)));
                    }
                    return false;
                }

                return true;
            });

            particles = particles.filter((particle) => {
                particle.vx *= particle.friction;
                particle.vy *= particle.friction;
                particle.vy += particle.gravity;
                particle.x += particle.vx;
                particle.y += particle.vy;
                particle.alpha -= particle.decay;

                if (particle.alpha <= 0) {
                    return false;
                }

                ctx.beginPath();
                ctx.fillStyle = particle.color;
                ctx.globalAlpha = particle.alpha;
                ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
                ctx.fill();
                return true;
            });

            sparks = sparks.filter((spark) => {
                spark.x += spark.vx;
                spark.y += spark.vy;
                spark.alpha -= spark.decay;

                if (spark.alpha <= 0) {
                    return false;
                }

                ctx.beginPath();
                ctx.fillStyle = spark.color;
                ctx.globalAlpha = spark.alpha;
                ctx.arc(spark.x, spark.y, spark.size, 0, Math.PI * 2);
                ctx.fill();
                return true;
            });

            ctx.globalAlpha = 1;
        };

        const tick = () => {
            if (!running) {
                return;
            }
            draw();
            frameId = window.requestAnimationFrame(tick);
        };

        const scheduleBursts = (durationMs) => {
            const start = performance.now();
            const planBurst = () => {
                if (!running) {
                    return;
                }
                const elapsed = performance.now() - start;
                if (elapsed >= durationMs - 400) {
                    return;
                }
                const x = rand(width * 0.12, width * 0.88);
                const y = rand(height * 0.12, height * 0.55);
                launchRocket(x, y, pickColor());
                launchTimers.push(window.setTimeout(planBurst, rand(220, 480)));
            };

            planBurst();
            launchTimers.push(window.setTimeout(planBurst, 120));
            launchTimers.push(window.setTimeout(planBurst, 280));
            launchTimers.push(window.setTimeout(planBurst, 520));
            launchTimers.push(window.setTimeout(planBurst, 760));
            launchTimers.push(window.setTimeout(planBurst, 980));
            launchTimers.push(window.setTimeout(planBurst, 1200));
            launchTimers.push(window.setTimeout(planBurst, 1480));
            launchTimers.push(window.setTimeout(planBurst, 1750));
            launchTimers.push(window.setTimeout(planBurst, 2050));
        };

        const clear = () => {
            launchTimers.forEach((timer) => window.clearTimeout(timer));
            launchTimers = [];
            rockets = [];
            particles = [];
            sparks = [];
            ctx.clearRect(0, 0, width, height);
        };

        const stop = () => {
            running = false;
            if (frameId) {
                window.cancelAnimationFrame(frameId);
                frameId = null;
            }
            clear();
        };

        const play = (durationMs = 3200) => new Promise((resolve) => {
            resize();
            running = true;
            scheduleBursts(durationMs);
            tick();
            window.setTimeout(() => {
                stop();
                resolve();
            }, durationMs);
        });

        window.addEventListener('resize', resize);

        return { play, stop };
    };

    const flashFireworks = (overlay) => {
        const flash = document.createElement('div');
        flash.className = 'fireworks-flash';
        overlay.appendChild(flash);
        window.setTimeout(() => flash.remove(), 700);
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

        const fireworksCanvas = document.getElementById('fireworksCanvas');
        const fireworks = createFireworksShow(fireworksCanvas);
        let isRevealing = false;

        const playFireworksAndReveal = async () => {
            if (isRevealing) {
                return;
            }
            isRevealing = true;
            enterBtn?.setAttribute('disabled', 'disabled');
            enterBtn?.setAttribute('aria-busy', 'true');

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                revealSite();
                return;
            }

            cardOverlay.classList.add('is-fireworks');
            flashFireworks(cardOverlay);

            await fireworks.play(3400);

            cardOverlay.classList.remove('is-fireworks');
            revealSite();
        };

        enterBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            playFireworksAndReveal();
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
        document.querySelectorAll('.photo-carousel .gallery-item, .gallery-masonry .gallery-item').forEach(item => {
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
