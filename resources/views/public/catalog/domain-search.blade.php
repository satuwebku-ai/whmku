@extends('public.layout')

@php
  use App\Models\Setting;
  $themeColor = Setting::get('theme_color', '#6366F1');

  $seoTitle = 'Cek Ketersediaan Domain';
  $seoDescription = 'Cari dan daftarkan domain impian Anda — proses cepat, harga transparan.';

  // Ada berapa yang tersedia, untuk ringkasan hasil.
  $availableCount = ($results && $results['success'])
    ? collect($results['results'])->filter(fn ($v) => $v === true)->count()
    : 0;

  $unknownCount = ($results && $results['success'])
    ? count($results['unknown'] ?? [])
    : 0;
@endphp

@section('full-width')

  {{-- ══════════ Kotak pencarian ══════════ --}}
  <section class="relative overflow-hidden">
    <div class="absolute inset-0" style="background:linear-gradient(160deg,#1e1b4b 0%,#312e81 40%,#4c1d95 75%,#1e1b4b 100%)"></div>
    <div class="absolute inset-0 opacity-[0.12]" style="background-image:radial-gradient(circle at 20% 20%, white 1px, transparent 1px);background-size:32px 32px"></div>

    <div class="relative max-w-3xl mx-auto px-6 py-14 text-center">
      <p class="text-white/50 text-xs font-semibold tracking-widest uppercase mb-2">Daftar Domain Baru</p>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-white mb-6">
        Domain <span class="text-amber-300">keren</span> bikin websitemu gampang diingat
      </h1>

      <form method="GET" action="{{ route('domain.search') }}" id="searchForm"
            class="bg-white rounded-2xl p-2 flex flex-col sm:flex-row gap-2 shadow-xl">
        <div class="flex items-center gap-2 flex-1 px-3">
          <i class="fa-solid fa-globe text-slate-300"></i>
          <input type="text" name="domain" value="{{ $query }}"
                 placeholder="ketik nama domain yang kamu inginkan…"
                 class="w-full py-2.5 text-sm outline-none bg-transparent" required autofocus>
        </div>

        {{-- Ekstensi terpilih ikut terkirim dari panel di bawah --}}
        <div id="selectedMirror"></div>

        <button type="submit" class="btn btn-primary !py-3 !px-6 shrink-0">
          <i class="fa-solid fa-magnifying-glass text-xs"></i> Cari Domain
        </button>
      </form>

      <p class="text-white/40 text-xs mt-3">
        Sudah punya domain? Hubungi kami untuk proses transfer.
      </p>
    </div>
  </section>

  <div class="max-w-6xl mx-auto px-6 py-10">
    <div class="grid lg:grid-cols-4 gap-6">

      {{-- ══════════ Sidebar kategori ══════════ --}}
      <div class="space-y-5">
        <div class="card overflow-hidden">
          <div class="px-4 py-3 border-b border-slate-100">
            <p class="font-semibold text-slate-800 text-sm">Kategori</p>
            <p class="text-[11px] text-slate-400">Klik untuk memilih sekelompok ekstensi</p>
          </div>
          <div class="divide-y divide-slate-100">
            @forelse ($groups as $groupName => $tlds)
              <button type="button" data-group="{{ $groupName }}"
                      class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-accent text-left">
                {{ $groupName }}
                <span class="text-xs text-slate-400">{{ $tlds->count() }}</span>
              </button>
            @empty
              <p class="px-4 py-3 text-xs text-slate-400">Belum ada ekstensi.</p>
            @endforelse
          </div>
        </div>

        <div class="card p-4">
          <p class="text-xs text-slate-500 leading-relaxed">
            <i class="fa-solid fa-circle-info text-accent"></i>
            Biarkan kosong untuk mencari otomatis di semua ekstensi yang kami jual
            (maksimal 20 hasil), atau centang ekstensi tertentu untuk hasil yang lebih spesifik.
          </p>
        </div>
      </div>

      {{-- ══════════ Ekstensi & hasil ══════════ --}}
      <div class="lg:col-span-3 space-y-6">

        {{-- Pilih ekstensi --}}
        @if ($tldPrices->isNotEmpty())
          <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
              <div>
                <p class="font-semibold text-slate-800 text-sm">Ekstensi Domain</p>
                <p class="text-[11px] text-slate-400">
                  <span id="selCount">0</span> dipilih —
                  <span class="text-slate-500">kosongkan untuk mencari otomatis di semua ekstensi</span>
                </p>
              </div>
              <div class="flex items-center gap-3 text-xs">
                <button type="button" id="selAll" class="text-accent hover:underline">Pilih semua</button>
                <button type="button" id="selNone" class="text-slate-400 hover:underline">Kosongkan</button>
              </div>
            </div>

            <div class="p-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
              @foreach ($tldPrices as $ext => $tld)
                <label data-ext-group="{{ $tld->search_group_label }}"
                       class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 cursor-pointer hover:border-accent/50 transition-colors">
                  <span class="flex items-center gap-2 min-w-0">
                    <input type="checkbox" value="{{ $ext }}" data-ext
                           @checked(in_array($ext, $selected))
                           class="rounded border-slate-300 text-accent focus:ring-accent/40 shrink-0">
                    <span class="text-sm text-slate-700 truncate">{{ $ext }}</span>
                    @if ($tld->is_demo)
                      <span class="text-[9px] font-bold px-1 py-0.5 rounded bg-amber-100 text-amber-700 shrink-0">DEMO</span>
                    @endif
                  </span>
                  <span class="text-[10px] text-slate-400 shrink-0">
                    {{ $tld->register_price >= 1000000
                        ? 'Rp' . number_format($tld->register_price / 1000000, 1) . 'jt'
                        : 'Rp' . number_format($tld->register_price / 1000, 0) . 'rb' }}
                  </span>
                </label>
              @endforeach
            </div>
          </div>
        @else
          <div class="card p-8 text-center">
            <p class="text-slate-500 text-sm mb-1">Belum ada ekstensi yang ditampilkan.</p>
            <p class="text-xs text-slate-400">
              Atur lewat admin: <b>Domain → TLD Pricing</b>, centang kolom "Tampil di Web".
            </p>
          </div>
        @endif

        {{-- Hasil pencarian --}}
        @if ($query)
          <div>
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
              <h2 class="font-semibold text-slate-800">Hasil pencarian untuk "{{ $query }}"</h2>
              <div class="flex items-center gap-3 text-xs">
                @if ($results && $results['success'] && $availableCount > 0)
                  <span class="text-emerald-600 font-medium">{{ $availableCount }} tersedia</span>
                @endif
                @if ($unknownCount > 0)
                  <span class="text-amber-600">{{ $unknownCount }} belum pasti</span>
                @endif
              </div>
            </div>

            @if (! $results['success'])
              <div class="card p-6 text-center">
                <p class="text-sm text-rose-600 mb-1">
                  <i class="fa-solid fa-circle-exclamation"></i> {{ $results['message'] }}
                </p>
                <p class="text-xs text-slate-400">Coba pilih lebih sedikit ekstensi, atau ulangi beberapa saat lagi.</p>
              </div>
            @else
              <div class="space-y-2">
                @forelse ($results['results'] as $domainName => $available)
                  @php
                    $ext = '.' . \Illuminate\Support\Str::after($domainName, '.');
                    $tld = $allTldPrices->get($ext);
                  @endphp

                  <div class="rounded-xl border px-5 py-3.5 flex items-center justify-between gap-4 flex-wrap
                              {{ $available === true ? 'border-emerald-200 bg-emerald-50/40' : ($available === null ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200 bg-slate-50/60') }}">
                    <div class="min-w-0">
                      <p class="font-semibold {{ $available === true ? 'text-slate-800' : ($available === null ? 'text-slate-700' : 'text-slate-400 line-through') }}">
                        {{ $domainName }}
                        @if ($tld?->is_demo)
                          <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 align-middle">DEMO</span>
                        @endif
                      </p>
                      @if ($available === true && $tld)
                        <p class="text-xs text-slate-500">
                          Rp {{ number_format($tld->register_price, 0, ',', '.') }} <span class="text-slate-400">/tahun</span>
                        </p>
                      @elseif ($available === null)
                        <p class="text-xs text-amber-700">Status belum bisa dipastikan — akan dicek ulang saat pemesanan.</p>
                      @endif
                    </div>

                    @if ($available === true)
                      <form method="POST" action="{{ route('domain.add-to-cart') }}" data-add-domain class="flex items-center gap-2 shrink-0">
                        @csrf
                        <input type="hidden" name="domain_name" value="{{ $domainName }}">
                        <input type="hidden" name="tld_id" value="{{ $tld->id ?? '' }}">
                        <select name="years" class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 bg-white text-slate-600">
                          @for ($y = 1; $y <= min($tld->max_years ?? 5, 5); $y++)
                            <option value="{{ $y }}">{{ $y }} thn</option>
                          @endfor
                        </select>
                        <button type="submit" class="btn btn-primary !py-1.5 !px-3 text-xs" {{ $tld ? '' : 'disabled' }}>
                          <i class="fa-solid fa-cart-plus text-xs"></i> Tambah
                        </button>
                      </form>
                    @elseif ($available === null)
                      <span class="text-xs text-amber-700 shrink-0">Perlu Dicek Manual</span>
                    @else
                      <span class="text-xs text-slate-400 shrink-0">Tidak Tersedia</span>
                    @endif
                  </div>
                @empty
                  <div class="card p-6 text-center text-sm text-slate-400">Tidak ada hasil untuk pencarian ini.</div>
                @endforelse
              </div>
            @endif
          </div>

          {{-- Langkah pemesanan --}}
          <div class="card p-5 flex items-center justify-between gap-4 flex-wrap">
            <a href="{{ route('home') }}" class="btn btn-outline">Kembali</a>

            <div class="flex items-center gap-2 sm:gap-4 text-xs">
              @foreach (['Domain', 'Hosting', 'Keranjang', 'Bayar'] as $i => $step)
                <div class="flex items-center gap-2">
                  <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px]
                               {{ $i === 0 ? 'text-white' : 'bg-slate-200 text-slate-500' }}"
                        @if ($i === 0) style="background:{{ $themeColor }}" @endif>{{ $i + 1 }}</span>
                  <span class="{{ $i === 0 ? 'font-semibold text-slate-800' : 'text-slate-400' }}">{{ $step }}</span>
                  @if ($i < 3)
                    <span class="w-4 sm:w-8 h-px bg-slate-200"></span>
                  @endif
                </div>
              @endforeach
            </div>

            <a href="{{ route('cart.index') }}" class="btn btn-primary">
              Lanjut <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Badge keranjang mengambang — sengaja fixed (bukan ikut scroll di
       dalam daftar hasil), supaya tetap terlihat berapa domain yang sudah
       ditambahkan meski sedang scroll jauh ke bawah daftar hasil.
       Ditaruh kiri bawah supaya tidak tumpang tindih dengan widget
       live chat yang sudah ada di kanan bawah. --}}
  <div id="floatingCartBadge" class="fixed left-5 bottom-5 z-[90] hidden">
    <a href="{{ route('cart.index') }}" class="flex items-center gap-2.5 bg-slate-800 text-white rounded-full pl-4 pr-5 py-3 shadow-xl hover:bg-slate-700 transition-colors">
      <span class="relative">
        <i class="fa-solid fa-cart-shopping"></i>
        <span id="floatingCartCount" class="absolute -top-2 -right-2 w-4 h-4 rounded-full bg-accent text-white text-[9px] font-bold flex items-center justify-center">0</span>
      </span>
      <span class="text-sm font-medium">Lihat Keranjang</span>
    </a>
  </div>

  <div id="toastBox" class="fixed left-5 bottom-24 z-[95] hidden max-w-xs">
    <div id="toastInner" class="text-sm rounded-lg px-4 py-3 shadow-xl flex items-center gap-2 text-white bg-emerald-600">
      <i class="fa-solid fa-circle-check shrink-0"></i>
      <span id="toastMsg"></span>
    </div>
  </div>

  <script>
    (function () {
      const boxes  = Array.from(document.querySelectorAll('[data-ext]'));
      const mirror = document.getElementById('selectedMirror');
      const count  = document.getElementById('selCount');

      // Checkbox berada di luar <form> pencarian (beda kolom layout), jadi
      // pilihannya disalin jadi hidden input tepat sebelum submit.
      function sync() {
        mirror.innerHTML = '';
        let n = 0;

        boxes.forEach(function (b) {
          if (!b.checked) return;
          n++;
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'extensions[]';
          hidden.value = b.value;
          mirror.appendChild(hidden);
        });

        count.textContent = n;
      }

      boxes.forEach(b => b.addEventListener('change', sync));

      document.getElementById('selAll')?.addEventListener('click', function () {
        boxes.forEach(b => b.checked = true); sync();
      });
      document.getElementById('selNone')?.addEventListener('click', function () {
        boxes.forEach(b => b.checked = false); sync();
      });

      // Klik kategori → centang hanya ekstensi dalam kelompok itu.
      document.querySelectorAll('[data-group]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          const group = btn.dataset.group;
          boxes.forEach(function (b) {
            b.checked = b.closest('[data-ext-group]')?.dataset.extGroup === group;
          });
          sync();
        });
      });

      sync();
    })();

    (function () {
      const badge      = document.getElementById('floatingCartBadge');
      const badgeCount = document.getElementById('floatingCartCount');
      const topbarBadge = document.getElementById('topbarCartBadge');
      const toast      = document.getElementById('toastBox');
      const toastInner = document.getElementById('toastInner');
      const toastMsg   = document.getElementById('toastMsg');
      let toastTimer = null;

      function updateCartCount(n) {
        badgeCount.textContent = n;
        badge.classList.toggle('hidden', n <= 0);

        if (topbarBadge) {
          topbarBadge.textContent = n;
          topbarBadge.classList.toggle('hidden', n <= 0);
        }
      }

      function showToast(message, isError) {
        toastMsg.textContent = message;
        toastInner.className = 'text-sm rounded-lg px-4 py-3 shadow-xl flex items-center gap-2 text-white '
          + (isError ? 'bg-rose-600' : 'bg-emerald-600');
        toast.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 3500);
      }

      // Mulai dari jumlah yang sudah dirender server di topbar, supaya
      // badge mengambang langsung akurat kalau keranjang sudah berisi
      // sesuatu dari kunjungan sebelumnya.
      updateCartCount(parseInt(topbarBadge?.textContent || '0', 10));

      document.querySelectorAll('form[data-add-domain]').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
          e.preventDefault();

          const btn = form.querySelector('button[type="submit"]');
          const originalHtml = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i>';

          try {
            const res = await fetch(form.action, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
              },
              body: new FormData(form),
            });
            const data = await res.json();

            showToast(data.message, !data.success);

            if (data.success) {
              updateCartCount(data.cart_count);
              btn.innerHTML = '<i class="fa-solid fa-check text-xs"></i> Ditambahkan';
              // Tombol dikembalikan normal setelah sebentar — klien tetap
              // bisa menambah domain lain kapan saja, tidak terkunci.
              setTimeout(function () {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
              }, 1500);
            } else {
              btn.innerHTML = originalHtml;
              btn.disabled = false;
            }
          } catch (err) {
            showToast('Gagal menambah domain. Periksa koneksi Anda dan coba lagi.', true);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          }
        });
      });
    })();
  </script>

@endsection
