@extends('client.layout-bootstrap')
@section('title', 'Dokumen Persyaratan — ' . $domain->domain_name)

@section('content')
  <a href="{{ route('client.domains.show.bootstrap-preview', $domain) }}" class="text-decoration-none text-muted" style="font-size:12px">
    &larr; Kembali ke {{ $domain->domain_name }}
  </a>

  <div class="mt-2 mb-4">
    <h1 class="h4 fw-bold text-dark mb-1">Dokumen Persyaratan — {{ $domain->domain_name }}</h1>
    <p class="text-muted mb-0">
      Domain <b>.{{ $tldExt }}</b> mewajibkan dokumen tambahan sesuai ketentuan PANDI sebelum bisa diaktifkan.
    </p>
  </div>

  <div class="row g-4">
    <div class="col-12 col-lg-8 d-flex flex-column gap-4">
      @if ($requirements)
        <div class="card-public p-4">
          <h2 class="fw-semibold text-dark mb-1" style="font-size:14px">Dokumen yang Diperlukan</h2>
          <p class="text-muted mb-3" style="font-size:11px">{{ $requirements['label'] }}</p>
          <ul class="text-muted mb-0 ps-0" style="font-size:14px;list-style:none">
            @foreach ($requirements['items'] as $item)
              <li class="d-flex align-items-start gap-2 mb-2">
                <i class="fa-solid fa-circle-check text-success" style="font-size:11px;margin-top:3px"></i>
                <span>{{ $item }}</span>
              </li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="card-public overflow-hidden">
        <div class="px-4 py-3 border-bottom">
          <h2 class="small fw-bold text-dark mb-0">Dokumen Terunggah</h2>
        </div>
        <div>
          @forelse ($documents as $doc)
            <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
              <div class="min-w-0 d-flex align-items-center gap-3">
                <i class="fa-solid fa-file-lines text-muted"></i>
                <div class="min-w-0">
                  <a href="{{ route('client.domains.documents.file', $doc) }}" target="_blank" class="text-decoration-none text-dark text-truncate d-block" style="font-size:14px">{{ $doc->original_name }}</a>
                  <p class="text-muted mb-0" style="font-size:11px">{{ $doc->created_at->format('d M Y H:i') }}</p>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-shrink-0">
                @if ($doc->status === 'approved')
                  <span class="badge badge-soft-success">Disetujui</span>
                @elseif ($doc->status === 'rejected')
                  <span class="badge badge-soft-secondary" title="{{ $doc->admin_note }}">Ditolak</span>
                @else
                  <span class="badge badge-soft-warning">Menunggu</span>
                @endif

                @if ($doc->status !== 'approved')
                  <form method="POST" action="{{ route('client.domains.documents.delete', $doc) }}"
                        data-confirm="Hapus dokumen {{ $doc->original_name }}?" data-confirm-title="Hapus Dokumen" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;padding:0">
                      <i class="fa-regular fa-trash-can" style="font-size:11px"></i>
                    </button>
                  </form>
                @endif
              </div>
            </div>
            @if ($doc->status === 'rejected' && $doc->admin_note)
              <div class="px-4 pb-3" style="margin-top:-.25rem">
                <p class="text-danger mb-0" style="font-size:11px"><i class="fa-solid fa-circle-exclamation"></i> {{ $doc->admin_note }}</p>
              </div>
            @endif
          @empty
            <p class="text-center text-muted py-5 mb-0" style="font-size:14px">Belum ada dokumen diunggah.</p>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card-public p-4">
        <h2 class="small fw-bold text-dark mb-3">Unggah Dokumen</h2>
        <form method="POST" action="{{ route('client.domains.documents.upload', $domain) }}" enctype="multipart/form-data" class="d-flex flex-column gap-3">
          @csrf
          <input type="file" name="file" accept=".zip,.rar,.jpg,.jpeg,.png" class="form-control form-control-sm" required>
          @error('file') <p class="text-danger mb-0" style="font-size:12px">{{ $message }}</p> @enderror
          <p class="text-muted mb-0" style="font-size:11px">Format: ZIP, RAR, JPG, JPEG, PNG. Maksimal 1 MB per file.</p>
          <button type="submit" class="btn btn-theme w-100">
            <i class="fa-solid fa-upload" style="font-size:11px"></i> Unggah
          </button>
        </form>
        <p class="text-muted mt-4 pt-4 border-top mb-0" style="font-size:11px">
          Proses verifikasi biasanya memakan waktu 2x24 jam kerja setelah dokumen lengkap kami terima.
          Domain akan otomatis lanjut diproses begitu semua dokumen disetujui.
        </p>
      </div>
    </div>
  </div>
@endsection
