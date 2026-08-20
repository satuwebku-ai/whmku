@extends('layouts.admin-bootstrap')

@section('title', $server->exists ? 'Edit Server' : 'Tambah Server')

@section('content')

  @php $selectStyle = 'padding:.25rem .6rem;font-size:.875rem;border-radius:.375rem'; @endphp

  <div class="mb-3">
    <h1 class="h4 fw-bold text-dark mb-1">{{ $server->exists ? 'Edit Server' : 'Tambah Server' }}</h1>
    <p class="small text-muted mb-0">Kredensial API dienkripsi otomatis di database (pakai APP_KEY).</p>
  </div>

  <div class="rounded-3 mb-3 px-3 py-2 small" style="max-width:42rem;background:#eef2ff;border:1px solid #c7d2fe;color:#4338ca">
    <i class="fa-solid fa-circle-info"></i>
    Untuk cPanel/WHM, buat API Token dari <b>WHM » Development » Manage API Tokens</b> —
    jangan pakai password root langsung. Port default WHM adalah <b>2087</b>.
  </div>

  <form method="POST" action="{{ $server->exists ? route('admin.servers.update', $server) : route('admin.servers.store') }}" class="card border rounded-4 p-4" style="max-width:42rem">
    @csrf
    @if ($server->exists) @method('PUT') @endif

    <div class="row g-3 mb-3">
      <div class="col-sm-6">
        <label class="form-label small fw-medium text-dark">Nama / Label Server</label>
        <input type="text" name="name" value="{{ old('name', $server->name) }}" placeholder="Server JKT-01" class="form-control form-control-sm" required>
        @error('name') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
      </div>
      <div class="col-sm-6">
        <label class="form-label small fw-medium text-dark">Jenis Panel</label>
        <select name="panel" class="form-select" style="{{ $selectStyle }}">
          <option value="cpanel" @selected(old('panel', $server->panel ?? 'cpanel') === 'cpanel')>cPanel / WHM</option>
          <option value="directadmin" @selected(old('panel', $server->panel) === 'directadmin')>DirectAdmin (segera)</option>
          <option value="plesk" @selected(old('panel', $server->panel) === 'plesk')>Plesk (segera)</option>
        </select>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-sm-8">
        <label class="form-label small fw-medium text-dark">Hostname / IP</label>
        <input type="text" name="hostname" value="{{ old('hostname', $server->hostname) }}" placeholder="server1.contoh.com" class="form-control form-control-sm" required>
        @error('hostname') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
      </div>
      <div class="col-sm-4">
        <label class="form-label small fw-medium text-dark">Port</label>
        <input type="number" name="port" value="{{ old('port', $server->port ?? 2087) }}" class="form-control form-control-sm" required>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-sm-6">
        <label class="form-label small fw-medium text-dark">Nameserver 1</label>
        <input type="text" name="ns1" value="{{ old('ns1', $server->ns1) }}" placeholder="ns1.satucloudhosting.com" class="form-control form-control-sm">
        @error('ns1') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
      </div>
      <div class="col-sm-6">
        <label class="form-label small fw-medium text-dark">Nameserver 2</label>
        <input type="text" name="ns2" value="{{ old('ns2', $server->ns2) }}" placeholder="ns2.satucloudhosting.com" class="form-control form-control-sm">
        @error('ns2') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
      </div>
      <p class="text-muted mb-0" style="font-size:11px">
        Kalau diisi, domain otomatis diarahkan ke nameserver ini begitu klien membeli hosting di server ini untuk domain yang sudah terdaftar lewat sistem kita.
      </p>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-sm-6">
        <label class="form-label small fw-medium text-dark">API Username</label>
        <input type="text" name="api_username" value="{{ old('api_username', $server->api_username) }}" placeholder="root" class="form-control form-control-sm" required>
        @error('api_username') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
      </div>
      <div class="col-sm-6">
        <label class="form-label small fw-medium text-dark">API Token {{ $server->exists ? '(kosongkan jika tidak diganti)' : '' }}</label>
        <input type="password" name="api_token" placeholder="{{ $server->exists ? '••••••••••••' : '' }}" class="form-control form-control-sm" {{ $server->exists ? '' : 'required' }}>
        @error('api_token') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="row g-3 mb-3 align-items-center">
      <div class="col-sm-6">
        <label class="form-label small fw-medium text-dark">Kapasitas Maks. Akun (opsional)</label>
        <input type="number" name="max_accounts" value="{{ old('max_accounts', $server->max_accounts) }}" class="form-control form-control-sm">
      </div>
      <div class="col-sm-6 d-flex align-items-center gap-4">
        <label class="d-flex align-items-center gap-2 small text-dark mb-0">
          <input type="checkbox" name="verify_ssl" value="1" @checked(old('verify_ssl', $server->verify_ssl ?? true)) class="form-check-input" style="margin-top:0">
          Verifikasi SSL
        </label>
        <label class="d-flex align-items-center gap-2 small text-dark mb-0">
          <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $server->is_active ?? true)) class="form-check-input" style="margin-top:0">
          Aktif
        </label>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 pt-2">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check" style="font-size:11px"></i> Simpan</button>
      <a href="{{ route('admin.servers.index.bootstrap-preview') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
    </div>
  </form>

@endsection
