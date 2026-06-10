# Invitationdebaby

Application web d'invitations de mariage avec RSVP, livre d'or et galeries photos.

## Déploiement Vercel + Neon PostgreSQL

### 1. Créer la base Neon

1. Allez sur [neon.tech](https://neon.tech) et créez un projet
2. Copiez la **Connection string** (format `postgresql://...`)

### 2. Pousser sur GitHub

```bash
git init
git add .
git commit -m "Initial commit — faire-part mariage"
git remote add origin https://github.com/VOTRE-USER/faire-part.git
git push -u origin main
```

### 3. Déployer sur Vercel

1. Importez le repo sur [vercel.com/new](https://vercel.com/new)
2. Nommez le projet **`invitationdebaby`** (URL : `https://invitationdebaby.vercel.app`)
3. Ajoutez les **variables d'environnement** :

| Variable | Valeur |
|----------|--------|
| `DATABASE_URL` | Votre URL Neon (`postgresql://...`) |
| `DB_DRIVER` | `pgsql` |
| `APP_URL` | `https://invitationdebaby.vercel.app` |
| `APP_DEBUG` | `false` |

3. Déployez — Vercel utilise `vercel.json` avec le runtime `vercel-php@0.6.2`

### 4. Initialiser la base de données

Après le premier déploiement, visitez votre site — la base s'initialise automatiquement au premier chargement.

Ou exécutez localement avec la variable Neon :

```bash
set DATABASE_URL=postgresql://...
set DB_DRIVER=pgsql
php database/init.php
```

### 5. Accès admin

- URL : `https://invitationdebaby.vercel.app/admin/login.php`
- Identifiants : `admin` / `admin123` (à changer !)

---

## Changer l’URL Vercel (ex. `mariage-amira-serge` → `invitationdebaby`)

L’URL par défaut est `{nom-du-projet}.vercel.app`. Pour obtenir **invitationdebaby.vercel.app** :

1. Ouvrez [vercel.com/dashboard](https://vercel.com/dashboard)
2. Ouvrez le projet du faire-part → **Settings → General → Project Name** → saisissez `invitationdebaby` → Save
3. **Settings → Environment Variables** → mettez `APP_URL` à `https://invitationdebaby.vercel.app`
4. Redéployez (Deployments → … → Redeploy)

---

Le stockage local **n'est pas persistant** sur Vercel. Utilisez des **URLs d'images** dans l'admin :

- [Cloudinary](https://cloudinary.com) (gratuit)
- [ImgBB](https://imgbb.com)
- Google Drive (lien public)

Dans **Admin → Galerie**, collez l'URL de l'image au lieu d'uploader un fichier.

---

## Gestion des sections photo (Admin)

**Admin → Galerie** comporte 4 onglets :

| Onglet | Action |
|--------|--------|
| **Sections — Notre Histoire** | Ajouter / modifier / supprimer des sections (nom + description facultative) |
| **Photos — Notre Histoire** | Ajouter des photos dans une section |
| **Sections — Album Mariage** | Gérer les sections de l'album post-mariage |
| **Médias — Album Mariage** | Ajouter photos/vidéos par section |

---

## Développement local

```bash
cp .env.example .env
# Laisser DB_DRIVER=sqlite ou commenter DATABASE_URL

php database/init.php
php scripts/generate-images.php
php -S localhost:8080 -t public public/router.php
```

Site : http://localhost:8080  
Admin : http://localhost:8080/admin/login.php

---

## Stack

- PHP 8+ / vercel-php runtime
- PostgreSQL (Neon) en production
- SQLite en local
- Bootstrap 5, AOS, GLightbox
