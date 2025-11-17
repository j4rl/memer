<?php
$uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$settingsFile = __DIR__ . DIRECTORY_SEPARATOR . 'settings.json';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$currentInterval = 5;
$messages = [];
$errors = [];

if (!is_dir($uploadsDir)) {
    $errors[] = 'Uploads directory is missing.';
}

if (is_file($settingsFile)) {
    $savedSettings = json_decode((string) file_get_contents($settingsFile), true);
    if (is_array($savedSettings) && isset($savedSettings['interval']) && is_numeric($savedSettings['interval'])) {
        $currentInterval = max(1, (int) $savedSettings['interval']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newInterval = filter_input(INPUT_POST, 'interval', FILTER_VALIDATE_INT);
    if ($newInterval === false || $newInterval === null) {
        $errors[] = 'Interval must be a number.';
    } else {
        $newInterval = max(1, min(3600, $newInterval));
        $currentInterval = $newInterval;
        $settingsPayload = json_encode(['interval' => $currentInterval], JSON_PRETTY_PRINT);
        if ($settingsPayload !== false && file_put_contents($settingsFile, $settingsPayload) !== false) {
            $messages[] = "Interval updated to {$currentInterval} seconds.";
        } else {
            $errors[] = 'Unable to save interval settings.';
        }
    }

    if (!empty($_FILES['image']['name'])) {
        if (!is_dir($uploadsDir)) {
            $errors[] = 'Uploads directory is missing on the server.';
        } elseif (!is_uploaded_file($_FILES['image']['tmp_name'])) {
            $errors[] = 'Upload failed. Please try again.';
        } else {
            $originalName = basename($_FILES['image']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'Only JPG, PNG, GIF, or WEBP files are allowed.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = $finfo ? finfo_file($finfo, $_FILES['image']['tmp_name']) : null;
                if ($finfo) {
                    finfo_close($finfo);
                }

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if ($mimeType !== null && !in_array($mimeType, $allowedMimeTypes, true)) {
                    $errors[] = 'Unsupported file type.';
                } else {
                    $cleanName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                    $targetName = $cleanName ?: ('image_' . time() . '.' . $extension);
                    $targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $targetName;

                    if (file_exists($targetPath)) {
                        $targetName = pathinfo($targetName, PATHINFO_FILENAME) . '_' . uniqid() . '.' . $extension;
                        $targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $targetName;
                    }

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $messages[] = 'Image uploaded successfully.';
                    } else {
                        $errors[] = 'Failed to move the uploaded file.';
                    }
                }
            }
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Memes</title>
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
            background: linear-gradient(135deg, #0b0c14, #1b1d2b);
            color: #f5f5f7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .panel {
            width: min(600px, 100%);
            background: rgba(12, 13, 20, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
        }

        h1 {
            margin-top: 0;
            font-size: clamp(1.8rem, 4vw, 2.4rem);
            margin-bottom: 12px;
        }

        p {
            margin-top: 0;
            color: rgba(255, 255, 255, 0.7);
        }

        form {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        label {
            font-weight: 600;
        }

        input[type=\"file\"],
        input[type=\"number\"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: inherit;
        }

        input[type=\"number\"]::-webkit-inner-spin-button {
            opacity: 1;
        }

        button {
            padding: 12px 16px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            background: #ffd643;
            color: #131313;
            cursor: pointer;
            transition: transform 120ms ease, box-shadow 120ms ease;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(255, 214, 67, 0.35);
        }

        .messages {
            margin-top: 16px;
            display: grid;
            gap: 8px;
        }

        .messages .alert {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .alert.success {
            background: rgba(72, 187, 120, 0.15);
            border: 1px solid rgba(72, 187, 120, 0.4);
            color: #a8f5cd;
        }

        .alert.error {
            background: rgba(245, 101, 101, 0.18);
            border: 1px solid rgba(245, 101, 101, 0.35);
            color: #ffd4d4;
        }

        .links {
            margin-top: 24px;
            display: flex;
            justify-content: flex-end;
        }

        .links a {
            color: #ffd643;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="panel">
    <h1>Upload a Meme</h1>
    <p>Drop images into the slideshow and control how fast they rotate.</p>

    <?php if ($messages || $errors): ?>
        <div class="messages">
            <?php foreach ($messages as $message): ?>
                <div class="alert success"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
            <?php endforeach; ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div>
            <label for="image">Image File</label>
            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
        </div>
        <div>
            <label for="interval">Slide Interval (seconds)</label>
            <input type="number" id="interval" name="interval" min="1" max="3600" value="<?php echo (int) $currentInterval; ?>" required>
        </div>
        <button type="submit">Save &amp; Upload</button>
    </form>

    <div class="links">
        <a href="index.php">Back to Gallery</a>
    </div>
</div>
</body>
</html>
