@extends('layouts.admin')

@section('title', $admin->exists ? 'Edit Admin' : 'Tambah Admin')

@section('content')

  <div class="mb-6">
    <a href="{{ route('admin.admins') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Manajemen Admin</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $admin->exists ? 'Edit Admin' : 'Tambah Admin' }}</h1>
  </div>

  <form method="POST" action="{{ $admin->exists ? route('admin.admin.update', $admin) : route('admin.admin.add') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="name" value="{{ old('name', $admin->name) }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Username</label>
        <input type="text" name="username" value="{{ old('username', $admin->username) }}" class="form-input" required>
        @error('username') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">Dipakai untuk login. Huruf, angka, dan tanda hubung.</p>
      </div>
    </div>

    <div>
      <label class="form-label">Email</label>
      <input type="email" name="email" value="{{ old('email', $admin->email) }}" class="form-input" required>
      @error('email') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1">Tujuan kode OTP dan notifikasi admin.</p>
    </div>

    <div>
      <label class="form-label">Peran</label>
      <select name="role" class="form-input">
        @foreach (\App\Models\Admin::ROLES as $key => $desc)
          <option value="{{ $key }}" @selected(old('role', $admin->role ?? 'admin') === $key)>{{ $desc }}</option>
        @endforeach
      </select>
      @error('role') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
      <div>
        <label class="form-label">Password {{ $admin->exists ? '(kosongkan jika tidak diganti)' : '' }}</label>
        <input type="password" name="password" class="form-input" {{ $admin->exists ? '' : 'required' }} autocomplete="new-password">
        @error('password') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">Minimal 8 karakter, mengandung huruf dan angka.</p>
      </div>
      <div>
        <label class="form-label">Ulangi Password</label>
        <input type="password" name="password_confirmation" class="form-input" {{ $admin->exists ? '' : 'required' }} autocomplete="new-password">
      </div>
    </div>

    @if (! $admin->exists || $admin->id !== auth('admin')->id())
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $admin->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
        Akun aktif (bisa login)
      </label>
    @else
      <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-slate-500">
        <i class="fa-solid fa-circle-info"></i>
        Status akun sendiri tidak bisa diubah dari sini — supaya Anda tidak mengunci diri sendiri.
      </div>
    @endif

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.admins') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
