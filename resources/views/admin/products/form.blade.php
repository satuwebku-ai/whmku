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
              <select name="server_id" id="serverSelect" class="form-select" style="{{ $selectStyle }}"
                      data-cloud-ids="{{ $servers->where('panel', 'idcloudhost')->pluck('id')->implode(',') }}">
                <option value="">— Manual, tanpa auto-provisioning —</option>
                @foreach ($servers as $srv)
                  <option value="{{ $srv->id }}" @selected(old('server_id', $product->server_id) == $srv->id)>
                    {{ $srv->name }}{{ $srv->panel === 'idcloudhost' ? ' (Cloud/VPS)' : '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-sm-6" id="cpanelPackageField">
              <label class="form-label small fw-medium text-dark">Nama Package di WHM/cPanel</label>
              <input type="text" name="panel_package" id="panelPackageInput" value="{{ old('panel_package', $product->panel_package) }}" class="form-control form-control-sm" placeholder="cloud_hosting_pro">
              <p class="text-muted mt-1 mb-0" style="font-size:11px">Harus sama persis dengan nama plan yang sudah dibuat di WHM.</p>
            </div>
          </div>

          {{-- Spesifikasi VPS -- muncul hanya kalau server tujuannya
               provider cloud. Isian ramah pengguna ini otomatis diubah
               jadi JSON di kolom panel_package saat disimpan, supaya
               admin tidak perlu mengetik JSON mentah. --}}
          @php
            $vmSpec = json_decode((string) $product->panel_package, true);
            $vmSpec = is_array($vmSpec) && isset($vmSpec['vcpu']) ? $vmSpec : [];
          @endphp
          <div id="vpsSpecFields" class="d-none mt-3 pt-3 border-top">
            <p class="fw-bold text-muted mb-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.03em">
              <i class="fa-solid fa-microchip"></i> Spesifikasi VPS
            </p>
            <div class="row g-3">
              <div class="col-6 col-lg-3">
                <label class="form-label small fw-medium text-dark">vCPU (Core)</label>
                <input type="number" name="vm_vcpu" id="vmVcpu" min="1" max="16" value="{{ old('vm_vcpu', $vmSpec['vcpu'] ?? 1) }}" class="form-control form-control-sm">
              </div>
              <div class="col-6 col-lg-3">
                <label class="form-label small fw-medium text-dark">RAM (MB)</label>
                <input type="number" name="vm_ram" id="vmRam" min="512" step="512" value="{{ old('vm_ram', $vmSpec['ram'] ?? 1024) }}" class="form-control form-control-sm">
                <p class="text-muted mt-1 mb-0" style="font-size:10px">1024 = 1 GB</p>
              </div>
              <div class="col-6 col-lg-3">
                <label class="form-label small fw-medium text-dark">Disk (GB)</label>
                <input type="number" name="vm_disk" id="vmDisk" min="20" value="{{ old('vm_disk', $vmSpec['disk'] ?? 20) }}" class="form-control form-control-sm">
              </div>
              <div class="col-6 col-lg-3">
                <label class="form-label small fw-medium text-dark">Backup Otomatis</label>
                <select name="vm_backup" id="vmBackup" class="form-select form-select-sm">
                  <option value="0" @selected(! ($vmSpec['backup_enabled'] ?? false))>Tidak</option>
                  <option value="1" @selected($vmSpec['backup_enabled'] ?? false)>Ya (biaya tambahan)</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-medium text-dark">OS (os_name)</label>
                <input type="text" name="vm_os_name" id="vmOsName" value="{{ old('vm_os_name', $vmSpec['os_name'] ?? 'ubuntu') }}" class="form-control form-control-sm" placeholder="ubuntu">
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-medium text-dark">Versi OS (os_version)</label>
                <input type="text" name="vm_os_version" id="vmOsVersion" value="{{ old('vm_os_version', $vmSpec['os_version'] ?? '') }}" class="form-control form-control-sm" placeholder="22.04-lts">
              </div>
            </div>
            <p class="text-muted mt-2 mb-0" style="font-size:11px">
              <i class="fa-solid fa-circle-info"></i>
              Salin <code>os_name</code> &amp; <code>os_version</code> <b>persis</b> dari halaman
              Diagnosa server (bagian "Jenis OS Tersedia") — kalau tidak cocok, pembuatan VM akan ditolak.
              Estimasi biaya modal bisa dilihat di halaman Diagnosa yang sama.
            </p>
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

  <script>
    (function () {
      const select = document.getElementById('serverSelect');
      if (! select) return;

      const cloudIds = (select.dataset.cloudIds || '').split(',').filter(Boolean);
      const vpsFields = document.getElementById('vpsSpecFields');
      const cpanelField = document.getElementById('cpanelPackageField');
      const packageInput = document.getElementById('panelPackageInput');

      function isCloud() {
        return cloudIds.includes(select.value);
      }

      function sync() {
        const cloud = isCloud();
        vpsFields.classList.toggle('d-none', ! cloud);
        cpanelField.classList.toggle('d-none', cloud);
      }

      // Saat disimpan, isian VPS yang ramah pengguna dipadatkan jadi JSON
      // ke kolom panel_package -- format yang dibaca IdCloudHostService
      // & HourlyRateCalculator. Admin tidak perlu tahu soal JSON-nya.
      select.form.addEventListener('submit', function () {
        if (! isCloud()) return;

        packageInput.value = JSON.stringify({
          vcpu: parseInt(document.getElementById('vmVcpu').value) || 1,
          ram: parseInt(document.getElementById('vmRam').value) || 1024,
          disk: parseInt(document.getElementById('vmDisk').value) || 20,
          os_name: document.getElementById('vmOsName').value.trim(),
          os_version: document.getElementById('vmOsVersion').value.trim(),
          backup_enabled: document.getElementById('vmBackup').value === '1',
        });
      });

      select.addEventListener('change', sync);
      sync();
    })();
  </script>
@endsection
