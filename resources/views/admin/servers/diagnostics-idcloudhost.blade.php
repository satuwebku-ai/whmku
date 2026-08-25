@extends('layouts.admin-bootstrap')

@section('title', 'Diagnosa — ' . $server->name)

@section('content')

  <a href="{{ route('admin.servers.index') }}" class="text-decoration-none text-muted" style="font-size:12px">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Server
  </a>
  <h1 class="h4 fw-bold text-dark mt-1 mb-2">Diagnosa — {{ $server->name }}</h1>
  <p class="small text-muted mb-4">
    <i class="fa-solid fa-cloud"></i> IDCloudHost — data langsung dari API, cuma membaca, tidak mengubah apa pun.
  </p>
@if (!empty($vmsByLocation))
  <div class="card border rounded-4 overflow-hidden mb-4">
    <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
      <div>
        <h2 class="small fw-bold text-dark mb-0">Cek Lokasi (Semua Datacenter)</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:12px">
          Server ini dikonfigurasi ke lokasi: <b>{{ $configuredSlug ?: 'default akun' }}</b>.
          VM list API IDCloudHost per-lokasi — kalau VM "hilang" di panel kanan, cek dulu apakah ada di lokasi lain di bawah ini.
        </p>
      </div>
      <span class="badge badge-soft-secondary">{{ $totalVmsAllLocations }} VM total (semua lokasi)</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:13px">
        <thead>
          <tr class="small text-uppercase text-muted" style="background:#f8fafc">
            <th class="px-4 py-3">Lokasi</th>
            <th class="py-3">Slug</th>
            <th class="py-3">Jumlah VM</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($vmsByLocation as $loc)
            <tr>
              <td class="px-4 py-3">
                {{ $loc['name'] }}
                @if ($loc['is_default'])
                  <span class="badge badge-soft-secondary ms-1" style="font-size:10px">Default Akun</span>
                @endif
                @if ($configuredSlug === $loc['slug'] || (!$configuredSlug && $loc['is_default']))
                  <span class="badge badge-soft-success ms-1" style="font-size:10px">Lokasi Server Ini</span>
                @endif
              </td>
              <td class="py-3"><code style="font-size:11px">{{ $loc['slug'] }}</code></td>
              <td class="py-3">
                @if ($loc['error'])
                  <span class="text-danger" style="font-size:11px">Gagal: {{ $loc['error'] }}</span>
                @else
                  {{ $loc['vm_count'] }}
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
<div class="card border rounded-4 p-4 mb-4">
  <h2 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-wallet"></i> Billing Account IDCloudHost</h2>

  @if ($billingError)
    <p class="mb-0" style="font-size:13px;color:#b91c1c"><i class="fa-solid fa-circle-exclamation"></i> {{ $billingError }}</p>
  @else
    @php $restriction = $billingAccount['restriction_level'] ?? null; $isNormal = in_array($restriction, [null, '', 'NONE', 'OK']); @endphp
    <div class="row g-3">
      <div class="col-6 col-md-3">
        <p class="text-muted mb-1" style="font-size:11px;text-transform:uppercase">Sisa Deposit</p>
        <p class="fw-bold text-dark mb-0" style="font-size:18px">{{ number_format($billingAccount['credit_amount'] ?? 0, 2) }}</p>
      </div>
      <div class="col-6 col-md-3">
        <p class="text-muted mb-1" style="font-size:11px;text-transform:uppercase">Tagihan Belum Dibayar</p>
        <p class="fw-bold mb-0" style="font-size:18px;color:{{ ($billingAccount['unpaid_amount'] ?? 0) > 0 ? '#b91c1c' : '#111827' }}">
          {{ number_format($billingAccount['unpaid_amount'] ?? 0, 2) }}
        </p>
      </div>
      <div class="col-6 col-md-3">
        <p class="text-muted mb-1" style="font-size:11px;text-transform:uppercase">Status Akun</p>
        <span class="badge {{ $isNormal ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $restriction ?: 'Normal' }}</span>
      </div>
      <div class="col-6 col-md-3">
        <p class="text-muted mb-1" style="font-size:11px;text-transform:uppercase">Aktif</p>
        <span class="badge {{ ($billingAccount['is_active'] ?? true) ? 'badge-soft-success' : 'badge-soft-secondary' }}">
          {{ ($billingAccount['is_active'] ?? true) ? 'Ya' : 'Tidak' }}
        </span>
      </div>
    </div>
    @unless ($isNormal)
      <p class="mt-3 mb-0" style="font-size:12px;color:#b45309">
        <i class="fa-solid fa-triangle-exclamation"></i> Akun dalam status <b>{{ $restriction }}</b> — create/modify VM kemungkinan ditolak API sampai ini diselesaikan (biasanya karena tagihan belum dibayar).
      </p>
    @endunless
  @endif
