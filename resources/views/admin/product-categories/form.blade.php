@extends('layouts.admin')
@section('title', $category->exists ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')
  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $category->exists ? 'Edit Kategori' : 'Tambah Kategori Produk' }}</h1>
  </div>

  <form method="POST" action="{{ $category->exists ? route('admin.product-categories.update', $category) : route('admin.product-categories.store') }}" class="card p-6 max-w-xl space-y-4">
    @csrf
    @if ($category->exists) @method('PUT') @endif

    <div>
      <label class="form-label">Nama Kategori</label>
      <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-input" required placeholder="Shared Hosting">
      @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="form-label">Slug URL</label>
      <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" class="form-input" placeholder="otomatis dari nama">
      @error('slug') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="form-label">Deskripsi Singkat</label>
      <textarea name="description" rows="2" maxlength="500" class="form-input" placeholder="Tampil di halaman katalog">{{ old('description', $category->description) }}</textarea>
    </div>

    <div>
      <label class="form-label">Ikon Font Awesome <span class="text-slate-400 font-normal">(opsional)</span></label>
      <input type="text" name="icon" value="{{ old('icon', $category->icon) }}" class="form-input" placeholder="fa-server">
      <p class="text-[11px] text-slate-400 mt-1">Nama class tanpa "fa-solid", mis. <code>fa-server</code>, <code>fa-globe</code>, <code>fa-rocket</code>.</p>
    </div>

    <div>
      <label class="form-label">Urutan Tampil</label>
      <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="form-input">
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-600">
      <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
      Aktif (tampil di katalog publik)
    </label>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.product-categories.index') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>
@endsection
