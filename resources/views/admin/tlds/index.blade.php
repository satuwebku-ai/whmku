@extends('layouts.admin')

@section('title', 'Status & Tampilan TLD')

@section('content')

  @include('admin.domains._nav')

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <h1 class="h4 fw-bold text-dark mb-1">Status &amp; Tampilan TLD</h1>
      <p class="small text-muted mb-0">Aktifkan/nonaktifkan TLD dan atur mana yang tampil di halaman Cek Domain. Harga jual, tarik harga registrar, markup massal, dan tambah TLD baru diatur di <a href="{{ route('admin.tlds.pricing') }}" class="text-accent">TLD Pricing</a>.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('admin.tlds.pricing') }}" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-tags" style="font-size:11px"></i> TLD Pricing
      </a>
    </div>
  </div>

  <div class="card border rounded-4 p-4 mb-4" style="max-width:28rem">
    <h2 class="small fw-bold text-dark mb-1">Harga Add-On Domain</h2>
    <p class="text-muted mb-3" style="font-size:12px">
      Pengaturan harga & eligibilitas ID Protection (WHOIS Privacy) sekarang punya halaman sendiri —
      supaya tabel di bawah ini tidak makin padat.
    </p>
    <a href="{{ route('admin.tlds.privacy') }}" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-user-shield" style="font-size:11px"></i> Buka Pengaturan ID Protection
    </a>
  </div>

  <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    @php $st = request('status'); @endphp
    <a href="{{ route('admin.tlds.index') }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ !$st ? 'text-white' : 'text-muted' }}" style="{{ !$st ? 'background:#4f46e5' : 'background:#f1f5f9' }}">
      Semua ({{ $counts['all'] }})
    </a>
    <a href="{{ route('admin.tlds.index', ['status' => 'active']) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ $st === 'active' ? 'text-white' : 'text-muted' }}" style="{{ $st === 'active' ? 'background:#4f46e5' : 'background:#f1f5f9' }}">
      Aktif ({{ $counts['active'] }})
    </a>
    <a href="{{ route('admin.tlds.index', ['status' => 'inactive']) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ $st === 'inactive' ? 'text-white' : 'text-muted' }}" style="{{ $st === 'inactive' ? 'background:#4f46e5' : 'background:#f1f5f9' }}">
      Nonaktif ({{ $counts['inactive'] }})
    </a>

    <span style="width:1px;height:20px;background:#e2e8f0"></span>

    @php $wb = request('web'); @endphp
    <a href="{{ route('admin.tlds.index', array_filter(['status' => $st, 'web' => null])) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ !$wb ? 'text-white' : 'text-muted' }}" style="{{ !$wb ? 'background:#4f46e5' : 'background:#f1f5f9' }}">
      Semua Tampil-di-Web
    </a>
    <a href="{{ route('admin.tlds.index', array_filter(['status' => $st, 'web' => 'shown'])) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ $wb === 'shown' ? 'text-white' : 'text-muted' }}" style="{{ $wb === 'shown' ? 'background:#059669' : 'background:#f1f5f9' }}">
      Tampil di Web ({{ $counts['shown'] }})
    </a>
    <a href="{{ route('admin.tlds.index', array_filter(['status' => $st, 'web' => 'hidden'])) }}" class="px-3 py-2 rounded-pill small fw-medium text-decoration-none {{ $wb === 'hidden' ? 'text-white' : 'text-muted' }}" style="{{ $wb === 'hidden' ? 'background:#475569' : 'background:#f1f5f9' }}">
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
      <select name="registrar" class="form-select form-select-sm" style="max-width:12rem">
        <option value="">Semua Registrar</option>
        <option value="none" @selected(request('registrar') === 'none')>— Tidak ditentukan —</option>
        @foreach ($registrars as $r)
          <option value="{{ $r->id }}" @selected((string) request('registrar') === (string) $r->id)>{{ $r->name }}</option>
        @endforeach
      </select>
      <select name="per_page" class="form-select form-select-sm" style="max-width:9rem">
        @foreach ([25, 50, 100, 200] as $n)
          <option value="{{ $n }}" @selected((int) request('per_page', 25) === $n)>{{ $n }} baris</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-outline-secondary btn-sm">Tampilkan</button>
      @if (request('search') || request('registrar'))
        <a href="{{ route('admin.tlds.index', ['status' => request('status')]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
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
              <th class="text-end py-3">Harga Jual</th>
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

                <td class="text-end py-2">
                  @if ($tld->register_price > 0)
                    <span class="fw-medium text-dark">Rp {{ number_format($tld->register_price, 0, ',', '.') }}</span>
                    <span class="d-block text-muted" style="font-size:10px">renew Rp {{ number_format($tld->renew_price, 0, ',', '.') }}</span>
                  @else
                    <a href="{{ route('admin.tlds.pricing', ['registrar' => $tld->registrar_id ?: 'none']) }}" class="text-danger" style="font-size:11px">
                      <i class="fa-solid fa-triangle-exclamation"></i> Belum ada harga
                    </a>
                  @endif
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
                <td colspan="7" class="text-center py-5">
                  <p class="text-muted mb-1" style="font-size:14px">Belum ada TLD.</p>
                  <p class="text-muted mb-0" style="font-size:11px">
                    Tambahkan manual, atau impor otomatis dari registrar:
                    buka tab <a href="{{ route('admin.registrars.index') }}" class="text-accent">Registrar</a>
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
      window.initTldMargins = function () {};
    })();
  </script>

  <script>
    (function () {
      // Panel "Tarik Harga Registrar" & "Markup Massal" sudah PINDAH ke
      // halaman TLD Pricing -- handler-nya ikut pindah ke sana. Yang
      // tersisa di halaman ini cuma pilih-semua untuk baris tabel.
      window.initTldSelectAll = function () {
        const all = document.getElementById('checkAllRows');
        const boxes = Array.from(document.querySelectorAll('[data-select]'));

        all?.addEventListener('change', function () {
          boxes.forEach(function (b) { b.checked = all.checked; });
        });
      };

      window.initTldSelectAll();
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
