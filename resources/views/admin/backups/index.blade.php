@extends('layouts.admin')

@section('title', 'Backup')

@section('content')

  <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Backup</h1>
      <p class="text-sm text-slate-500 mt-1">Cadangan database + seluruh file upload (bukti bayar, dokumen domain, logo), otomatis tiap hari jam 03:00.</p>
    </div>
    <form method="POST" action="{{ route('admin.backups.run') }}"
          data-confirm="Buat cadangan sekarang? Prosesnya bisa memakan waktu beberapa menit tergantung ukuran data." data-confirm-title="Backup Sekarang" data-confirm-style="info" data-confirm-label="Ya, Mulai">
      @csrf
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-database text-xs"></i> Backup Sekarang
      </button>
    </form>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
      <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100">
          <h2 class="text-sm font-semibold text-slate-800">Daftar Cadangan</h2>
        </div>
        <div class="divide-y divide-slate-100">
          @forelse ($backups as $backup)
            <div class="flex items-center justify-between px-5 py-3">
              <div class="flex items-center gap-3 min-w-0">
                <i class="fa-solid fa-file-zipper text-slate-300"></i>
                <div class="min-w-0">
                  <p class="text-sm text-slate-700 truncate">{{ $backup['name'] }}</p>
                  <p class="text-xs text-slate-400">{{ $backup['created_at']->format('d M Y H:i') }} — {{ $backup['size'] }} MB</p>
                </div>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.backups.download', $backup['name']) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Unduh">
                  <i class="fa-solid fa-download text-xs"></i>
                </a>
                <form method="POST" action="{{ route('admin.backups.destroy', $backup['name']) }}"
                      data-confirm="Hapus cadangan {{ $backup['name'] }}? Tidak bisa dibatalkan." data-confirm-title="Hapus Cadangan" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
                  @csrf @method('DELETE')
                  <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                    <i class="fa-regular fa-trash-can text-xs"></i>
                  </button>
                </form>
              </div>
            </div>
          @empty
            <p class="px-5 py-10 text-center text-slate-400 text-sm">Belum ada cadangan. Klik "Backup Sekarang" untuk membuat yang pertama.</p>
          @endforelse
        </div>
      </div>
    </div>

    <div>
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Pengaturan</h2>
        <form method="POST" action="{{ route('admin.backups.settings') }}" class="space-y-4">
          @csrf
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="backup_enabled" value="1" @checked($enabled) class="rounded border-slate-300 text-accent focus:ring-accent/40">
            Backup otomatis tiap hari (03:00)
          </label>
          <div>
            <label class="form-label">Simpan Berapa Cadangan Terakhir</label>
            <input type="number" name="backup_retention" value="{{ $retention }}" min="1" max="60" class="form-input">
            <p class="text-[11px] text-slate-400 mt-1">Cadangan lebih lama dari ini dihapus otomatis, supaya tidak menghabiskan kuota penyimpanan.</p>
          </div>
          <button type="submit" class="btn btn-outline w-full">Simpan Pengaturan</button>
        </form>
      </div>

      <div class="card p-5 mt-5 bg-amber-50/60 border-amber-200">
        <p class="text-xs text-amber-800">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Cadangan ini tersimpan di server yang <b>sama</b> dengan aplikasi. Kalau server bermasalah total
          (bukan cuma aplikasinya), cadangan ini bisa ikut hilang. Untuk perlindungan penuh, unduh cadangan
          secara berkala dan simpan di tempat terpisah (komputer sendiri, Google Drive, dll) — atau aktifkan
          unggah otomatis ke Google Drive di bawah.
        </p>
      </div>

      <div class="card p-5 mt-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">
          <i class="fa-brands fa-google-drive"></i> Unggah Otomatis ke Google Drive
        </h2>

        <form method="POST" action="{{ route('admin.backups.gdrive-settings') }}" class="space-y-3">
          @csrf
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="backup_gdrive_enabled" value="1" @checked($gdrive['enabled']) class="rounded border-slate-300 text-accent focus:ring-accent/40">
            Aktifkan unggah otomatis setelah tiap backup
          </label>
          <div>
            <label class="form-label">Client ID</label>
            <input type="text" name="backup_gdrive_client_id" value="{{ $gdrive['client_id'] }}" class="form-input !text-xs" placeholder="xxxxx.apps.googleusercontent.com">
          </div>
          <div>
            <label class="form-label">Client Secret</label>
            <input type="password" name="backup_gdrive_client_secret" value="{{ $gdrive['client_secret'] }}" class="form-input !text-xs">
          </div>
          <div>
            <label class="form-label">Refresh Token</label>
            <input type="password" name="backup_gdrive_refresh_token" value="{{ $gdrive['refresh_token'] }}" class="form-input !text-xs">
          </div>
          <div>
            <label class="form-label">Nama Folder di Drive</label>
            <input type="text" name="backup_gdrive_folder" value="{{ $gdrive['folder'] }}" class="form-input !text-xs">
          </div>
          <div class="flex gap-2">
            <button type="submit" class="btn btn-outline flex-1">Simpan</button>
          </div>
        </form>

        <form method="POST" action="{{ route('admin.backups.gdrive-test') }}" class="mt-2">
          @csrf
          <button type="submit" class="btn btn-primary w-full !py-2 text-xs">
            <i class="fa-solid fa-plug text-xs"></i> Coba Sambungkan
          </button>
        </form>

        <details class="mt-4 text-xs text-slate-500">
          <summary class="cursor-pointer font-medium text-slate-600">Cara mendapatkan Client ID, Secret, & Refresh Token</summary>
          <ol class="list-decimal list-inside space-y-1.5 mt-2">
            <li>Buka <a href="https://console.cloud.google.com/" target="_blank" class="text-accent hover:underline">Google Cloud Console</a>, buat proyek baru (atau pakai yang sudah ada).</li>
            <li>Aktifkan <b>Google Drive API</b> lewat menu "APIs & Services" → "Enable APIs".</li>
            <li>Buat kredensial: "APIs & Services" → "Credentials" → "Create Credentials" → "OAuth client ID" → pilih tipe <b>"Desktop app"</b>.</li>
            <li>Catat <b>Client ID</b> dan <b>Client Secret</b> yang muncul.</li>
            <li>Buka <a href="https://developers.google.com/oauthplayground/" target="_blank" class="text-accent hover:underline">Google OAuth Playground</a> → klik ikon gerigi (kanan atas) → centang "Use your own OAuth credentials" → isi Client ID & Secret dari langkah 4.</li>
            <li>Di panel kiri, cari & pilih scope <code>https://www.googleapis.com/auth/drive.file</code> → klik "Authorize APIs" → login dengan akun Google tujuan penyimpanan.</li>
            <li>Klik "Exchange authorization code for tokens" → salin nilai <b>Refresh Token</b> yang muncul.</li>
            <li>Buat folder baru di Google Drive-mu untuk menampung backup, catat namanya, isi di kolom "Nama Folder di Drive" di atas.</li>
          </ol>
        </details>
      </div>
    </div>
  </div>

@endsection
