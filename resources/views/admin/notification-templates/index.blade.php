@extends('layouts.admin')

@section('title', 'Template Notifikasi')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Template Notifikasi</h1>
    <p class="text-sm text-slate-500 mt-1">
      Atur kata-kata di setiap email & pesan WhatsApp otomatis. Klik salah satu untuk mengedit —
      selama belum pernah diedit, sistem memakai kata-kata bawaan.
    </p>
  </div>

  <div class="card overflow-hidden">
    <div class="divide-y divide-slate-100">
      @foreach ($templates as $tpl)
        <a href="{{ route('admin.notification-templates.edit', $tpl['key']) }}"
           class="flex items-center justify-between px-5 py-4 hover:bg-slate-50/60 transition-colors">
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-800">{{ $tpl['label'] }}</p>
            @if (! empty($tpl['note']))
              <p class="text-xs text-slate-400 mt-0.5">{{ $tpl['note'] }}</p>
            @endif
          </div>
          <div class="flex items-center gap-3 shrink-0 ml-3">
            @if ($tpl['is_customized'])
              <span class="badge badge-active">Sudah Diedit</span>
            @else
              <span class="badge badge-inactive">Bawaan</span>
            @endif
            <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
          </div>
        </a>
      @endforeach
    </div>
  </div>

@endsection
