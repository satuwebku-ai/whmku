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
      <button type="button" onclick="document.getElementById('markupPanel').classList.toggle('hidden')" class="btn btn-outline">
        <i class="fa-solid fa-percent text-xs"></i> Markup Massal
      </button>
      <a href="{{ route('admin.tlds.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus text-xs"></i> Tambah TLD
      </a>
    </div>
  </div>

  {{-- Panel markup massal --}}
  <div id="markupPanel" class="hidden card p-5 mb-5 border-indigo-200 bg-indigo-50/40">
    <h2 class="text-sm font-semibold text-slate-800 mb-1">Terapkan Harga Jual Massal</h2>
    <p class="text-xs text-slate-500 mb-4">
      Harga jual dihitung dari <b>harga modal</b> (kolom Modal di tabel), bukan dari harga jual sebelumnya —
      jadi aman dijalankan berulang kali tanpa membuat harga naik berlipat.
      @if ($counts['no_cost'] > 0)
        <br>
        <span class="text-amber-700"><i class="fa-solid fa-triangle-exclamation"></i>
        {{ $counts['no_cost'] }} TLD belum punya harga modal. Jalankan <b>Sinkronkan TLD</b> di tab Registrar
        untuk mengambilnya, atau pakai mode <b>Harga Tetap</b> di bawah.</span>
      @endif
    </p>

    <form method="POST" action="{{ route('admin.tld.bulk-markup') }}"
          data-confirm="Terapkan harga jual ke TLD yang dipilih? Harga jual saat ini akan ditimpa."
          data-confirm-title="Harga Jual Massal" data-confirm-style="warn" data-confirm-label="Ya, Terapkan"
          class="space-y-4">
      @csrf
      <input type="hidden" name="search" value="{{ request('search') }}">

      {{-- Pilih mode --}}
      <div class="flex items-center gap-4 text-sm">
        <label class="flex items-center gap-2 text-slate-700">
          <input type="radio" name="mode" value="markup" checked class="text-accent focus:ring-accent/40" data-mode-radio>
          Markup dari harga modal
        </label>
        <label class="flex items-center gap-2 text-slate-700">
          <input type="radio" name="mode" value="fixed" class="text-accent focus:ring-accent/40" data-mode-radio>
          Harga tetap (modal belum ada)
        </label>
      </div>

      {{-- Mode markup --}}
      <div data-mode-panel="markup" class="grid sm:grid-cols-3 gap-3 items-end">
        <div>
          <label class="form-label">Markup (%)</label>
          <input type="number" step="0.1" name="markup_percent" value="{{ old('markup_percent', 30) }}" class="form-input">
          <p class="text-[11px] text-slate-400 mt-1">Modal Rp 100.000 + 30% = Rp 130.000</p>
        </div>
      </div>

      {{-- Mode harga tetap --}}
      <div data-mode-panel="fixed" class="hidden grid sm:grid-cols-3 gap-3 items-end">
        <div>
          <label class="form-label">Harga Register (Rp)</label>
          <input type="number" step="1000" name="fixed_register" value="{{ old('fixed_register') }}" class="form-input" placeholder="150000">
        </div>
        <div>
          <label class="form-label">Harga Renew (opsional)</label>
          <input type="number" step="1000" name="fixed_renew" value="{{ old('fixed_renew') }}" class="form-input" placeholder="ikut register">
        </div>
        <div>
          <label class="form-label">Harga Transfer (opsional)</label>
          <input type="number" step="1000" name="fixed_transfer" value="{{ old('fixed_transfer') }}" class="form-input" placeholder="ikut register">
        </div>
      </div>

      {{-- Opsi umum --}}
      <div class="grid sm:grid-cols-4 gap-3 items-end pt-2 border-t border-indigo-100">
        <div>
          <label class="form-label">Pembulatan (Rp)</label>
          <select name="round_to" class="form-input">
            <option value="0">Tanpa pembulatan</option>
            <option value="1000" selected>Ribuan terdekat</option>
            <option value="5000">5 ribu terdekat</option>
            <option value="10000">10 ribu terdekat</option>
          </select>
        </div>
        <div>
          <label class="form-label">Terapkan ke</label>
          <select name="scope" class="form-input">
            <option value="all">Semua TLD</option>
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
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Terapkan</button>
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

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      @if (request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
      @endif
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari ekstensi, mis. .com" class="form-input sm:max-w-xs">
      <button type="submit" class="btn btn-outline">Cari</button>
      @if (request('search'))
        <a href="{{ route('admin.tlds.index', ['status' => request('status')]) }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Ekstensi</th>
            <th class="px-5 py-2.5 font-semibold">Registrar</th>
            <th class="px-5 py-2.5 font-semibold text-right">Modal</th>
            <th class="px-5 py-2.5 font-semibold text-right">Register</th>
            <th class="px-5 py-2.5 font-semibold text-right">Renew</th>
            <th class="px-5 py-2.5 font-semibold text-right">Transfer</th>
            <th class="px-5 py-2.5 font-semibold text-right">Margin</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($tlds as $tld)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">{{ $tld->extension }}</td>
              <td class="px-5 py-3 text-slate-600">{{ $tld->registrar->name ?? '—' }}</td>
              <td class="px-5 py-3 text-right {{ $tld->hasCost() ? 'text-slate-500' : 'text-amber-600' }}">
                @if ($tld->hasCost())
                  Rp {{ number_format($tld->cost_register, 0, ',', '.') }}
                @else
                  <span class="text-xs">Belum ada</span>
                @endif
              </td>
              <td class="px-5 py-3 text-right {{ $tld->register_price > 0 ? 'text-slate-700' : 'text-rose-500' }}">
                @if ($tld->register_price > 0)
                  Rp {{ number_format($tld->register_price, 0, ',', '.') }}
                @else
                  <span class="text-xs">Belum diisi</span>
                @endif
              </td>
              <td class="px-5 py-3 text-right text-slate-700">Rp {{ number_format($tld->renew_price, 0, ',', '.') }}</td>
              <td class="px-5 py-3 text-right text-slate-700">Rp {{ number_format($tld->transfer_price, 0, ',', '.') }}</td>
              <td class="px-5 py-3 text-right text-xs">
                @if ($tld->hasCost() && $tld->hasSellingPrice())
                  <span class="{{ $tld->margin > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    Rp {{ number_format($tld->margin, 0, ',', '.') }}
                    <br><span class="text-slate-400">{{ $tld->margin_percent }}%</span>
                  </span>
                @else
                  <span class="text-slate-300">—</span>
                @endif
              </td>
              <td class="px-5 py-3"><span class="badge {{ $tld->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $tld->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <form method="POST" action="{{ route('admin.tld.status') }}">
                    @csrf
                    <input type="hidden" name="tld_id" value="{{ $tld->id }}">
                    <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="{{ $tld->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                      <i class="fa-solid {{ $tld->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }} text-xs"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.tlds.edit', $tld) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.tlds.destroy', $tld) }}"
                        data-confirm="Hapus TLD {{ $tld->extension }}?" data-confirm-title="Hapus Data"
                        data-confirm-style="danger" data-confirm-label="Ya, Hapus">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
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

    @if ($tlds->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $tlds->links() }}</div>
    @endif
  </div>

@endsection

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
