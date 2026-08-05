@extends('client.auth.layout')
@section('title', 'Daftar')

@section('form')
  <h2 class="text-xl font-bold text-slate-800 mb-1">Buat Akun Baru</h2>
  <p class="text-sm text-slate-500 mb-6">Gratis, hanya butuh satu menit.</p>

  @if ($errors->any())
    <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
      <ul class="list-disc pl-4 space-y-0.5">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('client.register.store') }}" class="space-y-4">
    @csrf

    <div>
      <label class="form-label">Nama Lengkap</label>
      <input name="name" type="text" value="{{ old('name') }}" required autofocus class="form-input">
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="form-label">Email</label>
        <input name="email" type="email" value="{{ old('email') }}" required class="form-input">
      </div>
      <div>
        <label class="form-label">No. WhatsApp / Telepon</label>
        <input name="phone" type="text" value="{{ old('phone') }}" required placeholder="0812xxxxxxx" class="form-input">
      </div>
    </div>

    <div>
      <label class="form-label">Perusahaan <span class="text-slate-400 font-normal">(opsional)</span></label>
      <input name="company" type="text" value="{{ old('company') }}" class="form-input">
    </div>

    <div>
      <label class="form-label">Alamat <span class="text-slate-400 font-normal">(opsional)</span></label>
      <input name="address" type="text" value="{{ old('address') }}" class="form-input">
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="form-label">Kota</label>
        <input name="city" type="text" value="{{ old('city') }}" class="form-input">
      </div>
      <div>
        <label class="form-label">Negara</label>
        <input name="country" type="text" value="{{ old('country', 'Indonesia') }}" class="form-input">
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="form-label">Provinsi <span class="text-slate-400 font-normal">(untuk registrasi domain)</span></label>
        <input name="state" type="text" value="{{ old('state') }}" placeholder="DKI Jakarta" class="form-input">
      </div>
      <div>
        <label class="form-label">Kode Pos</label>
        <input name="postal_code" type="text" value="{{ old('postal_code') }}" class="form-input">
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="form-label">Password</label>
        <input name="password" type="password" required class="form-input">
        <p class="text-[11px] text-slate-400 mt-1">Min. 8 karakter, ada huruf & angka.</p>
      </div>
      <div>
        <label class="form-label">Ulangi Password</label>
        <input name="password_confirmation" type="password" required class="form-input">
      </div>
    </div>

    <label class="flex items-start gap-2 text-sm text-slate-600">
      <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="rounded border-slate-300 text-accent focus:ring-accent/40 mt-0.5">
      <span>
        Saya menyetujui
        <a href="{{ url('/p/syarat-ketentuan') }}" target="_blank" class="text-accent hover:underline">Syarat &amp; Ketentuan</a>
        dan
        <a href="{{ url('/p/kebijakan-privasi') }}" target="_blank" class="text-accent hover:underline">Kebijakan Privasi</a>.
      </span>
    </label>

    <button type="submit" class="w-full py-2.5 rounded-lg bg-accent text-white text-sm font-semibold hover:bg-accent-soft transition-colors shadow-[--shadow-rail]">
      Daftar
    </button>
  </form>

  <p class="text-center text-sm text-slate-500 mt-6">
    Sudah punya akun?
    <a href="{{ route('client.login') }}" class="text-accent font-medium hover:underline">Masuk di sini</a>
  </p>
@endsection
