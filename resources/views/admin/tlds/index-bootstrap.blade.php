@extends('layouts.admin-bootstrap')

@section('title', 'TLD Pricing')

@section('content')

  @include('admin.domains._nav-bootstrap')

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <h1 class="h4 fw-bold text-dark mb-1">TLD Pricing</h1>
      <p class="small text-muted mb-0">Atur harga jual per ekstensi domain. Hanya TLD aktif yang muncul di Cek Domain.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button type="button" onclick="document.getElementById('importPanel').classList.toggle('d-none')" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-cloud-arrow-down" style="font-size:11px"></i> Tarik Harga Registrar
      </button>
      <button type="button" onclick="document.getElementById('markupPanel').classList.toggle('d-none')" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-percent" style="font-size:11px"></i> Markup Massal
      </button>
      <a href="{{ route('admin.tlds.create.bootstrap-preview') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus" style="font-size:11px"></i> Tambah TLD
      </a>
    </div>
  </div>

  <div class="card border rounded-4 p-4 mb-4" style="max-width:28rem">
    <h2 class="small fw-bold text-dark mb-1">Harga Add-On Domain</h2>
    <p class="text-muted mb-3" style="font-size:12px">
      Ditampilkan sebagai opsi tambahan di halaman Keranjang saat klien mendaftarkan domain baru.
    </p>
    <form method="POST" action="{{ route('admin.tlds.addon-pricing') }}">
      @csrf
      <div class="mb-2">
        <label class="form-label small fw-medium text-dark">Harga ID Protection (per tahun)</label>
        <input type="number" name="whois_privacy_price" min="0" step="1000"
               value="{{ \App\Models\Setting::get('whois_privacy_price', 0) }}" class="form-control form-control-sm">
        <p class="text-muted mt-1 mb-0" style="font-size:11px">Kosongkan / isi 0 untuk menjadikannya gratis.</p>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
    </form>
  </div>

  <div id="importPanel" class="d-none card border rounded-4 p-4 mb-4" style="border-color:#a7f3d0!important;background:rgba(16,185,129,.04)">
    <h2 class="small fw-bold text-dark mb-1">Tarik Harga Modal dari Registrar</h2>
    <p class="text-muted mb-3" style="font-size:12px">
      Mengambil harga modal langsung lewat API registrar, lalu menampilkannya sebagai
      <b>tabel pratinjau</b> — di sana harga bisa diperiksa dan disesuaikan satu per satu
      sebelum benar-benar disimpan. Tidak ada yang tersimpan sampai kamu menekan Terapkan.
    </p>

    <form method="POST" action="{{ route('admin.tld.sync-preview') }}">
      @csrf
      <div class="row g-3 align-items-end">
        <div class="col-sm-6">
          <label class="form-label small fw-medium text-dark">Registrar</label>
          <select name="registrar_id" class="form-select form-select-sm">
            @forelse ($registrars as $r)
              <option value="{{ $r->id }}" @selected($r->is_default)>{{ $r->name }}</option>
            @empty
              <option value="">Belum ada registrar aktif</option>
            @endforelse
          </select>
        </div>
        <div class="col-sm-6">
          <label class="form-label small fw-medium text-dark">Markup Awal (%)</label>
          <input type="number" step="0.1" min="0" name="markup" value="30" class="form-control form-control-sm">
          <p class="text-muted mt-1 mb-0" style="font-size:11px">Masih bisa diubah di pratinjau.</p>
        </div>
        <div class="col-sm-6">
          <label class="form-label small fw-medium text-dark">Pembulatan</label>
          <select name="round_to" class="form-select form-select-sm">
            <option value="1000" selected>Ribuan</option>
            <option value="0">Tanpa</option>
            <option value="5000">5 ribu</option>
            <option value="10000">10 ribu</option>
          </select>
        </div>
        <div class="col-sm-6">
          <label class="d-flex align-items-center gap-2 small text-dark mb-0">
            <input type="checkbox" name="only_sellable" value="1" checked class="form-check-input" style="margin-top:0">
            Hanya "Sell"
          </label>
        </div>
        <div class="col-sm-6">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="fa-solid fa-cloud-arrow-down" style="font-size:11px"></i> Tarik &amp; Pratinjau
          </button>
        </div>
      </div>

      <p class="text-muted mt-2 mb-0" style="font-size:11px">
        "Hanya yang berstatus Sell" menyaring TLD yang sudah kamu aktifkan untuk dijual di panel
        registrar — biasanya jauh lebih sedikit dari total {{ $counts['all'] }} TLD yang tersedia.
      </p>
    </form>
  </div>

  <div id="markupPanel" class="d-none card border rounded-4 p-4 mb-4" style="border-color:#c7d2fe!important;background:rgba(79,70,229,.04)">
    <h2 class="small fw-bold text-dark mb-1">Tentukan Harga Jual dari Margin</h2>
    <p class="text-muted mb-3" style="font-size:12px">
      Harga jual dihitung dari <b>harga modal</b>, jadi aman dijalankan berulang kali.
      @if ($counts['no_cost'] > 0)
        <span style="color:#b45309"><i class="fa-solid fa-triangle-exclamation"></i>
        {{ $counts['no_cost'] }} TLD belum punya harga modal dan akan dilewati — isi lewat
        <b>Impor Harga</b> atau ketik langsung di kolom Modal.</span>
      @endif
    </p>

    <form method="POST" action="{{ route('admin.tld.bulk-markup') }}" id="markupForm"
          data-confirm="Terapkan margin ini? Harga jual yang ada akan ditimpa."
          data-confirm-title="Terapkan Margin" data-confirm-style="warn" data-confirm-label="Ya, Terapkan">
      @csrf
      <input type="hidden" name="search" value="{{ request('search') }}">
      <input type="hidden" name="selected_ids" id="selectedIds">

      <div class="d-flex align-items-center gap-4 mb-3" style="font-size:14px">
        <span class="fw-medium text-dark">Hitung profit dalam:</span>
        <label class="d-flex align-items-center gap-2 text-muted mb-0">
          <input type="radio" name="profit_type" value="percent" checked style="margin:0">
          Persen (%)
        </label>
        <label class="d-flex align-items-center gap-2 text-muted mb-0">
          <input type="radio" name="profit_type" value="fixed" style="margin:0">
          Rupiah tetap (Rp)
        </label>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-sm-4">
          <label class="form-label small fw-medium text-dark">Margin Register</label>
          <input type="number" step="0.01" min="0" name="margin_register" value="30" class="form-control form-control-sm" required>
        </div>
        <div class="col-sm-4">
          <label class="form-label small fw-medium text-dark">Margin Renew <span class="text-muted fw-normal">(opsional)</span></label>
          <input type="number" step="0.01" min="0" name="margin_renew" class="form-control form-control-sm" placeholder="ikut Register">
        </div>
        <div class="col-sm-4">
          <label class="form-label small fw-medium text-dark">Margin Transfer <span class="text-muted fw-normal">(opsional)</span></label>
          <input type="number" step="0.01" min="0" name="margin_transfer" class="form-control form-control-sm" placeholder="ikut Register">
        </div>
      </div>

      <div class="row g-3 mb-3 pt-3 border-top align-items-end">
        <div class="col-sm-4">
          <label class="form-label small fw-medium text-dark">Pembulatan</label>
          <select name="round_mode" id="roundMode" class="form-select form-select-sm">
            <option value="multiple" selected>Bulatkan ke kelipatan</option>
            <option value="ending">Akhiri dengan angka tertentu</option>
            <option value="none">Tanpa pembulatan</option>
          </select>
        </div>
        <div class="col-sm-4" data-round-field>
          <label class="form-label small fw-medium text-dark">Kelipatan (Rp)</label>
          <select name="round_step" class="form-select form-select-sm">
            <option value="1000" selected>1.000</option>
            <option value="5000">5.000</option>
            <option value="10000">10.000</option>
            <option value="50000">50.000</option>
          </select>
        </div>
        <div class="col-sm-4 d-none" data-round-field data-tail>
          <label class="form-label small fw-medium text-dark">Akhiran (Rp)</label>
          <input type="number" name="round_tail" value="9000" min="0" class="form-control form-control-sm">
          <p class="text-muted mt-1 mb-0" style="font-size:11px">Mis. kelipatan 10.000 + akhiran 9.000 → 219.000</p>
        </div>
      </div>

      <div class="row g-3 pt-3 border-top align-items-end">
        <div class="col-sm-4">
          <label class="form-label small fw-medium text-dark">Terapkan ke</label>
          <select name="scope" class="form-select form-select-sm">
            <option value="all">Semua TLD</option>
            <option value="selected">Hanya yang dicentang di tabel</option>
            <option value="filtered" @selected(request('search'))>Hasil pencarian "{{ request('search') ?: '—' }}"</option>
          </select>
        </div>
        <div class="col-sm-4 d-flex flex-column gap-1">
          <label class="d-flex align-items-center gap-2 text-muted mb-0" style="font-size:14px">
            <input type="checkbox" name="only_empty" value="1" style="margin:0">
            Hanya yang belum berharga
          </label>
          <label class="d-flex align-items-center gap-2 text-muted mb-0" style="font-size:14px">
            <input type="checkbox" name="activate" value="1" style="margin:0">
            Aktifkan sekalian
          </label>
        </div>
        <div class="col-sm-4 text-sm-end">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check" style="font-size:11px"></i> Terapkan Margin</button>
        </div>
      </div>
    </form>
  </div>

  <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    @php $st = request('status'); @endphp
    <a href="{{ route('admin.tlds.index.bootstrap-preview') }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ !$st ? 'text-white' : 'text-muted' }}" style="{{ !$st ? 'background:#4f46e5' : 'background:#f1f5f9' }}">
      Semua ({{ $counts['all'] }})
    </a>
    <a href="{{ route('admin.tlds.index.bootstrap-preview', ['status' => 'active']) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ $st === 'active' ? 'text-white' : 'text-muted' }}" style="{{ $st === 'active' ? 'background:#4f46e5' : 'background:#f1f5f9' }}">
      Aktif ({{ $counts['active'] }})
    </a>
    <a href="{{ route('admin.tlds.index.bootstrap-preview', ['status' => 'inactive']) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ $st === 'inactive' ? 'text-white' : 'text-muted' }}" style="{{ $st === 'inactive' ? 'background:#4f46e5' : 'background:#f1f5f9' }}">
      Nonaktif ({{ $counts['inactive'] }})
    </a>

    <span style="width:1px;height:20px;background:#e2e8f0"></span>

    @php $wb = request('web'); @endphp
    <a href="{{ route('admin.tlds.index.bootstrap-preview', array_filter(['status' => $st, 'web' => null])) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ !$wb ? 'text-white' : 'text-muted' }}" style="{{ !$wb ? 'background:#4f46e5' : 'background:#f1f5f9' }}">
      Semua Tampil-di-Web
    </a>
    <a href="{{ route('admin.tlds.index.bootstrap-preview', array_filter(['status' => $st, 'web' => 'shown'])) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ $wb === 'shown' ? 'text-white' : 'text-muted' }}" style="{{ $wb === 'shown' ? 'background:#059669' : 'background:#f1f5f9' }}">
      Tampil di Web ({{ $counts['shown'] }})
    </a>
    <a href="{{ route('admin.tlds.index.bootstrap-preview', array_filter(['status' => $st, 'web' => 'hidden'])) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ $wb === 'hidden' ? 'text-white' : 'text-muted' }}" style="{{ $wb === 'hidden' ? 'background:#475569' : 'background:#f1f5f9' }}">
      Disembunyikan ({{ $counts['hidden'] }})
    </a>
  </div>

  <div id="tldTableWrap">
  <div class="card border rounded-4 overflow-hidden">
    <form method="GET" class="px-4 py-3 border-bottom d-flex flex-wrap gap-2">
      @if (request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
      @endif
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari ekstensi, mis. .com" class="form-control form-control-sm" style="max-width:14rem">
      <select name="per_page" class="form-select form-select-sm" style="max-width:9rem">
        @foreach ([25, 50, 100, 200] as $n)
          <option value="{{ $n }}" @selected((int) request('per_page', 25) === $n)>{{ $n }} baris</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-outline-secondary btn-sm">Tampilkan</button>
      @if (request('search'))
        <a href="{{ route('admin.tlds.index.bootstrap-preview', ['status' => request('status')]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
      @endif
    </form>

    <form method="POST" action="{{ route('admin.tld.bulk-update') }}" id="bulkForm">
      @csrf

      <datalist id="searchGroups">
        @foreach (['Populer', 'Indonesia', 'Bisnis', 'Teknologi', 'Umum'] as $g)
          <option value="{{ $g }}"></option>
        @endforeach
      </datalist>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:13px">
          <thead>
            <tr class="small text-uppercase text-muted" style="background:#f8fafc">
              <th class="px-3 py-3 text-center"><input type="checkbox" id="checkAllRows" title="Pilih semua" style="margin:0"></th>
              <th class="py-3">Ekstensi</th>
              <th class="text-end py-3">Modal (Rp)</th>
              <th class="text-end py-3">Register (Rp)</th>
              <th class="text-end py-3">Renew (Rp)</th>
              <th class="text-end py-3">Transfer (Rp)</th>
              <th class="text-end py-3">Margin</th>
              <th class="text-center py-3">Aktif</th>
              <th class="text-center py-3" title="Tampil di halaman Cek Domain publik">Tampil di Web</th>
              <th class="py-3">Grup</th>
              <th class="text-end px-4 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($tlds as $tld)
              <tr data-row>
                <td class="px-3 py-2 text-center">
                  <input type="checkbox" value="{{ $tld->id }}" data-select style="margin:0">
                </td>
                <td class="py-2 fw-medium text-dark text-nowrap">
                  {{ $tld->extension }}
                  @if ($tld->is_demo)
                    <span class="badge" style="font-size:9px;background:#fef3c7;color:#b45309">DEMO</span>
                  @endif
                  <span class="d-block fw-normal text-muted" style="font-size:10px">{{ $tld->registrar->name ?? 'manual' }}</span>
                </td>

                <td class="py-2">
                  <input type="number" step="1" min="0" data-cost
                         name="rows[{{ $tld->id }}][cost_register]"
                         value="{{ (int) $tld->cost_register ?: '' }}" placeholder="0"
                         class="form-control form-control-sm text-end" style="width:7rem">
                </td>

                <td class="py-2">
                  <input type="number" step="1" min="0" data-register
                         name="rows[{{ $tld->id }}][register_price]"
                         value="{{ (int) $tld->register_price ?: '' }}" placeholder="0"
                         class="form-control form-control-sm text-end" style="width:7rem;{{ $tld->register_price > 0 ? '' : 'border-color:#fca5a5;background:#fef2f2' }}">
                </td>

                <td class="text-end py-2">
                  <input type="number" step="1" min="0"
                         name="rows[{{ $tld->id }}][renew_price]"
                         value="{{ (int) $tld->renew_price ?: '' }}" placeholder="0"
                         class="form-control form-control-sm text-end" style="width:7rem">
                  <span class="d-block text-muted mt-1" style="font-size:10px">
                    modal {{ $tld->cost_renew > 0 ? number_format($tld->cost_renew, 0, ',', '.') : '—' }}
                  </span>
                </td>

                <td class="text-end py-2">
                  <input type="number" step="1" min="0"
                         name="rows[{{ $tld->id }}][transfer_price]"
                         value="{{ (int) $tld->transfer_price ?: '' }}" placeholder="0"
                         class="form-control form-control-sm text-end" style="width:7rem">
                  <span class="d-block text-muted mt-1" style="font-size:10px">
                    modal {{ $tld->cost_transfer > 0 ? number_format($tld->cost_transfer, 0, ',', '.') : '—' }}
                  </span>
                </td>

                <td class="text-end py-2 text-nowrap" data-margin>
                  <span class="text-muted">—</span>
                </td>

                <td class="text-center py-2">
                  <input type="checkbox" name="active[]" value="{{ $tld->id }}" @checked($tld->is_active) style="margin:0">
                </td>

                <td class="text-center py-2">
                  <input type="checkbox" name="in_search[]" value="{{ $tld->id }}" @checked($tld->show_in_search) style="margin:0">
                </td>

                <td class="py-2">
                  <input type="text" name="rows[{{ $tld->id }}][search_group]"
                         value="{{ $tld->search_group }}" placeholder="mis. Populer"
                         list="searchGroups" class="form-control form-control-sm" style="width:7rem">
                </td>

                <td class="text-end px-4 py-2">
                  <button type="submit" form="del-{{ $tld->id }}"
                          class="btn btn-outline-danger btn-sm d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;padding:0" title="Hapus {{ $tld->extension }}">
                    <i class="fa-regular fa-trash-can" style="font-size:11px"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center py-5">
                  <p class="text-muted mb-1" style="font-size:14px">Belum ada TLD.</p>
                  <p class="text-muted mb-0" style="font-size:11px">
                    Tambahkan manual, atau impor otomatis dari registrar:
                    buka tab <a href="{{ route('admin.registrars.index.bootstrap-preview') }}" class="text-accent">Registrar</a>
                    lalu klik ikon <i class="fa-solid fa-rotate" style="font-size:10px"></i> (Sinkronkan daftar TLD).
                  </p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if ($tlds->isNotEmpty())
        <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2" style="background:#f8fafc">
          <p class="text-muted mb-0" style="font-size:11px">
            Ketik harga langsung di tabel, lalu klik Simpan. Perubahan berlaku untuk halaman ini saja
            ({{ $tlds->count() }} baris) — pindah halaman tanpa menyimpan akan membatalkan perubahan.
          </p>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-floppy-disk" style="font-size:11px"></i> Simpan Perubahan
          </button>
        </div>
      @endif
    </form>

    @foreach ($tlds as $tld)
      <form id="del-{{ $tld->id }}" method="POST" action="{{ route('admin.tlds.destroy', $tld) }}" class="d-none"
            data-confirm="Hapus TLD {{ $tld->extension }}?" data-confirm-title="Hapus Data"
            data-confirm-style="danger" data-confirm-label="Ya, Hapus">
        @csrf @method('DELETE')
      </form>
    @endforeach

    @if ($tlds->hasPages())
      <div class="px-4 py-3 border-top">{{ $tlds->links('pagination.bootstrap') }}</div>
    @endif
  </div>

  </div>{{-- /#tldTableWrap --}}

<script>
    document.querySelectorAll('[data-mode-radio]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        document.querySelectorAll('[data-mode-panel]').forEach(function (panel) {
          panel.classList.toggle('d-none', panel.dataset.modePanel !== radio.value);
        });
      });
    });
  </script>

  <script>
    (function () {
      const rupiah = new Intl.NumberFormat('id-ID');

      function updateRow(row) {
        const cost = parseFloat(row.querySelector('[data-cost]').value) || 0;
        const sell = parseFloat(row.querySelector('[data-register]').value) || 0;
        const cell = row.querySelector('[data-margin]');

        if (cost <= 0 || sell <= 0) {
          cell.innerHTML = '<span class="text-muted">—</span>';
          return;
        }

        const margin = sell - cost;
        const percent = (margin / cost * 100).toFixed(1);
        const tone = margin > 0 ? 'text-success' : 'text-danger';

        cell.innerHTML = '<span class="' + tone + '">Rp ' + rupiah.format(Math.round(margin)) +
                         '<br><span class="text-muted">' + percent + '%</span></span>';
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
      window.initTldSelectAll = function () {
        const all = document.getElementById('checkAllRows');
        const boxes = Array.from(document.querySelectorAll('[data-select]'));

        all?.addEventListener('change', function () {
          boxes.forEach(function (b) { b.checked = all.checked; });
        });
      };

      window.initTldSelectAll();

      const boxes = Array.from(document.querySelectorAll('[data-select]'));

      const markupForm = document.getElementById('markupForm');
      markupForm?.addEventListener('submit', function () {
        document.getElementById('selectedIds').value =
          boxes.filter(function (b) { return b.checked; })
               .map(function (b) { return b.value; })
               .join(',');
      });

      const mode = document.getElementById('roundMode');
      function syncRound() {
        document.querySelectorAll('[data-round-field]').forEach(function (el) {
          const isTail = el.hasAttribute('data-tail');
          if (mode.value === 'none') {
            el.classList.add('d-none');
          } else if (mode.value === 'ending') {
            el.classList.remove('d-none');
          } else {
            el.classList.toggle('d-none', isTail);
          }
        });
      }
      mode?.addEventListener('change', syncRound);
      syncRound();
    })();
  </script>

<script>
  (function () {
    const wrap = document.getElementById('tldTableWrap');
    if (!wrap) return;

    let dirty = false;

    function markDirty() { dirty = true; }

    function toast(message, tone) {
      const box = document.createElement('div');
      box.className = 'position-fixed rounded-3 border px-3 py-2 shadow';
      box.style.cssText = 'bottom:20px;right:20px;z-index:1090;font-size:14px;' +
        (tone === 'error' ? 'background:#fef2f2;border-color:#fecaca;color:#b91c1c'
                           : 'background:#f0fdf4;border-color:#bbf7d0;color:#15803d');
      box.textContent = message;
      document.body.appendChild(box);
      setTimeout(function () { box.remove(); }, 4000);
    }

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

    function bind() {
      wrap.querySelectorAll('#bulkForm input').forEach(function (el) {
        el.addEventListener('input', markDirty);
        el.addEventListener('change', markDirty);
      });

      const saveBtn = wrap.querySelector('#bulkForm button[type="submit"]');
      saveBtn?.addEventListener('click', async function (e) {
        e.preventDefault();
        saveBtn.disabled = true;
        await saveChanges();
        saveBtn.disabled = false;
      });

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

      if (typeof window.initTldMargins === 'function') window.initTldMargins();
      if (typeof window.initTldSelectAll === 'function') window.initTldSelectAll();
    }

    bind();

    window.addEventListener('popstate', function () { loadTable(window.location.href); });

  })();
</script>

@endsection
