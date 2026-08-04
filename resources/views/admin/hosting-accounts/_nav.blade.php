@php
  $haTabs = [
    ['label' => 'Semua', 'route' => 'admin.hosting-accounts'],
    ['label' => 'Pending', 'route' => 'admin.hosting-accounts.pending'],
    ['label' => 'Aktif', 'route' => 'admin.hosting-accounts.active'],
    ['label' => 'Suspended', 'route' => 'admin.hosting-accounts.suspended'],
    ['label' => 'Terminated', 'route' => 'admin.hosting-accounts.terminated'],
  ];
@endphp

<div class="flex items-center gap-1 mb-5 border-b border-slate-200 overflow-x-auto">
  @foreach ($haTabs as $tab)
    <a href="{{ route($tab['route']) }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px whitespace-nowrap transition-colors
              {{ request()->routeIs($tab['route']) ? 'border-accent text-accent' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
      {{ $tab['label'] }}
    </a>
  @endforeach
</div>
