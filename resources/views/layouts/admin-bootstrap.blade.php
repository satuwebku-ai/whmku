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
<script>
  try{ if(localStorage.getItem('lumora-sidebar-collapsed')==='1'){ document.documentElement.classList.add('sidebar-pre-collapsed'); } }catch(e){}
</script>

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
          // Dikelompokkan berdasarkan fungsi -- item dengan 'route'
          // adalah tautan tunggal, item dengan 'children' adalah grup
          // yang punya submenu.
          $groups = [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard*'], 'icon' => 'M3 3h7v9H3zM14 3h7v5h-7zM14 10h7v11h-7zM3 14h7v7H3z'],

            ['label' => 'Penjualan', 'icon' => 'M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'admin_only' => true, 'children' => [
              ['label' => 'Produk', 'route' => 'admin.products.index', 'match' => ['admin.products.*', 'admin.product-categories.*', 'admin.product.*', 'admin.addons.*', 'admin.addon.*']],
              ['label' => 'Order',  'route' => 'admin.orders', 'match' => ['admin.order*']],
              ['label' => 'Kupon',  'route' => 'admin.coupons', 'match' => ['admin.coupon*']],
            ]],

            ['label' => 'Billing', 'icon' => 'M2 7h20v10H2zM2 10h20M6 15h4', 'admin_only' => true, 'children' => [
              ['label' => 'Invoice',     'route' => 'admin.invoices', 'match' => ['admin.invoice*']],
              ['label' => 'Pembayaran',  'route' => 'admin.payments', 'match' => ['admin.payment*', 'admin.gateway*']],
            ]],

            ['label' => 'Layanan', 'icon' => 'M22 12H2M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z', 'children' => [
              ['label' => 'Klien',            'route' => 'admin.clients', 'match' => ['admin.client*']],
              ['label' => 'Hosting Account',  'route' => 'admin.hosting-accounts', 'match' => ['admin.hosting-account*']],
              ['label' => 'Domain',           'route' => 'admin.domains', 'match' => ['admin.domain*', 'admin.tlds.*', 'admin.registrars.*']],
            ]],

            ['label' => 'Infrastruktur', 'icon' => 'M4 4h16v6H4zM4 14h16v6H4zM8 8h.01M8 18h.01', 'admin_only' => true, 'children' => [
              ['label' => 'Server',      'route' => 'admin.servers.index', 'match' => ['admin.servers*']],
              ['label' => 'Backup',      'route' => 'admin.backups.index', 'match' => ['admin.backups.*']],
              ['label' => 'Konsol Web',  'route' => 'admin.console.index', 'match' => ['admin.console.*']],
            ]],

            ['label' => 'Dukungan', 'icon' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z', 'children' => [
              ['label' => 'Live Chat',        'route' => 'admin.chats', 'match' => ['admin.chats*'], 'chat_badge' => true],
              ['label' => 'Support / Tiket',  'route' => 'admin.tickets', 'match' => ['admin.ticket*']],
            ]],

            ['label' => 'Konten', 'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15Z', 'admin_only' => true, 'children' => [
              ['label' => 'Konten & Halaman',     'route' => 'admin.pages', 'match' => ['admin.page*', 'admin.announcement*', 'admin.promo-banners.*', 'admin.popup-banner.*']],
              ['label' => 'Template Notifikasi',  'route' => 'admin.notification-templates.index', 'match' => ['admin.notification-templates.*']],
            ]],

            ['label' => 'Sistem', 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6 1.65 1.65 0 0 0 10 3.09V3a2 2 0 0 1 4 0v.09c0 .67.39 1.28 1 1.51.63.24 1.35.12 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06c-.45.47-.57 1.19-.33 1.82.23.61.84 1 1.51 1H21a2 2 0 0 1 0 4h-.09c-.67 0-1.28.39-1.51 1Z', 'admin_only' => true, 'children' => [
              ['label' => 'Admin & Akses', 'route' => 'admin.admins', 'match' => ['admin.admins*', 'admin.admin.*', 'admin.login-attempts*'], 'superadmin' => true],
              ['label' => 'Aktivitas',     'route' => 'admin.activities', 'match' => ['admin.activities*', 'admin.activity*', 'admin.promo*']],
              ['label' => 'Pengaturan',    'route' => 'admin.settings.general', 'match' => ['admin.settings.*']],
            ]],
          ];

          // Grup terbuka otomatis kalau salah satu anaknya sedang aktif
          // -- supaya klien langsung lihat konteksnya tanpa perlu klik.
          $isChildActive = fn ($children) => collect($children)->contains(
              fn ($c) => $c['match'] && request()->routeIs(...$c['match'])
          );
        @endphp

        @foreach ($groups as $group)
          @continue(!empty($group['superadmin']) && ! auth('admin')->user()?->isSuperadmin())
          @continue(!empty($group['admin_only']) && ! auth('admin')->user()?->canManage())

          @if (isset($group['route']))
            {{-- Item tunggal, tanpa submenu (mis. Dashboard) --}}
            <li class="menu-item">
              <a href="{{ route($group['route']) }}" data-label="{{ $group['label'] }}"
                 class="nav-item-link w-100 btn d-flex align-items-center gap-3 px-3 py-2 rounded-3 border-0 text-start text-decoration-none
                        {{ $group['match'] && request()->routeIs(...$group['match']) ? 'active text-white' : 'text-white-50' }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="{{ $group['icon'] }}"/></svg>
                <span class="label-text text-nowrap">{{ $group['label'] }}</span>
              </a>
            </li>
          @else
            @php
              // Anak dengan superadmin/admin_only sendiri difilter dulu,
              // supaya grup yang SEMUA anaknya tersembunyi tidak
              // menampilkan grup kosong tanpa isi.
              $visibleChildren = collect($group['children'])->filter(function ($c) {
                  if (!empty($c['superadmin']) && ! auth('admin')->user()?->isSuperadmin()) return false;
                  if (!empty($c['admin_only']) && ! auth('admin')->user()?->canManage()) return false;
                  return true;
              });
            @endphp
            @continue($visibleChildren->isEmpty())

            @php $groupActive = $isChildActive($visibleChildren); @endphp
            <li class="menu-item {{ $groupActive ? 'open' : '' }}">
              <button type="button" data-label="{{ $group['label'] }}" aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                      class="menu-trigger nav-item-link w-100 btn d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-3 border-0 text-start
                             {{ $groupActive ? 'active text-white' : 'text-white-50' }}">
                <span class="d-flex align-items-center gap-3">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="{{ $group['icon'] }}"/></svg>
                  <span class="label-text text-nowrap">{{ $group['label'] }}</span>
                </span>
                <svg class="chevron flex-shrink-0" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
              </button>
              <ul class="submenu nav flex-column ms-2 ps-3 border-start border-white border-opacity-10 {{ $groupActive ? 'open' : '' }}">
                @foreach ($visibleChildren as $child)
                  <li>
                    <a href="{{ route($child['route']) }}"
                       class="nav-link px-3 py-2 rounded-2 d-flex align-items-center justify-content-between {{ $child['match'] && request()->routeIs(...$child['match']) ? 'active' : '' }}">
                      {{ $child['label'] }}
                      @if (! empty($child['chat_badge']))
                        <span id="chatSidebarBadge" class="d-none badge rounded-pill bg-danger" style="font-size:10px">0</span>
                      @endif
                    </a>
                  </li>
                @endforeach
              </ul>
            </li>
          @endif
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
  const sidebarEl = document.getElementById('sidebar');
  const mainEl = document.getElementById('main');
  const topbarEl = document.getElementById('topbar');

  // Status collapse DIINGAT lewat localStorage -- supaya tidak balik ke
  // kondisi awal tiap kali pindah halaman (karena ini website biasa,
  // tiap klik menu = reload halaman baru, bukan single-page app).
  if (localStorage.getItem('lumora-sidebar-collapsed') === '1') {
    sidebarEl.classList.add('sidebar-collapsed');
    mainEl.classList.add('collapsed');
    topbarEl.style.left = '84px';
  }

  document.getElementById('collapseBtn')?.addEventListener('click', () => {
    const collapsed = sidebarEl.classList.toggle('sidebar-collapsed');
    mainEl.classList.toggle('collapsed', collapsed);
    topbarEl.style.left = collapsed ? '84px' : '272px';
    localStorage.setItem('lumora-sidebar-collapsed', collapsed ? '1' : '0');
  });

  // Mobile: sidebar off-canvas + backdrop.
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const backdropEl = document.getElementById('sidebarBackdrop');

  function toggleMobileSidebar(open) {
    sidebarEl.classList.toggle('mobile-open', open);
    backdropEl.classList.toggle('visible', open);
  }
  mobileBtn?.addEventListener('click', () => toggleMobileSidebar(! sidebarEl.classList.contains('mobile-open')));
  backdropEl?.addEventListener('click', () => toggleMobileSidebar(false));

  /* ══════════ Submenu — toggle & accordion ══════════
     Klik menu-trigger membuka/menutup submenunya, dan otomatis menutup
     menu lain yang sejajar (accordion) -- persis seperti file referensi. */
  document.querySelectorAll('.menu-trigger').forEach(btn => {
    const item = btn.closest('.menu-item');
    const submenu = item ? item.querySelector('.submenu') : null;
    if (!item || !submenu) return;

    btn.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');
      const parentUl = item.parentElement;

      parentUl.querySelectorAll(':scope > .menu-item.open').forEach(sib => {
        if (sib !== item) {
          sib.classList.remove('open');
          sib.querySelector('.submenu')?.classList.remove('open');
          sib.querySelector('.menu-trigger')?.setAttribute('aria-expanded', 'false');
        }
      });

      item.classList.toggle('open', !isOpen);
      submenu.classList.toggle('open', !isOpen);
      btn.setAttribute('aria-expanded', String(!isOpen));
    });
  });

  /* ══════════ Flyout / tooltip untuk mode sidebar collapsed ══════════
     Saat sidebar diciutkan, submenu inline disembunyikan total, diganti
     panel "flyout" yang muncul di samping ikon saat hover. Item tanpa
     submenu cukup tooltip label saja. */
  const tooltipEl = document.createElement('div');
  tooltipEl.id = 'sidebarTooltip';
  document.body.appendChild(tooltipEl);
  const flyoutEl = document.createElement('div');
  flyoutEl.id = 'sidebarFlyout';
  document.body.appendChild(flyoutEl);
  let flyoutHideTimer = null;

  function showTooltip(target) {
    if (!sidebarEl.classList.contains('sidebar-collapsed')) return;
    const label = target.getAttribute('data-label');
    if (!label) return;
    const rect = target.getBoundingClientRect();
    tooltipEl.textContent = label;
    tooltipEl.style.left = (rect.right + 14) + 'px';
    tooltipEl.style.top = (rect.top + rect.height / 2) + 'px';
    tooltipEl.classList.add('visible');
  }
  function hideTooltip() { tooltipEl.classList.remove('visible'); }

  function showFlyout(menuItem, target) {
    if (!sidebarEl.classList.contains('sidebar-collapsed')) return;
    clearTimeout(flyoutHideTimer);
    const submenu = menuItem.querySelector('.submenu');
    if (!submenu) return;
    const label = target.getAttribute('data-label') || '';
    const links = submenu.querySelectorAll('a');
    let html = '<div class="flyout-title">' + label + '</div>';
    links.forEach(a => { html += '<a href="' + a.getAttribute('href') + '">' + a.textContent.trim() + '</a>'; });
    flyoutEl.innerHTML = html;
    const rect = target.getBoundingClientRect();
    flyoutEl.style.left = (rect.right + 14) + 'px';
    flyoutEl.style.top = rect.top + 'px';
    flyoutEl.classList.add('visible');
  }
  function scheduleHideFlyout() {
    clearTimeout(flyoutHideTimer);
    flyoutHideTimer = setTimeout(() => flyoutEl.classList.remove('visible'), 150);
  }

  document.querySelectorAll('.menu-item').forEach(menuItem => {
    const link = menuItem.querySelector('.nav-item-link');
    if (!link || !link.hasAttribute('data-label')) return;
    const hasSubmenu = !!menuItem.querySelector('.submenu');
    if (hasSubmenu) {
      menuItem.addEventListener('mouseenter', () => showFlyout(menuItem, link));
      menuItem.addEventListener('mouseleave', scheduleHideFlyout);
    } else {
      link.addEventListener('mouseenter', () => showTooltip(link));
      link.addEventListener('mouseleave', hideTooltip);
    }
  });
  flyoutEl.addEventListener('mouseenter', () => clearTimeout(flyoutHideTimer));
  flyoutEl.addEventListener('mouseleave', scheduleHideFlyout);
  document.querySelector('.sidebar-scroll')?.addEventListener('scroll', () => {
    hideTooltip();
    flyoutEl.classList.remove('visible');
  });
</script>

</body>
</html>
