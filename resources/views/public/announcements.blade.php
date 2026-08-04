@extends('public.layout')

@php
  $seoTitle       = 'Pengumuman';
  $seoDescription = 'Informasi terbaru, jadwal maintenance, dan promo layanan kami.';
@endphp

@section('content')
  <h1 class="text-2xl font-bold text-slate-800 mb-6">Pengumuman</h1>

  <div class="space-y-3">
    @forelse ($announcements as $item)
      <a href="{{ route('announcements.show', $item->slug) }}"
         class="block bg-white rounded-2xl border border-slate-200 p-6 hover:border-accent/40 transition-colors">
        <div class="flex items-center gap-2 mb-2">
          @if ($item->is_pinned)
            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Disematkan</span>
          @endif
          <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 capitalize">{{ $item->category }}</span>
          <span class="text-xs text-slate-400">{{ $item->published_at?->format('d M Y') }}</span>
        </div>
        <h2 class="text-lg font-semibold text-slate-800 mb-1">{{ $item->title }}</h2>
        @if ($item->excerpt)
          <p class="text-sm text-slate-500">{{ $item->excerpt }}</p>
        @endif
      </a>
    @empty
      <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-400">
        Belum ada pengumuman.
      </div>
    @endforelse
  </div>

  @if ($announcements->hasPages())
    <div class="mt-6">{{ $announcements->links() }}</div>
  @endif
@endsection
