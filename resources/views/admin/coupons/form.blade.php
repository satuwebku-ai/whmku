@extends('layouts.admin')

@section('title', $coupon->exists ? 'Edit Kupon' : 'Tambah Kupon')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $coupon->exists ? 'Edit Kupon' : 'Tambah Kupon' }}</h1>
  </div>

  <form method="POST" action="{{ $coupon->exists ? route('admin.coupon.update', $coupon) : route('admin.coupon.add') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Kode Kupon</label>
        <input type="text" name="code" value="{{ old('code', $coupon->code) }}" placeholder="HEMAT30" class="form-input uppercase" required style="text-transform:uppercase">
        @error('code') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">Otomatis disimpan dalam huruf kapital.</p>
      </div>
      <div>
        <label class="form-label">Tipe Diskon</label>
        <select name="type" class="form-input">
          <option value="percent" @selected(old('type', $coupon->type ?? 'percent') === 'percent')>Persentase (%)</option>
          <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Nominal Tetap (Rp)</option>
        </select>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nilai Diskon</label>
        <input type="number" step="0.01" min="0.01" name="value" value="{{ old('value', $coupon->value) }}" class="form-input" required>
        @error('value') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">Isi angka saja — 30 untuk 30%, atau 50000 untuk Rp 50.000.</p>
      </div>
      <div>
        <label class="form-label">Maks. Potongan (Rp, opsional)</label>
        <input type="number" step="1" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" class="form-input" placeholder="Tanpa batas">
        <p class="text-[11px] text-slate-400 mt-1">Berguna untuk tipe persentase, mis. "30% maks Rp 100.000".</p>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Min. Transaksi (Rp)</label>
        <input type="number" step="1" min="0" name="min_order" value="{{ old('min_order', $coupon->min_order ?? 0) }}" class="form-input">
      </div>
      <div>
        <label class="form-label">Maks. Pemakaian per Klien</label>
        <input type="number" min="1" name="usage_limit_per_client" value="{{ old('usage_limit_per_client', $coupon->usage_limit_per_client ?? 1) }}" class="form-input" required>
      </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="form-label">Kuota Total (opsional)</label>
        <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="form-input" placeholder="Tanpa batas">
      </div>
      <div>
        <label class="form-label">Mulai Berlaku</label>
        <input type="date" name="starts_at" value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d')) }}" class="form-input">
      </div>
      <div>
        <label class="form-label">Berlaku Sampai</label>
        <input type="date" name="expires_at" value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d')) }}" class="form-input">
        @error('expires_at') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-600">
      <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
      Aktif
    </label>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.coupons') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
