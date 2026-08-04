@extends('layouts.admin')

@section('title', 'Server')

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Server</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola server cPanel/WHM, DirectAdmin, atau Plesk yang terhubung.</p>
    </div>
    <a href="{{ route('admin.servers.create') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Server
    </a>
  </div>

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Nama</th>
            <th class="px-5 py-2.5 font-semibold">Hostname</th>
            <th class="px-5 py-2.5 font-semibold">Panel</th>
            <th class="px-5 py-2.5 font-semibold text-center">Akun</th>
            <th class="px-5 py-2.5 font-semibold">Cek Terakhir</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($servers as $server)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">{{ $server->name }}</td>
              <td class="px-5 py-3 text-slate-600">{{ $server->hostname }}:{{ $server->port }}</td>
              <td class="px-5 py-3 text-slate-600 capitalize">{{ $server->panel === 'cpanel' ? 'cPanel / WHM' : $server->panel }}</td>
              <td class="px-5 py-3 text-center text-slate-600">{{ $server->hosting_accounts_count }}</td>
              <td class="px-5 py-3 text-slate-500 text-xs">
                @if ($server->last_checked_at)
                  {{ $server->last_checked_at->diffForHumans() }}
                  <br>
                  <span class="{{ $server->last_check_status === 'ok' ? 'text-emerald-600' : 'text-rose-500' }}">
                    {{ $server->last_check_status === 'ok' ? 'Terhubung' : Str::limit($server->last_check_status, 40) }}
                  </span>
                @else
                  Belum pernah dicek
                @endif
              </td>
              <td class="px-5 py-3">
                <span class="badge {{ $server->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $server->is_active ? 'Aktif' : 'Nonaktif' }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <form method="POST" action="{{ route('admin.servers.test-connection', $server) }}">
                    @csrf
                    <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Tes Koneksi">
                      <i class="fa-solid fa-plug text-xs"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.servers.edit', $server) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.servers.destroy', $server) }}" onsubmit="return confirm('Hapus server ini?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Belum ada server terhubung.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($servers->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $servers->links() }}</div>
    @endif
  </div>

@endsection
