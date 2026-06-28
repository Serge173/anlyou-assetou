<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize(coupleLabel($settings) ?: 'Notre Mariage') ?> — <?= sanitize(brandName()) ?></title>
    <link rel="icon" type="image/png" href="<?= sanitize(brandLogoUrl()) ?>">
    <meta name="description" content="Invitation de mariage<?= coupleLabel($settings) !== '' ? ' de ' . sanitize(coupleLabel($settings, ' et ')) : '' ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet">
    <meta name="robots" content="noarchive, nosnippet">
    <link href="<?= sanitize(assetUrl('assets/css/style.css')) ?>" rel="stylesheet">
    <link href="<?= sanitize(assetUrl('assets/css/experience.css')) ?>" rel="stylesheet">
    <?php if (isProductionSite()): ?>
    <link href="<?= sanitize(assetUrl('assets/css/protect.css')) ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body data-bs-spy="scroll" data-bs-target="#mainNav" data-bs-offset="100" class="intro-active">

<audio id="ambientMusic" loop preload="auto">
    <source src="<?= sanitize(ambientMusicUrl($settings)) ?>" type="<?= sanitize(ambientMusicMime(ambientMusicPath($settings))) ?>">
</audio>

<button type="button"
        class="site-music-toggle"
        id="siteMusicToggle"
        data-music-toggle
        data-label-idle="Activer la musique"
        data-label-playing="Musique en cours"
        aria-label="Activer la musique d'ambiance"
        aria-pressed="false">
    <span class="site-music-toggle__icon" aria-hidden="true">
        <i class="bi bi-music-note-beamed music-icon-idle"></i>
        <span class="music-bars"><span></span><span></span><span></span></span>
    </span>
    <span class="music-btn-label site-music-toggle__label">Activer la musique</span>
</button>

<?php
$groomInitial = coupleInitial($settings['groom_name'] ?? null);
$brideInitial = coupleInitial($settings['bride_name'] ?? null);
$invitationCardBg = invitationCardImageUrl($settings);
$heroImageUrl = mediaUrl($settings['hero_image'] ?? defaultCouplePhotoPath());
?>

<!-- 1. Écran de chargement cinématographique -->
<div id="cinematicIntro">
    <div class="cinematic-inner">
        <div class="cinematic-monogram">
            <svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle class="mono-ring" cx="80" cy="80" r="68"/>
            </svg>
            <span class="mono-letters"><?= sanitize($groomInitial) ?><span class="mono-amp">&amp;</span><?= sanitize($brideInitial) ?></span>
        </div>
        <p class="cinematic-tagline">Une histoire d'amour, un jour unique, un souvenir éternel...</p>
        <div class="cinematic-music">
            <button type="button"
                    class="cinematic-music-btn"
                    data-music-toggle
                    data-label-idle="Activer la musique"
                    data-label-playing="Musique en cours"
                    aria-label="Activer la musique d'ambiance"
                    aria-pressed="false">
                <i class="bi bi-music-note-beamed me-2" aria-hidden="true"></i>
                <span class="music-btn-label">Activer la musique</span>
            </button>
        </div>
        <div class="cinematic-progress"><div class="cinematic-progress-bar"></div></div>
    </div>
</div>

