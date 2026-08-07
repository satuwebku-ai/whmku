@extends('layouts.admin')

@section('title', $menu->exists ? 'Edit Menu' : 'Tambah Menu')

@section('content')

  <div class="mb-6">
    <a href="{{ route('admin.nav-menus') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Menu Navigasi</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $menu->exists ? 'Edit Menu' : 'Tambah Menu' }}</h1>
  </div>

  <form method="POST" action="{{ $menu->exists ? route('admin.nav-menu.update', $menu) : route('admin.nav-menu.add') }}" class="card p-6 max-w-xl space-y-4">
    @csrf

    <div>
      <label class="form-label">Nama Menu</label>
      <input type="text" name="label" value="{{ old('label', $menu->label) }}" class="form-input" required placeholder="Tentang Kami">
      @error('label') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1">Teks yang tampil di navigasi.</p>
    </div>

    <div>
      <label class="form-label">Tautan Menuju</label>
      <div class="grid grid-cols-3 gap-2">
        @foreach (['route' => 'Halaman Bawaan', 'page' => 'Halaman Saya', 'url' => 'Tautan Bebas'] as $key => $label)
          <label class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-medium cursor-pointer text-center
                        {{ old('type', $menu->type) === $key ? 'border-accent bg-accent/5 text-accent' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
            <input type="radio" name="type" value="{{ $key }}" @checked(old('type', $menu->type) === $key) class="hidden" data-type-radio>
            {{ $label }}
          </label>
        @endforeach
      </div>
      @error('type') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div data-type-field="route" class="{{ old('type', $menu->type) === 'route' ? '' : 'hidden' }}">
      <label class="form-label">Pilih Halaman Bawaan</label>
      <select name="route_name" class="form-input">
        <option value="">— Pilih —</option>
        @foreach (\App\Models\NavMenu::BUILTIN_ROUTES as $key => $label)
          <option value="{{ $key }}" @selected(old('route_name', $menu->route_name) === $key)>{{ $label }}</option>
        @endforeach
      </select>
      @error('route_name') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div data-type-field="page" class="{{ old('type', $menu->type) === 'page' ? '' : 'hidden' }}">
      <label class="form-label">Pilih Halaman</label>
      <select name="page_id" class="form-input">
        <option value="">— Pilih —</option>
        @forelse ($pages as $page)
          <option value="{{ $page->id }}" @selected((int) old('page_id', $menu->page_id) === $page->id)>{{ $page->title }}</option>
        @empty
          <option value="" disabled>Belum ada halaman yang diterbitkan</option>
        @endforelse
      </select>
      @error('page_id') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1">
        Hanya halaman berstatus Terbit yang muncul di sini.
        <a href="{{ route('admin.page.add.page') }}" class="text-accent hover:underline">Buat halaman baru →</a>
      </p>
    </div>

    <div data-type-field="url" class="{{ old('type', $menu->type) === 'url' ? '' : 'hidden' }}">
      <label class="form-label">Alamat Tautan</label>
      <input type="text" name="url" value="{{ old('url', $menu->url) }}" class="form-input" placeholder="https://wa.me/6281234567890">
      @error('url') <p class="form-error">{{ $message }}</p> @enderror
      <p class="text-[11px] text-slate-400 mt-1">Bisa ke luar situs, mis. WhatsApp atau media sosial.</p>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-600">
      <input type="checkbox" name="open_in_new_tab" value="1" @checked(old('open_in_new_tab', $menu->open_in_new_tab)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
      Buka di tab baru
    </label>

    <label class="flex items-center gap-2 text-sm text-slate-600">
      <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $menu->is_active ?? true)) class="rounded border-slate-300 text-accent focus:ring-accent/40">
      Tampilkan di navigasi
    </label>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.nav-menus') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

  <script>
    // Tampilkan hanya kolom yang relevan dengan jenis tautan terpilih.
    (function () {
      const radios = document.querySelectorAll('[data-type-radio]');
      const fields = document.querySelectorAll('[data-type-field]');

      function sync() {
        const active = document.querySelector('[data-type-radio]:checked')?.value;

        fields.forEach(function (el) {
          el.classList.toggle('hidden', el.dataset.typeField !== active);
        });

        radios.forEach(function (r) {
          r.closest('label').classList.toggle('border-accent', r.checked);
          r.closest('label').classList.toggle('bg-accent/5', r.checked);
          r.closest('label').classList.toggle('text-accent', r.checked);
        });
      }

      radios.forEach(r => r.addEventListener('change', sync));
      sync();
    })();
  </script>

@endsection
