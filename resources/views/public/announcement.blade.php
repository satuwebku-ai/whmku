@extends('public.layout')

@php
  $seoTitle       = $announcement->seo_title;
  $seoDescription = $announcement->seo_description;
@endphp

@section('content')
  <a href="{{ route('announcements.index') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Pengumuman</a>

  <article class="bg-white rounded-2xl border border-slate-200 p-8 mt-3">
    <div class="flex items-center gap-2 mb-3">
      <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 capitalize">{{ $announcement->category }}</span>
      <span class="text-xs text-slate-400">{{ $announcement->published_at?->format('d M Y H:i') }}</span>
    </div>

    <h1 class="text-2xl font-bold text-slate-800 mb-6">{{ $announcement->title }}</h1>

    <div class="prose-content">
      {!! $announcement->content !!}
    </div>
  </article>
@endsection
