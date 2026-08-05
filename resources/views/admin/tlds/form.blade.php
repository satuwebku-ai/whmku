@extends('layouts.admin')

@section('title', $tld->exists ? 'Edit TLD' : 'Tambah TLD')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $tld->exists ? 'Edit TLD' : 'Tambah TLD' }}</h1>
  </div>

  <form method="POST" action="{{ $tld->exists ? route('admin.tlds.update', $tld) : route('admin.tlds.store') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    @if ($tld->exists) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Ekstensi</label>
        <input type="text" name="extension" value="{{ old('extension', $tld->extension) }}" placeholder=".com" class="form-input" required>
        @error('extension') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Registrar (opsional)</label>
        <select name="registrar_id" class="form-input">
          <option value="">— Tidak ditentukan —</option>
          @foreach ($registrars as $r)
            <option value="{{ $r->id }}" @selected(old('registrar_id', $tld->registrar_id) == $r->id)>{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    @if ($tld->exists && $tld->hasCost())
      <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-slate-600">
        <b>Harga modal dari registrar:</b>
        Register Rp {{ number_format($tld->cost_register, 0, ',', '.') }} ·
        Renew Rp {{ number_format($tld->cost_renew, 0, ',', '.') }} ·
        Transfer Rp {{ number_format($tld->cost_transfer, 0, ',', '.') }}
        @if ($tld->cost_synced_at)
          <span class="text-slate-400">(disinkronkan {{ $tld->cost_synced_at->diffForHumans() }})</span>
        @endif
        <br>Harga modal diperbarui otomatis saat sinkronisasi, jadi tidak bisa diedit manual di sini.
      </div>
    @endif

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="form-label">Harga Register</label>
        <input type="number" step="0.01" name="register_price" value="{{ old('register_price', $tld->register_price) }}" class="form-input" required>
      </div>
      <div>
        <label class="form-label">Harga Renew</label>
        <input type="number" step="0.01" name="renew_price" value="{{ old('renew_price', $tld->renew_price) }}" class="form-input" required>
      </div>
      <div>
        <label class="form-label">Harga Transfer</label>
        <input type="number" step="0.01" name="transfer_price" value="{{ old('transfer_price', $tld->transfer_price) }}" class="form-input" required>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Min. Tahun</label>
        <input type="number" name="min_years" value="{{ old('min_years', $tld->min_years ?? 1) }}" class="form-input" required>
      </div>
      <div>
        <label class="form-label">Maks. Tahun</label>
        <input type="number" name="max_years" value="{{ old('max_years', $tld->max_years ?? 10) }}" class="form-input" required>
      </div>
    </div>

    {{-- Harga per durasi --}}
    <div class="pt-4 border-t border-slate-100">
      <h2 class="text-sm font-semibold text-slate-800 mb-1">Harga per Durasi</h2>
      <p class="text-xs text-slate-500 mb-3">
        Kosongkan untuk memakai perhitungan otomatis (harga 1 tahun × jumlah tahun).
        Isi hanya kalau ingin memberi diskon untuk pembelian jangka panjang.
      </p>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
              <th class="px-3 py-2 font-semibold">Durasi</th>
              <th class="px-3 py-2 font-semibold text-right">Harga Register (Rp)</th>
              <th class="px-3 py-2 font-semibold text-right">Harga Renew (Rp)</th>
              <th class="px-3 py-2 font-semibold text-right">Otomatis</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @php
              $maxYears = (int) old('max_years', $tld->max_years ?? 10);
              $yearReg = old('year_prices', $tld->year_prices ?? []);
              $yearRen = old('year_renew_prices', $tld->year_renew_prices ?? []);
            @endphp

            @for ($y = 1; $y <= max($maxYears, 1); $y++)
              <tr class="hover:bg-slate-50/60">
                <td class="px-3 py-2 font-medium text-slate-700">{{ $y }} tahun</td>
                <td class="px-3 py-2 text-right">
                  @if ($y === 1)
                    <span class="text-xs text-slate-400">pakai Harga Register di atas</span>
                  @else
                    <input type="number" step="1" min="0" name="year_prices[{{ $y }}]"
                           value="{{ $yearReg[$y] ?? $yearReg[(string) $y] ?? '' }}"
                           placeholder="{{ number_format((float) old('register_price', $tld->register_price ?? 0) * $y, 0, ',', '') }}"
                           class="w-32 px-2 py-1.5 rounded-lg border border-slate-200 text-right text-sm outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
                  @endif
                </td>
                <td class="px-3 py-2 text-right">
                  @if ($y === 1)
                    <span class="text-xs text-slate-400">pakai Harga Renew di atas</span>
                  @else
                    <input type="number" step="1" min="0" name="year_renew_prices[{{ $y }}]"
                           value="{{ $yearRen[$y] ?? $yearRen[(string) $y] ?? '' }}"
                           placeholder="{{ number_format((float) old('renew_price', $tld->renew_price ?? 0) * $y, 0, ',', '') }}"
                           class="w-32 px-2 py-1.5 rounded-lg border border-slate-200 text-right text-sm outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
                  @endif
                </td>
                <td class="px-3 py-2 text-right text-xs text-slate-400">
                  Rp {{ number_format((float) old('register_price', $tld->register_price ?? 0) * $y, 0, ',', '.') }}
                </td>
              </tr>
            @endfor
          </tbody>
        </table>
      </div>
      <p class="text-[11px] text-slate-400 mt-2">
        Angka abu-abu di kolom terakhir adalah harga otomatis. Kolom isian menampilkannya
        sebagai placeholder — kalau dibiarkan kosong, itulah yang dipakai.
      </p>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-600">
      <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tld->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
      Aktif (tampil di halaman Cek Domain)
    </label>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.tlds.index') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
