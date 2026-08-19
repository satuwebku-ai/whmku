@extends('layouts.admin')

@section('title', $banner->exists ? 'Edit Banner Promo' : 'Tambah Banner Promo')

@section('content')

  <a href="{{ route('admin.promo-banners.index') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Banner Promo</a>
  <h1 class="text-xl font-bold text-slate-800 mt-1 mb-6">{{ $banner->exists ? 'Edit Banner Promo' : 'Tambah Banner Promo' }}</h1>

  <form method="POST" action="{{ $banner->exists ? route('admin.promo-banners.update', $banner) : route('admin.promo-banners.store') }}"
        enctype="multipart/form-data" class="max-w-2xl space-y-5">
    @csrf
    @if ($banner->exists) @method('PUT') @endif

    <div class="card p-5 space-y-4">
      <div>
        <label class="form-label">Judul</label>
        <input type="text" name="title" value="{{ old('title', $banner->title) }}" class="form-input" required placeholder="Promo Hosting Diskon 30%">
        @error('title') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Subjudul (opsional)</label>
        <input type="text" name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}" class="form-input" placeholder="Berlaku sampai akhir bulan ini">
        @error('subtitle') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="card p-5">
      <label class="form-label">Gambar Banner</label>
      @if ($banner->image)
        <img src="{{ route('banner.file', $banner->image) }}" alt="{{ $banner->title }}" class="w-full max-w-md rounded-lg border border-slate-100 mb-3">
      @endif
      <input type="file" name="image" accept="image/*" class="form-input">
      @error('image') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1">Disarankan rasio lebar 16:9 atau lebih lebar (mis. 1600×600px), maksimal 2 MB.</p>
    </div>

    <div class="card p-5 space-y-4">
      <div>
        <label class="form-label">Tautan Tujuan (opsional)</label>
        <input type="text" name="link_url" value="{{ old('link_url', $banner->link_url) }}" class="form-input" placeholder="/hosting atau https://...">
        @error('link_url') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Teks Tombol (opsional)</label>
        <input type="text" name="button_text" value="{{ old('button_text', $banner->button_text) }}" class="form-input" placeholder="Lihat Paket">
        @error('button_text') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="open_in_new_tab" value="1" @checked(old('open_in_new_tab', $banner->open_in_new_tab)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
        Buka di tab baru
      </label>
    </div>

    <div class="card p-5 space-y-4">
      <label class="form-label mb-0">Jadwal Tayang (opsional — kosongkan supaya tayang terus)</label>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-xs text-slate-500 mb-1 block">Mulai</label>
          <input type="date" name="starts_at" value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d')) }}" class="form-input">
          @error('starts_at') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="text-xs text-slate-500 mb-1 block">Sampai</label>
          <input type="date" name="ends_at" value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d')) }}" class="form-input">
          @error('ends_at') <p class="form-error">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    <div class="card p-5">
      <label class="form-label">Tampil di Halaman</label>
      <select name="display_page" class="form-input max-w-xs">
        @foreach (\App\Models\PromoBanner::PAGES as $key => $label)
          <option value="{{ $key }}" @selected(old('display_page', $banner->display_page ?? 'all') === $key)>{{ $label }}</option>
        @endforeach
      </select>
      <p class="text-[11px] text-slate-400 mt-1">Banner cuma tampil di halaman yang dipilih (atau semua halaman kalau "Semua Halaman" dipilih).</p>
    </div>

    <div class="card p-5">
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
        Aktif (tampil di situs publik)
      </label>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="{{ route('admin.promo-banners.index') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
