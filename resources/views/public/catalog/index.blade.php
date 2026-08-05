@extends('public.layout')

@php
  $seoTitle = 'Paket Hosting & Domain';
  $seoDescription = 'Pilih paket hosting sesuai kebutuhan Anda — mulai dari shared hosting hingga VPS, lengkap dengan domain.';
@endphp

@section('content')

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
