@extends('layouts.admin')

@section('title', 'Halaman')

@section('content')

  @include('admin.pages._nav')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Halaman Statis</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola halaman seperti Tentang Kami, Syarat & Ketentuan, Kebijakan Privasi.</p>
    </div>
    <a href="{{ route('admin.page.add.page') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Halaman
    </a>
  </div>

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul halaman..." class="form-input sm:max-w-xs">
      <button type="submit" class="btn btn-outline">Cari</button>
      @if (request('search'))
        <a href="{{ url()->current() }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Judul</th>
            <th class="px-5 py-2.5 font-semibold">URL</th>
            <th class="px-5 py-2.5 font-semibold">SEO</th>
            <th class="px-5 py-2.5 font-semibold">Footer</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($pages as $page)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">{{ $page->title }}</td>
              <td class="px-5 py-3 text-slate-500 text-xs">
                <a href="{{ route('page.show', $page->slug) }}" target="_blank" class="hover:text-accent">/p/{{ $page->slug }}</a>
              </td>
              <td class="px-5 py-3">
                @if ($page->meta_description)
                  <span class="badge badge-active">Lengkap</span>
                @else
                  <span class="badge badge-pending">Belum diisi</span>
                @endif
                @if ($page->noindex)
                  <span class="badge badge-suspended ml-1">noindex</span>
                @endif
              </td>
              <td class="px-5 py-3 text-slate-600">{{ $page->show_in_footer ? 'Ya' : '—' }}</td>
              <td class="px-5 py-3">
                <span class="badge {{ $page->is_published ? 'badge-active' : 'badge-inactive' }}">{{ $page->is_published ? 'Terbit' : 'Draf' }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <form method="POST" action="{{ route('admin.page.status') }}">
                    @csrf
                    <input type="hidden" name="page_id" value="{{ $page->id }}">
                    <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="{{ $page->is_published ? 'Jadikan draf' : 'Terbitkan' }}">
                      <i class="fa-solid {{ $page->is_published ? 'fa-toggle-on' : 'fa-toggle-off' }} text-xs"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.page.edit.page', $page) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.page.delete', $page) }}" onsubmit="return confirm('Hapus halaman ini?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada halaman.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($pages->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $pages->links() }}</div>
    @endif
  </div>

@endsection
