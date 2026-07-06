<?php

namespace App\Service;

class ImageResizer
{
    public function cropSquareAndResize(string $absolutePath, int $size = 800): void
    {
        if (!is_file($absolutePath)) {
            return;
        }

        if (!extension_loaded('gd')) {
            throw new \RuntimeException('Extension PHP GD manquante. Active gd dans ton php.ini.');
        }

        [$w, $h, $type] = getimagesize($absolutePath);

        // Si image invalide
        if (!$w || !$h) {
            return;
        }

        // Charger selon type
        $src = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($absolutePath),
            IMAGETYPE_PNG  => imagecreatefrompng($absolutePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($absolutePath) : null,
            default => null,
        };

        if (!$src) {
            return;
        }

        // Crop centré au carré
        $side = min($w, $h);
        $x = (int) floor(($w - $side) / 2);
        $y = (int) floor(($h - $side) / 2);

        $dst = imagecreatetruecolor($size, $size);

        // fond blanc (si png transparent)
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        imagecopyresampled(
            $dst,
            $src,
            0, 0,
            $x, $y,
            $size, $size,
            $side, $side
        );

        // On réécrit en JPEG (léger + compatible)
        imagejpeg($dst, $absolutePath, 88);

        imagedestroy($src);
        imagedestroy($dst);
    }
}
