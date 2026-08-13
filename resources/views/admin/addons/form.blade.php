@extends('layouts.admin')

@section('title', $addon->exists ? 'Edit Addon' : 'Tambah Addon')

@section('content')

  <a href="{{ route('admin.addons.index') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Addons</a>
  <h1 class="text-xl font-bold text-slate-800 mt-1 mb-6">{{ $addon->exists ? 'Edit Addon' : 'Tambah Addon' }}</h1>

  <form method="POST" action="{{ $addon->exists ? route('admin.addons.update', $addon) : route('admin.addons.store') }}" class="max-w-2xl space-y-5">
    @csrf
    @if ($addon->exists) @method('PUT') @endif

    <div class="card p-5 space-y-4">
      <div>
        <label class="form-label">Nama Addon</label>
        <input type="text" name="name" value="{{ old('name', $addon->name) }}" class="form-input" placeholder="mis. IP Dedicated" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Slug (opsional, otomatis dibuat dari nama kalau kosong)</label>
        <input type="text" name="slug" value="{{ old('slug', $addon->slug) }}" class="form-input" placeholder="ip-dedicated">
        @error('slug') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Deskripsi</label>
        <textarea name="description" rows="3" class="form-input" placeholder="Dijelaskan singkat ke klien saat memilih addon ini.">{{ old('description', $addon->description) }}</textarea>
        @error('description') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="card p-5">
      <label class="form-label mb-3">Harga per Siklus (kosongkan kalau tidak ditawarkan untuk siklus itu)</label>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-xs text-slate-500 mb-1 block">Bulanan</label>
          <input type="number" step="1" min="0" name="price_monthly" value="{{ old('price_monthly', $addon->price_monthly) }}" class="form-input">
        </div>
        <div>
          <label class="text-xs text-slate-500 mb-1 block">3 Bulan</label>
          <input type="number" step="1" min="0" name="price_quarterly" value="{{ old('price_quarterly', $addon->price_quarterly) }}" class="form-input">
        </div>
        <div>
          <label class="text-xs text-slate-500 mb-1 block">6 Bulan</label>
          <input type="number" step="1" min="0" name="price_semi_annually" value="{{ old('price_semi_annually', $addon->price_semi_annually) }}" class="form-input">
        </div>
        <div>
          <label class="text-xs text-slate-500 mb-1 block">Tahunan</label>
          <input type="number" step="1" min="0" name="price_annually" value="{{ old('price_annually', $addon->price_annually) }}" class="form-input">
        </div>
      </div>
      <p class="text-[11px] text-slate-400 mt-3">
        Addon otomatis ikut ditagih di invoice perpanjangan layanan hosting yang memasangnya — mengikuti
        siklus tagihan layanan itu sendiri. Kalau siklus layanan tidak punya harga di sini, addon tidak
        bisa dipasang untuk layanan dengan siklus itu.
      </p>
    </div>

    <div class="card p-5">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="form-label">Urutan Tampil</label>
          <input type="number" name="sort_order" value="{{ old('sort_order', $addon->sort_order ?? 0) }}" min="0" class="form-input">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600 mt-6">
          <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $addon->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Aktif (bisa dipasang klien)
        </label>
      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="{{ route('admin.addons.index') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
