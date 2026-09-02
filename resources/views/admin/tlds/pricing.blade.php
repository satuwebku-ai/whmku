@extends('layouts.admin')

@section('title', 'TLD Pricing')

@section('content')

  @include('admin.domains._nav')

  <div class="mb-4">
    <h1 class="h4 fw-bold text-dark mb-1">TLD Pricing</h1>
    <p class="small text-muted mb-0">
      Pilih registrar dulu, baru atur harga jualnya -- sejak satu ekstensi (mis. ".com") bisa
      dimiliki beberapa registrar sekaligus, harga per registrar diatur terpisah.
    </p>
  </div>

  {{-- Pemilih registrar -- selalu tampil di atas, jadi kelihatan jelas
       registrar mana yang sedang dibuka. --}}
  <div class="d-flex flex-wrap gap-2 mb-4">
    @foreach ($registrars as $r)
      <a href="{{ route('admin.tlds.pricing', ['registrar' => $r->id]) }}"
         class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none border {{ is_object($selected) && $selected->id === $r->id ? 'border-primary' : '' }}"
         style="font-size:13px;{{ is_object($selected) && $selected->id === $r->id ? 'background:rgba(79,70,229,.06);color:#4338ca;border-color:#4f46e5!important' : 'color:#334155' }}">
        <i class="fa-solid fa-server" style="font-size:11px"></i>
        {{ $r->name }}
        <span class="badge badge-soft-secondary" style="font-size:10px">{{ $r->tlds_count }}</span>
      </a>
    @endforeach
    <a href="{{ route('admin.tlds.pricing', ['registrar' => 'none']) }}"
       class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none border {{ $selected === 'none' ? 'border-primary' : '' }}"
       style="font-size:13px;{{ $selected === 'none' ? 'background:rgba(79,70,229,.06);color:#4338ca;border-color:#4f46e5!important' : 'color:#334155' }}">
      <i class="fa-solid fa-user-slash" style="font-size:11px"></i> Manual (Tidak Ditentukan)
    </a>
  </div>

  @if (! $selected)
    <div class="card border rounded-4 p-5 text-center">
      <i class="fa-solid fa-hand-pointer text-muted mb-3" style="font-size:1.75rem"></i>
      <p class="fw-medium text-dark mb-1">Pilih registrar di atas untuk mulai atur harga</p>
      <p class="text-muted mb-0" style="font-size:13px">
        Belum ada registrar? Tambahkan dulu lewat <a href="{{ route('admin.registrars.create') }}" class="text-accent">Registrar &rarr; Tambah Registrar</a>.
      </p>
    </div>
  @else
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
      <input type="hidden" name="registrar" value="{{ is_object($selected) ? $selected->id : 'none' }}">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari ekstensi, mis. .com" class="form-control form-control-sm" style="max-width:14rem">
      <button type="submit" class="btn btn-outline-secondary btn-sm">Cari</button>
      @if (request('search'))
        <a href="{{ route('admin.tlds.pricing', ['registrar' => is_object($selected) ? $selected->id : 'none']) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
      @endif
    </form>

    <form method="POST" action="{{ route('admin.tld.update-pricing') }}" id="pricingForm">
      @csrf
      <input type="hidden" name="registrar_id" value="{{ is_object($selected) ? $selected->id : 'none' }}">

      <div class="card border rounded-4 overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size:13px">
            <thead>
              <tr class="small text-uppercase text-muted" style="background:#f8fafc">
                <th class="py-3 px-3">Ekstensi</th>
                <th class="text-end py-3">Modal Register</th>
                <th class="text-end py-3">Harga Register</th>
                <th class="text-end py-3">Modal Renew</th>
                <th class="text-end py-3">Harga Renew</th>
                <th class="text-end py-3">Modal Transfer</th>
                <th class="text-end py-3">Harga Transfer</th>
                <th class="text-end py-3">Margin</th>
                <th class="text-center py-3">1-10 Thn</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($tlds as $tld)
                <tr data-row>
                  <td class="py-2 px-3 fw-medium text-dark text-nowrap">
                    {{ $tld->extension }}
                    @if ($tld->is_demo)
                      <span class="badge" style="font-size:9px;background:#fef3c7;color:#b45309">DEMO</span>
                    @endif
                    @if (! $tld->is_active)
                      <span class="badge badge-soft-secondary" style="font-size:9px">Nonaktif</span>
                    @endif
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

                  <td class="py-2">
                    <input type="number" step="1" min="0"
                           name="rows[{{ $tld->id }}][cost_renew]"
                           value="{{ (int) $tld->cost_renew ?: '' }}" placeholder="0"
                           class="form-control form-control-sm text-end" style="width:7rem">
                  </td>
                  <td class="py-2">
                    <input type="number" step="1" min="0"
                           name="rows[{{ $tld->id }}][renew_price]"
                           value="{{ (int) $tld->renew_price ?: '' }}" placeholder="0"
                           class="form-control form-control-sm text-end" style="width:7rem">
                  </td>

                  <td class="py-2">
                    <input type="number" step="1" min="0"
                           name="rows[{{ $tld->id }}][cost_transfer]"
                           value="{{ (int) $tld->cost_transfer ?: '' }}" placeholder="0"
                           class="form-control form-control-sm text-end" style="width:7rem">
                  </td>
                  <td class="py-2">
                    <input type="number" step="1" min="0"
                           name="rows[{{ $tld->id }}][transfer_price]"
                           value="{{ (int) $tld->transfer_price ?: '' }}" placeholder="0"
                           class="form-control form-control-sm text-end" style="width:7rem">
                  </td>

                  <td class="text-end py-2 text-nowrap" data-margin>
                    <span class="text-muted">—</span>
                  </td>

                  <td class="text-center py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm year-price-btn"
                            data-tld-id="{{ $tld->id }}"
                            data-extension="{{ $tld->extension }}"
                            data-max-years="{{ $tld->max_years }}"
                            data-year-prices='@json($tld->year_prices ?: [])'
                            data-year-renew-prices='@json($tld->year_renew_prices ?: [])'
                            data-cost-year-prices='@json($tld->cost_year_prices ?: [])'
                            title="Atur harga jual per tahun (1-10 tahun)">
                      <i class="fa-regular fa-calendar" style="font-size:11px"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center py-5">
                    <p class="text-muted mb-1" style="font-size:14px">Belum ada TLD untuk registrar ini.</p>
                    <p class="text-muted mb-0" style="font-size:11px">
                      Buka <a href="{{ route('admin.registrars.index') }}" class="text-accent">Registrar</a>
                      lalu klik ikon <i class="fa-solid fa-rotate" style="font-size:10px"></i> (Sinkronkan daftar TLD).
                    </p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if ($tlds->isNotEmpty())
          <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check" style="font-size:11px"></i> Simpan Harga</button>
            {{ $tlds->links('pagination.bootstrap') }}
          </div>
        @endif
      </div>
    </form>

    {{-- Modal harga per tahun -- diisi/dibaca lewat JS, hasilnya
         dituliskan sebagai input tersembunyi di form utama di atas
         sebelum submit, supaya tersimpan dalam SATU kali kirim. --}}
    <div id="yearPriceModal" class="d-none" style="position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:1050;align-items:center;justify-content:center">
      <div class="bg-white rounded-4 p-4" style="width:32rem;max-width:92vw;max-height:85vh;overflow-y:auto">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="small fw-bold text-dark mb-0">Harga per Tahun — <span id="yearPriceExt"></span></h2>
          <button type="button" onclick="closeYearPriceModal()" class="btn-close" style="font-size:11px"></button>
        </div>
        <p class="text-muted mb-3" style="font-size:11px">
          Kosongkan supaya tahun itu dihitung otomatis (harga 1 tahun × jumlah tahun). Angka abu-abu
          di bawah kolom adalah harga modal dari registrar (referensi, bukan yang tersimpan).
        </p>
        <div id="yearPriceRows" class="d-flex flex-column gap-2"></div>
        <button type="button" onclick="closeYearPriceModal(true)" class="btn btn-primary btn-sm w-100 mt-3">Terapkan ke Form</button>
      </div>
    </div>

    <script>
      let currentYearPriceTld = null;

      function openYearPriceModal(btn) {
        currentYearPriceTld = btn.dataset.tldId;
        const maxYears = parseInt(btn.dataset.maxYears || '10', 10);
        const yearPrices = JSON.parse(btn.dataset.yearPrices || '{}');
        const yearRenewPrices = JSON.parse(btn.dataset.yearRenewPrices || '{}');
        const costYearPrices = JSON.parse(btn.dataset.costYearPrices || '{}');

        document.getElementById('yearPriceExt').textContent = btn.dataset.extension;

        const rows = document.getElementById('yearPriceRows');
        rows.innerHTML = '';

        for (let y = 2; y <= Math.max(maxYears, 2); y++) {
          const cost = costYearPrices[y] ? Number(costYearPrices[y]).toLocaleString('id-ID') : '—';
          rows.insertAdjacentHTML('beforeend', `
            <div class="d-flex align-items-center gap-2">
              <span class="text-muted" style="font-size:12px;width:4rem">${y} Thn</span>
              <div class="flex-grow-1">
                <input type="number" step="1" min="0" class="form-control form-control-sm year-price-input" data-year="${y}" data-kind="register" placeholder="otomatis" value="${yearPrices[y] ?? ''}">
                <span class="text-muted" style="font-size:10px">modal ${cost}</span>
              </div>
              <div class="flex-grow-1">
                <input type="number" step="1" min="0" class="form-control form-control-sm year-price-input" data-year="${y}" data-kind="renew" placeholder="otomatis (renew)" value="${yearRenewPrices[y] ?? ''}">
              </div>
            </div>
          `);
        }

        document.getElementById('yearPriceModal').classList.remove('d-none');
        document.getElementById('yearPriceModal').style.display = 'flex';
      }

      function closeYearPriceModal(apply) {
        if (apply && currentYearPriceTld) {
          const form = document.getElementById('pricingForm');

          // Hapus input tersembunyi lama untuk TLD ini (kalau modal dibuka berkali-kali).
          form.querySelectorAll(`input[data-year-hidden="${currentYearPriceTld}"]`).forEach(el => el.remove());

          document.querySelectorAll('.year-price-input').forEach(input => {
            if (! input.value) return;
            const kind = input.dataset.kind === 'renew' ? 'year_renew_prices' : 'year_prices';
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.dataset.yearHidden = currentYearPriceTld;
            hidden.name = `rows[${currentYearPriceTld}][${kind}][${input.dataset.year}]`;
            hidden.value = input.value;
            form.appendChild(hidden);
          });
        }

        document.getElementById('yearPriceModal').classList.add('d-none');
        document.getElementById('yearPriceModal').style.display = 'none';
        currentYearPriceTld = null;
      }

      document.querySelectorAll('.year-price-btn').forEach(btn => {
        btn.addEventListener('click', () => openYearPriceModal(btn));
      });

      // Margin real-time, sama seperti halaman Status TLD.
      document.querySelectorAll('[data-row]').forEach(row => {
        const costInput = row.querySelector('[data-cost]');
        const registerInput = row.querySelector('[data-register]');
        const marginCell = row.querySelector('[data-margin]');

        function updateMargin() {
          const cost = parseFloat(costInput.value) || 0;
          const register = parseFloat(registerInput.value) || 0;

          if (cost <= 0 || register <= 0) {
            marginCell.innerHTML = '<span class="text-muted">—</span>';
            return;
          }

          const margin = register - cost;
          const percent = (margin / cost * 100).toFixed(1);
          const tone = margin >= 0 ? 'text-success' : 'text-danger';
          marginCell.innerHTML = `<span class="${tone}">Rp ${Math.round(margin).toLocaleString('id-ID')}<br>${percent}%</span>`;
        }

        costInput?.addEventListener('input', updateMargin);
        registerInput?.addEventListener('input', updateMargin);
        updateMargin();
      });
    </script>
  @endif

@endsection
