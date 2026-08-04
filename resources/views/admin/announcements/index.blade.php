@extends('layouts.admin')

@section('title', 'Pengumuman')

@section('content')

  @include('admin.pages._nav')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Pengumuman</h1>
      <p class="text-sm text-slate-500 mt-1">Info maintenance, promo, dan gangguan layanan untuk klien.</p>
    </div>
    <a href="{{ route('admin.announcement.add.page') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Buat Pengumuman
    </a>
  </div>

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul..." class="form-input sm:max-w-xs">
      <select name="category" class="form-input sm:max-w-[160px]">
        <option value="">Semua Kategori</option>
        <option value="info" @selected(request('category') === 'info')>Info</option>
        <option value="promo" @selected(request('category') === 'promo')>Promo</option>
        <option value="maintenance" @selected(request('category') === 'maintenance')>Maintenance</option>
        <option value="incident" @selected(request('category') === 'incident')>Gangguan</option>
      </select>
      <button type="submit" class="btn btn-outline">Filter</button>
      @if (request('search') || request('category'))
        <a href="{{ url()->current() }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Judul</th>
            <th class="px-5 py-2.5 font-semibold">Kategori</th>
            <th class="px-5 py-2.5 font-semibold">Terbit</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($announcements as $item)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3">
                <p class="font-medium text-slate-700">
                  {{ $item->title }}
                  @if ($item->is_pinned)
                    <i class="fa-solid fa-thumbtack text-[10px] text-indigo-500 ml-1" title="Disematkan"></i>
                  @endif
                </p>
                <a href="{{ route('announcements.show', $item->slug) }}" target="_blank" class="text-xs text-slate-400 hover:text-accent">/announcements/{{ $item->slug }}</a>
              </td>
              <td class="px-5 py-3"><span class="badge badge-{{ $item->category_badge }} capitalize">{{ $item->category }}</span></td>
              <td class="px-5 py-3 text-slate-600 text-xs">{{ $item->published_at?->format('d M Y H:i') ?? '—' }}</td>
              <td class="px-5 py-3">
                <span class="badge {{ $item->is_published ? 'badge-active' : 'badge-inactive' }}">{{ $item->is_published ? 'Terbit' : 'Draf' }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.announcement.edit.page', $item) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.announcement.delete', $item) }}" onsubmit="return confirm('Hapus pengumuman ini?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Belum ada pengumuman.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($announcements->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $announcements->links() }}</div>
    @endif
  </div>

@endsection
