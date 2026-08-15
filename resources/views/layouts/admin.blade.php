<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') — {{ config('app.name', 'Lumora Hosting') }}</title>

<style>html{visibility:hidden}</style>

<script src="{{ route('tailwind.browser') }}" onload="document.documentElement.style.visibility='visible'"></script>

<script>setTimeout(function(){document.documentElement.style.visibility='visible'},2500)</script>
<link rel="stylesheet" href="{{ route('fontawesome.css', 'all.min.css') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style type="text/tailwindcss">
@theme {
  --font-sans: "Inter", sans-serif;
  --color-ink-900: #0B1120;
  --color-ink-800: #0F172A;
  --color-ink-700: #152033;
  --color-ink-600: #1E293B;
  --color-ink-500: #334155;
  --color-accent:      #6366F1;
  --color-accent-soft: #818CF8;
  --color-accent-glow: #A5B4FC;
  --shadow-rail:   0 0 16px 2px rgba(99,102,241,0.75);
  --shadow-topbar: 0 2px 24px rgba(79,70,229,0.18);
}

@layer base {
  html { font-family: 'Inter', sans-serif; }
  .sidebar-scroll::-webkit-scrollbar       { width: 5px; }
  .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
  .sidebar-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
  .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #475569; }
}

@layer components {
  .bg-sidebar { background: linear-gradient(160deg, #1e1b4b 0%, #312e81 35%, #4c1d95 70%, #1e1b4b 100%); }
  .bg-topbar  { background: linear-gradient(90deg, #1e1b4b 0%, #312e81 40%, #4f46e5 100%); box-shadow: var(--shadow-topbar); }
  .bg-body    { background: linear-gradient(135deg, #eef2ff 0%, #f0fdf4 35%, #fdf4ff 65%, #fff7ed 100%); background-attachment: fixed; }

  .nav-item-link { position: relative; }
  .nav-item-link.active::before {
    content: '';
    position: absolute;
    left: -16px; top: 8px; bottom: 8px; width: 3px;
    border-radius: 4px;
    background: linear-gradient(180deg, #c7d2fe, #818CF8, #6366F1);
    box-shadow: 0 0 18px 3px rgba(99,102,241,0.85);
  }
  .nav-item-link.active {
    background: linear-gradient(to right, rgba(99,102,241,0.18), rgba(99,102,241,0.06)) !important;
  }

  .submenu { max-height: 0; overflow: hidden; transition: max-height .25s ease; }
  .submenu.open { max-height: 600px; }

  .card { @apply bg-white rounded-2xl border border-slate-200/70 shadow-sm; }
  .stat-icon { @apply w-11 h-11 rounded-xl flex items-center justify-center shrink-0; }
  .badge { @apply text-[11px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1; }
  .badge-active     { @apply bg-emerald-100 text-emerald-700; }
  .badge-pending    { @apply bg-amber-100 text-amber-700; }
  .badge-suspended  { @apply bg-rose-100 text-rose-700; }
  .badge-terminated { @apply bg-slate-200 text-slate-600; }
  .badge-cancelled  { @apply bg-slate-200 text-slate-600; }
  .badge-paid       { @apply bg-emerald-100 text-emerald-700; }
  .badge-unpaid     { @apply bg-amber-100 text-amber-700; }
  .badge-overdue    { @apply bg-rose-100 text-rose-700; }
  .badge-inactive   { @apply bg-slate-200 text-slate-600; }

  /* ── Buttons (dipakai di halaman CRUD) ── */
  .btn { @apply inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-all; }
  .btn:hover { transform: translateY(-1px); }
  .btn:active { transform: scale(.97); }
  .btn-primary { @apply bg-[#4f46e5] text-white border-[#4f46e5]; box-shadow: 0 4px 14px rgba(99,102,241,.35); }
  .btn-primary:hover { @apply bg-[#4338ca] border-[#4338ca]; }
  .btn-outline { @apply bg-white text-slate-600 border-slate-200; }
  .btn-outline:hover { @apply bg-slate-50 border-slate-300; }
  .btn-danger-soft { @apply bg-rose-50 text-rose-600 border-rose-200; }
  .btn-danger-soft:hover { @apply bg-rose-100 border-rose-300; }

  .form-input { @apply w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-sm outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition-all; }
  .form-label { @apply block text-xs font-semibold text-slate-600 mb-1.5; }
  .form-error { @apply text-xs text-rose-600 mt-1; }
}
</style>

<style>
  html.sidebar-collapsed #sidebar { width: 84px !important; min-width: 84px !important; }
  html.sidebar-collapsed #sidebar .label-text,
  html.sidebar-collapsed #sidebar .menu-eyebrow,
  html.sidebar-collapsed #sidebar .chevron,
  html.sidebar-collapsed #sidebar .brand-text,
  html.sidebar-collapsed #sidebar .submenu { display: none; }
  html.sidebar-collapsed #sidebar .nav-item-link { justify-content: center !important; }
  html.sidebar-collapsed #main, html.sidebar-collapsed #topbar { margin-left: 84px !important; left: 84px !important; }
</style>
</head>
<body class="antialiased bg-body text-ink-800">

<div class="flex min-h-screen">

  {{-- ══════════ Sidebar ══════════ --}}
  <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-[272px] flex flex-col border-r border-white/10 bg-sidebar transition-all duration-200">

    <div class="h-16 flex items-center gap-3 px-6 shrink-0 border-b border-white/5">
      @php
        $adminLogo = \App\Models\Setting::get('site_logo');
        $brandingDisplay = \App\Models\Setting::get('branding_display', 'logo_and_text');
      @endphp
      @if ($brandingDisplay !== 'text_only')
        @if ($adminLogo)
          <img src="{{ route('branding.file', $adminLogo) }}" alt="{{ config('app.name', 'Lumora Hosting') }}" class="h-10 w-auto object-contain shrink-0">
        @else
          <div class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center shrink-0 shadow-[--shadow-rail]">
            <svg viewBox="0 0 24 24" class="text-white" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px">
              <path d="M13 2 3 14h7l-1 8 11-12h-7l1-8z"/>
            </svg>
          </div>
        @endif
      @endif
      @if ($brandingDisplay !== 'logo_only')
        <span class="brand-text text-white font-bold text-[15px] tracking-tight whitespace-nowrap">{{ config('app.name', 'Lumora Hosting') }}</span>
      @endif
    </div>

    <nav class="sidebar-scroll flex-1 overflow-y-auto px-4 py-5">
      <p class="menu-eyebrow text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase mb-3 mt-1">Menu Utama</p>

      <ul class="space-y-1 text-[13.5px]">
        @php
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
            ['label' => 'Konten & Halaman', 'route' => 'admin.pages', 'match' => ['admin.page*', 'admin.announcement*', 'admin.promo-banners.*'], 'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15Z', 'admin_only' => true],
            ['label' => 'Template Notifikasi', 'route' => 'admin.notification-templates.index', 'match' => ['admin.notification-templates.*'], 'icon' => 'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2ZM22 6l-10 7L2 6', 'admin_only' => true],
            ['label' => 'Pengaturan',       'route' => 'admin.settings.general', 'match' => ['admin.settings.*'], 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6 1.65 1.65 0 0 0 10 3.09V3a2 2 0 0 1 4 0v.09c0 .67.39 1.28 1 1.51.63.24 1.35.12 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06c-.45.47-.57 1.19-.33 1.82.23.61.84 1 1.51 1H21a2 2 0 0 1 0 4h-.09c-.67 0-1.28.39-1.51 1Z', 'admin_only' => true],
            ['label' => 'Backup',           'route' => 'admin.backups.index', 'match' => ['admin.backups.*'], 'icon' => 'M12 3C7 3 3 4.5 3 6.5V17.5C3 19.5 7 21 12 21C17 21 21 19.5 21 17.5V6.5C21 4.5 17 3 12 3ZM3 6.5C3 8.5 7 10 12 10C17 10 21 8.5 21 6.5M3 12C3 14 7 15.5 12 15.5C17 15.5 21 14 21 12', 'admin_only' => true],
            ['label' => 'Konsol Web',       'route' => 'admin.console.index', 'match' => ['admin.console.*'], 'icon' => 'M4 17l6-6-6-6M12 19h8', 'admin_only' => true],
          ];
        @endphp

        @foreach ($menu as $item)
          {{-- Menu bertanda superadmin disembunyikan dari admin biasa & staff.
               Menu bertanda admin_only disembunyikan dari staff saja
               (admin & superadmin tetap lihat). Ini hanya menyembunyikan
               tautannya; pembatasan sebenarnya ada di middleware 'role'
               pada route-nya masing-masing. --}}
          @continue(!empty($item['superadmin']) && ! auth('admin')->user()?->isSuperadmin())
          @continue(!empty($item['admin_only']) && ! auth('admin')->user()?->canManage())

          <li class="menu-item">
            <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
               class="nav-item-link w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ $item['match'] && request()->routeIs(...$item['match']) ? 'active text-white bg-white/[0.06]' : 'text-slate-300 hover:bg-white/[0.06] hover:text-white' }}">
              <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="{{ $item['icon'] }}"/>
              </svg>
              <span class="label-text whitespace-nowrap">{{ $item['label'] }}</span>
              @if (! $item['route'])
                <span class="label-text ml-auto text-[9px] font-bold bg-white/10 text-slate-400 px-1.5 py-0.5 rounded-full">Fase 3+</span>
              @endif
            </a>
          </li>
        @endforeach
      </ul>
    </nav>

    <div class="help-box p-4 m-4 mt-0 rounded-xl bg-white/[0.06] border border-white/10">
      <p class="text-white text-xs font-semibold mb-1">Butuh Bantuan?</p>
      <p class="text-slate-400 text-[11px] leading-relaxed">Modul hosting &amp; billing lain akan ditambahkan bertahap.</p>
    </div>
  </aside>

  {{-- ══════════ Main content ══════════ --}}
  <div id="main" class="flex-1 min-h-screen flex flex-col pt-16 transition-all duration-200" style="margin-left:272px;overflow-x:hidden;min-width:0;">

    <header id="topbar" class="h-16 flex items-center justify-between px-6 fixed top-0 right-0 z-[60] transition-all duration-200 bg-topbar" style="left:272px;">
      <div class="flex items-center gap-3">
        <button id="collapseBtn" class="w-9 h-9 rounded-lg hover:bg-white/10 flex items-center justify-center text-white/80 hover:text-white transition-colors">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>

        <div class="relative hidden sm:block">
          <div class="flex items-center gap-2 bg-white/10 rounded-lg px-3 py-2 w-64 focus-within:w-80 transition-all duration-300 focus-within:ring-2 focus-within:ring-white/30 focus-within:bg-white/15">
            <svg class="w-4 h-4 text-white/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" placeholder="Cari klien, order, invoice..." class="bg-transparent text-sm outline-none w-full text-white placeholder:text-white/40">
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2">
        @php
          // Jumlah notifikasi belum dibaca — dihitung di layout supaya
          // muncul di seluruh halaman admin tanpa perlu diteruskan
          // satu per satu dari tiap controller.
          $unreadActivities = \App\Models\ActivityLog::unread()->count();
        @endphp

        <a href="{{ route('admin.activities') }}"
           class="w-9 h-9 rounded-lg hover:bg-white/10 flex items-center justify-center text-white/80 hover:text-white transition-colors relative"
           title="{{ $unreadActivities > 0 ? $unreadActivities . ' notifikasi belum dibaca' : 'Aktivitas' }}">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          @if ($unreadActivities > 0)
            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-[#312e81]">
              {{ $unreadActivities > 99 ? '99+' : $unreadActivities }}
            </span>
          @endif
        </a>

        <div class="relative">
          <button id="profileBtn" class="flex items-center gap-2.5 pl-2 pr-1 py-1 rounded-lg hover:bg-white/10 transition-colors">
            <img src="{{ auth('admin')->user()->avatar_url }}" class="w-8 h-8 rounded-full ring-2 ring-white/20" alt="avatar">
            <span class="hidden md:block text-left">
              <span class="block text-white text-[13px] font-semibold leading-tight">{{ auth('admin')->user()->name }}</span>
              <span class="block text-white/50 text-[11px] leading-tight capitalize">{{ auth('admin')->user()->role }}</span>
            </span>
            <svg class="w-3.5 h-3.5 text-white/60 hidden md:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
          </button>

          <div id="profileDropdown" class="hidden absolute right-0 top-full mt-2 w-56 bg-white rounded-xl border border-slate-200 shadow-lg shadow-slate-200/80 overflow-hidden z-50">
            <div class="px-4 py-3 border-b border-slate-100">
              <p class="text-sm font-semibold text-ink-800">{{ auth('admin')->user()->name }}</p>
              <p class="text-xs text-slate-400">{{ auth('admin')->user()->email }}</p>
            </div>
            <div class="py-1.5">
              <a href="{{ route('admin.profile') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-ink-700 hover:bg-slate-50">
                <i class="fa-regular fa-user w-4 text-slate-400"></i> Profil Saya
              </a>
              <a href="{{ route('admin.profile') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-ink-700 hover:bg-slate-50">
                <i class="fa-solid fa-shield-halved w-4 text-slate-400"></i> Keamanan &amp; 2FA
              </a>
            </div>
            <div class="border-t border-slate-100 py-1.5">
              <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">
                  <i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Keluar
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-1 p-6">
      @if (session('success'))
        <div class="flash-msg mb-5 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 flex items-start gap-2.5">
          <i class="fa-solid fa-circle-check mt-0.5 shrink-0"></i>
          <span class="flex-1">{{ session('success') }}</span>
          <button type="button" class="text-emerald-400 hover:text-emerald-600 shrink-0" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark text-xs"></i>
          </button>
        </div>
      @endif
      @if (session('error'))
        <div class="flash-msg mb-5 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 flex items-start gap-2.5">
          <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0"></i>
          <span class="flex-1">{{ session('error') }}</span>
          <button type="button" class="text-rose-400 hover:text-rose-600 shrink-0" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark text-xs"></i>
          </button>
        </div>
      @endif
      @if (session('info'))
        <div class="flash-msg mb-5 rounded-lg bg-indigo-50 border border-indigo-200 px-4 py-3 text-sm text-indigo-700 flex items-start gap-2.5">
          <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
          <span class="flex-1">{{ session('info') }}</span>
          <button type="button" class="text-indigo-400 hover:text-indigo-600 shrink-0" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark text-xs"></i>
          </button>
        </div>
      @endif

      @yield('content')
    </main>
  </div>
</div>

{{-- ══════════ Modal konfirmasi ══════════ --}}
<div id="confirmModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4" style="background:rgba(15,23,42,.6);backdrop-filter:blur(2px)">
  <div id="confirmBox" class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden" style="animation:modalIn .18s ease-out">
    <div class="p-6">
      <div class="flex items-start gap-4">
        <span id="confirmIcon" class="w-11 h-11 rounded-full flex items-center justify-center shrink-0 bg-rose-100 text-rose-600">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </span>
        <div class="flex-1 min-w-0">
          <h3 id="confirmTitle" class="text-base font-bold text-slate-800 mb-1">Konfirmasi</h3>
          <p id="confirmText" class="text-sm text-slate-500 leading-relaxed"></p>
        </div>
      </div>
    </div>
    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
      <button type="button" id="confirmCancel" class="btn btn-outline">Batal</button>
      <button type="button" id="confirmOk" class="btn btn-primary">Lanjutkan</button>
    </div>
  </div>
</div>

<style>
  @keyframes modalIn { from { opacity:0; transform:translateY(-8px) scale(.97) } to { opacity:1; transform:none } }
  #confirmModal.show { display:flex }
</style>

<script>
  document.getElementById('collapseBtn')?.addEventListener('click', () => {
    document.documentElement.classList.toggle('sidebar-collapsed');
  });

  const profileBtn = document.getElementById('profileBtn');
  const profileDropdown = document.getElementById('profileDropdown');
  profileBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle('hidden');
  });
  document.addEventListener('click', () => profileDropdown?.classList.add('hidden'));

  /* ── Modal konfirmasi ──
     Menggantikan confirm() bawaan browser yang menampilkan nama domain
     ("apps-ku.my.id says") dan tidak bisa didesain. Form cukup diberi
     atribut data-confirm, opsional data-confirm-title & data-confirm-style. */
  (function () {
    const modal  = document.getElementById('confirmModal');
    const box    = document.getElementById('confirmBox');
    const icon   = document.getElementById('confirmIcon');
    const title  = document.getElementById('confirmTitle');
    const text   = document.getElementById('confirmText');
    const okBtn  = document.getElementById('confirmOk');
    const noBtn  = document.getElementById('confirmCancel');

    let pendingForm = null;
    let pendingResolve = null;

    const styles = {
      danger: { cls: 'bg-rose-100 text-rose-600',     icon: 'fa-triangle-exclamation', btn: 'btn btn-danger-soft', label: 'Ya, Hapus' },
      warn:   { cls: 'bg-amber-100 text-amber-600',   icon: 'fa-circle-exclamation',   btn: 'btn btn-primary',     label: 'Lanjutkan' },
      info:   { cls: 'bg-indigo-100 text-indigo-600', icon: 'fa-circle-info',          btn: 'btn btn-primary',     label: 'Lanjutkan' },
    };

    function open(form) {
      pendingForm = form;

      const style = styles[form.dataset.confirmStyle || 'danger'] || styles.danger;

      icon.className = 'w-11 h-11 rounded-full flex items-center justify-center shrink-0 ' + style.cls;
      icon.innerHTML = '<i class="fa-solid ' + style.icon + '"></i>';
      okBtn.className = style.btn;
      okBtn.textContent = form.dataset.confirmLabel || style.label;

      title.textContent = form.dataset.confirmTitle || 'Konfirmasi';
      text.textContent  = form.dataset.confirm;

      modal.classList.add('show');
      okBtn.focus();
    }

    function close() {
      modal.classList.remove('show');
      pendingForm = null;

      if (pendingResolve) {
        const resolve = pendingResolve;
        pendingResolve = null;
        resolve(false);
      }
    }

    /**
     * Versi promise dari modal ini, supaya kode JS lain (mis. AJAX)
     * tidak perlu memakai confirm() bawaan browser yang tampilannya
     * menampilkan nama domain dan tidak bisa didesain.
     *
     *   if (await confirmDialog({ message: '...', style: 'warn' })) { ... }
     */
    window.confirmDialog = function (options) {
      return new Promise(function (resolve) {
        const opts = options || {};
        const style = styles[opts.style || 'info'] || styles.info;

        pendingForm = null;
        pendingResolve = resolve;

        icon.className = 'w-11 h-11 rounded-full flex items-center justify-center shrink-0 ' + style.cls;
        icon.innerHTML = '<i class="fa-solid ' + style.icon + '"></i>';
        okBtn.className = style.btn;
        okBtn.textContent = opts.label || style.label;

        title.textContent = opts.title || 'Konfirmasi';
        text.textContent  = opts.message || '';

        modal.classList.add('show');
        okBtn.focus();
      });
    };

    document.addEventListener('submit', function (e) {
      const form = e.target;
      if (form.dataset && form.dataset.confirm && !form.dataset.confirmed) {
        e.preventDefault();
        open(form);
      }
    });

    okBtn.addEventListener('click', function () {
      // Mode promise: kembalikan true ke pemanggil.
      if (pendingResolve) {
        const resolve = pendingResolve;
        pendingResolve = null;
        modal.classList.remove('show');
        resolve(true);
        return;
      }

      if (!pendingForm) return;
      // Tandai supaya submit berikutnya lolos tanpa memicu modal lagi.
      pendingForm.dataset.confirmed = '1';
      pendingForm.submit();
      close();
    });

    noBtn.addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  })();
</script>

</body>
</html>
