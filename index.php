<?php
$uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$images = [];

if (is_dir($uploadsDir)) {
    $dirIterator = scandir($uploadsDir);
    foreach ($dirIterator as $file) {
        if ($file[0] === '.') {
            continue;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            continue;
        }

        $images[] = 'uploads/' . rawurlencode($file);
    }
}

if ($images) {
    natsort($images);
    $images = array_values($images);
}

$settingsFile = __DIR__ . DIRECTORY_SEPARATOR . 'settings.json';
$intervalSeconds = 5;

if (is_file($settingsFile)) {
    $settings = json_decode((string) file_get_contents($settingsFile), true);
    if (is_array($settings) && isset($settings['interval']) && is_numeric($settings['interval'])) {
        $intervalSeconds = max(1, (int) $settings['interval']);
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memer Gallery</title>
    <style>
        :root {
            color-scheme: light dark;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(circle at top, #1d1e26, #10111a 60%);
            color: #f8f8f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .frame {
            width: min(1200px, 100%);
            background: rgba(15, 16, 26, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.4rem);
        }

        .actions a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            color: #111;
            background: #ffd643;
            font-weight: 600;
            text-decoration: none;
            transition: transform 120ms ease, box-shadow 120ms ease;
        }

        .actions a:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        .viewer {
            position: relative;
            background: rgba(0, 0, 0, 0.25);
            border-radius: 16px;
            overflow: hidden;
            padding-top: 56%;
        }

        .viewer img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #050608;
        }

        .viewer .empty {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
            padding: 24px;
            text-align: center;
        }

        .info-bar {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.75);
        }

        .info-bar strong {
            color: #fff;
        }

        .controls {
            display: flex;
            gap: 12px;
        }

        .controls button {
            border: none;
            border-radius: 999px;
            padding: 10px 18px;
            font-weight: 600;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            transition: background 120ms ease;
        }

        .controls button:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.16);
        }

        .controls button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .thumbnails {
            margin-top: 32px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
        }

        .thumb {
            border: 2px solid transparent;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 120ms ease, border-color 120ms ease;
            background: rgba(255, 255, 255, 0.02);
        }

        .thumb img {
            width: 100%;
            height: 90px;
            object-fit: cover;
            display: block;
        }

        .thumb.active {
            border-color: #ffd643;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .frame {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="frame">
    <header>
        <h1>Memer Gallery</h1>
        <div class="actions">
            <a href="upload.php">Upload &amp; Settings</a>
        </div>
    </header>

    <div class="viewer">
        <?php if ($images): ?>
            <img id="viewerImage" src="<?php echo htmlspecialchars($images[0], ENT_QUOTES); ?>" alt="Uploaded meme">
        <?php else: ?>
            <div class="empty">
                Drop your first meme via the upload page to start the slideshow.
            </div>
        <?php endif; ?>
    </div>

    <div class="info-bar">
        <div>
            Showing <strong id="imageCounter"><?php echo $images ? '1 of ' . count($images) : '0 of 0'; ?></strong><br>
            Interval: <strong><?php echo $intervalSeconds; ?>s</strong>
        </div>
        <div class="controls">
            <button id="prevBtn" <?php echo $images ? '' : 'disabled'; ?>>Prev</button>
            <button id="nextBtn" <?php echo $images ? '' : 'disabled'; ?>>Next</button>
        </div>
    </div>

    <?php if ($images): ?>
        <div class="thumbnails" id="thumbnails">
            <?php foreach ($images as $index => $image): ?>
                <div class="thumb<?php echo $index === 0 ? ' active' : ''; ?>" data-index="<?php echo $index; ?>">
                    <img src="<?php echo htmlspecialchars($image, ENT_QUOTES); ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($images): ?>
<script>
    const images = <?php echo json_encode(array_values($images), JSON_UNESCAPED_SLASHES); ?>;
    const intervalMs = <?php echo (int) $intervalSeconds * 1000; ?>;
    const viewerImage = document.getElementById('viewerImage');
    const imageCounter = document.getElementById('imageCounter');
    const thumbnails = document.querySelectorAll('.thumb');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    let currentIndex = 0;
    let timerId;

    function render(index) {
        currentIndex = index;
        viewerImage.src = images[currentIndex];
        imageCounter.textContent = `${currentIndex + 1} of ${images.length}`;
        thumbnails.forEach((thumb, idx) => {
            thumb.classList.toggle('active', idx === currentIndex);
        });
    }

    function next() {
        const newIndex = (currentIndex + 1) % images.length;
        render(newIndex);
    }

    function prev() {
        const newIndex = (currentIndex - 1 + images.length) % images.length;
        render(newIndex);
    }

    function startTimer() {
        clearInterval(timerId);
        timerId = setInterval(next, intervalMs);
    }

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', () => {
            const targetIndex = Number(thumb.dataset.index);
            render(targetIndex);
            startTimer();
        });
    });

    prevBtn.addEventListener('click', () => {
        prev();
        startTimer();
    });

    nextBtn.addEventListener('click', () => {
        next();
        startTimer();
    });

    startTimer();
</script>
<?php endif; ?>
</body>
</html>
