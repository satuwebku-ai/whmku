<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainDocument extends Model
{
    protected $fillable = ['domain_id', 'document_requirement_id', 'file_path', 'original_name', 'status', 'admin_note'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class, 'document_requirement_id');
    }

    /**
     * Ringkasan kelengkapan berkas satu domain.
     *
     * Ini SATU-SATUNYA sumber kebenaran untuk pertanyaan "boleh lanjut
     * bayar / boleh diproses belum?" -- dipakai halaman klien, halaman
     * verifikasi admin, DAN gerbang pembayaran invoice, supaya ketiganya
     * tidak mungkin berbeda pendapat.
     *
     * Berkas berstatus 'replaced' (versi lama yang sudah diunggah ulang)
     * sengaja diabaikan -- yang dihitung cuma berkas terbaru per
     * persyaratan.
     *
     * @return array{required: int, approved: int, pending: int, rejected: int, missing: int, complete: bool, items: \Illuminate\Support\Collection}
     */
    public static function progressFor(Domain $domain): array
    {
        $requirements = DocumentRequirement::forExtension($domain->tld?->extension);

        $docs = $domain->relationLoaded('documents')
            ? $domain->documents
            : $domain->documents()->get();

        $aktif = $docs->where('status', '!=', 'replaced')->groupBy('document_requirement_id');

        $items = $requirements->map(function ($req) use ($aktif) {
            // Kalau ada beberapa berkas untuk satu persyaratan, yang
            // dipakai adalah yang TERBARU -- itu yang mewakili keadaan
            // sekarang.
            $doc = ($aktif[$req->id] ?? collect())->sortByDesc('id')->first();

            return [
                'requirement' => $req,
                'document' => $doc,
                'status' => $doc->status ?? 'missing',
            ];
        });

        $wajib = $items->filter(fn ($i) => $i['requirement']->is_required);

        return [
            'required' => $wajib->count(),
            'approved' => $wajib->where('status', 'approved')->count(),
            'pending'  => $wajib->where('status', 'pending')->count(),
            'rejected' => $wajib->where('status', 'rejected')->count(),
            'missing'  => $wajib->where('status', 'missing')->count(),
            // Lengkap = SEMUA berkas wajib sudah disetujui. Domain tanpa
            // persyaratan otomatis lengkap (tidak ada yang ditunggu).
            'complete' => $wajib->isEmpty() || $wajib->every(fn ($i) => $i['status'] === 'approved'),
            'items'    => $items,
        ];
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
