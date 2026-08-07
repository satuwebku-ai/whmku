@extends('client.layout')
@section('title', 'Bayar dengan QRIS')

@section('content')

  <a href="{{ route('client.invoices.show', $invoice) }}" class="text-xs text-slate-400 hover:text-slate-600">
    &larr; Kembali ke Invoice
  </a>

  <div class="max-w-md mx-auto mt-4">
    <div class="card p-6 text-center">
      <h1 class="text-lg font-bold text-slate-800 mb-1">Bayar dengan QRIS</h1>
      <p class="text-sm text-slate-500 mb-5">
        Invoice {{ $invoice->invoice_number }} — Rp {{ number_format((float) $payment->total, 0, ',', '.') }}
      </p>

      {{-- Kode QR digambar langsung di browser (bukan dari layanan luar),
           supaya kode pembayaran tidak pernah dikirim ke server pihak
           ketiga mana pun. --}}
      <div id="qrHolder" class="flex items-center justify-center bg-white p-4 rounded-xl border border-slate-200 mx-auto" style="width:264px;height:264px"></div>
      <p class="text-[11px] text-slate-400 mt-2">
        Berlaku sampai {{ $payment->expires_at?->format('H:i') }} ·
        <span id="countdown"></span>
      </p>

      <div id="statusBox" class="mt-5">
        <p class="text-sm text-slate-500">
          <i class="fa-solid fa-circle-notch fa-spin"></i> Menunggu pembayaran…
        </p>
      </div>

      <div class="mt-5 pt-5 border-t border-slate-100 text-left">
        <p class="text-xs font-semibold text-slate-600 mb-2">Cara membayar</p>
        <ol class="text-xs text-slate-500 space-y-1 list-decimal list-inside">
          <li>Buka aplikasi e-wallet atau mobile banking yang mendukung QRIS</li>
          <li>Pilih menu Scan / Bayar dengan QR</li>
          <li>Arahkan kamera ke kode QR di atas</li>
          <li>Periksa nominal, lalu selesaikan pembayaran</li>
        </ol>
      </div>

      <a href="{{ route('client.invoices.show', $invoice) }}" class="block text-xs text-accent hover:underline mt-5">
        Pilih metode pembayaran lain
      </a>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    (function () {
      new QRCode(document.getElementById('qrHolder'), {
        text: @json($qrString),
        width: 240,
        height: 240,
        correctLevel: QRCode.CorrectLevel.M,
      });

      const statusBox = document.getElementById('statusBox');
      const countdownEl = document.getElementById('countdown');
      const expiresAt = @json($payment->expires_at?->toIso8601String());
      const statusUrl = @json(route('client.invoices.qris-status', $payment));
      const invoiceUrl = @json(route('client.invoices.show', $invoice));

      function tickCountdown() {
        if (!expiresAt) return;

        const diff = Math.max(0, new Date(expiresAt) - new Date());
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        countdownEl.textContent = diff > 0
          ? `sisa ${minutes}:${String(seconds).padStart(2, '0')}`
          : 'kedaluwarsa';
      }

      async function poll() {
        try {
          const res = await fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const data = await res.json();

          if (data.status === 'paid') {
            statusBox.innerHTML = '<p class="text-sm text-emerald-600 font-medium">'
              + '<i class="fa-solid fa-circle-check"></i> Pembayaran berhasil! Mengalihkan…</p>';
            clearInterval(timer);
            setTimeout(() => window.location.href = invoiceUrl, 1500);
            return;
          }

          if (data.expired) {
            statusBox.innerHTML = '<p class="text-sm text-rose-600">'
              + '<i class="fa-solid fa-circle-exclamation"></i> Kode QR sudah kedaluwarsa. '
              + '<a href="' + invoiceUrl + '" class="underline">Buat kode baru</a></p>';
            clearInterval(timer);
          }
        } catch (e) {
          // Diam saja saat gagal sesekali — percobaan berikutnya akan jalan normal.
        }
      }

      tickCountdown();
      poll();

      const timer = setInterval(function () { tickCountdown(); poll(); }, 5000);
    })();
  </script>

@endsection
