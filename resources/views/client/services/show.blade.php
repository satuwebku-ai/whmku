@extends('client.layout')
@section('title', $service->domain)

@section('content')
  <a href="{{ route('client.services') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Layanan</a>

  <div class="flex items-center justify-between mt-2 mb-5 flex-wrap gap-3">
    <h1 class="text-xl font-bold text-slate-800">{{ $service->domain }}</h1>
    <span class="badge badge-{{ $service->status }} !text-sm !px-3 !py-1">{{ ucfirst($service->status) }}</span>
  </div>

  @if ($service->status === 'suspended')
    <div class="card p-4 mb-5 border-rose-200 bg-rose-50/60 text-sm text-rose-700">
      <i class="fa-solid fa-circle-exclamation"></i>
      Layanan ini sedang disuspend. Umumnya karena tagihan belum dibayar — silakan cek
      <a href="{{ route('client.invoices') }}" class="underline font-medium">halaman invoice</a>
      atau hubungi support.
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-4">Detail Layanan</h2>
      <dl class="grid sm:grid-cols-2 gap-4 text-sm">
        <div><dt class="text-slate-400 text-xs mb-0.5">Paket</dt><dd class="text-slate-700 font-medium">{{ $service->package }}</dd></div>
        <div><dt class="text-slate-400 text-xs mb-0.5">Domain</dt><dd class="text-slate-700 font-medium">{{ $service->domain }}</dd></div>
        <div><dt class="text-slate-400 text-xs mb-0.5">Username Panel</dt><dd class="text-slate-700 font-medium">{{ $service->username ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 text-xs mb-0.5">Panel</dt><dd class="text-slate-700 font-medium capitalize">{{ $service->panel }}</dd></div>
        <div><dt class="text-slate-400 text-xs mb-0.5">Harga</dt><dd class="text-slate-700 font-medium">Rp {{ number_format($service->price, 0, ',', '.') }} / {{ str_replace('_', ' ', $service->billing_cycle) }}</dd></div>
        <div><dt class="text-slate-400 text-xs mb-0.5">Jatuh Tempo Berikutnya</dt><dd class="text-slate-700 font-medium">{{ $service->next_due_date?->format('d M Y') ?? '—' }}</dd></div>
      </dl>
    </div>

    <div class="space-y-5">
      @if ($service->status === 'active' && $service->username && $service->server_id)
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-1">Kelola Hosting</h2>
          <p class="text-sm text-slate-500 mb-3">
            Masuk ke control panel tanpa perlu memasukkan password.
          </p>
          <a href="{{ route('client.services.login-panel', $service) }}" target="_blank" rel="noopener"
             class="btn btn-primary w-full">
            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> Buka cPanel
          </a>
          <p class="text-[11px] text-slate-400 mt-2">
            Tautan berlaku sekali pakai dan kedaluwarsa beberapa menit setelah dibuka.
          </p>
        </div>
      @endif

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Bantuan</h2>
        <p class="text-sm text-slate-500 mb-3">Ada kendala dengan layanan ini?</p>
        <a href="{{ route('client.tickets.create') }}" class="btn btn-primary w-full">
          <i class="fa-solid fa-headset text-xs"></i> Hubungi Support
        </a>
      </div>
    </div>
  </div>
@endsection
