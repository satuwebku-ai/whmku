@extends('layouts.admin')

@section('title', $registrar->exists ? 'Edit Registrar' : 'Tambah Registrar')

@section('content')

  @include('admin.domains._nav')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $registrar->exists ? 'Edit Registrar' : 'Tambah Registrar' }}</h1>
    <p class="text-sm text-slate-500 mt-1">API Key dienkripsi otomatis di database.</p>
  </div>

  {{-- Panduan per provider, tampil sesuai pilihan --}}
  <div id="hint-namecheap" class="provider-hint max-w-2xl rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 text-xs text-indigo-700 mb-4">
    <i class="fa-solid fa-circle-info"></i>
    <b>Namecheap:</b> aktifkan API Access di <b>Profile » Tools » Namecheap API Access</b>,
    lalu whitelist IP server kamu di halaman yang sama. IP itu yang diisi di field <b>Client IP</b>.
  </div>

  <div id="hint-liquid" class="provider-hint hidden max-w-2xl rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 text-xs text-indigo-700 mb-4">
    <i class="fa-solid fa-circle-info"></i>
    <b>Liqu.id:</b> isi <b>Reseller ID</b> dan <b>API Key</b> dari reseller control panel
    (Autentikasi memakai HTTP Basic Auth). API URL boleh dikosongkan — otomatis memakai
    <code>api.liqu.id/v1</code> (produksi) atau <code>api.domainsas.com/v1</code> (sandbox)
    sesuai centang Mode Sandbox. Rate limit ±100 request / 15 menit.
  </div>

  <div id="hint-resellbiz" class="provider-hint hidden max-w-2xl rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-xs text-amber-800 mb-4">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <b>ResellBiz / UK2Group:</b> integrasi belum diimplementasikan. Struktur sudah
    disiapkan, tinggal diisi begitu dokumentasi API tersedia.
  </div>

  <form method="POST" action="{{ $registrar->exists ? route('admin.registrars.update', $registrar) : route('admin.registrars.store') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    @if ($registrar->exists) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama / Label</label>
        <input type="text" name="name" value="{{ old('name', $registrar->name) }}" placeholder="Namecheap - Utama" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Provider</label>
        <select name="provider" id="providerSelect" class="form-input">
          <option value="namecheap" @selected(old('provider', $registrar->provider ?? 'namecheap') === 'namecheap')>Namecheap</option>
          <option value="liquid" @selected(old('provider', $registrar->provider) === 'liquid')>Liqu.id</option>
          <option value="resellbiz" @selected(old('provider', $registrar->provider) === 'resellbiz')>ResellBiz / UK2Group (segera)</option>
        </select>
      </div>
    </div>

    {{-- API URL — hanya relevan untuk provider selain Namecheap --}}
    <div id="fieldApiUrl" class="hidden">
      <label class="form-label">API URL <span class="text-slate-400 font-normal">(opsional)</span></label>
      <input type="url" name="api_url" value="{{ old('api_url', $registrar->api_url) }}" placeholder="https://api.liqu.id/v1" class="form-input">
      @error('api_url') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1">Kosongkan untuk memakai endpoint default sesuai mode sandbox/produksi. Isi hanya kalau instance Liqu.id kamu memakai domain sendiri.</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label"><span id="labelApiUser">API User</span></label>
        <input type="text" name="api_username" value="{{ old('api_username', $registrar->api_username) }}" class="form-input" required>
        @error('api_username') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">API Key {{ $registrar->exists ? '(kosongkan jika tidak diganti)' : '' }}</label>
        <input type="password" name="api_key" placeholder="{{ $registrar->exists ? '••••••••••••' : '' }}" class="form-input" {{ $registrar->exists ? '' : 'required' }}>
        @error('api_key') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    {{-- Field khusus Namecheap --}}
    <div id="fieldsNamecheap" class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">UserName (opsional, default = API User)</label>
        <input type="text" name="username" value="{{ old('username', $registrar->username) }}" class="form-input">
      </div>
      <div>
        <label class="form-label">Client IP (wajib di-whitelist di Namecheap)</label>
        <input type="text" name="client_ip" value="{{ old('client_ip', $registrar->client_ip) }}" placeholder="203.0.113.10" class="form-input">
        @error('client_ip') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nameserver Default 1</label>
        <input type="text" name="default_ns1" value="{{ old('default_ns1', $registrar->default_ns1) }}" placeholder="ns1.dyna-ns.net" class="form-input">
        @error('default_ns1') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Nameserver Default 2</label>
        <input type="text" name="default_ns2" value="{{ old('default_ns2', $registrar->default_ns2) }}" placeholder="ns2.dyna-ns.net" class="form-input">
        @error('default_ns2') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <p class="text-[11px] text-slate-400 sm:col-span-2 -mt-2">
        Dipakai otomatis saat domain baru didaftarkan — cek di dashboard registrar-mu (biasanya menu "Default Nameserver" di Settings). Kosongkan kalau tidak mau ada default sama sekali.
      </p>
    </div>

    <div class="flex items-center gap-6 flex-wrap">
      <label id="labelSandbox" class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="sandbox" value="1" @checked(old('sandbox', $registrar->sandbox ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
        Mode Sandbox (testing)
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $registrar->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
        Aktif
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $registrar->is_default)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
        Jadikan Default
      </label>
    </div>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.registrars.index') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

  <script>
    (function () {
      const select        = document.getElementById('providerSelect');
      const fieldApiUrl   = document.getElementById('fieldApiUrl');
      const fieldsNc      = document.getElementById('fieldsNamecheap');
      const labelApiUser  = document.getElementById('labelApiUser');
      const labelSandbox  = document.getElementById('labelSandbox');

      function sync() {
        const provider = select.value;
        const isNamecheap = provider === 'namecheap';

        // Label kredensial berbeda: Namecheap pakai "API User",
        // Liqu.id pakai "Reseller ID".
        labelApiUser.textContent = provider === 'liquid' ? 'Reseller ID' : 'API User';

        // API URL hanya relevan untuk provider non-Namecheap (opsional).
        fieldApiUrl.classList.toggle('hidden', isNamecheap);

        // UserName & Client IP khusus Namecheap.
        fieldsNc.classList.toggle('hidden', !isNamecheap);

        // Sandbox berlaku untuk Namecheap DAN Liqu.id (keduanya punya
        // environment demo), tapi tidak untuk ResellBiz.
        labelSandbox.classList.toggle('hidden', provider === 'resellbiz');

        document.querySelectorAll('.provider-hint').forEach(el => el.classList.add('hidden'));
        document.getElementById('hint-' + provider)?.classList.remove('hidden');
      }

      select.addEventListener('change', sync);
      sync();
    })();
  </script>

@endsection
