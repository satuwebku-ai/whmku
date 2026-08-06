@extends('public.layout')

@php
  $seoTitle = 'Cek Ketersediaan Domain';
  $seoDescription = 'Cari dan daftarkan domain impian Anda — proses cepat, harga transparan.';
@endphp

@section('content')

  <div class="max-w-3xl mx-auto text-center mb-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-2">Cek Ketersediaan Domain</h1>
    <p class="text-slate-500">Ketik nama yang Anda inginkan, pilih ekstensi yang mau dicek.</p>
  </div>

  <form method="GET" action="{{ route('domain.search') }}" class="max-w-3xl mx-auto mb-8">
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
      <input type="text" name="domain" value="{{ $query }}" placeholder="contoh: tokosaya" class="form-input flex-1" required autofocus>
      <button type="submit" class="btn btn-primary shrink-0"><i class="fa-solid fa-magnifying-glass text-xs"></i> Cek Domain</button>
    </div>

    @if ($tldPrices->isNotEmpty())
      <div class="card p-4">
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Pilih Ekstensi</p>
          <div class="flex items-center gap-3 text-xs">
            <button type="button" onclick="document.querySelectorAll('[data-ext]').forEach(c=>c.checked=true)" class="text-accent hover:underline">Pilih semua</button>
            <button type="button" onclick="document.querySelectorAll('[data-ext]').forEach(c=>c.checked=false)" class="text-slate-400 hover:underline">Kosongkan</button>
          </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2">
          @foreach ($tldPrices as $ext => $tld)
            <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
              <input type="checkbox" name="extensions[]" value="{{ $ext }}" data-ext
                     @checked(in_array($ext, $selected)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
              {{ $ext }}
              <span class="text-[10px] text-slate-400">Rp{{ number_format($tld->register_price / 1000, 0) }}rb</span>
            </label>
          @endforeach
        </div>
      </div>
    @endif
  </form>

  @if ($tldPrices->isEmpty() && ! $query)
    <div class="max-w-3xl mx-auto rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
      <i class="fa-solid fa-triangle-exclamation"></i> Belum ada ekstensi domain yang dijual saat ini.
    </div>
  @endif

  @if ($query)
    <div class="max-w-3xl mx-auto card overflow-hidden">
      @if (! $results['success'])
        <div class="p-6 text-center text-sm text-rose-600">
          <i class="fa-solid fa-circle-exclamation"></i> {{ $results['message'] }}
          <p class="text-xs text-slate-400 mt-2">Coba pilih lebih sedikit ekstensi, atau coba lagi dalam beberapa saat.</p>
        </div>
      @else
        <div class="divide-y divide-slate-100">
          @forelse ($results['results'] as $domainName => $available)
            @php
              $ext = '.' . \Illuminate\Support\Str::after($domainName, '.');
              $tld = $tldPrices->get($ext);
            @endphp
            <div class="flex items-center justify-between px-5 py-4">
              <div>
                <p class="font-medium text-slate-800">{{ $domainName }}</p>
                @if ($tld)
                  <p class="text-xs text-slate-400">Rp {{ number_format($tld->register_price, 0, ',', '.') }} /tahun</p>
                @endif
              </div>

              @if ($available)
                <form method="POST" action="{{ route('domain.add-to-cart') }}">
                  @csrf
                  <input type="hidden" name="domain_name" value="{{ $domainName }}">
                  <input type="hidden" name="tld_id" value="{{ $tld->id ?? '' }}">
                  <input type="hidden" name="years" value="1">
                  <button type="submit" class="btn btn-primary !py-1.5 !px-3 text-xs" {{ $tld ? '' : 'disabled' }}>
                    <i class="fa-solid fa-cart-plus text-xs"></i> Daftarkan
                  </button>
                </form>
              @else
                <span class="badge badge-inactive">Sudah Terdaftar</span>
              @endif
            </div>
          @empty
            <div class="p-6 text-center text-sm text-slate-400">Tidak ada hasil untuk pencarian ini.</div>
          @endforelse
        </div>
      @endif
    </div>
  @endif

@endsection