<!-- 2. Carte d'invitation 3D -->
<div id="invitationOverlay">
    <canvas id="fireworksCanvas" class="fireworks-canvas" aria-hidden="true"></canvas>
    <div class="invite-scene">
        <div class="invite-card" id="inviteCard">
            <div class="invite-cover">
                <div class="invite-cover-front<?= $invitationCardBg ? ' invite-cover-front--photo' : '' ?>"<?php if ($invitationCardBg): ?> style="background-image: url('<?= sanitize($invitationCardBg) ?>')"<?php endif; ?>>
                    <span class="invite-cover-monogram"><?= sanitize($groomInitial) ?></span>
                    <span class="invite-cover-ampersand">&amp;</span>
                    <span class="invite-cover-monogram"><?= sanitize($brideInitial) ?></span>
                    <span class="invite-cover-hint">Toucher pour ouvrir</span>
                </div>
            </div>
            <div class="invite-inside">
                <span class="invite-inside-label">Vous êtes notre invité au mariage de&nbsp;:</span>
                <h2 class="invite-inside-names"><?= sanitize($settings['groom_name'] ?? '') ?><br>&amp; <?= sanitize($settings['bride_name'] ?? '') ?></h2>
                <p class="invite-inside-date"><?= formatFrenchDate($settings['wedding_date'] ?? '') ?></p>
                <button type="button" class="btn btn-gold invite-enter-btn" id="inviteEnterBtn">
                    Découvrir l'invitation
                </button>
            </div>
        </div>
    </div>
</div>

<div id="mainSite" class="main-site is-hidden">

<!-- Navigation -->
<nav id="mainNav" class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#hero">
            <img src="<?= sanitize(brandLogoUrl()) ?>" alt="<?= sanitize(brandName()) ?>" class="brand-logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#hero">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="#details">Détails</a></li>
                <li class="nav-item"><a class="nav-link" href="#rsvp">RSVP</a></li>
                <li class="nav-item"><a class="nav-link" href="#story">Notre Histoire</a></li>
                <li class="nav-item"><a class="nav-link" href="#album">Album</a></li>
                <li class="nav-item"><a class="nav-link" href="#guestbook">Livre d'or</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Section 1: Hero -->
<section id="hero" class="hero-section">
    <div class="hero-media" aria-hidden="true">
        <img class="hero-media-fill" src="<?= sanitize($heroImageUrl) ?>" alt="">
        <img class="hero-media-main" src="<?= sanitize($heroImageUrl) ?>" alt="">
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content text-center" data-aos="fade-up" data-aos-duration="1200">
        <p class="hero-subtitle"><?= sanitize($settings['welcome_title'] ?? 'Bienvenue à notre mariage') ?></p>
        <h1 class="hero-names">
            <?= sanitize($settings['groom_name'] ?? '') ?>
            <span class="ampersand">&amp;</span>
            <?= sanitize($settings['bride_name'] ?? '') ?>
        </h1>
        <div class="hero-divider"><span>♥</span></div>
        <p class="hero-message"><?= sanitize($settings['welcome_message'] ?? '') ?></p>
        <?php
        $invitationParts = preg_split('/\R\s*\R/u', trim($settings['invitation_text'] ?? ''), 2);
        $vipText = trim($invitationParts[0] ?? '');
        $inviteText = trim($invitationParts[1] ?? ($invitationParts[0] ?? ''));
        if (count($invitationParts) < 2) {
            $vipText = '';
            $inviteText = trim($settings['invitation_text'] ?? '');
        }
        ?>
        <?php if ($vipText !== ''): ?>
        <div class="hero-vip" data-aos="fade-up" data-aos-delay="200">
            <span class="hero-vip-badge">Invité VVIP</span>
            <p class="hero-vip-text"><?= sanitize($vipText) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($inviteText !== ''): ?>
        <p class="hero-invitation" data-aos="fade-up" data-aos-delay="300"><?= sanitize($inviteText) ?></p>
        <?php endif; ?>
        <a href="#details" class="btn btn-hero mt-4">
            Découvrir les détails
            <i class="bi bi-chevron-down ms-2"></i>
        </a>
    </div>
    <div class="hero-scroll-indicator">
        <span></span>
    </div>
</section>

