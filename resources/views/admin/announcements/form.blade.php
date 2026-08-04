@extends('layouts.admin')

@section('title', $announcement->exists ? 'Edit Pengumuman' : 'Buat Pengumuman')

@section('content')

  @include('admin.pages._nav')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $announcement->exists ? 'Edit Pengumuman' : 'Buat Pengumuman' }}</h1>
    @if ($announcement->exists)
      <p class="text-sm text-slate-500 mt-1">
        URL: <a href="{{ route('announcements.show', $announcement->slug) }}" target="_blank" class="text-accent hover:underline">{{ route('announcements.show', $announcement->slug) }}</a>
      </p>
    @endif
  </div>

  <form method="POST" action="{{ $announcement->exists ? route('admin.announcement.update', $announcement) : route('admin.announcement.add') }}" class="grid lg:grid-cols-3 gap-5 max-w-5xl">
    @csrf

    <div class="lg:col-span-2 space-y-5">
      <div class="card p-6 space-y-4">
        <div>
          <label class="form-label">Judul</label>
          <input type="text" name="title" value="{{ old('title', $announcement->title) }}" class="form-input" required>
          @error('title') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="form-label">Slug URL</label>
          <input type="text" name="slug" value="{{ old('slug', $announcement->slug) }}" placeholder="otomatis dari judul" class="form-input">
          @error('slug') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="form-label">Ringkasan (opsional)</label>
          <textarea name="excerpt" rows="2" maxlength="500" class="form-input" placeholder="Ditampilkan di daftar pengumuman">{{ old('excerpt', $announcement->excerpt) }}</textarea>
        </div>

        <div>
          <label class="form-label">Isi Pengumuman</label>
          <textarea name="content" rows="12" class="form-input font-mono text-xs" required>{{ old('content', $announcement->content) }}</textarea>
          @error('content') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">Mendukung HTML dasar.</p>
        </div>
      </div>

      <div class="card p-6 space-y-4">
        <h2 class="text-sm font-semibold text-slate-800">SEO (opsional)</h2>
        <div>
          <label class="form-label">Meta Title</label>
          <input type="text" name="meta_title" maxlength="70" value="{{ old('meta_title', $announcement->meta_title) }}" class="form-input" placeholder="Kosongkan untuk memakai judul">
        </div>
        <div>
          <label class="form-label">Meta Description</label>
          <textarea name="meta_description" rows="2" maxlength="170" class="form-input" placeholder="Kosongkan untuk memakai ringkasan">{{ old('meta_description', $announcement->meta_description) }}</textarea>
        </div>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-5 space-y-4">
        <h2 class="text-sm font-semibold text-slate-800">Publikasi</h2>

        <div>
          <label class="form-label">Kategori</label>
          <select name="category" class="form-input">
            <option value="info" @selected(old('category', $announcement->category ?? 'info') === 'info')>Info</option>
            <option value="promo" @selected(old('category', $announcement->category) === 'promo')>Promo</option>
            <option value="maintenance" @selected(old('category', $announcement->category) === 'maintenance')>Maintenance</option>
            <option value="incident" @selected(old('category', $announcement->category) === 'incident')>Gangguan</option>
          </select>
        </div>

        <div>
          <label class="form-label">Jadwal Terbit</label>
          <input type="datetime-local" name="published_at"
                 value="{{ old('published_at', optional($announcement->published_at)->format('Y-m-d\TH:i')) }}"
                 class="form-input">
          <p class="text-[11px] text-slate-400 mt-1">Kosongkan untuk terbit sekarang. Isi tanggal ke depan untuk menjadwalkan.</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $announcement->is_published ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Terbitkan
        </label>

        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $announcement->is_pinned)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Sematkan di atas
        </label>

        <div class="flex flex-col gap-2 pt-2 border-t border-slate-100">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
          <a href="{{ route('admin.announcements') }}" class="btn btn-outline">Batal</a>
        </div>
      </div>
    </div>
  </form>

@endsection
