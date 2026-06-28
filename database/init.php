<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/default-media.php';

function initializeDatabase(PDO $pdo): void
{
    $isPgsql = isPostgres($pdo);

    $statements = getSchemaStatements($isPgsql);
    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    runMigrations($pdo);
    seedDefaultData($pdo);
}

function runMigrations(PDO $pdo): void
{
    $isPgsql = isPostgres($pdo);

    if (!tableExists($pdo, 'wedding_sections')) {
        if ($isPgsql) {
            $pdo->exec("CREATE TABLE wedding_sections (
                id SERIAL PRIMARY KEY,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                sort_order INTEGER DEFAULT 0,
                created_at TIMESTAMPTZ DEFAULT NOW()
            )");
        } else {
            $pdo->exec("CREATE TABLE wedding_sections (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                sort_order INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )");
        }
    }

    if (tableExists($pdo, 'wedding_album') && !columnExists($pdo, 'wedding_album', 'section_id')) {
        if ($isPgsql) {
            $pdo->exec('ALTER TABLE wedding_album ADD COLUMN section_id INTEGER REFERENCES wedding_sections(id) ON DELETE SET NULL');
        } else {
            $pdo->exec('ALTER TABLE wedding_album ADD COLUMN section_id INTEGER');
        }
    }

    if (tableExists($pdo, 'settings') && !columnExists($pdo, 'settings', 'countdown_title')) {
        if ($isPgsql) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN countdown_title TEXT DEFAULT 'Le grand jour approche'");
            $pdo->exec("ALTER TABLE settings ADD COLUMN countdown_message_past TEXT DEFAULT 'C''est aujourd''hui — le grand jour est arrivé !'");
            $pdo->exec('ALTER TABLE settings ADD COLUMN countdown_enabled BOOLEAN DEFAULT TRUE');
        } else {
            $pdo->exec("ALTER TABLE settings ADD COLUMN countdown_title TEXT DEFAULT 'Le grand jour approche'");
            $pdo->exec("ALTER TABLE settings ADD COLUMN countdown_message_past TEXT DEFAULT 'C''est aujourd''hui — le grand jour est arrivé !'");
            $pdo->exec('ALTER TABLE settings ADD COLUMN countdown_enabled INTEGER DEFAULT 1');
        }
    }

    if (tableExists($pdo, 'settings') && !columnExists($pdo, 'settings', 'invitation_card_image')) {
        if ($isPgsql) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN invitation_card_image TEXT DEFAULT 'assets/images/invitation-card-bg.png'");
        } else {
            $pdo->exec("ALTER TABLE settings ADD COLUMN invitation_card_image TEXT DEFAULT 'assets/images/invitation-card-bg.png'");
        }
        $pdo->exec("UPDATE settings SET invitation_card_image = 'assets/images/invitation-card-bg.png' WHERE id = 1 AND (invitation_card_image IS NULL OR invitation_card_image = '')");
    }

    if (tableExists($pdo, 'settings') && !columnExists($pdo, 'settings', 'ambient_music')) {
        if ($isPgsql) {
            $pdo->exec('ALTER TABLE settings ADD COLUMN ambient_music TEXT DEFAULT NULL');
        } else {
            $pdo->exec('ALTER TABLE settings ADD COLUMN ambient_music TEXT DEFAULT NULL');
        }
    }

    if (tableExists($pdo, 'settings') && !columnExists($pdo, 'settings', 'invitation_share_message')) {
        if ($isPgsql) {
            $pdo->exec('ALTER TABLE settings ADD COLUMN invitation_share_message TEXT DEFAULT NULL');
        } else {
            $pdo->exec('ALTER TABLE settings ADD COLUMN invitation_share_message TEXT DEFAULT NULL');
        }
    }

    upgradeDefaultMedia($pdo);
    migrateAssetouWeddingDetails($pdo);
}

