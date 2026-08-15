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
        <div class="flex gap-2">
          <input type="password" name="password" id="pwField" class="form-input" {{ $admin->exists ? '' : 'required' }} autocomplete="new-password">
          <button type="button" onclick="lumoraGeneratePassword('pwField', 'pwConfirmField', 'pwChecklist')" class="btn btn-outline !py-2 !px-3 text-xs whitespace-nowrap shrink-0">
            <i class="fa-solid fa-dice text-xs"></i> Buatkan Otomatis
          </button>
        </div>
        @error('password') <p class="form-error">{{ $message }}</p> @enderror
        <ul id="pwChecklist" class="text-[11px] text-slate-400 mt-1.5 space-y-0.5"></ul>
      </div>
      <div>
        <label class="form-label">Ulangi Password</label>
        <input type="password" name="password_confirmation" id="pwConfirmField" class="form-input" {{ $admin->exists ? '' : 'required' }} autocomplete="new-password">
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

  <script>
    /**
     * Dipakai bersama di 3 form (Admin & Akses, Profil, Hosting Account)
     * — satu-satunya tempat admin BENAR-BENAR membuat password baru
     * (bukan menempel kredensial API pihak ketiga yang tidak bisa
     * "digenerate" begitu saja).
     */
    function lumoraPasswordChecks(pw) {
      return [
        { label: 'Minimal 8 karakter', ok: pw.length >= 8 },
        { label: 'Huruf besar & kecil', ok: /[a-z]/.test(pw) && /[A-Z]/.test(pw) },
        { label: 'Mengandung angka', ok: /[0-9]/.test(pw) },
        { label: 'Mengandung simbol (!@#$dst)', ok: /[^a-zA-Z0-9]/.test(pw) },
      ];
    }

    function lumoraRenderChecklist(pw, checklistId) {
      const el = document.getElementById(checklistId);
      if (!el) return;
      el.innerHTML = lumoraPasswordChecks(pw).map(c =>
        `<li class="${c.ok ? 'text-emerald-600' : 'text-slate-400'}"><i class="fa-solid ${c.ok ? 'fa-circle-check' : 'fa-circle'} text-[9px]"></i> ${c.label}</li>`
      ).join('');
    }

    function lumoraGeneratePassword(pwFieldId, confirmFieldId, checklistId) {
      const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
      const lower = 'abcdefghijkmnpqrstuvwxyz';
      const digits = '23456789';
      const symbols = '!@#$%&*';
      const all = upper + lower + digits + symbols;

      const pick = (set) => set[Math.floor(Math.random() * set.length)];

      // Jamin keempat syarat terpenuhi, sisanya diacak dari semua jenis.
      let pw = [pick(upper), pick(lower), pick(digits), pick(symbols)];
      for (let i = 0; i < 8; i++) pw.push(pick(all));
      pw = pw.sort(() => Math.random() - 0.5).join('');

      const pwField = document.getElementById(pwFieldId);
      pwField.value = pw;
      pwField.type = 'text'; // sengaja ditampilkan sesaat, supaya admin bisa lihat/salin

      if (confirmFieldId) {
        const confirmField = document.getElementById(confirmFieldId);
        if (confirmField) confirmField.value = pw;
      }

      lumoraRenderChecklist(pw, checklistId);
    }

    document.addEventListener('DOMContentLoaded', () => {
      const pwField = document.getElementById('pwField');
      if (pwField) {
        pwField.addEventListener('input', () => lumoraRenderChecklist(pwField.value, 'pwChecklist'));
      }
    });
  </script>

@endsection
