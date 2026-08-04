@extends('client.layout')

@section('title', 'Dashboard')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Halo, {{ $client->name }} 👋</h1>
    <p class="text-sm text-slate-500 mt-1">Berikut ringkasan layanan Anda.</p>
  </div>

  {{-- Tagihan menunggak tampil paling menonjol --}}
  @if ($stats['unpaidInvoices'] > 0)
    <div class="card p-5 mb-5 border-amber-200 bg-amber-50/60">
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <p class="text-sm font-semibold text-amber-800">
            <i class="fa-solid fa-circle-exclamation"></i>
            Anda punya {{ $stats['unpaidInvoices'] }} invoice belum dibayar
          </p>
          <p class="text-2xl font-bold text-slate-800 mt-1">Rp {{ number_format($unpaidTotal, 0, ',', '.') }}</p>
        </div>
        <a href="{{ route('client.invoices', ['status' => 'unpaid']) }}" class="btn btn-primary">
          <i class="fa-solid fa-credit-card text-xs"></i> Bayar Sekarang
        </a>
      </div>
    </div>
  @endif

  {{-- Statistik --}}
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    @php
      $cards = [
        ['label' => 'Layanan Aktif', 'value' => $stats['services'], 'icon' => 'fa-server', 'color' => 'bg-indigo-100 text-indigo-600', 'route' => 'client.services'],
        ['label' => 'Domain Aktif', 'value' => $stats['domains'], 'icon' => 'fa-globe', 'color' => 'bg-cyan-100 text-cyan-600', 'route' => 'client.domains'],
        ['label' => 'Invoice Belum Bayar', 'value' => $stats['unpaidInvoices'], 'icon' => 'fa-file-invoice', 'color' => 'bg-amber-100 text-amber-600', 'route' => 'client.invoices'],
        ['label' => 'Tiket Terbuka', 'value' => $stats['openTickets'], 'icon' => 'fa-comments', 'color' => 'bg-emerald-100 text-emerald-600', 'route' => 'client.tickets'],
      ];
    @endphp

    @foreach ($cards as $card)
      <a href="{{ route($card['route']) }}" class="card p-5 hover:border-accent/40 transition-colors">
        <span class="w-10 h-10 rounded-xl {{ $card['color'] }} flex items-center justify-center mb-3">
          <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
        </span>
        <p class="text-2xl font-bold text-slate-800">{{ $card['value'] }}</p>
        <p class="text-xs text-slate-500 mt-0.5">{{ $card['label'] }}</p>
      </a>
    @endforeach
  </div>

  <div class="grid lg:grid-cols-3 gap-5">

    <div class="lg:col-span-2 space-y-5">
      {{-- Invoice terbaru --}}
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-800">Invoice Terbaru</h2>
          <a href="{{ route('client.invoices') }}" class="text-xs font-medium text-accent hover:underline">Lihat semua</a>
        </div>

        @if ($recentInvoices->isEmpty())
          <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada invoice.</p>
        @else
          <div class="divide-y divide-slate-100">
            @foreach ($recentInvoices as $invoice)
              <a href="{{ route('client.invoices.show', $invoice) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50/60">
                <div>
                  <p class="text-sm font-medium text-slate-700">{{ $invoice->invoice_number }}</p>
                  <p class="text-xs text-slate-400">Jatuh tempo {{ $invoice->due_date->format('d M Y') }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-semibold text-slate-700">Rp {{ number_format($invoice->total, 0, ',', '.') }}</p>
                  <span class="badge badge-{{ $invoice->is_overdue ? 'overdue' : $invoice->status }}">
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
        <div class="card overflow-hidden border-amber-200">
          <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-800">
              <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
              Domain Akan Segera Kedaluwarsa
            </h2>
          </div>
          <div class="divide-y divide-slate-100">
            @foreach ($expiringSoon as $domain)
              <a href="{{ route('client.domains.show', $domain) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50/60">
                <span class="text-sm font-medium text-slate-700">{{ $domain->domain_name }}</span>
                <span class="text-xs text-amber-600 font-medium">
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
    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Pengumuman</h2>
        @if ($announcements->isEmpty())
          <p class="text-sm text-slate-400">Belum ada pengumuman.</p>
        @else
          <div class="space-y-3">
            @foreach ($announcements as $item)
              <a href="{{ route('announcements.show', $item->slug) }}" target="_blank" class="block group">
                <span class="badge badge-{{ $item->category_badge }} capitalize mb-1">{{ $item->category }}</span>
                <p class="text-sm font-medium text-slate-700 group-hover:text-accent leading-snug">{{ $item->title }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $item->published_at?->diffForHumans() }}</p>
              </a>
            @endforeach
          </div>
        @endif
      </div>

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Butuh Bantuan?</h2>
        <p class="text-sm text-slate-500 mb-3">Tim support kami siap membantu masalah teknis maupun tagihan.</p>
        <a href="{{ route('client.tickets.create') }}" class="btn btn-primary w-full">
          <i class="fa-solid fa-plus text-xs"></i> Buat Tiket Support
        </a>
      </div>
    </div>
  </div>

@endsection