function migrateAssetouWeddingDetails(PDO $pdo): void
{
    if (!tableExists($pdo, 'settings')) {
        return;
    }

    $row = $pdo->query('SELECT wedding_date, religious_venue FROM settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
    if (!$row || ($row['religious_venue'] ?? '') !== 'Église Saint-Pierre') {
        return;
    }

    $welcomeTitle = 'Bienvenue à notre mariage';
    $welcomeMessage = '« Et parmi Ses signes, Il a créé de vous, pour vous, des épouses afin que vous viviez en tranquillité avec elles et Il a mis entre vous affection et miséricorde. Il y a certes là des signes pour des gens qui réfléchissent. » — Sourate Ar-Rum (30:21)';
    $invitationText = "Si vous avez reçu ce lien, c'est que vous êtes notre heureux invité ou notre heureuse invitée VVIP.\n\nNous avons l'immense joie de vous inviter à célébrer notre union : la célébration du mariage à la Mosquée Salam, la réception à la Salle TENE BRAHIMA OUATTARA, et la danse de réjouissance au centre social Attécoubé. Votre présence sera notre plus belle joie.";
    $religiousVenue = "Mosquée Salam, en face du super marché Bon Prix d'Attécoubé\nJeudi 30 juillet 2026 à 09h00";
    $receptionVenue = "Salle TENE BRAHIMA OUATTARA, Hôtel des Armées (État Major) du Plateau, Abidjan\nJeudi 30 juillet 2026 à 11h00";
    $civilVenue = "Centre social Attécoubé\nDimanche 2 août 2026 à 14h00";
    $cardImage = 'assets/images/carte-invitation-assetou.png';
    $coverImage = 'assets/images/couple-couverture.png';

    $stmt = $pdo->prepare(
        'UPDATE settings SET
            wedding_date = ?,
            start_time = ?,
            end_time = ?,
            religious_venue = ?,
            reception_venue = ?,
            civil_venue = ?,
            welcome_title = ?,
            welcome_message = ?,
            invitation_text = ?,
            gps_lat = ?,
            gps_lng = ?,
            countdown_title = ?,
            hero_image = ?,
            invitation_card_image = ?
         WHERE id = 1'
    );
    $stmt->execute([
        '2026-07-30',
        '09:00',
        '23:00',
        $religiousVenue,
        $receptionVenue,
        $civilVenue,
        $welcomeTitle,
        $welcomeMessage,
        $invitationText,
        5.3561,
        -4.0127,
        'Le grand jour approche',
        $cardImage,
        $coverImage,
    ]);
}

function tableExists(PDO $pdo, string $table): bool
{
    if (isPostgres($pdo)) {
        $stmt = $pdo->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = ?)");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    if (isPostgres($pdo)) {
        $stmt = $pdo->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = ? AND column_name = ?)");
        $stmt->execute([$table, $column]);
        return (bool) $stmt->fetchColumn();
    }
    $cols = $pdo->query("PRAGMA table_info({$table})")->fetchAll();
    foreach ($cols as $col) {
        if (($col['name'] ?? '') === $column) {
            return true;
        }
    }
    return false;
}

