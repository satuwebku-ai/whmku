@extends('public.layout')

@php
  $seoTitle = 'Paket Hosting & Domain';
  $seoDescription = 'Pilih paket hosting sesuai kebutuhan Anda — mulai dari shared hosting hingga VPS, lengkap dengan domain.';
@endphp

@section('content')

  @include('public._promo-banner-carousel')

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
