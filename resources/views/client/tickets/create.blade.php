@extends('client.layout')
@section('title', 'Buat Tiket')

@section('content')
  <a href="{{ route('client.tickets') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Tiket</a>

  <h1 class="text-xl font-bold text-slate-800 mt-2 mb-1">Buat Tiket Support</h1>
  <p class="text-sm text-slate-500 mb-5">Jelaskan kendala Anda sedetail mungkin agar kami bisa membantu lebih cepat.</p>

  @if ($errors->any())
    <div class="mb-5 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
      <ul class="list-disc pl-4 space-y-0.5">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('client.tickets.store') }}" enctype="multipart/form-data" class="card p-6 space-y-4 max-w-2xl">
    @csrf

    <div>
      <label class="form-label">Subjek</label>
      <input type="text" name="subject" value="{{ old('subject') }}" required class="form-input" placeholder="Website tidak bisa diakses">
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Departemen</label>
        <select name="department" class="form-input">
          <option value="support" @selected(old('department') === 'support')>Bantuan Teknis</option>
          <option value="billing" @selected(old('department') === 'billing')>Tagihan &amp; Pembayaran</option>
          <option value="sales" @selected(old('department') === 'sales')>Penjualan</option>
        </select>
      </div>
      <div>
        <label class="form-label">Prioritas</label>
        <select name="priority" class="form-input">
          <option value="low" @selected(old('priority') === 'low')>Rendah</option>
          <option value="medium" @selected(old('priority', 'medium') === 'medium')>Sedang</option>
          <option value="high" @selected(old('priority') === 'high')>Tinggi</option>
        </select>
      </div>
    </div>

    @if ($services->isNotEmpty())
      <div>
        <label class="form-label">Layanan Terkait <span class="text-slate-400 font-normal">(opsional)</span></label>
        <select name="hosting_account_id" class="form-input">
          <option value="">— Tidak terkait layanan tertentu —</option>
          @foreach ($services as $service)
            <option value="{{ $service->id }}" @selected(old('hosting_account_id') == $service->id)>{{ $service->domain }} ({{ $service->package }})</option>
          @endforeach
        </select>
      </div>
    @endif

    <div>
      <label class="form-label">Pesan</label>
      <textarea name="message" rows="7" required class="form-input" placeholder="Ceritakan kendala Anda...">{{ old('message') }}</textarea>
    </div>

    <div>
      <label class="form-label">Lampiran <span class="text-slate-400 font-normal">(opsional, maks 5MB)</span></label>
      <input type="file" name="attachment" class="form-input text-xs">
      <p class="text-[11px] text-slate-400 mt-1">Format: jpg, png, pdf, txt, log, zip. Screenshot error sangat membantu.</p>
    </div>

    <div class="flex gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane text-xs"></i> Kirim Tiket</button>
      <a href="{{ route('client.tickets') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>
@endsection
