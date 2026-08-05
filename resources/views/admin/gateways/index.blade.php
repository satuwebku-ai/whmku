@extends('layouts.admin')

@section('title', 'Payment Gateway')

@section('content')

  @include('admin.payments._nav')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Payment Gateway</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola metode pembayaran yang tersedia. Kredensial dienkripsi otomatis.</p>
    </div>
    <a href="{{ route('admin.gateway.add.page') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Gateway
    </a>
  </div>

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Nama</th>
            <th class="px-5 py-2.5 font-semibold">Driver</th>
            <th class="px-5 py-2.5 font-semibold">Mode</th>
            <th class="px-5 py-2.5 font-semibold">Biaya</th>
            <th class="px-5 py-2.5 font-semibold text-center">Transaksi</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($gateways as $gw)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">{{ $gw->name }}</td>
              <td class="px-5 py-3 text-slate-600">{{ $gw->driver_label }}</td>
              <td class="px-5 py-3 text-slate-600">
                @if ($gw->isManual())
                  <span class="text-slate-400 text-xs">—</span>
                @else
                  <span class="badge {{ $gw->isSandbox() ? 'badge-pending' : 'badge-active' }}">{{ $gw->isSandbox() ? 'Sandbox' : 'Production' }}</span>
                @endif
              </td>
              <td class="px-5 py-3 text-slate-600 text-xs">
                @if ($gw->fee_flat > 0 || $gw->fee_percent > 0)
                  Rp {{ number_format($gw->fee_flat, 0, ',', '.') }} + {{ rtrim(rtrim(number_format($gw->fee_percent, 2), '0'), '.') }}%
                @else
                  Gratis
                @endif
              </td>
              <td class="px-5 py-3 text-center text-slate-600">{{ $gw->payments_count }}</td>
              <td class="px-5 py-3">
                <span class="badge {{ $gw->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $gw->is_active ? 'Aktif' : 'Nonaktif' }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <form method="POST" action="{{ route('admin.gateway.status') }}">
                    @csrf
                    <input type="hidden" name="gateway_id" value="{{ $gw->id }}">
                    <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="{{ $gw->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                      <i class="fa-solid {{ $gw->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }} text-xs"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.gateway.edit.page', $gw) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.gateway.delete', $gw) }}" data-confirm="Hapus gateway ini?" data-confirm-title="Hapus Data" data-confirm-style="danger" data-confirm-label="Ya, Hapus" >
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Belum ada payment gateway. Tambahkan minimal satu supaya bisa menerima pembayaran.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($gateways->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $gateways->links() }}</div>
    @endif
  </div>

@endsection
