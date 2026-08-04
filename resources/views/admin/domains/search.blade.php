@extends('layouts.admin')

@section('title', 'Cek Domain')

@section('content')

  @include('admin.domains._nav')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Cek Ketersediaan Domain</h1>
    <p class="text-sm text-slate-500 mt-1">Cari domain lewat registrar aktif (default), hasil dicek untuk semua TLD yang sudah diberi harga.</p>
  </div>

  <form method="GET" action="{{ route('admin.domain.search') }}" class="card p-5 mb-5 flex flex-col sm:flex-row gap-3">
    <input type="text" name="domain" value="{{ $query }}" placeholder="contoh: namahosting" class="form-input flex-1" required>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass text-xs"></i> Cek Domain</button>
  </form>

  @if ($query)
    <div class="card overflow-hidden">
      @if (! $results['success'])
        <div class="p-5 text-sm text-rose-600 flex items-center gap-2">
          <i class="fa-solid fa-circle-exclamation"></i> {{ $results['message'] }}
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
                <th class="px-5 py-2.5 font-semibold">Domain</th>
                <th class="px-5 py-2.5 font-semibold">Ketersediaan</th>
                <th class="px-5 py-2.5 font-semibold text-right">Harga Register / Tahun</th>
                <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @forelse ($results['results'] as $domainName => $available)
                @php
                  $ext = '.' . \Illuminate\Support\Str::after($domainName, '.');
                  $tld = $tldPrices->get($ext);
                @endphp
                <tr class="hover:bg-slate-50/60">
                  <td class="px-5 py-3 font-medium text-slate-700">{{ $domainName }}</td>
                  <td class="px-5 py-3">
                    @if ($available)
                      <span class="badge badge-active"><i class="fa-solid fa-circle-check"></i> Tersedia</span>
                    @else
                      <span class="badge badge-suspended"><i class="fa-solid fa-circle-xmark"></i> Sudah Terdaftar</span>
                    @endif
                  </td>
                  <td class="px-5 py-3 text-right text-slate-700">
                    {{ $tld ? 'Rp ' . number_format($tld->register_price, 0, ',', '.') : '—' }}
                  </td>
                  <td class="px-5 py-3 text-right">
                    @if ($available)
                      <a href="{{ route('admin.domain.add.page', ['domain' => $domainName]) }}" class="btn btn-primary !py-1.5 !px-3 text-xs">
                        <i class="fa-solid fa-cart-plus text-xs"></i> Daftarkan
                      </a>
                    @else
                      <span class="text-xs text-slate-400">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">Tidak ada hasil. Tambahkan TLD dulu di menu TLD Pricing.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      @endif
    </div>
  @endif

@endsection
