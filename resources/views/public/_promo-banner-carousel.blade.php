{{--
    Carousel banner promo bersama — dipakai di Beranda, Katalog, dan Cek
    Domain. Sebelumnya kode ini disalin langsung di halaman Katalog saja;
    dijadikan partial supaya perbaikan di masa depan cukup di satu
    tempat, bukan disalin ulang ke tiap halaman.

    Butuh variabel $banners (koleksi PromoBanner, sudah difilter live()
    dan forPage() dari controller masing-masing).
--}}
@if ($banners->isNotEmpty())
  <div class="relative rounded-2xl overflow-hidden mb-8" id="promoBannerCarousel">
    @foreach ($banners as $i => $banner)
      <div class="promo-slide {{ $i === 0 ? '' : 'hidden' }}" data-slide="{{ $i }}">
        @if ($banner->link_url)
          <a href="{{ $banner->link_url }}" @if ($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="block relative">
        @else
          <div class="relative">
        @endif

          <img src="{{ route('banner.file', $banner->image) }}" alt="{{ $banner->title }}" class="w-full h-48 sm:h-64 object-cover">

          @if ($banner->title || $banner->subtitle || $banner->button_text)
            <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/20 to-transparent flex items-center">
              <div class="px-6 sm:px-10 max-w-lg">
                <h2 class="text-white text-xl sm:text-2xl font-bold mb-1">{{ $banner->title }}</h2>
                @if ($banner->subtitle)
                  <p class="text-white/80 text-sm mb-3">{{ $banner->subtitle }}</p>
                @endif
                @if ($banner->button_text)
                  <span class="btn btn-primary !inline-flex">{{ $banner->button_text }}</span>
                @endif
              </div>
            </div>
          @endif

        @if ($banner->link_url)
          </a>
        @else
          </div>
        @endif
      </div>
    @endforeach

    @if ($banners->count() > 1)
      <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
        @foreach ($banners as $i => $banner)
          <button type="button" class="promo-dot w-2 h-2 rounded-full {{ $i === 0 ? 'bg-white' : 'bg-white/40' }}" data-dot="{{ $i }}"></button>
        @endforeach
      </div>
    @endif
  </div>

  @if ($banners->count() > 1)
    <script>
      (function () {
        const slides = document.querySelectorAll('#promoBannerCarousel .promo-slide');
        const dots = document.querySelectorAll('#promoBannerCarousel .promo-dot');
        let current = 0;

        function show(index) {
          slides.forEach((s, i) => s.classList.toggle('hidden', i !== index));
          dots.forEach((d, i) => d.classList.toggle('bg-white', i === index));
          dots.forEach((d, i) => d.classList.toggle('bg-white/40', i !== index));
          current = index;
        }

        dots.forEach(dot => dot.addEventListener('click', () => show(parseInt(dot.dataset.dot))));

        setInterval(() => show((current + 1) % slides.length), 5000);
      })();
    </script>
  @endif
@endif
