@php
  $cmsTabs = [
    ['label' => 'Halaman', 'route' => 'admin.pages.bootstrap-preview'],
    ['label' => 'Pengumuman', 'route' => 'admin.announcements.bootstrap-preview'],
    ['label' => 'Menu Navigasi', 'route' => 'admin.nav-menus.bootstrap-preview'],
    ['label' => 'Banner Promo', 'route' => 'admin.promo-banners.index.bootstrap-preview'],
    ['label' => 'Banner Popup', 'route' => 'admin.popup-banner.edit.bootstrap-preview'],
  ];
@endphp

<div class="d-flex align-items-center gap-1 mb-4 border-bottom flex-wrap">
  @foreach ($cmsTabs as $tab)
    <a href="{{ route($tab['route']) }}"
       class="px-3 py-2 small fw-medium text-decoration-none border-bottom border-2 {{ request()->routeIs(str_replace('.bootstrap-preview', '', $tab['route'])) ? 'border-primary text-accent' : 'border-transparent text-muted' }}">
      {{ $tab['label'] }}
    </a>
  @endforeach
</div>
