@extends('client.layout')
@section('title', 'Pilih Metode Pembayaran')

@section('content')
  <a href="{{ route('client.invoices.show', $invoice) }}" class="text-xs text-slate-400 hover:text-slate-600">
    &larr; Kembali ke Invoice {{ $invoice->invoice_number }}
  </a>

  <div class="mt-2 mb-6">
    <h1 class="text-xl font-bold text-slate-800">Pilih Metode Pembayaran</h1>
    <p class="text-sm text-slate-500 mt-1">
      Total tagihan: <b>Rp {{ number_format($total, 0, ',', '.') }}</b>
    </p>
  </div>

  @if ($grouped->isEmpty())
    <div class="card p-8 text-center">
      <p class="text-slate-500 text-sm">Tidak ada metode pembayaran yang tersedia saat ini. Silakan hubungi support kami.</p>
    </div>
  @endif

  @foreach ($grouped as $category => $methods)
    <div class="mb-6">
      <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">{{ $category }}</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ($methods as $method)
          <form method="POST" action="{{ route('client.invoices.pay-duitku', $invoice) }}">
            @csrf
            <input type="hidden" name="payment_gateway_id" value="{{ $gateway->id }}">
            <input type="hidden" name="method_code" value="{{ $method['paymentMethod'] }}">
            <button type="submit" class="card p-4 w-full text-left hover:border-accent hover:shadow-sm transition-all flex items-center gap-3">
              @if (! empty($method['paymentImage']))
                <img src="{{ $method['paymentImage'] }}" alt="{{ $method['paymentName'] }}" class="h-8 w-auto object-contain shrink-0" loading="lazy">
              @endif
              <div class="min-w-0">
                <p class="text-sm font-medium text-slate-700 truncate">{{ $method['paymentName'] }}</p>
                <p class="text-xs text-slate-400">
                  {{ (float) ($method['totalFee'] ?? 0) > 0 ? 'Biaya Rp ' . number_format((float) $method['totalFee'], 0, ',', '.') : 'Tanpa biaya tambahan' }}
                </p>
              </div>
            </button>
          </form>
        @endforeach
      </div>
    </div>
  @endforeach
@endsection
