@extends('client.layout')
@section('title', 'Addons — ' . $domain->domain_name)

@section('content')
  <a href="{{ route('client.domains.show', $domain) }}" class="text-xs text-slate-400 hover:text-slate-600">
    &larr; Kembali ke {{ $domain->domain_name }}
  </a>

  <div class="mt-2 mb-6">
    <h1 class="text-xl font-bold text-slate-800">Addons — {{ $domain->domain_name }}</h1>
    <p class="text-sm text-slate-500 mt-1">Fitur tambahan yang bisa dipasang di domain ini.</p>
  </div>

  <div class="card p-5">
    <div class="flex items-start justify-between gap-4 flex-wrap">
      <div class="flex items-start gap-3">
        <span class="w-9 h-9 rounded-lg bg-accent/10 text-accent flex items-center justify-center shrink-0">
          <i class="fa-solid fa-user-shield text-sm"></i>
        </span>
        <div>
          <h2 class="text-sm font-semibold text-slate-800">ID Protection (WHOIS Privacy)</h2>
          <p class="text-xs text-slate-500 mt-0.5 max-w-md">Sembunyikan data pribadi (nama, alamat, email, telepon) dari pencarian WHOIS publik — mencegah spam dan penyalahgunaan data.</p>

          @php
            $privacyActive = $domain->hasActivePrivacy();
            $privacyDaysLeft = $domain->privacyDaysLeft();
            $privacyPrice = (float) \App\Models\Setting::get('whois_privacy_price', 0);
          @endphp

          <div class="flex items-center gap-2 flex-wrap mt-2">
            <span class="badge {{ $privacyActive ? 'badge-active' : 'badge-inactive' }}">{{ $privacyActive ? 'Aktif' : 'Nonaktif' }}</span>

            @if ($privacyActive)
              <span class="text-xs {{ $privacyDaysLeft !== null && $privacyDaysLeft <= 30 ? 'text-amber-600' : 'text-slate-400' }}">
                s.d. {{ $domain->privacy_expires_at->format('d M Y') }}
                @if ($privacyDaysLeft !== null && $privacyDaysLeft <= 30)
                  ({{ $privacyDaysLeft }} hari lagi)
                @endif
              </span>
            @elseif ($domain->privacy_expires_at && $domain->privacy_expires_at->isPast())
              <span class="text-xs text-rose-500">Kedaluwarsa {{ $domain->privacy_expires_at->format('d M Y') }}</span>
            @endif
          </div>

          @if (! is_null($privacyAtRegistrar ?? null) && $privacyAtRegistrar !== $privacyActive)
            <p class="text-xs text-rose-600 mt-2">
              <i class="fa-solid fa-triangle-exclamation"></i>
              Status di registrar: <b>{{ $privacyAtRegistrar ? 'Aktif' : 'Nonaktif' }}</b> — tidak cocok dengan catatan di sini. Hubungi support untuk disesuaikan.
            </p>
          @endif
        </div>
      </div>

      <div class="shrink-0">
        @if ($domain->privacy_invoice_id)
          <a href="{{ route('client.invoices.show', $domain->privacy_invoice_id) }}" class="btn btn-primary !py-1.5 !px-3 text-xs">
            Bayar Sekarang
          </a>
        @else
          <form method="POST" action="{{ route('client.domains.privacy', $domain) }}">
            @csrf
            <button type="submit" class="btn {{ $privacyActive ? 'btn-outline' : 'btn-primary' }} !py-1.5 !px-3 text-xs">
              @if ($privacyActive)
                Matikan
              @else
                {{ $domain->privacy_expires_at ? 'Perpanjang' : 'Aktifkan' }}{{ $privacyPrice > 0 ? ' — Rp ' . number_format($privacyPrice, 0, ',', '.') . '/thn' : '' }}
              @endif
            </button>
          </form>
        @endif
      </div>
    </div>
  </div>
@endsection
