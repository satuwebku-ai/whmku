@php
  use App\Models\Setting;
  use App\Services\Cart\CartService;
  $siteName = Setting::get('site_name', config('app.name', 'Lumora Hosting'));
  $client = auth('client')->user();
  $cartCount = app(CartService::class)->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') — {{ $siteName }}</title>

<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style type="text/tailwindcss">
@theme {
  --font-sans: "Inter", sans-serif;
  --color-accent: #6366F1;
  --color-accent-soft: #818CF8;
  --shadow-rail: 0 0 16px 2px rgba(99,102,241,0.55);
}

@layer base {
  html { font-family: 'Inter', sans-serif; }
}

@layer components {
  .bg-topbar { background: linear-gradient(90deg, #1e1b4b 0%, #312e81 40%, #4f46e5 100%); }
  .card { @apply bg-white rounded-2xl border border-slate-200/70 shadow-sm; }

  .badge { @apply text-[11px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1; }
  .badge-active, .badge-paid { @apply bg-emerald-100 text-emerald-700; }
  .badge-pending, .badge-unpaid, .badge-answered { @apply bg-amber-100 text-amber-700; }
  .badge-suspended, .badge-overdue, .badge-expired { @apply bg-rose-100 text-rose-700; }
  .badge-inactive, .badge-closed, .badge-cancelled, .badge-terminated { @apply bg-slate-200 text-slate-600; }

  .btn { @apply inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-all; }
  .btn:active { transform: scale(.97); }
  .btn-primary { @apply bg-[#4f46e5] text-white border-[#4f46e5]; box-shadow: 0 4px 14px rgba(99,102,241,.35); }
  .btn-primary:hover { @apply bg-[#4338ca] border-[#4338ca]; }
  .btn-outline { @apply bg-white text-slate-600 border-slate-200; }
  .btn-outline:hover { @apply bg-slate-50 border-slate-300; }

  .form-input { @apply w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-sm outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition-all; }
  .form-label { @apply block text-xs font-semibold text-slate-600 mb-1.5; }
  .form-error { @apply text-xs text-rose-600 mt-1; }

  .cnav { @apply flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors text-slate-600 hover:bg-slate-100; }
  .cnav.active { @apply bg-accent/10 text-accent font-semibold; }
}
</style>
</head>
<body class="antialiased bg-slate-50 text-slate-800 min-h-screen">

  {{-- Pita peringatan impersonasi — selalu tampil paling atas, tidak bisa
       ditutup, supaya admin tidak pernah lupa sedang berada di akun
       klien lain, bukan akun sendiri. --}}
  @if (session('impersonator_admin_id'))
    <div class="bg-amber-500 text-white text-sm px-4 py-2.5 flex items-center justify-center gap-3 flex-wrap sticky top-0 z-50">
      <span>
        <i class="fa-solid fa-user-shield"></i>
        <b>{{ session('impersonator_admin_name') }}</b> sedang login sebagai <b>{{ $client->name }}</b>
      </span>
      <form method="POST" action="{{ route('client.impersonate.stop') }}">
        @csrf
        <button type="submit" class="px-3 py-1 rounded-md bg-white/20 hover:bg-white/30 font-medium transition-colors">
          Kembali ke Admin
        </button>
      </form>
    </div>
  @endif

  {{-- Topbar --}}
  <header class="bg-topbar h-16 flex items-center px-5 sticky {{ session('impersonator_admin_id') ? 'top-[41px]' : 'top-0' }} z-40">
    <div class="max-w-6xl mx-auto w-full flex items-center justify-between">
      <a href="{{ route('client.dashboard') }}" class="flex items-center gap-2.5">
        <span class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center">
          <svg viewBox="0 0 24 24" class="text-white" fill="none" stroke="currentColor" stroke-width="2.2" style="width:17px;height:17px"><path d="M13 2 3 14h7l-1 8 11-12h-7l1-8z"/></svg>
        </span>
        <span class="font-bold text-white">{{ $siteName }}</span>
      </a>

      <div class="flex items-center gap-3">
        <a href="{{ route('catalog.index') }}" class="hidden sm:flex items-center gap-1.5 text-white/80 hover:text-white text-sm">
          <i class="fa-solid fa-cart-plus"></i> Pesan Layanan Baru
        </a>
        <a href="{{ route('cart.index') }}" class="relative w-9 h-9 rounded-lg hover:bg-white/10 flex items-center justify-center text-white/80 hover:text-white">
          <i class="fa-solid fa-cart-shopping"></i>
          @if ($cartCount > 0)
            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $cartCount }}</span>
          @endif
        </a>
        <span class="hidden sm:block text-white/80 text-sm">{{ $client->name }}</span>
        <img src="{{ $client->avatar_url }}" class="w-8 h-8 rounded-full ring-2 ring-white/20" alt="">
        <form method="POST" action="{{ route('client.logout') }}">
          @csrf
          <button type="submit" class="text-white/70 hover:text-white text-sm" title="Keluar">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
          </button>
        </form>
      </div>
    </div>
  </header>

  <div class="max-w-6xl mx-auto px-5 py-6 flex gap-6">

    {{-- Sidebar --}}
    <aside class="hidden lg:block w-56 shrink-0">
      <nav class="space-y-1 sticky top-24">
        @php
          $menu = [
            ['label' => 'Dashboard', 'route' => 'client.dashboard', 'match' => 'client.dashboard*', 'icon' => 'fa-gauge'],
            ['label' => 'Pesan Layanan Baru', 'route' => 'catalog.index', 'match' => 'catalog.*', 'icon' => 'fa-cart-plus'],
            ['label' => 'Keranjang', 'route' => 'cart.index', 'match' => 'cart.*', 'icon' => 'fa-cart-shopping'],
            ['label' => 'Layanan Saya', 'route' => 'client.services', 'match' => 'client.services*', 'icon' => 'fa-server'],
            ['label' => 'Domain Saya', 'route' => 'client.domains', 'match' => 'client.domains*', 'icon' => 'fa-globe'],
            ['label' => 'Invoice', 'route' => 'client.invoices', 'match' => 'client.invoices*', 'icon' => 'fa-file-invoice'],
            ['label' => 'Saldo Saya', 'route' => 'client.balance', 'match' => 'client.balance*', 'icon' => 'fa-wallet'],
            ['label' => 'Tiket Support', 'route' => 'client.tickets', 'match' => 'client.tickets*', 'icon' => 'fa-comments'],
            ['label' => 'Profil Saya', 'route' => 'client.profile', 'match' => 'client.profile*', 'icon' => 'fa-user'],
          ];
        @endphp

        @foreach ($menu as $item)
          <a href="{{ route($item['route']) }}" class="cnav {{ request()->routeIs($item['match']) ? 'active' : '' }}">
            <i class="fa-solid {{ $item['icon'] }} w-4 text-center"></i>
            {{ $item['label'] }}
          </a>
        @endforeach
      </nav>
    </aside>

    {{-- Konten --}}
    <main class="flex-1 min-w-0">
      {{-- Nav mobile --}}
      <nav class="lg:hidden flex gap-1 mb-5 overflow-x-auto pb-1">
        @foreach ($menu as $item)
          <a href="{{ route($item['route']) }}"
             class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap
                    {{ request()->routeIs($item['match']) ? 'bg-accent text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
            {{ $item['label'] }}
          </a>
        @endforeach
      </nav>

      @if (session('success'))
        <div class="mb-5 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 flex items-center gap-2">
          <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
      @endif
      @if (session('error'))
        <div class="mb-5 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 flex items-center gap-2">
          <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
        </div>
      @endif

      @yield('content')
    </main>
  </div>

{{-- Modal konfirmasi --}}
<div id="confirmModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4" style="background:rgba(15,23,42,.6);backdrop-filter:blur(2px)">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden" style="animation:modalIn .18s ease-out">
    <div class="p-6 flex items-start gap-4">
      <span id="confirmIcon" class="w-11 h-11 rounded-full flex items-center justify-center shrink-0 bg-amber-100 text-amber-600">
        <i class="fa-solid fa-circle-exclamation"></i>
      </span>
      <div class="flex-1 min-w-0">
        <h3 id="confirmTitle" class="text-base font-bold text-slate-800 mb-1">Konfirmasi</h3>
        <p id="confirmText" class="text-sm text-slate-500 leading-relaxed"></p>
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
  (function () {
    const modal = document.getElementById('confirmModal');
    const icon  = document.getElementById('confirmIcon');
    const title = document.getElementById('confirmTitle');
    const text  = document.getElementById('confirmText');
    const okBtn = document.getElementById('confirmOk');
    const noBtn = document.getElementById('confirmCancel');

    let pendingForm = null;

    function close() { modal.classList.remove('show'); pendingForm = null; }

    document.addEventListener('submit', function (e) {
      const form = e.target;
      if (form.dataset && form.dataset.confirm && !form.dataset.confirmed) {
        e.preventDefault();
        pendingForm = form;

        const danger = (form.dataset.confirmStyle || '') === 'danger';
        icon.className = 'w-11 h-11 rounded-full flex items-center justify-center shrink-0 '
                       + (danger ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600');
        okBtn.className = danger ? 'btn btn-outline !text-rose-600 !border-rose-200' : 'btn btn-primary';
        okBtn.textContent = form.dataset.confirmLabel || 'Lanjutkan';
        title.textContent = form.dataset.confirmTitle || 'Konfirmasi';
        text.textContent  = form.dataset.confirm;

        modal.classList.add('show');
        okBtn.focus();
      }
    });

    okBtn.addEventListener('click', function () {
      if (!pendingForm) return;
      pendingForm.dataset.confirmed = '1';
      pendingForm.submit();
      close();
    });

    noBtn.addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  })();
</script>

  @include('public.partials.livechat')
</body>
</html>
