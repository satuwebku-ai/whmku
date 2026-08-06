@extends('layouts.admin')

@section('title', 'Kupon Diskon')

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Kupon Diskon</h1>
      <p class="text-sm text-slate-500 mt-1">Kode promo yang bisa dipakai klien saat checkout.</p>
    </div>
    <a href="{{ route('admin.coupon.add.page') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Kupon
    </a>
  </div>

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode kupon..." class="form-input sm:max-w-xs">
      <button type="submit" class="btn btn-outline">Cari</button>
      @if (request('search'))
        <a href="{{ route('admin.coupons') }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Kode</th>
            <th class="px-5 py-2.5 font-semibold">Nilai</th>
            <th class="px-5 py-2.5 font-semibold">Min. Transaksi</th>
            <th class="px-5 py-2.5 font-semibold text-center">Terpakai</th>
            <th class="px-5 py-2.5 font-semibold">Berlaku Sampai</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($coupons as $coupon)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-mono font-semibold text-slate-700">{{ $coupon->code }}</td>
              <td class="px-5 py-3 text-slate-600">{{ $coupon->value_label }}</td>
              <td class="px-5 py-3 text-slate-600">
                {{ $coupon->min_order > 0 ? 'Rp ' . number_format($coupon->min_order, 0, ',', '.') : '—' }}
              </td>
              <td class="px-5 py-3 text-center text-slate-600">
                {{ $coupon->invoices_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}
              </td>
              <td class="px-5 py-3 text-slate-600">{{ $coupon->expires_at?->format('d M Y') ?? 'Tanpa batas' }}</td>
              <td class="px-5 py-3">
                <span class="badge {{ $coupon->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $coupon->is_active ? 'Aktif' : 'Nonaktif' }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <form method="POST" action="{{ route('admin.coupon.status') }}">
                    @csrf
                    <input type="hidden" name="coupon_id" value="{{ $coupon->id }}">
                    <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="{{ $coupon->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                      <i class="fa-solid {{ $coupon->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }} text-xs"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.coupon.edit.page', $coupon) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.coupon.delete', $coupon) }}" onsubmit="return confirm('Hapus kupon ini?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Belum ada kupon.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($coupons->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $coupons->links() }}</div>
    @endif
  </div>

@endsection
