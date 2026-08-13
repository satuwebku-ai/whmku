<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainDocument extends Model
{
    protected $fillable = ['domain_id', 'file_path', 'original_name', 'status', 'admin_note'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * TLD Indonesia yang mewajibkan dokumen — dan daftar dokumen yang
     * diperlukan tiap TLD, ditulis langsung dari sumber resmi (bukan
     * ditebak). ".id", ".biz.id", ".web.id" SENGAJA tidak masuk sini —
     * TLD itu tidak butuh dokumen tambahan sama sekali.
     *
     * @return array<string, array{label: string, items: string[]}>
     */
    public static function requirements(): array
    {
        return [
            'ac.id' => [
                'label' => 'Institusi pendidikan tinggi',
                'items' => [
                    'Kartu identitas penanggung jawab (KTP/Paspor)',
                    'SK Pendirian Lembaga dari instansi berwenang',
                    'Surat keterangan rektor atau pimpinan lembaga',
                ],
            ],
            'co.id' => [
                'label' => 'Badan usaha / komersial',
                'items' => [
                    'Kartu identitas penanggung jawab (KTP/Paspor/SIM/KITAS/KITAP)',
                    'Nomor Induk Berusaha (NIB)',
                    'Sertifikat merek (kalau ada)',
                ],
            ],
            'net.id' => [
                'label' => 'Penyedia jasa telekomunikasi',
                'items' => [
                    'Kartu identitas penanggung jawab (KTP/Paspor)',
                    'Surat izin penyelenggaraan usaha telekomunikasi',
                    'Surat keterangan laik operasi',
                    'Kepemilikan merek (kalau ada)',
                ],
            ],
            'sch.id' => [
                'label' => 'Sekolah / lembaga pendidikan',
                'items' => [
                    'Surat keterangan kepala sekolah/pimpinan lembaga (sekolah resmi) ATAU SK pendirian lembaga dari instansi terkait (pendidikan non-formal)',
                    'Kartu identitas penanggung jawab',
                ],
            ],
            'or.id' => [
                'label' => 'Organisasi',
                'items' => [
                    'Kartu identitas penanggung jawab',
                    'Akta notaris atau SK organisasi',
                ],
            ],
            'ponpes.id' => [
                'label' => 'Pondok pesantren',
                'items' => [
                    'Surat keterangan pimpinan pondok pesantren',
                    'Kartu identitas penanggung jawab',
                ],
            ],
        ];
    }

    public static function requiresDocuments(string $extension): bool
    {
        return array_key_exists(ltrim($extension, '.'), static::requirements());
    }
}
