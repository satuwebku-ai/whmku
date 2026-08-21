@extends('client.layout-bootstrap')
@section('title', 'Tiket Support')

@section('content')
  @php
    $badgeMap = ['active' => 'badge-soft-success', 'pending' => 'badge-soft-warning', 'inactive' => 'badge-soft-secondary', 'suspended' => 'badge-soft-danger'];
  @endphp

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
      <h1 class="h4 fw-bold text-dark mb-1">Tiket Support</h1>
      <p class="text-muted mb-0">Riwayat percakapan Anda dengan tim kami.</p>
    </div>
    <a href="{{ route('client.tickets.create.bootstrap-preview') }}" class="btn btn-theme">
      <i class="fa-solid fa-plus" style="font-size:11px"></i> Buat Tiket
    </a>
  </div>

  <div class="d-flex gap-2 mb-4">
    @php $s = request('status'); @endphp
    <a href="{{ route('client.tickets.bootstrap-preview') }}" class="px-3 py-2 rounded-pill text-decoration-none" style="font-size:12px;font-weight:500;{{ !$s ? 'background:var(--lumora-theme);color:#fff' : 'background:#fff;border:1px solid #e2e8f0;color:#475569' }}">Semua</a>
    <a href="{{ route('client.tickets.bootstrap-preview', ['status' => 'open']) }}" class="px-3 py-2 rounded-pill text-decoration-none" style="font-size:12px;font-weight:500;{{ $s === 'open' ? 'background:var(--lumora-theme);color:#fff' : 'background:#fff;border:1px solid #e2e8f0;color:#475569' }}">Aktif</a>
    <a href="{{ route('client.tickets.bootstrap-preview', ['status' => 'closed']) }}" class="px-3 py-2 rounded-pill text-decoration-none" style="font-size:12px;font-weight:500;{{ $s === 'closed' ? 'background:var(--lumora-theme);color:#fff' : 'background:#fff;border:1px solid #e2e8f0;color:#475569' }}">Ditutup</a>
  </div>

  <div class="d-flex flex-column gap-3">
    @forelse ($tickets as $ticket)
      <a href="{{ route('client.tickets.show.bootstrap-preview', $ticket) }}" class="card-public p-4 d-flex align-items-center justify-content-between gap-3 text-decoration-none">
        <div class="min-w-0">
          <p class="fw-semibold text-dark text-truncate mb-0">{{ $ticket->subject }}</p>
          <p class="text-muted mt-1 mb-0" style="font-size:11px">
            {{ $ticket->ticket_number }} · {{ $ticket->public_replies_count }} pesan ·
            update {{ $ticket->last_reply_at?->diffForHumans() }}
          </p>
        </div>
        <div class="text-end flex-shrink-0">
          <span class="badge {{ $badgeMap[$ticket->status_badge] ?? 'badge-soft-secondary' }}">{{ $ticket->status_label }}</span>
        </div>
      </a>
    @empty
      <div class="card-public p-5 text-center">
        <p class="text-muted mb-3" style="font-size:14px">Belum ada tiket.</p>
        <a href="{{ route('client.tickets.create.bootstrap-preview') }}" class="btn btn-theme">Buat Tiket Pertama</a>
      </div>
    @endforelse
  </div>

  @if ($tickets->hasPages())
    <div class="mt-4">{{ $tickets->links('pagination.bootstrap') }}</div>
  @endif
@endsection
