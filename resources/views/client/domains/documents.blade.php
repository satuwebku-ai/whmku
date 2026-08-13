@extends('client.layout')
@section('title', 'Dokumen Persyaratan — ' . $domain->domain_name)

@section('content')
  <a href="{{ route('client.domains.show', $domain) }}" class="text-xs text-slate-400 hover:text-slate-600">
    &larr; Kembali ke {{ $domain->domain_name }}
  </a>

  <div class="mt-2 mb-5">
    <h1 class="text-xl font-bold text-slate-800">Dokumen Persyaratan — {{ $domain->domain_name }}</h1>
    <p class="text-sm text-slate-500 mt-1">
      Domain <b>.{{ $tldExt }}</b> mewajibkan dokumen tambahan sesuai ketentuan PANDI sebelum bisa diaktifkan.
    </p>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
      @if ($requirements)
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-1">Dokumen yang Diperlukan</h2>
          <p class="text-xs text-slate-400 mb-3">{{ $requirements['label'] }}</p>
          <ul class="text-sm text-slate-600 space-y-1.5">
            @foreach ($requirements['items'] as $item)
              <li class="flex items-start gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500 text-xs mt-1"></i>
                <span>{{ $item }}</span>
              </li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100">
          <h2 class="text-sm font-semibold text-slate-800">Dokumen Terunggah</h2>
        </div>
        <div class="divide-y divide-slate-100">
          @forelse ($documents as $doc)
            <div class="flex items-center justify-between px-5 py-3">
              <div class="min-w-0 flex items-center gap-3">
                <i class="fa-solid fa-file-lines text-slate-300"></i>
                <div class="min-w-0">
                  <a href="{{ route('client.domains.documents.file', $doc) }}" target="_blank" class="text-sm text-slate-700 hover:text-accent truncate block">{{ $doc->original_name }}</a>
                  <p class="text-xs text-slate-400">{{ $doc->created_at->format('d M Y H:i') }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                @if ($doc->status === 'approved')
                  <span class="badge badge-active">Disetujui</span>
                @elseif ($doc->status === 'rejected')
                  <span class="badge badge-inactive" title="{{ $doc->admin_note }}">Ditolak</span>
                @else
                  <span class="badge badge-pending">Menunggu</span>
                @endif

                @if ($doc->status !== 'approved')
                  <form method="POST" action="{{ route('client.domains.documents.delete', $doc) }}"
                        data-confirm="Hapus dokumen {{ $doc->original_name }}?" data-confirm-title="Hapus Dokumen" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-7 h-7 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                @endif
              </div>
            </div>
            @if ($doc->status === 'rejected' && $doc->admin_note)
              <div class="px-5 pb-3 -mt-1">
                <p class="text-xs text-rose-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $doc->admin_note }}</p>
              </div>
            @endif
          @empty
            <p class="px-5 py-10 text-center text-slate-400 text-sm">Belum ada dokumen diunggah.</p>
          @endforelse
        </div>
      </div>
    </div>

    <div>
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Unggah Dokumen</h2>
        <form method="POST" action="{{ route('client.domains.documents.upload', $domain) }}" enctype="multipart/form-data" class="space-y-3">
          @csrf
          <input type="file" name="file" accept=".zip,.rar,.jpg,.jpeg,.png" class="form-input" required>
          @error('file') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400">Format: ZIP, RAR, JPG, JPEG, PNG. Maksimal 1 MB per file.</p>
          <button type="submit" class="btn btn-primary w-full">
            <i class="fa-solid fa-upload text-xs"></i> Unggah
          </button>
        </form>
        <p class="text-[11px] text-slate-400 mt-4 pt-4 border-t border-slate-100">
          Proses verifikasi biasanya memakan waktu 2x24 jam kerja setelah dokumen lengkap kami terima.
          Domain akan otomatis lanjut diproses begitu semua dokumen disetujui.
        </p>
      </div>
    </div>
  </div>
@endsection
