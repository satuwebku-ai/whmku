@extends('client.auth.layout')
@section('title', 'Masuk')

@section('form')
  <h2 class="text-xl font-bold text-slate-800 mb-1">Masuk ke Akun Anda</h2>
  <p class="text-sm text-slate-500 mb-6">Kelola layanan hosting dan domain Anda.</p>

  @if ($errors->any())
    <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('client.login.store') }}" class="space-y-4">
    @csrf

    <div>
      <label for="email" class="form-label">Email</label>
      <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
             placeholder="email@contoh.com" class="form-input">
    </div>

    <div>
      <label for="password" class="form-label">Password</label>
      <input id="password" name="password" type="password" required placeholder="••••••••" class="form-input">
    </div>

    <div class="flex items-center justify-between">
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="remember" class="rounded border-slate-300 text-accent focus:ring-accent/40">
        Ingat saya
      </label>
      <a href="{{ route('client.password.request') }}" class="text-sm text-accent font-medium hover:underline">Lupa password?</a>
    </div>

    <button type="submit" class="w-full py-2.5 rounded-lg bg-accent text-white text-sm font-semibold hover:bg-accent-soft transition-colors shadow-[--shadow-rail]">
      Masuk
    </button>
  </form>

  <p class="text-center text-sm text-slate-500 mt-6">
    Belum punya akun?
    <a href="{{ route('client.register') }}" class="text-accent font-medium hover:underline">Daftar sekarang</a>
  </p>
@endsection
