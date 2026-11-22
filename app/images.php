<?php
declare(strict_types=1);

/**
 * Speichert ein hochgeladenes Bild sicher:
 * - prüft Typ anhand des Inhalts (nicht des Dateinamens)
 * - rechnet es mit GD neu (entfernt Metadaten/Schadcode), max. 1600 px
 * - erzeugt ein 400-px-Vorschaubild
 * @return string Dateiname in public/uploads
 */
function store_uploaded_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException(match ($file['error'] ?? 0) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Das Foto ist zu groß.',
            UPLOAD_ERR_NO_FILE => 'Bitte ein Foto auswählen.',
            default => 'Das Hochladen hat nicht geklappt. Bitte erneut versuchen.',
        });
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Ungültiger Upload.');
    }
    $maxBytes = (int) config('upload_max_mb', 10) * 1024 * 1024;
    if ((int) $file['size'] > $maxBytes) {
        throw new RuntimeException('Das Foto ist größer als ' . (int) config('upload_max_mb', 10) . ' MB.');
    }
    if (!function_exists('imagecreatefromjpeg')) {
        throw new RuntimeException('Auf dem Server fehlt die PHP-Erweiterung GD.');
    }

    $info = @getimagesize($file['tmp_name']);
    $type = $info[2] ?? 0;
    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
        IMAGETYPE_PNG => @imagecreatefrompng($file['tmp_name']),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : false,
        default => false,
    };
    if (!$src) {
        throw new RuntimeException('Bitte ein JPG-, PNG- oder WebP-Foto hochladen.');
    }

    // Handy-Fotos: Ausrichtung aus EXIF übernehmen
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($src, 180, 0),
            6 => imagerotate($src, -90, 0),
            8 => imagerotate($src, 90, 0),
            default => null,
        };
        if ($rotated) {
            imagedestroy($src);
            $src = $rotated;
        }
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $name = bin2hex(random_bytes(12)) . '.jpg';
    save_resized_jpeg($src, UPLOAD_DIR . '/' . $name, 1600, false);
    save_resized_jpeg($src, UPLOAD_DIR . '/thumb_' . $name, 400, true);
    imagedestroy($src);
    return $name;
}

function save_resized_jpeg(GdImage $src, string $path, int $max, bool $square): void
{
    $w = imagesx($src);
    $h = imagesy($src);
    $sx = 0;
    $sy = 0;
    if ($square) {
        $side = min($w, $h);
        $sx = (int) (($w - $side) / 2);
        $sy = (int) (($h - $side) / 2);
        $w = $h = $side;
    }
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
    imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $nw, $nh, $w, $h);
    imageinterlace($dst, true);
    imagejpeg($dst, $path, 82);
    imagedestroy($dst);
}

function delete_photo_files(array $photo): void
{
    $file = (string) $photo['filename'];
    if (str_starts_with($file, 'assets/') || !preg_match('/^[a-f0-9]{24}\.jpg$/', $file)) {
        return; // mitgelieferte Bilder nicht löschen
    }
    @unlink(UPLOAD_DIR . '/' . $file);
    @unlink(UPLOAD_DIR . '/thumb_' . $file);
}
