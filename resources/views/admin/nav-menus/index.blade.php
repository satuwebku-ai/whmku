@extends('layouts.admin')

@section('title', 'Menu Navigasi')

@section('content')

  @include('admin.pages._nav')

  <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Menu Navigasi Publik</h1>
      <p class="text-sm text-slate-500 mt-1">Menu yang tampil di bagian atas situs publik, di samping logo.</p>
    </div>
    <a href="{{ route('admin.nav-menu.add.page') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Menu
    </a>
  </div>

  <div class="card overflow-hidden">
    <div class="divide-y divide-slate-100">
      @forelse ($menus as $menu)
        <div class="flex items-center gap-4 px-5 py-3.5 {{ $menu->is_active ? '' : 'opacity-50' }}">

          {{-- Naik / turun urutan --}}
          <div class="flex flex-col gap-0.5 shrink-0">
            <form method="POST" action="{{ route('admin.nav-menu.move', $menu) }}">
              @csrf
              <input type="hidden" name="direction" value="up">
              <button type="submit" class="w-6 h-5 rounded hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600" title="Naikkan">
                <i class="fa-solid fa-chevron-up text-[10px]"></i>
              </button>
            </form>
            <form method="POST" action="{{ route('admin.nav-menu.move', $menu) }}">
              @csrf
              <input type="hidden" name="direction" value="down">
              <button type="submit" class="w-6 h-5 rounded hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600" title="Turunkan">
                <i class="fa-solid fa-chevron-down text-[10px]"></i>
              </button>
            </form>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-800">{{ $menu->label }}</p>
            <p class="text-xs text-slate-400 truncate">
              @switch($menu->type)
                @case('route')
                  <i class="fa-solid fa-house text-[10px]"></i> Halaman bawaan — {{ \App\Models\NavMenu::BUILTIN_ROUTES[$menu->route_name] ?? $menu->route_name }}
                  @break
                @case('page')
                  <i class="fa-regular fa-file text-[10px]"></i> Halaman — {{ $menu->page->title ?? '(halaman terhapus)' }}
                  @break
                @default
                  <i class="fa-solid fa-link text-[10px]"></i> {{ $menu->url }}
              @endswitch
              @if ($menu->open_in_new_tab)
                <span class="text-slate-300">· tab baru</span>
              @endif
            </p>
          </div>

          @if (! $menu->resolved_url)
            <span class="badge badge-suspended shrink-0" title="Tujuannya tidak lagi ada / belum terbit">Tautan rusak</span>
          @endif

          <div class="flex items-center gap-2 shrink-0">
            <form method="POST" action="{{ route('admin.nav-menu.status') }}">
              @csrf
              <input type="hidden" name="nav_menu_id" value="{{ $menu->id }}">
              <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500"
                      title="{{ $menu->is_active ? 'Sembunyikan' : 'Tampilkan' }}">
                <i class="fa-solid {{ $menu->is_active ? 'fa-eye' : 'fa-eye-slash' }} text-xs"></i>
              </button>
            </form>
            <a href="{{ route('admin.nav-menu.edit.page', $menu) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500">
              <i class="fa-regular fa-pen-to-square text-xs"></i>
            </a>
            <form method="POST" action="{{ route('admin.nav-menu.delete', $menu) }}"
                  data-confirm="Hapus menu &quot;{{ $menu->label }}&quot;?" data-confirm-title="Hapus Menu" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
              @csrf @method('DELETE')
              <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500">
                <i class="fa-regular fa-trash-can text-xs"></i>
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="px-5 py-12 text-center">
          <p class="text-slate-500 text-sm mb-1">Belum ada menu.</p>
          <p class="text-xs text-slate-400">Situs publik akan tampil tanpa menu navigasi sampai kamu menambahkan satu.</p>
        </div>
      @endforelse
    </div>
  </div>

  <div class="card p-5 mt-5">
    <h2 class="text-sm font-semibold text-slate-800 mb-2">Tiga Jenis Tautan</h2>
    <dl class="space-y-2 text-xs text-slate-500">
      <div><b class="text-slate-700">Halaman Bawaan</b> — Hosting, Domain, Pengumuman, dsb. Sudah ada di sistem, tinggal dipilih.</div>
      <div><b class="text-slate-700">Halaman</b> — konten yang kamu buat sendiri di tab Halaman (Tentang Kami, Syarat & Ketentuan, dll).</div>
      <div><b class="text-slate-700">Tautan Bebas</b> — alamat apa saja, termasuk ke luar situs (mis. WhatsApp, blog, media sosial).</div>
    </dl>
  </div>

@endsection
