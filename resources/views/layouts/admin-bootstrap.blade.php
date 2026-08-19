<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') — {{ config('app.name', 'Lumora Hosting') }} <span style="display:none">[Preview Bootstrap]</span></title>

<link rel="stylesheet" href="{{ asset('assets/css/framework.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/lumora-admin.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
  :root{ --bs-font-sans-serif: 'Inter', -apple-system, sans-serif; --bs-body-font-family: var(--bs-font-sans-serif); }
  html{ font-family: var(--bs-font-sans-serif); }
</style>
</head>
<body class="lumora-body">

<div class="d-flex min-vh-100">

  {{-- ══════════ Sidebar ══════════ --}}
  <aside id="sidebar" class="position-fixed top-0 start-0 bottom-0 d-flex flex-column border-end border-white border-opacity-10 bg-sidebar" style="width:272px;z-index:1040">

    <div class="d-flex align-items-center gap-3 px-4 border-bottom border-white border-opacity-10 flex-shrink-0" style="height:64px">
      @php
        $adminLogo = \App\Models\Setting::get('site_logo');
        $brandingDisplay = \App\Models\Setting::get('branding_display', 'logo_and_text');
      @endphp
      @if ($brandingDisplay !== 'text_only')
        @if ($adminLogo)
          <img src="{{ route('branding.file', $adminLogo) }}" alt="{{ config('app.name', 'Lumora Hosting') }}" style="height:40px;width:auto;object-fit:contain" class="flex-shrink-0">
        @else
          <div class="rounded-3 bg-accent d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;box-shadow:var(--shadow-rail)">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="M13 2 3 14h7l-1 8 11-12h-7l1-8z"/></svg>
          </div>
        @endif
      @endif
      @if ($brandingDisplay !== 'logo_only')
        <span class="brand-text text-white fw-bold text-nowrap" style="font-size:15px">{{ config('app.name', 'Lumora Hosting') }}</span>
      @endif
    </div>

    <nav class="sidebar-scroll flex-grow-1 overflow-y-auto px-3 py-4">
      <p class="menu-eyebrow text-uppercase small fw-semibold mb-3 mt-1" style="font-size:11px;letter-spacing:.14em;color:#64748b!important">Menu Utama</p>

      <ul class="nav flex-column gap-1" style="font-size:13.5px">
        @php
          // Daftar menu PERSIS sama dengan layouts/admin.blade.php (Tailwind) --
          // supaya kedua layout tetap konsisten selama masa transisi.
          $menu = [
            ['label' => 'Dashboard',        'route' => 'admin.dashboard',        'match' => ['admin.dashboard*'], 'icon' => 'M3 3h7v9H3zM14 3h7v5h-7zM14 10h7v11h-7zM3 14h7v7H3z'],
            ['label' => 'Produk',           'route' => 'admin.products.index',   'match' => ['admin.products.*', 'admin.product-categories.*', 'admin.product.*', 'admin.addons.*', 'admin.addon.*'], 'icon' => 'M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'admin_only' => true],
            ['label' => 'Klien',            'route' => 'admin.clients',          'match' => ['admin.client*'],    'icon' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'],
            ['label' => 'Order',            'route' => 'admin.orders',          'match' => ['admin.order*'],     'icon' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
            ['label' => 'Invoice',          'route' => 'admin.invoices',        'match' => ['admin.invoice*'],   'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6'],
            ['label' => 'Hosting Account',  'route' => 'admin.hosting-accounts', 'match' => ['admin.hosting-account*'], 'icon' => 'M22 12H2M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z'],
            ['label' => 'Domain',           'route' => 'admin.domains',         'match' => ['admin.domain*', 'admin.tlds.*', 'admin.registrars.*'],    'icon' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM2 12h20M12 2a15 15 0 0 1 4 10 15 15 0 0 1-4 10 15 15 0 0 1-4-10 15 15 0 0 1 4-10Z'],
            ['label' => 'Server',           'route' => 'admin.servers.index',   'match' => ['admin.servers*'],   'icon' => 'M4 4h16v6H4zM4 14h16v6H4zM8 8h.01M8 18h.01', 'admin_only' => true],
            ['label' => 'Pembayaran',       'route' => 'admin.payments', 'match' => ['admin.payment*', 'admin.gateway*'], 'icon' => 'M2 7h20v10H2zM2 10h20M6 15h4', 'admin_only' => true],
            ['label' => 'Admin & Akses',    'route' => 'admin.admins', 'match' => ['admin.admins*', 'admin.admin.*', 'admin.login-attempts*'], 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8.5 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM20 8v6M23 11h-6', 'superadmin' => true],
            ['label' => 'Live Chat',        'route' => 'admin.chats', 'match' => ['admin.chats*'], 'icon' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
            ['label' => 'Aktivitas',        'route' => 'admin.activities', 'match' => ['admin.activities*', 'admin.activity*', 'admin.promo*'], 'icon' => 'M13 2 3 14h7l-1 8 11-12h-7l1-8z'],
            ['label' => 'Kupon',            'route' => 'admin.coupons', 'match' => ['admin.coupon*'], 'icon' => 'M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2.59 12.59a2 2 0 0 1 0-2.83L9.76 2.59A2 2 0 0 1 11.17 2H18a2 2 0 0 1 2 2v6.83a2 2 0 0 1-.59 1.41ZM7 7h.01', 'admin_only' => true],
            ['label' => 'Support / Tiket',  'route' => 'admin.tickets', 'match' => ['admin.ticket*'], 'icon' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
            ['label' => 'Konten & Halaman', 'route' => 'admin.pages', 'match' => ['admin.page*', 'admin.announcement*', 'admin.promo-banners.*', 'admin.popup-banner.*'], 'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15Z', 'admin_only' => true],
            ['label' => 'Template Notifikasi', 'route' => 'admin.notification-templates.index', 'match' => ['admin.notification-templates.*'], 'icon' => 'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2ZM22 6l-10 7L2 6', 'admin_only' => true],
            ['label' => 'Pengaturan',       'route' => 'admin.settings.general', 'match' => ['admin.settings.*'], 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6 1.65 1.65 0 0 0 10 3.09V3a2 2 0 0 1 4 0v.09c0 .67.39 1.28 1 1.51.63.24 1.35.12 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06c-.45.47-.57 1.19-.33 1.82.23.61.84 1 1.51 1H21a2 2 0 0 1 0 4h-.09c-.67 0-1.28.39-1.51 1Z', 'admin_only' => true],
            ['label' => 'Backup',           'route' => 'admin.backups.index', 'match' => ['admin.backups.*'], 'icon' => 'M12 3C7 3 3 4.5 3 6.5V17.5C3 19.5 7 21 12 21C17 21 21 19.5 21 17.5V6.5C21 4.5 17 3 12 3ZM3 6.5C3 8.5 7 10 12 10C17 10 21 8.5 21 6.5M3 12C3 14 7 15.5 12 15.5C17 15.5 21 14 21 12', 'admin_only' => true],
            ['label' => 'Konsol Web',       'route' => 'admin.console.index', 'match' => ['admin.console.*'], 'icon' => 'M4 17l6-6-6-6M12 19h8', 'admin_only' => true],
          ];
        @endphp

        @foreach ($menu as $item)
          @continue(!empty($item['superadmin']) && ! auth('admin')->user()?->isSuperadmin())
          @continue(!empty($item['admin_only']) && ! auth('admin')->user()?->canManage())

          <li class="menu-item">
            <a href="{{ $item['route'] ? route($item['route']) : '#' }}" data-label="{{ $item['label'] }}"
               class="nav-item-link w-100 btn d-flex align-items-center gap-3 px-3 py-2 rounded-3 border-0 text-start text-decoration-none
                      {{ $item['match'] && request()->routeIs(...$item['match']) ? 'active text-white' : 'text-white-50' }}">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0">
                <path d="{{ $item['icon'] }}"/>
              </svg>
              <span class="label-text text-nowrap">{{ $item['label'] }}</span>
              @if ($item['route'] === 'admin.chats')
                <span id="chatSidebarBadge" class="d-none label-text ms-auto badge rounded-pill bg-danger" style="font-size:10px">0</span>
              @endif
            </a>
          </li>
        @endforeach
      </ul>
    </nav>

    <div class="help-box px-4 py-3 m-3 mt-0 rounded-3 border border-white border-opacity-10 flex-shrink-0" style="background:rgba(255,255,255,.05)">
      <p class="text-white fw-semibold mb-1" style="font-size:13px">Butuh Bantuan?</p>
      <p class="text-white-50 mb-0" style="font-size:11px;line-height:1.6">Modul hosting &amp; billing lain akan ditambahkan bertahap.</p>
    </div>
  </aside>

  <div id="sidebarBackdrop"></div>

  {{-- ══════════ Main content ══════════ --}}
  <div id="main" class="main-shifted flex-grow-1 min-vh-100 d-flex flex-column" style="padding-top:64px;overflow-x:hidden;min-width:0">

    <header id="topbar" class="d-flex align-items-center justify-content-between px-4 position-fixed top-0 end-0 bg-topbar" style="height:64px;left:272px;z-index:1041">
      <div class="d-flex align-items-center gap-2">
        <button id="collapseBtn" class="topbar-icon-btn btn d-flex align-items-center justify-content-center">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <button id="mobileMenuBtn" class="topbar-icon-btn btn align-items-center justify-content-center" style="display:none">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>

        <div class="d-none d-sm-block">
          <div class="d-flex align-items-center gap-2 rounded-3 px-3 py-2" style="background:rgba(255,255,255,.1);width:16rem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-white-50 flex-shrink-0"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" placeholder="Cari klien, order, invoice..." class="bg-transparent border-0 text-white small w-100" style="outline:none">
          </div>
        </div>
      </div>

      <div class="d-flex align-items-center gap-1">
        @php $unreadActivities = \App\Models\ActivityLog::unread()->count(); @endphp

        <a href="{{ route('admin.activities') }}" class="topbar-icon-btn btn d-flex align-items-center justify-content-center position-relative"
           title="{{ $unreadActivities > 0 ? $unreadActivities . ' notifikasi belum dibaca' : 'Aktivitas' }}">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          @if ($unreadActivities > 0)
            <span class="position-absolute badge rounded-pill bg-danger" style="top:2px;right:2px;font-size:10px">
              {{ $unreadActivities > 99 ? '99+' : $unreadActivities }}
            </span>
          @endif
        </a>

        <div class="dropdown">
          <button class="btn d-flex align-items-center gap-2 px-2 py-1 rounded-3" type="button" data-bs-toggle="dropdown" style="background:transparent;border:0">
            <img src="{{ auth('admin')->user()->avatar_url }}" class="rounded-circle" style="width:32px;height:32px;border:2px solid rgba(255,255,255,.2)" alt="avatar">
            <span class="d-none d-md-block text-start">
              <span class="d-block text-white fw-semibold lh-sm" style="font-size:13px">{{ auth('admin')->user()->name }}</span>
              <span class="d-block text-white-50 text-capitalize lh-sm" style="font-size:11px">{{ auth('admin')->user()->role }}</span>
            </span>
          </button>
          <div class="dropdown-menu dropdown-menu-end rounded-3 overflow-hidden" style="width:14rem">
            <div class="px-3 py-2 border-bottom">
              <p class="mb-0 small fw-semibold text-dark">{{ auth('admin')->user()->name }}</p>
              <p class="mb-0 text-muted" style="font-size:11px">{{ auth('admin')->user()->email }}</p>
            </div>
            <a href="{{ route('admin.profile') }}" class="dropdown-item d-flex align-items-center gap-2 small py-2">
              <i class="fa-regular fa-user text-muted"></i> Profil Saya
            </a>
            <a href="{{ route('admin.profile') }}" class="dropdown-item d-flex align-items-center gap-2 small py-2">
              <i class="fa-solid fa-shield-halved text-muted"></i> Keamanan &amp; 2FA
            </a>
            <div class="border-top">
              <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 small py-2 text-danger">
                  <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow-1 p-4">
      @yield('content')
    </main>
  </div>
</div>

<script src="{{ asset('assets/js/framework.js') }}"></script>
<script>
  document.getElementById('collapseBtn')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
    document.getElementById('main').classList.toggle('collapsed');
    document.getElementById('topbar').style.left = document.getElementById('sidebar').classList.contains('sidebar-collapsed') ? '84px' : '272px';
  });

  // Mobile: sidebar off-canvas + backdrop.
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const sidebarEl = document.getElementById('sidebar');
  const backdropEl = document.getElementById('sidebarBackdrop');

  function toggleMobileSidebar(open) {
    sidebarEl.classList.toggle('mobile-open', open);
    backdropEl.classList.toggle('visible', open);
  }
  mobileBtn?.addEventListener('click', () => toggleMobileSidebar(! sidebarEl.classList.contains('mobile-open')));
  backdropEl?.addEventListener('click', () => toggleMobileSidebar(false));
</script>

</body>
</html>
