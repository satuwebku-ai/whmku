@extends('layouts.admin')
@section('title', 'Banner Popup')
@section('content')

  @include('admin.pages._nav')

  @php use App\Models\Setting; @endphp

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Banner Popup</h1>
    <p class="text-sm text-slate-500 mt-1">
      Muncul sebagai jendela di atas halaman depan (Beranda) begitu pengunjung membuka situs — beda dari Banner Promo yang tampil sebagai carousel di dalam halaman.
    </p>
  </div>

  <form method="POST" action="{{ route('admin.popup-banner.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-5">
    @csrf

    <div class="card p-6">
      <label class="flex items-center gap-2 cursor-pointer w-fit mb-1">
        <input type="checkbox" name="popup_banner_enabled" value="1" @checked(Setting::get('popup_banner_enabled', '0') === '1')
               class="rounded border-slate-300 text-accent focus:ring-accent/40">
        <span class="text-sm font-medium text-slate-700">Aktifkan banner popup</span>
      </label>
      <p class="text-xs text-slate-400">Kalau dimatikan, tidak ada popup yang muncul di Beranda sama sekali.</p>
    </div>

    <div class="card p-6 space-y-4">
      <div>
        <label class="form-label">Gambar</label>
        @php $currentImage = Setting::get('popup_banner_image'); @endphp
        @if ($currentImage)
          <div class="mb-2 flex items-center gap-3">
            <img src="{{ route('branding.file', $currentImage) }}" alt="Banner popup" class="h-20 rounded-lg border border-slate-200 object-cover">
            <label class="flex items-center gap-1.5 text-xs text-rose-600 cursor-pointer">
              <input type="checkbox" name="remove_popup_banner_image" value="1" class="rounded border-slate-300">
              Hapus gambar ini
            </label>
          </div>
        @endif
        <input type="file" name="popup_banner_image" accept="image/png,image/jpeg,image/webp" class="form-input">
        @error('popup_banner_image') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">PNG/JPG/WEBP, maksimal 2 MB. Disarankan rasio persegi atau landscape (mis. 600×400px).</p>
      </div>

      <div>
        <label class="form-label">Judul</label>
        <input type="text" name="popup_banner_title" value="{{ old('popup_banner_title', Setting::get('popup_banner_title')) }}" class="form-input" placeholder="Promo Spesial Bulan Ini!">
        @error('popup_banner_title') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="form-label">Deskripsi</label>
        <textarea name="popup_banner_description" rows="3" class="form-input" placeholder="Diskon 20% untuk semua paket hosting, berlaku sampai akhir bulan.">{{ old('popup_banner_description', Setting::get('popup_banner_description')) }}</textarea>
        @error('popup_banner_description') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="form-label">Teks Tombol</label>
          <input type="text" name="popup_banner_button_text" value="{{ old('popup_banner_button_text', Setting::get('popup_banner_button_text', 'Lihat Sekarang')) }}" class="form-input">
        </div>
        <div>
          <label class="form-label">Tautan Tombol</label>
          <input type="text" name="popup_banner_link_url" value="{{ old('popup_banner_link_url', Setting::get('popup_banner_link_url')) }}" class="form-input" placeholder="/hosting atau https://...">
          @error('popup_banner_link_url') <p class="form-error">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    <div class="card p-6">
      <label class="form-label">Seberapa Sering Muncul</label>
      @php $freq = old('popup_banner_frequency', Setting::get('popup_banner_frequency', 'once_per_day')); @endphp
      <select name="popup_banner_frequency" class="form-input max-w-xs">
        <option value="every_visit" @selected($freq === 'every_visit')>Setiap kali buka halaman</option>
        <option value="once_per_session" @selected($freq === 'once_per_session')>Sekali per kunjungan (sampai browser ditutup)</option>
        <option value="once_per_day" @selected($freq === 'once_per_day')>Sekali per hari per pengunjung</option>
      </select>
      <p class="text-[11px] text-slate-400 mt-1">
        "Setiap kali buka halaman" cukup mengganggu untuk pengunjung yang sering balik — cuma disarankan untuk pengumuman yang benar-benar penting/mendesak.
      </p>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Pengaturan</button>
  </form>

@endsection
