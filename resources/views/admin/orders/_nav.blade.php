@php
  $orderTabs = [
    ['label' => 'Semua', 'route' => 'admin.orders', 'count' => null],
    ['label' => 'Pending', 'route' => 'admin.orders.pending'],
    ['label' => 'Aktif', 'route' => 'admin.orders.active'],
    ['label' => 'Suspended', 'route' => 'admin.orders.suspended'],
    ['label' => 'Cancelled', 'route' => 'admin.orders.cancelled'],
  ];
@endphp

<div class="flex items-center gap-1 mb-5 border-b border-slate-200 overflow-x-auto">
  @foreach ($orderTabs as $tab)
    <a href="{{ route($tab['route']) }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px whitespace-nowrap transition-colors
              {{ request()->routeIs($tab['route']) ? 'border-accent text-accent' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
      {{ $tab['label'] }}
    </a>
  @endforeach
</div>
