document.addEventListener('DOMContentLoaded', () => {
    const initSite = () => {
        AOS.init({ duration: 800, once: true, offset: 80 });

        GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: true,
            openEffect: 'zoom',
            closeEffect: 'fade',
        });
    };

    if (document.getElementById('mainSite')?.classList.contains('is-visible')) {
        initSite();
    } else {
        window.addEventListener('siteReady', initSite, { once: true });
    }

    const navbar = document.getElementById('mainNav');
    const scrollToTopBtn = document.getElementById('scrollToTop');
    const handleScroll = () => {
        if (window.scrollY > 60) {
            navbar?.classList.add('scrolled');
        } else {
            navbar?.classList.remove('scrolled');
        }
        if (scrollToTopBtn) {
            scrollToTopBtn.classList.toggle('is-visible', window.scrollY > 400);
        }
    };
    window.addEventListener('scroll', handleScroll);
    handleScroll();

    scrollToTopBtn?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', (e) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    const slider = document.getElementById('storySlider');
    if (slider) {
        slider.innerHTML = slider.innerHTML + slider.innerHTML;
    }

    const showAlert = (el, message, type = 'success') => {
        el.textContent = message;
        el.className = `alert mt-4 alert-${type}`;
        el.classList.remove('d-none');
        setTimeout(() => el.classList.add('d-none'), 6000);
    };

    const confirmForm = document.getElementById('rsvpConfirmForm');
    confirmForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const alert = document.getElementById('rsvpAlert');
        const data = Object.fromEntries(new FormData(confirmForm));
        try {
            const res = await fetch('/api/rsvp/confirm', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            if (json.success) {
                showAlert(alert, json.message, 'success');
                confirmForm.reset();
            } else {
                showAlert(alert, json.message || 'Une erreur est survenue.', 'danger');
            }
        } catch {
            showAlert(alert, 'Erreur de connexion. Veuillez réessayer.', 'danger');
        }
    });

    const declineForm = document.getElementById('rsvpDeclineForm');
    declineForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const alert = document.getElementById('rsvpAlert');
        const data = Object.fromEntries(new FormData(declineForm));
        try {
            const res = await fetch('/api/rsvp/decline', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            if (json.success) {
                showAlert(alert, json.message, 'success');
                declineForm.reset();
            } else {
                showAlert(alert, json.message || 'Une erreur est survenue.', 'danger');
            }
        } catch {
            showAlert(alert, 'Erreur de connexion. Veuillez réessayer.', 'danger');
        }
    });

    const guestbookForm = document.getElementById('guestbookForm');
    guestbookForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const alert = document.getElementById('guestbookAlert');
        const data = Object.fromEntries(new FormData(guestbookForm));
        try {
            const res = await fetch('/api/guestbook', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            if (json.success) {
                alert.textContent = json.message;
                alert.className = 'alert mt-3 alert-success';
                alert.classList.remove('d-none');
                if (typeof window.addGuestbookFloatCard === 'function') {
                    window.addGuestbookFloatCard(data);
                }
                guestbookForm.reset();
            } else {
                alert.textContent = json.message || 'Une erreur est survenue.';
                alert.className = 'alert mt-3 alert-danger';
                alert.classList.remove('d-none');
            }
        } catch {
            alert.textContent = 'Erreur de connexion.';
            alert.className = 'alert mt-3 alert-danger';
            alert.classList.remove('d-none');
        }
    });

    document.querySelectorAll('.share-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const raw = btn.dataset.url || '';
            const url = raw.startsWith('http') ? raw : window.location.origin + raw;
            if (navigator.share) {
                navigator.share({ title: 'Notre Mariage', url });
            } else {
                navigator.clipboard.writeText(url);
                btn.innerHTML = '<i class="bi bi-check"></i>';
                setTimeout(() => { btn.innerHTML = '<i class="bi bi-share"></i>'; }, 2000);
            }
        });
    });

    const countdownEl = document.getElementById('countdown');
    if (countdownEl) {
        const weddingDate = new Date(countdownEl.dataset.wedding);
        const daysEl = document.getElementById('cdDays');
        const hoursEl = document.getElementById('cdHours');
        const minutesEl = document.getElementById('cdMinutes');
        const secondsEl = document.getElementById('cdSeconds');
        const pad = (n) => String(n).padStart(2, '0');

        const tick = () => {
            const diff = weddingDate - new Date();
            if (diff <= 0) {
                countdownEl.classList.add('is-past');
                const label = countdownEl.querySelector('.countdown-label');
                if (label) label.textContent = "C'est aujourd'hui — le grand jour est arrivé !";
                if (daysEl) daysEl.textContent = '0';
                if (hoursEl) hoursEl.textContent = '00';
                if (minutesEl) minutesEl.textContent = '00';
                if (secondsEl) secondsEl.textContent = '00';
                return;
            }
            if (daysEl) daysEl.textContent = Math.floor(diff / 86400000);
            if (hoursEl) hoursEl.textContent = pad(Math.floor((diff / 3600000) % 24));
            if (minutesEl) minutesEl.textContent = pad(Math.floor((diff / 60000) % 60));
            if (secondsEl) secondsEl.textContent = pad(Math.floor((diff / 1000) % 60));
        };
        tick();
        setInterval(tick, 1000);
    }
});
