@extends('layouts.admin')
@section('title', 'Pengaturan Halaman Depan')
@section('content')
  @include('admin.settings._nav')

  @php use App\Models\Setting; @endphp

  <div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h1 class="h4 fw-bold text-dark mb-1">Pengaturan Halaman Depan</h1>
      <p class="small text-muted mb-0">Atur berapa banyak item dan section mana saja yang tampil di beranda situs publik.</p>
    </div>
    <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
      <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px"></i> Lihat Beranda
    </a>
  </div>

  <form method="POST" action="{{ route('admin.settings.homepage.update') }}" class="card border rounded-4 p-4" style="max-width:42rem">
    @csrf

    <h2 class="small fw-bold text-dark mb-3">Jumlah Item per Section</h2>

    <div class="row g-3 mb-4">
      <div class="col-sm-4">
        <label class="form-label small fw-medium text-dark">Paket Hosting Pilihan</label>
        <input type="number" name="home_featured_limit" min="1" max="12"
               value="{{ old('home_featured_limit', Setting::get('home_featured_limit', 3)) }}" class="form-control form-control-sm">
        @error('home_featured_limit') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
      </div>
      <div class="col-sm-4">
        <label class="form-label small fw-medium text-dark">Kategori Layanan</label>
        <input type="number" name="home_categories_limit" min="0" max="24"
               value="{{ old('home_categories_limit', Setting::get('home_categories_limit', 6)) }}" class="form-control form-control-sm">
        @error('home_categories_limit') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
        <p class="text-muted mt-1 mb-0" style="font-size:10px">0 = tanpa batas</p>
      </div>
      <div class="col-sm-4">
        <label class="form-label small fw-medium text-dark">Kabar Terbaru</label>
        <input type="number" name="home_announcements_limit" min="1" max="12"
               value="{{ old('home_announcements_limit', Setting::get('home_announcements_limit', 3)) }}" class="form-control form-control-sm">
        @error('home_announcements_limit') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
      </div>
    </div>

    <h2 class="small fw-bold text-dark mb-3 pt-3 border-top">Tampilkan / Sembunyikan Section</h2>

    <div class="d-flex flex-column gap-2 mb-4">
      @php
        $sections = [
          'home_show_benefits' => ['label' => 'Keunggulan', 'desc' => '4 kartu "Aktif Otomatis", "Aman & Terjaga", dst.'],
          'home_show_featured' => ['label' => 'Paket Hosting Pilihan', 'desc' => 'Daftar paket unggulan.'],
          'home_show_categories' => ['label' => 'Layanan Kami (Kategori)', 'desc' => 'Grid kategori produk.'],
          'home_show_announcements' => ['label' => 'Kabar Terbaru', 'desc' => 'Pengumuman yang dipublikasikan.'],
        ];
      @endphp
      @foreach ($sections as $key => $section)
        <label class="d-flex align-items-center justify-content-between rounded-3 border px-3 py-2" style="cursor:pointer">
          <span>
            <span class="d-block fw-medium text-dark" style="font-size:13px">{{ $section['label'] }}</span>
            <span class="d-block text-muted" style="font-size:11px">{{ $section['desc'] }}</span>
          </span>
          <input type="checkbox" name="{{ $key }}" value="1" @checked(Setting::get($key, '1') === '1') class="form-check-input" style="margin-top:0">
        </label>
      @endforeach
    </div>

    <div class="rounded-3 p-3 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0">
      <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
        <h3 class="fw-bold text-dark mb-0" style="font-size:13px">Banner Promo di Beranda</h3>
        <a href="{{ route('admin.promo-banners.index') }}" class="text-accent text-decoration-none" style="font-size:11px">
          Kelola Banner <i class="fa-solid fa-arrow-right" style="font-size:9px"></i>
        </a>
      </div>

      @php $tampil = $banners->where('shows_on_home', true); @endphp

      @if ($banners->isEmpty())
        <p class="text-muted mb-0" style="font-size:11px">
          Belum ada banner sama sekali. Tambahkan lewat <a href="{{ route('admin.promo-banners.create') }}" class="text-accent">Konten &rarr; Banner Promo</a>.
        </p>
      @else
        @if ($tampil->isEmpty())
          <p class="mb-2" style="font-size:11px;color:#b45309">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <b>Tidak ada banner yang tampil di beranda saat ini.</b> Alasannya per banner ada di bawah.
          </p>
        @else
          <p class="mb-2" style="font-size:11px;color:#047857">
            <i class="fa-solid fa-circle-check"></i>
            {{ $tampil->count() }} banner sedang tampil di beranda.
          </p>
        @endif

        <div class="d-flex flex-column gap-1">
          @foreach ($banners as $b)
            <div class="d-flex align-items-start justify-content-between gap-2 rounded-2 px-2 py-1" style="background:#fff;border:1px solid #e2e8f0">
              <div class="min-w-0">
                <span class="fw-medium text-dark" style="font-size:12px">{{ $b['title'] }}</span>
                <span class="text-muted" style="font-size:10px"> &middot; {{ $b['page'] }}</span>
                @if ($b['reasons'])
                  <span class="d-block" style="font-size:10px;color:#b45309">{{ implode(' &middot; ', $b['reasons']) }}</span>
                @endif
              </div>
              <span class="badge flex-shrink-0" style="font-size:9px;background:{{ $b['shows_on_home'] ? '#d1fae5' : '#f1f5f9' }};color:{{ $b['shows_on_home'] ? '#047857' : '#64748b' }}">
                {{ $b['shows_on_home'] ? 'Tampil' : 'Tidak tampil' }}
              </span>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <button type="submit" class="btn btn-primary btn-sm" style="width:fit-content"><i class="fa-solid fa-check" style="font-size:11px"></i> Simpan Pengaturan</button>
  </form>
@endsection
