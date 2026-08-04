@extends('layouts.admin')

@section('title', $gateway->exists ? 'Edit Gateway' : 'Tambah Gateway')

@section('content')

  @include('admin.payments._nav')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $gateway->exists ? 'Edit Payment Gateway' : 'Tambah Payment Gateway' }}</h1>
    <p class="text-sm text-slate-500 mt-1">Semua kredensial dienkripsi otomatis di database.</p>
  </div>

  <div id="hint-midtrans" class="driver-hint hidden max-w-2xl rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 text-xs text-indigo-700 mb-4">
    <i class="fa-solid fa-circle-info"></i>
    <b>Midtrans:</b> ambil Server Key &amp; Client Key dari Dashboard Midtrans » Settings » Access Keys.
    Daftarkan Payment Notification URL berikut di Settings » Configuration:
    <code class="bg-white/60 px-1 rounded">{{ url('/payment/webhook/midtrans') }}</code>
  </div>

  <div id="hint-xendit" class="driver-hint hidden max-w-2xl rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 text-xs text-indigo-700 mb-4">
    <i class="fa-solid fa-circle-info"></i>
    <b>Xendit:</b> isi Secret API Key di field Server Key, dan Callback Verification Token di field Callback Token
    (Dashboard Xendit » Settings » Developers). Daftarkan webhook URL:
    <code class="bg-white/60 px-1 rounded">{{ url('/payment/webhook/xendit') }}</code>
  </div>

  <div id="hint-manual" class="driver-hint hidden max-w-2xl rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-xs text-amber-800 mb-4">
    <i class="fa-solid fa-circle-info"></i>
    <b>Transfer Manual:</b> tidak memanggil API apapun. Isi instruksi transfer (nomor rekening, nama penerima)
    di kolom Instruksi. Pembayaran diverifikasi admin lewat tombol Setujui di halaman detail.
  </div>

  <form method="POST" action="{{ $gateway->exists ? route('admin.gateway.update', $gateway) : route('admin.gateway.add') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama (dilihat klien)</label>
        <input type="text" name="name" value="{{ old('name', $gateway->name) }}" placeholder="Transfer Bank BCA" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Driver</label>
        <select name="driver" id="driverSelect" class="form-input">
          @foreach ($drivers as $key => $label)
            <option value="{{ $key }}" @selected(old('driver', $gateway->driver ?? 'midtrans') === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div id="fieldsAuto" class="space-y-4">
      <div id="fieldMode">
        <label class="form-label">Mode</label>
        <select name="mode" class="form-input">
          <option value="sandbox" @selected(old('mode', $gateway->mode ?? 'sandbox') === 'sandbox')>Sandbox (testing)</option>
          <option value="production" @selected(old('mode', $gateway->mode) === 'production')>Production</option>
        </select>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="form-label"><span id="labelServerKey">Server Key</span> {{ $gateway->exists ? '(kosongkan jika tidak diganti)' : '' }}</label>
          <input type="password" name="server_key" placeholder="{{ $gateway->exists ? '••••••••••••' : '' }}" class="form-input">
          @error('server_key') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div id="fieldClientKey">
          <label class="form-label">Client Key {{ $gateway->exists ? '(opsional)' : '' }}</label>
          <input type="password" name="client_key" placeholder="{{ $gateway->exists ? '••••••••••••' : '' }}" class="form-input">
        </div>
      </div>

      <div id="fieldCallbackToken">
        <label class="form-label">Callback Verification Token {{ $gateway->exists ? '(kosongkan jika tidak diganti)' : '' }}</label>
        <input type="password" name="callback_token" placeholder="{{ $gateway->exists ? '••••••••••••' : '' }}" class="form-input">
        <p class="text-[11px] text-slate-400 mt-1">Wajib untuk Xendit — dipakai memverifikasi keaslian webhook.</p>
      </div>
    </div>

    <div id="fieldInstructions" class="hidden">
      <label class="form-label">Instruksi Transfer</label>
      <textarea name="instructions" rows="4" class="form-input" placeholder="Bank BCA&#10;No. Rek: 1234567890&#10;a/n PT Contoh Hosting">{{ old('instructions', $gateway->instructions) }}</textarea>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="form-label">Biaya Tetap (Rp)</label>
        <input type="number" step="0.01" name="fee_flat" value="{{ old('fee_flat', $gateway->fee_flat ?? 0) }}" class="form-input">
      </div>
      <div>
        <label class="form-label">Biaya Persentase (%)</label>
        <input type="number" step="0.01" name="fee_percent" value="{{ old('fee_percent', $gateway->fee_percent ?? 0) }}" class="form-input">
      </div>
      <div>
        <label class="form-label">Mata Uang</label>
        <input type="text" name="currency" maxlength="3" value="{{ old('currency', $gateway->currency ?? 'IDR') }}" class="form-input" required>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 items-end">
      <div>
        <label class="form-label">Urutan Tampil</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $gateway->sort_order ?? 0) }}" class="form-input">
      </div>
      <label class="flex items-center gap-2 text-sm text-slate-600 pb-2.5">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $gateway->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
        Aktif
      </label>
    </div>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.gateways') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

  <script>
    (function () {
      const select        = document.getElementById('driverSelect');
      const fieldsAuto    = document.getElementById('fieldsAuto');
      const fieldClient   = document.getElementById('fieldClientKey');
      const fieldToken    = document.getElementById('fieldCallbackToken');
      const fieldInstr    = document.getElementById('fieldInstructions');
      const labelServer   = document.getElementById('labelServerKey');

      function sync() {
        const driver = select.value;
        const isManual = driver === 'manual';

        // Gateway manual tidak butuh kredensial API sama sekali.
        fieldsAuto.classList.toggle('hidden', isManual);
        fieldInstr.classList.toggle('hidden', !isManual);

        // Client Key hanya dipakai Midtrans; Callback Token hanya Xendit.
        fieldClient.classList.toggle('hidden', driver !== 'midtrans');
        fieldToken.classList.toggle('hidden', driver !== 'xendit');

        labelServer.textContent = driver === 'xendit' ? 'Secret API Key' : 'Server Key';

        document.querySelectorAll('.driver-hint').forEach(el => el.classList.add('hidden'));
        document.getElementById('hint-' + driver)?.classList.remove('hidden');
      }

      select.addEventListener('change', sync);
      sync();
    })();
  </script>

@endsection
