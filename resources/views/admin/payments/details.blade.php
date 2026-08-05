@extends('layouts.admin')

@section('title', 'Detail Pembayaran ' . $payment->reference)

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="{{ route('admin.payments') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Pembayaran</a>
      <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $payment->reference }}</h1>
    </div>
    <span class="badge badge-{{ $payment->status_badge }} !text-sm !px-3 !py-1">{{ ucfirst($payment->status) }}</span>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Informasi Pembayaran</h2>
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Klien</dt>
            <dd class="text-slate-700 font-medium">{{ $payment->client->name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Invoice</dt>
            <dd class="text-slate-700 font-medium">
              @if ($payment->invoice)
                <a href="{{ route('admin.invoices.details', $payment->invoice) }}" class="text-accent hover:underline">{{ $payment->invoice->invoice_number }}</a>
              @else
                —
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Gateway</dt>
            <dd class="text-slate-700 font-medium">{{ $payment->gateway->name ?? '—' }} <span class="text-xs text-slate-400">({{ $payment->gateway->driver_label ?? '' }})</span></dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Metode</dt>
            <dd class="text-slate-700 font-medium">{{ $payment->payment_method ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">ID Transaksi Gateway</dt>
            <dd class="text-slate-700 font-medium break-all">{{ $payment->external_id ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Dibayar Pada</dt>
            <dd class="text-slate-700 font-medium">{{ $payment->paid_at?->format('d M Y H:i') ?? '—' }}</dd>
          </div>
        </dl>

        <div class="border-t border-slate-100 mt-4 pt-4 space-y-1.5 text-sm">
          <div class="flex justify-between"><span class="text-slate-500">Nominal Invoice</span><span class="text-slate-700">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Biaya Gateway</span><span class="text-slate-700">Rp {{ number_format($payment->fee, 0, ',', '.') }}</span></div>
          <div class="flex justify-between font-semibold text-slate-800 text-base pt-1.5 border-t border-slate-100"><span>Total Ditagih</span><span>Rp {{ number_format($payment->total, 0, ',', '.') }}</span></div>
        </div>

        @if ($payment->payment_url)
          <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-slate-400 mb-1.5">Link Pembayaran (kirim ke klien)</p>
            <div class="flex items-center gap-2">
              <input type="text" readonly value="{{ $payment->payment_url }}" class="form-input text-xs" id="payUrl">
              <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('payUrl').value)" class="btn btn-outline shrink-0"><i class="fa-regular fa-copy text-xs"></i></button>
            </div>
          </div>
        @endif

        @if ($payment->gateway?->isManual() && $payment->gateway->instructions)
          <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-slate-400 mb-1.5">Instruksi Transfer</p>
            <div class="text-sm text-slate-600 whitespace-pre-line bg-slate-50 rounded-lg p-3">{{ $payment->gateway->instructions }}</div>
          </div>
        @endif
      </div>

      @if ($payment->gateway_response)
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-3">Respons Gateway (audit)</h2>
          <pre class="text-[11px] bg-slate-900 text-slate-200 rounded-lg p-3 overflow-x-auto">{{ json_encode($payment->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
      @endif
    </div>

    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Verifikasi</h2>

        @if ($payment->status !== 'paid')
          <form method="POST" action="{{ route('admin.payment.approve') }}" class="space-y-3" data-confirm="Setujui pembayaran ini? Invoice terkait akan ditandai lunas." data-confirm-title="Setujui Pembayaran" data-confirm-style="info" data-confirm-label="Ya, Setujui" >
            @csrf
            <input type="hidden" name="payment_id" value="{{ $payment->id }}">
            <textarea name="admin_note" rows="2" class="form-input" placeholder="Catatan admin (opsional)">{{ $payment->admin_note }}</textarea>
            <button type="submit" class="w-full btn btn-primary !justify-start"><i class="fa-solid fa-check text-xs"></i> Setujui &amp; Lunasi</button>
          </form>

          <form method="POST" action="{{ route('admin.payment.reject') }}" class="mt-2" data-confirm="Tolak pembayaran ini?" data-confirm-title="Konfirmasi" data-confirm-style="warn" data-confirm-label="Lanjutkan" >
            @csrf
            <input type="hidden" name="payment_id" value="{{ $payment->id }}">
            <button type="submit" class="w-full btn btn-danger-soft !justify-start"><i class="fa-solid fa-xmark text-xs"></i> Tolak Pembayaran</button>
          </form>
        @else
          <p class="text-sm text-emerald-600 mb-3"><i class="fa-solid fa-circle-check"></i> Pembayaran sudah terverifikasi lunas.</p>
        @endif

        @if ($payment->gateway && ! $payment->gateway->isManual())
          <form method="POST" action="{{ route('admin.payment.check.status', $payment) }}" class="mt-2">
            @csrf
            <button type="submit" class="w-full btn btn-outline !justify-start"><i class="fa-solid fa-rotate text-xs"></i> Cek Status ke Gateway</button>
          </form>
        @endif
      </div>

      @if ($payment->admin_note)
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-2">Catatan Admin</h2>
          <p class="text-sm text-slate-600 whitespace-pre-line">{{ $payment->admin_note }}</p>
        </div>
      @endif
    </div>
  </div>

@endsection
