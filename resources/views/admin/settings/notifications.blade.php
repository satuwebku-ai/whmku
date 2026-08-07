@extends('layouts.admin')

@section('title', 'Pengaturan Notifikasi')

@section('content')

  @include('admin.settings._nav')

  @php use App\Models\Setting; @endphp

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Notifikasi</h1>
    <p class="text-sm text-slate-500 mt-1">Atur email dan WhatsApp yang dikirim ke klien maupun ke Anda sendiri.</p>
  </div>

  <form method="POST" action="{{ route('admin.settings.notifications.update') }}" class="space-y-5 max-w-3xl">
    @csrf

    {{-- Notifikasi ke klien --}}
    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-1">Invoice Perpanjangan Otomatis</h2>
      <p class="text-xs text-slate-500 mb-4">
        Invoice baru dibuat otomatis untuk hosting & domain yang masa aktifnya mendekati habis —
        klien tidak perlu ditagih manual satu per satu.
      </p>

      <div class="max-w-xs">
        <label class="form-label">Buat Invoice Berapa Hari Sebelum Jatuh Tempo</label>
        <input type="number" name="renewal_invoice_days_before" min="1" max="60"
               value="{{ Setting::get('renewal_invoice_days_before', 7) }}" class="form-input">
        <p class="text-[11px] text-slate-400 mt-1">
          Domain hanya diikutkan kalau opsi "Perpanjangan Otomatis" klien menyala.
        </p>
      </div>

      <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Hanya berjalan kalau cron sudah dipasang — sama seperti Pengingat Jatuh Tempo di bawah.
        Uji dulu tanpa membuat apa pun:
        <code class="block mt-1 bg-white/60 px-2 py-1 rounded">php artisan lumora:generate-renewal-invoices --dry</code>
      </div>
    </div>

    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-1">Notifikasi ke Klien</h2>
      <p class="text-xs text-slate-500 mb-4">Dikirim otomatis ke email klien saat kejadian berikut terjadi.</p>

      <div class="space-y-3">
        @foreach ([
          'notify_welcome' => ['Selamat datang', 'Saat klien selesai mendaftar.'],
          'notify_invoice' => ['Invoice baru', 'Saat tagihan terbit — PDF invoice ikut dilampirkan.'],
          'notify_paid'    => ['Pembayaran diterima', 'Saat invoice ditandai lunas.'],
          'notify_reminder'=> ['Pengingat jatuh tempo', 'Sebelum dan sesudah tanggal jatuh tempo.'],
        ] as $key => [$judul, $ket])
          <label class="flex items-start gap-3 rounded-lg border border-slate-100 px-4 py-3 hover:border-slate-200">
            <input type="checkbox" name="{{ $key }}" value="1" @checked(Setting::get($key, '1') === '1')
                   class="mt-0.5 rounded border-slate-300 text-accent focus:ring-accent/40">
            <span>
              <span class="block text-sm font-medium text-slate-700">{{ $judul }}</span>
              <span class="block text-xs text-slate-500">{{ $ket }}</span>
            </span>
          </label>
        @endforeach
      </div>

      <div class="grid sm:grid-cols-2 gap-4 mt-5 pt-5 border-t border-slate-100">
        <div>
          <label class="form-label">Pengingat Sebelum Jatuh Tempo</label>
          <input type="text" name="reminder_days_before" value="{{ Setting::get('reminder_days_before', '7,3,1') }}" class="form-input" placeholder="7,3,1">
          @error('reminder_days_before') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">Jumlah hari, dipisah koma. "7,3,1" = dikirim H-7, H-3, dan H-1.</p>
        </div>
        <div>
          <label class="form-label">Pengingat Setelah Lewat Tempo</label>
          <input type="text" name="reminder_days_after" value="{{ Setting::get('reminder_days_after', '1,7') }}" class="form-input" placeholder="1,7">
          @error('reminder_days_after') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">"1,7" = dikirim 1 hari dan 7 hari setelah lewat tempo.</p>
        </div>
      </div>

      <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Pengingat hanya berjalan kalau cron sudah dipasang. Tambahkan di cPanel → Cron Jobs,
        dijalankan tiap menit:
        <code class="block mt-1 bg-white/60 px-2 py-1 rounded">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
      </div>
    </div>

    {{-- Notifikasi ke admin --}}
    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-1">Notifikasi ke Admin</h2>
      <p class="text-xs text-slate-500 mb-4">Dikirim ke semua admin aktif, dan selalu tercatat di menu Aktivitas.</p>

      <div class="space-y-3">
        @foreach ([
          'notify_admin_order'   => ['Pesanan baru masuk', 'Saat klien menyelesaikan checkout.'],
          'notify_admin_payment' => ['Pembayaran diterima', 'Saat invoice lunas.'],
          'notify_admin_ticket'  => ['Tiket support baru', 'Saat klien membuka tiket.'],
          'notify_admin_client'  => ['Klien baru mendaftar', 'Saat ada pendaftaran akun baru.'],
        ] as $key => [$judul, $ket])
          <label class="flex items-start gap-3 rounded-lg border border-slate-100 px-4 py-3 hover:border-slate-200">
            <input type="checkbox" name="{{ $key }}" value="1" @checked(Setting::get($key, '1') === '1')
                   class="mt-0.5 rounded border-slate-300 text-accent focus:ring-accent/40">
            <span>
              <span class="block text-sm font-medium text-slate-700">{{ $judul }}</span>
              <span class="block text-xs text-slate-500">{{ $ket }}</span>
            </span>
          </label>
        @endforeach
      </div>
    </div>

    {{-- WhatsApp --}}
    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-1">WhatsApp</h2>
      <p class="text-xs text-slate-500 mb-4">
        WhatsApp tidak punya API resmi yang murah untuk skala kecil, jadi dipakai gateway pihak ketiga.
        Klien hanya menerima WhatsApp kalau mereka sendiri mengaktifkannya di halaman Profil.
      </p>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="form-label">Gateway</label>
          <select name="wa_provider" id="waProvider" class="form-input">
            <option value="none" @selected(Setting::get('wa_provider', 'none') === 'none')>Nonaktif</option>
            <option value="fonnte" @selected(Setting::get('wa_provider') === 'fonnte')>Fonnte</option>
            <option value="wablas" @selected(Setting::get('wa_provider') === 'wablas')>Wablas</option>
            <option value="custom" @selected(Setting::get('wa_provider') === 'custom')>Lainnya (JSON)</option>
          </select>
        </div>
        <div>
          <label class="form-label">Token {{ Setting::get('wa_token') ? '(kosongkan jika tidak diganti)' : '' }}</label>
          <input type="password" name="wa_token" class="form-input" placeholder="{{ Setting::get('wa_token') ? '••••••••••••' : 'Token dari dashboard gateway' }}">
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4 mt-4">
        <div id="waEndpointField">
          <label class="form-label">Endpoint API</label>
          <input type="text" name="wa_endpoint" value="{{ Setting::get('wa_endpoint') }}" class="form-input" placeholder="https://console.wablas.com">
          <p class="text-[11px] text-slate-400 mt-1">Wablas: domain akun Anda. Lainnya: URL lengkap endpoint kirim pesan.</p>
        </div>
        <div>
          <label class="form-label">Nomor WhatsApp Admin</label>
          <input type="text" name="wa_admin_number" value="{{ Setting::get('wa_admin_number') }}" class="form-input" placeholder="6281234567890">
          <p class="text-[11px] text-slate-400 mt-1">Tujuan notifikasi admin lewat WhatsApp.</p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Pengaturan</button>
    </div>
  </form>

  {{-- Tes WhatsApp: form terpisah supaya tidak ikut menyimpan pengaturan --}}
  <div class="card p-6 max-w-3xl mt-5">
    <h2 class="text-sm font-semibold text-slate-800 mb-1">Tes Kirim WhatsApp</h2>
    <p class="text-xs text-slate-500 mb-3">Simpan pengaturan dulu, baru kirim pesan percobaan.</p>

    <form method="POST" action="{{ route('admin.settings.notifications.test-wa') }}" class="flex gap-2">
      @csrf
      <input type="text" name="test_number" class="form-input flex-1" placeholder="6281234567890" required>
      <button type="submit" class="btn btn-outline shrink-0">
        <i class="fa-brands fa-whatsapp"></i> Kirim Tes
      </button>
    </form>
  </div>

  <script>
    // Endpoint hanya relevan untuk Wablas dan gateway custom.
    (function () {
      const provider = document.getElementById('waProvider');
      const endpoint = document.getElementById('waEndpointField');

      function sync() {
        endpoint.classList.toggle('hidden', !['wablas', 'custom'].includes(provider.value));
      }

      provider.addEventListener('change', sync);
      sync();
    })();
  </script>

@endsection
