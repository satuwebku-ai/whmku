@extends('layouts.admin')

@section('title', 'TLD Pricing')

@section('content')

  @include('admin.domains._nav')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">TLD Pricing</h1>
      <p class="text-sm text-slate-500 mt-1">Atur harga jual per ekstensi domain.</p>
    </div>
    <a href="{{ route('admin.tlds.create') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah TLD
    </a>
  </div>

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Ekstensi</th>
            <th class="px-5 py-2.5 font-semibold">Registrar</th>
            <th class="px-5 py-2.5 font-semibold text-right">Register</th>
            <th class="px-5 py-2.5 font-semibold text-right">Renew</th>
            <th class="px-5 py-2.5 font-semibold text-right">Transfer</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($tlds as $tld)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">{{ $tld->extension }}</td>
              <td class="px-5 py-3 text-slate-600">{{ $tld->registrar->name ?? '—' }}</td>
              <td class="px-5 py-3 text-right text-slate-700">Rp {{ number_format($tld->register_price, 0, ',', '.') }}</td>
              <td class="px-5 py-3 text-right text-slate-700">Rp {{ number_format($tld->renew_price, 0, ',', '.') }}</td>
              <td class="px-5 py-3 text-right text-slate-700">Rp {{ number_format($tld->transfer_price, 0, ',', '.') }}</td>
              <td class="px-5 py-3"><span class="badge {{ $tld->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $tld->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.tlds.edit', $tld) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.tlds.destroy', $tld) }}" onsubmit="return confirm('Hapus TLD ini?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Belum ada TLD. Tambahkan dulu supaya harga muncul di halaman Cek Domain.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($tlds->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $tlds->links() }}</div>
    @endif
  </div>

@endsection
