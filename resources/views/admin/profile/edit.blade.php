@extends('layouts.admin')

@section('title', 'Profil & Keamanan')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Profil &amp; Keamanan</h1>
    <p class="text-sm text-slate-500 mt-1">Kelola data akun dan pengaturan keamanan login Anda.</p>
  </div>

  <div class="grid lg:grid-cols-2 gap-5 max-w-4xl">

    {{-- Profil --}}
    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-4">Data Akun</h2>
      <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
        @csrf
        <div>
          <label class="form-label">Username</label>
          <input type="text" value="{{ $admin->username }}" class="form-input bg-slate-50" disabled>
          <p class="text-[11px] text-slate-400 mt-1">Username tidak bisa diubah sendiri. Hubungi superadmin bila perlu.</p>
        </div>
        <div>
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="name" value="{{ old('name', $admin->name) }}" class="form-input" required>
          @error('name') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="form-label">Email</label>
          <input type="email" name="email" value="{{ old('email', $admin->email) }}" class="form-input" required>
          @error('email') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">Kode verifikasi 2FA dikirim ke alamat ini.</p>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Profil</button>
      </form>
    </div>

    {{-- Ganti password --}}
    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-4">Ganti Password</h2>
      <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
        @csrf
        <div>
          <label class="form-label">Password Saat Ini</label>
          <input type="password" name="current_password" class="form-input" required>
          @error('current_password') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="form-label">Password Baru</label>
          <input type="password" name="password" class="form-input" required>
          @error('password') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">Minimal 8 karakter, mengandung huruf dan angka.</p>
        </div>
        <div>
          <label class="form-label">Konfirmasi Password Baru</label>
          <input type="password" name="password_confirmation" class="form-input" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key text-xs"></i> Ganti Password</button>
      </form>
    </div>

    {{-- 2FA --}}
    <div class="card p-6 lg:col-span-2">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div class="flex-1 min-w-[280px]">
          <div class="flex items-center gap-2 mb-1">
            <h2 class="text-sm font-semibold text-slate-800">Verifikasi Dua Langkah (2FA)</h2>
            <span class="badge {{ $admin->two_factor_enabled ? 'badge-active' : 'badge-inactive' }}">
              {{ $admin->two_factor_enabled ? 'Aktif' : 'Nonaktif' }}
            </span>
          </div>
          <p class="text-sm text-slate-500 leading-relaxed">
            Saat aktif, setiap login akan meminta kode 6 digit yang dikirim ke email
            <b>{{ $admin->email }}</b>. Ini melindungi akun Anda meski password bocor.
          </p>
          <p class="text-xs text-amber-600 mt-2">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Pastikan pengaturan email server (SMTP) sudah benar sebelum mengaktifkan —
            kalau email tidak terkirim, Anda tidak akan bisa login.
          </p>
        </div>

        <form method="POST" action="{{ route('admin.profile.two-factor') }}" class="w-full sm:w-auto sm:min-w-[240px] space-y-2">
          @csrf
          @if ($admin->two_factor_enabled)
            <input type="password" name="current_password" class="form-input" placeholder="Password untuk menonaktifkan" required>
            @error('current_password') <p class="form-error">{{ $message }}</p> @enderror
            <button type="submit" class="w-full btn btn-danger-soft"><i class="fa-solid fa-shield-halved text-xs"></i> Nonaktifkan 2FA</button>
          @else
            <button type="submit" class="w-full btn btn-primary"><i class="fa-solid fa-shield-halved text-xs"></i> Aktifkan 2FA</button>
          @endif
        </form>
      </div>
    </div>

    {{-- Info login terakhir --}}
    <div class="card p-6 lg:col-span-2">
      <h2 class="text-sm font-semibold text-slate-800 mb-3">Aktivitas Login Terakhir</h2>
      <dl class="grid sm:grid-cols-2 gap-4 text-sm">
        <div>
          <dt class="text-slate-400 text-xs mb-0.5">Waktu</dt>
          <dd class="text-slate-700 font-medium">{{ $admin->last_login_at?->format('d M Y H:i') ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-slate-400 text-xs mb-0.5">Alamat IP</dt>
          <dd class="text-slate-700 font-medium">{{ $admin->last_login_ip ?? '—' }}</dd>
        </div>
      </dl>
    </div>
  </div>

@endsection
