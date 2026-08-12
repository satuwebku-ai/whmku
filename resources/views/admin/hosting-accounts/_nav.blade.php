@php
  $haTabs = [
    ['label' => 'Semua', 'route' => 'admin.hosting-accounts'],
    ['label' => 'Pending', 'route' => 'admin.hosting-accounts.pending'],
    ['label' => 'Aktif', 'route' => 'admin.hosting-accounts.active'],
    ['label' => 'Suspended', 'route' => 'admin.hosting-accounts.suspended'],
    ['label' => 'Terminated', 'route' => 'admin.hosting-accounts.terminated'],
  ];
  $unlinkedCount = \App\Models\HostingAccount::where('status', 'active')->whereNull('product_id')->count();
@endphp

<div class="flex items-center gap-1 mb-5 border-b border-slate-200 overflow-x-auto">
  @foreach ($haTabs as $tab)
    <a href="{{ route($tab['route']) }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px whitespace-nowrap transition-colors
              {{ request()->routeIs($tab['route']) ? 'border-accent text-accent' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
      {{ $tab['label'] }}
    </a>
  @endforeach
  @if ($unlinkedCount > 0)
    <a href="{{ route('admin.hosting-accounts.unlinked') }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px whitespace-nowrap transition-colors
              {{ request()->routeIs('admin.hosting-accounts.unlinked') ? 'border-accent text-accent' : 'border-transparent text-amber-600 hover:text-amber-700' }}">
      Belum Tertaut <span class="badge badge-pending !text-[10px] ml-0.5">{{ $unlinkedCount }}</span>
    </a>
  @endif
</div>
