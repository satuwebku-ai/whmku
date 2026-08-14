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
        @include('admin.nav-menus._row', ['menu' => $menu, 'indent' => false])
        @foreach ($menu->children as $child)
          @include('admin.nav-menus._row', ['menu' => $child, 'indent' => true])
        @endforeach
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
