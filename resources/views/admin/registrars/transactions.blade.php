@extends('layouts.admin')

@section('title', 'Riwayat Transaksi — ' . $registrar->name)

@section('content')

  <a href="{{ route('admin.registrars.index') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Registrar</a>
  <h1 class="text-xl font-bold text-slate-800 mt-1 mb-6">Riwayat Transaksi — {{ $registrar->name }}</h1>

  @if ($warning)
    <div class="card p-4 mb-5 border-amber-200 bg-amber-50/60 text-sm text-amber-800">
      <i class="fa-solid fa-triangle-exclamation"></i> {{ $warning }}
    </div>
  @endif

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Tanggal</th>
            <th class="px-5 py-2.5 font-semibold">Jenis</th>
            <th class="px-5 py-2.5 font-semibold">Keterangan</th>
            <th class="px-5 py-2.5 font-semibold text-right">Jumlah</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($transactions as $tx)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 text-slate-500 text-xs">{{ $tx['date'] ?? '—' }}</td>
              <td class="px-5 py-3"><span class="badge badge-inactive">{{ $tx['type'] }}</span></td>
              <td class="px-5 py-3 text-slate-700">{{ $tx['description'] }}</td>
              <td class="px-5 py-3 text-right font-medium {{ $tx['amount'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ $tx['amount'] >= 0 ? '+' : '' }}Rp {{ number_format($tx['amount'], 0, ',', '.') }}
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">Tidak ada transaksi ditemukan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
      <p class="text-xs text-slate-400">Halaman {{ $page }}</p>
      <div class="flex gap-2">
        @if ($page > 1)
          <a href="{{ route('admin.registrars.transactions', [$registrar, 'page' => $page - 1]) }}" class="btn btn-outline !py-1.5 !px-3 text-xs">Sebelumnya</a>
        @endif
        @if (count($transactions) === 25)
          <a href="{{ route('admin.registrars.transactions', [$registrar, 'page' => $page + 1]) }}" class="btn btn-outline !py-1.5 !px-3 text-xs">Berikutnya</a>
        @endif
      </div>
    </div>
  </div>

@endsection
