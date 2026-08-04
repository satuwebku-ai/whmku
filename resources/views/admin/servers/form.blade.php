@extends('layouts.admin')

@section('title', $server->exists ? 'Edit Server' : 'Tambah Server')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $server->exists ? 'Edit Server' : 'Tambah Server' }}</h1>
    <p class="text-sm text-slate-500 mt-1">Kredensial API dienkripsi otomatis di database (pakai APP_KEY).</p>
  </div>

  <div class="max-w-2xl rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 text-xs text-indigo-700 mb-4">
    <i class="fa-solid fa-circle-info"></i>
    Untuk cPanel/WHM, buat API Token dari <b>WHM » Development » Manage API Tokens</b> —
    jangan pakai password root langsung. Port default WHM adalah <b>2087</b>.
  </div>

  <form method="POST" action="{{ $server->exists ? route('admin.servers.update', $server) : route('admin.servers.store') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    @if ($server->exists) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama / Label Server</label>
        <input type="text" name="name" value="{{ old('name', $server->name) }}" placeholder="Server JKT-01" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Jenis Panel</label>
        <select name="panel" class="form-input">
          <option value="cpanel" @selected(old('panel', $server->panel ?? 'cpanel') === 'cpanel')>cPanel / WHM</option>
          <option value="directadmin" @selected(old('panel', $server->panel) === 'directadmin')>DirectAdmin (segera)</option>
          <option value="plesk" @selected(old('panel', $server->panel) === 'plesk')>Plesk (segera)</option>
        </select>
      </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div class="sm:col-span-2">
        <label class="form-label">Hostname / IP</label>
        <input type="text" name="hostname" value="{{ old('hostname', $server->hostname) }}" placeholder="server1.contoh.com" class="form-input" required>
        @error('hostname') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Port</label>
        <input type="number" name="port" value="{{ old('port', $server->port ?? 2087) }}" class="form-input" required>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">API Username</label>
        <input type="text" name="api_username" value="{{ old('api_username', $server->api_username) }}" placeholder="root" class="form-input" required>
        @error('api_username') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">API Token {{ $server->exists ? '(kosongkan jika tidak diganti)' : '' }}</label>
        <input type="password" name="api_token" placeholder="{{ $server->exists ? '••••••••••••' : '' }}" class="form-input" {{ $server->exists ? '' : 'required' }}>
        @error('api_token') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Kapasitas Maks. Akun (opsional)</label>
        <input type="number" name="max_accounts" value="{{ old('max_accounts', $server->max_accounts) }}" class="form-input">
      </div>
      <div class="flex items-center gap-6 pt-6">
        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="verify_ssl" value="1" @checked(old('verify_ssl', $server->verify_ssl ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Verifikasi SSL
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $server->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Aktif
        </label>
      </div>
    </div>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.servers.index') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
