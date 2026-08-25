@extends('client.layout')
@section('title', 'Invoice')

@section('content')
  @php
    $badgeMap = ['unpaid' => 'badge-soft-warning', 'paid' => 'badge-soft-success', 'overdue' => 'badge-soft-danger', 'cancelled' => 'badge-soft-secondary'];
  @endphp

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
      <h1 class="h4 fw-bold text-dark mb-1">Invoice</h1>
      <p class="text-muted mb-0">Riwayat tagihan dan pembayaran Anda.</p>
    </div>
    <form method="GET">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="unpaid" @selected(request('status') === 'unpaid')>Belum Bayar</option>
        <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
        <option value="overdue" @selected(request('status') === 'overdue')>Terlambat</option>
        <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
      </select>
    </form>
  </div>

  <div class="d-flex flex-column gap-3">
    @forelse ($invoices as $invoice)
      <div class="card-public p-4 d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div class="min-w-0">
          <a href="{{ route('client.invoices.show', $invoice) }}" class="fw-semibold text-dark text-decoration-none" style="font-size:15px">
            {{ $invoice->invoice_number }}
          </a>
          <p class="text-muted mt-1 mb-0" style="font-size:11px">
            Terbit {{ $invoice->issue_date->format('d M Y') }} ·
            Jatuh tempo {{ $invoice->due_date->format('d M Y') }}
          </p>
        </div>

        <div class="d-flex align-items-center gap-3">
          <div class="text-end">
            <p class="fw-bold text-dark mb-0" style="font-size:15px">Rp {{ number_format($invoice->total, 0, ',', '.') }}</p>
            <span class="badge {{ $badgeMap[$invoice->is_overdue ? 'overdue' : $invoice->status] ?? 'badge-soft-secondary' }}">
              {{ $invoice->is_overdue ? 'Terlambat' : ucfirst($invoice->status) }}
            </span>
          </div>

          @if (in_array($invoice->status, ['unpaid', 'overdue']))
            <a href="{{ route('client.invoices.show', $invoice) }}" class="btn btn-theme">
              <i class="fa-solid fa-credit-card" style="font-size:11px"></i> Bayar
            </a>
          @else
            <a href="{{ route('client.invoices.show', $invoice) }}" class="btn btn-outline-secondary">Detail</a>
          @endif
        </div>
      </div>
    @empty
      <div class="card-public p-5 text-center">
        <p class="text-muted mb-0" style="font-size:14px">Belum ada invoice.</p>
      </div>
    @endforelse
  </div>

  @if ($invoices->hasPages())
    <div class="mt-4">{{ $invoices->links('pagination.bootstrap') }}</div>
  @endif
@endsection
