@php
  $domainStatusTabs = [
    ['label' => 'Semua', 'route' => 'admin.domains'],
    ['label' => 'Pending', 'route' => 'admin.domains.pending'],
    ['label' => 'Aktif', 'route' => 'admin.domains.active'],
    ['label' => 'Expired', 'route' => 'admin.domains.expired'],
    ['label' => 'Cancelled', 'route' => 'admin.domains.cancelled'],
  ];
@endphp

<div class="flex items-center gap-1 mb-5 overflow-x-auto">
  @foreach ($domainStatusTabs as $tab)
    <a href="{{ route($tab['route']) }}"
       class="px-3 py-1.5 text-xs font-medium rounded-full whitespace-nowrap transition-colors
              {{ request()->routeIs($tab['route']) ? 'bg-accent text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
      {{ $tab['label'] }}
    </a>
  @endforeach
</div>
