@extends('layouts.admin')

@section('title', 'Detail Invoice ' . $invoice->invoice_number)

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="{{ route('admin.invoices') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Invoice</a>
      <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $invoice->invoice_number }}</h1>
    </div>
    <span class="badge badge-{{ $invoice->is_overdue ? 'overdue' : $invoice->status }} !text-sm !px-3 !py-1">
      {{ $invoice->is_overdue ? 'Overdue' : ucfirst($invoice->status) }}
    </span>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Rincian Invoice</h2>
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Klien</dt>
            <dd class="text-slate-700 font-medium">{{ $invoice->client->name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Order Terkait</dt>
            <dd class="text-slate-700 font-medium">
              @if ($invoice->order)
                <a href="{{ route('admin.orders.details', $invoice->order) }}" class="text-accent hover:underline">#{{ $invoice->order->order_number }}</a>
              @else
                —
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Tanggal Terbit</dt>
            <dd class="text-slate-700 font-medium">{{ $invoice->issue_date->format('d M Y') }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Jatuh Tempo</dt>
            <dd class="text-slate-700 font-medium">{{ $invoice->due_date->format('d M Y') }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Metode Pembayaran</dt>
            <dd class="text-slate-700 font-medium">{{ $invoice->payment_method ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Dibayar Pada</dt>
            <dd class="text-slate-700 font-medium">{{ $invoice->paid_at?->format('d M Y') ?? '—' }}</dd>
          </div>
        </dl>

        <div class="border-t border-slate-100 mt-4 pt-4 space-y-1.5 text-sm">
          <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="text-slate-700">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Pajak</span><span class="text-slate-700">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</span></div>
          <div class="flex justify-between font-semibold text-slate-800 text-base pt-1.5 border-t border-slate-100"><span>Total</span><span>Rp {{ number_format($invoice->total, 0, ',', '.') }}</span></div>
        </div>
      </div>

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Catatan</h2>
        <form method="POST" action="{{ route('admin.invoice.notes') }}">
          @csrf
          <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
          <textarea name="notes" rows="4" class="form-input" placeholder="Catatan tentang invoice ini...">{{ old('notes', $invoice->notes) }}</textarea>
          <button type="submit" class="btn btn-outline mt-3"><i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Catatan</button>
        </form>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Aksi</h2>
        <div class="space-y-2">
          @if ($invoice->status !== 'paid')
            <form method="POST" action="{{ route('admin.invoice.mark.paid') }}">
              @csrf
              <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
              <button type="submit" class="w-full btn btn-primary !justify-start"><i class="fa-solid fa-check text-xs"></i> Tandai Lunas</button>
            </form>
          @else
            <form method="POST" action="{{ route('admin.invoice.mark.unpaid') }}">
              @csrf
              <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
              <button type="submit" class="w-full btn btn-outline !justify-start"><i class="fa-solid fa-rotate-left text-xs"></i> Batalkan Status Lunas</button>
            </form>
          @endif
          @if ($invoice->status !== 'cancelled')
            <form method="POST" action="{{ route('admin.invoice.cancel') }}" onsubmit="return confirm('Batalkan invoice ini?');">
              @csrf
              <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
              <button type="submit" class="w-full btn btn-danger-soft !justify-start"><i class="fa-solid fa-xmark text-xs"></i> Batalkan Invoice</button>
            </form>
          @endif
          <a href="{{ route('admin.invoice.edit.page', $invoice) }}" class="w-full btn btn-outline !justify-start"><i class="fa-regular fa-pen-to-square text-xs"></i> Edit Data Invoice</a>
        </div>
      </div>
    </div>
  </div>

@endsection
