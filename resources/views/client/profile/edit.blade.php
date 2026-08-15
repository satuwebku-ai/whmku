@extends('client.layout')
@section('title', 'Profil Saya')

@section('content')
  <div class="mb-5">
    <h1 class="text-xl font-bold text-slate-800">Profil Saya</h1>
    <p class="text-sm text-slate-500 mt-1">Perbarui data akun dan password Anda.</p>
  </div>

  @if ($client->google_id)
    <div class="card p-4 mb-5 flex items-center gap-3 border-indigo-100 bg-indigo-50/40">
      @if ($client->avatar)
        <img src="{{ $client->avatar }}" alt="{{ $client->name }}" class="w-10 h-10 rounded-full object-cover">
      @endif
      <p class="text-xs text-slate-600">
        <i class="fa-brands fa-google text-indigo-500"></i>
        Akun ini tertaut dengan Google (<b>{{ $client->email }}</b>). Anda tetap bisa mengatur password
        di bawah kalau ingin bisa masuk tanpa Google juga.
      </p>
    </div>
  @endif

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

        {{-- Preferensi notifikasi --}}
        <div class="pt-4 border-t border-slate-100">
          <h3 class="text-sm font-semibold text-slate-800 mb-1">Notifikasi</h3>
          <p class="text-xs text-slate-500 mb-3">
            Email tagihan, pembayaran, dan tiket selalu dikirim karena bagian dari layanan.
            Yang di bawah ini bisa Anda atur sendiri.
          </p>

          <div class="space-y-3">
            <div>
              <label class="form-label">Nomor WhatsApp <span class="text-slate-400 font-normal">(opsional)</span></label>
              <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $client->whatsapp_number) }}"
                     placeholder="081234567890" class="form-input">
              @error('whatsapp_number') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-start gap-3 rounded-lg border border-slate-100 px-4 py-3">
              <input type="checkbox" name="notify_whatsapp" value="1" @checked(old('notify_whatsapp', $client->notify_whatsapp))
                     class="mt-0.5 rounded border-slate-300 text-accent focus:ring-accent/40">
              <span>
                <span class="block text-sm font-medium text-slate-700">Terima notifikasi lewat WhatsApp</span>
                <span class="block text-xs text-slate-500">Tagihan dan info layanan dikirim juga ke WhatsApp. Butuh nomor di atas terisi.</span>
              </span>
            </label>

            <label class="flex items-start gap-3 rounded-lg border border-slate-100 px-4 py-3">
              <input type="checkbox" name="notify_promo" value="1" @checked(old('notify_promo', $client->notify_promo))
                     class="mt-0.5 rounded border-slate-300 text-accent focus:ring-accent/40">
              <span>
                <span class="block text-sm font-medium text-slate-700">Terima info promo dan penawaran</span>
                <span class="block text-xs text-slate-500">Hilangkan centang untuk berhenti menerima email promosi.</span>
              </span>
            </label>
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
            <div class="flex gap-2">
              <input type="password" name="password" id="pwField" required class="form-input">
              <button type="button" onclick="lumoraGeneratePassword('pwField', 'pwConfirmField', 'pwChecklist')" class="btn btn-outline !py-2 !px-3 text-xs whitespace-nowrap shrink-0">
                <i class="fa-solid fa-dice text-xs"></i> Buatkan Otomatis
              </button>
            </div>
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
            <ul id="pwChecklist" class="text-[11px] text-slate-400 mt-1.5 space-y-0.5"></ul>
          </div>

          <div>
            <label class="form-label">Ulangi Password Baru</label>
            <input type="password" name="password_confirmation" id="pwConfirmField" required class="form-input">
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

  <script>
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

    let pw = [pick(upper), pick(lower), pick(digits), pick(symbols)];
    for (let i = 0; i < 8; i++) pw.push(pick(all));
    pw = pw.sort(() => Math.random() - 0.5).join('');

    const pwField = document.getElementById(pwFieldId);
    pwField.value = pw;
    pwField.type = 'text';

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
