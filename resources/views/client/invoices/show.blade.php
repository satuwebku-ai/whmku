@extends('client.layout')
@section('title', $invoice->invoice_number)

@section('content')
  <a href="{{ route('client.invoices') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Invoice</a>

  <div class="flex items-center justify-between mt-2 mb-5 flex-wrap gap-3">
    <h1 class="text-xl font-bold text-slate-800">{{ $invoice->invoice_number }}</h1>
    <div class="flex items-center gap-2">
      <span class="badge badge-{{ $invoice->is_overdue ? 'overdue' : $invoice->status }} !text-sm !px-3 !py-1">
        {{ $invoice->is_overdue ? 'Terlambat' : ucfirst($invoice->status) }}
      </span>
      <a href="{{ route('client.invoices.pdf', $invoice) }}" class="btn btn-outline !py-1.5 !px-3 text-xs">
        <i class="fa-solid fa-file-arrow-down text-xs"></i> Unduh PDF
      </a>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      <div class="card p-6">
        <div class="flex justify-between items-start mb-5 pb-5 border-b border-slate-100">
          <div>
            <p class="text-xs text-slate-400">Ditagihkan kepada</p>
            <p class="font-semibold text-slate-800 mt-0.5">{{ $invoice->client->name }}</p>
            <p class="text-sm text-slate-500">{{ $invoice->client->email }}</p>
          </div>
          <div class="text-right">
            <p class="text-xs text-slate-400">Tanggal Terbit</p>
            <p class="text-sm text-slate-700 font-medium">{{ $invoice->issue_date->format('d M Y') }}</p>
            <p class="text-xs text-slate-400 mt-2">Jatuh Tempo</p>
            <p class="text-sm text-slate-700 font-medium">{{ $invoice->due_date->format('d M Y') }}</p>
          </div>
        </div>

        <table class="w-full text-sm mb-5">
          <thead>
            <tr class="text-left text-xs text-slate-400 uppercase border-b border-slate-100">
              <th class="pb-2 font-semibold">Deskripsi</th>
              <th class="pb-2 font-semibold text-right">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($invoice->items as $lineItem)
              <tr>
                <td class="py-3 text-slate-700">{{ $lineItem->description }}</td>
                <td class="py-3 text-right text-slate-700">Rp {{ number_format($lineItem->amount, 0, ',', '.') }}</td>
              </tr>
            @empty
              <tr>
                <td class="py-3 text-slate-700">
                  {{ $invoice->order->product_name ?? 'Tagihan layanan' }}
                  @if ($invoice->order)
                    <span class="block text-xs text-slate-400">Order #{{ $invoice->order->order_number }}</span>
                  @endif
                </td>
                <td class="py-3 text-right text-slate-700">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>

        <div class="space-y-1.5 text-sm border-t border-slate-100 pt-4">
          <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="text-slate-700">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Pajak</span><span class="text-slate-700">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</span></div>
          @if ($invoice->discount > 0)
            <div class="flex justify-between text-emerald-600">
              <span>Kupon{{ $invoice->coupon ? ' ' . $invoice->coupon->code : '' }}</span>
              <span>- Rp {{ number_format($invoice->discount, 0, ',', '.') }}</span>
            </div>
          @endif
          <div class="flex justify-between font-bold text-slate-800 text-lg pt-2 border-t border-slate-100">
            <span>Total</span><span>Rp {{ number_format($invoice->total, 0, ',', '.') }}</span>
          </div>
        </div>

        @if ($invoice->notes)
          <div class="mt-5 pt-4 border-t border-slate-100">
            <p class="text-xs text-slate-400 mb-1">Catatan</p>
            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $invoice->notes }}</p>
          </div>
        @endif
      </div>
    </div>

    {{-- Panel pembayaran --}}
    <div class="space-y-5">
      @if ($invoice->status === 'paid')
        <div class="card p-5 border-emerald-200 bg-emerald-50/60 text-center">
          <i class="fa-solid fa-circle-check text-emerald-500 text-3xl mb-2"></i>
          <p class="font-semibold text-emerald-800">Invoice Lunas</p>
          <p class="text-xs text-emerald-700 mt-1">
            Dibayar {{ $invoice->paid_at?->format('d M Y') }}
            @if ($invoice->payment_method) via {{ $invoice->payment_method }} @endif
          </p>
        </div>

      @elseif ($invoice->status === 'cancelled')
        <div class="card p-5 text-center text-slate-500">
          <p class="text-sm">Invoice ini sudah dibatalkan.</p>
        </div>

      @else
        {{-- Ada pembayaran yang belum selesai --}}
        @if ($pendingPayment && $pendingPayment->payment_url)
          <div class="card p-5 border-amber-200 bg-amber-50/60">
            <p class="text-sm font-semibold text-amber-800 mb-1">Pembayaran Sedang Diproses</p>
            <p class="text-xs text-amber-700 mb-3">
              Anda sudah memulai pembayaran ({{ $pendingPayment->reference }}). Lanjutkan di link berikut,
              atau pilih metode lain di bawah.
            </p>
            <a href="{{ $pendingPayment->payment_url }}" class="btn btn-primary w-full">
              <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> Lanjutkan Pembayaran
            </a>
          </div>
        @endif

        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-1">Bayar Invoice</h2>
          <p class="text-xs text-slate-500 mb-4">Pilih metode pembayaran yang Anda inginkan.</p>

          @if ($gateways->isEmpty())
            <p class="text-sm text-slate-400">Belum ada metode pembayaran tersedia. Silakan hubungi support.</p>
          @else
            {{-- Jalan pintas: kalau ada gateway Duitku dengan QRIS tertanam
                 aktif, tawarkan langsung di sini — tanpa klien perlu
                 memilih radio dulu untuk hal paling umum dipakai. --}}
            @php $qrisGateway = $gateways->first(fn ($g) => $g->supportsEmbeddedQris()); @endphp
            @if ($qrisGateway)
              <a href="{{ route('client.invoices.qris', [$invoice, $qrisGateway]) }}"
                 class="flex items-center gap-3 p-3 mb-3 rounded-lg border-2 border-accent/30 bg-accent/5 hover:border-accent/50 transition-colors">
                <span class="w-10 h-10 rounded-lg bg-white flex items-center justify-center shrink-0 border border-accent/20">
                  <i class="fa-solid fa-qrcode text-accent"></i>
                </span>
                <span class="flex-1">
                  <span class="block text-sm font-semibold text-slate-800">Bayar dengan QRIS</span>
                  <span class="block text-xs text-slate-500">Scan langsung dari halaman ini — tanpa pindah situs</span>
                </span>
                <i class="fa-solid fa-arrow-right text-accent text-xs"></i>
              </a>

              <div class="flex items-center gap-3 mb-3">
                <span class="flex-1 h-px bg-slate-100"></span>
                <span class="text-[11px] text-slate-400">atau metode lain</span>
                <span class="flex-1 h-px bg-slate-100"></span>
              </div>
            @endif

            <form method="POST" action="{{ route('client.invoices.pay', $invoice) }}" class="space-y-3">
              @csrf

              <div class="space-y-2">
                @foreach ($gateways as $gw)
                  @php $fee = $gw->calculateFee((float) $invoice->total); @endphp
                  <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 cursor-pointer hover:border-accent/50 transition-colors">
                    <input type="radio" name="payment_gateway_id" value="{{ $gw->id }}" required
                           class="mt-0.5 border-slate-300 text-accent focus:ring-accent/40">
                    <span class="flex-1 min-w-0">
                      <span class="block text-sm font-medium text-slate-700">{{ $gw->name }}</span>
                      @if ($fee > 0)
                        <span class="block text-xs text-slate-400">
                          + biaya Rp {{ number_format($fee, 0, ',', '.') }}
                          — total Rp {{ number_format($invoice->total + $fee, 0, ',', '.') }}
                        </span>
                      @else
                        <span class="block text-xs text-emerald-600">Tanpa biaya tambahan</span>
                      @endif
                    </span>
                  </label>
                @endforeach
              </div>

              <button type="submit" class="btn btn-primary w-full">
                <i class="fa-solid fa-credit-card text-xs"></i> Lanjutkan Pembayaran
              </button>
            </form>
          @endif
        </div>

        {{-- Instruksi transfer manual --}}
        @foreach ($gateways->where('driver', 'manual') as $manual)
          @if ($manual->instructions)
            <div class="card p-5">
              <h2 class="text-sm font-semibold text-slate-800 mb-2">{{ $manual->name }}</h2>
              <div class="text-sm text-slate-600 whitespace-pre-line bg-slate-50 rounded-lg p-3">{{ $manual->instructions }}</div>
              <p class="text-[11px] text-slate-400 mt-2">
                Cantumkan nomor invoice <b>{{ $invoice->invoice_number }}</b> saat konfirmasi transfer.
              </p>
            </div>
          @endif
        @endforeach
      @endif
    </div>
  </div>
@endsection
