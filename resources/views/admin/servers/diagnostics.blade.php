@extends('layouts.admin')

@section('title', 'Diagnosa Server — ' . $server->name)

@section('content')

  <a href="{{ route('admin.servers.index') }}" class="text-xs text-slate-400 hover:text-slate-600">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Server
  </a>
  <h1 class="text-xl font-bold text-slate-800 mt-1 mb-2">Diagnosa — {{ $server->name }}</h1>
  <p class="text-sm text-slate-500 mb-6">
    Data langsung dari {{ ucfirst($server->panel) }} — cuma membaca, tidak mengubah apa pun di server.
  </p>

  @if ($apiError)
    <div class="card p-4 mb-5 border-rose-200 bg-rose-50/60 text-sm text-rose-800">
      <i class="fa-solid fa-circle-exclamation"></i> {{ $apiError }}
    </div>
  @endif

  {{-- Cocokkan produk vs paket sungguhan — ini yang paling penting --}}
  <div class="card overflow-hidden mb-5">
    <div class="px-5 py-3 border-b border-slate-100">
      <h2 class="text-sm font-semibold text-slate-800">Produk yang Terhubung ke Server Ini</h2>
      <p class="text-xs text-slate-400 mt-0.5">Nama paket di kolom kanan harus PERSIS sama dengan yang ada di WHM (besar-kecil huruf ikut berpengaruh).</p>
    </div>
    <div class="divide-y divide-slate-100">
      @forelse ($products as $p)
        <div class="flex items-center justify-between px-5 py-3">
          <p class="text-sm text-slate-700">{{ $p['name'] }}</p>
          <div class="flex items-center gap-2">
            <code class="text-xs bg-slate-100 px-2 py-1 rounded">{{ $p['panel_package'] ?: '(kosong — mode manual)' }}</code>
            @if (is_null($p['matches']))
              <span class="badge badge-inactive">Manual</span>
            @elseif ($p['matches'])
              <span class="badge badge-active"><i class="fa-solid fa-check text-[10px]"></i> Cocok</span>
            @else
              <span class="badge badge-inactive !bg-rose-100 !text-rose-700"><i class="fa-solid fa-xmark text-[10px]"></i> Tidak ditemukan di server</span>
            @endif
          </div>
        </div>
      @empty
        <p class="px-5 py-8 text-center text-slate-400 text-sm">Belum ada produk yang terhubung ke server ini.</p>
      @endforelse
    </div>
  </div>

  {{-- Daftar paket sungguhan di server, untuk referensi --}}
  <div class="card p-5">
    <h2 class="text-sm font-semibold text-slate-800 mb-3">Semua Paket di Server Ini ({{ count($packages) }})</h2>
    @if (count($packages))
      <div class="flex flex-wrap gap-2">
        @foreach ($packages as $pkg)
          <code class="text-xs bg-slate-100 px-2 py-1 rounded">{{ $pkg }}</code>
        @endforeach
      </div>
    @else
      <p class="text-slate-300 text-sm">Tidak bisa diambil — lihat pesan galat di atas.</p>
    @endif
  </div>

@endsection
