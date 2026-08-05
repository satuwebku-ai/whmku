@extends('layouts.admin')

@section('title', 'TLD Pricing')

@section('content')

  @include('admin.domains._nav')

  <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">TLD Pricing</h1>
      <p class="text-sm text-slate-500 mt-1">Atur harga jual per ekstensi domain. Hanya TLD aktif yang muncul di Cek Domain.</p>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" onclick="document.getElementById('importPanel').classList.toggle('hidden')" class="btn btn-outline">
        <i class="fa-solid fa-cloud-arrow-down text-xs"></i> Tarik Harga Registrar
      </button>
      <button type="button" onclick="document.getElementById('markupPanel').classList.toggle('hidden')" class="btn btn-outline">
        <i class="fa-solid fa-percent text-xs"></i> Markup Massal
      </button>
      <a href="{{ route('admin.tlds.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus text-xs"></i> Tambah TLD
      </a>
    </div>
  </div>

  {{-- Panel tarik harga dari registrar --}}
  <div id="importPanel" class="hidden card p-5 mb-5 border-emerald-200 bg-emerald-50/40">
    <h2 class="text-sm font-semibold text-slate-800 mb-1">Tarik Harga Modal dari Registrar</h2>
    <p class="text-xs text-slate-500 mb-4">
      Mengambil harga modal langsung lewat API registrar, lalu menampilkannya sebagai
      <b>tabel pratinjau</b> — di sana harga bisa diperiksa dan disesuaikan satu per satu
      sebelum benar-benar disimpan. Tidak ada yang tersimpan sampai kamu menekan Terapkan.
    </p>

    <form method="POST" action="{{ route('admin.tld.sync-preview') }}" class="space-y-4">
      @csrf

      <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        <div>
          <label class="form-label">Registrar</label>
          <select name="registrar_id" class="form-input">
            @forelse ($registrars as $r)
              <option value="{{ $r->id }}" @selected($r->is_default)>{{ $r->name }}</option>
            @empty
              <option value="">Belum ada registrar aktif</option>
            @endforelse
          </select>
        </div>
        <div>
          <label class="form-label">Markup Awal (%)</label>
          <input type="number" step="0.1" min="0" name="markup" value="30" class="form-input">
          <p class="text-[11px] text-slate-400 mt-1">Masih bisa diubah di pratinjau.</p>
        </div>
        <div>
          <label class="form-label">Pembulatan</label>
          <select name="round_to" class="form-input">
            <option value="1000" selected>Ribuan</option>
            <option value="0">Tanpa</option>
            <option value="5000">5 ribu</option>
            <option value="10000">10 ribu</option>
          </select>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600 pb-2.5">
          <input type="checkbox" name="only_sellable" value="1" checked class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Hanya yang berstatus "Sell"
        </label>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-cloud-arrow-down text-xs"></i> Tarik &amp; Lihat Pratinjau
        </button>
      </div>

      <p class="text-[11px] text-slate-400">
        "Hanya yang berstatus Sell" menyaring TLD yang sudah kamu aktifkan untuk dijual di panel
        registrar — biasanya jauh lebih sedikit dari total {{ $counts['all'] }} TLD yang tersedia.
      </p>
    </form>
  </div>

  {{-- Panel margin massal (meniru "Set prices using Profit Margin") --}}
  <div id="markupPanel" class="hidden card p-5 mb-5 border-indigo-200 bg-indigo-50/40">
    <h2 class="text-sm font-semibold text-slate-800 mb-1">Tentukan Harga Jual dari Margin</h2>
    <p class="text-xs text-slate-500 mb-4">
      Harga jual dihitung dari <b>harga modal</b>, jadi aman dijalankan berulang kali.
      @if ($counts['no_cost'] > 0)
        <span class="text-amber-700"><i class="fa-solid fa-triangle-exclamation"></i>
        {{ $counts['no_cost'] }} TLD belum punya harga modal dan akan dilewati — isi lewat
        <b>Impor Harga</b> atau ketik langsung di kolom Modal.</span>
      @endif
    </p>

    <form method="POST" action="{{ route('admin.tld.bulk-markup') }}" id="markupForm"
          data-confirm="Terapkan margin ini? Harga jual yang ada akan ditimpa."
          data-confirm-title="Terapkan Margin" data-confirm-style="warn" data-confirm-label="Ya, Terapkan"
          class="space-y-4">
      @csrf
      <input type="hidden" name="search" value="{{ request('search') }}">
      <input type="hidden" name="selected_ids" id="selectedIds">

      {{-- Jenis profit --}}
      <div class="flex items-center gap-5 text-sm">
        <span class="font-medium text-slate-700">Hitung profit dalam:</span>
        <label class="flex items-center gap-2 text-slate-600">
          <input type="radio" name="profit_type" value="percent" checked class="text-accent focus:ring-accent/40">
          Persen (%)
        </label>
        <label class="flex items-center gap-2 text-slate-600">
          <input type="radio" name="profit_type" value="fixed" class="text-accent focus:ring-accent/40">
          Rupiah tetap (Rp)
        </label>
      </div>

      {{-- Margin per kolom --}}
      <div class="grid sm:grid-cols-3 gap-3">
        <div>
          <label class="form-label">Margin Register</label>
          <input type="number" step="0.01" min="0" name="margin_register" value="30" class="form-input" required>
        </div>
        <div>
          <label class="form-label">Margin Renew <span class="text-slate-400 font-normal">(opsional)</span></label>
          <input type="number" step="0.01" min="0" name="margin_renew" class="form-input" placeholder="ikut Register">
        </div>
        <div>
          <label class="form-label">Margin Transfer <span class="text-slate-400 font-normal">(opsional)</span></label>
          <input type="number" step="0.01" min="0" name="margin_transfer" class="form-input" placeholder="ikut Register">
        </div>
      </div>

      {{-- Pembulatan --}}
      <div class="grid sm:grid-cols-3 gap-3 items-end pt-3 border-t border-indigo-100">
        <div>
          <label class="form-label">Pembulatan</label>
          <select name="round_mode" id="roundMode" class="form-input">
            <option value="multiple" selected>Bulatkan ke kelipatan</option>
            <option value="ending">Akhiri dengan angka tertentu</option>
            <option value="none">Tanpa pembulatan</option>
          </select>
        </div>
        <div data-round-field>
          <label class="form-label">Kelipatan (Rp)</label>
          <select name="round_step" class="form-input">
            <option value="1000" selected>1.000</option>
            <option value="5000">5.000</option>
            <option value="10000">10.000</option>
            <option value="50000">50.000</option>
          </select>
        </div>
        <div data-round-field data-tail class="hidden">
          <label class="form-label">Akhiran (Rp)</label>
          <input type="number" name="round_tail" value="9000" min="0" class="form-input">
          <p class="text-[11px] text-slate-400 mt-1">Mis. kelipatan 10.000 + akhiran 9.000 → 219.000</p>
        </div>
      </div>

      {{-- Cakupan & aksi --}}
      <div class="grid sm:grid-cols-4 gap-3 items-end pt-3 border-t border-indigo-100">
        <div>
          <label class="form-label">Terapkan ke</label>
          <select name="scope" class="form-input">
            <option value="all">Semua TLD</option>
            <option value="selected">Hanya yang dicentang di tabel</option>
            <option value="filtered" @selected(request('search'))>Hasil pencarian "{{ request('search') ?: '—' }}"</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="only_empty" value="1" class="rounded border-slate-300 text-accent focus:ring-accent/40">
            Hanya yang belum berharga
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="activate" value="1" class="rounded border-slate-300 text-accent focus:ring-accent/40">
            Aktifkan sekalian
          </label>
        </div>
        <div class="sm:col-span-2 flex sm:justify-end">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Terapkan Margin</button>
        </div>
      </div>
    </form>
  </div>

  {{-- Filter status --}}
  <div class="flex items-center gap-2 mb-4 flex-wrap">
    @php $st = request('status'); @endphp
    <a href="{{ route('admin.tlds.index') }}" class="px-3 py-1.5 rounded-full text-xs font-medium {{ !$st ? 'bg-accent text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
      Semua ({{ $counts['all'] }})
    </a>
    <a href="{{ route('admin.tlds.index', ['status' => 'active']) }}" class="px-3 py-1.5 rounded-full text-xs font-medium {{ $st === 'active' ? 'bg-accent text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
      Aktif ({{ $counts['active'] }})
    </a>
    <a href="{{ route('admin.tlds.index', ['status' => 'inactive']) }}" class="px-3 py-1.5 rounded-full text-xs font-medium {{ $st === 'inactive' ? 'bg-accent text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
      Nonaktif ({{ $counts['inactive'] }})
    </a>
  </div>

  <div id="tldTableWrap">
  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      @if (request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
      @endif
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari ekstensi, mis. .com" class="form-input sm:max-w-xs">
      <select name="per_page" class="form-input sm:max-w-[150px]">
        @foreach ([25, 50, 100, 200] as $n)
          <option value="{{ $n }}" @selected((int) request('per_page', 25) === $n)>{{ $n }} baris</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-outline">Tampilkan</button>
      @if (request('search'))
        <a href="{{ route('admin.tlds.index', ['status' => request('status')]) }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    {{-- Tabel bisa diedit langsung: ketik harga di kolomnya, lalu Simpan. --}}
    <form method="POST" action="{{ route('admin.tld.bulk-update') }}" id="bulkForm">
      @csrf

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
              <th class="px-3 py-2.5 font-semibold text-center">
                <input type="checkbox" id="checkAllRows" class="rounded border-slate-300 text-accent focus:ring-accent/40" title="Pilih semua">
              </th>
              <th class="px-4 py-2.5 font-semibold">Ekstensi</th>
              <th class="px-3 py-2.5 font-semibold text-right">Modal (Rp)</th>
              <th class="px-3 py-2.5 font-semibold text-right">Register (Rp)</th>
              <th class="px-3 py-2.5 font-semibold text-right">Renew (Rp)</th>
              <th class="px-3 py-2.5 font-semibold text-right">Transfer (Rp)</th>
              <th class="px-3 py-2.5 font-semibold text-right">Margin</th>
              <th class="px-3 py-2.5 font-semibold text-center">Aktif</th>
              <th class="px-4 py-2.5 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse ($tlds as $tld)
              <tr class="hover:bg-slate-50/60" data-row>
                <td class="px-3 py-2 text-center">
                  <input type="checkbox" value="{{ $tld->id }}" data-select
                         class="rounded border-slate-300 text-accent focus:ring-accent/40">
                </td>
                <td class="px-4 py-2 font-medium text-slate-700 whitespace-nowrap">
                  {{ $tld->extension }}
                  <span class="block text-[10px] text-slate-400 font-normal">{{ $tld->registrar->name ?? 'manual' }}</span>
                </td>

                <td class="px-3 py-2">
                  <input type="number" step="1" min="0" data-cost
                         name="rows[{{ $tld->id }}][cost_register]"
                         value="{{ (int) $tld->cost_register ?: '' }}" placeholder="0"
                         class="w-28 px-2 py-1.5 rounded-lg border border-slate-200 text-right text-sm outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
                </td>

                <td class="px-3 py-2">
                  <input type="number" step="1" min="0" data-register
                         name="rows[{{ $tld->id }}][register_price]"
                         value="{{ (int) $tld->register_price ?: '' }}" placeholder="0"
                         class="w-28 px-2 py-1.5 rounded-lg border {{ $tld->register_price > 0 ? 'border-slate-200' : 'border-rose-200 bg-rose-50/40' }} text-right text-sm outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
                </td>

                <td class="px-3 py-2 text-right">
                  <input type="number" step="1" min="0"
                         name="rows[{{ $tld->id }}][renew_price]"
                         value="{{ (int) $tld->renew_price ?: '' }}" placeholder="0"
                         class="w-28 px-2 py-1.5 rounded-lg border border-slate-200 text-right text-sm outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
                  <span class="block text-[10px] text-slate-400 mt-0.5 pr-1">
                    modal {{ $tld->cost_renew > 0 ? number_format($tld->cost_renew, 0, ',', '.') : '—' }}
                  </span>
                </td>

                <td class="px-3 py-2 text-right">
                  <input type="number" step="1" min="0"
                         name="rows[{{ $tld->id }}][transfer_price]"
                         value="{{ (int) $tld->transfer_price ?: '' }}" placeholder="0"
                         class="w-28 px-2 py-1.5 rounded-lg border border-slate-200 text-right text-sm outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
                  <span class="block text-[10px] text-slate-400 mt-0.5 pr-1">
                    modal {{ $tld->cost_transfer > 0 ? number_format($tld->cost_transfer, 0, ',', '.') : '—' }}
                  </span>
                </td>

                {{-- Margin dihitung ulang oleh JS begitu angka diubah --}}
                <td class="px-3 py-2 text-right text-xs whitespace-nowrap" data-margin>
                  <span class="text-slate-300">—</span>
                </td>

                <td class="px-3 py-2 text-center">
                  <input type="checkbox" name="active[]" value="{{ $tld->id }}" @checked($tld->is_active)
                         class="rounded border-slate-300 text-accent focus:ring-accent/40">
                </td>

                <td class="px-4 py-2 text-right">
                  <button type="submit" form="del-{{ $tld->id }}"
                          class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 inline-flex items-center justify-center text-rose-500" title="Hapus {{ $tld->extension }}">
                    <i class="fa-regular fa-trash-can text-xs"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-5 py-10 text-center">
                  <p class="text-slate-500 text-sm mb-1">Belum ada TLD.</p>
                  <p class="text-xs text-slate-400">
                    Tambahkan manual, atau impor otomatis dari registrar:
                    buka tab <a href="{{ route('admin.registrars.index') }}" class="text-accent hover:underline">Registrar</a>
                    lalu klik ikon <i class="fa-solid fa-rotate text-[10px]"></i> (Sinkronkan daftar TLD).
                  </p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if ($tlds->isNotEmpty())
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap bg-slate-50/60">
          <p class="text-xs text-slate-500">
            Ketik harga langsung di tabel, lalu klik Simpan. Perubahan berlaku untuk halaman ini saja
            ({{ $tlds->count() }} baris) — pindah halaman tanpa menyimpan akan membatalkan perubahan.
          </p>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Perubahan
          </button>
        </div>
      @endif
    </form>

    {{-- Form hapus diletakkan di luar tabel: HTML tidak mengizinkan form bersarang. --}}
    @foreach ($tlds as $tld)
      <form id="del-{{ $tld->id }}" method="POST" action="{{ route('admin.tlds.destroy', $tld) }}" class="hidden"
            data-confirm="Hapus TLD {{ $tld->extension }}?" data-confirm-title="Hapus Data"
            data-confirm-style="danger" data-confirm-label="Ya, Hapus">
        @csrf @method('DELETE')
      </form>
    @endforeach

    @if ($tlds->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $tlds->links() }}</div>
    @endif
  </div>

  </div>{{-- /#tldTableWrap --}}

<script>
    // Tampilkan panel input sesuai mode yang dipilih.
    document.querySelectorAll('[data-mode-radio]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        document.querySelectorAll('[data-mode-panel]').forEach(function (panel) {
          panel.classList.toggle('hidden', panel.dataset.modePanel !== radio.value);
        });
      });
    });
  </script>

  <script>
    // Hitung ulang margin tiap kali harga modal / register diubah,
    // supaya tidak perlu menyimpan dulu untuk tahu untungnya.
    (function () {
      const rupiah = new Intl.NumberFormat('id-ID');

      function updateRow(row) {
        const cost = parseFloat(row.querySelector('[data-cost]').value) || 0;
        const sell = parseFloat(row.querySelector('[data-register]').value) || 0;
        const cell = row.querySelector('[data-margin]');

        if (cost <= 0 || sell <= 0) {
          cell.innerHTML = '<span class="text-slate-300">—</span>';
          return;
        }

        const margin = sell - cost;
        const percent = (margin / cost * 100).toFixed(1);
        const tone = margin > 0 ? 'text-emerald-600' : 'text-rose-600';

        cell.innerHTML = '<span class="' + tone + '">Rp ' + rupiah.format(Math.round(margin)) +
                         '<br><span class="text-slate-400">' + percent + '%</span></span>';
      }

      window.initTldMargins = function () {
        document.querySelectorAll('[data-row]').forEach(function (row) {
          row.querySelectorAll('[data-cost], [data-register]').forEach(function (input) {
            input.addEventListener('input', function () { updateRow(row); });
          });
          updateRow(row);
        });
      };

      window.initTldMargins();
    })();
  </script>

  <script>
    (function () {
      // Centang semua baris sekaligus.
      window.initTldSelectAll = function () {
        const all = document.getElementById('checkAllRows');
        const boxes = Array.from(document.querySelectorAll('[data-select]'));

        all?.addEventListener('change', function () {
          boxes.forEach(function (b) { b.checked = all.checked; });
        });
      };

      window.initTldSelectAll();

      const boxes = Array.from(document.querySelectorAll('[data-select]'));

      // Kirim daftar TLD yang dicentang ke form margin saat disubmit.
      const markupForm = document.getElementById('markupForm');
      markupForm?.addEventListener('submit', function () {
        document.getElementById('selectedIds').value =
          boxes.filter(function (b) { return b.checked; })
               .map(function (b) { return b.value; })
               .join(',');
      });

      // Tampilkan field pembulatan yang relevan saja.
      const mode = document.getElementById('roundMode');
      function syncRound() {
        document.querySelectorAll('[data-round-field]').forEach(function (el) {
          const isTail = el.hasAttribute('data-tail');
          if (mode.value === 'none') {
            el.classList.add('hidden');
          } else if (mode.value === 'ending') {
            el.classList.remove('hidden');
          } else {
            el.classList.toggle('hidden', isTail);
          }
        });
      }
      mode?.addEventListener('change', syncRound);
      syncRound();
    })();
  </script>


