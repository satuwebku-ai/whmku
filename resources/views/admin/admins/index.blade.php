@extends('layouts.admin')

@section('title', 'Manajemen Admin')

@section('content')

  @include('admin.admins._nav')

  <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Manajemen Admin</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola akun staf beserta tingkat aksesnya.</p>
    </div>
    <a href="{{ route('admin.admin.add.page') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Admin
    </a>
  </div>

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, username, email..." class="form-input sm:max-w-xs">
      <button type="submit" class="btn btn-outline">Cari</button>
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Nama</th>
            <th class="px-5 py-2.5 font-semibold">Username</th>
            <th class="px-5 py-2.5 font-semibold">Peran</th>
            <th class="px-5 py-2.5 font-semibold">Login Terakhir</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($admins as $row)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3">
                <p class="font-medium text-slate-700">
                  {{ $row->name }}
                  @if ($row->id === auth('admin')->id())
                    <span class="text-[10px] text-accent font-normal">(Anda)</span>
                  @endif
                </p>
                <p class="text-xs text-slate-400">{{ $row->email }}</p>
              </td>
              <td class="px-5 py-3 font-mono text-slate-600">{{ $row->username }}</td>
              <td class="px-5 py-3">
                <span class="badge {{ $row->isSuperadmin() ? 'badge-active' : ($row->isStaff() ? 'badge-inactive' : 'badge-pending') }}">
                  {{ $row->role_label }}
                </span>
              </td>
              <td class="px-5 py-3 text-slate-600 text-xs">
                {{ $row->last_login_at?->diffForHumans() ?? 'Belum pernah' }}
                @if ($row->last_login_ip)
                  <span class="block text-slate-400">{{ $row->last_login_ip }}</span>
                @endif
              </td>
              <td class="px-5 py-3">
                <span class="badge {{ $row->is_active ? 'badge-active' : 'badge-suspended' }}">
                  {{ $row->is_active ? 'Aktif' : 'Diblokir' }}
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  @if ($row->id !== auth('admin')->id())
                    <form method="POST" action="{{ route('admin.admin.status') }}"
                          data-confirm="{{ $row->is_active ? 'Blokir' : 'Aktifkan' }} akun {{ $row->username }}?"
                          data-confirm-title="{{ $row->is_active ? 'Blokir Admin' : 'Aktifkan Admin' }}"
                          data-confirm-style="{{ $row->is_active ? 'danger' : 'info' }}"
                          data-confirm-label="Ya, Lanjutkan">
                      @csrf
                      <input type="hidden" name="admin_id" value="{{ $row->id }}">
                      <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500"
                              title="{{ $row->is_active ? 'Blokir' : 'Aktifkan' }}">
                        <i class="fa-solid {{ $row->is_active ? 'fa-ban' : 'fa-circle-check' }} text-xs"></i>
                      </button>
                    </form>
                  @endif

                  <a href="{{ route('admin.admin.edit.page', $row) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>

                  @if ($row->id !== auth('admin')->id())
                    <form method="POST" action="{{ route('admin.admin.delete', $row) }}"
                          data-confirm="Hapus admin {{ $row->username }}? Tindakan ini tidak bisa dibatalkan."
                          data-confirm-title="Hapus Admin" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
                      @csrf @method('DELETE')
                      <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500">
                        <i class="fa-regular fa-trash-can text-xs"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada admin lain.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($admins->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $admins->links() }}</div>
    @endif
  </div>

  <div class="card p-5 mt-5 border-slate-200">
    <h2 class="text-sm font-semibold text-slate-800 mb-2">Arti Peran</h2>
    <dl class="space-y-2 text-xs">
      @foreach (\App\Models\Admin::ROLES as $key => $desc)
        <div class="flex gap-2">
          <dt class="shrink-0"><span class="badge {{ $key === 'superadmin' ? 'badge-active' : ($key === 'staff' ? 'badge-inactive' : 'badge-pending') }}">{{ ucfirst($key) }}</span></dt>
          <dd class="text-slate-500">{{ \Illuminate\Support\Str::after($desc, '— ') }}</dd>
        </div>
      @endforeach
    </dl>
  </div>

@endsection