</div>
{{-- Perbandingan Harga: cost IDCloudHost vs rate card jual Lumora --}}
<div class="card border rounded-4 overflow-hidden mb-4">
  <div class="px-4 py-3 border-bottom">
    <h2 class="small fw-bold text-dark mb-0">Perbandingan Harga (per jam)</h2>
    <p class="text-muted mb-0 mt-1" style="font-size:12px">Cost dari IDCloudHost vs harga jual di rate card server ini — kalau kolom Margin merah, harga jual sudah di bawah cost.</p>
  </div>

  @if ($pricingError)
    <div class="p-4"><p class="mb-0" style="font-size:13px;color:#b91c1c"><i class="fa-solid fa-circle-exclamation"></i> {{ $pricingError }}</p></div>
  @else
    @php
      $rows = [
        ['label' => 'vCPU', 'cost' => $costPolicy['vcpu_hour'], 'sell' => $server->price_per_vcpu_hour],
        ['label' => 'RAM (GB)', 'cost' => $costPolicy['ram_gb_hour'], 'sell' => $server->price_per_ram_gb_hour],
        ['label' => 'Storage (GB)', 'cost' => $costPolicy['storage_gb_hour'], 'sell' => $server->price_per_storage_gb_hour],
        ['label' => 'Backup (GB)', 'cost' => $costPolicy['backup_gb_hour'], 'sell' => $server->price_per_backup_gb_hour],
        ['label' => 'Snapshot (GB)', 'cost' => $costPolicy['snapshot_gb_hour'], 'sell' => $server->price_per_snapshot_gb_hour],
        ['label' => 'Lisensi Windows / vCPU', 'cost' => $costPolicy['windows_vcpu_hour'], 'sell' => $server->price_windows_license_per_vcpu_hour],
      ];
    @endphp
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:13px">
        <thead>
          <tr class="small text-uppercase text-muted" style="background:#f8fafc">
            <th class="px-4 py-3">Komponen</th>
            <th class="py-3">Cost IDCloudHost</th>
            <th class="py-3">Harga Jual (Rate Card)</th>
            <th class="py-3">Margin</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rows as $r)
            @php $margin = ($r['sell'] !== null && $r['cost'] !== null) ? $r['sell'] - $r['cost'] : null; @endphp
            <tr>
              <td class="px-4 py-3">{{ $r['label'] }}</td>
              <td class="py-3 text-muted">{{ $r['cost'] !== null ? number_format($r['cost'], 6) : '—' }}</td>
              <td class="py-3 text-muted">{{ $r['sell'] !== null ? number_format($r['sell'], 6) : '(kosong)' }}</td>
              <td class="py-3">
                @if ($margin === null)
                  <span class="text-muted">—</span>
                @elseif ($margin < 0)
                  <span class="text-danger fw-medium">{{ number_format($margin, 6) }}</span>
                @else
                  <span class="text-success">{{ number_format($margin, 6) }}</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

