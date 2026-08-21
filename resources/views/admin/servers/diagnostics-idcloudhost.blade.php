@extends('layouts.admin-bootstrap')

@section('title', 'Diagnosa — ' . $server->name)

@section('content')

  <a href="{{ route('admin.servers.index.bootstrap-preview') }}" class="text-decoration-none text-muted" style="font-size:12px">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Server
  </a>
  <h1 class="h4 fw-bold text-dark mt-1 mb-2">Diagnosa — {{ $server->name }}</h1>
  <p class="small text-muted mb-4">
    <i class="fa-solid fa-cloud"></i> IDCloudHost — data langsung dari API, cuma membaca, tidak mengubah apa pun.
  </p>

  <div class="row g-4">

    {{-- VM sungguhan di akun ini --}}
    <div class="col-12 col-lg-7">
      <div class="card border rounded-4 overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
          <h2 class="small fw-bold text-dark mb-0">VM di Akun Ini</h2>
          <span class="badge badge-soft-secondary">{{ is_array($vms) ? count($vms) : 0 }} VM</span>
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
