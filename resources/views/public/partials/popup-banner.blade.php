@php
  use App\Models\Setting;

  $popupEnabled = Setting::get('popup_banner_enabled', '0') === '1';
@endphp

@if ($popupEnabled)
  @php
    $popupImage = Setting::get('popup_banner_image');
    $popupTitle = Setting::get('popup_banner_title');
    $popupDesc  = Setting::get('popup_banner_description');
    $popupBtn   = Setting::get('popup_banner_button_text', 'Lihat Sekarang');
    $popupUrl   = Setting::get('popup_banner_link_url');
    $popupFreq  = Setting::get('popup_banner_frequency', 'once_per_day');
  @endphp

  {{-- Kalau tidak ada gambar MAUPUN judul/deskripsi sama sekali, tidak
       ada yang berguna untuk ditampilkan -- daripada muncul modal
       kosong, lebih baik tidak ditampilkan sama sekali. --}}
  @if ($popupImage || $popupTitle || $popupDesc)
    <div id="popupBannerOverlay" class="hidden fixed inset-0 z-[95] items-center justify-center p-4" style="background:rgba(15,23,42,.65)">
      <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden relative" style="animation:popupBannerIn .25s ease-out">
        <button type="button" id="popupBannerClose" aria-label="Tutup"
                class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center z-10">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>

        @if ($popupImage)
          <img src="{{ route('branding.file', $popupImage) }}" alt="{{ $popupTitle }}" class="w-full h-48 object-cover">
        @endif

        @if ($popupTitle || $popupDesc || $popupUrl)
          <div class="p-6">
            @if ($popupTitle)
              <h2 class="text-lg font-bold text-slate-800 mb-1.5">{{ $popupTitle }}</h2>
            @endif
            @if ($popupDesc)
              <p class="text-sm text-slate-500 mb-4">{{ $popupDesc }}</p>
            @endif
            @if ($popupUrl)
              <a href="{{ $popupUrl }}" class="btn btn-primary w-full !justify-center">{{ $popupBtn }}</a>
            @endif
          </div>
        @endif
      </div>
    </div>

    <style>
      @keyframes popupBannerIn {
        from { opacity: 0; transform: scale(.95); }
        to   { opacity: 1; transform: scale(1); }
      }
    </style>

    <script>
      (function () {
        const STORAGE_KEY = 'lumora_popup_banner_seen';
        const frequency = @json($popupFreq);

        function alreadySeen() {
          if (frequency === 'every_visit') return false;

          try {
            const raw = frequency === 'once_per_session'
              ? sessionStorage.getItem(STORAGE_KEY)
              : localStorage.getItem(STORAGE_KEY);

            if (!raw) return false;

            if (frequency === 'once_per_day') {
              // Simpan sebagai tanggal (YYYY-MM-DD) -- kalau tanggal
              // tersimpan beda dari hari ini, dianggap belum pernah
              // lihat HARI INI, jadi muncul lagi.
              const today = new Date().toISOString().slice(0, 10);
              return raw === today;
            }

            return true; // once_per_session: sudah ada tandanya = sudah pernah lihat.
          } catch (e) {
            return false; // Storage tidak tersedia (mis. mode privat ketat) -- tampilkan saja.
          }
        }

        function markSeen() {
          if (frequency === 'every_visit') return;

          try {
            if (frequency === 'once_per_session') {
              sessionStorage.setItem(STORAGE_KEY, '1');
            } else if (frequency === 'once_per_day') {
              localStorage.setItem(STORAGE_KEY, new Date().toISOString().slice(0, 10));
            }
          } catch (e) { /* Diamkan -- bukan fitur krusial. */ }
        }

        if (alreadySeen()) return;

        const overlay = document.getElementById('popupBannerOverlay');
        const closeBtn = document.getElementById('popupBannerClose');

        function show() {
          overlay.classList.remove('hidden');
          overlay.classList.add('flex');
        }

        function close() {
          overlay.classList.add('hidden');
          overlay.classList.remove('flex');
          markSeen();
        }

        // Ditunda sedikit (800ms) supaya tidak "menyerang" pengunjung
        // sepersekian detik setelah halaman baru mulai render.
        setTimeout(show, 800);

        closeBtn.addEventListener('click', close);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !overlay.classList.contains('hidden')) close(); });
      })();
    </script>
  @endif
@endif
