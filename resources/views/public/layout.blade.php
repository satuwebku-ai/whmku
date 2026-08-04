@php
  use App\Models\Setting;
  $siteName = Setting::get('site_name', config('app.name', 'Lumora Hosting'));
  $footerPages = \App\Models\Page::published()->where('show_in_footer', true)->orderBy('sort_order')->get();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  @include('public.partials.head')

  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style type="text/tailwindcss">
    @theme {
      --font-sans: "Inter", sans-serif;
      --color-accent: #6366F1;
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
  </style>
</head>
<body class="antialiased bg-slate-50 text-slate-800 min-h-screen flex flex-col">

  <header class="bg-white border-b border-slate-200">
    <div class="max-w-4xl mx-auto px-6 h-16 flex items-center justify-between">
      <a href="{{ url('/') }}" class="flex items-center gap-2.5">
        <span class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center">
          <svg viewBox="0 0 24 24" class="text-white" fill="none" stroke="currentColor" stroke-width="2.2" style="width:17px;height:17px"><path d="M13 2 3 14h7l-1 8 11-12h-7l1-8z"/></svg>
        </span>
        <span class="font-bold text-slate-800">{{ $siteName }}</span>
      </a>
      <nav class="flex items-center gap-5 text-sm text-slate-600">
        <a href="{{ route('announcements.index') }}" class="hover:text-accent">Pengumuman</a>
      </nav>
    </div>
  </header>

  <main class="flex-1 py-10">
    <div class="max-w-4xl mx-auto px-6">
      @yield('content')
    </div>
  </main>

  <footer class="bg-white border-t border-slate-200 py-8 mt-10">
    <div class="max-w-4xl mx-auto px-6 text-sm text-slate-500">
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
