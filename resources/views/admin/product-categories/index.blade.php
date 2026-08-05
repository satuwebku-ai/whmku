@extends('layouts.admin')
@section('title', 'Kategori Produk')

@section('content')
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Kategori Produk</h1>
      <p class="text-sm text-slate-500 mt-1">Kelompok produk di katalog, mis. Shared Hosting, WordPress Hosting, VPS.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('admin.products.index') }}" class="btn btn-outline"><i class="fa-solid fa-box text-xs"></i> Lihat Produk</a>
      <a href="{{ route('admin.product-categories.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus text-xs"></i> Tambah Kategori</a>
    </div>
  </div>

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Nama</th>
            <th class="px-5 py-2.5 font-semibold">Slug</th>
            <th class="px-5 py-2.5 font-semibold text-center">Produk</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($categories as $category)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">
                @if ($category->icon)<i class="fa-solid {{ $category->icon }} text-slate-400 mr-1.5"></i>@endif
                {{ $category->name }}
              </td>
              <td class="px-5 py-3 text-slate-500 text-xs">/hosting/{{ $category->slug }}</td>
              <td class="px-5 py-3 text-center text-slate-600">{{ $category->products_count }}</td>
              <td class="px-5 py-3"><span class="badge {{ $category->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.product-categories.edit', $category) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.product-categories.destroy', $category) }}"
                        data-confirm="Hapus kategori {{ $category->name }}?" data-confirm-title="Hapus Kategori"
                        data-confirm-style="danger" data-confirm-label="Ya, Hapus">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Belum ada kategori produk.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($categories->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $categories->links() }}</div>
    @endif
  </div>
@endsection
