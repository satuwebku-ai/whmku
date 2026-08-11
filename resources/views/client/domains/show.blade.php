@extends('client.layout')
@section('title', $domain->domain_name)

@section('content')
  <a href="{{ route('client.domains') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Domain</a>

  <div class="flex items-center justify-between mt-2 mb-5 flex-wrap gap-3">
    <h1 class="text-xl font-bold text-slate-800">{{ $domain->domain_name }}</h1>
    <span class="badge badge-{{ $domain->status === 'expired' ? 'expired' : $domain->status }} !text-sm !px-3 !py-1">{{ ucfirst($domain->status) }}</span>
  </div>

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
        <div><dt class="text-slate-400 text-xs mb-0.5">WHOIS Privacy</dt><dd class="text-slate-700 font-medium">{{ $domain->whois_privacy ? 'Aktif' : 'Nonaktif' }}</dd></div>
      </dl>

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
