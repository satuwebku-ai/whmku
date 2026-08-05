@extends('client.layout')
@section('title', 'Profil Saya')

@section('content')
  <div class="mb-5">
    <h1 class="text-xl font-bold text-slate-800">Profil Saya</h1>
    <p class="text-sm text-slate-500 mt-1">Perbarui data akun dan password Anda.</p>
  </div>

  <div class="grid lg:grid-cols-2 gap-5">

    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-4">Data Akun</h2>
      <form method="POST" action="{{ route('client.profile.update') }}" class="space-y-4">
        @csrf

        <div>
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="name" value="{{ old('name', $client->name) }}" required class="form-input">
          @error('name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="form-label">Email</label>
          <input type="email" name="email" value="{{ old('email', $client->email) }}" required class="form-input">
          @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="form-label">No. WhatsApp / Telepon</label>
          <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" required class="form-input">
          @error('phone') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="form-label">Perusahaan</label>
          <input type="text" name="company" value="{{ old('company', $client->company) }}" class="form-input">
        </div>

        <div>
          <label class="form-label">Alamat</label>
          <input type="text" name="address" value="{{ old('address', $client->address) }}" class="form-input">
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="form-label">Kota</label>
            <input type="text" name="city" value="{{ old('city', $client->city) }}" class="form-input">
          </div>
          <div>
            <label class="form-label">Negara</label>
            <input type="text" name="country" value="{{ old('country', $client->country) }}" class="form-input">
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="form-label">Provinsi <span class="text-slate-400 font-normal">(untuk registrasi domain)</span></label>
            <input type="text" name="state" value="{{ old('state', $client->state) }}" placeholder="DKI Jakarta" class="form-input">
          </div>
          <div>
            <label class="form-label">Kode Pos</label>
            <input type="text" name="postal_code" value="{{ old('postal_code', $client->postal_code) }}" class="form-input">
          </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Perubahan</button>
      </form>
    </div>

    <div class="space-y-5">
      <div class="card p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Ganti Password</h2>
        <form method="POST" action="{{ route('client.profile.password') }}" class="space-y-4">
          @csrf

          <div>
            <label class="form-label">Password Saat Ini</label>
            <input type="password" name="current_password" required class="form-input">
            @error('current_password') <p class="form-error">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="form-label">Password Baru</label>
            <input type="password" name="password" required class="form-input">
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
            <p class="text-[11px] text-slate-400 mt-1">Min. 8 karakter, ada huruf dan angka.</p>
          </div>

          <div>
            <label class="form-label">Ulangi Password Baru</label>
            <input type="password" name="password_confirmation" required class="form-input">
          </div>

          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key text-xs"></i> Ganti Password</button>
        </form>
      </div>

      <div class="card p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Aktivitas Login Terakhir</h2>
        <dl class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Waktu</dt>
            <dd class="text-slate-700 font-medium">{{ $client->last_login_at?->format('d M Y H:i') ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Alamat IP</dt>
            <dd class="text-slate-700 font-medium">{{ $client->last_login_ip ?? '—' }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
@endsection
