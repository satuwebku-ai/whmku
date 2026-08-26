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
  <div style="position:relative;overflow:hidden;background:linear-gradient(160deg,#1e1b4b 0%,#312e81 40%,#4c1d95 75%,#1e1b4b 100%)">
    <div style="position:relative;max-width:44rem;margin:0 auto;padding:6rem 1.5rem;text-align:center">
      <span style="display:inline-flex;align-items:center;gap:.5rem;border-radius:999px;padding:.5rem 1rem;margin-bottom:1.5rem;font-size:12px;font-weight:600;background:rgba(255,255,255,.1);color:rgba(255,255,255,.85)">
        <i class="fa-solid fa-bolt"></i> Aktivasi otomatis, langsung online
      </span>

      <h1 style="color:#fff;font-weight:700;font-size:2.5rem;line-height:1.2;letter-spacing:-.02em;margin:0 0 1.25rem 0">
        {{ $tagline }}
      </h1>
      <p style="color:rgba(255,255,255,.65);max-width:32rem;margin:0 auto 2.5rem auto;font-size:16px;line-height:1.7">
        Cek nama domain impianmu, pilih paket hosting, bayar — layanan langsung aktif tanpa menunggu.
      </p>

      {{-- Kotak cek domain --}}
      <form method="GET" action="{{ route('domain.search') }}" id="heroSearchForm"
            style="background:#fff;border-radius:1rem;padding:.5rem;display:flex;flex-direction:column;gap:.5rem;box-shadow:0 20px 40px rgba(0,0,0,.25);max-width:34rem;margin:0 auto">
        <div style="display:flex;align-items:center;gap:.5rem;flex:1;padding:0 .75rem">
          <i class="fa-solid fa-globe" style="color:#94a3b8"></i>
          <input type="text" name="domain" value="{{ request('domain') }}"
                 placeholder="ketik nama domain impianmu…"
                 style="width:100%;padding:.6rem 0;border:0;outline:none;font-size:15px" required>
        </div>
        <button type="submit" class="btn btn-theme" style="flex-shrink:0;padding:.6rem 1.5rem">
          <i class="fa-solid fa-magnifying-glass" style="font-size:12px"></i> Cek Domain
        </button>
      </form>

      <style>
        @media (min-width: 576px) {
          #heroSearchForm { flex-direction: row !important; }
        }
      </style>

      @if ($popularTlds->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:1.5rem;margin-top:1.5rem;font-size:14px">
          @foreach ($popularTlds as $tld)
            <span style="color:rgba(255,255,255,.65)">
              <b style="color:#fff">{{ $tld->extension }}</b>
              Rp {{ number_format($tld->register_price, 0, ',', '.') }}
            </span>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- ══════════ Banner Promo ══════════ --}}
  <div style="max-width:72rem;margin:1.5rem auto 0 auto;padding:0 1.5rem">
    @include('public._promo-banner-carousel')
  </div>

  {{-- ══════════ Keunggulan ══════════ --}}
  <div style="position:relative;max-width:72rem;margin:-2rem auto 0 auto;padding:0 1.5rem;z-index:10">
    <div style="display:flex;flex-wrap:wrap;gap:1.5rem">
      @php
        $benefits = [
          ['icon' => 'fa-bolt',          'title' => 'Aktif Otomatis',    'desc' => 'Akun hosting dibuat otomatis begitu pembayaran masuk.'],
          ['icon' => 'fa-shield-halved', 'title' => 'Aman & Terjaga',    'desc' => 'SSL gratis, backup rutin, dan proteksi berlapis.'],
          ['icon' => 'fa-headset',       'title' => 'Dukungan Responsif','desc' => 'Tim support siap membantu lewat tiket dan chat.'],
          ['icon' => 'fa-wallet',        'title' => 'Bayar Mudah',       'desc' => 'Transfer bank, e-wallet, kartu kredit, dan QRIS.'],
        ];
      @endphp

      @foreach ($benefits as $item)
        <div style="flex:1 1 240px;min-width:240px">
          <div class="card-public" style="padding:1.5rem;height:100%">
            <span style="display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:1rem;margin-bottom:1rem;background:rgba(79,70,229,.1);color:#4f46e5;font-size:16px">
              <i class="fa-solid {{ $item['icon'] }}"></i>
            </span>
            <h3 style="font-weight:600;color:#1e293b;font-size:15px;margin:0 0 .5rem 0">{{ $item['title'] }}</h3>
            <p style="color:#64748b;font-size:13px;line-height:1.7;margin:0">{{ $item['desc'] }}</p>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- ══════════ Paket unggulan ══════════ --}}
  <div style="max-width:72rem;margin:0 auto;padding:5rem 1.5rem">
    <div style="text-align:center;margin-bottom:3rem">
      <h2 style="font-weight:700;color:#1e293b;font-size:1.75rem;letter-spacing:-.02em;margin:0 0 .5rem 0">Paket Hosting Pilihan</h2>
      <p style="color:#64748b;font-size:15px;margin:0">Mulai kecil, naik kelas kapan saja tanpa pindah server.</p>
    </div>

    @if ($featured->isEmpty())
      <div class="card-public" style="padding:3rem;text-align:center">
        <p style="color:#64748b;font-size:14px;margin:0 0 .25rem 0">Katalog sedang disiapkan.</p>
        <p style="color:#94a3b8;font-size:12px;margin:0">
          Belum ada produk yang bisa ditampilkan — tambahkan lewat menu Produk di admin panel.
        </p>
      </div>
    @else
      <div style="display:flex;flex-wrap:wrap;gap:1.5rem">
        @foreach ($featured as $product)
          <div style="flex:1 1 300px;min-width:280px;max-width:400px">
            @include('public.catalog._product-card', ['product' => $product])
          </div>
        @endforeach
      </div>

      <div style="text-align:center;margin-top:3rem">
        <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary" style="padding:.6rem 1.5rem">
          Lihat Semua Paket <i class="fa-solid fa-arrow-right" style="font-size:12px"></i>
        </a>
      </div>
    @endif
  </div>

  {{-- ══════════ Kategori ══════════ --}}
  @if ($categories->isNotEmpty())
    <div style="background:#fff;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;padding:5rem 0">
      <div style="max-width:72rem;margin:0 auto;padding:0 1.5rem">
        <div style="text-align:center;margin-bottom:3rem">
          <h2 style="font-weight:700;color:#1e293b;font-size:1.75rem;letter-spacing:-.02em;margin:0 0 .5rem 0">Layanan Kami</h2>
          <p style="color:#64748b;font-size:15px;margin:0">Pilih kategori yang sesuai kebutuhanmu.</p>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:1.5rem">
          @foreach ($categories as $category)
            <div style="flex:1 1 280px;min-width:260px;max-width:360px">
              <a href="{{ $category->publicUrl() }}" class="card-public" style="display:block;padding:1.5rem;text-decoration:none;height:100%">
                <span style="display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:1rem;margin-bottom:1rem;background:rgba(79,70,229,.1);color:#4f46e5;font-size:18px">
                  <i class="fa-solid fa-server"></i>
                </span>
                <h3 style="font-weight:600;color:#1e293b;font-size:16px;margin:0 0 .5rem 0">{{ $category->name }}</h3>
                @if ($category->description)
                  <p style="color:#64748b;font-size:13px;line-height:1.7;margin:0 0 .75rem 0">{{ Str::limit($category->description, 90) }}</p>
                @endif
                <span style="display:inline-flex;align-items:center;gap:.25rem;color:var(--lumora-theme);font-weight:500;font-size:13px">
                  {{ $category->products_count }} paket <i class="fa-solid fa-arrow-right" style="font-size:10px"></i>
                </span>
              </a>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endif

  {{-- ══════════ Pengumuman ══════════ --}}
  @if ($announcements->isNotEmpty())
    <div style="max-width:72rem;margin:0 auto;padding:5rem 1.5rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3rem">
        <h2 style="font-weight:700;color:#1e293b;font-size:1.4rem;letter-spacing:-.02em;margin:0">Kabar Terbaru</h2>
        <a href="{{ route('announcements.index') }}" style="text-decoration:none;color:var(--lumora-theme);font-weight:500;font-size:14px">Lihat semua</a>
      </div>

      <div style="display:flex;flex-wrap:wrap;gap:1.5rem">
        @foreach ($announcements as $item)
          <div style="flex:1 1 280px;min-width:260px">
            <a href="{{ route('announcements.show', $item->slug) }}" class="card-public" style="display:block;padding:1.5rem;text-decoration:none;height:100%">
              <span class="badge-public-inactive" style="text-transform:capitalize;margin-bottom:.75rem;display:inline-block">{{ $item->category }}</span>
              <h3 style="font-weight:600;color:#1e293b;font-size:15px;line-height:1.5;margin:0 0 .5rem 0">{{ $item->title }}</h3>
              <p style="color:#94a3b8;font-size:12px;margin:0">{{ $item->published_at?->format('d M Y') }}</p>
            </a>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- ══════════ Ajakan ══════════ --}}
  <div style="max-width:72rem;margin:0 auto;padding:5rem 1.5rem">
    <div style="border-radius:1rem;padding:4rem 2rem;text-align:center;position:relative;overflow:hidden;background:linear-gradient(135deg,#4f46e5 0%,#6366F1 50%,#7c3aed 100%)">
      <h2 style="font-weight:700;color:#fff;font-size:1.75rem;letter-spacing:-.02em;margin:0 0 .75rem 0">Siap memulai website-mu?</h2>
      <p style="color:rgba(255,255,255,.75);font-size:15px;line-height:1.7;max-width:30rem;margin:0 auto 2rem auto">
        Buat akun gratis, pilih paket, dan mulai online hari ini juga.
      </p>
      <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.75rem">
        <a href="{{ route('catalog.index') }}" class="btn" style="background:#fff;color:#4f46e5;padding:.6rem 1.5rem">
          <i class="fa-solid fa-server" style="font-size:12px"></i> Lihat Paket Hosting
        </a>
        <a href="{{ route('client.register') }}" class="btn" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.3);padding:.6rem 1.5rem">
          Daftar Gratis
        </a>
      </div>
    </div>
  </div>

@endsection
