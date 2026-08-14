@extends('layouts.admin')

@section('title', 'Keamanan')

@section('content')

  @include('admin.settings._nav')

  @php use App\Models\Setting; @endphp

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Keamanan</h1>
    <p class="text-sm text-slate-500 mt-1">Verifikasi robot dan verifikasi email pendaftar.</p>
  </div>

  <form method="POST" action="{{ route('admin.settings.security.update') }}" class="space-y-5 max-w-3xl">
    @csrf

    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-1">Verifikasi Robot (CAPTCHA)</h2>
      <p class="text-xs text-slate-500 mb-4">Melindungi form login dan pendaftaran dari bot penebak password.</p>

      <div class="space-y-2 mb-5">
        @foreach ([
          'adaptive' => ['Adaptif (disarankan)', 'Muncul hanya setelah 3 kali gagal dari IP yang sama. Pengguna normal tidak terganggu, bot tetap tersaring.'],
          'always'   => ['Selalu tampil', 'CAPTCHA di setiap login dan pendaftaran. Paling aman, tapi menambah langkah bagi semua orang.'],
          'off'      => ['Nonaktif', 'Tidak disarankan — form jadi terbuka untuk percobaan otomatis.'],
        ] as $key => [$judul, $ket])
          <label class="flex items-start gap-3 rounded-lg border px-4 py-3 cursor-pointer
                        {{ Setting::get('captcha_mode', 'adaptive') === $key ? 'border-accent bg-accent/5' : 'border-slate-100 hover:border-slate-200' }}">
            <input type="radio" name="captcha_mode" value="{{ $key }}" @checked(Setting::get('captcha_mode', 'adaptive') === $key)
                   class="mt-0.5 text-accent focus:ring-accent/40">
            <span>
              <span class="block text-sm font-medium text-slate-700">{{ $judul }}</span>
              <span class="block text-xs text-slate-500">{{ $ket }}</span>
            </span>
          </label>
        @endforeach
      </div>

      <div class="pt-4 border-t border-slate-100">
        <div class="flex items-center gap-2 mb-1">
          <h3 class="text-sm font-medium text-slate-700">Google reCAPTCHA v2 <span class="text-slate-400 font-normal">(opsional)</span></h3>
          @php
            $recaptchaStatus = Setting::get('recaptcha_last_test_status');
            $recaptchaTestedAt = Setting::get('recaptcha_last_test_at');
          @endphp
          @if ($recaptchaStatus === 'success')
            <span class="badge badge-active" title="Diuji {{ \Carbon\Carbon::parse($recaptchaTestedAt)->diffForHumans() }}">
              <i class="fa-solid fa-check text-[10px]"></i> Success
            </span>
          @elseif ($recaptchaStatus === 'failed')
            <span class="badge badge-inactive !bg-rose-100 !text-rose-700" title="Diuji {{ \Carbon\Carbon::parse($recaptchaTestedAt)->diffForHumans() }}">
              <i class="fa-solid fa-xmark text-[10px]"></i> Ditolak
            </span>
          @endif
        </div>
        <p class="text-xs text-slate-500 mb-3">
          Kalau kunci di bawah diisi, sistem memakai kotak centang "Saya bukan robot" dari Google.
          Kalau dikosongkan, dipakai pertanyaan hitungan sederhana bawaan — tetap efektif dan tidak
          bergantung layanan luar.
          Dapatkan kunci di <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener" class="text-accent hover:underline">google.com/recaptcha/admin</a>
          (pilih tipe <b>reCAPTCHA v2 → Checkbox</b>).
        </p>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Site Key</label>
            <input type="text" name="recaptcha_site_key" value="{{ Setting::get('recaptcha_site_key') }}" class="form-input">
          </div>
          <div>
            <label class="form-label">Secret Key {{ Setting::get('recaptcha_secret_key') ? '(kosongkan jika tidak diganti)' : '' }}</label>
            <input type="password" name="recaptcha_secret_key" class="form-input"
                   placeholder="{{ Setting::get('recaptcha_secret_key') ? '••••••••••••' : '' }}">
          </div>
        </div>

        <button type="submit" formaction="{{ route('admin.settings.security.test-recaptcha') }}" formnovalidate
                class="btn btn-outline !py-1.5 !px-3 text-xs mt-3">
          <i class="fa-solid fa-plug text-xs"></i> Coba Sambungkan
        </button>
        <p class="text-[11px] text-slate-400 mt-1">
          Simpan dulu Secret Key-nya (tombol Simpan di bawah), baru klik ini untuk menguji ke API Google.
        </p>
      </div>
    </div>

    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-1">Verifikasi Email Pendaftar</h2>
      <p class="text-xs text-slate-500 mb-4">Memastikan email yang didaftarkan benar-benar milik pendaftar.</p>

      <label class="flex items-start gap-3 rounded-lg border border-slate-100 px-4 py-3">
        <input type="checkbox" name="require_email_verification" value="1"
               @checked(Setting::get('require_email_verification', '1') === '1')
               class="mt-0.5 rounded border-slate-300 text-accent focus:ring-accent/40">
        <span>
          <span class="block text-sm font-medium text-slate-700">Wajib verifikasi email sebelum bisa masuk</span>
          <span class="block text-xs text-slate-500">
            Setelah mendaftar, klien menerima kode 6 digit dan harus memasukkannya sebelum akun bisa dipakai.
          </span>
        </span>
      </label>

      <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Fitur ini bergantung penuh pada SMTP. Pastikan email server sudah berfungsi
        (<code>php artisan lumora:test-mail</code>) — kalau tidak, tidak ada klien baru yang bisa masuk.
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Pengaturan</button>
  </form>

@endsection