<script>
  /**
   * Pindah halaman & simpan tanpa memuat ulang seluruh halaman.
   *
   * Perubahan yang belum disimpan otomatis dikirim dulu sebelum pindah,
   * supaya ketikan tidak hilang — masalah yang sering terjadi pada tabel
   * editable berhalaman.
   */
  (function () {
    const wrap = document.getElementById('tldTableWrap');
    if (!wrap) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.content
      || document.querySelector('#bulkForm input[name="_token"]')?.value;

    let dirty = false;

    function markDirty() { dirty = true; }

    function toast(message, tone) {
      const box = document.createElement('div');
      const cls = tone === 'error'
        ? 'bg-rose-50 border-rose-200 text-rose-700'
        : 'bg-emerald-50 border-emerald-200 text-emerald-700';
      box.className = 'fixed bottom-5 right-5 z-[100] rounded-lg border px-4 py-3 text-sm shadow-lg ' + cls;
      box.textContent = message;
      document.body.appendChild(box);
      setTimeout(function () { box.remove(); }, 4000);
    }

    // Kirim isi tabel lewat fetch, bukan submit biasa.
    async function saveChanges() {
      const form = document.getElementById('bulkForm');
      if (!form) return true;

      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });

      if (!res.ok) {
        toast('Gagal menyimpan (HTTP ' + res.status + ').', 'error');
        return false;
      }

      const data = await res.json().catch(function () { return null; });
      dirty = false;
      toast(data?.message || 'Perubahan tersimpan.', 'success');
      return true;
    }

    // Muat ulang isi tabel dari URL tertentu.
    async function loadTable(url) {
      wrap.style.opacity = '0.5';

      try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const fresh = doc.getElementById('tldTableWrap');

        if (!fresh) { window.location.href = url; return; }

        wrap.innerHTML = fresh.innerHTML;
        window.history.pushState({}, '', url);
        dirty = false;
        bind();
        window.scrollTo({ top: wrap.offsetTop - 100, behavior: 'smooth' });
      } catch (e) {
        window.location.href = url;
      } finally {
        wrap.style.opacity = '';
      }
    }

    // Pasang ulang listener setiap kali isi tabel diganti.
    function bind() {
      // Tandai ada perubahan.
      wrap.querySelectorAll('#bulkForm input').forEach(function (el) {
        el.addEventListener('input', markDirty);
        el.addEventListener('change', markDirty);
      });

      // Tombol simpan → fetch.
      const saveBtn = wrap.querySelector('#bulkForm button[type="submit"]');
      saveBtn?.addEventListener('click', async function (e) {
        e.preventDefault();
        saveBtn.disabled = true;
        await saveChanges();
        saveBtn.disabled = false;
      });

      // Link pagination → muat via AJAX, simpan dulu bila perlu.
      wrap.querySelectorAll('nav a[href], .pagination a[href]').forEach(function (link) {
        link.addEventListener('click', async function (e) {
          e.preventDefault();

          if (dirty) {
            const simpan = await window.confirmDialog({
              title: 'Perubahan Belum Disimpan',
              message: 'Ada harga yang kamu ubah tapi belum disimpan. Simpan dulu sebelum pindah halaman?',
              style: 'warn',
              label: 'Simpan & Lanjut',
            });

            if (simpan) {
              const ok = await saveChanges();
              if (!ok) return;
            }
          }

          loadTable(link.href);
        });
      });

      // Hitung ulang margin & aktifkan "pilih semua" untuk isi yang baru.
      if (typeof window.initTldMargins === 'function') window.initTldMargins();
      if (typeof window.initTldSelectAll === 'function') window.initTldSelectAll();
    }

    bind();

    window.addEventListener('popstate', function () { loadTable(window.location.href); });

  })();
</script>

@endsection

