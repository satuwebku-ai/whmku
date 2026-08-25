@extends('layouts.admin-bootstrap')

@section('title', 'Template Notifikasi')

@section('content')

  <div class="mb-4">
    <h1 class="h4 fw-bold text-dark mb-1">Template Notifikasi</h1>
    <p class="small text-muted mb-0">
      Atur kata-kata di setiap email &amp; pesan WhatsApp otomatis. Klik salah satu untuk mengedit —
      selama belum pernah diedit, sistem memakai kata-kata bawaan.
    </p>
  </div>

  <div class="card border rounded-4 overflow-hidden">
    <div>
      @foreach ($templates as $tpl)
        <a href="{{ route('admin.notification-templates.edit', $tpl['key']) }}"
           class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom text-decoration-none">
          <div class="min-w-0">
            <p class="fw-medium text-dark mb-0" style="font-size:14px">{{ $tpl['label'] }}</p>
            @if (! empty($tpl['note']))
              <p class="text-muted mb-0 mt-1" style="font-size:11px">{{ $tpl['note'] }}</p>
            @endif
          </div>
          <div class="d-flex align-items-center gap-3 flex-shrink-0 ms-3">
            @if ($tpl['is_customized'])
              <span class="badge badge-soft-success">Sudah Diedit</span>
            @else
              <span class="badge badge-soft-secondary">Bawaan</span>
            @endif
            <i class="fa-solid fa-chevron-right text-muted" style="font-size:11px"></i>
          </div>
        </a>
      @endforeach
    </div>
  </div>

@endsection
