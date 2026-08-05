@php
  use App\Models\Setting;
  use App\Services\Cart\CartService;

  $siteName = Setting::get('site_name', config('app.name', 'Lumora Hosting'));
  $footerPages = \App\Models\Page::published()->where('show_in_footer', true)->orderBy('sort_order')->get();
  $cartCount = app(CartService::class)->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  @include('public.partials.head')

  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style type="text/tailwindcss">
    @theme {
      --font-sans: "Inter", sans-serif;
      --color-accent: #6366F1;
      --color-accent-soft: #818CF8;
    }
    @layer base {
      html { font-family: 'Inter', sans-serif; }
      .prose-content h2 { @apply text-xl font-bold text-slate-800 mt-8 mb-3; }
      .prose-content h3 { @apply text-lg font-semibold text-slate-800 mt-6 mb-2; }
      .prose-content p  { @apply text-slate-600 leading-relaxed mb-4; }
      .prose-content ul { @apply list-disc pl-6 text-slate-600 mb-4 space-y-1; }
      .prose-content ol { @apply list-decimal pl-6 text-slate-600 mb-4 space-y-1; }
      .prose-content a  { @apply text-accent hover:underline; }
      .prose-content img { @apply rounded-xl my-4; }
      .prose-content table { @apply w-full text-sm border border-slate-200 rounded-lg my-4; }
      .prose-content th, .prose-content td { @apply border border-slate-200 px-3 py-2; }
    }
    @layer components {
      .card { @apply bg-white rounded-2xl border border-slate-200/70 shadow-sm; }
      .badge { @apply text-[11px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1; }
      .badge-active { @apply bg-emerald-100 text-emerald-700; }
      .badge-inactive { @apply bg-slate-200 text-slate-600; }

      .btn { @apply inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-all; }
      .btn:active { transform: scale(.97); }
      .btn-primary { @apply bg-[#4f46e5] text-white border-[#4f46e5]; box-shadow: 0 4px 14px rgba(99,102,241,.35); }
      .btn-primary:hover { @apply bg-[#4338ca] border-[#4338ca]; }
      .btn-outline { @apply bg-white text-slate-600 border-slate-200; }
      .btn-outline:hover { @apply bg-slate-50 border-slate-300; }

      .form-input { @apply w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-sm outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition-all; }
      .form-label { @apply block text-xs font-semibold text-slate-600 mb-1.5; }
      .form-error { @apply text-xs text-rose-600 mt-1; }
    }
  </style>
</head>
<body class="antialiased bg-slate-50 text-slate-800 min-h-screen flex flex-col">

  <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
    <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
      <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0">
        <span class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center">
          <svg viewBox="0 0 24 24" class="text-white" fill="none" stroke="currentColor" stroke-width="2.2" style="width:17px;height:17px"><path d="M13 2 3 14h7l-1 8 11-12h-7l1-8z"/></svg>
        </span>
        <span class="font-bold text-slate-800">{{ $siteName }}</span>
      </a>

      <nav class="hidden sm:flex items-center gap-6 text-sm text-slate-600">
        <a href="{{ route('catalog.index') }}" class="hover:text-accent {{ request()->routeIs('catalog.*') ? 'text-accent font-medium' : '' }}">Hosting</a>
        <a href="{{ route('domain.search') }}" class="hover:text-accent {{ request()->routeIs('domain.*') ? 'text-accent font-medium' : '' }}">Domain</a>
        <a href="{{ route('announcements.index') }}" class="hover:text-accent {{ request()->routeIs('announcements.*') ? 'text-accent font-medium' : '' }}">Pengumuman</a>
      </nav>

      <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('cart.index') }}" class="relative w-9 h-9 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-600">
          <i class="fa-solid fa-cart-shopping"></i>
          @if ($cartCount > 0)
            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-accent text-white text-[10px] font-bold flex items-center justify-center">{{ $cartCount }}</span>
          @endif
        </a>
        @auth('client')
          <a href="{{ route('client.dashboard') }}" class="btn btn-outline !py-1.5 !px-3 text-xs">Akun Saya</a>
        @else
          <a href="{{ route('client.login') }}" class="btn btn-outline !py-1.5 !px-3 text-xs hidden sm:inline-flex">Masuk</a>
          <a href="{{ route('client.register') }}" class="btn btn-primary !py-1.5 !px-3 text-xs">Daftar</a>
        @endauth
      </div>
    </div>

    {{-- Nav mobile --}}
    <nav class="sm:hidden flex items-center gap-4 px-6 pb-3 text-xs text-slate-600 overflow-x-auto">
      <a href="{{ route('catalog.index') }}" class="whitespace-nowrap {{ request()->routeIs('catalog.*') ? 'text-accent font-medium' : '' }}">Hosting</a>
      <a href="{{ route('domain.search') }}" class="whitespace-nowrap {{ request()->routeIs('domain.*') ? 'text-accent font-medium' : '' }}">Domain</a>
      <a href="{{ route('announcements.index') }}" class="whitespace-nowrap">Pengumuman</a>
    </nav>
  </header>

  <main class="flex-1">
    {{-- Pesan flash tetap dalam container, apa pun jenis halamannya. --}}
    @if (session('success') || session('error'))
      <div class="max-w-6xl mx-auto px-6 pt-6">
        @if (session('success'))
          <div class="mb-3 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
          </div>
        @endif
        @if (session('error'))
          <div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
          </div>
        @endif
      </div>
    @endif

    {{-- Halaman biasa memakai @section('content') dan dibungkus container.
         Halaman yang butuh lebar penuh (mis. hero landing) memakai
         @section('full-width') dan mengatur containernya sendiri. --}}
    @hasSection('full-width')
      @yield('full-width')
    @else
      <div class="max-w-6xl mx-auto px-6 py-10">
        @yield('content')
      </div>
    @endif
  </main>

  <footer class="bg-white border-t border-slate-200 py-8">
    <div class="max-w-6xl mx-auto px-6 text-sm text-slate-500">
      @if ($footerPages->isNotEmpty())
        <nav class="flex flex-wrap gap-x-5 gap-y-2 mb-4">
          @foreach ($footerPages as $fp)
            <a href="{{ route('page.show', $fp->slug) }}" class="hover:text-accent">{{ $fp->title }}</a>
          @endforeach
        </nav>
      @endif
      <p>{{ Setting::get('footer_text') ?: '© ' . date('Y') . ' ' . $siteName . '. Semua hak dilindungi.' }}</p>
    </div>
  </footer>

  @include('public.partials.livechat')
</body>
</html>
