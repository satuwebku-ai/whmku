@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Selamat datang, {{ auth('admin')->user()->name }} 👋</h1>
    <p class="text-sm text-slate-500 mt-1">Ringkasan aktivitas hosting &amp; billing hari ini.</p>
  </div>

  {{-- Stat widgets --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @php
      $iconMap = [
        'users'     => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
        'server'    => 'M4 4h16v6H4zM4 14h16v6H4zM8 8h.01M8 18h.01',
        'clipboard' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11',
        'wallet'    => 'M2 7h20v10H2zM2 10h20M6 15h4',
      ];
      $colorMap = [
        'users' => 'bg-indigo-100 text-indigo-600',
        'server' => 'bg-emerald-100 text-emerald-600',
        'clipboard' => 'bg-amber-100 text-amber-600',
        'wallet' => 'bg-fuchsia-100 text-fuchsia-600',
      ];
    @endphp

    @foreach ($stats as $stat)
      <div class="card p-5">
        <div class="flex items-start justify-between mb-4">
          <div class="stat-icon {{ $colorMap[$stat['icon']] }}">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="{{ $iconMap[$stat['icon']] }}"/>
            </svg>
          </div>
          <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $stat['trend'] === 'up' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
            {{ $stat['delta'] }}
          </span>
        </div>
        <p class="text-2xl font-bold text-slate-800">{{ $stat['value'] }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ $stat['label'] }}</p>
      </div>
    @endforeach
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    {{-- Recent orders --}}
    <div class="xl:col-span-2 card overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Order Terbaru</h2>
        <a href="#" class="text-xs font-medium text-accent hover:underline">Lihat semua</a>
      </div>
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">ID</th>
            <th class="px-5 py-2.5 font-semibold">Klien</th>
            <th class="px-5 py-2.5 font-semibold">Produk</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach ($recentOrders as $order)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">{{ $order['id'] }}</td>
              <td class="px-5 py-3 text-slate-600">{{ $order['client'] }}</td>
              <td class="px-5 py-3 text-slate-600">{{ $order['product'] }}</td>
              <td class="px-5 py-3">
                <span class="badge badge-{{ $order['status'] }}">
                  {{ ucfirst($order['status']) }}
                </span>
              </td>
              <td class="px-5 py-3 text-right font-medium text-slate-700">{{ $order['total'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Quick actions --}}
    <div class="card p-5">
      <h2 class="text-sm font-semibold text-slate-800 mb-4">Aksi Cepat</h2>
      <div class="space-y-2">
        <a href="{{ route('admin.client.add.page') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-slate-100 hover:border-accent/30 hover:bg-accent/5 transition-colors text-sm text-slate-700">
          <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-user-plus text-xs"></i>
          </span>
          Tambah Klien Baru
        </a>
        <a href="{{ route('admin.hosting-account.add.page') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-slate-100 hover:border-accent/30 hover:bg-accent/5 transition-colors text-sm text-slate-700">
          <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-server text-xs"></i>
          </span>
          Buat Hosting Account
        </a>
        <a href="{{ route('admin.invoice.add.page') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-slate-100 hover:border-accent/30 hover:bg-accent/5 transition-colors text-sm text-slate-700">
          <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-file-invoice text-xs"></i>
          </span>
          Buat Invoice Manual
        </a>
        <a href="{{ route('admin.order.add.page') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-slate-100 hover:border-accent/30 hover:bg-accent/5 transition-colors text-sm text-slate-700">
          <span class="w-8 h-8 rounded-lg bg-fuchsia-100 text-fuchsia-600 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-cart-plus text-xs"></i>
          </span>
          Buat Order
        </a>
        <a href="{{ route('admin.domain.search') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-slate-100 hover:border-accent/30 hover:bg-accent/5 transition-colors text-sm text-slate-700">
          <span class="w-8 h-8 rounded-lg bg-cyan-100 text-cyan-600 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-globe text-xs"></i>
          </span>
          Cek Domain
        </a>
      </div>
    </div>
  </div>

  {{-- Tiket yang butuh perhatian --}}
  @if ($openTickets->isNotEmpty())
    <div class="card overflow-hidden mt-5">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Tiket Butuh Perhatian</h2>
        <a href="{{ route('admin.tickets') }}" class="text-xs font-medium text-accent hover:underline">Lihat semua</a>
      </div>
      <div class="divide-y divide-slate-100">
        @foreach ($openTickets as $ticket)
          <a href="{{ route('admin.tickets.details', $ticket) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50/60 text-sm">
            <div>
              <p class="font-medium text-slate-700">{{ $ticket->subject }}</p>
              <p class="text-xs text-slate-400">{{ $ticket->ticket_number }} · {{ $ticket->client->name ?? '—' }} · {{ $ticket->last_reply_at?->diffForHumans() }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <span class="badge badge-{{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span>
              <span class="badge badge-{{ $ticket->status_badge }}">{{ $ticket->status_label }}</span>
            </div>
          </a>
        @endforeach
      </div>
    </div>
  @endif

@endsection
