@extends('layouts.admin')
@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')

@section('content')
  @php $selectStyle = 'padding:.25rem .6rem;font-size:.875rem;border-radius:.375rem'; @endphp

  <div class="mb-4">
    <h1 class="h4 fw-bold text-dark mb-1">{{ $product->exists ? 'Edit Produk' : 'Tambah Produk' }}</h1>
    @if ($product->exists && $product->category)
      <p class="small text-muted mb-0">
        URL: <a href="{{ route('catalog.product', [$product->category->slug, $product->slug]) }}" target="_blank" class="text-accent">{{ route('catalog.product', [$product->category->slug, $product->slug]) }}</a>
      </p>
    @endif
  </div>

  @if ($categories->isEmpty())
    <div class="card border rounded-4 p-4 text-center text-muted small" style="max-width:42rem">
      Belum ada kategori produk.
      <a href="{{ route('admin.product-categories.create') }}" class="text-accent">Buat kategori dulu</a>.
    </div>
  @else
    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="row g-3" style="max-width:70rem">
      @csrf
      @if ($product->exists) @method('PUT') @endif

      <div class="col-12 col-lg-8">
        <div class="card border rounded-4 p-4 mb-3">
          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label small fw-medium text-dark">Kategori</label>
              <select name="product_category_id" class="form-select" style="{{ $selectStyle }}" required>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $cat)
                  <option value="{{ $cat->id }}" @selected(old('product_category_id', $product->product_category_id) == $cat->id)>{{ $cat->name }}</option>
                @endforeach
              </select>
              @error('product_category_id') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-medium text-dark">Nama Produk</label>
              <input type="text" name="name" id="nameInput" value="{{ old('name', $product->name) }}" class="form-control form-control-sm" required placeholder="Cloud Hosting - Pro">
              @error('name') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-medium text-dark">Slug URL</label>
            <input type="text" name="slug" id="slugInput" value="{{ old('slug', $product->slug) }}" class="form-control form-control-sm" placeholder="otomatis dari nama">
            @error('slug') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
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

          <div class="mb-3">
            <label class="form-label small fw-medium text-dark">Tagline <span class="text-muted fw-normal">(1 baris, tampil di kartu produk)</span></label>
            <input type="text" name="tagline" maxlength="255" value="{{ old('tagline', $product->tagline) }}" class="form-control form-control-sm" placeholder="Cocok untuk website bisnis & toko online">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-medium text-dark">Deskripsi</label>
            <textarea name="description" rows="5" class="form-control form-control-sm">{{ old('description', $product->description) }}</textarea>
          </div>

          <div>
            <label class="form-label small fw-medium text-dark">Daftar Fitur <span class="text-muted fw-normal">(satu per baris)</span></label>
            <textarea name="features_raw" rows="6" class="form-control form-control-sm" style="font-family:monospace;font-size:12px" placeholder="10 GB SSD Storage&#10;Unlimited Bandwidth&#10;Free SSL&#10;1 Domain">{{ old('features_raw', $product->features ? implode("\n", $product->features) : '') }}</textarea>
          </div>
        </div>

        <div class="card border rounded-4 p-4 mb-3">
          <h2 class="small fw-bold text-dark mb-1">Harga per Siklus Tagihan</h2>
          <p class="text-muted mb-3" style="font-size:12px">Kosongkan siklus yang tidak dijual untuk produk ini. Minimal isi satu.</p>
          @error('price_monthly') <p class="text-danger mb-2" style="font-size:12px">{{ $message }}</p> @enderror

          <div class="row g-3 mb-3">
            @foreach (\App\Models\Product::CYCLES as $key => $label)
              <div class="col-sm-6">
                <label class="form-label small fw-medium text-dark">{{ $label }}</label>
                <input type="number" step="0.01" name="price_{{ $key }}" value="{{ old('price_' . $key, $product->{'price_' . $key}) }}" class="form-control form-control-sm" placeholder="Kosongkan jika tidak dijual">
              </div>
            @endforeach
          </div>

          @if (auth('admin')->user()->isSuperadmin())
            <div class="mb-3" style="max-width:16rem">
              <label class="form-label small fw-medium text-dark">Jumlah Hari untuk Siklus "Custom" <span class="text-warning fw-normal" style="font-size:11px"><i class="fa-solid fa-lock"></i> Superadmin</span></label>
              <input type="number" min="1" name="custom_cycle_days" value="{{ old('custom_cycle_days', $product->custom_cycle_days) }}" class="form-control form-control-sm" placeholder="Contoh: 45">
              @error('custom_cycle_days') <p class="text-danger mt-1 mb-0" style="font-size:12px">{{ $message }}</p> @enderror
              <p class="text-muted mt-1 mb-0" style="font-size:11px">Cuma dipakai kalau kolom "Custom" di atas diisi harga.</p>
            </div>
          @endif

          <div>
            <label class="form-label small fw-medium text-dark">Biaya Setup <span class="text-muted fw-normal">(sekali bayar, opsional)</span></label>
            <input type="number" step="0.01" name="setup_fee" value="{{ old('setup_fee', $product->setup_fee ?? 0) }}" class="form-control form-control-sm">
          </div>
        </div>

        <div class="card border rounded-4 p-4">
          <h2 class="small fw-bold text-dark mb-1">Provisioning Otomatis</h2>
          <p class="text-muted mb-3" style="font-size:12px">
            Data ini menentukan ke server mana dan dengan paket apa akun cPanel dibuat otomatis
            saat order produk ini lunas. Boleh dikosongkan kalau provisioning-nya manual.
          </p>

          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label small fw-medium text-dark">Server Tujuan</label>
              <select name="server_id" class="form-select" style="{{ $selectStyle }}">
                <option value="">— Manual, tanpa auto-provisioning —</option>
                @foreach ($servers as $srv)
                  <option value="{{ $srv->id }}" @selected(old('server_id', $product->server_id) == $srv->id)>{{ $srv->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-medium text-dark">Nama Package di WHM/cPanel</label>
              <input type="text" name="panel_package" value="{{ old('panel_package', $product->panel_package) }}" class="form-control form-control-sm" placeholder="cloud_hosting_pro">
              <p class="text-muted mt-1 mb-0" style="font-size:11px">Harus sama persis dengan nama plan yang sudah dibuat di WHM.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card border rounded-4 p-4 mb-3">
          <h2 class="small fw-bold text-dark mb-2">Domain</h2>
          <select name="domain_option" class="form-select" style="{{ $selectStyle }}">
            <option value="none" @selected(old('domain_option', $product->domain_option ?? 'optional') === 'none')>Tidak terkait domain</option>
            <option value="optional" @selected(old('domain_option', $product->domain_option) === 'optional')>Opsional (boleh pakai domain sendiri)</option>
            <option value="required" @selected(old('domain_option', $product->domain_option) === 'required')>Wajib disertai domain</option>
          </select>
        </div>

        <div class="card border rounded-4 p-4">
          <h2 class="small fw-bold text-dark mb-2">Publikasi</h2>

          <label class="d-flex align-items-center gap-2 small text-dark mb-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="form-check-input" style="margin-top:0">
            Aktif (tampil di katalog)
          </label>
          <label class="d-flex align-items-center gap-2 small text-dark mb-3">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="form-check-input" style="margin-top:0">
            Tandai sebagai Unggulan
          </label>

          <div class="mb-3">
            <label class="form-label small fw-medium text-dark">Stok <span class="text-muted fw-normal">(opsional)</span></label>
            <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" class="form-control form-control-sm" placeholder="Kosongkan = tidak dibatasi">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-medium text-dark">Urutan Tampil</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="form-control form-control-sm">
          </div>

          <div class="d-flex flex-column gap-2 pt-2 border-top">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check" style="font-size:11px"></i> Simpan Produk</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
          </div>
        </div>
      </div>
    </form>
  @endif
@endsection
