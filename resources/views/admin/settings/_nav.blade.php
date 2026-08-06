@php
  $settingTabs = [
    ['label' => 'Umum', 'route' => 'admin.settings.general'],
    ['label' => 'SEO', 'route' => 'admin.settings.seo'],
    ['label' => 'Analytics', 'route' => 'admin.settings.analytics'],
    ['label' => 'Notifikasi', 'route' => 'admin.settings.notifications'],
    ['label' => 'Keamanan', 'route' => 'admin.settings.security'],
    ['label' => 'Live Chat', 'route' => 'admin.settings.livechat'],
    ['label' => 'Cron Jobs', 'route' => 'admin.cron.index'],
  ];
@endphp

<div class="flex items-center gap-1 mb-5 border-b border-slate-200 overflow-x-auto">
  @foreach ($settingTabs as $tab)
    <a href="{{ route($tab['route']) }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px whitespace-nowrap transition-colors
              {{ request()->routeIs($tab['route']) ? 'border-accent text-accent' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
      {{ $tab['label'] }}
    </a>
  @endforeach
</div>