<div class="row g-4 mb-4">
  {{-- Usage bulan berjalan --}}
  <div class="col-12 col-lg-6">
    <div class="card border rounded-4 overflow-hidden h-100">
      <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
        <h2 class="small fw-bold text-dark mb-0">Usage Bulan Berjalan</h2>
        @if (!$usageError)
          <span class="badge badge-soft-secondary">Total: {{ number_format($usageTotalCost, 4) }}</span>
        @endif
      </div>
      @if ($usageError)
        <div class="p-4"><p class="mb-0" style="font-size:13px;color:#b91c1c"><i class="fa-solid fa-circle-exclamation"></i> {{ $usageError }}</p></div>
      @else
        <div>
          @forelse ($usage as $u)
            <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
              <div>
                <p class="small text-dark mb-0">{{ $u['description'] ?? '—' }}</p>
                <p class="text-muted mb-0" style="font-size:11px">{{ $u['hours'] ?? 0 }} jam × {{ $u['price'] ?? 0 }}/{{ $u['price_unit'] ?? 'h' }}</p>
              </div>
              <span class="fw-medium text-dark" style="font-size:13px">{{ number_format($u['cost'] ?? 0, 4) }}</span>
            </div>
          @empty
            <p class="text-center text-muted small py-4 mb-0">Belum ada pemakaian tercatat bulan ini.</p>
          @endforelse
        </div>
      @endif
    </div>
  </div>

  {{-- Floating IP nganggur --}}
  <div class="col-12 col-lg-6">
    <div class="card border rounded-4 overflow-hidden h-100">
      <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
        <h2 class="small fw-bold text-dark mb-0">Floating IP Nganggur</h2>
        <span class="badge badge-soft-secondary">{{ count($orphanFloatingIps) }} dari {{ count($floatingIps) }}</span>
      </div>
      @if ($ipError)
        <div class="p-4"><p class="mb-0" style="font-size:13px;color:#b91c1c"><i class="fa-solid fa-circle-exclamation"></i> {{ $ipError }}</p></div>
      @else
        <div>
          @forelse ($orphanFloatingIps as $ip)
            <div class="px-4 py-3 border-bottom">
              <p class="small text-dark mb-0" style="font-family:monospace">{{ $ip['address'] ?? '—' }}</p>
              <p class="text-muted mb-0" style="font-size:11px">{{ $ip['name'] ?? '(tanpa nama)' }} — tidak terpasang ke resource apa pun, tetap kena biaya.</p>
            </div>
          @empty
            <p class="text-center text-muted small py-4 mb-0">Semua Floating IP sudah terpasang. Tidak ada yang nganggur.</p>
          @endforelse
        </div>
      @endif
    </div>
  </div>
</div>

{{-- Resource Pool tersedia --}}
<div class="card border rounded-4 p-4 mb-4">
  <h2 class="small fw-bold text-dark mb-2">Resource Pool di Lokasi Ini</h2>
  @if ($poolError)
    <p class="text-muted small mb-0"><i class="fa-solid fa-circle-exclamation"></i> {{ $poolError }}</p>
  @elseif (count($resourcePools))
    <div class="d-flex flex-wrap gap-2">
      @foreach ($resourcePools as $pool)
        <span class="px-2 py-1 rounded-2" style="font-size:11px;background:#f1f5f9;color:#475569">
          {{ $pool['name'] ?? '?' }}
          @if ($pool['is_default_designated'] ?? false)
            <span class="text-muted">(default)</span>
          @endif
        </span>
      @endforeach
    </div>
  @else
    <p class="text-muted small mb-0">Tidak ada resource pool ditemukan di lokasi ini.</p>
  @endif
</div>
  <div class="row g-4">

    {{-- VM sungguhan di akun ini --}}
    <div class="col-12 col-lg-7">
      <div class="card border rounded-4 overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
  <h2 class="small fw-bold text-dark mb-0">VM di Akun Ini</h2>
  <div class="d-flex align-items-center gap-2">
    <span class="badge badge-soft-success">{{ $vmRunningCount }} Jalan</span>
    <span class="badge badge-soft-secondary">{{ $vmStoppedCount }} Berhenti</span>
  </div>
