@extends('client.layout-bootstrap')

@section('title', 'Dashboard')

@section('content')

  @php
    $badgeMap = [
      'active' => 'badge-soft-success', 'paid' => 'badge-soft-success',
      'pending' => 'badge-soft-warning', 'unpaid' => 'badge-soft-warning', 'answered' => 'badge-soft-warning',
      'suspended' => 'badge-soft-danger', 'overdue' => 'badge-soft-danger', 'expired' => 'badge-soft-danger',
      'inactive' => 'badge-soft-secondary', 'closed' => 'badge-soft-secondary', 'cancelled' => 'badge-soft-secondary', 'terminated' => 'badge-soft-secondary',
    ];
  @endphp

  <div class="mb-4">
    <h1 class="h4 fw-bold text-dark mb-1">Halo, {{ $client->name }} 👋</h1>
    <p class="text-muted mb-0">Berikut ringkasan layanan Anda.</p>
  </div>

  {{-- Tagihan menunggak tampil paling menonjol --}}
  @if ($stats['unpaidInvoices'] > 0)
    <div class="card-public p-4 mb-4" style="border-color:#fde68a!important;background:#fffbeb">
      <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
          <p class="fw-semibold mb-0" style="font-size:14px;color:#92400e">
            <i class="fa-solid fa-circle-exclamation"></i>
            Anda punya {{ $stats['unpaidInvoices'] }} invoice belum dibayar
          </p>
          <p class="fw-bold text-dark mt-1 mb-0" style="font-size:1.5rem">Rp {{ number_format($unpaidTotal, 0, ',', '.') }}</p>
        </div>
        <a href="{{ route('client.invoices.bootstrap-preview', ['status' => 'unpaid']) }}" class="btn btn-theme">
          <i class="fa-solid fa-credit-card" style="font-size:12px"></i> Bayar Sekarang
        </a>
      </div>
    </div>
  @endif

  {{-- Statistik --}}
  <div class="row g-3 mb-4">
    @php
      $cards = [
        ['label' => 'Layanan Aktif', 'value' => $stats['services'], 'icon' => 'fa-server', 'bg' => 'rgba(79,70,229,.1)', 'fg' => '#4f46e5', 'route' => 'client.services.bootstrap-preview'],
        ['label' => 'Domain Aktif', 'value' => $stats['domains'], 'icon' => 'fa-globe', 'bg' => 'rgba(6,182,212,.1)', 'fg' => '#0891b2', 'route' => 'client.domains.bootstrap-preview'],
        ['label' => 'Invoice Belum Bayar', 'value' => $stats['unpaidInvoices'], 'icon' => 'fa-file-invoice', 'bg' => 'rgba(245,158,11,.1)', 'fg' => '#b45309', 'route' => 'client.invoices.bootstrap-preview'],
        ['label' => 'Tiket Terbuka', 'value' => $stats['openTickets'], 'icon' => 'fa-comments', 'bg' => 'rgba(16,185,129,.1)', 'fg' => '#047857', 'route' => 'client.tickets.bootstrap-preview'],
      ];
    @endphp

    @foreach ($cards as $card)
      <div class="col-6 col-lg-3">
        <a href="{{ route($card['route']) }}" class="card-public p-4 text-decoration-none d-block h-100">
          <span class="rounded-4 d-flex align-items-center justify-content-center mb-3" style="width:40px;height:40px;background:{{ $card['bg'] }};color:{{ $card['fg'] }}">
            <i class="fa-solid {{ $card['icon'] }}" style="font-size:14px"></i>
          </span>
          <p class="fw-bold text-dark mb-0" style="font-size:1.5rem">{{ $card['value'] }}</p>
          <p class="text-muted mb-0 mt-1" style="font-size:12px">{{ $card['label'] }}</p>
        </a>
      </div>
    @endforeach
  </div>

  <div class="row g-4">

    <div class="col-12 col-lg-8 d-flex flex-column gap-4">
      {{-- Invoice terbaru --}}
      <div class="card-public overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
          <h2 class="small fw-bold text-dark mb-0">Invoice Terbaru</h2>
          <a href="{{ route('client.invoices.bootstrap-preview') }}" class="text-decoration-none text-theme fw-medium" style="font-size:12px">Lihat semua</a>
        </div>

        @if ($recentInvoices->isEmpty())
          <p class="text-center text-muted py-4 mb-0" style="font-size:14px">Belum ada invoice.</p>
        @else
          <div>
            @foreach ($recentInvoices as $invoice)
              <a href="{{ route('client.invoices.show.bootstrap-preview', $invoice) }}" class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom text-decoration-none">
                <div>
                  <p class="fw-medium text-dark mb-0" style="font-size:14px">{{ $invoice->invoice_number }}</p>
                  <p class="text-muted mb-0" style="font-size:11px">Jatuh tempo {{ $invoice->due_date->format('d M Y') }}</p>
                </div>
                <div class="text-end">
                  <p class="fw-semibold text-dark mb-0" style="font-size:14px">Rp {{ number_format($invoice->total, 0, ',', '.') }}</p>
                  <span class="badge {{ $badgeMap[$invoice->is_overdue ? 'overdue' : $invoice->status] ?? 'badge-soft-secondary' }}">
                    {{ $invoice->is_overdue ? 'Terlambat' : ucfirst($invoice->status) }}
                  </span>
                </div>
              </a>
            @endforeach
          </div>
        @endif
      </div>

      {{-- Domain segera habis --}}
      @if ($expiringSoon->isNotEmpty())
        <div class="card-public overflow-hidden" style="border-color:#fde68a!important">
          <div class="px-4 py-3 border-bottom">
            <h2 class="small fw-bold text-dark mb-0">
              <i class="fa-solid fa-triangle-exclamation text-warning"></i>
              Domain Akan Segera Kedaluwarsa
            </h2>
          </div>
          <div>
            @foreach ($expiringSoon as $domain)
              <a href="{{ route('client.domains.show.bootstrap-preview', $domain) }}" class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom text-decoration-none">
                <span class="fw-medium text-dark" style="font-size:14px">{{ $domain->domain_name }}</span>
                <span class="fw-medium" style="font-size:12px;color:#b45309">
                  {{ $domain->expiry_date->format('d M Y') }}
                  ({{ (int) now()->diffInDays($domain->expiry_date) }} hari lagi)
                </span>
              </a>
            @endforeach
          </div>
        </div>
      @endif
    </div>

    {{-- Pengumuman --}}
    <div class="col-12 col-lg-4 d-flex flex-column gap-4">
      <div class="card-public p-4">
        <h2 class="small fw-bold text-dark mb-3">Pengumuman</h2>
        @if ($announcements->isEmpty())
          <p class="text-muted mb-0" style="font-size:14px">Belum ada pengumuman.</p>
        @else
          <div class="d-flex flex-column gap-3">
            @foreach ($announcements as $item)
              <a href="{{ route('announcements.show', $item->slug) }}" target="_blank" class="text-decoration-none">
                <span class="badge {{ $badgeMap[$item->category] ?? 'badge-soft-secondary' }} text-capitalize mb-1 d-inline-block">{{ $item->category }}</span>
                <p class="fw-medium text-dark mb-0" style="font-size:14px;line-height:1.4">{{ $item->title }}</p>
                <p class="text-muted mb-0 mt-1" style="font-size:11px">{{ $item->published_at?->diffForHumans() }}</p>
              </a>
            @endforeach
          </div>
        @endif
      </div>

      <div class="card-public p-4">
        <h2 class="small fw-bold text-dark mb-2">Butuh Bantuan?</h2>
        <p class="text-muted mb-3" style="font-size:14px">Tim support kami siap membantu masalah teknis maupun tagihan.</p>
        <a href="{{ route('client.tickets.create.bootstrap-preview') }}" class="btn btn-theme w-100">
          <i class="fa-solid fa-plus" style="font-size:12px"></i> Buat Tiket Support
        </a>
      </div>
    </div>
  </div>

@endsection
