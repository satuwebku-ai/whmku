@extends('public.layout')

@php
  use App\Models\Setting;

  $siteName = Setting::get('site_name', config('app.name'));
  $tagline  = Setting::get('site_tagline', 'Hosting cepat, domain murah, aktif dalam hitungan menit.');

  $seoTitle = $siteName . ' — Hosting & Domain Indonesia';
  $seoDescription = $tagline;
@endphp

@section('full-width')

  @include('public.partials.popup-banner')

  {{-- ══════════ Hero + pencarian domain ══════════ --}}
  <section class="relative overflow-hidden">
    <div class="absolute inset-0" style="background:linear-gradient(160deg,#1e1b4b 0%,#312e81 40%,#4c1d95 75%,#1e1b4b 100%)"></div>
    <div class="absolute inset-0 opacity-[0.15]" style="background-image:radial-gradient(circle at 20% 20%, white 1px, transparent 1px);background-size:32px 32px"></div>

    <div class="relative max-w-4xl mx-auto px-6 py-20 text-center">
      <span class="inline-flex items-center gap-2 text-[11px] font-semibold px-3 py-1 rounded-full bg-white/10 text-white/80 mb-5">
        <i class="fa-solid fa-bolt"></i> Aktivasi otomatis, langsung online
      </span>

      <h1 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight mb-4">
        {{ $tagline }}
      </h1>
      <p class="text-white/60 mb-8 max-w-xl mx-auto">
        Cek nama domain impianmu, pilih paket hosting, bayar — layanan langsung aktif tanpa menunggu.
      </p>

      {{-- Kotak cek domain --}}
      <form method="GET" action="{{ route('domain.search') }}"
            class="bg-white rounded-2xl p-2 flex flex-col sm:flex-row gap-2 shadow-xl max-w-2xl mx-auto">
        <div class="flex items-center gap-2 flex-1 px-3">
          <i class="fa-solid fa-globe text-slate-300"></i>
          <input type="text" name="domain" value="{{ request('domain') }}"
                 placeholder="ketik nama domain impianmu…"
                 class="w-full py-2.5 text-sm outline-none bg-transparent" required>
        </div>
        <button type="submit" class="btn btn-primary !py-3 !px-6 shrink-0">
          <i class="fa-solid fa-magnifying-glass text-xs"></i> Cek Domain
        </button>
      </form>

      @if ($popularTlds->isNotEmpty())
        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 mt-6 text-sm">
          @foreach ($popularTlds as $tld)
            <span class="text-white/70">
              <b class="text-white">{{ $tld->extension }}</b>
              Rp {{ number_format($tld->register_price, 0, ',', '.') }}
            </span>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  {{-- ══════════ Banner Promo ══════════ --}}
  <div class="max-w-6xl mx-auto px-6 mt-8">
    @include('public._promo-banner-carousel')
  </div>

  {{-- ══════════ Keunggulan ══════════ --}}
  <section class="max-w-6xl mx-auto px-6 -mt-8 relative z-10">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
      @php
        $benefits = [
          ['icon' => 'fa-bolt',          'title' => 'Aktif Otomatis',   'desc' => 'Akun hosting dibuat otomatis begitu pembayaran masuk.'],
          ['icon' => 'fa-shield-halved', 'title' => 'Aman & Terjaga',   'desc' => 'SSL gratis, backup rutin, dan proteksi berlapis.'],
          ['icon' => 'fa-headset',       'title' => 'Dukungan Responsif','desc' => 'Tim support siap membantu lewat tiket dan chat.'],
          ['icon' => 'fa-wallet',        'title' => 'Bayar Mudah',      'desc' => 'Transfer bank, e-wallet, kartu kredit, dan QRIS.'],
        ];
      @endphp

      @foreach ($benefits as $item)
        <div class="card p-5">
          <span class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-3">
            <i class="fa-solid {{ $item['icon'] }}"></i>
          </span>
          <h3 class="font-semibold text-slate-800 text-sm mb-1">{{ $item['title'] }}</h3>
          <p class="text-xs text-slate-500 leading-relaxed">{{ $item['desc'] }}</p>
        </div>
      @endforeach
    </div>
  </section>

  {{-- ══════════ Paket unggulan ══════════ --}}
  <section class="max-w-6xl mx-auto px-6 py-16">
    <div class="text-center mb-8">
      <h2 class="text-2xl font-bold text-slate-800 mb-2">Paket Hosting Pilihan</h2>
      <p class="text-slate-500 text-sm">Mulai kecil, naik kelas kapan saja tanpa pindah server.</p>
    </div>

    @if ($featured->isEmpty())
      <div class="card p-10 text-center">
        <p class="text-slate-500 text-sm mb-1">Katalog sedang disiapkan.</p>
        <p class="text-xs text-slate-400">
          Belum ada produk yang bisa ditampilkan — tambahkan lewat menu Produk di admin panel.
        </p>
      </div>
    @else
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ($featured as $product)
          @include('public.catalog._product-card', ['product' => $product])
        @endforeach
      </div>

      <div class="text-center mt-8">
        <a href="{{ route('catalog.index') }}" class="btn btn-outline">
          Lihat Semua Paket <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
      </div>
    @endif
  </section>

  {{-- ══════════ Kategori ══════════ --}}
  @if ($categories->isNotEmpty())
    <section class="bg-white border-y border-slate-200 py-16">
      <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-bold text-slate-800 mb-2">Layanan Kami</h2>
          <p class="text-slate-500 text-sm">Pilih kategori yang sesuai kebutuhanmu.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
          @foreach ($categories as $category)
            <a href="{{ route('catalog.category', $category->slug) }}"
               class="card p-6 hover:border-accent/40 hover:shadow-md transition-all group">
              <span class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-3 group-hover:bg-accent group-hover:text-white transition-colors">
                <i class="fa-solid fa-server"></i>
              </span>
              <h3 class="font-semibold text-slate-800 mb-1">{{ $category->name }}</h3>
              @if ($category->description)
                <p class="text-xs text-slate-500 leading-relaxed mb-2">{{ Str::limit($category->description, 90) }}</p>
              @endif
              <span class="text-xs text-accent font-medium">
                {{ $category->products_count }} paket <i class="fa-solid fa-arrow-right text-[10px]"></i>
              </span>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ══════════ Pengumuman ══════════ --}}
  @if ($announcements->isNotEmpty())
    <section class="max-w-6xl mx-auto px-6 py-16">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-slate-800">Kabar Terbaru</h2>
        <a href="{{ route('announcements.index') }}" class="text-sm text-accent hover:underline">Lihat semua</a>
      </div>

      <div class="grid sm:grid-cols-3 gap-5">
        @foreach ($announcements as $item)
          <a href="{{ route('announcements.show', $item->slug) }}" class="card p-5 hover:border-accent/40 transition-colors">
            <span class="badge badge-inactive capitalize mb-2">{{ $item->category }}</span>
            <h3 class="font-semibold text-slate-800 text-sm mb-1 leading-snug">{{ $item->title }}</h3>
            <p class="text-xs text-slate-400">{{ $item->published_at?->format('d M Y') }}</p>
          </a>
        @endforeach
      </div>
    </section>
  @endif

  {{-- ══════════ Ajakan ══════════ --}}
  <section class="max-w-6xl mx-auto px-6 pb-16">
    <div class="rounded-2xl p-10 text-center relative overflow-hidden"
         style="background:linear-gradient(135deg,#4f46e5 0%,#6366F1 50%,#7c3aed 100%)">
      <h2 class="text-2xl font-bold text-white mb-2">Siap memulai website-mu?</h2>
      <p class="text-white/70 text-sm mb-6 max-w-lg mx-auto">
        Buat akun gratis, pilih paket, dan mulai online hari ini juga.
      </p>
      <div class="flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('catalog.index') }}" class="btn bg-white text-indigo-700 border-white hover:bg-slate-100">
          <i class="fa-solid fa-server text-xs"></i> Lihat Paket Hosting
        </a>
        <a href="{{ route('client.register') }}" class="btn bg-white/10 text-white border-white/30 hover:bg-white/20">
          Daftar Gratis
        </a>
      </div>
    </div>
  </section>

@endsection
