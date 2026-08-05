@extends('public.layout')

@php
  $seoTitle = $category->name;
  $seoDescription = $category->description ?: "Pilihan paket {$category->name} — aktif cepat, dukungan 24/7.";
@endphp

@section('content')

  <nav class="text-xs text-slate-400 mb-4">
    <a href="{{ route('catalog.index') }}" class="hover:text-accent">Hosting</a> / {{ $category->name }}
  </nav>

  <div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-800">{{ $category->name }}</h1>
    @if ($category->description)
      <p class="text-slate-500 mt-1">{{ $category->description }}</p>
    @endif
  </div>

  @if ($products->isEmpty())
    <div class="card p-10 text-center text-slate-400 text-sm">Belum ada produk di kategori ini.</div>
  @else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach ($products as $product)
        @include('public.catalog._product-card', ['product' => $product])
      @endforeach
    </div>

    @if ($products->hasPages())
      <div class="mt-8">{{ $products->links() }}</div>
    @endif
  @endif

@endsection
