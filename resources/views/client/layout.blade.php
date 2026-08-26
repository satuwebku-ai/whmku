@php
  use App\Models\Setting;
  use App\Services\Cart\CartService;
  $siteName = Setting::get('site_name', config('app.name', 'Lumora Hosting'));
  $themeColor = Setting::get('theme_color', '#6366F1');
  $client = auth('client')->user();
  $cartCount = app(CartService::class)->count();

  $menu = [
    ['label' => 'Dashboard', 'route' => 'client.dashboard', 'match' => 'client.dashboard*', 'icon' => 'fa-gauge'],
    ['label' => 'Pesan Layanan Baru', 'route' => 'catalog.index', 'match' => 'catalog.*', 'icon' => 'fa-cart-plus'],
    ['label' => 'Keranjang', 'route' => 'cart.index', 'match' => 'cart.*', 'icon' => 'fa-cart-shopping'],
    // TODO: alihkan ke .bootstrap-preview begitu masing-masing modul selesai dimigrasikan.
    ['label' => 'Layanan Saya', 'route' => 'client.services', 'match' => 'client.services*', 'icon' => 'fa-server'],
    ['label' => 'VPS Saya', 'route' => 'client.vps', 'match' => 'client.vps*', 'icon' => 'fa-cloud'],
    ['label' => 'Domain Saya', 'route' => 'client.domains', 'match' => 'client.domains*', 'icon' => 'fa-globe'],
    ['label' => 'Invoice', 'route' => 'client.invoices', 'match' => 'client.invoices*', 'icon' => 'fa-file-invoice'],
    ['label' => 'Saldo Saya', 'route' => 'client.balance', 'match' => 'client.balance*', 'icon' => 'fa-wallet'],
    ['label' => 'Tiket Support', 'route' => 'client.tickets', 'match' => 'client.tickets*', 'icon' => 'fa-comments'],
    ['label' => 'Profil Saya', 'route' => 'client.profile', 'match' => 'client.profile*', 'icon' => 'fa-user'],
  ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') — {{ $siteName }}</title>

<link rel="stylesheet" href="{{ asset('assets/css/vendor/bootstrap-5.3.8.min.css') }}?v={{ @filemtime(public_path('assets/css/vendor/bootstrap-5.3.8.min.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/lumora-public.css') }}?v={{ @filemtime(public_path('assets/css/lumora-public.css')) ?: time() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
  :root{ --lumora-theme: {{ $themeColor }}; }
  body{ background:#f8fafc; }
  #clientTopbar{ background:linear-gradient(90deg,#1e1b4b 0%,#312e81 40%,#4f46e5 100%); height:64px; }
  .cnav{ display:flex; align-items:center; gap:.75rem; padding:.6rem .75rem; border-radius:.6rem; font-size:14px; color:#475569; text-decoration:none; transition:background .15s; }
  .cnav:hover{ background:#f1f5f9; color:#334155; }
  .cnav.active{ background:rgba(79,70,229,.1); color:var(--lumora-theme); font-weight:600; }
</style>
</head>
<body class="lumora-public">

  {{-- Pita peringatan impersonasi --}}
  @if (session('impersonator_admin_id'))
    <div class="text-white text-center px-3 py-2 d-flex align-items-center justify-content-center gap-3 flex-wrap position-sticky top-0" style="background:#f59e0b;font-size:14px;z-index:1040">
      <span>
        <i class="fa-solid fa-user-shield"></i>
        <b>{{ session('impersonator_admin_name') }}</b> sedang login sebagai <b>{{ $client->name }}</b>
      </span>
      <form method="POST" action="{{ route('client.impersonate.stop') }}">
        @csrf
        <button type="submit" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:0">
          Kembali ke Admin
        </button>
      </form>
    </div>
  @endif

  {{-- Topbar --}}
  <header id="clientTopbar" class="d-flex align-items-center px-3 position-sticky" style="top:{{ session('impersonator_admin_id') ? '41px' : '0' }};z-index:1030">
    <div class="container d-flex align-items-center justify-content-between" style="max-width:72rem">
      <a href="{{ route('client.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
        <span class="rounded-3 d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(255,255,255,.15)">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="#fff" stroke-width="2.2"><path d="M13 2 3 14h7l-1 8 11-12h-7l1-8z"/></svg>
        </span>
        <span class="fw-bold text-white">{{ $siteName }}</span>
      </a>

      <div class="d-flex align-items-center gap-3">
        <a href="{{ route('catalog.index') }}" class="d-none d-sm-flex align-items-center gap-2 text-decoration-none" style="color:rgba(255,255,255,.8);font-size:14px">
          <i class="fa-solid fa-cart-plus"></i> Pesan Layanan Baru
        </a>
        <a href="{{ route('cart.index') }}" class="position-relative d-flex align-items-center justify-content-center text-decoration-none rounded-3" style="width:36px;height:36px;color:rgba(255,255,255,.8)">
          <i class="fa-solid fa-cart-shopping"></i>
          @if ($cartCount > 0)
            <span class="position-absolute rounded-circle d-flex align-items-center justify-content-center fw-bold text-white" style="top:-2px;right:-2px;width:16px;height:16px;font-size:10px;background:#f43f5e">{{ $cartCount }}</span>
          @endif
        </a>
        <span class="d-none d-sm-block" style="color:rgba(255,255,255,.8);font-size:14px">{{ $client->name }}</span>
        <img src="{{ $client->avatar_url }}" class="rounded-circle" style="width:32px;height:32px;border:2px solid rgba(255,255,255,.2)" alt="">
        <form method="POST" action="{{ route('client.logout') }}">
          @csrf
          <button type="submit" class="btn btn-link p-0" style="color:rgba(255,255,255,.7)" title="Keluar">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
          </button>
        </form>
      </div>
    </div>
  </header>

  <div class="container d-flex gap-4 py-4" style="max-width:72rem">

    {{-- Sidebar --}}
    <aside class="d-none d-lg-block flex-shrink-0" style="width:224px">
      <nav class="d-flex flex-column gap-1 position-sticky" style="top:6rem">
        @foreach ($menu as $item)
          <a href="{{ route($item['route']) }}" class="cnav {{ request()->routeIs($item['match']) ? 'active' : '' }}">
            <i class="fa-solid {{ $item['icon'] }} text-center" style="width:16px"></i>
            {{ $item['label'] }}
          </a>
        @endforeach
      </nav>
    </aside>

    {{-- Konten --}}
    <main class="flex-grow-1 min-w-0">
      {{-- Nav mobile --}}
      <nav class="d-lg-none d-flex gap-2 mb-4 pb-1" style="overflow-x:auto">
        @foreach ($menu as $item)
          <a href="{{ route($item['route']) }}"
             class="px-3 py-2 rounded-pill text-decoration-none text-nowrap"
             style="font-size:12px;font-weight:500;{{ request()->routeIs($item['match']) ? 'background:var(--lumora-theme);color:#fff' : 'background:#fff;border:1px solid #e2e8f0;color:#475569' }}">
            {{ $item['label'] }}
          </a>
        @endforeach
      </nav>

      <x-toast />

      @yield('content')
    </main>
  </div>

  {{-- Modal konfirmasi --}}
  <div class="modal" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 overflow-hidden">
        <div class="p-4">
          <div class="d-flex align-items-start gap-3">
            <span id="confirmIcon" class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-warning bg-opacity-10 text-warning" style="width:44px;height:44px">
              <i class="fa-solid fa-circle-exclamation"></i>
            </span>
            <div class="flex-grow-1 min-w-0">
              <h3 id="confirmTitle" class="h6 fw-bold text-dark mb-1">Konfirmasi</h3>
              <p id="confirmText" class="small text-muted mb-0" style="line-height:1.6"></p>
            </div>
          </div>
        </div>
        <div class="px-4 py-3 bg-light border-top d-flex align-items-center justify-content-end gap-2">
          <button type="button" id="confirmCancel" class="btn btn-outline-secondary">Batal</button>
          <button type="button" id="confirmOk" class="btn btn-primary">Lanjutkan</button>
        </div>
      </div>
    </div>
  </div>

  <script src="{{ asset('assets/js/vendor/bootstrap-5.3.8.bundle.min.js') }}"></script>

  <script>
    (function () {
      const modal  = document.getElementById('confirmModal');
      const icon   = document.getElementById('confirmIcon');
      const title  = document.getElementById('confirmTitle');
      const text   = document.getElementById('confirmText');
      const okBtn  = document.getElementById('confirmOk');
      const noBtn  = document.getElementById('confirmCancel');

      let pendingForm = null;
      let confirmBackdrop = null;

      const styles = {
        danger: { cls: 'bg-danger bg-opacity-10 text-danger', icon: 'fa-triangle-exclamation', btn: 'btn btn-danger',  label: 'Ya, Lanjutkan' },
        warn:   { cls: 'bg-warning bg-opacity-10 text-warning', icon: 'fa-circle-exclamation', btn: 'btn btn-primary', label: 'Lanjutkan' },
        info:   { cls: 'bg-primary bg-opacity-10 text-primary', icon: 'fa-circle-info',        btn: 'btn btn-primary', label: 'Lanjutkan' },
      };

      function openModal() {
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');

        confirmBackdrop = document.createElement('div');
        confirmBackdrop.className = 'modal-backdrop';
        document.body.appendChild(confirmBackdrop);
        requestAnimationFrame(() => confirmBackdrop.classList.add('show'));

        okBtn.focus();
      }
      function closeModal() {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');

        if (confirmBackdrop) {
          confirmBackdrop.classList.remove('show');
          confirmBackdrop.remove();
          confirmBackdrop = null;
        }
        pendingForm = null;
      }

      function open(form) {
        pendingForm = form;
        const style = styles[form.dataset.confirmStyle || 'warn'] || styles.warn;

        icon.className = 'rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ' + style.cls;
        icon.style.width = '44px'; icon.style.height = '44px';
        icon.innerHTML = '<i class="fa-solid ' + style.icon + '"></i>';
        okBtn.className = style.btn;
        okBtn.textContent = form.dataset.confirmLabel || style.label;

        title.textContent = form.dataset.confirmTitle || 'Konfirmasi';
        text.textContent  = form.dataset.confirm;

        openModal();
      }

      document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.dataset && form.dataset.confirm && !form.dataset.confirmed) {
          e.preventDefault();
          open(form);
        }
      });

      okBtn.addEventListener('click', function () {
        if (!pendingForm) return;
        pendingForm.dataset.confirmed = '1';
        pendingForm.submit();
        closeModal();
      });

      noBtn.addEventListener('click', closeModal);
      modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('show')) closeModal(); });
    })();
  </script>

  @include('public.partials.livechat')
</body>
</html>
