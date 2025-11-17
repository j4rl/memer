# memer

Simple PHP slideshow that rotates through images stored inside the local `uploads/` catalog.

## Features
- Gallery (`index.php`) that lists every JPG, PNG, GIF, or WEBP file from `uploads/`, shows thumbnails, and plays them back automatically with manual prev/next controls.
- Upload & settings page (`upload.php`) that lets you drop new files into the catalog and choose how many seconds the slideshow should wait between slides.
- Interval preference saved in `settings.json` so it persists between visits.

## Getting Started
1. Ensure the `uploads/` directory is writable by the web server (create it if it does not exist).
2. Serve the project from a PHP-capable server (e.g., Apache in XAMPP) with the document root pointing to this folder.
3. Visit `upload.php` in your browser to upload one or more images and set the slide interval.
4. Open `index.php` to watch the rotating gallery. Thumbnails and buttons let you jump between files instantly.
