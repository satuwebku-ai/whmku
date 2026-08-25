@extends('layouts.admin')

@section('title', 'Diagnosa — ' . $server->name)

@php
  /** Kotak error kecil yang dipakai berulang di tiap bagian. */
  $err = function ($message) {
    return '<p class="mb-0" style="font-size:12px;color:#b91c1c"><i class="fa-solid fa-circle-exclamation"></i> ' . e($message) . '</p>';
  };
@endphp

@section('content')

  <a href="{{ route('admin.servers.index') }}" class="text-decoration-none text-muted" style="font-size:12px">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Server
  </a>
  <h1 class="h4 fw-bold text-dark mt-1 mb-2">Diagnosa — {{ $server->name }}</h1>
  <p class="small text-muted mb-4">
    <i class="fa-solid fa-cloud"></i> IDCloudHost — semua data diambil langsung dari API, hanya membaca, tidak mengubah apa pun.
  </p>

  <div class="row g-3">

    {{-- ══════ 1. AKUN ══════ --}}
    <div class="col-12 col-lg-6">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-user text-muted"></i> Akun</h2>
        @if ($sections['user']['error'])
          {!! $err($sections['user']['error']) !!}
        @else
          @php $u = $sections['user']['data']; $p = $u['profile_data'] ?? []; @endphp
          <div class="row g-3">
            <div class="col-6">
              <p class="text-muted mb-0" style="font-size:11px">Email / Nama Akun</p>
              <p class="fw-medium text-dark mb-0" style="font-size:14px">{{ $u['name'] ?? '—' }}</p>
            </div>
            <div class="col-6">
              <p class="text-muted mb-0" style="font-size:11px">User ID</p>
              <p class="fw-medium text-dark mb-0" style="font-size:14px">{{ $u['id'] ?? '—' }}</p>
            </div>
            @if (!empty($p['first_name']) || !empty($p['last_name']))
              <div class="col-6">
                <p class="text-muted mb-0" style="font-size:11px">Nama Lengkap</p>
                <p class="fw-medium text-dark mb-0" style="font-size:14px">{{ trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) }}</p>
              </div>
            @endif
            @if (!empty($p['phone_number']))
              <div class="col-6">
                <p class="text-muted mb-0" style="font-size:11px">Telepon</p>
                <p class="fw-medium text-dark mb-0" style="font-size:14px">{{ $p['phone_number'] }}</p>
              </div>
            @endif
            <div class="col-6">
              <p class="text-muted mb-0" style="font-size:11px">Aktivitas Terakhir</p>
              <p class="fw-medium text-dark mb-0" style="font-size:14px">{{ $u['last_activity'] ?? '—' }}</p>
            </div>
          </div>
        @endif
      </div>
    </div>

    {{-- ══════ 2. NILAI DEPOSIT ══════ --}}
    <div class="col-12 col-lg-6">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-wallet text-muted"></i> Nilai Deposit / Kredit</h2>
        @if ($sections['credit']['error'])
          <div class="rounded-3 px-3 py-2" style="background:#fffbeb;border:1px solid #fde68a">
            <p class="mb-0" style="font-size:12px;color:#92400e">
              <i class="fa-solid fa-circle-info"></i> {{ $sections['credit']['error'] }}
            </p>
          </div>
        @else
          @php
            $acc = $sections['credit']['data'] ?? [];
            $totals = $acc['running_totals'] ?? [];
            $available = (float) ($totals['credit_available'] ?? 0);
            $unpaid = (float) ($acc['unpaid_amount'] ?? 0);
            $restricted = ($acc['restriction_level'] ?? null) === 'FROZEN' || ($acc['can_pay'] ?? true) === false;
          @endphp
          <div class="row g-3 mb-2">
            <div class="col-6">
              <p class="text-muted mb-0" style="font-size:11px">Saldo Tersedia</p>
              <p class="fw-bold mb-0" style="font-size:20px;color:{{ $available > 0 ? '#047857' : '#b91c1c' }}">
                {{ number_format($available, 2, ',', '.') }}
              </p>
            </div>
            <div class="col-6">
              <p class="text-muted mb-0" style="font-size:11px">Belum Dibayar</p>
              <p class="fw-bold mb-0" style="font-size:20px;color:{{ $unpaid > 0 ? '#b45309' : '#111827' }}">
                {{ number_format($unpaid, 2, ',', '.') }}
              </p>
            </div>
          </div>
          @if ($restricted)
            <div class="rounded-3 px-3 py-2 mb-2" style="background:#fef2f2;border:1px solid #fecaca">
              <p class="mb-0" style="font-size:12px;color:#b91c1c">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Akun dibatasi ({{ $acc['restriction_level'] ?? 'FROZEN' }}) — VM berisiko tidak bisa dibuat/berjalan sampai top-up.
              </p>
            </div>
          @endif
          <p class="text-muted mb-0" style="font-size:11px">
            Billing Account: <span class="fw-medium text-dark">{{ $acc['title'] ?? '—' }}</span>
            (ID: {{ $acc['id'] ?? '—' }})
          </p>
        @endif
      </div>
    </div>

    {{-- ══════ 3. NILAI HARGA JUAL ══════ --}}
    <div class="col-12">
      <div class="card border rounded-4 p-4">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <h2 class="small fw-bold text-dark mb-0"><i class="fa-solid fa-tags text-muted"></i> Nilai Harga Jual (kartu harga server ini)</h2>
          <a href="{{ route('admin.servers.edit', $server) }}" class="btn btn-outline-secondary btn-sm">Ubah Kartu Harga</a>
        </div>

        @php $rateEmpty = collect($rateCard)->filter()->isEmpty(); @endphp
        @if ($rateEmpty)
          <p class="mb-0 rounded-3 px-3 py-2" style="font-size:12px;color:#92400e;background:#fffbeb;border:1px solid #fde68a">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Kartu harga belum diisi — tagihan per jam TIDAK bisa dihitung otomatis untuk VM di server ini.
          </p>
        @else
          <div class="row g-2 mb-3">
            @foreach ($rateCard as $label => $value)
              <div class="col-6 col-lg-2">
                <div class="rounded-3 border p-2 h-100">
                  <p class="text-muted mb-0" style="font-size:10px">{{ $label }}</p>
                  <p class="fw-semibold text-dark mb-0" style="font-size:13px">
                    {{ $value ? 'Rp ' . number_format((float) $value, 4, ',', '.') : '—' }}
                  </p>
                </div>
              </div>
            @endforeach
          </div>
        @endif

        <p class="fw-bold text-muted mb-2 mt-3" style="font-size:11px;text-transform:uppercase;letter-spacing:.03em">Produk VPS di server ini</p>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0" style="font-size:13px">
            <thead>
              <tr class="small text-uppercase text-muted" style="background:#f8fafc">
                <th class="py-2">Produk</th>
                <th class="py-2">Spesifikasi</th>
                <th class="text-end py-2">Harga Jual / Jam</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($products as $product)
                @php
                  $spec = $product['spec'];
                  $hourly = ($spec && !$rateEmpty)
                    ? \App\Services\Billing\HourlyRateCalculator::calculate($server, $spec + ['backup_enabled' => $spec['backup_enabled'] ?? false, 'snapshot_gb' => $spec['snapshot_gb'] ?? 0])
                    : null;
                @endphp
                <tr>
                  <td class="py-2 fw-medium text-dark">{{ $product['name'] }}</td>
                  <td class="py-2 text-muted">
                    @if ($spec)
                      {{ $spec['vcpu'] ?? '?' }} vCPU · {{ $spec['ram'] ?? '?' }} MB · {{ $spec['disk'] ?? '?' }} GB ·
                      {{ $spec['os_name'] ?? '' }} {{ $spec['os_version'] ?? '' }}
                    @else
                      <span style="color:#b45309"><i class="fa-solid fa-triangle-exclamation"></i> Spesifikasi JSON belum diisi</span>
                    @endif
                  </td>
                  <td class="text-end py-2 fw-semibold text-dark">
                    {{ $hourly !== null ? 'Rp ' . number_format($hourly, 2, ',', '.') : '—' }}
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-4">Belum ada produk yang menunjuk ke server ini.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ══════ 4. JENIS OS ══════ --}}
    <div class="col-12 col-lg-7">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-3"><i class="fa-brands fa-linux text-muted"></i> Jenis OS Tersedia</h2>
        @if ($sections['images']['error'])
          {!! $err($sections['images']['error']) !!}
        @else
          <div class="table-responsive" style="max-height:20rem;overflow-y:auto">
            <table class="table table-sm align-middle mb-0" style="font-size:13px">
              <thead>
                <tr class="small text-uppercase text-muted" style="background:#f8fafc;position:sticky;top:0">
                  <th class="py-2">OS</th>
                  <th class="py-2">os_name</th>
                  <th class="py-2">Versi (os_version)</th>
                  <th class="py-2">Tipe</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($sections['images']['data'] as $img)
                  <tr>
                    <td class="py-2 fw-medium text-dark">{{ $img['display_name'] ?? '—' }}</td>
                    <td class="py-2 text-muted" style="font-family:monospace;font-size:11px">{{ $img['os_name'] ?? '' }}</td>
                    <td class="py-2 text-muted" style="font-size:11px">
                      @foreach ($img['versions'] ?? [] as $v)
                        <span class="d-inline-block me-1 mb-1 px-2 py-1 rounded-2" style="background:#f1f5f9;font-family:monospace">{{ $v['os_version'] ?? '' }}</span>
                      @endforeach
                    </td>
                    <td class="py-2">
                      <span class="badge {{ !empty($img['is_app_catalog']) ? 'badge-soft-warning' : 'badge-soft-secondary' }}">
                        {{ !empty($img['is_app_catalog']) ? 'App Catalog' : 'Plain OS' }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada image.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <p class="text-muted mt-2 mb-0" style="font-size:11px">
            Salin persis <code>os_name</code> &amp; <code>os_version</code> ke JSON spesifikasi produk (kolom Panel Package).
          </p>
        @endif
      </div>
    </div>

    {{-- ══════ 5. BATASAN PARAMETER VM ══════ --}}
    <div class="col-12 col-lg-5">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-1"><i class="fa-solid fa-sliders text-muted"></i> Batasan Spesifikasi VM</h2>
        <p class="text-muted mb-3" style="font-size:11px">
          Aturan dari IDCloudHost — paket VPS yang kamu jual HARUS di dalam rentang ini, kalau tidak provisioning akan ditolak.
        </p>
        @if ($sections['params']['error'])
          {!! $err($sections['params']['error']) !!}
        @else
          <div class="d-flex flex-column gap-2">
            @foreach ($sections['params']['data'] as $param)
              @if (in_array($param['parameter'] ?? '', ['vcpu', 'ram', 'disks']))
                <div class="d-flex align-items-center justify-content-between rounded-3 border px-3 py-2">
                  <div>
                    <p class="fw-medium text-dark mb-0" style="font-size:13px;text-transform:uppercase">{{ $param['parameter'] }}</p>
                    <p class="text-muted mb-0" style="font-size:11px">{{ $param['description'] ?? '' }}</p>
                  </div>
                  <span class="badge badge-soft-secondary text-nowrap">
                    {{ $param['min'] ?? '?' }} – {{ $param['max'] ?? '?' }}
                  </span>
                </div>
              @endif
            @endforeach
          </div>
        @endif
      </div>
    </div>

    {{-- ══════ 6. RESOURCE POOL & LOKASI ══════ --}}
    <div class="col-12 col-lg-6">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-server text-muted"></i> Kelas Server (Resource Pool)</h2>
        @if ($sections['pools']['error'])
          {!! $err($sections['pools']['error']) !!}
        @else
          @forelse ($sections['pools']['data'] as $pool)
            <div class="rounded-3 border px-3 py-2 mb-2">
              <div class="d-flex align-items-center gap-2">
                <p class="fw-medium text-dark mb-0" style="font-size:13px">{{ $pool['name'] ?? '—' }}</p>
                @if (!empty($pool['is_default_designated']))
                  <span class="badge badge-soft-success">Default</span>
                @endif
              </div>
              <p class="text-muted mb-0" style="font-size:11px">{{ $pool['description'] ?? '' }}</p>
              <p class="text-muted mb-0" style="font-size:10px;font-family:monospace">{{ $pool['uuid'] ?? '' }}</p>
            </div>
          @empty
            <p class="text-muted mb-0" style="font-size:13px">Tidak ada resource pool.</p>
          @endforelse
        @endif
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-location-dot text-muted"></i> Lokasi Datacenter</h2>
        @if ($sections['locations']['error'])
          {!! $err($sections['locations']['error']) !!}
        @else
          @forelse ($sections['locations']['data'] as $loc)
            <div class="d-flex align-items-center justify-content-between rounded-3 border px-3 py-2 mb-2">
              <div>
                <p class="fw-medium text-dark mb-0" style="font-size:13px">{{ $loc['display_name'] ?? '—' }}</p>
                <p class="text-muted mb-0" style="font-size:11px">{{ $loc['description'] ?? '' }}</p>
              </div>
              <div class="text-end">
                <span class="badge badge-soft-secondary" style="font-family:monospace">{{ $loc['slug'] ?? '' }}</span>
                @if (!empty($loc['is_default']))
                  <span class="d-block text-success mt-1" style="font-size:10px">default</span>
                @endif
              </div>
            </div>
          @empty
            <p class="text-muted mb-0" style="font-size:13px">Tidak ada lokasi.</p>
          @endforelse
        @endif
      </div>
    </div>

    {{-- ══════ 7. VM BERJALAN ══════ --}}
    <div class="col-12">
      <div class="card border rounded-4 overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
          <h2 class="small fw-bold text-dark mb-0"><i class="fa-solid fa-desktop text-muted"></i> VM di Akun Ini</h2>
          <span class="badge badge-soft-secondary">{{ is_array($sections['vms']['data'] ?? null) ? count($sections['vms']['data']) : 0 }} VM</span>
        </div>
        @if ($sections['vms']['error'])
          <div class="p-4">{!! $err($sections['vms']['error']) !!}</div>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px">
              <thead>
                <tr class="small text-uppercase text-muted" style="background:#f8fafc">
                  <th class="px-4 py-3">Nama</th>
                  <th class="py-3">Spek</th>
                  <th class="py-3">OS</th>
                  <th class="py-3">IP</th>
                  <th class="py-3">Backup</th>
                  <th class="py-3">Status</th>
                  <th class="text-end px-4 py-3">Biaya/Jam</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($sections['vms']['data'] as $vm)
                  @php
                    $vmDisk = collect($vm['storage'] ?? [])->firstWhere('primary', true)['size'] ?? 0;
                    $vmSpec = [
                      'vcpu' => $vm['vcpu'] ?? 0, 'ram' => $vm['memory'] ?? 0, 'disk' => $vmDisk,
                      'os_name' => $vm['os_name'] ?? '', 'backup_enabled' => (bool) ($vm['backup'] ?? false), 'snapshot_gb' => 0,
                    ];
                    $vmRate = $rateEmpty ? null : \App\Services\Billing\HourlyRateCalculator::calculate($server, $vmSpec);
                  @endphp
                  <tr>
                    <td class="px-4 py-3">
                      <p class="fw-medium text-dark mb-0">{{ $vm['name'] ?? '—' }}</p>
                      <p class="text-muted mb-0" style="font-size:10px;font-family:monospace">{{ $vm['uuid'] ?? '' }}</p>
                    </td>
                    <td class="py-3 text-muted">{{ $vm['vcpu'] ?? '?' }} vCPU · {{ $vm['memory'] ?? '?' }} MB · {{ $vmDisk }} GB</td>
                    <td class="py-3 text-muted" style="font-size:11px">{{ $vm['os_name'] ?? '' }} {{ $vm['os_version'] ?? '' }}</td>
                    <td class="py-3 text-muted" style="font-family:monospace;font-size:11px">
                      {{ $vm['public_ipv4'] ?? $vm['private_ipv4'] ?? '—' }}
                    </td>
                    <td class="py-3">
                      <span class="badge {{ !empty($vm['backup']) ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ !empty($vm['backup']) ? 'Aktif' : 'Off' }}
                      </span>
                    </td>
                    <td class="py-3">
                      <span class="badge {{ ($vm['status'] ?? '') === 'running' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ucfirst($vm['status'] ?? '—') }}
                      </span>
                    </td>
                    <td class="text-end px-4 py-3 fw-semibold text-dark">
                      {{ $vmRate !== null ? 'Rp ' . number_format($vmRate, 2, ',', '.') : '—' }}
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center text-muted py-5">Belum ada VM di akun ini.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    {{-- ══════ 8. DISK, IP, NETWORK ══════ --}}
    <div class="col-12 col-lg-4">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-hard-drive text-muted"></i> Block Storage</h2>
        @if ($sections['disks']['error'])
          {!! $err($sections['disks']['error']) !!}
        @else
          @forelse ($sections['disks']['data'] as $disk)
            <div class="d-flex align-items-center justify-content-between rounded-3 border px-3 py-2 mb-2">
              <div class="min-w-0">
                <p class="fw-medium text-dark text-truncate mb-0" style="font-size:13px">{{ $disk['display_name'] ?? '(tanpa nama)' }}</p>
                <p class="text-muted mb-0" style="font-size:10px">{{ count($disk['snapshots'] ?? []) }} snapshot</p>
              </div>
              <span class="badge badge-soft-secondary text-nowrap">{{ $disk['size_gb'] ?? '?' }} GB</span>
            </div>
          @empty
            <p class="text-muted mb-0" style="font-size:13px">Tidak ada disk terpisah.</p>
          @endforelse
        @endif
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-network-wired text-muted"></i> Floating IP</h2>
        @if ($sections['ips']['error'])
          {!! $err($sections['ips']['error']) !!}
        @else
          @forelse ($sections['ips']['data'] as $ip)
            <div class="d-flex align-items-center justify-content-between rounded-3 border px-3 py-2 mb-2">
              <p class="fw-medium text-dark mb-0" style="font-size:13px;font-family:monospace">{{ $ip['address'] ?? '—' }}</p>
              <span class="badge {{ !empty($ip['assigned_to']) ? 'badge-soft-success' : 'badge-soft-warning' }}">
                {{ !empty($ip['assigned_to']) ? 'Terpakai' : 'Nganggur' }}
              </span>
            </div>
          @empty
            <p class="text-muted mb-0" style="font-size:13px">Tidak ada floating IP.</p>
          @endforelse
        @endif
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card border rounded-4 p-4 h-100">
        <h2 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-diagram-project text-muted"></i> Private Network</h2>
        @if ($sections['networks']['error'])
          {!! $err($sections['networks']['error']) !!}
        @else
          @forelse ($sections['networks']['data'] as $net)
            <div class="rounded-3 border px-3 py-2 mb-2">
              <div class="d-flex align-items-center gap-2">
                <p class="fw-medium text-dark mb-0" style="font-size:13px">{{ $net['name'] ?? '—' }}</p>
                @if (!empty($net['is_default']))
                  <span class="badge badge-soft-success">Default</span>
                @endif
              </div>
              <p class="text-muted mb-0" style="font-size:11px;font-family:monospace">{{ $net['subnet'] ?? '' }}</p>
              <p class="text-muted mb-0" style="font-size:10px">{{ $net['resources_count'] ?? 0 }} resource</p>
            </div>
          @empty
            <p class="text-muted mb-0" style="font-size:13px">Tidak ada private network.</p>
          @endforelse
        @endif
      </div>
    </div>
  </div>

@endsection