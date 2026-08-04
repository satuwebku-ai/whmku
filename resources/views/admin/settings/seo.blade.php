@extends('layouts.admin')
@section('title', 'Pengaturan SEO')
@section('content')
  @include('admin.settings._nav')

  @php use App\Models\Setting; @endphp

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Pengaturan SEO</h1>
    <p class="text-sm text-slate-500 mt-1">Nilai default untuk halaman yang belum punya meta sendiri.</p>
  </div>

  @if (Setting::get('seo_noindex_site') === '1')
    <div class="max-w-2xl rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-xs text-rose-700 mb-4">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <b>Situs sedang disembunyikan dari mesin pencari.</b> Jangan lupa matikan opsi ini
      setelah situs siap diluncurkan, kalau tidak halamanmu tidak akan pernah muncul di Google.
    </div>
  @endif

  <form method="POST" action="{{ route('admin.settings.seo.update') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf

    <div>
      <label class="form-label">Meta Title Default</label>
      <input type="text" name="seo_title" maxlength="70" value="{{ old('seo_title', Setting::get('seo_title')) }}" class="form-input" placeholder="Hosting Murah & Domain Indonesia">
      @error('seo_title') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1">Maks. 70 karakter, idealnya di bawah 60.</p>
    </div>

    <div>
      <label class="form-label">Meta Description Default</label>
      <textarea name="seo_description" rows="3" maxlength="170" class="form-input" placeholder="Layanan hosting cepat dengan dukungan 24/7...">{{ old('seo_description', Setting::get('seo_description')) }}</textarea>
      @error('seo_description') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1">Idealnya 120–155 karakter.</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Keywords Default</label>
        <input type="text" name="seo_keywords" value="{{ old('seo_keywords', Setting::get('seo_keywords')) }}" class="form-input" placeholder="hosting indonesia, domain .id">
      </div>
      <div>
        <label class="form-label">OG Image Default (URL)</label>
        <input type="text" name="seo_og_image" value="{{ old('seo_og_image', Setting::get('seo_og_image')) }}" class="form-input" placeholder="https://.../og-image.jpg">
        <p class="text-[11px] text-slate-400 mt-1">Ukuran ideal 1200×630 px.</p>
      </div>
    </div>

    <div>
      <label class="form-label">Canonical URL Situs (opsional)</label>
      <input type="url" name="seo_canonical" value="{{ old('seo_canonical', Setting::get('seo_canonical')) }}" class="form-input" placeholder="https://contohhosting.com">
      @error('seo_canonical') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="form-label">Isi robots.txt (opsional)</label>
      <textarea name="seo_robots" rows="5" class="form-input font-mono text-xs" placeholder="User-agent: *&#10;Disallow: /admin&#10;Sitemap: https://contohhosting.com/sitemap.xml">{{ old('seo_robots', Setting::get('seo_robots')) }}</textarea>
      <p class="text-[11px] text-slate-400 mt-1">
        Catatan: field ini menyimpan teksnya saja. Untuk benar-benar dipakai, isi file
        <code>public/robots.txt</code> di server dengan konten ini, atau buat route khusus.
      </p>
    </div>

    <div class="pt-3 border-t border-slate-100">
      <label class="flex items-start gap-2 text-sm text-slate-600">
        <input type="checkbox" name="seo_noindex_site" value="1" @checked(old('seo_noindex_site', Setting::get('seo_noindex_site') === '1')) class="rounded border-slate-300 text-accent focus:ring-accent/40 mt-0.5">
        <span>
          Sembunyikan seluruh situs dari mesin pencari
          <span class="block text-[11px] text-slate-400">Pakai saat situs masih dikembangkan. Wajib dimatikan sebelum peluncuran.</span>
        </span>
      </label>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Pengaturan</button>
  </form>
@endsection
