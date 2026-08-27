@extends('layouts.admin')
@section('title', 'cPanel Aplikasi Ini')

@section('content')

  @include('admin.settings._nav')

  <div class="mb-4">
    <h1 class="h4 fw-bold text-dark mb-1">cPanel Aplikasi Ini</h1>
    <p class="small text-muted mb-0">
      Login sekali klik ke cPanel server tempat Lumora sendiri berjalan — untuk cek file, log,
      database, dst. Beda dari SSO klien (itu untuk login ke cPanel LAYANAN klien, bukan aplikasi ini).
    </p>
  </div>

  @if ($servers->isEmpty())
    <div class="card border rounded-4 p-5 text-center" style="max-width:36rem">
      <i class="fa-solid fa-server text-muted mb-3" style="font-size:1.75rem"></i>
      <p class="fw-medium text-dark mb-1">Belum ada server cPanel terdaftar</p>
      <p class="text-muted mb-3" style="font-size:14px">
        Tambahkan dulu server tempat aplikasi ini di-hosting lewat menu Server, lengkap dengan API Token WHM-nya.
      </p>
      <a href="{{ route('admin.servers.create') }}" class="btn btn-primary btn-sm mx-auto" style="width:fit-content">Tambah Server</a>
    </div>
  @else
    <form method="POST" action="{{ route('admin.self-cpanel.update') }}" class="card border rounded-4 p-4 mb-3" style="max-width:36rem">
      @csrf
      <div class="mb-3">
        <label class="form-label small fw-medium text-dark">Server</label>
        <select name="self_cpanel_server_id" class="form-select form-select-sm">
          @foreach ($servers as $srv)
            <option value="{{ $srv->id }}" @selected(old('self_cpanel_server_id', \App\Models\Setting::get('self_cpanel_server_id')) == $srv->id)>
              {{ $srv->name }} ({{ $srv->hostname }})
            </option>
          @endforeach
        </select>
        <p class="text-muted mt-1 mb-0" style="font-size:11px">
          Pilih server yang API Token WHM-nya punya akses ke akun cPanel aplikasi ini
          (biasanya server yang sama dengan tempat Lumora ini sendiri di-upload).
        </p>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-medium text-dark">Username cPanel Aplikasi Ini</label>
        <input type="text" name="self_cpanel_username" value="{{ old('self_cpanel_username', \App\Models\Setting::get('self_cpanel_username')) }}"
               class="form-control form-control-sm" placeholder="mis. satuclou">
        <p class="text-muted mt-1 mb-0" style="font-size:11px">
          Username cPanel yang dipakai untuk hosting Lumora sendiri — bisa dilihat dari prompt SSH
          (mis. <code>satuclou@agile</code> berarti username-nya <code>satuclou</code>) atau di cPanel → Account Information.
        </p>
      </div>

      <button type="submit" class="btn btn-primary btn-sm" style="width:fit-content">Simpan</button>
    </form>

    @if (\App\Models\Setting::get('self_cpanel_server_id') && \App\Models\Setting::get('self_cpanel_username'))
      <a href="{{ route('admin.self-cpanel.login') }}" target="_blank" rel="noopener" class="btn btn-theme btn-sm" style="max-width:36rem">
        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px"></i>
        Buka cPanel Aplikasi Ini
      </a>
      <p class="text-muted mt-2 mb-0" style="font-size:11px;max-width:36rem">
        Tautan yang dibuat berlaku sekali pakai dan kedaluwarsa beberapa menit setelah dibuka — sama seperti login otomatis ke cPanel klien.
      </p>
    @endif
  @endif

@endsection
