@extends('layouts.admin')

@section('title', 'Buat Pembayaran')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Buat Pembayaran</h1>
    <p class="text-sm text-slate-500 mt-1">Pilih invoice yang belum lunas dan gateway yang dipakai. Untuk gateway otomatis, link pembayaran langsung dibuat.</p>
  </div>

  @if ($invoices->isEmpty())
    <div class="card p-6 max-w-2xl text-center text-slate-500 text-sm">
      Tidak ada invoice berstatus unpaid/overdue. Buat invoice dulu di menu Invoice.
    </div>
  @elseif ($gateways->isEmpty())
    <div class="card p-6 max-w-2xl text-center text-slate-500 text-sm">
      Belum ada payment gateway aktif. Tambahkan dulu di
      <a href="{{ route('admin.gateways') }}" class="text-accent hover:underline">tab Gateway</a>.
    </div>
  @else
    <form method="POST" action="{{ route('admin.payment.add') }}" class="card p-6 max-w-2xl space-y-4">
      @csrf

      <div>
        <label class="form-label">Invoice</label>
        <select name="invoice_id" class="form-input" required>
          <option value="">Pilih invoice</option>
          @foreach ($invoices as $invoice)
            <option value="{{ $invoice->id }}" @selected(old('invoice_id') == $invoice->id)>
              {{ $invoice->invoice_number }} — {{ $invoice->client->name ?? '—' }} (Rp {{ number_format($invoice->total, 0, ',', '.') }})
            </option>
          @endforeach
        </select>
        @error('invoice_id') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="form-label">Payment Gateway</label>
        <select name="payment_gateway_id" class="form-input" required>
          <option value="">Pilih gateway</option>
          @foreach ($gateways as $gw)
            <option value="{{ $gw->id }}" @selected(old('payment_gateway_id') == $gw->id)>
              {{ $gw->name }} ({{ $gw->driver_label }}{{ $gw->isSandbox() && ! $gw->isManual() ? ' — Sandbox' : '' }})
            </option>
          @endforeach
        </select>
        @error('payment_gateway_id') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-[11px] text-slate-400 mt-1">Biaya gateway (jika ada) otomatis ditambahkan ke total tagihan.</p>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Buat Pembayaran</button>
        <a href="{{ route('admin.payments') }}" class="btn btn-outline">Batal</a>
      </div>
    </form>
  @endif

@endsection