<!-- Compteur jusqu'au mariage -->
<?php if (isCountdownEnabled($settings)): ?>
<section id="countdown" class="countdown-section"
    data-wedding="<?= sanitize(weddingDatetimeIso($settings)) ?>"
    data-countdown-past="<?= sanitize(countdownPastMessage($settings)) ?>">
    <div class="container">
        <div class="countdown-glass glass-card" data-aos="fade-up">
            <p class="countdown-label"><?= sanitize(countdownTitle($settings)) ?></p>
            <div class="countdown-grid">
                <div class="countdown-item">
                    <span class="countdown-value" id="cdDays">—</span>
                    <span class="countdown-unit">Jours</span>
                </div>
                <div class="countdown-sep">:</div>
                <div class="countdown-item">
                    <span class="countdown-value" id="cdHours">—</span>
                    <span class="countdown-unit">Heures</span>
                </div>
                <div class="countdown-sep">:</div>
                <div class="countdown-item">
                    <span class="countdown-value" id="cdMinutes">—</span>
                    <span class="countdown-unit">Minutes</span>
                </div>
                <div class="countdown-sep">:</div>
                <div class="countdown-item">
                    <span class="countdown-value" id="cdSeconds">—</span>
                    <span class="countdown-unit">Secondes</span>
                </div>
            </div>
            <p class="countdown-date"><?= formatFrenchDate($settings['wedding_date'] ?? '') ?></p>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Section 2: Détails du mariage -->
<section id="details" class="section-padding">
    <div class="container">
        <div class="section-header text-center" data-aos="fade-up">
            <span class="section-label">Les informations</span>
            <h2 class="section-title">Détails du Mariage</h2>
            <div class="title-divider"></div>
        </div>

        <div class="details-grid">
            <div class="row g-3 mt-2">
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="detail-card">
                        <div class="detail-icon"><i class="bi bi-calendar-heart"></i></div>
                        <h3>Date &amp; Heure</h3>
                        <p class="detail-main"><?= formatFrenchDate($settings['wedding_date'] ?? '') ?></p>
                        <p class="detail-sub"><?= sanitize($settings['start_time'] ?? '') ?> — <?= sanitize($settings['end_time'] ?? '') ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="150">
                    <div class="detail-card">
                        <div class="detail-icon"><i class="bi bi-moon-stars"></i></div>
                        <h3>Célébration du mariage</h3>
                        <p class="detail-main"><?= nl2br(sanitize($settings['religious_venue'] ?? '')) ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="detail-card">
                        <div class="detail-icon"><i class="bi bi-cup-straw"></i></div>
                        <h3>La réception</h3>
                        <p class="detail-main"><?= nl2br(sanitize($settings['reception_venue'] ?? '')) ?></p>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="250">
                    <div class="detail-card">
                        <div class="detail-icon"><i class="bi bi-music-note-beamed"></i></div>
                        <h3>Danse de réjouissance</h3>
                        <p class="detail-main"><?= nl2br(sanitize($settings['civil_venue'] ?? '')) ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="detail-card">
                        <div class="detail-icon"><i class="bi bi-geo-alt"></i></div>
                        <h3>Coordonnées GPS</h3>
                        <p class="detail-main"><?= number_format((float)($settings['gps_lat'] ?? 0), 4) ?>, <?= number_format((float)($settings['gps_lng'] ?? 0), 4) ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="350">
                    <div class="detail-card">
                        <div class="detail-icon"><i class="bi bi-map"></i></div>
                        <h3>Itinéraire</h3>
                        <p class="detail-main">Trouvez le chemin jusqu'au lieu</p>
                        <a href="<?= sanitize(mapDirectionsUrl($settings)) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-gold btn-sm detail-map-btn">
                            Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="map-container mt-4" data-aos="fade-up">
            <iframe
                src="<?= sanitize(mapEmbedUrl($settings)) ?>"
                width="100%" height="280" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Carte du lieu du mariage">
            </iframe>
        </div>
    </div>
</section>