function getSchemaStatements(bool $isPgsql): array
{
    if ($isPgsql) {
        return [
            "CREATE TABLE IF NOT EXISTS settings (
                id SERIAL PRIMARY KEY,
                bride_name TEXT NOT NULL DEFAULT 'Koné Assetou',
                groom_name TEXT NOT NULL DEFAULT 'Kone Anlyou',
                wedding_date TEXT NOT NULL DEFAULT '2026-09-15',
                start_time TEXT NOT NULL DEFAULT '14:00',
                end_time TEXT NOT NULL DEFAULT '23:00',
                civil_venue TEXT DEFAULT 'Mairie de Paris',
                religious_venue TEXT DEFAULT 'Église Saint-Pierre',
                reception_venue TEXT DEFAULT 'Château des Roses',
                gps_lat DOUBLE PRECISION DEFAULT 48.8566,
                gps_lng DOUBLE PRECISION DEFAULT 2.3522,
                welcome_title TEXT DEFAULT 'Bienvenue à notre mariage',
                welcome_message TEXT DEFAULT 'Nous sommes heureux de vous compter parmi les personnes qui ont marqué notre histoire.',
                invitation_text TEXT DEFAULT 'Si vous avez reçu ce lien, c''est que vous êtes notre heureux invité ou notre heureuse invitée VVIP.

Nous vous invitons à célébrer avec nous ce jour unique.',
                hero_image TEXT DEFAULT 'assets/images/invitation-card-bg.png',
                invitation_card_image TEXT DEFAULT 'assets/images/invitation-card-bg.png',
                contact_email TEXT DEFAULT '',
                contact_phone TEXT DEFAULT '',
                wedding_passed BOOLEAN DEFAULT FALSE,
                album_enabled BOOLEAN DEFAULT FALSE,
                countdown_title TEXT DEFAULT 'Le grand jour approche',
                countdown_message_past TEXT DEFAULT 'C''est aujourd''hui — le grand jour est arrivé !',
                countdown_enabled BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            )",
            "CREATE TABLE IF NOT EXISTS admins (
                id SERIAL PRIMARY KEY,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                email TEXT,
                created_at TIMESTAMPTZ DEFAULT NOW()
            )",
            "CREATE TABLE IF NOT EXISTS rsvp_confirmations (
                id SERIAL PRIMARY KEY,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                phone TEXT,
                email TEXT,
                relationship TEXT NOT NULL,
                companions INTEGER DEFAULT 0,
                message TEXT,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            )",
            "CREATE TABLE IF NOT EXISTS rsvp_declines (
                id SERIAL PRIMARY KEY,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                reason TEXT NOT NULL,
                message TEXT,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            )",
            "CREATE TABLE IF NOT EXISTS guestbook (
                id SERIAL PRIMARY KEY,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                message TEXT NOT NULL,
                status TEXT DEFAULT 'pending',
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            )",
            "CREATE TABLE IF NOT EXISTS gallery_albums (
                id SERIAL PRIMARY KEY,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                type TEXT DEFAULT 'story',
                sort_order INTEGER DEFAULT 0,
                created_at TIMESTAMPTZ DEFAULT NOW()
            )",
            "CREATE TABLE IF NOT EXISTS gallery_photos (
                id SERIAL PRIMARY KEY,
                album_id INTEGER NOT NULL REFERENCES gallery_albums(id) ON DELETE CASCADE,
                title TEXT,
                caption TEXT,
                file_path TEXT NOT NULL,
                sort_order INTEGER DEFAULT 0,
                created_at TIMESTAMPTZ DEFAULT NOW()
            )",
            "CREATE TABLE IF NOT EXISTS wedding_sections (
                id SERIAL PRIMARY KEY,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                sort_order INTEGER DEFAULT 0,
                created_at TIMESTAMPTZ DEFAULT NOW()
            )",
            "CREATE TABLE IF NOT EXISTS wedding_album (
                id SERIAL PRIMARY KEY,
                title TEXT NOT NULL,
                caption TEXT,
                file_path TEXT NOT NULL,
                media_type TEXT DEFAULT 'image',
                section_id INTEGER REFERENCES wedding_sections(id) ON DELETE SET NULL,
                sort_order INTEGER DEFAULT 0,
                allow_download BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMPTZ DEFAULT NOW()
            )",
        ];
    }

    return [
        "CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY,
            bride_name TEXT NOT NULL DEFAULT 'Koné Assetou',
            groom_name TEXT NOT NULL DEFAULT 'Kone Anlyou',
            wedding_date TEXT NOT NULL DEFAULT '2026-09-15',
            start_time TEXT NOT NULL DEFAULT '14:00',
            end_time TEXT NOT NULL DEFAULT '23:00',
            civil_venue TEXT DEFAULT 'Mairie de Paris',
            religious_venue TEXT DEFAULT 'Église Saint-Pierre',
            reception_venue TEXT DEFAULT 'Château des Roses',
            gps_lat REAL DEFAULT 48.8566,
            gps_lng REAL DEFAULT 2.3522,
            welcome_title TEXT DEFAULT 'Bienvenue à notre mariage',
            welcome_message TEXT DEFAULT 'Nous sommes heureux de vous compter parmi les personnes qui ont marqué notre histoire.',
            invitation_text TEXT DEFAULT 'Si vous avez reçu ce lien, c''est que vous êtes notre heureux invité ou notre heureuse invitée VVIP.

Nous vous invitons à célébrer avec nous ce jour unique.',
            hero_image TEXT DEFAULT 'assets/images/invitation-card-bg.png',
            invitation_card_image TEXT DEFAULT 'assets/images/invitation-card-bg.png',
            contact_email TEXT DEFAULT '',
            contact_phone TEXT DEFAULT '',
            wedding_passed INTEGER DEFAULT 0,
            album_enabled INTEGER DEFAULT 0,
            countdown_title TEXT DEFAULT 'Le grand jour approche',
            countdown_message_past TEXT DEFAULT 'C''est aujourd''hui — le grand jour est arrivé !',
            countdown_enabled INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            email TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS rsvp_confirmations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            phone TEXT,
            email TEXT,
            relationship TEXT NOT NULL,
            companions INTEGER DEFAULT 0,
            message TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS rsvp_declines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            reason TEXT NOT NULL,
            message TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS guestbook (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            message TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS gallery_albums (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT,
            type TEXT DEFAULT 'story',
            sort_order INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS gallery_photos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            album_id INTEGER NOT NULL,
            title TEXT,
            caption TEXT,
            file_path TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS wedding_sections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT,
            sort_order INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS wedding_album (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            caption TEXT,
            file_path TEXT NOT NULL,
            media_type TEXT DEFAULT 'image',
            section_id INTEGER,
            sort_order INTEGER DEFAULT 0,
            allow_download INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (section_id) REFERENCES wedding_sections(id) ON DELETE SET NULL
        )",
    ];
}

function seedDefaultData(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT INTO settings (id, bride_name, groom_name) VALUES (1, 'Koné Assetou', 'Kone Anlyou')");
    }

    $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($adminCount === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, email) VALUES (?, ?, ?)');
        $stmt->execute(['admin', $hash, 'admin@mariage.fr']);
    }

    $albumCount = (int) $pdo->query('SELECT COUNT(*) FROM gallery_albums')->fetchColumn();
    if ($albumCount === 0) {
        $albums = [
            ['Première rencontre', 'premiere-rencontre', 'Le jour où tout a commencé', 1],
            ['Fiançailles', 'fiancailles', 'Une demande inoubliable', 2],
            ['Voyages', 'voyages', 'Nos aventures ensemble', 3],
            ['Anniversaires', 'anniversaires', 'Célébrations mémorables', 4],
            ['Moments importants', 'moments', 'Les instants qui comptent', 5],
            ['Séances photo', 'seances-photo', 'Portraits du couple', 6],
        ];
        $stmt = $pdo->prepare('INSERT INTO gallery_albums (name, slug, description, sort_order) VALUES (?, ?, ?, ?)');
        foreach ($albums as $album) {
            $stmt->execute($album);
        }

        $photos = [];
        foreach (defaultStoryGalleryPhotos() as $albumId => $photo) {
            $photos[] = [$albumId, $photo['title'], $photo['path'], 1];
        }
        $photoStmt = $pdo->prepare('INSERT INTO gallery_photos (album_id, title, file_path, sort_order) VALUES (?, ?, ?, ?)');
        foreach ($photos as $photo) {
            $photoStmt->execute($photo);
        }
    }

    $weddingSectionCount = (int) $pdo->query('SELECT COUNT(*) FROM wedding_sections')->fetchColumn();
    if ($weddingSectionCount === 0) {
        $sections = [
            ['Cérémonie', 'ceremonie', 'Les moments de la cérémonie', 1],
            ['Réception', 'reception', 'La fête et les discours', 2],
            ['Invités', 'invites', 'Photos avec nos proches', 3],
            ['Vidéos', 'videos', 'Souvenirs en mouvement', 4],
        ];
        $stmt = $pdo->prepare('INSERT INTO wedding_sections (name, slug, description, sort_order) VALUES (?, ?, ?, ?)');
        foreach ($sections as $section) {
            $stmt->execute($section);
        }

        upgradeDefaultMedia($pdo);
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
    $pdo = getDatabase();
    initializeDatabase($pdo);
    echo "Database initialized successfully.\n";
}
