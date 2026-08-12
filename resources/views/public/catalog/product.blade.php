@extends('public.layout')

@php
  use Illuminate\Support\Str;
  $seoTitle = $product->name . ' — ' . $category->name;
  $seoDescription = $product->tagline ?: Str::limit(strip_tags((string) $product->description), 155);
  $cycles = $product->availableCycles();
  $unit = ['monthly' => '/bulan', 'quarterly' => '/3 bulan', 'semi_annually' => '/6 bulan', 'annually' => '/tahun'];
@endphp

@section('content')

  <nav class="text-xs text-slate-400 mb-4">
    <a href="{{ route('catalog.index') }}" class="hover:text-accent">Hosting</a> /
    <a href="{{ route('catalog.category', $category->slug) }}" class="hover:text-accent">{{ $category->name }}</a> /
    {{ $product->name }}
  </nav>

  @if ($errors->any())
    <div class="mb-5 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
      {{ $errors->first() }}
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
      <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ $product->name }}</h1>
      @if ($product->tagline)
        <p class="text-slate-500 mb-6">{{ $product->tagline }}</p>
      @endif

      @if ($product->description)
        <div class="prose-content mb-6">{!! nl2br(e($product->description)) !!}</div>
      @endif

      @if ($product->features)
        <div class="card p-6">
          <h2 class="text-sm font-semibold text-slate-800 mb-4">Fitur yang Anda Dapatkan</h2>
          <ul class="grid sm:grid-cols-2 gap-3">
            @foreach ($product->features as $feature)
              <li class="flex items-start gap-2 text-sm text-slate-600">
                <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 shrink-0"></i> {{ $feature }}
              </li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>

    {{-- Panel pemesanan --}}
    <div>
      <div class="card p-6 sticky top-24">
        @if (! $product->isInStock())
          <div class="text-center py-6">
            <i class="fa-solid fa-box-open text-3xl text-slate-300 mb-3"></i>
            <p class="font-semibold text-slate-700 mb-1">Stok Habis</p>
            <p class="text-sm text-slate-500">Paket ini sedang tidak tersedia untuk pemesanan baru.</p>
          </div>
        @else
        <form method="POST" action="{{ route('cart.add-product') }}" id="orderForm">
          @csrf
          <input type="hidden" name="product_id" value="{{ $product->id }}">

          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Pilih Siklus Tagihan</p>
          <div class="space-y-2 mb-5">
            @foreach ($cycles as $cycleKey => $price)
              <label class="flex items-center justify-between p-3 rounded-lg border border-slate-200 cursor-pointer hover:border-accent/50 transition-colors cycle-option">
                <span class="flex items-center gap-2.5">
                  <input type="radio" name="billing_cycle" value="{{ $cycleKey }}" {{ $loop->first ? 'checked' : '' }} required
                         class="border-slate-300 text-accent focus:ring-accent/40">
                  <span class="text-sm text-slate-700">{{ \App\Models\Product::CYCLES[$cycleKey] }}</span>
                </span>
                <span class="text-sm font-semibold text-slate-800">Rp {{ number_format($price, 0, ',', '.') }}</span>
              </label>
            @endforeach
          </div>

          @if ($product->setup_fee > 0)
            <p class="text-xs text-slate-400 mb-4">+ Biaya setup Rp {{ number_format($product->setup_fee, 0, ',', '.') }} (sekali bayar)</p>
          @endif

          @if ($product->allowsDomain())
            <div class="border-t border-slate-100 pt-4 mb-4">
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
                Domain {{ $product->requiresDomain() ? '(Wajib)' : '(Opsional)' }}
              </p>

              <div class="space-y-2">
                @unless ($product->requiresDomain())
                  <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="radio" name="domain_mode" value="" checked class="domain-mode-radio border-slate-300 text-accent focus:ring-accent/40">
                    Tidak perlu domain
                  </label>
                @endunless
                <label class="flex items-center gap-2 text-sm text-slate-600">
                  <input type="radio" name="domain_mode" value="register" {{ $product->requiresDomain() ? 'checked' : '' }} class="domain-mode-radio border-slate-300 text-accent focus:ring-accent/40">
                  Daftarkan domain baru
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                  <input type="radio" name="domain_mode" value="transfer" class="domain-mode-radio border-slate-300 text-accent focus:ring-accent/40">
                  Transfer domain dari registrar lain
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                  <input type="radio" name="domain_mode" value="existing" class="domain-mode-radio border-slate-300 text-accent focus:ring-accent/40">
                  Saya sudah punya domain ini (arahkan nameserver saja)
                </label>
              </div>

              <div id="domainNameField" class="mt-3 {{ $product->requiresDomain() ? '' : 'hidden' }}">
                <input type="text" name="domain_name" value="{{ old('domain_name') }}" placeholder="contoh.com" class="form-input">
                <p id="domainNameHint" class="text-[11px] text-slate-400 mt-1">
                  Ketersediaan domain baru dicek ulang saat checkout.
                  Untuk cek dulu, pakai <a href="{{ route('domain.search') }}" class="text-accent hover:underline" target="_blank">halaman Cek Domain</a>.
                </p>
              </div>

              <div id="transferAuthField" class="mt-3 hidden">
                <label class="text-xs font-medium text-slate-600 mb-1 block">Kode EPP / Auth Code</label>
                <input type="text" name="transfer_auth_code" value="{{ old('transfer_auth_code') }}" placeholder="Diminta dari registrar domain Anda saat ini" class="form-input">
                <p class="text-[11px] text-slate-400 mt-1">
                  Proses transfer butuh persetujuan pemilik domain (email dari registrar lama)
                  dan biasanya memakan waktu 5–7 hari, bukan langsung aktif detik itu juga.
                </p>
              </div>
            </div>
          @endif

          <button type="submit" class="btn btn-primary w-full">
            <i class="fa-solid fa-cart-plus text-xs"></i> Tambah ke Keranjang
          </button>
        </form>
        @endif
      </div>
    </div>
  </div>

  @if ($related->isNotEmpty())
    <div class="mt-14">
      <h2 class="text-lg font-bold text-slate-800 mb-4">Paket Lain di {{ $category->name }}</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ($related as $rp)
          @include('public.catalog._product-card', ['product' => $rp])
        @endforeach
      </div>
    </div>
  @endif

  <script>
    (function () {
      const radios = document.querySelectorAll('.domain-mode-radio');
      const field = document.getElementById('domainNameField');
      const hint = document.getElementById('domainNameHint');
      const transferField = document.getElementById('transferAuthField');
      if (!radios.length || !field) return;

      function sync() {
        const checked = document.querySelector('.domain-mode-radio:checked');
        const mode = checked ? checked.value : '';
        const needsInput = mode === 'register' || mode === 'transfer' || mode === 'existing';

        field.classList.toggle('hidden', !needsInput);
        field.querySelector('input').required = !!needsInput;

        transferField.classList.toggle('hidden', mode !== 'transfer');
        transferField.querySelector('input').required = mode === 'transfer';

        if (hint) {
          hint.textContent = mode === 'transfer'
            ? 'Domain harus sudah "unlock" di registrar lama sebelum bisa ditransfer.'
            : (mode === 'existing'
                ? 'Kami akan minta Anda mengarahkan nameserver domain ini setelah checkout.'
                : 'Ketersediaan domain baru dicek ulang saat checkout.');
        }
      }

      radios.forEach(r => r.addEventListener('change', sync));
      sync();
    })();
  </script>

@endsection
