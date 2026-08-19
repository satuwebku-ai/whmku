@extends('layouts.admin-bootstrap')
@section('title', 'Pratinjau Layout Bootstrap')
@section('content')

  <div class="alert alert-primary d-flex align-items-center gap-2 mb-4">
    <i class="fa-solid fa-flask"></i>
    <div>
      <b>Halaman pratinjau</b> — ini layout Bootstrap baru, terpisah dari dashboard asli.
      Kalau sudah cocok, baru kita rencanakan migrasi halaman lain satu per satu.
    </div>
  </div>

  {{-- Kotak diagnosa sementara -- baca angka font-size langsung dari
       halaman ini, tanpa perlu buka Developer Tools. Boleh dihapus
       setelah masalah ukuran teks ketemu. --}}
  <div id="fontDebugBox" class="alert alert-warning mb-4" style="font-family:monospace;font-size:13px;white-space:pre-line"></div>
  <script>
    (function () {
      const targets = [
        ['Menu sidebar (contoh: label "Dashboard")', '#sidebar .nav-item-link .label-text'],
        ['Kotak pencarian tengah', '#topbarSearch'],
        ['Nama aplikasi di sidebar', '#sidebar .brand-text'],
        ['Body / dasar halaman', 'body'],
        ['Isi konten (paragraf biasa)', 'main p'],
      ];
      let out = 'UKURAN FONT SEKARANG:\n';
      targets.forEach(([label, sel]) => {
        const el = document.querySelector(sel);
        if (el) {
          const size = window.getComputedStyle(el).fontSize;
          out += `- ${label}: ${size}\n`;
        } else {
          out += `- ${label}: (elemen tidak ketemu)\n`;
        }
      });
      document.getElementById('fontDebugBox').textContent = out;
    })();
  </script>

  <h1 class="h4 fw-bold mb-4">Contoh Komponen</h1>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card card-stat-blue border rounded-4 p-3">
        <p class="text-muted small mb-1">Klien Aktif</p>
        <p class="h3 fw-bold mb-0">128</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-stat-emerald border rounded-4 p-3">
        <p class="text-muted small mb-1">Hosting Aktif</p>
        <p class="h3 fw-bold mb-0">94</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-stat-amber border rounded-4 p-3">
        <p class="text-muted small mb-1">Invoice Belum Bayar</p>
        <p class="h3 fw-bold mb-0">7</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-stat-rose border rounded-4 p-3">
        <p class="text-muted small mb-1">Tiket Terbuka</p>
        <p class="h3 fw-bold mb-0">3</p>
      </div>
    </div>
  </div>

  <div class="card border rounded-4 p-4 mb-4">
    <h2 class="h6 fw-bold mb-3">Tombol</h2>
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-primary">Primary</button>
      <button class="btn btn-success">Success</button>
      <button class="btn btn-warning">Warning</button>
      <button class="btn btn-danger">Danger</button>
      <button class="btn btn-outline-primary">Outline</button>
    </div>
  </div>

  <div class="card border rounded-4 p-4 mb-5">
    <h2 class="h6 fw-bold mb-3">Badge</h2>
    <span class="badge text-bg-primary me-1">Aktif</span>
    <span class="badge text-bg-success me-1">Lunas</span>
    <span class="badge text-bg-warning me-1">Pending</span>
    <span class="badge text-bg-danger me-1">Suspend</span>
  </div>

  <div class="card border rounded-4 p-4">
    <h2 class="h6 fw-bold mb-3">Dropdown &amp; Modal</h2>
    <div class="dropdown d-inline-block me-2">
      <button class="btn btn-outline-primary" type="button" data-bs-toggle="dropdown">Buka Dropdown</button>
      <div class="dropdown-menu">
        <a href="#" class="dropdown-item">Aksi Satu</a>
        <a href="#" class="dropdown-item">Aksi Dua</a>
      </div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#demoModal">Buka Modal</button>
  </div>

  <div class="modal" id="demoModal">
    <div class="modal-dialog">
      <div class="modal-content rounded-4">
        <div class="modal-header">
          <h5 class="modal-title">Contoh Modal</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">Ini contoh isi modal memakai framework.js buatan sendiri.</div>
        <div class="modal-footer">
          <button class="btn btn-outline-primary" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

@endsection
