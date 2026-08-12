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
        <p class="text-[11px] text-slate-400 mt-1">Dihitung dari subtotal produk yang berlaku saja, bukan seluruh keranjang.</p>
      </div>
      <div>
        <label class="form-label">Maks. Pemakaian per Klien</label>
        <input type="number" min="1" name="usage_limit_per_client" value="{{ old('usage_limit_per_client', $coupon->usage_limit_per_client ?? 1) }}" class="form-input" required>
      </div>
    </div>

    <div class="rounded-lg border border-slate-100 p-4" id="scopeBox">
      <label class="form-label">Berlaku Untuk</label>
      <div class="grid grid-cols-2 gap-2 mb-3">
        <label class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-medium cursor-pointer text-center
                      {{ old('applies_to', $coupon->applies_to ?? 'all') === 'all' ? 'border-accent bg-accent/5 text-accent' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
          <input type="radio" name="applies_to" value="all" @checked(old('applies_to', $coupon->applies_to ?? 'all') === 'all') class="hidden" data-scope-radio>
          Semua Produk
        </label>
        <label class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-medium cursor-pointer text-center
                      {{ old('applies_to', $coupon->applies_to) === 'specific' ? 'border-accent bg-accent/5 text-accent' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
          <input type="radio" name="applies_to" value="specific" @checked(old('applies_to', $coupon->applies_to) === 'specific') class="hidden" data-scope-radio>
          Produk Tertentu
        </label>
      </div>

      <div id="scopeSpecific" class="{{ old('applies_to', $coupon->applies_to ?? 'all') === 'specific' ? '' : 'hidden' }} space-y-3">
        <div>
          <p class="text-xs font-medium text-slate-600 mb-1.5">Kategori (semua produk di dalamnya ikut berlaku)</p>
          <div class="flex flex-wrap gap-2">
            @forelse ($categories as $cat)
              @php $checkedCats = old('category_ids', $coupon->exists ? $coupon->categories->pluck('id')->all() : []); @endphp
              <label class="flex items-center gap-1.5 text-xs border border-slate-200 rounded-full px-3 py-1.5 cursor-pointer hover:border-accent/50">
                <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" @checked(in_array($cat->id, $checkedCats)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
                {{ $cat->name }}
              </label>
            @empty
              <p class="text-xs text-slate-400">Belum ada kategori produk.</p>
            @endforelse
          </div>
        </div>

        <div>
          <p class="text-xs font-medium text-slate-600 mb-1.5">Atau produk spesifik (di luar kategori manapun di atas)</p>
          <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto">
            @forelse ($products as $p)
              @php $checkedProducts = old('product_ids', $coupon->exists ? $coupon->products->pluck('id')->all() : []); @endphp
              <label class="flex items-center gap-1.5 text-xs border border-slate-200 rounded-full px-3 py-1.5 cursor-pointer hover:border-accent/50">
                <input type="checkbox" name="product_ids[]" value="{{ $p->id }}" @checked(in_array($p->id, $checkedProducts)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
                {{ $p->name }}
              </label>
            @empty
              <p class="text-xs text-slate-400">Belum ada produk.</p>
            @endforelse
          </div>
        </div>
        @error('scope') <p class="form-error">{{ $message }}</p> @enderror
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

  <script>
    (function () {
      const radios = document.querySelectorAll('[data-scope-radio]');
      const box = document.getElementById('scopeSpecific');

      function sync() {
        const active = document.querySelector('[data-scope-radio]:checked')?.value;
        box.classList.toggle('hidden', active !== 'specific');

        radios.forEach(function (r) {
          const label = r.closest('label');
          label.classList.toggle('border-accent', r.checked);
          label.classList.toggle('bg-accent/5', r.checked);
          label.classList.toggle('text-accent', r.checked);
        });
      }

      radios.forEach(r => r.addEventListener('change', sync));
      sync();
    })();
  </script>

@endsection
