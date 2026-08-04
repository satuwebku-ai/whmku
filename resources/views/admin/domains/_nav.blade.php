@php
  $tabs = [
    ['label' => 'Domain Aktif', 'route' => 'admin.domains'],
    ['label' => 'Cek Domain', 'route' => 'admin.domain.search'],
    ['label' => 'TLD Pricing', 'route' => 'admin.tlds.index'],
    ['label' => 'Registrar', 'route' => 'admin.registrars.index'],
  ];
@endphp

<div class="flex items-center gap-1 mb-5 border-b border-slate-200">
  @foreach ($tabs as $tab)
    <a href="{{ route($tab['route']) }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors
              {{ request()->routeIs($tab['route']) ? 'border-accent text-accent' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
      {{ $tab['label'] }}
    </a>
  @endforeach
</div>