<!-- Section 3: RSVP -->
<section id="rsvp" class="section-padding section-alt rsvp-modern">
    <div class="container">
        <div class="section-header text-center" data-aos="fade-up">
            <span class="section-label">Confirmez votre présence</span>
            <h2 class="section-title">RSVP</h2>
            <div class="title-divider"></div>
            <p class="section-desc">Serez-vous parmi nous ?</p>
        </div>

        <div class="rsvp-wrapper mx-auto" data-aos="fade-up" data-aos-delay="200">
            <div class="rsvp-choice-modern">
                <button type="button" class="rsvp-big-btn rsvp-big-yes" data-choice="yes">
                    <span>✅</span> Avec joie, je serai présent(e)
                </button>
                <button type="button" class="rsvp-big-btn rsvp-big-no" data-choice="no">
                    <span>❌</span> Malheureusement non
                </button>
            </div>

            <div class="rsvp-forms-stage">
                <!-- Formulaire OUI — glisse depuis la droite -->
                <div id="rsvpConfirmPanel" class="rsvp-form-panel">
                    <form id="rsvpConfirmForm" class="rsvp-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Prénom *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lien avec les mariés *</label>
                                <select name="relationship" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    <?php foreach (relationshipOptions() as $opt): ?>
                                    <option value="<?= sanitize($opt) ?>"><?= sanitize($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre d'accompagnants</label>
                                <input type="number" name="companions" class="form-control" min="0" max="10" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message aux mariés</label>
                                <textarea name="message" class="form-control" rows="3" placeholder="Un mot doux pour les mariés..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gold w-100 mt-4">
                            <i class="bi bi-send me-2"></i>Confirmer ma présence
                        </button>
                    </form>
                </div>

                <!-- Formulaire NON — glisse depuis la gauche -->
                <div id="rsvpDeclinePanel" class="rsvp-form-panel from-left">
                    <form id="rsvpDeclineForm" class="rsvp-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Prénom *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Motif d'absence *</label>
                                <select name="reason" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    <?php foreach (declineReasonOptions() as $opt): ?>
                                    <option value="<?= sanitize($opt) ?>"><?= sanitize($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message aux mariés</label>
                                <textarea name="message" class="form-control" rows="4" placeholder="Un mot d'encouragement ou de félicitations..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gold w-100 mt-4">
                            <i class="bi bi-envelope me-2"></i>Envoyer mon message
                        </button>
                    </form>
                </div>
            </div>

            <div id="rsvpAlert" class="alert mt-4 d-none" role="alert"></div>
        </div>
    </div>
</section>

<!-- Section 4: Notre histoire -->
<section id="story" class="section-padding gallery-premium">
    <div class="container">
        <div class="section-header text-center" data-aos="fade-up">
            <span class="section-label">Nos souvenirs</span>
            <h2 class="section-title">Galerie des Souvenirs</h2>
            <div class="title-divider"></div>
        </div>

        <?php if ($coupleVideoUrl = coupleVideoUrl()): ?>
        <div class="couple-video-block mb-5" data-aos="fade-up">
            <div class="story-album-header text-center mb-4">
                <h3 class="story-album-title">Notre histoire en vidéo</h3>
                <p class="story-album-desc">Un aperçu de notre amour, avant le grand jour</p>
            </div>
            <div class="couple-video-frame glass-card">
                <video controls playsinline preload="metadata" class="couple-video-player">
                    <source src="<?= sanitize($coupleVideoUrl) ?>" type="video/mp4">
                </video>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($galleryPhotos)): ?>
        <div class="photo-carousel photo-carousel--hero mb-5" data-aos="fade-up">
            <button type="button" class="photo-carousel-btn photo-carousel-prev" aria-label="Photo précédente">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <div class="photo-carousel-viewport">
                <div class="photo-carousel-track">
                    <?php foreach ($galleryPhotos as $photo): ?>
                    <div class="photo-carousel-slide photo-carousel-slide--hero">
                        <img src="<?= sanitize(mediaUrl($photo['file_path'])) ?>" alt="<?= sanitize($photo['title'] ?? '') ?>" loading="lazy">
                        <div class="slider-caption"><?= sanitize($photo['title'] ?? $photo['album_name'] ?? '') ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="photo-carousel-btn photo-carousel-next" aria-label="Photo suivante">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
        <?php endif; ?>

        <?php foreach ($galleryAlbums as $album): ?>
        <?php if (empty($album['photos'])) continue; ?>
        <div class="story-album-block mb-5" data-aos="fade-up">
            <div class="story-album-header text-center mb-4">
                <h3 class="story-album-title"><?= sanitize($album['name']) ?></h3>
                <?php if (!empty($album['description'])): ?>
                <p class="story-album-desc"><?= sanitize($album['description']) ?></p>
                <?php endif; ?>
            </div>
            <div class="photo-carousel photo-carousel--gallery">
                <button type="button" class="photo-carousel-btn photo-carousel-prev" aria-label="Photo précédente">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <div class="photo-carousel-viewport">
                    <div class="photo-carousel-track">
                        <?php foreach ($album['photos'] as $i => $photo): ?>
                        <a href="<?= sanitize(mediaUrl($photo['file_path'])) ?>" class="gallery-item glightbox photo-carousel-slide" data-gallery="story-<?= (int) $album['id'] ?>" data-aos="fade-up" data-aos-delay="<?= ($i % 6) * 80 ?>">
                            <img src="<?= sanitize(mediaUrl($photo['file_path'])) ?>" alt="<?= sanitize($photo['title'] ?? '') ?>" loading="lazy">
                            <div class="gallery-overlay">
                                <span><?= sanitize($photo['title'] ?? '') ?></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="photo-carousel-btn photo-carousel-next" aria-label="Photo suivante">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($galleryAlbums) || empty($galleryPhotos)): ?>
        <p class="text-center text-muted">Les photos de notre histoire seront bientôt disponibles.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Section 5: Album du mariage -->
