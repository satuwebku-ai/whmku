@extends('layouts.admin')

@section('title', 'Addons')

@section('content')

  <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Addons</h1>
      <p class="text-sm text-slate-500 mt-1">Fitur tambahan yang bisa dipasang klien di layanan hosting mereka (IP Dedicated, Backup, dll).</p>
    </div>
    <a href="{{ route('admin.addons.create') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Addon
    </a>
  </div>

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Nama</th>
            <th class="px-5 py-2.5 font-semibold text-right">Bulanan</th>
            <th class="px-5 py-2.5 font-semibold text-right">Tahunan</th>
            <th class="px-5 py-2.5 font-semibold text-center">Dipakai</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($addons as $addon)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">
                <a href="{{ route('admin.addons.edit', $addon) }}" class="hover:text-accent">{{ $addon->name }}</a>
              </td>
              <td class="px-5 py-3 text-right text-slate-600">{{ $addon->price_monthly ? 'Rp ' . number_format($addon->price_monthly, 0, ',', '.') : '—' }}</td>
              <td class="px-5 py-3 text-right text-slate-600">{{ $addon->price_annually ? 'Rp ' . number_format($addon->price_annually, 0, ',', '.') : '—' }}</td>
              <td class="px-5 py-3 text-center text-slate-500">{{ $addon->attachments_count }}</td>
              <td class="px-5 py-3">
                <form method="POST" action="{{ route('admin.addon.status') }}">
                  @csrf
                  <input type="hidden" name="addon_id" value="{{ $addon->id }}">
                  <button type="submit" class="badge {{ $addon->is_active ? 'badge-active' : 'badge-inactive' }}">
                    {{ $addon->is_active ? 'Aktif' : 'Nonaktif' }}
                  </button>
                </form>
              </td>
              <td class="px-5 py-3 text-right">
                <a href="{{ route('admin.addons.edit', $addon) }}" class="text-xs text-slate-500 hover:text-accent mr-3">Edit</a>
                <form method="POST" action="{{ route('admin.addons.destroy', $addon) }}" class="inline"
                      data-confirm="Hapus addon {{ $addon->name }}?" data-confirm-title="Hapus Addon" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
                  @csrf @method('DELETE')
                  <button type="submit" class="text-xs text-rose-500 hover:underline">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada addon. Klik "Tambah Addon" untuk membuat yang pertama.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($addons->hasPages())
      <div class="px-5 py-3 border-t border-slate-100">{{ $addons->links() }}</div>
    @endif
  </div>

@endsection
