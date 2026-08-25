<?php

namespace App\Services\Image;

/**
 * Crop & resize gambar upload supaya PAS dengan rasio tampilan di
 * publik -- tanpa ini, admin bisa upload gambar rasio sembarang, lalu
 * CSS object-fit:cover di publik memotongnya secara tidak terduga
 * (bisa memotong bagian penting gambar/teks banner).
 *
 * Dipakai PHP GD bawaan (bukan package composer tambahan) supaya
 * tidak perlu instalasi ekstra di shared hosting cPanel yang sering
 * tidak bisa `composer install` ulang sembarangan.
 */
class ImageFitter
{
    /**
     * Crop tengah (center-crop) gambar sumber supaya persis $targetW x
     * $targetH -- sama seperti CSS object-fit:cover, tapi dilakukan
     * SEKALI saat upload, bukan tiap kali dirender di browser.
     */
    public static function cropToFit(string $sourcePath, string $destPath, int $targetW, int $targetH): bool
    {
        if (! extension_loaded('gd')) {
            // GD tidak ada di server ini -- simpan apa adanya daripada
            // gagal total. object-fit:cover di CSS tetap jadi jaring
            // pengaman kedua di publik.
            return copy($sourcePath, $destPath);
        }

        $info = @getimagesize($sourcePath);

        if (! $info) {
            return copy($sourcePath, $destPath);
        }

        [$srcW, $srcH] = $info;
        $mime = $info['mime'];

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default      => null,
        };

        if (! $src) {
            return copy($sourcePath, $destPath);
        }

        // Hitung area crop di gambar SUMBER supaya rasionya sama persis
        // dengan target, baru diskalakan ke ukuran target.
        $srcRatio = $srcW / $srcH;
        $targetRatio = $targetW / $targetH;

        if ($srcRatio > $targetRatio) {
            // Sumber lebih "lebar" dari target -- potong kiri-kanan.
            $cropH = $srcH;
            $cropW = (int) round($srcH * $targetRatio);
            $cropX = (int) round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            // Sumber lebih "tinggi" dari target -- potong atas-bawah.
            $cropW = $srcW;
            $cropH = (int) round($srcW / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($srcH - $cropH) / 2);
        }

        $dest = imagecreatetruecolor($targetW, $targetH);

        // Latar transparan dijaga untuk PNG -- supaya logo/gambar dengan
        // transparansi tidak berubah jadi kotak hitam.
        if ($mime === 'image/png') {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
            $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
            imagefilledrectangle($dest, 0, 0, $targetW, $targetH, $transparent);
        }

        imagecopyresampled($dest, $src, 0, 0, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);

        $result = match ($mime) {
            'image/jpeg' => imagejpeg($dest, $destPath, 88),
            'image/png'  => imagepng($dest, $destPath, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($dest, $destPath, 88) : imagejpeg($dest, $destPath, 88),
            default      => false,
        };

        imagedestroy($src);
        imagedestroy($dest);

        return $result;
    }
}
