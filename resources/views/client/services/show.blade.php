@extends('client.layout')
@section('title', $service->domain)

@section('content')
  <a href="{{ route('client.services') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Layanan</a>

  <div class="flex items-center justify-between mt-2 mb-5 flex-wrap gap-3">
    <h1 class="text-xl font-bold text-slate-800">{{ $service->domain }}</h1>
    <div class="flex items-center gap-2">
      @if ($service->status === 'active' && ! $service->renewal_invoice_id)
        <form method="POST" action="{{ route('client.services.renew-now', $service) }}">
          @csrf
          <button type="submit" class="btn btn-outline !py-1.5 !px-3 text-xs">
            <i class="fa-solid fa-rotate text-xs"></i> Perpanjang Sekarang
          </button>
        </form>
      @endif
      @if ($service->status === 'active' && ! $service->pending_upgrade_invoice_id)
        <a href="{{ route('client.services.upgrade', $service) }}" class="btn btn-outline !py-1.5 !px-3 text-xs">
          <i class="fa-solid fa-arrow-up text-xs"></i> Upgrade Paket
        </a>
      @endif
      @if ($service->status === 'active')
        <a href="{{ route('client.services.addons', $service) }}" class="btn btn-outline !py-1.5 !px-3 text-xs">
          <i class="fa-solid fa-puzzle-piece text-xs"></i> Addons
        </a>
      @endif
      <span class="badge badge-{{ $service->status }} !text-sm !px-3 !py-1">{{ ucfirst($service->status) }}</span>
      @if (! is_null($sslStatus))
        @if ($sslStatus['installed'])
          <span class="badge badge-active !text-sm !px-3 !py-1" title="Website ini sudah HTTPS">
            <i class="fa-solid fa-lock text-[10px]"></i> SSL Aktif
          </span>
        @else
          <span class="badge badge-inactive !text-sm !px-3 !py-1" title="Belum ada SSL — hubungi support kalau butuh HTTPS">
            <i class="fa-solid fa-lock-open text-[10px]"></i> Belum Ada SSL
          </span>
        @endif
      @endif
    </div>
  </div>

  @if ($service->pending_upgrade_invoice_id && $service->pendingUpgradeInvoice)
    <div class="card p-4 mb-5 border-accent/30 bg-accent/5 text-sm">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <p class="text-slate-700">
          <i class="fa-solid fa-arrow-up text-accent"></i>
          Permintaan upgrade ke <b>{{ $service->pendingUpgradeProduct?->name }}</b> sedang menunggu pembayaran
          invoice <b>{{ $service->pendingUpgradeInvoice->invoice_number }}</b>.
        </p>
        <div class="flex items-center gap-2 shrink-0">
          <form method="POST" action="{{ route('client.services.upgrade.cancel', $service) }}">
            @csrf
            <button type="submit" class="btn btn-outline !py-1.5 !px-3 text-xs">Batalkan</button>
          </form>
          <a href="{{ route('client.invoices.show', $service->pendingUpgradeInvoice) }}" class="btn btn-primary !py-1.5 !px-3 text-xs">
            Bayar Sekarang
          </a>
        </div>
      </div>
    </div>
  @endif

  @if ($service->renewal_invoice_id && $service->renewalInvoice)
    <div class="card p-4 mb-5 {{ $service->status === 'suspended' ? 'border-rose-200 bg-rose-50/60' : 'border-accent/30 bg-accent/5' }} text-sm">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <p class="{{ $service->status === 'suspended' ? 'text-rose-700' : 'text-slate-700' }}">
          <i class="fa-solid {{ $service->status === 'suspended' ? 'fa-circle-exclamation' : 'fa-file-invoice' }}"></i>
          @if ($service->status === 'suspended')
            Layanan ini disuspend karena invoice <b>{{ $service->renewalInvoice->invoice_number }}</b> belum dibayar.
            Aktif kembali otomatis begitu dibayar.
          @else
            Invoice perpanjangan <b>{{ $service->renewalInvoice->invoice_number }}</b> sudah dibuat,
            jatuh tempo {{ $service->renewalInvoice->due_date->format('d M Y') }}.
          @endif
        </p>
        <a href="{{ route('client.invoices.show', $service->renewalInvoice) }}"
           class="btn {{ $service->status === 'suspended' ? '!bg-rose-600 !text-white !border-rose-600' : 'btn-primary' }} !py-1.5 !px-3 text-xs shrink-0">
          Bayar Sekarang
        </a>
      </div>
    </div>
  @elseif ($service->status === 'suspended')
    <div class="card p-4 mb-5 border-rose-200 bg-rose-50/60 text-sm text-rose-700">
      <i class="fa-solid fa-circle-exclamation"></i>
      Layanan ini sedang disuspend. Silakan cek
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

      @if ($usage)
        @php
          $usedNum = (float) preg_replace('/[^0-9.]/', '', $usage['disk_used'] ?? '0');
          $limitRaw = $usage['disk_limit'] ?? 'unlimited';
          $isUnlimited = strtolower((string) $limitRaw) === 'unlimited';
          $limitNum = $isUnlimited ? null : (float) preg_replace('/[^0-9.]/', '', $limitRaw);
          $percent = ($limitNum && $limitNum > 0) ? min(100, round($usedNum / $limitNum * 100)) : 0;
        @endphp
        <div class="mt-5 pt-5 border-t border-slate-100">
          <div class="flex items-center justify-between mb-1.5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Pemakaian Disk</p>
            <p class="text-xs text-slate-500">
              {{ $usage['disk_used'] ?? '—' }} / {{ $isUnlimited ? 'Unlimited' : $usage['disk_limit'] }}
            </p>
          </div>
          @unless ($isUnlimited)
            <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
              <div class="h-full rounded-full {{ $percent >= 90 ? 'bg-rose-500' : ($percent >= 70 ? 'bg-amber-500' : 'bg-accent') }}"
                   style="width: {{ $percent }}%"></div>
            </div>
          @endunless
        </div>
      @endif

      {{-- Info akses layanan — satu-satunya cara klien lihat kredensial
           untuk layanan yang provisioning-nya manual (VPS, dedicated
           server, lisensi, dll). Untuk akun cPanel otomatis, ini
           opsional (info tambahan di luar SSO). --}}
      @if ($service->client_details)
        <div class="mt-5 pt-5 border-t border-slate-100">
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
            <i class="fa-solid fa-key"></i> Info Akses Layanan
          </p>
          <div class="rounded-lg bg-slate-800 text-slate-100 p-4 text-xs font-mono whitespace-pre-line break-words">{{ $service->client_details }}</div>
          <p class="text-[11px] text-slate-400 mt-2">
            Jaga kerahasiaan info ini. Hubungi support kalau ada yang perlu diubah atau di-reset.
          </p>
        </div>
      @endif

      {{-- Info koneksi — dibutuhkan klien untuk setup email/FTP manual
           lewat aplikasi pihak ketiga (Outlook, FileZilla, dll), bukan
           hanya lewat webmail/File Manager di cPanel. --}}
      @if ($service->serverModel)
        <div class="mt-5 pt-5 border-t border-slate-100">
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Info Koneksi (untuk email &amp; FTP)</p>
          <dl class="grid sm:grid-cols-2 gap-3 text-sm">
            <div>
              <dt class="text-slate-400 text-xs mb-0.5">Mail/FTP Server</dt>
              <dd class="text-slate-700 font-mono text-xs">{{ $service->serverModel->hostname }}</dd>
            </div>
            @if ($usage && $usage['ip'])
              <div>
                <dt class="text-slate-400 text-xs mb-0.5">Alamat IP</dt>
                <dd class="text-slate-700 font-mono text-xs">{{ $usage['ip'] }}</dd>
              </div>
            @endif
          </dl>
          <p class="text-[11px] text-slate-400 mt-2">
            Gunakan username panel &amp; password akun ini saat mengatur aplikasi email atau FTP.
          </p>
        </div>
      @endif
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

      {{-- Pembatalan layanan --}}
      @if ($service->status !== 'terminated')
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-3">Batalkan Layanan</h2>

          @if ($service->cancellation_status === 'requested')
            <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5 mb-3">
              <p class="text-xs font-semibold text-amber-800 mb-1">
                <i class="fa-solid fa-clock"></i> Sedang ditinjau
              </p>
              <p class="text-xs text-amber-700">
                Diajukan {{ $service->cancellation_requested_at?->diffForHumans() }}.
                Tim kami akan meninjau dalam 1x24 jam.
              </p>
            </div>
            <form method="POST" action="{{ route('client.services.cancel.withdraw', $service) }}">
              @csrf
              <button type="submit" class="btn btn-outline w-full">Batalkan Pengajuan</button>
            </form>

          @elseif ($service->cancellation_status === 'declined')
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2.5 mb-3 text-xs text-slate-600">
              <p class="font-semibold mb-1">Pengajuan sebelumnya ditolak</p>
              @if ($service->cancellation_admin_note)
                <p>{{ $service->cancellation_admin_note }}</p>
              @endif
            </div>
            <button type="button" onclick="document.getElementById('cancelForm').classList.remove('hidden'); this.classList.add('hidden')"
                    class="btn btn-outline w-full !text-rose-600 !border-rose-200">
              Ajukan Kembali
            </button>
            <form id="cancelForm" method="POST" action="{{ route('client.services.cancel', $service) }}" class="hidden mt-3 space-y-2">
              @csrf
              <textarea name="reason" rows="3" class="form-input text-sm" placeholder="Alasan pembatalan..." required></textarea>
              <button type="submit" class="btn w-full !bg-rose-600 !text-white !border-rose-600">Kirim Pengajuan</button>
            </form>

          @else
            <p class="text-xs text-slate-500 mb-3">
              Pengajuan akan ditinjau tim kami sebelum layanan benar-benar dihentikan — bukan otomatis.
            </p>
            <button type="button" onclick="document.getElementById('cancelForm').classList.remove('hidden'); this.classList.add('hidden')"
                    class="btn btn-outline w-full !text-rose-600 !border-rose-200">
              Ajukan Pembatalan
            </button>
            <form id="cancelForm" method="POST" action="{{ route('client.services.cancel', $service) }}" class="hidden mt-3 space-y-2">
              @csrf
              <textarea name="reason" rows="3" class="form-input text-sm" placeholder="Alasan pembatalan..." required></textarea>
              <button type="submit" class="btn w-full !bg-rose-600 !text-white !border-rose-600">Kirim Pengajuan</button>
            </form>
          @endif

          @error('reason') <p class="form-error mt-2">{{ $message }}</p> @enderror
        </div>
      @endif
    </div>
  </div>
@endsection
