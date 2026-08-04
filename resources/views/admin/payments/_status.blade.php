@php
  $paymentStatusTabs = [
    ['label' => 'Semua', 'route' => 'admin.payments'],
    ['label' => 'Menunggu Bayar', 'route' => 'admin.payments.initiated'],
    ['label' => 'Perlu Verifikasi', 'route' => 'admin.payments.pending'],
    ['label' => 'Lunas', 'route' => 'admin.payments.paid'],
    ['label' => 'Gagal', 'route' => 'admin.payments.failed'],
    ['label' => 'Refund', 'route' => 'admin.payments.refunded'],
  ];
@endphp

<div class="flex items-center gap-1 mb-5 overflow-x-auto">
  @foreach ($paymentStatusTabs as $tab)
    <a href="{{ route($tab['route']) }}"
       class="px-3 py-1.5 text-xs font-medium rounded-full whitespace-nowrap transition-colors
              {{ request()->routeIs($tab['route']) ? 'bg-accent text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
      {{ $tab['label'] }}
    </a>
  @endforeach
</div>