</div>

        @if ($vmError)
          <div class="p-4">
            <p class="mb-0" style="font-size:14px;color:#b91c1c"><i class="fa-solid fa-circle-exclamation"></i> {{ $vmError }}</p>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px">
              <thead>
                <tr class="small text-uppercase text-muted" style="background:#f8fafc">
                  <th class="px-4 py-3">Nama</th>
                  <th class="py-3">Spek</th>
                  <th class="py-3">IP</th>
                  <th class="py-3">Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($vms as $vm)
                  <tr>
                    <td class="px-4 py-3">
                      <p class="fw-medium text-dark mb-0">{{ $vm['name'] ?? '—' }}</p>
                      <p class="text-muted mb-0" style="font-size:11px;font-family:monospace">{{ $vm['uuid'] ?? '' }}</p>
                    </td>
                    <td class="py-3 text-muted">
                      {{ $vm['vcpu'] ?? '?' }} vCPU · {{ $vm['memory'] ?? '?' }} MB
                      <span class="d-block" style="font-size:11px">{{ $vm['os_name'] ?? '' }} {{ $vm['os_version'] ?? '' }}</span>
                    </td>
                    <td class="py-3 text-muted" style="font-family:monospace;font-size:11px">
                      {{ $vm['public_ipv6'] ?? $vm['private_ipv4'] ?? '—' }}
                    </td>
                    <td class="py-3">
                      <span class="badge {{ ($vm['status'] ?? '') === 'running' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ucfirst($vm['status'] ?? '—') }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted py-5">Belum ada VM di akun ini.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    <div class="col-12 col-lg-5 d-flex flex-column gap-4">

      {{-- Produk VPS yang dikonfigurasi jual --}}
      <div class="card border rounded-4 overflow-hidden">
        <div class="px-4 py-3 border-bottom">
          <h2 class="small fw-bold text-dark mb-0">Produk VPS di Server Ini</h2>
        </div>
        <div>
          @forelse ($products as $product)
            <div class="px-4 py-3 border-bottom">
              <p class="fw-medium text-dark mb-1" style="font-size:14px">{{ $product['name'] }}</p>
              @if ($product['spec'])
                <p class="text-muted mb-0" style="font-size:11px">
                  {{ $product['spec']['vcpu'] ?? '?' }} vCPU ·
                  {{ $product['spec']['ram'] ?? '?' }} MB RAM ·
                  {{ $product['spec']['disk'] ?? '?' }} GB Disk ·
                  {{ $product['spec']['os_name'] ?? '' }} {{ $product['spec']['os_version'] ?? '' }}
                </p>
              @else
                <p class="mb-0" style="font-size:11px;color:#b45309">
                  <i class="fa-solid fa-triangle-exclamation"></i> Spesifikasi belum diisi (kolom Panel Package kosong/tidak valid).
                </p>
              @endif
            </div>
          @empty
            <p class="text-center text-muted py-4 mb-0" style="font-size:13px">
              Belum ada produk yang menunjuk ke server ini.
            </p>
          @endforelse
        </div>
      </div>

      {{-- OS yang tersedia --}}
      <div class="card border rounded-4 overflow-hidden">
        <div class="px-4 py-3 border-bottom">
          <h2 class="small fw-bold text-dark mb-0">OS Tersedia untuk Produk Baru</h2>
        </div>

        @if ($osError)
          <div class="p-4">
            <p class="mb-0" style="font-size:13px;color:#b91c1c"><i class="fa-solid fa-circle-exclamation"></i> {{ $osError }}</p>
          </div>
        @else
          <div class="p-3">
            @forelse ($osImages as $os)
              <div class="d-inline-block me-2 mb-2 px-2 py-1 rounded-2" style="font-size:11px;background:#f1f5f9;color:#475569">
                {{ $os['display_name'] ?? $os['os_name'] ?? '?' }}
                @if (!empty($os['versions']))
                  <span class="text-muted">({{ collect($os['versions'])->pluck('display_name')->implode(', ') }})</span>
                @endif
              </div>
            @empty
              <p class="text-muted mb-0" style="font-size:13px">Tidak bisa mengambil daftar OS.</p>
            @endforelse
          </div>
          <p class="text-muted px-3 pb-3 mb-0" style="font-size:11px">
            Salin persis <code>os_name</code>/<code>os_version</code> ini ke JSON spesifikasi produk (kolom Panel Package),
            mis. <code>{"vcpu":2,"ram":2048,"disk":40,"os_name":"ubuntu","os_version":"22.04"}</code>.
          </p>
        @endif
      </div>
    </div>
  </div>

@endsection
