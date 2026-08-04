@extends('public.layout')

@php
  $seoTitle       = $page->seo_title;
  $seoDescription = $page->seo_description;
  $seoKeywords    = $page->meta_keywords;
  $seoImage       = $page->og_image;
  $seoNoindex     = $page->noindex;
@endphp

@section('content')
  <article class="bg-white rounded-2xl border border-slate-200 p-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ $page->title }}</h1>
    <p class="text-xs text-slate-400 mb-6">Diperbarui {{ $page->updated_at->format('d M Y') }}</p>

    <div class="prose-content">
      {!! $page->content !!}
    </div>
  </article>
@endsection
