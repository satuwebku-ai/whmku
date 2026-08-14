@extends('public.layout')

@php
  $seoTitle = 'Paket Hosting & Domain';
  $seoDescription = 'Pilih paket hosting sesuai kebutuhan Anda — mulai dari shared hosting hingga VPS, lengkap dengan domain.';
@endphp

@section('content')

  @if ($banners->isNotEmpty())
    <div class="relative rounded-2xl overflow-hidden mb-8" id="promoBannerCarousel">
      @foreach ($banners as $i => $banner)
        <div class="promo-slide {{ $i === 0 ? '' : 'hidden' }}" data-slide="{{ $i }}">
          @if ($banner->link_url)
            <a href="{{ $banner->link_url }}" @if ($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="block relative">
          @else
            <div class="relative">
          @endif

            <img src="{{ asset('uploads/banners/' . $banner->image) }}" alt="{{ $banner->title }}" class="w-full h-48 sm:h-64 object-cover">

            @if ($banner->title || $banner->subtitle || $banner->button_text)
              <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/20 to-transparent flex items-center">
                <div class="px-6 sm:px-10 max-w-lg">
                  <h2 class="text-white text-xl sm:text-2xl font-bold mb-1">{{ $banner->title }}</h2>
                  @if ($banner->subtitle)
                    <p class="text-white/80 text-sm mb-3">{{ $banner->subtitle }}</p>
                  @endif
                  @if ($banner->button_text)
                    <span class="btn btn-primary !inline-flex">{{ $banner->button_text }}</span>
                  @endif
                </div>
              </div>
            @endif

          @if ($banner->link_url)
            </a>
          @else
            </div>
          @endif
        </div>
      @endforeach

      @if ($banners->count() > 1)
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
          @foreach ($banners as $i => $banner)
            <button type="button" class="promo-dot w-2 h-2 rounded-full {{ $i === 0 ? 'bg-white' : 'bg-white/40' }}" data-dot="{{ $i }}"></button>
          @endforeach
        </div>
      @endif
    </div>

    @if ($banners->count() > 1)
      <script>
        (function () {
          const slides = document.querySelectorAll('#promoBannerCarousel .promo-slide');
          const dots = document.querySelectorAll('#promoBannerCarousel .promo-dot');
          let current = 0;

          function show(index) {
            slides.forEach((s, i) => s.classList.toggle('hidden', i !== index));
            dots.forEach((d, i) => d.classList.toggle('bg-white', i === index));
            dots.forEach((d, i) => d.classList.toggle('bg-white/40', i !== index));
            current = index;
          }

          dots.forEach(dot => dot.addEventListener('click', () => show(parseInt(dot.dataset.dot))));

          setInterval(() => show((current + 1) % slides.length), 5000);
        })();
      </script>
    @endif
  @endif

  @if (request('dari_domain'))
    <div class="card p-4 mb-6 border-accent/30 bg-accent/5 flex items-center justify-between gap-4 flex-wrap">
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 text-xs">
          <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-[11px]">✓</span>
          <span class="text-slate-400">Domain</span>
          <span class="w-4 sm:w-8 h-px bg-slate-200"></span>
          <span class="w-6 h-6 rounded-full text-white flex items-center justify-center font-bold text-[11px]" style="background:{{ $themeColor ?? '#6366F1' }}">2</span>
          <span class="font-semibold text-slate-800">Hosting</span>
        </div>
        <p class="text-sm text-slate-600 hidden sm:block">
          Pilih paket di bawah untuk didampingkan dengan domain Anda, atau lewati kalau cuma butuh domainnya saja.
        </p>
      </div>
      <a href="{{ route('cart.index') }}" class="btn btn-outline !py-2 !px-4 shrink-0">
        Lewati — Cuma Domain Saja <i class="fa-solid fa-arrow-right text-xs"></i>
      </a>
    </div>
  @endif

  <div class="text-center mb-10 max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-slate-800 mb-3">Paket Hosting untuk Setiap Kebutuhan</h1>
    <p class="text-slate-500">Dari website pribadi sampai toko online — pilih paket yang pas, aktif dalam hitungan menit.</p>
    <div class="mt-6">
      <a href="{{ route('domain.search') }}" class="btn btn-outline">
        <i class="fa-solid fa-magnifying-glass text-xs"></i> Cek Ketersediaan Domain
      </a>
    </div>
  </div>

  @if ($featured->isNotEmpty())
    <div class="mb-12">
      <h2 class="text-lg font-bold text-slate-800 mb-4">Paket Unggulan</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ($featured as $product)
          @include('public.catalog._product-card', ['product' => $product])
        @endforeach
      </div>
    </div>
  @endif

  <div>
    <h2 class="text-lg font-bold text-slate-800 mb-4">Kategori</h2>

    @if ($categories->isEmpty())
      <div class="card p-10 text-center text-slate-400 text-sm">Katalog sedang disiapkan. Silakan cek kembali nanti.</div>
    @else
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ($categories as $category)
          <a href="{{ route('catalog.category', $category->slug) }}" class="card p-6 hover:border-accent/40 transition-colors">
            <span class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-3">
              <i class="fa-solid {{ $category->icon ?: 'fa-box' }}"></i>
            </span>
            <h3 class="font-semibold text-slate-800 mb-1">{{ $category->name }}</h3>
            @if ($category->description)
              <p class="text-sm text-slate-500 mb-2">{{ $category->description }}</p>
            @endif
            <p class="text-xs text-slate-400">{{ $category->products_count }} paket tersedia</p>
          </a>
        @endforeach
      </div>
    @endif
  </div>

@endsection
