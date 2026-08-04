@extends('layouts.admin')

@section('title', 'Domain')

@section('content')

  @include('admin.domains._nav')

  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Domain Aktif</h1>
      <p class="text-sm text-slate-500 mt-1">Domain milik klien.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('admin.domain.search') }}" class="btn btn-outline"><i class="fa-solid fa-magnifying-glass text-xs"></i> Cek Domain</a>
      <a href="{{ route('admin.domain.add.page') }}" class="btn btn-primary"><i class="fa-solid fa-plus text-xs"></i> Tambah Domain</a>
    </div>
  </div>

  @include('admin.domains._status')

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari domain..." class="form-input sm:max-w-xs">
      <button type="submit" class="btn btn-outline">Cari</button>
      @if (request('search'))
        <a href="{{ url()->current() }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Domain</th>
            <th class="px-5 py-2.5 font-semibold">Klien</th>
            <th class="px-5 py-2.5 font-semibold">Registrar</th>
            <th class="px-5 py-2.5 font-semibold">Jatuh Tempo</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($domains as $domain)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">
                <a href="{{ route('admin.domains.details', $domain) }}" class="hover:text-accent">{{ $domain->domain_name }}</a>
              </td>
              <td class="px-5 py-3 text-slate-600">{{ $domain->client->name ?? '—' }}</td>
              <td class="px-5 py-3 text-slate-600">{{ $domain->registrar->name ?? 'Manual' }}</td>
              <td class="px-5 py-3 text-slate-600">
                {{ $domain->expiry_date?->format('d M Y') ?? '—' }}
                @if ($domain->is_expiring_soon)
                  <span class="badge badge-pending ml-1">Segera Habis</span>
                @endif
              </td>
              <td class="px-5 py-3"><span class="badge badge-{{ $domain->status === 'expired' ? 'suspended' : $domain->status }}">{{ ucfirst($domain->status) }}</span></td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.domains.details', $domain) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Detail">
                    <i class="fa-regular fa-eye text-xs"></i>
                  </a>
                  <a href="{{ route('admin.domain.edit.page', $domain) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.domain.delete', $domain) }}" onsubmit="return confirm('Hapus data domain ini?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Tidak ada domain di kategori ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($domains->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $domains->links() }}</div>
    @endif
  </div>

@endsection
