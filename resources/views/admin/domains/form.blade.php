@extends('layouts.admin')

@section('title', $domain->exists ? 'Edit Domain' : 'Tambah Domain')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $domain->exists ? 'Edit Domain' : 'Tambah Domain' }}</h1>
    <p class="text-sm text-slate-500 mt-1">
      @if ($domain->exists && $domain->provision_message)
        Status registrasi terakhir:
        <span class="{{ $domain->provision_status === 'registered' ? 'text-emerald-600' : ($domain->provision_status === 'failed' ? 'text-rose-600' : 'text-slate-500') }} font-medium">
          {{ $domain->provision_message }}
        </span>
      @else
        Centang "Registrasi Otomatis" untuk langsung mendaftarkan domain lewat registrar.
      @endif
    </p>
  </div>

  <form method="POST" action="{{ $domain->exists ? route('admin.domain.update', $domain) : route('admin.domain.add') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    @if ($domain->exists) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Klien</label>
        <select name="client_id" class="form-input" required>
          <option value="">Pilih klien</option>
          @foreach ($clients as $client)
            <option value="{{ $client->id }}" @selected(old('client_id', $domain->client_id) == $client->id)>{{ $client->name }}</option>
          @endforeach
        </select>
        @error('client_id') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Nama Domain</label>
        <input type="text" name="domain_name" value="{{ old('domain_name', $domain->domain_name ?? request('domain')) }}" placeholder="contoh.com" class="form-input" required>
        @error('domain_name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="form-label">Registrar (opsional)</label>
        <select name="registrar_id" class="form-input">
          <option value="">— Manual —</option>
          @foreach ($registrars as $r)
            <option value="{{ $r->id }}" @selected(old('registrar_id', $domain->registrar_id) == $r->id)>{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">TLD (opsional, untuk harga)</label>
        <select name="tld_id" class="form-input">
          <option value="">—</option>
          @foreach ($tlds as $t)
            <option value="{{ $t->id }}" @selected(old('tld_id', $domain->tld_id) == $t->id)>{{ $t->extension }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Lama (tahun)</label>
        <input type="number" name="years" min="1" max="10" value="{{ old('years', $domain->years ?? 1) }}" class="form-input" required>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Harga</label>
        <input type="number" step="0.01" name="price" value="{{ old('price', $domain->price) }}" class="form-input" required>
        @error('price') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Jatuh Tempo (kalau sudah tahu)</label>
        <input type="date" name="expiry_date" value="{{ old('expiry_date', optional($domain->expiry_date)->format('Y-m-d')) }}" class="form-input">
      </div>
    </div>

    <div class="flex items-center gap-6">
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="auto_renew" value="1" @checked(old('auto_renew', $domain->auto_renew ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
        Auto Renew
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="whois_privacy" value="1" @checked(old('whois_privacy', $domain->whois_privacy)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
        WHOIS Privacy
      </label>
    </div>

    <div>
      <label class="form-label">Status</label>
      <select name="status" class="form-input">
        <option value="pending" @selected(old('status', $domain->status) === 'pending')>Pending</option>
        <option value="active" @selected(old('status', $domain->status) === 'active')>Aktif</option>
        <option value="expired" @selected(old('status', $domain->status) === 'expired')>Expired</option>
        <option value="cancelled" @selected(old('status', $domain->status) === 'cancelled')>Cancelled</option>
      </select>
    </div>

    @unless ($domain->exists)
      <div class="rounded-lg border border-dashed border-indigo-200 bg-indigo-50/50 p-4 space-y-4">
        <label class="flex items-center gap-2 text-sm font-semibold text-indigo-700">
          <input type="checkbox" name="register_now" value="1" id="registerNow" @checked(old('register_now')) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Registrasi Otomatis lewat Registrar Sekarang
        </label>

        <p class="text-xs text-slate-500">Wajib diisi kalau opsi di atas dicentang — Namecheap butuh data kontak WHOIS lengkap.</p>

        <div class="grid sm:grid-cols-2 gap-3">
          <input type="text" name="contact_first_name" value="{{ old('contact_first_name') }}" placeholder="Nama Depan" class="form-input">
          <input type="text" name="contact_last_name" value="{{ old('contact_last_name') }}" placeholder="Nama Belakang" class="form-input">
        </div>
        <input type="text" name="contact_address" value="{{ old('contact_address') }}" placeholder="Alamat" class="form-input">
        <div class="grid sm:grid-cols-3 gap-3">
          <input type="text" name="contact_city" value="{{ old('contact_city') }}" placeholder="Kota" class="form-input">
          <input type="text" name="contact_state" value="{{ old('contact_state') }}" placeholder="Provinsi" class="form-input">
          <input type="text" name="contact_postal_code" value="{{ old('contact_postal_code') }}" placeholder="Kode Pos" class="form-input">
        </div>
        <div class="grid sm:grid-cols-3 gap-3">
          <input type="text" name="contact_country" value="{{ old('contact_country', 'ID') }}" maxlength="2" placeholder="Kode Negara (ID)" class="form-input">
          <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" placeholder="+62.8123456789" class="form-input">
          <input type="email" name="contact_email" value="{{ old('contact_email') }}" placeholder="email@contoh.com" class="form-input">
        </div>
      </div>
    @endunless

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.domains') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
