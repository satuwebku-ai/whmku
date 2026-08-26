@extends('client.layout')
@section('title', $vps->domain)

@section('content')
  <a href="{{ route('client.vps') }}" class="text-decoration-none text-muted" style="font-size:12px">&larr; Kembali ke VPS Saya</a>

  @php
    $status = $vmInfo['status'] ?? null;
    $isRunning = $status === 'running';
    $spec = $vps->hasVmSpec() ? $vps->vmSpec() : null;
  @endphp

  <div class="d-flex align-items-center justify-content-between mt-2 mb-4 flex-wrap gap-3">
    <div>
      <h1 class="h4 fw-bold text-dark mb-0">{{ $vps->domain }}</h1>
      @if ($status)
        <span class="badge {{ $isRunning ? 'badge-soft-success' : 'badge-soft-secondary' }} mt-1">
          <i class="fa-solid fa-circle" style="font-size:7px"></i> {{ $isRunning ? 'Menyala' : ucfirst($status) }}
        </span>
      @endif
    </div>

    @if ($vps->serverModel && $vps->username)
      <div class="d-flex align-items-center gap-2">
        @if (! $isRunning)
          <form method="POST" action="{{ route('client.vps.power', $vps) }}">
            @csrf <input type="hidden" name="action" value="start">
            <button type="submit" class="btn btn-theme btn-sm"><i class="fa-solid fa-play" style="font-size:11px"></i> Nyalakan</button>
          </form>
        @else
          <form method="POST" action="{{ route('client.vps.power', $vps) }}"
                data-confirm="Nyalakan ulang VPS ini? Layanan akan terputus sesaat." data-confirm-title="Restart VPS" data-confirm-style="warn" data-confirm-label="Ya, Restart">
            @csrf <input type="hidden" name="action" value="restart">
            <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-rotate" style="font-size:11px"></i> Restart</button>
          </form>
          <form method="POST" action="{{ route('client.vps.power', $vps) }}"
                data-confirm="Matikan VPS ini? Website/aplikasi di dalamnya akan berhenti." data-confirm-title="Matikan VPS" data-confirm-style="danger" data-confirm-label="Ya, Matikan">
            @csrf <input type="hidden" name="action" value="stop">
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-power-off" style="font-size:11px"></i> Matikan</button>
          </form>
        @endif
      </div>
    @endif
  </div>

  @if ($apiError)
    <div class="card-public p-4 mb-4" style="border-color:#fde68a!important;background:#fffbeb">
      <p class="mb-0" style="font-size:14px;color:#92400e">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Status terkini tidak bisa diambil: {{ $apiError }}
      </p>
    </div>
  @endif

  {{-- Peringatan saldo menipis -- ditaruh menonjol karena akibatnya
       VPS mati otomatis, bukan sekadar informasi biasa. --}}
  @if ($vps->billing_mode === 'deposit' && $hoursLeft !== null && $hoursLeft < 48)
    <div class="card-public p-4 mb-4" style="border-color:#fecaca!important;background:#fef2f2">
      <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <p class="mb-0" style="font-size:14px;color:#b91c1c">
          <i class="fa-solid fa-circle-exclamation"></i>
          Saldo Anda tinggal cukup untuk <b>± {{ $hoursLeft }} jam</b> lagi.
          VPS akan otomatis dimatikan kalau saldo habis.
        </p>
        <a href="{{ route('client.balance') }}" class="btn btn-theme btn-sm flex-shrink-0">Isi Saldo</a>
      </div>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-lg-8 d-flex flex-column gap-4">
      <div class="card-public p-4">
        <h2 class="small fw-bold text-dark mb-3">Spesifikasi</h2>
        <div class="row g-3">
          <div class="col-6 col-lg-3">
            <p class="text-muted mb-0" style="font-size:11px">vCPU</p>
            <p class="fw-semibold text-dark mb-0">{{ $vmInfo['vcpu'] ?? $spec['vcpu'] ?? '—' }} Core</p>
          </div>
          <div class="col-6 col-lg-3">
            <p class="text-muted mb-0" style="font-size:11px">RAM</p>
            <p class="fw-semibold text-dark mb-0">{{ $vmInfo['memory'] ?? $spec['ram'] ?? '—' }} MB</p>
          </div>
          <div class="col-6 col-lg-3">
            <p class="text-muted mb-0" style="font-size:11px">Disk</p>
            <p class="fw-semibold text-dark mb-0">
              {{ collect($vmInfo['storage'] ?? [])->firstWhere('primary', true)['size'] ?? $spec['disk'] ?? '—' }} GB
            </p>
          </div>
          <div class="col-6 col-lg-3">
            <p class="text-muted mb-0" style="font-size:11px">OS</p>
            <p class="fw-semibold text-dark mb-0" style="font-size:14px">
              {{ $vmInfo['os_name'] ?? '—' }} {{ $vmInfo['os_version'] ?? '' }}
            </p>
          </div>
        </div>

        <div class="mt-4 pt-4 border-top">
          <h3 class="small fw-bold text-dark mb-3">Alamat Jaringan</h3>
          <div class="row g-3">
            <div class="col-sm-6">
              <p class="text-muted mb-0" style="font-size:11px">IP Publik</p>
              <p class="fw-medium text-dark mb-0" style="font-family:monospace;font-size:14px">
                {{ $vmInfo['public_ipv4'] ?? $vmInfo['public_ipv6'] ?? '—' }}
              </p>
            </div>
            <div class="col-sm-6">
              <p class="text-muted mb-0" style="font-size:11px">IP Privat</p>
              <p class="fw-medium text-dark mb-0" style="font-family:monospace;font-size:14px">
                {{ $vmInfo['private_ipv4'] ?? '—' }}
              </p>
            </div>
          </div>
        </div>

        @if ($vps->client_details)
          <div class="mt-4 pt-4 border-top">
            <h3 class="small fw-bold text-dark mb-2"><i class="fa-solid fa-key"></i> Info Akses</h3>
            <div class="rounded-3 p-3" style="background:#1e293b;color:#f1f5f9;font-family:monospace;font-size:12px;white-space:pre-line;word-break:break-word">{{ $vps->client_details }}</div>
            <p class="text-muted mt-2 mb-0" style="font-size:11px">Jaga kerahasiaan info ini. Ganti password lewat panel di samping.</p>
          </div>
        @endif
      </div>

      @if ($vps->serverModel && $vps->username)
        <div class="card-public p-4">
          <h2 class="small fw-bold text-dark mb-1">Ganti Password VPS</h2>
          <p class="text-muted mb-3" style="font-size:12px">VPS harus dalam keadaan <b>menyala</b> agar password bisa diganti.</p>
          <form method="POST" action="{{ route('client.vps.password', $vps) }}" class="row g-2"
                data-confirm="Ganti password VPS sekarang?" data-confirm-title="Ganti Password" data-confirm-style="warn" data-confirm-label="Ya, Ganti">
            @csrf
            <div class="col-sm-5">
              <input type="text" name="vm_username" value="{{ $vmInfo['username'] ?? '' }}" placeholder="Username di dalam VM" class="form-control form-control-sm" required>
            </div>
            <div class="col-sm-5">
              <input type="text" name="new_password" id="vpsPass" placeholder="Password baru" class="form-control form-control-sm" required minlength="8">
            </div>
            <div class="col-sm-2 d-flex gap-1">
              <button type="button" onclick="genVpsPass()" class="btn btn-outline-secondary btn-sm" title="Buatkan password"><i class="fa-solid fa-dice" style="font-size:11px"></i></button>
              <button type="submit" class="btn btn-theme btn-sm flex-grow-1">Ganti</button>
            </div>
          </form>
        </div>
      @endif
    </div>

    <div class="col-12 col-lg-4 d-flex flex-column gap-4">
      <div class="card-public p-4">
        <h2 class="small fw-bold text-dark mb-3">Tagihan</h2>

        @if ($vps->billing_mode === 'deposit')
          <div class="rounded-3 p-3 mb-3" style="background:rgba(79,70,229,.06);border:1px solid #c7d2fe">
            <p class="text-muted mb-0" style="font-size:11px">Tarif Berjalan</p>
            <p class="fw-bold text-dark mb-0" style="font-size:1.25rem">
              {{ $rate ? 'Rp ' . number_format($rate, 2, ',', '.') : '—' }}
              <span class="text-muted fw-normal" style="font-size:12px">/ jam</span>
            </p>
            @if ($rate)
              <p class="text-muted mb-0" style="font-size:11px">± Rp {{ number_format($rate * 730, 0, ',', '.') }} / bulan</p>
            @endif
          </div>

          <div class="d-flex justify-content-between mb-2" style="font-size:14px">
            <span class="text-muted">Saldo Anda</span>
            <span class="fw-semibold {{ (float) $client->balance <= 0 ? 'text-danger' : 'text-dark' }}">
              Rp {{ number_format((float) $client->balance, 0, ',', '.') }}
            </span>
          </div>
          @if ($hoursLeft !== null)
            <div class="d-flex justify-content-between mb-3" style="font-size:14px">
              <span class="text-muted">Cukup untuk</span>
              <span class="fw-semibold text-dark">± {{ $hoursLeft }} jam</span>
            </div>
          @endif

          <a href="{{ route('client.balance') }}" class="btn btn-theme w-100">
            <i class="fa-solid fa-wallet" style="font-size:11px"></i> Isi Saldo
          </a>
          <p class="text-muted mt-2 mb-0" style="font-size:11px">
            Saldo dipotong tiap jam selama VPS menyala. Matikan VPS untuk berhenti menagih.
          </p>
        @else
          <div class="d-flex justify-content-between mb-2" style="font-size:14px">
            <span class="text-muted">Harga</span>
            <span class="fw-semibold text-dark">Rp {{ number_format((float) $vps->price, 0, ',', '.') }}</span>
          </div>
          <div class="d-flex justify-content-between mb-2" style="font-size:14px">
            <span class="text-muted">Siklus</span>
            <span class="text-dark text-capitalize">{{ str_replace('_', ' ', $vps->billing_cycle) }}</span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:14px">
            <span class="text-muted">Jatuh Tempo</span>
            <span class="text-dark">{{ $vps->next_due_date?->format('d M Y') ?? '—' }}</span>
          </div>
        @endif
      </div>

      <div class="card-public p-4">
        <h2 class="small fw-bold text-dark mb-2">Butuh Bantuan?</h2>
        <p class="text-muted mb-3" style="font-size:14px">Ada kendala dengan VPS ini?</p>
        <a href="{{ route('client.tickets.create') }}" class="btn btn-theme w-100">
          <i class="fa-solid fa-headset" style="font-size:11px"></i> Hubungi Support
        </a>
      </div>
    </div>
  </div>

  <script>
    function genVpsPass() {
      const U = 'ABCDEFGHJKLMNPQRSTUVWXYZ', L = 'abcdefghijkmnpqrstuvwxyz', D = '23456789';
      const all = U + L + D;
      const pick = (s) => s[Math.floor(Math.random() * s.length)];
      let p = [pick(U), pick(L), pick(D)];
      for (let i = 0; i < 9; i++) p.push(pick(all));
      document.getElementById('vpsPass').value = p.sort(() => Math.random() - 0.5).join('');
    }
  </script>
@endsection
