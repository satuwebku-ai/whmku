@extends('layouts.admin')
@section('title', 'Produk')

@section('content')
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Produk</h1>
      <p class="text-sm text-slate-500 mt-1">Katalog paket hosting/layanan yang dijual di halaman publik.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('admin.product-categories.index') }}" class="btn btn-outline"><i class="fa-solid fa-folder text-xs"></i> Kategori</a>
      <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus text-xs"></i> Tambah Produk</a>
    </div>
  </div>

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama produk..." class="form-input sm:max-w-xs">
      <select name="category_id" class="form-input sm:max-w-[200px]" onchange="this.form.submit()">
        <option value="">Semua Kategori</option>
        @foreach ($categories as $cat)
          <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-outline">Cari</button>
      @if (request('search') || request('category_id'))
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Produk</th>
            <th class="px-5 py-2.5 font-semibold">Kategori</th>
            <th class="px-5 py-2.5 font-semibold text-right">Mulai Dari</th>
            <th class="px-5 py-2.5 font-semibold">Domain</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($products as $product)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3">
                <p class="font-medium text-slate-700">
                  {{ $product->name }}
                  @if ($product->is_featured)
                    <i class="fa-solid fa-star text-[10px] text-amber-400 ml-1" title="Unggulan"></i>
                  @endif
                </p>
                @if ($product->tagline)
                  <p class="text-xs text-slate-400">{{ $product->tagline }}</p>
                @endif
              </td>
              <td class="px-5 py-3 text-slate-600">{{ $product->category->name ?? '—' }}</td>
              <td class="px-5 py-3 text-right text-slate-700">
                @if ($product->starting_price !== null)
                  Rp {{ number_format($product->starting_price, 0, ',', '.') }}
                @else
                  <span class="text-rose-500 text-xs">Belum ada harga</span>
                @endif
              </td>
              <td class="px-5 py-3 text-slate-600 text-xs capitalize">{{ $product->domain_option }}</td>
              <td class="px-5 py-3">
                <span class="badge {{ $product->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <form method="POST" action="{{ route('admin.product.status') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                      <i class="fa-solid {{ $product->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }} text-xs"></i>
                    </button>
                  </form>
                  @if ($product->is_active && $product->category)
                    <a href="{{ route('catalog.product', [$product->category->slug, $product->slug]) }}" target="_blank" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Lihat di katalog">
                      <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                    </a>
                  @endif
                  <a href="{{ route('admin.products.edit', $product) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                        data-confirm="Hapus produk {{ $product->name }}?" data-confirm-title="Hapus Produk"
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
            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada produk. Buat kategori dulu kalau belum ada.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($products->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $products->links() }}</div>
    @endif
  </div>
@endsection
