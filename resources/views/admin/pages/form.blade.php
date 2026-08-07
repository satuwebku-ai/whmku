@extends('layouts.admin')

@section('title', $page->exists ? 'Edit Halaman' : 'Tambah Halaman')

@section('content')

  @include('admin.pages._nav')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $page->exists ? 'Edit Halaman' : 'Tambah Halaman' }}</h1>
    @if ($page->exists)
      <p class="text-sm text-slate-500 mt-1">
        URL: <a href="{{ route('page.show', $page->slug) }}" target="_blank" class="text-accent hover:underline">{{ $page->url }}</a>
      </p>
    @endif
  </div>

  <form method="POST" action="{{ $page->exists ? route('admin.page.update', $page) : route('admin.page.add') }}" class="grid lg:grid-cols-3 gap-5 max-w-5xl">
    @csrf

    {{-- Konten utama --}}
    <div class="lg:col-span-2 space-y-5">
      <div class="card p-6 space-y-4">
        <div>
          <label class="form-label">Judul Halaman</label>
          <input type="text" name="title" id="titleInput" value="{{ old('title', $page->title) }}" class="form-input" required>
          @error('title') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="form-label">Slug URL</label>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 shrink-0">{{ url('/') }}/</span>
            <input type="text" name="slug" id="slugInput" value="{{ old('slug', $page->slug) }}" placeholder="otomatis dari judul" class="form-input">
          </div>
          @error('slug') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">
            Kosongkan untuk dibuat otomatis dari judul. Hati-hati mengubah slug halaman yang sudah terbit — link lama akan mati.
            Beberapa kata seperti "admin", "hosting", "keranjang" tidak bisa dipakai karena sudah menjadi alamat fitur sistem.
          </p>
        </div>

        <div>
          <label class="form-label">Konten</label>
          <textarea name="content" rows="16" class="form-input font-mono text-xs">{{ old('content', $page->content) }}</textarea>
          @error('content') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">
            Mendukung HTML (<code>&lt;h2&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;a&gt;</code>, dsb).
            Konten ditampilkan apa adanya ke pengunjung, jadi jangan tempel HTML dari sumber yang tidak dipercaya.
          </p>
        </div>
      </div>

      {{-- SEO --}}
      <div class="card p-6 space-y-4">
        <h2 class="text-sm font-semibold text-slate-800">Pengaturan SEO</h2>

        <div>
          <label class="form-label">Meta Title</label>
          <input type="text" name="meta_title" id="metaTitle" maxlength="70" value="{{ old('meta_title', $page->meta_title) }}" class="form-input" placeholder="Kosongkan untuk memakai judul halaman">
          <p class="text-[11px] text-slate-400 mt-1"><span id="metaTitleCount">0</span>/70 karakter — idealnya di bawah 60 supaya tidak terpotong di Google.</p>
          @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="form-label">Meta Description</label>
          <textarea name="meta_description" id="metaDesc" rows="3" maxlength="170" class="form-input" placeholder="Ringkasan singkat isi halaman untuk hasil pencarian">{{ old('meta_description', $page->meta_description) }}</textarea>
          <p class="text-[11px] text-slate-400 mt-1"><span id="metaDescCount">0</span>/170 karakter — idealnya 120–155.</p>
          @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Meta Keywords (opsional)</label>
            <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $page->meta_keywords) }}" class="form-input" placeholder="hosting murah, domain id">
            <p class="text-[11px] text-slate-400 mt-1">Google mengabaikan tag ini sejak lama; isi hanya kalau butuh untuk mesin pencari lain.</p>
          </div>
          <div>
            <label class="form-label">OG Image URL (opsional)</label>
            <input type="text" name="og_image" value="{{ old('og_image', $page->og_image) }}" class="form-input" placeholder="https://...">
            <p class="text-[11px] text-slate-400 mt-1">Gambar saat link dibagikan ke media sosial. Ukuran ideal 1200×630.</p>
          </div>
        </div>

        {{-- Pratinjau hasil pencarian --}}
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
          <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-2">Pratinjau di Google</p>
          <p class="text-[13px] text-emerald-700 truncate">{{ url('/') }}/<span id="previewSlug">{{ $page->slug ?: 'slug-halaman' }}</span></p>
          <p class="text-[18px] text-[#1a0dab] leading-snug truncate" id="previewTitle">{{ $page->seo_title ?: 'Judul Halaman' }}</p>
          <p class="text-[13px] text-slate-600 leading-snug" id="previewDesc">{{ $page->seo_description ?: 'Deskripsi halaman akan muncul di sini.' }}</p>
        </div>
      </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">
      <div class="card p-5 space-y-4">
        <h2 class="text-sm font-semibold text-slate-800">Publikasi</h2>

        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Terbitkan halaman
        </label>

        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="show_in_footer" value="1" @checked(old('show_in_footer', $page->show_in_footer)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Tampilkan link di footer
        </label>

        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="noindex" value="1" @checked(old('noindex', $page->noindex)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Sembunyikan dari mesin pencari (noindex)
        </label>

        <div>
          <label class="form-label">Urutan di Footer</label>
          <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" class="form-input">
        </div>

        <div class="flex flex-col gap-2 pt-2 border-t border-slate-100">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Halaman</button>
          <a href="{{ route('admin.pages') }}" class="btn btn-outline">Batal</a>
        </div>
      </div>
    </div>
  </form>

  <script>
    (function () {
      const title     = document.getElementById('titleInput');
      const slug      = document.getElementById('slugInput');
      const metaTitle = document.getElementById('metaTitle');
      const metaDesc  = document.getElementById('metaDesc');

      const pvSlug  = document.getElementById('previewSlug');
      const pvTitle = document.getElementById('previewTitle');
      const pvDesc  = document.getElementById('previewDesc');
      const ctTitle = document.getElementById('metaTitleCount');
      const ctDesc  = document.getElementById('metaDescCount');

      const slugify = (s) => s.toLowerCase().trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');

      // Slug hanya diisi otomatis kalau halaman baru dan belum disentuh manual,
      // supaya slug halaman lama tidak berubah tanpa sengaja.
      let slugTouched = slug.value.length > 0;
      slug.addEventListener('input', () => { slugTouched = true; });

      function sync() {
        if (!slugTouched) slug.value = slugify(title.value);

        pvSlug.textContent  = slug.value || 'slug-halaman';
        pvTitle.textContent = metaTitle.value || title.value || 'Judul Halaman';
        pvDesc.textContent  = metaDesc.value || 'Deskripsi halaman akan muncul di sini.';
        ctTitle.textContent = metaTitle.value.length;
        ctDesc.textContent  = metaDesc.value.length;
      }

      [title, slug, metaTitle, metaDesc].forEach(el => el.addEventListener('input', sync));
      sync();
    })();
  </script>

@endsection
