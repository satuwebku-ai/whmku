@extends('layouts.admin')
@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')

@section('content')
  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $product->exists ? 'Edit Produk' : 'Tambah Produk' }}</h1>
    @if ($product->exists && $product->category)
      <p class="text-sm text-slate-500 mt-1">
        URL: <a href="{{ route('catalog.product', [$product->category->slug, $product->slug]) }}" target="_blank" class="text-accent hover:underline">{{ route('catalog.product', [$product->category->slug, $product->slug]) }}</a>
      </p>
    @endif
  </div>

  @if ($categories->isEmpty())
    <div class="card p-6 max-w-2xl text-center text-slate-500 text-sm">
      Belum ada kategori produk.
      <a href="{{ route('admin.product-categories.create') }}" class="text-accent hover:underline">Buat kategori dulu</a>.
    </div>
  @else
    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="grid lg:grid-cols-3 gap-5 max-w-5xl">
      @csrf
      @if ($product->exists) @method('PUT') @endif

      <div class="lg:col-span-2 space-y-5">
        <div class="card p-6 space-y-4">
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Kategori</label>
              <select name="product_category_id" class="form-input" required>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $cat)
                  <option value="{{ $cat->id }}" @selected(old('product_category_id', $product->product_category_id) == $cat->id)>{{ $cat->name }}</option>
                @endforeach
              </select>
              @error('product_category_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="form-label">Nama Produk</label>
              <input type="text" name="name" id="nameInput" value="{{ old('name', $product->name) }}" class="form-input" required placeholder="Cloud Hosting - Pro">
              @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
          </div>

          <div>
            <label class="form-label">Slug URL</label>
            <input type="text" name="slug" id="slugInput" value="{{ old('slug', $product->slug) }}" class="form-input" placeholder="otomatis dari nama">
            @error('slug') <p class="form-error">{{ $message }}</p> @enderror
          </div>

          <script>
            (function () {
              const name = document.getElementById('nameInput');
              const slug = document.getElementById('slugInput');

              const slugify = (s) => s.toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');

              let slugTouched = slug.value.length > 0;
              slug.addEventListener('input', () => { slugTouched = true; });
              name.addEventListener('input', () => {
                if (!slugTouched) slug.value = slugify(name.value);
              });
            })();
          </script>

          <div>
            <label class="form-label">Tagline <span class="text-slate-400 font-normal">(1 baris, tampil di kartu produk)</span></label>
            <input type="text" name="tagline" maxlength="255" value="{{ old('tagline', $product->tagline) }}" class="form-input" placeholder="Cocok untuk website bisnis & toko online">
          </div>

          <div>
            <label class="form-label">Deskripsi</label>
            <textarea name="description" rows="5" class="form-input">{{ old('description', $product->description) }}</textarea>
          </div>

          <div>
            <label class="form-label">Daftar Fitur <span class="text-slate-400 font-normal">(satu per baris)</span></label>
            <textarea name="features_raw" rows="6" class="form-input font-mono text-xs" placeholder="10 GB SSD Storage&#10;Unlimited Bandwidth&#10;Free SSL&#10;1 Domain">{{ old('features_raw', $product->features ? implode("\n", $product->features) : '') }}</textarea>
          </div>
        </div>

        <div class="card p-6 space-y-4">
          <h2 class="text-sm font-semibold text-slate-800">Harga per Siklus Tagihan</h2>
          <p class="text-xs text-slate-500">Kosongkan siklus yang tidak dijual untuk produk ini. Minimal isi satu.</p>
          @error('price_monthly') <p class="form-error">{{ $message }}</p> @enderror

          <div class="grid sm:grid-cols-2 gap-4">
            @foreach (\App\Models\Product::CYCLES as $key => $label)
              <div>
                <label class="form-label">{{ $label }}</label>
                <input type="number" step="0.01" name="price_{{ $key }}" value="{{ old('price_' . $key, $product->{'price_' . $key}) }}" class="form-input" placeholder="Kosongkan jika tidak dijual">
              </div>
            @endforeach
          </div>

          <div>
            <label class="form-label">Biaya Setup <span class="text-slate-400 font-normal">(sekali bayar, opsional)</span></label>
            <input type="number" step="0.01" name="setup_fee" value="{{ old('setup_fee', $product->setup_fee ?? 0) }}" class="form-input">
          </div>
        </div>

        <div class="card p-6 space-y-4">
          <h2 class="text-sm font-semibold text-slate-800">Provisioning Otomatis <span class="text-slate-400 font-normal text-xs">(dipakai Fase 7c)</span></h2>
          <p class="text-xs text-slate-500">
            Data ini menentukan ke server mana dan dengan paket apa akun cPanel dibuat otomatis
            saat order produk ini lunas. Boleh dikosongkan kalau provisioning-nya manual.
          </p>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Server Tujuan</label>
              <select name="server_id" class="form-input">
                <option value="">— Manual, tanpa auto-provisioning —</option>
                @foreach ($servers as $srv)
                  <option value="{{ $srv->id }}" @selected(old('server_id', $product->server_id) == $srv->id)>{{ $srv->name }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="form-label">Nama Package di WHM/cPanel</label>
              <input type="text" name="panel_package" value="{{ old('panel_package', $product->panel_package) }}" class="form-input" placeholder="cloud_hosting_pro">
              <p class="text-[11px] text-slate-400 mt-1">Harus sama persis dengan nama plan yang sudah dibuat di WHM.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-5">
        <div class="card p-5 space-y-4">
          <h2 class="text-sm font-semibold text-slate-800">Domain</h2>
          <select name="domain_option" class="form-input">
            <option value="none" @selected(old('domain_option', $product->domain_option ?? 'optional') === 'none')>Tidak terkait domain</option>
            <option value="optional" @selected(old('domain_option', $product->domain_option) === 'optional')>Opsional (boleh pakai domain sendiri)</option>
            <option value="required" @selected(old('domain_option', $product->domain_option) === 'required')>Wajib disertai domain</option>
          </select>
        </div>

        <div class="card p-5 space-y-4">
          <h2 class="text-sm font-semibold text-slate-800">Publikasi</h2>

          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
            Aktif (tampil di katalog)
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
            Tandai sebagai Unggulan
          </label>

          <div>
            <label class="form-label">Stok <span class="text-slate-400 font-normal">(opsional)</span></label>
            <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" class="form-input" placeholder="Kosongkan = tidak dibatasi">
          </div>

          <div>
            <label class="form-label">Urutan Tampil</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="form-input">
          </div>

          <div class="flex flex-col gap-2 pt-2 border-t border-slate-100">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Produk</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Batal</a>
          </div>
        </div>
      </div>
    </form>
  @endif
@endsection
