@extends('public.layout-bootstrap')

@php
  use App\Models\Setting;

  $siteName = Setting::get('site_name', config('app.name'));
  $tagline  = Setting::get('site_tagline', 'Hosting cepat, domain murah, aktif dalam hitungan menit.');

  $seoTitle = $siteName . ' — Hosting & Domain Indonesia';
  $seoDescription = $tagline;
@endphp

@section('full-width')

  @include('public.partials.popup-banner-bootstrap')

  {{-- ══════════ Hero + pencarian domain ══════════ --}}
  <section class="position-relative overflow-hidden">
    <div class="position-absolute top-0 start-0 end-0 bottom-0" style="background:linear-gradient(160deg,#1e1b4b 0%,#312e81 40%,#4c1d95 75%,#1e1b4b 100%)"></div>

    <div class="position-relative container text-center py-5" style="max-width:48rem;padding-top:5rem!important;padding-bottom:5rem!important">
      <span class="d-inline-flex align-items-center gap-2 rounded-pill px-3 py-1 mb-3" style="font-size:11px;font-weight:600;background:rgba(255,255,255,.1);color:rgba(255,255,255,.8)">
        <i class="fa-solid fa-bolt"></i> Aktivasi otomatis, langsung online
      </span>

      <h1 class="fw-bold text-white mb-3" style="font-size:2.2rem;line-height:1.2">
        {{ $tagline }}
      </h1>
      <p class="mb-4 mx-auto" style="color:rgba(255,255,255,.6);max-width:36rem">
        Cek nama domain impianmu, pilih paket hosting, bayar — layanan langsung aktif tanpa menunggu.
      </p>

      {{-- Kotak cek domain --}}
      <form method="GET" action="{{ route('domain.search') }}"
            class="bg-white rounded-4 p-2 d-flex flex-column flex-sm-row gap-2 shadow mx-auto" style="max-width:36rem">
        <div class="d-flex align-items-center gap-2 flex-grow-1 px-3">
          <i class="fa-solid fa-globe text-muted"></i>
          <input type="text" name="domain" value="{{ request('domain') }}"
                 placeholder="ketik nama domain impianmu…"
                 class="w-100 py-2 border-0" style="outline:none;font-size:14px" required>
        </div>
        <button type="submit" class="btn btn-theme flex-shrink-0 px-4 py-2">
          <i class="fa-solid fa-magnifying-glass" style="font-size:12px"></i> Cek Domain
        </button>
      </form>

      @if ($popularTlds->isNotEmpty())
        <div class="d-flex flex-wrap align-items-center justify-content-center gap-4 mt-4" style="font-size:14px">
          @foreach ($popularTlds as $tld)
            <span style="color:rgba(255,255,255,.7)">
              <b class="text-white">{{ $tld->extension }}</b>
              Rp {{ number_format($tld->register_price, 0, ',', '.') }}
            </span>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  {{-- ══════════ Banner Promo ══════════ --}}
  <div class="container mt-4" style="max-width:72rem">
    @include('public._promo-banner-carousel-bootstrap')
  </div>

  {{-- ══════════ Keunggulan ══════════ --}}
  <section class="container position-relative" style="max-width:72rem;margin-top:-2rem;z-index:10">
    <div class="row g-3">
      @php
        $benefits = [
          ['icon' => 'fa-bolt',          'title' => 'Aktif Otomatis',   'desc' => 'Akun hosting dibuat otomatis begitu pembayaran masuk.'],
          ['icon' => 'fa-shield-halved', 'title' => 'Aman & Terjaga',   'desc' => 'SSL gratis, backup rutin, dan proteksi berlapis.'],
          ['icon' => 'fa-headset',       'title' => 'Dukungan Responsif','desc' => 'Tim support siap membantu lewat tiket dan chat.'],
          ['icon' => 'fa-wallet',        'title' => 'Bayar Mudah',      'desc' => 'Transfer bank, e-wallet, kartu kredit, dan QRIS.'],
        ];
      @endphp

      @foreach ($benefits as $item)
        <div class="col-sm-6 col-lg-4">
          <div class="card-public p-4 h-100">
            <span class="rounded-4 d-flex align-items-center justify-content-center mb-3" style="width:40px;height:40px;background:rgba(79,70,229,.12);color:#4f46e5">
              <i class="fa-solid {{ $item['icon'] }}"></i>
            </span>
            <h3 class="fw-semibold text-dark mb-1" style="font-size:14px">{{ $item['title'] }}</h3>
            <p class="text-muted mb-0" style="font-size:12px;line-height:1.6">{{ $item['desc'] }}</p>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- ══════════ Paket unggulan ══════════ --}}
  <section class="container py-5" style="max-width:72rem">
    <div class="text-center mb-4">
      <h2 class="fw-bold text-dark mb-2" style="font-size:1.6rem">Paket Hosting Pilihan</h2>
      <p class="text-muted mb-0" style="font-size:14px">Mulai kecil, naik kelas kapan saja tanpa pindah server.</p>
    </div>

    @if ($featured->isEmpty())
      <div class="card-public p-5 text-center">
        <p class="text-muted mb-1" style="font-size:14px">Katalog sedang disiapkan.</p>
        <p class="text-muted mb-0" style="font-size:12px">
          Belum ada produk yang bisa ditampilkan — tambahkan lewat menu Produk di admin panel.
        </p>
      </div>
    @else
      <div class="row g-3">
        @foreach ($featured as $product)
          <div class="col-sm-6 col-lg-4">
            @include('public.catalog._product-card-bootstrap', ['product' => $product])
          </div>
        @endforeach
      </div>

      <div class="text-center mt-4">
        <a href="{{ route('catalog.index.bootstrap-preview') }}" class="btn btn-outline-secondary">
          Lihat Semua Paket <i class="fa-solid fa-arrow-right" style="font-size:12px"></i>
        </a>
      </div>
    @endif
  </section>

  {{-- ══════════ Kategori ══════════ --}}
  @if ($categories->isNotEmpty())
    <section class="bg-white border-top border-bottom py-5">
      <div class="container" style="max-width:72rem">
        <div class="text-center mb-4">
          <h2 class="fw-bold text-dark mb-2" style="font-size:1.6rem">Layanan Kami</h2>
          <p class="text-muted mb-0" style="font-size:14px">Pilih kategori yang sesuai kebutuhanmu.</p>
        </div>

        <div class="row g-3">
          @foreach ($categories as $category)
            <div class="col-sm-6 col-lg-4">
              <a href="{{ route('catalog.category.bootstrap-preview', $category->slug) }}" class="card-public p-4 text-decoration-none d-block h-100">
                <span class="rounded-4 d-flex align-items-center justify-content-center mb-3" style="width:44px;height:44px;background:rgba(79,70,229,.12);color:#4f46e5">
                  <i class="fa-solid fa-server"></i>
                </span>
                <h3 class="fw-semibold text-dark mb-1" style="font-size:15px">{{ $category->name }}</h3>
                @if ($category->description)
                  <p class="text-muted mb-2" style="font-size:12px;line-height:1.6">{{ Str::limit($category->description, 90) }}</p>
                @endif
                <span class="text-theme fw-medium" style="font-size:12px">
                  {{ $category->products_count }} paket <i class="fa-solid fa-arrow-right" style="font-size:10px"></i>
                </span>
              </a>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ══════════ Pengumuman ══════════ --}}
  @if ($announcements->isNotEmpty())
    <section class="container py-5" style="max-width:72rem">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="fw-bold text-dark mb-0" style="font-size:1.3rem">Kabar Terbaru</h2>
        <a href="{{ route('announcements.index') }}" class="text-decoration-none text-theme" style="font-size:14px">Lihat semua</a>
      </div>

      <div class="row g-3">
        @foreach ($announcements as $item)
          <div class="col-sm-4">
            <a href="{{ route('announcements.show', $item->slug) }}" class="card-public p-4 text-decoration-none d-block h-100">
              <span class="badge-public-inactive text-capitalize mb-2 d-inline-block">{{ $item->category }}</span>
              <h3 class="fw-semibold text-dark mb-1" style="font-size:14px;line-height:1.4">{{ $item->title }}</h3>
              <p class="text-muted mb-0" style="font-size:12px">{{ $item->published_at?->format('d M Y') }}</p>
            </a>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  {{-- ══════════ Ajakan ══════════ --}}
  <section class="container pb-5" style="max-width:72rem">
    <div class="rounded-4 p-5 text-center position-relative overflow-hidden"
         style="background:linear-gradient(135deg,#4f46e5 0%,#6366F1 50%,#7c3aed 100%)">
      <h2 class="fw-bold text-white mb-2" style="font-size:1.6rem">Siap memulai website-mu?</h2>
      <p class="mb-4 mx-auto" style="color:rgba(255,255,255,.7);font-size:14px;max-width:32rem">
        Buat akun gratis, pilih paket, dan mulai online hari ini juga.
      </p>
      <div class="d-flex flex-wrap align-items-center justify-content-center gap-2">
        <a href="{{ route('catalog.index.bootstrap-preview') }}" class="btn bg-white text-primary">
          <i class="fa-solid fa-server" style="font-size:12px"></i> Lihat Paket Hosting
        </a>
        <a href="{{ route('client.register') }}" class="btn text-white" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.3)">
          Daftar Gratis
        </a>
      </div>
    </div>
  </section>

@endsection
