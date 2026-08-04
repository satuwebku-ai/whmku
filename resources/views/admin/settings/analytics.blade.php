@extends('layouts.admin')
@section('title', 'Analytics')
@section('content')
  @include('admin.settings._nav')

  @php use App\Models\Setting; @endphp

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Analytics</h1>
    <p class="text-sm text-slate-500 mt-1">Pelacakan pengunjung di halaman publik.</p>
  </div>

  <div class="max-w-2xl rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 text-xs text-indigo-700 mb-4">
    <i class="fa-solid fa-circle-info"></i>
    Isi <b>ID-nya saja</b>, bukan seluruh kode script. Script-nya dibangun otomatis oleh sistem —
    ini disengaja, karena menempelkan HTML mentah dari database ke halaman publik membuka celah XSS.
  </div>

  <form method="POST" action="{{ route('admin.settings.analytics.update') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf

    <div>
      <label class="form-label">Google Analytics 4 — Measurement ID</label>
      <input type="text" name="ga_measurement_id" value="{{ old('ga_measurement_id', Setting::get('ga_measurement_id')) }}" class="form-input" placeholder="G-XXXXXXXXXX">
      @error('ga_measurement_id') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1">Dari Google Analytics » Admin » Data Streams.</p>
    </div>

    <div>
      <label class="form-label">Google Tag Manager — Container ID</label>
      <input type="text" name="gtm_container_id" value="{{ old('gtm_container_id', Setting::get('gtm_container_id')) }}" class="form-input" placeholder="GTM-XXXXXXX">
      @error('gtm_container_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="form-label">Facebook Pixel ID</label>
      <input type="text" name="fb_pixel_id" value="{{ old('fb_pixel_id', Setting::get('fb_pixel_id')) }}" class="form-input" placeholder="1234567890123456">
      @error('fb_pixel_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Pengaturan</button>
  </form>
@endsection
