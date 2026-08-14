<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pratinjau — {{ $meta['label'] }}</title>
  <style>html{visibility:hidden}</style>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" onload="document.documentElement.style.visibility='visible'"></script>
  <script>setTimeout(function(){document.documentElement.style.visibility='visible'},2500)</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="antialiased bg-slate-100 min-h-screen py-10 px-4">

  <div class="max-w-lg mx-auto mb-4 text-center">
    <p class="text-xs text-slate-400">
      <i class="fa-solid fa-circle-info"></i>
      Pratinjau dengan data contoh — bukan tampilan persis di tiap aplikasi email/WhatsApp,
      tapi cukup mewakili susunan &amp; isinya.
    </p>
  </div>

  {{-- Kartu Email --}}
  <div class="max-w-lg mx-auto bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="bg-slate-800 px-6 py-4 text-center">
      @if ($siteLogo)
        <img src="{{ route('branding.file', $siteLogo) }}" alt="{{ $siteName }}" class="h-8 mx-auto">
      @else
        <p class="text-white font-bold">{{ $siteName }}</p>
      @endif
    </div>
    <div class="p-6">
      <p class="text-xs text-slate-400 mb-1">Subjek</p>
      <p class="font-semibold text-slate-800 mb-5">{{ $subject ?: '(kosong)' }}</p>

      <div class="text-sm text-slate-600 space-y-3 leading-relaxed">
        @forelse ($lines as $line)
          <p>{!! preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', e($line)) !!}</p>
        @empty
          <p class="text-slate-300 italic">(isi email kosong)</p>
        @endforelse
      </div>

      @if ($action)
        <div class="mt-6">
          <span class="inline-block bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg">
            {{ $action['label'] }}
          </span>
          <p class="text-[11px] text-slate-400 mt-1">↳ {{ $action['url'] }}</p>
        </div>
      @endif
    </div>
  </div>

  {{-- Gelembung WhatsApp --}}
  @if (trim((string) $bodyWhatsapp) !== '')
    <div class="max-w-lg mx-auto">
      <p class="text-xs text-slate-400 mb-2 text-center"><i class="fa-brands fa-whatsapp"></i> Pratinjau WhatsApp</p>
      <div class="bg-[#dcf8c6] rounded-2xl rounded-tl-none p-4 text-sm text-slate-800 whitespace-pre-line shadow-sm max-w-md mx-auto"
           style="font-family: -apple-system, sans-serif;">{!! preg_replace('/\*(.+?)\*/', '<b>$1</b>', e($bodyWhatsapp)) !!}</div>
    </div>
  @endif

  <div class="max-w-lg mx-auto text-center mt-6">
    <button onclick="window.close()" class="text-xs text-slate-400 hover:text-slate-600">Tutup tab ini</button>
  </div>

</body>
</html>
