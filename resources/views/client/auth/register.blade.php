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
        <a href="{{ route('page.show', 'syarat-ketentuan') }}" target="_blank" class="text-accent hover:underline">Syarat &amp; Ketentuan</a>
        dan
        <a href="{{ route('page.show', 'kebijakan-privasi') }}" target="_blank" class="text-accent hover:underline">Kebijakan Privasi</a>.
      </span>
    </label>

    @include('partials.captcha')


    <button type="submit" class="w-full py-2.5 rounded-lg bg-accent text-white text-sm font-semibold hover:bg-accent-soft transition-colors shadow-[--shadow-rail]">
      Daftar
    </button>
  </form>

  @if (config('services.google.client_id') && config('services.google.client_secret'))
    <div class="flex items-center gap-3 my-5">
      <span class="flex-1 h-px bg-slate-200"></span>
      <span class="text-xs text-slate-400">atau</span>
      <span class="flex-1 h-px bg-slate-200"></span>
    </div>

    <a href="{{ route('client.google.redirect') }}"
       class="w-full flex items-center justify-center gap-2.5 py-2.5 rounded-lg border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
      <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.9c1.7-1.57 2.7-3.88 2.7-6.62z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.9-2.26c-.8.54-1.83.86-3.06.86-2.35 0-4.34-1.59-5.05-3.72H.96v2.33A9 9 0 0 0 9 18z"/><path fill="#FBBC05" d="M3.95 10.7A5.4 5.4 0 0 1 3.67 9c0-.59.1-1.17.28-1.7V4.97H.96A9 9 0 0 0 0 9c0 1.45.35 2.83.96 4.03l2.99-2.33z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.51.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.97l2.99 2.33C4.66 5.17 6.65 3.58 9 3.58z"/></svg>
      Daftar dengan Google
    </a>
    <p class="text-[11px] text-slate-400 text-center mt-2">Langsung aktif — tidak perlu verifikasi email lagi.</p>
  @endif

  <p class="text-center text-sm text-slate-500 mt-6">
    Sudah punya akun?
    <a href="{{ route('client.login') }}" class="text-accent font-medium hover:underline">Masuk di sini</a>
  </p>
@endsection
