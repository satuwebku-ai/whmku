@php
  $settingTabs = [
    ['label' => 'Umum', 'route' => 'admin.settings.general.bootstrap-preview'],
    ['label' => 'SEO', 'route' => 'admin.settings.seo.bootstrap-preview'],
    ['label' => 'Analytics', 'route' => 'admin.settings.analytics.bootstrap-preview'],
    ['label' => 'Notifikasi', 'route' => 'admin.settings.notifications.bootstrap-preview'],
    ['label' => 'Keamanan', 'route' => 'admin.settings.security.bootstrap-preview'],
    ['label' => 'Live Chat', 'route' => 'admin.settings.livechat.bootstrap-preview'],
    ['label' => 'Cron Jobs', 'route' => 'admin.cron.index.bootstrap-preview'],
  ];
@endphp

<div class="d-flex align-items-center gap-1 mb-4 border-bottom flex-wrap">
  @foreach ($settingTabs as $tab)
    <a href="{{ route($tab['route']) }}"
       class="px-3 py-2 small fw-medium text-decoration-none border-bottom border-2 {{ request()->routeIs(str_replace('.bootstrap-preview', '', $tab['route'])) ? 'border-primary text-accent' : 'border-transparent text-muted' }}">
      {{ $tab['label'] }}
    </a>
  @endforeach
</div>
