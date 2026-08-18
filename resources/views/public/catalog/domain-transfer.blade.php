@extends('public.layout')
@section('title', 'Transfer Domain')

@section('content')
  <div class="max-w-2xl mx-auto">
    <div class="text-center mb-8">
      <h1 class="text-2xl font-bold text-slate-800">Transfer Domain ke Sini</h1>
      <p class="text-sm text-slate-500 mt-2">
        Pindahkan domain Anda dari registrar lain. Masa aktif domain <b>bertambah 1 tahun</b> setelah transfer selesai.
      </p>
    </div>

    <div class="card p-6 mb-6">
      <form method="POST" action="{{ route('domains.transfer.submit') }}" class="space-y-4">
        @csrf

        <div>
          <label class="form-label">Nama Domain</label>
          <input type="text" name="domain_name" value="{{ old('domain_name') }}"
                 placeholder="contohdomain.com" class="form-input font-mono" required>
          @error('domain_name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="form-label">Kode EPP / Auth Code</label>
          <input type="text" name="auth_code" value="{{ old('auth_code') }}"
                 placeholder="Kode dari registrar lama Anda" class="form-input font-mono" required>
          @error('auth_code') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">
            Minta kode ini ke registrar tempat domain Anda terdaftar sekarang.
          </p>
        </div>

        <button type="submit" class="btn btn-primary w-full">
          <i class="fa-solid fa-right-left text-xs"></i> Lanjutkan Transfer
        </button>
      </form>
    </div>

    {{-- Syarat transfer -- sengaja ditulis di depan supaya klien tidak
         terlanjur bayar lalu transfernya ditolak registry karena syarat
         yang sebenarnya bisa dicek sendiri sebelum memesan. --}}
    <div class="card p-5 mb-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-3">Sebelum Transfer, Pastikan:</h2>
      <ul class="space-y-2 text-sm text-slate-600">
        <li class="flex gap-2">
          <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 shrink-0 text-xs"></i>
          <span>Domain sudah <b>berumur minimal 60 hari</b> sejak didaftarkan atau sejak transfer terakhir.</span>
        </li>
        <li class="flex gap-2">
          <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 shrink-0 text-xs"></i>
          <span><b>Registrar Lock dimatikan</b> di registrar lama Anda.</span>
        </li>
        <li class="flex gap-2">
          <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 shrink-0 text-xs"></i>
          <span><b>ID Protection/WHOIS Privacy dimatikan sementara</b>, supaya email konfirmasi bisa sampai ke Anda.</span>
        </li>
        <li class="flex gap-2">
          <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 shrink-0 text-xs"></i>
          <span>Email pemilik domain di WHOIS <b>masih aktif dan bisa Anda akses</b> — konfirmasi transfer dikirim ke sana.</span>
        </li>
      </ul>
      <p class="text-xs text-slate-400 mt-3">
        Proses transfer biasanya memakan waktu 5–7 hari, tergantung kecepatan persetujuan dari registrar lama.
      </p>
    </div>

    @if ($tlds->isNotEmpty())
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Biaya Transfer</h2>
        <div class="grid sm:grid-cols-2 gap-2">
          @foreach ($tlds as $tld)
            <div class="flex items-center justify-between text-sm py-1.5 px-3 rounded-lg bg-slate-50">
              <span class="font-mono text-slate-600">.{{ ltrim($tld->extension, '.') }}</span>
              <span class="font-semibold text-slate-800">Rp {{ number_format($tld->transfer_price, 0, ',', '.') }}</span>
            </div>
          @endforeach
        </div>
        <p class="text-xs text-slate-400 mt-3">Biaya transfer sudah termasuk perpanjangan 1 tahun.</p>
      </div>
    @endif
  </div>
@endsection
