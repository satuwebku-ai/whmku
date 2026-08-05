@extends('layouts.admin')

@section('title', 'Klien')

@section('content')

  @include('admin.clients._nav')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Klien</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola data pelanggan hosting Anda.</p>
    </div>
    <a href="{{ route('admin.client.add.page') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Klien
    </a>
  </div>

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau perusahaan..." class="form-input sm:max-w-xs">
      <button type="submit" class="btn btn-outline">Cari</button>
      @if (request('search'))
        <a href="{{ url()->current() }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Klien</th>
            <th class="px-5 py-2.5 font-semibold">Kontak</th>
            <th class="px-5 py-2.5 font-semibold text-center">Layanan</th>
            <th class="px-5 py-2.5 font-semibold text-center">Order</th>
            <th class="px-5 py-2.5 font-semibold text-center">Invoice</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($clients as $client)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3">
                <a href="{{ route('admin.clients.details', $client) }}" class="flex items-center gap-3 hover:text-accent">
                  <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center shrink-0">
                    {{ $client->initials }}
                  </span>
                  <div>
                    <p class="font-medium">{{ $client->name }}</p>
                    @if ($client->company)
                      <p class="text-xs text-slate-400">{{ $client->company }}</p>
                    @endif
                  </div>
                </a>
              </td>
              <td class="px-5 py-3 text-slate-600">
                <p>{{ $client->email }}</p>
                @if ($client->phone)
                  <p class="text-xs text-slate-400">{{ $client->phone }}</p>
                @endif
              </td>
              <td class="px-5 py-3 text-center text-slate-600">{{ $client->hosting_accounts_count }}</td>
              <td class="px-5 py-3 text-center text-slate-600">{{ $client->orders_count }}</td>
              <td class="px-5 py-3 text-center text-slate-600">{{ $client->invoices_count }}</td>
              <td class="px-5 py-3">
                <span class="badge badge-{{ $client->status }}">{{ $client->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.clients.details', $client) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Detail">
                    <i class="fa-regular fa-eye text-xs"></i>
                  </a>
                  <a href="{{ route('admin.client.edit.page', $client) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.client.delete', $client) }}" data-confirm="Hapus klien ini? Semua layanan, order, dan invoice terkait juga akan terhapus." data-confirm-title="Hapus Data" data-confirm-style="danger" data-confirm-label="Ya, Hapus" >
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Tidak ada klien di kategori ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($clients->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $clients->links() }}</div>
    @endif
  </div>

@endsection