<section id="album" class="section-padding section-alt">
    <div class="container">
        <div class="section-header text-center" data-aos="fade-up">
            <span class="section-label">Le grand jour</span>
            <h2 class="section-title">Album du Mariage</h2>
            <div class="title-divider"></div>
        </div>

        <?php if (!$settings['album_enabled'] && !$settings['wedding_passed']): ?>
        <div class="album-placeholder text-center" data-aos="fade-up">
            <div class="album-placeholder-icon"><i class="bi bi-camera"></i></div>
            <p class="album-placeholder-text">
                Cette galerie sera disponible après notre mariage.<br>
                Revenez bientôt pour revivre avec nous les plus beaux moments de cette journée exceptionnelle.
            </p>
        </div>
        <?php else: ?>
        <?php foreach ($weddingSections as $section): ?>
        <?php if (empty($section['media'])) continue; ?>
        <div class="wedding-section-block mb-5" data-aos="fade-up">
            <div class="story-album-header text-center mb-4">
                <h3 class="story-album-title"><?= sanitize($section['name']) ?></h3>
                <?php if (!empty($section['description'])): ?>
                <p class="story-album-desc"><?= sanitize($section['description']) ?></p>
                <?php endif; ?>
            </div>
            <div class="photo-carousel photo-carousel--gallery">
                <button type="button" class="photo-carousel-btn photo-carousel-prev" aria-label="Photo précédente">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <div class="photo-carousel-viewport">
                    <div class="photo-carousel-track">
                        <?php foreach ($section['media'] as $i => $item): ?>
                        <?php if (($item['media_type'] ?? 'image') === 'video'): ?>
                        <div class="photo-carousel-slide photo-carousel-slide--video">
                            <div class="wedding-album-item glass-card">
                                <video controls class="w-100">
                                    <source src="<?= sanitize(mediaUrl($item['file_path'])) ?>" type="video/mp4">
                                </video>
                                <div class="wedding-album-info">
                                    <h4><?= sanitize($item['title'] ?? '') ?></h4>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <a href="<?= sanitize(mediaUrl($item['file_path'])) ?>" class="gallery-item glightbox photo-carousel-slide" data-gallery="wedding-<?= (int) $section['id'] ?>">
                            <img src="<?= sanitize(mediaUrl($item['file_path'])) ?>" alt="<?= sanitize($item['title'] ?? '') ?>" loading="lazy">
                            <div class="gallery-overlay">
                                <span><?= sanitize($item['title'] ?? '') ?></span>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="photo-carousel-btn photo-carousel-next" aria-label="Photo suivante">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($weddingSections)): ?>
        <p class="text-center text-muted mt-4">Les photos seront bientôt disponibles.</p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Section 6: Livre d'or -->
