# Memer [mi:mer]

Single-folder PHP app for unattended meme displays: drop files into `uploads/`, configure timing/retention, and a kiosk-friendly slideshow keeps itself refreshed.

## Components
- `index.php` — fullscreen viewer that discovers JPG/JPEG/PNG/GIF/WEBP files from `uploads/`, sorts them naturally, and cycles through them at the configured interval. When the playlist wraps it forces a page reload so newly arrived images appear automatically. **Images that have existed longer than the retention window are deleted on the fly.**
- `upload.php` — upload console with a drag-and-drop zone plus settings form. It enforces extension + MIME checks, sanitizes filenames, avoids collisions, and persists both `interval` (seconds between slides) and `expire_days` (auto-delete window) to `settings.json`.
- `files.php` — management grid that shows every stored image with preview, file size, first-seen timestamp, remaining days before deletion, and buttons for single or bulk removal. It also keeps `image_log.json` in sync if files disappear.

## Settings & data files
- `settings.json` — JSON document written by `upload.php` that stores `interval` (1–3600 seconds) and `expire_days` (1–3650 days). The viewer and management panel honor these values.
- `image_log.json` — tracks when each filename was first seen so expiration is based on age, not modification time. Both the viewer and the file manager trim this log and delete expired entries/files.

## Getting Started
1. Create an `uploads/` directory that is readable and writable by PHP.
2. Serve the folder via any PHP-capable web server (Apache, nginx+php-fpm, the built-in `php -S`, etc.).
3. Visit `upload.php` to add memes and adjust the interval/retention settings.
4. Use `index.php` on the kiosk display; it keeps looping, purges expired files, and reloads itself so any new upload shows up on the next cycle.
5. Optional: open `files.php` to review every asset, see when it expires, and delete unneeded items individually or in bulk.
