-- Schema compatible SQLite & PostgreSQL (via init.php)

CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY,
    bride_name TEXT NOT NULL DEFAULT 'Amira',
    groom_name TEXT NOT NULL DEFAULT 'Serge',
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
);

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    email TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rsvp_confirmations (
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
);

CREATE TABLE IF NOT EXISTS rsvp_declines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    reason TEXT NOT NULL,
    message TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS guestbook (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    message TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gallery_albums (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    type TEXT DEFAULT 'story',
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gallery_photos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    album_id INTEGER NOT NULL,
    title TEXT,
    caption TEXT,
    file_path TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS wedding_album (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    caption TEXT,
    file_path TEXT NOT NULL,
    media_type TEXT DEFAULT 'image',
    category TEXT DEFAULT 'ceremony',
    sort_order INTEGER DEFAULT 0,
    allow_download INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
