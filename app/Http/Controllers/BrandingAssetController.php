<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * Melayani logo, favicon, dan gambar banner LEWAT Laravel (bukan file
 * statis di folder public/) — supaya tidak bergantung sama sekali pada
 * folder mana yang benar-benar dilayani web server. Ini dibuktikan
 * berfungsi karena halaman 404 kustom kita sendiri bisa muncul, artinya
 * jalur ini (lewat index.php Laravel) pasti bisa diakses, apa pun
 * struktur foldernya di server.
 *
 * File sesungguhnya disimpan di storage/app/branding & storage/app/banners
 * (disk 'local', BUKAN 'public') — tidak butuh symlink storage/public
 * sama sekali, dan tidak butuh folder public/ yang benar-benar
 * disinkronkan ke luar.
 */
class BrandingAssetController extends Controller
{
    public function branding(string $filename)
    {
        return $this->serve('branding', $filename);
    }

    public function banner(string $filename)
    {
        return $this->serve('banners', $filename);
    }

    private function serve(string $folder, string $filename)
    {
        // Cegah path traversal (mis. "../../.env") — nama file cuma
        // boleh huruf/angka/titik/underscore/strip, tidak boleh ada
        // pemisah folder sama sekali.
        abort_unless(preg_match('/^[\w.\-]+$/', $filename) === 1, 403);

        $path = "{$folder}/{$filename}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $filename, [
            // Boleh disimpan cache browser lumayan lama — logo/banner
            // jarang berubah, dan kalaupun berubah, nama filenya juga
            // ikut berubah (pakai timestamp), jadi aman di-cache agresif.
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
