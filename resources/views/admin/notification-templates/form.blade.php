@extends('layouts.admin')

@section('title', 'Edit Template — ' . $meta['label'])

@section('content')

  <a href="{{ route('admin.notification-templates.index') }}" class="text-xs text-slate-400 hover:text-slate-600">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Template Notifikasi
  </a>

  <div class="flex items-center justify-between mt-2 mb-6 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">{{ $meta['label'] }}</h1>
      @if (! empty($meta['note']))
        <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-2 max-w-xl">
          <i class="fa-solid fa-circle-info"></i> {{ $meta['note'] }}
        </p>
      @endif
    </div>
    @if ($isCustomized)
      <form method="POST" action="{{ route('admin.notification-templates.reset', $key) }}"
            data-confirm="Kembalikan ke kata-kata bawaan? Perubahan yang sudah kamu buat akan hilang." data-confirm-title="Reset Template" data-confirm-style="danger" data-confirm-label="Ya, Reset">
        @csrf
        <button type="submit" class="btn btn-outline !text-rose-600 !border-rose-200">
          <i class="fa-solid fa-rotate-left text-xs"></i> Reset ke Bawaan
        </button>
      </form>
    @endif
    <form method="POST" action="{{ route('admin.notification-templates.preview.draft', $key) }}" target="_blank" id="previewForm">
      @csrf
      <input type="hidden" name="subject" id="previewSubject">
      <input type="hidden" name="body_mail" id="previewBodyMail">
      <input type="hidden" name="body_whatsapp" id="previewBodyWhatsapp">
      <button type="submit" class="btn btn-outline">
        <i class="fa-solid fa-eye text-xs"></i> Lihat Pratinjau
      </button>
    </form>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
      <form method="POST" action="{{ route('admin.notification-templates.update', $key) }}" class="space-y-5" id="editForm">
        @csrf

        @if (! is_null($meta['subject']))
          <div class="card p-5">
            <label class="form-label">Subjek Email</label>
            <input type="text" name="subject" value="{{ old('subject', $effective['subject']) }}" class="form-input">
            @error('subject') <p class="form-error">{{ $message }}</p> @enderror
          </div>
        @endif

        <div class="card p-5">
          <label class="form-label">Isi Email</label>
          <textarea name="body_mail" rows="12" class="form-input font-mono text-xs leading-relaxed">{{ old('body_mail', $effective['body_mail']) }}</textarea>
          @error('body_mail') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-2">
            Satu baris = satu paragraf. Baris kosong dilewati (tidak jadi paragraf kosong).
            Untuk tombol aksi, tulis di baris tersendiri: <code class="bg-slate-100 px-1 rounded">[ACTION:Teks Tombol:{invoice_url}]</code>
          </p>
        </div>

        @if (! is_null($meta['body_whatsapp']))
          <div class="card p-5">
            <label class="form-label">Isi Pesan WhatsApp</label>
            <textarea name="body_whatsapp" rows="6" class="form-input font-mono text-xs leading-relaxed">{{ old('body_whatsapp', $effective['body_whatsapp']) }}</textarea>
            @error('body_whatsapp') <p class="form-error">{{ $message }}</p> @enderror
            <p class="text-[11px] text-slate-400 mt-2">
              Cuma dikirim kalau WhatsApp aktif di Pengaturan → Notifikasi. Pakai *bintang* untuk teks tebal (format asli WhatsApp).
            </p>
          </div>
        @endif

        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-check text-xs"></i> Simpan Template
        </button>
      </form>
    </div>

    <div>
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Variabel Tersedia</h2>
        <p class="text-xs text-slate-500 mb-3">Klik untuk menyalin, lalu tempel di isi email/WhatsApp.</p>
        <div class="flex flex-wrap gap-1.5">
          @foreach ($meta['variables'] as $v)
            <button type="button" data-copy-var="{{ $v }}"
                    class="copy-var-btn text-[11px] font-mono bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-1 rounded transition-colors">
              {{ '{' . $v . '}' }}
            </button>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll('.copy-var-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const text = '{' + btn.dataset.copyVar + '}';
        navigator.clipboard.writeText(text);

        const original = btn.textContent;
        btn.textContent = 'Disalin!';
        setTimeout(() => { btn.textContent = original; }, 1000);
      });
    });

    // Salin isi field yang sedang diketik (belum disimpan) ke form
    // pratinjau, supaya "Lihat Pratinjau" selalu menampilkan draf
    // terbaru — bukan cuma versi yang terakhir disimpan.
    document.getElementById('previewForm').addEventListener('submit', function () {
      const mainForm = document.getElementById('editForm');
      document.getElementById('previewSubject').value = mainForm.querySelector('[name="subject"]')?.value || '';
      document.getElementById('previewBodyMail').value = mainForm.querySelector('[name="body_mail"]')?.value || '';
      document.getElementById('previewBodyWhatsapp').value = mainForm.querySelector('[name="body_whatsapp"]')?.value || '';
    });
  </script>

@endsection
