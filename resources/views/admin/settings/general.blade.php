@extends('layouts.admin')
@section('title', 'Pengaturan Umum')
@section('content')
  @include('admin.settings._nav')

  @php use App\Models\Setting; @endphp

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Pengaturan Umum</h1>
    <p class="text-sm text-slate-500 mt-1">Identitas bisnis yang tampil di halaman publik dan email.</p>
  </div>

  <form method="POST" action="{{ route('admin.settings.general.update') }}" enctype="multipart/form-data" class="card p-6 max-w-2xl space-y-4">
    @csrf
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama Situs</label>
        <input type="text" name="site_name" value="{{ old('site_name', Setting::get('site_name', config('app.name'))) }}" class="form-input" required>
        @error('site_name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Tagline</label>
        <input type="text" name="site_tagline" value="{{ old('site_tagline', Setting::get('site_tagline')) }}" class="form-input" placeholder="Hosting cepat & terjangkau">
      </div>
    </div>

    <div>
      <label class="form-label">Nama Perusahaan (untuk invoice)</label>
      <input type="text" name="company_name" value="{{ old('company_name', Setting::get('company_name')) }}" class="form-input" placeholder="PT Contoh Hosting">
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Email Support</label>
        <input type="email" name="support_email" value="{{ old('support_email', Setting::get('support_email')) }}" class="form-input" placeholder="support@contoh.com">
        @error('support_email') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Telepon Support</label>
        <input type="text" name="support_phone" value="{{ old('support_phone', Setting::get('support_phone')) }}" class="form-input" placeholder="+62 811 2345 678">
      </div>
    </div>

    <div>
      <label class="form-label">Alamat Perusahaan</label>
      <textarea name="company_address" rows="2" class="form-input">{{ old('company_address', Setting::get('company_address')) }}</textarea>
    </div>

    <div>
      <label class="form-label">Teks Footer</label>
      <input type="text" name="footer_text" value="{{ old('footer_text', Setting::get('footer_text')) }}" class="form-input" placeholder="© {{ date('Y') }} Nama Perusahaan. Semua hak dilindungi.">

    {{-- Identitas visual --}}
    <div class="pt-4 border-t border-slate-100 space-y-4">
      <h2 class="text-sm font-semibold text-slate-800">Identitas Visual</h2>

      @php
        $logo = Setting::get('site_logo');
        $favicon = Setting::get('site_favicon');
      @endphp

      <div>
        <label class="form-label">Logo</label>
        @if ($logo)
          <div class="flex items-center gap-3 mb-2">
            <img src="{{ asset('storage/' . $logo) }}" alt="Logo" class="h-10 bg-slate-100 rounded-lg px-3 py-1.5 object-contain">
            <label class="flex items-center gap-2 text-xs text-rose-600">
              <input type="checkbox" name="remove_site_logo" value="1" class="rounded border-slate-300 text-rose-500 focus:ring-rose-400/40">
              Hapus logo
            </label>
          </div>
        @endif
        <input type="file" name="site_logo" accept="image/*" class="form-input text-xs">
        @error('site_logo') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">
          PNG/SVG dengan latar transparan, tinggi ideal 40–60px, maks 1 MB.
          Kalau kosong, nama situs ditampilkan sebagai teks.
        </p>
      </div>

      <div>
        <label class="form-label">Favicon</label>
        @if ($favicon)
          <div class="flex items-center gap-3 mb-2">
            <img src="{{ asset('storage/' . $favicon) }}" alt="Favicon" class="w-8 h-8 bg-slate-100 rounded p-1 object-contain">
            <label class="flex items-center gap-2 text-xs text-rose-600">
              <input type="checkbox" name="remove_site_favicon" value="1" class="rounded border-slate-300 text-rose-500 focus:ring-rose-400/40">
              Hapus favicon
            </label>
          </div>
        @endif
        <input type="file" name="site_favicon" accept="image/*" class="form-input text-xs">
        @error('site_favicon') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">Ikon di tab browser. PNG 32×32 atau 64×64, maks 256 KB.</p>
      </div>

      <div>
        <label class="form-label">Warna Tema</label>
        <div class="flex items-center gap-3">
          <input type="color" name="theme_color" value="{{ old('theme_color', Setting::get('theme_color', '#6366F1')) }}"
                 class="w-14 h-10 rounded-lg border border-slate-200 cursor-pointer">
          <span class="text-xs text-slate-500">Dipakai untuk tombol dan aksen di halaman publik.</span>
        </div>
        @error('theme_color') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Pengaturan</button>
  </form>
@endsection
