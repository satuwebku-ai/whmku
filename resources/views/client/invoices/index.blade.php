@extends('client.layout')
@section('title', 'Invoice')

@section('content')
  <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Invoice</h1>
      <p class="text-sm text-slate-500 mt-1">Riwayat tagihan dan pembayaran Anda.</p>
    </div>
    <form method="GET">
      <select name="status" class="form-input !py-2 text-sm" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="unpaid" @selected(request('status') === 'unpaid')>Belum Bayar</option>
        <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
        <option value="overdue" @selected(request('status') === 'overdue')>Terlambat</option>
        <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
      </select>
    </form>
  </div>

  <div class="space-y-3">
    @forelse ($invoices as $invoice)
      <div class="card p-5 flex items-center justify-between gap-4 flex-wrap">
        <div class="min-w-0">
          <a href="{{ route('client.invoices.show', $invoice) }}" class="font-semibold text-slate-800 hover:text-accent">
            {{ $invoice->invoice_number }}
          </a>
          <p class="text-xs text-slate-400 mt-1">
            Terbit {{ $invoice->issue_date->format('d M Y') }} ·
            Jatuh tempo {{ $invoice->due_date->format('d M Y') }}
          </p>
        </div>

        <div class="flex items-center gap-4">
          <div class="text-right">
            <p class="text-base font-bold text-slate-800">Rp {{ number_format($invoice->total, 0, ',', '.') }}</p>
            <span class="badge badge-{{ $invoice->is_overdue ? 'overdue' : $invoice->status }}">
              {{ $invoice->is_overdue ? 'Terlambat' : ucfirst($invoice->status) }}
            </span>
          </div>

          @if (in_array($invoice->status, ['unpaid', 'overdue']))
            <a href="{{ route('client.invoices.show', $invoice) }}" class="btn btn-primary">
              <i class="fa-solid fa-credit-card text-xs"></i> Bayar
            </a>
          @else
            <a href="{{ route('client.invoices.show', $invoice) }}" class="btn btn-outline">Detail</a>
          @endif
        </div>
      </div>
    @empty
      <div class="card p-10 text-center">
        <p class="text-slate-400 text-sm">Belum ada invoice.</p>
      </div>
    @endforelse
  </div>

  @if ($invoices->hasPages())
    <div class="mt-5">{{ $invoices->links() }}</div>
  @endif
@endsection
