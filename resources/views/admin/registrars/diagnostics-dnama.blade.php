@extends('layouts.admin')

@section('title', 'Diagnosa Registrar — ' . $registrar->name)

@section('content')

  <a href="{{ route('admin.registrars.index') }}" class="text-decoration-none text-muted" style="font-size:12px">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Registrar
  </a>
  <h1 class="h4 fw-bold text-dark mt-1 mb-2">Diagnosa — {{ $registrar->name }}</h1>
  <p class="small text-muted mb-4">
    Data langsung dari API DNAMA — cuma membaca, tidak mengubah apa pun di akunmu.
  </p>

  @if (! empty($apiErrors))
    @foreach ($apiErrors as $err)
      <div class="card border rounded-3 p-3 mb-3" style="border-color:#fecaca!important;background:#fef2f2">
        <p class="mb-0" style="font-size:14px;color:#991b1b"><i class="fa-solid fa-circle-exclamation"></i> {{ $err }}</p>
      </div>
    @endforeach
  @endif

  <div class="row g-3">

    {{-- DNAMA selalu IDR, tapi kartu ini dipertahankan sama seperti
         provider lain supaya kalau ada perubahan di masa depan tetap
         konsisten polanya. --}}
    <div class="col-12 col-lg-4">
      <div class="card border rounded-4 p-4 h-100" style="{{ $details && $details['selling_currency'] ? 'border-color:#a7f3d0!important;background:rgba(16,185,129,.04)' : '' }}">
        <h2 class="small fw-bold text-dark mb-3">Mata Uang Akun</h2>
        @if ($details && $details['selling_currency'])
          <p class="fw-bold text-dark mb-2" style="font-size:1.75rem">{{ $details['selling_currency'] }}</p>
          <p class="text-muted mb-0" style="font-size:12px">
            Semua angka harga &amp; saldo dari API DNAMA dalam satuan <b class="text-dark">{{ $details['selling_currency'] }}</b> --
            tidak perlu dikonversi, langsung Rupiah.
          </p>
        @else
          <p class="fw-bold text-dark mb-2" style="font-size:1.75rem">IDR</p>
          <p class="text-muted mb-0" style="font-size:12px">DNAMA selalu memakai Rupiah untuk semua transaksinya.</p>
        @endif
      </div>
    </div>

    {{-- Saldo --}}
    <div class="col-12 col-lg-4">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-3">Saldo Deposit</h2>
        @if ($balance)
          <p class="fw-bold mb-2 {{ $balance['balance'] < 50000 ? 'text-danger' : 'text-dark' }}" style="font-size:1.75rem">
            Rp {{ number_format($balance['balance'], 0, ',', '.') }}
          </p>
          @if ($balance['balance'] < 50000)
            <p class="text-danger mb-0" style="font-size:12px">
              <i class="fa-solid fa-triangle-exclamation"></i>
              Saldo tipis — pastikan deposit cukup sebelum ada klien yang membeli,
              karena registrasi domain dipotong langsung dari saldo ini.
            </p>
          @endif
        @else
          <p class="text-muted mb-0" style="font-size:14px">Tidak bisa diambil — lihat pesan galat di atas.</p>
        @endif
      </div>
    </div>
  </div>

  {{-- Statistik & contoh harga mentah -- lebih ditekankan di sini
       dibanding file generic, karena DNAMA punya kuirk khusus: satu
       ekstensi bisa muncul BERKALI-KALI (varian premium 2/3/4-karakter
       dengan harga sampai ratusan juta), jadi statistik ini penting
       untuk memastikan filternya bekerja benar. --}}
  <div class="card border rounded-4 p-4 mt-3">
    <h2 class="small fw-bold text-dark mb-1">Statistik & Contoh Harga (data mentah)</h2>
    <p class="text-muted mb-3" style="font-size:12px">
      DNAMA mengirim baris TERPISAH untuk varian premium dari ekstensi yang sama
      (mis. ".id" biasa Rp 215rb DAN ".id" premium 2-karakter Rp 585 juta, sama-sama
      bertuliskan "tld": ".id"). Baris premium otomatis dilewati saat sinkronisasi --
      angka di bawah ini menunjukkan persis berapa yang dilewati dan berapa yang benar-benar disinkron.
    </p>

    @if (isset($priceStats))
      <div class="row g-2 mb-3">
        <div class="col-4">
          <div class="rounded-3 border p-2 text-center">
            <p class="fw-bold text-dark mb-0">{{ $priceStats['total'] }}</p>
            <p class="text-muted mb-0" style="font-size:10px">Total baris dari API</p>
          </div>
        </div>
        <div class="col-4">
          <div class="rounded-3 border p-2 text-center" style="background:rgba(180,83,9,.06)">
            <p class="fw-bold mb-0" style="color:#b45309">{{ $priceStats['premium'] }}</p>
            <p class="text-muted mb-0" style="font-size:10px">Baris premium (dilewati)</p>
          </div>
        </div>
        <div class="col-4">
          <div class="rounded-3 border p-2 text-center" style="background:rgba(16,185,129,.06)">
            <p class="fw-bold mb-0" style="color:#047857">{{ $priceStats['unique'] }}</p>
            <p class="text-muted mb-0" style="font-size:10px">Ekstensi unik (yang disinkron)</p>
          </div>
        </div>
      </div>
      <p class="text-muted mb-3" style="font-size:11px">
        Angka "Ekstensi unik" inilah jumlah TLD yang akan masuk saat Sinkron TLD —
        kalau jauh lebih sedikit dari harapan, berarti akun reseller-mu di DNAMA
        memang baru diberi akses sebanyak itu (bukan masalah di sistem ini).
      </p>
    @endif

    <pre class="rounded-3 p-3 mb-0" style="background:#1e293b;color:#f1f5f9;font-size:12px;overflow-x:auto">{{ json_encode($priceSample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
  </div>

  {{-- DNAMA TIDAK punya endpoint daftar customer seperti Liqu.id --
       bagian itu SENGAJA tidak ada di file ini, bukan ketinggalan. --}}
  <div class="rounded-3 p-3 mt-3" style="background:#f8fafc;border:1px dashed #cbd5e1">
    <p class="text-muted mb-0" style="font-size:11px">
      <i class="fa-solid fa-circle-info"></i>
      DNAMA tidak punya endpoint daftar customer seperti Liqu.id, jadi bagian itu tidak ditampilkan di sini.
    </p>
  </div>

@endsection
