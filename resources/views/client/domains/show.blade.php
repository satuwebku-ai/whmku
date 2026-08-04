@extends('client.layout')
@section('title', $domain->domain_name)

@section('content')
  <a href="{{ route('client.domains') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Domain</a>

  <div class="flex items-center justify-between mt-2 mb-5 flex-wrap gap-3">
    <h1 class="text-xl font-bold text-slate-800">{{ $domain->domain_name }}</h1>
    <span class="badge badge-{{ $domain->status === 'expired' ? 'expired' : $domain->status }} !text-sm !px-3 !py-1">{{ ucfirst($domain->status) }}</span>
  </div>

  @if ($domain->is_expiring_soon)
    <div class="card p-4 mb-5 border-amber-200 bg-amber-50/60 text-sm text-amber-800">
      <i class="fa-solid fa-triangle-exclamation"></i>
      Domain ini akan kedaluwarsa {{ $domain->expiry_date->format('d M Y') }}.
      Hubungi kami untuk perpanjangan agar website Anda tetap aktif.
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-4">Detail Domain</h2>
      <dl class="grid sm:grid-cols-2 gap-4 text-sm">
        <div><dt class="text-slate-400 text-xs mb-0.5">Tanggal Registrasi</dt><dd class="text-slate-700 font-medium">{{ $domain->register_date?->format('d M Y') ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 text-xs mb-0.5">Kedaluwarsa</dt><dd class="text-slate-700 font-medium">{{ $domain->expiry_date?->format('d M Y') ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 text-xs mb-0.5">Perpanjangan Otomatis</dt><dd class="text-slate-700 font-medium">{{ $domain->auto_renew ? 'Aktif' : 'Nonaktif' }}</dd></div>
        <div><dt class="text-slate-400 text-xs mb-0.5">WHOIS Privacy</dt><dd class="text-slate-700 font-medium">{{ $domain->whois_privacy ? 'Aktif' : 'Nonaktif' }}</dd></div>
      </dl>

      @if ($domain->nameservers)
        <div class="mt-5 pt-5 border-t border-slate-100">
          <p class="text-slate-400 text-xs mb-2">Nameserver</p>
          <ul class="text-sm text-slate-700 space-y-1 font-mono">
            @foreach ($domain->nameservers as $ns)
              <li>{{ $ns }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>

    <div class="card p-5">
      <h2 class="text-sm font-semibold text-slate-800 mb-3">Bantuan</h2>
      <p class="text-sm text-slate-500 mb-3">Butuh ubah nameserver atau perpanjang domain?</p>
      <a href="{{ route('client.tickets.create') }}" class="btn btn-primary w-full">
        <i class="fa-solid fa-headset text-xs"></i> Hubungi Support
      </a>
    </div>
  </div>
@endsection
