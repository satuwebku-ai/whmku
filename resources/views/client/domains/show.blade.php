@extends('client.layout')
@section('title', $domain->domain_name)

@section('content')
  <a href="{{ route('client.domains') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Domain</a>

  <div class="flex items-center justify-between mt-2 mb-5 flex-wrap gap-3">
    <h1 class="text-xl font-bold text-slate-800">{{ $domain->domain_name }}</h1>
    <div class="flex items-center gap-2">
      @if ($domain->status === 'active' && ! $domain->renewal_invoice_id)
        <form method="POST" action="{{ route('client.domains.renew-now', $domain) }}">
          @csrf
          <button type="submit" class="btn btn-outline !py-1.5 !px-3 text-xs">
            <i class="fa-solid fa-rotate text-xs"></i> Perpanjang Sekarang
          </button>
        </form>
      @endif
      <span class="badge badge-{{ $domain->status === 'expired' ? 'expired' : $domain->status }} !text-sm !px-3 !py-1">{{ ucfirst($domain->status) }}</span>
    </div>
  </div>

  @if ($domain->provision_status === 'needs_documents')
    <div class="card p-4 mb-5 border-amber-200 bg-amber-50/60">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <p class="text-sm text-amber-800">
          <i class="fa-solid fa-file-circle-exclamation"></i>
          Domain ini butuh dokumen tambahan sebelum bisa diaktifkan (persyaratan PANDI untuk TLD Indonesia).
        </p>
        <a href="{{ route('client.domains.documents', $domain) }}" class="btn btn-primary !py-1.5 !px-3 text-xs shrink-0">
          Unggah Dokumen
        </a>
      </div>
    </div>
  @endif

  @if ($domain->renewal_invoice_id && $domain->renewalInvoice)
    <div class="card p-4 mb-5 border-accent/30 bg-accent/5 text-sm">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <p class="text-slate-700">
          <i class="fa-solid fa-file-invoice text-accent"></i>
          Invoice perpanjangan <b>{{ $domain->renewalInvoice->invoice_number }}</b> sudah dibuat,
          jatuh tempo {{ $domain->renewalInvoice->due_date->format('d M Y') }}.
        </p>
        <a href="{{ route('client.invoices.show', $domain->renewalInvoice) }}" class="btn btn-primary !py-1.5 !px-3 text-xs shrink-0">
          Bayar Sekarang
        </a>
      </div>
    </div>
  @elseif ($domain->is_expiring_soon)
    <div class="card p-4 mb-5 border-amber-200 bg-amber-50/60 text-sm text-amber-800">
      <i class="fa-solid fa-triangle-exclamation"></i>
      Domain ini akan kedaluwarsa {{ $domain->expiry_date->format('d M Y') }}.
      @if ($domain->auto_renew)
        Invoice perpanjangan akan dibuat otomatis mendekati tanggal tersebut.
      @else
        Perpanjangan Otomatis sedang nonaktif untuk domain ini — aktifkan di bawah atau hubungi kami sebelum tanggal tersebut.
      @endif
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-4">Detail Domain</h2>
      <dl class="grid sm:grid-cols-2 gap-4 text-sm">
        <div><dt class="text-slate-400 text-xs mb-0.5">Tanggal Registrasi</dt><dd class="text-slate-700 font-medium">{{ $domain->register_date?->format('d M Y') ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 text-xs mb-0.5">Kedaluwarsa</dt><dd class="text-slate-700 font-medium">{{ $domain->expiry_date?->format('d M Y') ?? '—' }}</dd></div>
        <div>
          <dt class="text-slate-400 text-xs mb-0.5">Perpanjangan Otomatis</dt>
          <dd class="flex items-center gap-2">
            <span class="text-slate-700 font-medium">{{ $domain->auto_renew ? 'Aktif' : 'Nonaktif' }}</span>
            <form method="POST" action="{{ route('client.domains.auto-renew', $domain) }}">
              @csrf
              <button type="submit" class="text-xs text-accent hover:underline">
                {{ $domain->auto_renew ? 'Matikan' : 'Aktifkan' }}
              </button>
            </form>
          </dd>
        </div>
        <div>
          <dt class="text-slate-400 text-xs mb-0.5">ID Protection (WHOIS Privacy)</dt>
          <dd class="flex items-center gap-2 flex-wrap">
            @php
              $privacyActive = $domain->hasActivePrivacy();
              $privacyDaysLeft = $domain->privacyDaysLeft();
              $privacyPrice = (float) \App\Models\Setting::get('whois_privacy_price', 0);
            @endphp

            <span class="text-slate-700 font-medium">{{ $privacyActive ? 'Aktif' : 'Nonaktif' }}</span>

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

            @if ($domain->privacy_invoice_id)
              <a href="{{ route('client.invoices.show', $domain->privacy_invoice_id) }}" class="text-xs text-amber-600 hover:underline">
                Menunggu pembayaran — Bayar Sekarang
              </a>
            @else
              <form method="POST" action="{{ route('client.domains.privacy', $domain) }}">
                @csrf
                <button type="submit" class="text-xs text-accent hover:underline">
                  @if ($privacyActive)
                    Matikan
                  @else
                    {{ $domain->privacy_expires_at ? 'Perpanjang' : 'Aktifkan' }}{{ $privacyPrice > 0 ? ' — Rp ' . number_format($privacyPrice, 0, ',', '.') . '/tahun' : '' }}
                  @endif
                </button>
              </form>
            @endif

            {{-- Peringatan kalau catatan kita TIDAK COCOK dengan kondisi
                 sungguhan di registrar — misal aktif di Liqu.id padahal
                 di sistem kita sudah kedaluwarsa (berarti kita masih
                 ditagih), atau sebaliknya (klien sudah bayar tapi belum
                 benar-benar aktif). --}}
            @if (! is_null($privacyAtRegistrar) && $privacyAtRegistrar !== $privacyActive)
              <span class="text-xs text-rose-600 w-full mt-1 block">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Status di registrar: <b>{{ $privacyAtRegistrar ? 'Aktif' : 'Nonaktif' }}</b> — tidak cocok dengan catatan di sini.
                Hubungi support untuk disesuaikan.
              </span>
            @endif
          </dd>
        </div>
        @if (! is_null($lockStatus))
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Registrar Lock</dt>
            <dd class="flex items-center gap-2">
              <span class="text-slate-700 font-medium">{{ $lockStatus ? 'Terkunci' : 'Tidak Terkunci' }}</span>
              <form method="POST" action="{{ route('client.domains.lock', $domain) }}">
                @csrf
                <button type="submit" class="text-xs text-accent hover:underline">
                  {{ $lockStatus ? 'Buka Kunci' : 'Kunci Domain' }}
                </button>
              </form>
            </dd>
          </div>
        @endif
        @if (! is_null($theftStatus))
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">
              Theft Protection
              <i class="fa-solid fa-circle-question text-slate-300" title="Proteksi tambahan dari pencurian domain lewat perubahan data tanpa verifikasi ekstra — beda dari ID Protection"></i>
            </dt>
            <dd class="flex items-center gap-2">
              <span class="text-slate-700 font-medium">{{ $theftStatus ? 'Aktif' : 'Nonaktif' }}</span>
              <form method="POST" action="{{ route('client.domains.theft-protection', $domain) }}">
                @csrf
                <button type="submit" class="text-xs text-accent hover:underline">
                  {{ $theftStatus ? 'Matikan' : 'Aktifkan' }}
                </button>
              </form>
            </dd>
          </div>
        @endif
      </dl>

      @if ($supportsForwarding)
        <div class="mt-5 pt-5 border-t border-slate-100">
          <h3 class="text-sm font-semibold text-slate-800 mb-1">Domain Forwarding</h3>
          <p class="text-xs text-slate-400 mb-2">Arahkan domain ini ke alamat website lain (redirect), tanpa perlu hosting terpisah.</p>
          <form method="POST" action="{{ route('client.domains.forwarding', $domain) }}" class="flex gap-2">
            @csrf
            <input type="url" name="forward_to" value="{{ $forwardTo }}" placeholder="https://contoh.com (kosongkan untuk matikan)" class="form-input flex-1">
            <button type="submit" class="btn btn-outline shrink-0">Simpan</button>
          </form>
        </div>
      @endif

      @if ($supportsEmailForwarding)
        <div class="mt-5 pt-5 border-t border-slate-100">
          <a href="{{ route('client.domains.email-forwarding', $domain) }}" class="btn btn-outline">
            <i class="fa-solid fa-envelope text-xs"></i> Kelola Email Forwarding
          </a>
        </div>
      @endif

      {{-- Kelola DNS & kode transfer --}}
      <div class="mt-5 pt-5 border-t border-slate-100 flex flex-wrap gap-3">
        <a href="{{ route('client.domains.dns', $domain) }}" class="btn btn-outline">
          <i class="fa-solid fa-server text-xs"></i> Kelola DNS
        </a>
        <form method="POST" action="{{ route('client.domains.auth-code', $domain) }}">
          @csrf
          <button type="submit" class="btn btn-outline">
            <i class="fa-solid fa-key text-xs"></i> Minta Kode Transfer (EPP)
          </button>
        </form>
      </div>

      @if (session('auth_code'))
        <div class="mt-4 rounded-lg bg-slate-800 text-white px-4 py-3 text-sm">
          <p class="text-slate-300 text-xs mb-1">Kode transfer domain Anda (berlaku sekali pakai):</p>
          <p class="font-mono font-bold text-lg tracking-wide">{{ session('auth_code') }}</p>
          <p class="text-slate-400 text-[11px] mt-1">
            Jangan bagikan kode ini kecuali Anda memang sedang memindahkan domain ke registrar lain.
          </p>
        </div>
      @endif

      {{-- Ubah nameserver --}}
      <div class="mt-5 pt-5 border-t border-slate-100">
        <h3 class="text-sm font-semibold text-slate-800 mb-1">Nameserver</h3>
        <p class="text-xs text-slate-500 mb-3">
          Arahkan domain ke server hosting mana pun. Isi minimal dua nameserver.
        </p>

        @if ($domain->status !== 'active')
          <p class="text-sm text-slate-500">
            Nameserver hanya bisa diubah untuk domain berstatus aktif.
          </p>
        @elseif (! $domain->registrar_id)
          <p class="text-sm text-slate-500">
            Domain ini belum terhubung ke registrar. Silakan hubungi support untuk mengubah nameserver.
          </p>
        @else
          @php $ns = $domain->nameservers ?? []; @endphp

          <form method="POST" action="{{ route('client.domains.nameservers', $domain) }}" class="space-y-2">
            @csrf

            @for ($i = 0; $i < 4; $i++)
              <div>
                <input type="text" name="nameservers[]" value="{{ old('nameservers.' . $i, $ns[$i] ?? '') }}"
                       placeholder="ns{{ $i + 1 }}.contoh.com{{ $i >= 2 ? ' (opsional)' : '' }}"
                       class="form-input font-mono text-sm" {{ $i < 2 ? 'required' : '' }}>
              </div>
            @endfor

            @error('nameservers') <p class="form-error">{{ $message }}</p> @enderror
            @error('nameservers.*') <p class="form-error">{{ $message }}</p> @enderror

            <button type="submit" class="btn btn-primary mt-1">
              <i class="fa-solid fa-check text-xs"></i> Simpan Nameserver
            </button>

            <p class="text-[11px] text-slate-400">
              Perubahan DNS bisa memakan waktu hingga 24 jam untuk menyebar ke seluruh dunia.
            </p>
          </form>
        @endif
      </div>
    </div>

    <div class="card p-5">
      <h2 class="text-sm font-semibold text-slate-800 mb-3">Bantuan</h2>
      <p class="text-sm text-slate-500 mb-3">Butuh perpanjang domain atau ubah data WHOIS?</p>
      <a href="{{ route('client.tickets.create') }}" class="btn btn-primary w-full">
        <i class="fa-solid fa-headset text-xs"></i> Hubungi Support
      </a>
    </div>
  </div>
@endsection