<section id="guestbook" class="section-padding">
    <div class="container">
        <div class="section-header text-center" data-aos="fade-up">
            <span class="section-label">Vos vœux</span>
            <h2 class="section-title">Livre d'Or</h2>
            <div class="title-divider"></div>
            <p class="section-desc">Laissez-nous un message de bonheur et de félicitations</p>
        </div>

        <div class="row g-5">
            <div class="col-lg-5" data-aos="fade-right">
                <form id="guestbookForm" class="guestbook-form">
                    <div class="mb-3">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Inspirations de vœux</label>
                        <div class="guestbook-suggestions">
                            <button type="button" class="guestbook-chip" data-message="Félicitations pour votre union.">Félicitations pour votre union</button>
                            <button type="button" class="guestbook-chip" data-message="Que Dieu bénisse votre foyer.">Que Dieu bénisse votre foyer</button>
                            <button type="button" class="guestbook-chip" data-message="Beaucoup de bonheur à vous deux.">Beaucoup de bonheur</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message de vœux *</label>
                        <textarea name="message" class="form-control" rows="4" required placeholder="Votre message du cœur..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-gold w-100">
                        <i class="bi bi-heart me-2"></i>Envoyer mon vœu
                    </button>
                    <div id="guestbookAlert" class="alert mt-3 d-none"></div>
                </form>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="guestbook-cloud" id="guestbookCloud">
                    <?php if (empty($guestbookEntries)): ?>
                    <p class="guestbook-empty-state">Soyez le premier à laisser un vœu flottant de bonheur !</p>
                    <?php else: ?>
                    <?php foreach ($guestbookEntries as $i => $entry): ?>
                    <div class="guestbook-float-card" style="--delay: <?= $i * 0.15 ?>s">
                        <div class="guestbook-card-header">
                            <span class="guestbook-name"><?= sanitize($entry['first_name']) ?> <?= sanitize($entry['last_name']) ?></span>
                            <span class="guestbook-date"><?= formatFrenchDate(substr($entry['created_at'], 0, 10)) ?></span>
                        </div>
                        <p class="guestbook-message"><?= sanitize($entry['message']) ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="site-footer">
    <div class="container text-center">
        <p class="footer-names"><?= sanitize(coupleLabel($settings)) ?></p>
        <p class="footer-date"><?= formatFrenchDate($settings['wedding_date'] ?? '') ?></p>
        <p class="footer-copy">&copy; <?= date('Y') ?> — Avec tout notre amour</p>
    </div>
</footer>

</div><!-- #mainSite -->

<button type="button" class="scroll-to-top" id="scrollToTop" aria-label="Remonter en haut de la page" title="Retour en haut">
    <i class="bi bi-chevron-up" aria-hidden="true"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<?php if (isProductionSite()): ?>
<script src="<?= sanitize(assetUrl('assets/js/protect.js')) ?>" defer></script>
<?php endif; ?>
<script src="<?= sanitize(assetUrl('assets/js/experience.js')) ?>" defer></script>
<script src="<?= sanitize(assetUrl('assets/js/main.js')) ?>" defer></script>
</body>
</html>
