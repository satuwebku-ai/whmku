@extends('layouts.admin')

@section('title', 'Cron Jobs')

@section('content')

  @include('admin.settings._nav')

  @php use App\Models\Setting; use App\Models\CronJob; @endphp

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Cron Jobs</h1>
    <p class="text-sm text-slate-500 mt-1">Tugas otomatis yang berjalan di latar belakang: pengingat tagihan, penandaan tunggakan, dan pembersihan data.</p>
  </div>

  {{-- Peringatan kalau cron server belum jalan --}}
  @if ($neverRan)
    <div class="card p-5 mb-5 border-amber-200 bg-amber-50/60">
      <p class="text-sm font-semibold text-amber-800 mb-1">
        <i class="fa-solid fa-triangle-exclamation"></i> Cron server belum berjalan
      </p>
      <p class="text-xs text-amber-700">
        Belum ada satu pun tugas yang pernah dijalankan. Selama cron di server belum dipasang,
        pengingat tagihan dan tugas lain tidak akan jalan — pasang lewat panel di bawah.
      </p>
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      {{-- Daftar tugas --}}
      <form method="POST" action="{{ route('admin.cron.update') }}">
        @csrf

        <div class="card overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
                  <th class="px-4 py-2.5 font-semibold">Tugas</th>
                  <th class="px-3 py-2.5 font-semibold">Jadwal</th>
                  <th class="px-3 py-2.5 font-semibold">Terakhir Jalan</th>
                  <th class="px-3 py-2.5 font-semibold">Status</th>
                  <th class="px-3 py-2.5 font-semibold text-center">Aktif</th>
                  <th class="px-4 py-2.5 font-semibold text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @forelse ($jobs as $job)
                  <tr class="hover:bg-slate-50/60">
                    <td class="px-4 py-3">
                      <p class="font-medium text-slate-700">{{ $job->name }}</p>
                      <p class="text-[11px] text-slate-400 font-mono">{{ $job->command }}</p>
                      @if ($job->description)
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $job->description }}</p>
                      @endif
                    </td>

                    <td class="px-3 py-3">
                      <select name="jobs[{{ $job->id }}][interval_minutes]"
                              class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-xs outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
                        @foreach (CronJob::INTERVALS as $menit => $label)
                          <option value="{{ $menit }}" @selected($job->interval_minutes === $menit)>{{ $label }}</option>
                        @endforeach
                      </select>
                      @if ($job->next_run_at && $job->is_enabled)
                        <p class="text-[10px] text-slate-400 mt-1">
                          Berikutnya {{ $job->next_run_at->diffForHumans() }}
                        </p>
                      @endif
                    </td>

                    <td class="px-3 py-3 text-xs text-slate-500">
                      @if ($job->last_run_at)
                        {{ $job->last_run_at->diffForHumans() }}
                        <span class="block text-[10px] text-slate-400">
                          {{ $job->run_count }}x · {{ $job->last_duration_ms }}ms
                        </span>
                      @else
                        <span class="text-slate-400">Belum pernah</span>
                      @endif
                    </td>

                    <td class="px-3 py-3">
                      <span class="badge badge-{{ $job->status_badge }}">
                        {{ $job->last_status ? ucfirst($job->last_status) : 'Menunggu' }}
                      </span>
                      @if ($job->last_output)
                        <p class="text-[10px] text-slate-400 mt-1 max-w-[180px] truncate" title="{{ $job->last_output }}">
                          {{ $job->last_output }}
                        </p>
                      @endif
                    </td>

                    <td class="px-3 py-3 text-center">
                      <input type="checkbox" name="enabled[]" value="{{ $job->id }}" @checked($job->is_enabled)
                             class="rounded border-slate-300 text-accent focus:ring-accent/40">
                    </td>

                    <td class="px-4 py-3 text-right">
                      <button type="submit" form="run-{{ $job->id }}"
                              class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 inline-flex items-center justify-center text-slate-500"
                              title="Jalankan sekarang">
                        <i class="fa-solid fa-play text-xs"></i>
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada tugas terdaftar.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap bg-slate-50/60">
            <p class="text-xs text-slate-500">Ubah jadwal atau matikan tugas, lalu simpan.</p>
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Jadwal
            </button>
          </div>
        </div>
      </form>

      {{-- Form "jalankan sekarang" diletakkan di luar tabel: HTML tidak
           mengizinkan form bersarang. --}}
      @foreach ($jobs as $job)
        <form id="run-{{ $job->id }}" method="POST" action="{{ route('admin.cron.run', $job) }}" class="hidden">
          @csrf
        </form>
      @endforeach
    </div>

    {{-- Sidebar: pemasangan cron --}}
    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-1">Pasang Cron di Server</h2>
        <p class="text-xs text-slate-500 mb-3">
          Cukup <b>satu baris cron</b> untuk semua tugas di atas. Jadwal tiap tugas diatur dari halaman ini,
          bukan dari baris cron-nya.
        </p>

        <label class="form-label">Perintah</label>
        <div class="flex items-center gap-2 mb-2">
          <input type="text" readonly value="{{ $cronLine }}" id="cronLine"
                 class="form-input text-[11px] font-mono">
          <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('cronLine').value); this.innerHTML='<i class=\'fa-solid fa-check text-xs\'></i>'"
                  class="btn btn-outline shrink-0" title="Salin">
            <i class="fa-regular fa-copy text-xs"></i>
          </button>
        </div>
        <p class="text-[11px] text-slate-400 mb-3">
          Pasang di cPanel → Cron Jobs dengan setelan <b>Once Per Minute</b> (<code>* * * * *</code>).
        </p>

        {{-- Panduan langkah demi langkah, disembunyikan agar tidak
             memenuhi halaman bagi yang sudah paham. --}}
        <details class="rounded-lg border border-slate-200 bg-slate-50/60">
          <summary class="px-3 py-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
            <i class="fa-solid fa-circle-question"></i> Panduan lengkap memasang di cPanel
          </summary>

          <div class="px-3 pb-3 pt-1 text-xs text-slate-600 space-y-2.5">
            <p><b>1.</b> Login ke cPanel, cari menu <b>Cron Jobs</b> (biasanya di bagian Advanced).</p>

            <p><b>2.</b> Di bagian <b>Add New Cron Job</b>, pada dropdown <b>Common Settings</b>
               pilih <b>Once Per Minute (* * * * *)</b>. Kelima kolom di bawahnya akan terisi
               tanda bintang otomatis.</p>

            <p><b>3.</b> Di kolom <b>Command</b>, tempel perintah yang sudah disalin di atas.</p>

            <p><b>4.</b> Klik <b>Add New Cron Job</b>. Selesai.</p>

            <div class="rounded bg-white border border-slate-200 p-2.5">
              <p class="font-semibold text-slate-700 mb-1">Kenapa tiap menit?</p>
              <p class="leading-relaxed">
                Laravel sendiri yang menentukan kapan tiap tugas benar-benar dijalankan —
                cron hanya bertugas "membangunkan" Laravel setiap menit untuk memeriksa
                ada tugas yang jatuh tempo atau tidak. Jadi meski cron jalan tiap menit,
                pengingat tagihan tetap terkirim sekali sehari sesuai jadwal di halaman ini.
              </p>
            </div>

            <div class="rounded bg-white border border-slate-200 p-2.5">
              <p class="font-semibold text-slate-700 mb-1">Kalau muncul email tiap menit</p>
              <p class="leading-relaxed">
                Kosongkan kolom <b>Email</b> di halaman Cron Jobs cPanel, atau pastikan
                perintahnya diakhiri <code>&gt;&gt; /dev/null 2&gt;&amp;1</code> seperti pada
                perintah di atas — itu yang membuat keluarannya tidak dikirim sebagai email.
              </p>
            </div>

            <div class="rounded bg-white border border-slate-200 p-2.5">
              <p class="font-semibold text-slate-700 mb-1">Kalau tetap tidak jalan</p>
              <p class="leading-relaxed">
                Sebagian hosting memakai path PHP berbeda. Coba ganti <code>php</code> di awal
                perintah dengan path lengkap, misalnya <code>/usr/local/bin/php</code> atau
                <code>/opt/alt/php82/usr/bin/php</code>. Path yang benar bisa ditanyakan ke
                penyedia hosting, atau dilihat di cPanel → Select PHP Version.
              </p>
            </div>
          </div>
        </details>
      </div>

      {{-- Integrasi cPanel --}}
      <form method="POST" action="{{ route('admin.cron.settings') }}" class="card p-5 space-y-3">
        @csrf
        <h2 class="text-sm font-semibold text-slate-800">Pasang Otomatis ke cPanel</h2>
        <p class="text-xs text-slate-500">
          Isi kredensial cPanel <b>tempat aplikasi ini di-hosting</b>, lalu cron bisa dipasang
          tanpa membuka cPanel. Berbeda dari kredensial server pelanggan di menu Server.
        </p>

        <div>
          <label class="form-label">Host cPanel</label>
          <input type="text" name="cpanel_host" value="{{ Setting::get('cpanel_host') }}" class="form-input" placeholder="beragam.kreasi.org">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="form-label">Port</label>
            <input type="number" name="cpanel_port" value="{{ Setting::get('cpanel_port', 2083) }}" class="form-input">
          </div>
          <div>
            <label class="form-label">Username</label>
            <input type="text" name="cpanel_user" value="{{ Setting::get('cpanel_user') }}" class="form-input" placeholder="appskumy">
          </div>
        </div>

        <div>
          <label class="form-label">API Token {{ Setting::get('cpanel_token') ? '(kosongkan jika tidak diganti)' : '' }}</label>
          <input type="password" name="cpanel_token" class="form-input" placeholder="{{ Setting::get('cpanel_token') ? '••••••••••••' : 'Token dari cPanel → Manage API Tokens' }}">
        </div>

        <div>
          <label class="form-label">Path PHP <span class="text-slate-400 font-normal">(opsional)</span></label>
          <input type="text" name="cpanel_php_path" value="{{ Setting::get('cpanel_php_path') }}" class="form-input" placeholder="/usr/local/bin/php">
          <p class="text-[11px] text-slate-400 mt-1">Isi kalau versi PHP default server berbeda dengan yang dipakai aplikasi.</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="cpanel_verify_ssl" value="1" @checked(Setting::get('cpanel_verify_ssl', '1') === '1')
                 class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Verifikasi SSL
        </label>

        <div class="pt-3 border-t border-slate-100 space-y-2">
          <button type="submit" class="w-full btn btn-primary"><i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Kredensial</button>
          <button type="submit" formaction="{{ route('admin.cron.test-cpanel') }}" class="w-full btn btn-outline">
            <i class="fa-solid fa-plug text-xs"></i> Tes Koneksi
          </button>
          <button type="submit" formaction="{{ route('admin.cron.install-cpanel') }}" class="w-full btn btn-outline"
                  {{ $cpanelConfigured ? '' : 'disabled' }}>
            <i class="fa-solid fa-download text-xs"></i> Pasang Cron Sekarang
          </button>
        </div>

        @unless ($cpanelConfigured)
          <p class="text-[11px] text-amber-700">Simpan kredensial dulu sebelum memasang cron otomatis.</p>
        @endunless
      </form>

      {{-- Auto suspend --}}
      <form method="POST" action="{{ route('admin.cron.settings') }}" class="card p-5 space-y-3">
        @csrf
        <h2 class="text-sm font-semibold text-slate-800">Suspend Otomatis</h2>

        <label class="flex items-start gap-2 text-sm text-slate-600">
          <input type="checkbox" name="auto_suspend" value="1" @checked(Setting::get('auto_suspend', '0') === '1')
                 class="mt-0.5 rounded border-slate-300 text-accent focus:ring-accent/40">
          <span>
            <span class="block font-medium text-slate-700">Aktifkan suspend otomatis</span>
            <span class="block text-xs text-slate-500">Layanan disuspend kalau tagihan menunggak melewati batas.</span>
          </span>
        </label>

        <div>
          <label class="form-label">Toleransi (hari setelah jatuh tempo)</label>
          <input type="number" name="suspend_grace_days" value="{{ Setting::get('suspend_grace_days', 7) }}" min="1" max="90" class="form-input">
        </div>

        <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5 text-[11px] text-amber-800">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Suspend bisa dibatalkan, tapi tetap membuat website pelanggan mati.
          Pastikan pengingat tagihan sudah berjalan lebih dulu sebelum mengaktifkan ini.
        </div>

        <button type="submit" class="w-full btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      </form>
    </div>
  </div>

@endsection
