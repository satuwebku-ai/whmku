@extends('layouts.admin')

@section('title', 'Banner Promo')

@section('content')

  @include('admin.pages._nav')

  <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Banner Promo</h1>
      <p class="text-sm text-slate-500 mt-1">Tampil di halaman utama situs publik — bisa lebih dari satu, bergantian.</p>
    </div>
    <a href="{{ route('admin.promo-banners.create') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Banner
    </a>
  </div>

  <div class="card overflow-hidden">
    <div class="divide-y divide-slate-100">
      @forelse ($banners as $banner)
        <div class="flex items-center gap-4 px-5 py-3.5 {{ $banner->is_active ? '' : 'opacity-50' }}">

          <div class="flex flex-col gap-0.5 shrink-0">
            <form method="POST" action="{{ route('admin.promo-banners.move', $banner) }}">
              @csrf
              <input type="hidden" name="direction" value="up">
              <button type="submit" class="w-6 h-5 rounded hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600" title="Naikkan">
                <i class="fa-solid fa-chevron-up text-[10px]"></i>
              </button>
            </form>
            <form method="POST" action="{{ route('admin.promo-banners.move', $banner) }}">
              @csrf
              <input type="hidden" name="direction" value="down">
              <button type="submit" class="w-6 h-5 rounded hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600" title="Turunkan">
                <i class="fa-solid fa-chevron-down text-[10px]"></i>
              </button>
            </form>
          </div>

          <img src="{{ asset('uploads/banners/' . $banner->image) }}" alt="{{ $banner->title }}" class="w-24 h-14 object-cover rounded-lg border border-slate-100 shrink-0">

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-800">{{ $banner->title }}</p>
            <p class="text-xs text-slate-400 truncate">
              {{ $banner->subtitle ?: '—' }}
              @if ($banner->link_url)
                · <i class="fa-solid fa-link text-[9px]"></i> {{ $banner->link_url }}
              @endif
            </p>
            @if ($banner->starts_at || $banner->ends_at)
              <p class="text-[11px] text-slate-400 mt-0.5">
                <i class="fa-regular fa-calendar text-[9px]"></i>
                Tayang: {{ $banner->starts_at?->format('d M Y') ?? 'sekarang' }} — {{ $banner->ends_at?->format('d M Y') ?? 'tanpa batas' }}
              </p>
            @endif
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <form method="POST" action="{{ route('admin.promo-banners.status') }}">
              @csrf
              <input type="hidden" name="banner_id" value="{{ $banner->id }}">
              <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500"
                      title="{{ $banner->is_active ? 'Sembunyikan' : 'Tampilkan' }}">
                <i class="fa-solid {{ $banner->is_active ? 'fa-eye' : 'fa-eye-slash' }} text-xs"></i>
              </button>
            </form>
            <a href="{{ route('admin.promo-banners.edit', $banner) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500">
              <i class="fa-regular fa-pen-to-square text-xs"></i>
            </a>
            <form method="POST" action="{{ route('admin.promo-banners.destroy', $banner) }}"
                  data-confirm="Hapus banner &quot;{{ $banner->title }}&quot;?" data-confirm-title="Hapus Banner" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
              @csrf @method('DELETE')
              <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500">
                <i class="fa-regular fa-trash-can text-xs"></i>
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="px-5 py-12 text-center">
          <p class="text-slate-500 text-sm mb-1">Belum ada banner promo.</p>
          <p class="text-xs text-slate-400">Halaman utama situs publik akan tampil tanpa banner sampai kamu menambahkan satu.</p>
        </div>
      @endforelse
    </div>
  </div>

@endsection
