@extends('layouts.admin')
@section('title', 'Live Chat')
@section('content')
  @include('admin.settings._nav')

  @php use App\Models\Setting; $provider = Setting::get('livechat_provider', 'none'); @endphp

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Live Chat</h1>
    <p class="text-sm text-slate-500 mt-1">Widget chat yang tampil di halaman publik.</p>
  </div>

  <div class="max-w-2xl rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-xs text-amber-800 mb-4">
    <i class="fa-solid fa-circle-info"></i>
    Live chat di sini memakai layanan pihak ketiga (Tawk.to / Crisp) atau tombol WhatsApp —
    bukan sistem chat yang dibangun sendiri. Chat realtime buatan sendiri butuh WebSocket server
    dan staf yang standby di dashboard, jadi untuk kebanyakan bisnis hosting layanan eksternal
    lebih praktis dan gratis di tier dasarnya.
  </div>

  <form method="POST" action="{{ route('admin.settings.livechat.update') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf

    <div>
      <label class="form-label">Penyedia</label>
      <select name="livechat_provider" id="providerSelect" class="form-input">
        <option value="none" @selected($provider === 'none')>Nonaktif</option>
        <option value="widget" @selected($provider === 'widget')>Widget Bawaan (WhatsApp + Tiket + Email)</option>
        <option value="whatsapp" @selected($provider === 'whatsapp')>Tombol WhatsApp (paling sederhana)</option>
        <option value="tawkto" @selected($provider === 'tawkto')>Tawk.to (gratis)</option>
        <option value="crisp" @selected($provider === 'crisp')>Crisp</option>
      </select>
    </div>

    <div id="fieldProperty" class="hidden">
      <label class="form-label"><span id="labelProperty">Property ID</span></label>
      <input type="text" name="livechat_property_id" value="{{ old('livechat_property_id', Setting::get('livechat_property_id')) }}" class="form-input" placeholder="contoh: 65a1b2c3d4e5f6/1abcdefg">
      @error('livechat_property_id') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1" id="hintProperty"></p>
    </div>

    <div id="fieldWa" class="hidden space-y-4">
      <div>
        <label class="form-label">Nomor WhatsApp</label>
        <input type="text" name="livechat_whatsapp" value="{{ old('livechat_whatsapp', Setting::get('livechat_whatsapp')) }}" class="form-input" placeholder="6281234567890">
        @error('livechat_whatsapp') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">Diawali kode negara tanpa tanda +. Contoh: 6281234567890</p>
      </div>
      <div>
        <label class="form-label">Pesan Pembuka</label>
        <input type="text" name="livechat_greeting" value="{{ old('livechat_greeting', Setting::get('livechat_greeting', 'Halo, saya ingin bertanya tentang layanan hosting.')) }}" class="form-input">
        <p class="text-[11px] text-slate-400 mt-1">Teks yang otomatis terisi saat pengunjung membuka chat.</p>
      </div>
      <div>
        <label class="form-label">Pesan Sambutan Otomatis</label>
        <input type="text" name="chat_greeting_1" value="{{ old('chat_greeting_1', Setting::get('chat_greeting_1', 'Selamat datang! Ada yang bisa kami bantu?')) }}" class="form-input">
        <p class="text-[11px] text-slate-400 mt-1">Muncul otomatis saat pengunjung membuka chat pertama kali.</p>
      </div>
      <div>
        <label class="form-label">Pesan Kedua / Promo <span class="text-slate-400 font-normal">(opsional)</span></label>
        <input type="text" name="chat_greeting_2" value="{{ old('chat_greeting_2', Setting::get('chat_greeting_2')) }}" class="form-input"
               placeholder="Promo hosting + domain gratis, cek di halaman Hosting!">
        <p class="text-[11px] text-slate-400 mt-1">Ditampilkan setelah pesan sambutan. Boleh berisi tautan.</p>
      </div>
      <div>
        <label class="form-label">Jam Operasional <span class="text-slate-400 font-normal">(opsional)</span></label>
        <input type="text" name="support_hours" value="{{ old('support_hours', Setting::get('support_hours')) }}" class="form-input" placeholder="Senin–Jumat, 09.00–17.00 WIB">
        <p class="text-[11px] text-slate-400 mt-1">Ditampilkan di widget supaya pengunjung tahu kapan bisa dibalas cepat.</p>
      </div>
    </div>

    <div id="hintWidget" class="hidden rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 text-xs text-indigo-700">
      <i class="fa-solid fa-circle-info"></i>
      <b>Widget Bawaan</b> menampilkan tombol mengambang di pojok kanan bawah berisi tiga pilihan:
      chat WhatsApp, buat tiket support, dan kirim email. Tidak memuat script pihak ketiga sama sekali,
      jadi halaman tetap ringan dan data pengunjung tidak dikirim ke layanan luar.
      Email support diambil dari <b>Pengaturan → Umum</b>.
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Pengaturan</button>
  </form>

  <script>
    (function () {
      const select = document.getElementById('providerSelect');
      const fProp  = document.getElementById('fieldProperty');
      const fWa    = document.getElementById('fieldWa');
      const lProp  = document.getElementById('labelProperty');
      const hProp  = document.getElementById('hintProperty');

      function sync() {
        const v = select.value;
        fProp.classList.toggle('hidden', v !== 'tawkto' && v !== 'crisp');
        // Nomor WhatsApp dipakai oleh mode 'whatsapp' maupun 'widget'.
        fWa.classList.toggle('hidden', v !== 'whatsapp' && v !== 'widget');
        document.getElementById('hintWidget').classList.toggle('hidden', v !== 'widget');

        if (v === 'tawkto') {
          lProp.textContent = 'Tawk.to Property ID / Widget ID';
          hProp.textContent = 'Ambil dari Tawk.to Dashboard » Administration » Chat Widget. Formatnya: propertyId/widgetId';
        } else if (v === 'crisp') {
          lProp.textContent = 'Crisp Website ID';
          hProp.textContent = 'Ambil dari Crisp Settings » Website Settings » Setup Instructions.';
        }
      }

      select.addEventListener('change', sync);
      sync();
    })();
  </script>
@endsection
