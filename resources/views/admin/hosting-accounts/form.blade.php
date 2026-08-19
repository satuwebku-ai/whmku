@extends('layouts.admin')

@section('title', $account->exists ? 'Edit Hosting Account' : 'Buat Hosting Account')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $account->exists ? 'Edit Hosting Account' : 'Buat Hosting Account' }}</h1>
    <p class="text-sm text-slate-500 mt-1">
      @if ($account->exists && $account->provision_message)
        Status provisioning terakhir:
        <span class="{{ $account->provision_status === 'provisioned' ? 'text-emerald-600' : ($account->provision_status === 'failed' ? 'text-rose-600' : 'text-slate-500') }} font-medium">
          {{ $account->provision_message }}
        </span>
      @else
        Isi data hosting account. Centang "Provision Otomatis" untuk langsung membuat akun di server via API.
      @endif
    </p>
  </div>

  <form method="POST" action="{{ $account->exists ? route('admin.hosting-account.update', $account) : route('admin.hosting-account.add') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    @if ($account->exists) @method('PUT') @endif

    <div>
      <label class="form-label">Klien</label>
      <select name="client_id" class="form-input" required>
        <option value="">Pilih klien</option>
        @foreach ($clients as $client)
          <option value="{{ $client->id }}" @selected(old('client_id', $account->client_id) == $client->id)>{{ $client->name }}</option>
        @endforeach
      </select>
      @error('client_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Domain</label>
        <input type="text" name="domain" value="{{ old('domain', $account->domain) }}" placeholder="contoh.com" class="form-input" required>
        @error('domain') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Paket (nama plan di panel)</label>
        <input type="text" name="package" value="{{ old('package', $account->package) }}" placeholder="cloud_hosting_pro" class="form-input" required>
        @error('package') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">Kalau provisioning otomatis: harus sama persis dengan nama package/plan yang sudah dibuat di WHM.</p>
      </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="form-label">Panel Hosting</label>
        <select name="panel" class="form-input">
          <option value="cpanel" @selected(old('panel', $account->panel) === 'cpanel')>cPanel / WHM</option>
          <option value="directadmin" @selected(old('panel', $account->panel) === 'directadmin')>DirectAdmin</option>
          <option value="plesk" @selected(old('panel', $account->panel) === 'plesk')>Plesk</option>
          <option value="vps" @selected(old('panel', $account->panel) === 'vps')>VPS / Manual (tanpa panel otomatis)</option>
        </select>
      </div>
      <div>
        <label class="form-label">Server Terhubung (opsional)</label>
        <select name="server_id" class="form-input">
          <option value="">— Tanpa server (manual) —</option>
          @foreach ($servers as $srv)
            <option value="{{ $srv->id }}" @selected(old('server_id', $account->server_id) == $srv->id)>{{ $srv->name }} ({{ $srv->hostname }})</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Username Panel</label>
        <input type="text" name="username" value="{{ old('username', $account->username) }}" placeholder="contohus" class="form-input">
      </div>
    </div>

    <div class="rounded-lg border {{ $account->exists && ! $account->product_id ? 'border-amber-200 bg-amber-50/50' : 'border-slate-100' }} p-4">
      <label class="form-label">Produk Terkait</label>
      <select name="product_id" class="form-input max-w-md">
        <option value="">— Tidak ditautkan ke produk manapun —</option>
        @foreach ($products as $p)
          <option value="{{ $p->id }}" @selected(old('product_id', $account->product_id) == $p->id)>
            {{ $p->category->name ?? '' }} — {{ $p->name }}
          </option>
        @endforeach
      </select>
      @error('product_id') <p class="form-error">{{ $message }}</p> @enderror

      @if ($account->exists && ! $account->product_id)
        <p class="text-xs text-amber-700 mt-2">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Layanan ini belum tertaut ke produk manapun — biasanya karena dibuat sebelum fitur
          <b>Upgrade Paket Mandiri</b> ada. Klien <b>tidak akan melihat opsi upgrade</b> sampai ini diisi.
          Pilih produk yang paling sesuai dengan paket layanan ini saat ini.
        </p>
      @else
        <p class="text-[11px] text-slate-400 mt-2">
          Dipakai untuk menentukan paket lain apa saja yang bisa jadi tujuan upgrade mandiri klien
          (harus kategori &amp; server yang sama, harga lebih tinggi).
        </p>
      @endif
    </div>

    <div class="rounded-lg border {{ ! $account->server_id ? 'border-amber-200 bg-amber-50/50' : 'border-slate-100' }} p-4">
      <label class="form-label">Info Akses untuk Klien</label>
      <textarea name="client_details" rows="4" placeholder="Contoh untuk VPS:&#10;IP Address: 103.xx.xx.xx&#10;Username: root&#10;Password: ********&#10;Panel: https://xxx:4083"
                class="form-input font-mono text-xs">{{ old('client_details', $account->client_details) }}</textarea>
      @error('client_details') <p class="form-error">{{ $message }}</p> @enderror

      @if (! $account->server_id)
        <p class="text-xs text-amber-700 mt-2">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Layanan ini tidak terhubung server (mode manual — cocok untuk VPS, dedicated server, lisensi, dll).
          Klien <b>tidak punya cara lain</b> melihat info akses layanannya selain dari sini — isi setelah
          Anda setup layanan ini sendiri di luar sistem.
        </p>
      @else
        <p class="text-[11px] text-slate-400 mt-2">
          Opsional untuk akun cPanel otomatis — dipakai kalau ada info tambahan
          yang perlu disampaikan ke klien di luar login SSO.
        </p>
      @endif
      <p class="text-[11px] text-slate-400 mt-1">
        <i class="fa-solid fa-lock"></i> Disimpan terenkripsi di database, sama seperti kredensial server.
      </p>
    </div>

    @unless ($account->exists)
      <div class="rounded-lg border border-dashed border-indigo-200 bg-indigo-50/50 p-4 space-y-3">
        <label class="flex items-center gap-2 text-sm font-semibold text-indigo-700">
          <input type="checkbox" name="provision_now" value="1" id="provisionNow" @checked(old('provision_now')) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Provision Otomatis ke Server (buat akun cPanel sekarang via API)
        </label>
        <div>
          <label class="form-label">Password Akun cPanel</label>
          <div class="flex gap-2">
            <input type="password" name="provision_password" id="pwField" placeholder="Password untuk akun baru di server" class="form-input">
            <button type="button" onclick="lumoraGeneratePassword('pwField', null, 'pwChecklist')" class="btn btn-outline !py-2 !px-3 text-xs whitespace-nowrap shrink-0">
              <i class="fa-solid fa-dice text-xs"></i> Buatkan Otomatis
            </button>
          </div>
          <ul id="pwChecklist" class="text-[11px] text-slate-400 mt-1.5 space-y-0.5"></ul>
          <p class="text-[11px] text-slate-400 mt-1">Hanya dipakai sekali untuk request ke API, tidak disimpan di database.</p>
        </div>
      </div>
    @endunless

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="form-label">Harga</label>
        <input type="number" step="0.01" name="price" value="{{ old('price', $account->price) }}" class="form-input" required>
        @error('price') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Siklus Tagihan</label>
        <select name="billing_cycle" class="form-input">
          <option value="monthly" @selected(old('billing_cycle', $account->billing_cycle) === 'monthly')>Bulanan</option>
          <option value="quarterly" @selected(old('billing_cycle', $account->billing_cycle) === 'quarterly')>3 Bulan</option>
          <option value="semi_annually" @selected(old('billing_cycle', $account->billing_cycle) === 'semi_annually')>6 Bulan</option>
          <option value="annually" @selected(old('billing_cycle', $account->billing_cycle) === 'annually')>Tahunan</option>
        </select>
      </div>
      <div>
        <label class="form-label">Jatuh Tempo Berikutnya</label>
        <input type="date" name="next_due_date" value="{{ old('next_due_date', optional($account->next_due_date)->format('Y-m-d')) }}" class="form-input">
      </div>
    </div>

    <div>
      <label class="form-label">Status</label>
      <select name="status" class="form-input">
        <option value="pending" @selected(old('status', $account->status) === 'pending')>Pending</option>
        <option value="active" @selected(old('status', $account->status) === 'active')>Aktif</option>
        <option value="suspended" @selected(old('status', $account->status) === 'suspended')>Suspended</option>
        <option value="terminated" @selected(old('status', $account->status) === 'terminated')>Terminated</option>
      </select>
      <p class="text-[11px] text-slate-400 mt-1">Untuk suspend/unsuspend/terminate lewat API, gunakan tombol aksi di halaman daftar, bukan dropdown ini.</p>
    </div>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.hosting-accounts') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

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
