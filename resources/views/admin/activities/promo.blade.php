@extends('layouts.admin')

@section('title', 'Kirim Promo')

@section('content')

  @include('admin.activities._nav')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Kirim Promo ke Klien</h1>
    <p class="text-sm text-slate-500 mt-1">Email promosi ke seluruh klien aktif yang bersedia menerimanya.</p>
  </div>

  <div class="grid lg:grid-cols-3 gap-5 max-w-5xl">
    <div class="lg:col-span-2">
      <form method="POST" action="{{ route('admin.promo.send') }}" class="card p-6 space-y-4"
            data-confirm="Kirim promo ini ke {{ $optedIn }} klien? Email yang sudah terkirim tidak bisa ditarik kembali."
            data-confirm-title="Kirim Promo" data-confirm-style="warn" data-confirm-label="Ya, Kirim Sekarang">
        @csrf

        <div>
          <label class="form-label">Judul</label>
          <input type="text" name="judul" value="{{ old('judul') }}" class="form-input" required
                 placeholder="Diskon 30% untuk semua paket hosting">
          @error('judul') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">Dipakai sebagai subjek email.</p>
        </div>

        <div>
          <label class="form-label">Isi Pesan</label>
          <textarea name="isi" rows="8" class="form-input" required
                    placeholder="Tulis isi promosi di sini.&#10;&#10;Pisahkan dengan baris kosong untuk membuat paragraf baru.">{{ old('isi') }}</textarea>
          @error('isi') <p class="form-error">{{ $message }}</p> @enderror
          <p class="text-[11px] text-slate-400 mt-1">Teks biasa. Baris kosong menjadi paragraf baru.</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Tautan Tombol (opsional)</label>
            <input type="url" name="tautan" value="{{ old('tautan') }}" class="form-input" placeholder="https://...">
            @error('tautan') <p class="form-error">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="form-label">Teks Tombol</label>
            <input type="text" name="label_tautan" value="{{ old('label_tautan') }}" class="form-input" placeholder="Lihat Promo">
          </div>
        </div>

        <label class="flex items-start gap-2 text-sm text-slate-600 pt-2 border-t border-slate-100">
          <input type="checkbox" name="konfirmasi" value="1" class="mt-0.5 rounded border-slate-300 text-accent focus:ring-accent/40">
          <span>Saya sudah memeriksa isi pesan dan siap mengirimnya ke {{ $optedIn }} klien.</span>
        </label>
        @error('konfirmasi') <p class="form-error">{{ $message }}</p> @enderror

        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane text-xs"></i> Kirim Promo</button>
      </form>
    </div>

    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Penerima</h2>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-500">Klien aktif</span>
            <span class="font-medium text-slate-700">{{ $total }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Bersedia dikirimi promo</span>
            <span class="font-bold text-accent">{{ $optedIn }}</span>
          </div>
          @if ($total > $optedIn)
            <p class="text-[11px] text-slate-400 pt-2 border-t border-slate-100">
              {{ $total - $optedIn }} klien menolak email promosi. Pilihan mereka dihormati dan tidak bisa ditimpa dari sini.
            </p>
          @endif
        </div>
      </div>

      <div class="card p-5 border-amber-200 bg-amber-50/40">
        <p class="text-xs text-amber-800 leading-relaxed">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <b>Sebelum mengirim:</b> pastikan domain pengirim sudah punya SPF dan DKIM di DNS.
          Mengirim promo massal dari domain tanpa keduanya membuat email masuk spam,
          dan bisa membuat email transaksional Anda (invoice, reset password) ikut terblokir.
        </p>
      </div>
    </div>
  </div>

@endsection
