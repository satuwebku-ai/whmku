@extends('layouts.admin')

@section('title', 'Detail Domain — ' . $domain->domain_name)

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="{{ route('admin.domains') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Domain</a>
      <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $domain->domain_name }}</h1>
    </div>
    <span class="badge badge-{{ $domain->status === 'expired' ? 'suspended' : $domain->status }} !text-sm !px-3 !py-1">{{ ucfirst($domain->status) }}</span>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Informasi Domain</h2>
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Klien</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->client->name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Registrar</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->registrar->name ?? 'Manual' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Tanggal Register</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->register_date?->format('d M Y') ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Jatuh Tempo</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->expiry_date?->format('d M Y') ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Auto Renew</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->auto_renew ? 'Ya' : 'Tidak' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">WHOIS Privacy</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->whois_privacy ? 'Aktif' : 'Nonaktif' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Order Terkait</dt>
            <dd class="text-slate-700 font-medium">
              @if ($domain->order)
                <a href="{{ route('admin.orders.details', $domain->order) }}" class="text-accent hover:underline">#{{ $domain->order->order_number }}</a>
              @else
                —
              @endif
            </dd>
          </div>
        </dl>

        @if ($domain->provision_message)
          <div class="mt-4 pt-4 border-t border-slate-100 text-sm">
            <span class="text-slate-400 text-xs">Status Registrasi Terakhir</span>
            <p class="{{ $domain->provision_status === 'registered' ? 'text-emerald-600' : 'text-rose-600' }} mt-0.5">{{ $domain->provision_message }}</p>
          </div>
        @endif
      </div>

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Catatan Internal</h2>
        <form method="POST" action="{{ route('admin.domain.notes') }}">
          @csrf
          <input type="hidden" name="domain_id" value="{{ $domain->id }}">
          <textarea name="internal_notes" rows="4" class="form-input" placeholder="Catatan staf tentang domain ini...">{{ old('internal_notes', $domain->internal_notes) }}</textarea>
          <button type="submit" class="btn btn-outline mt-3"><i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Catatan</button>
        </form>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Aksi</h2>
        <div class="space-y-2">
          @if ($domain->registrar)
            <form method="POST" action="{{ route('admin.domains.renew', $domain) }}" data-confirm="Perpanjang domain ini 1 tahun via registrar?" data-confirm-title="Perpanjang Domain" data-confirm-style="info" data-confirm-label="Ya, Perpanjang" >
              @csrf
              <button type="submit" class="w-full btn btn-primary !justify-start"><i class="fa-solid fa-rotate text-xs"></i> Perpanjang 1 Tahun</button>
            </form>
          @endif
          @if ($domain->status !== 'cancelled')
            <form method="POST" action="{{ route('admin.domain.cancel') }}" data-confirm="Batalkan domain ini?" data-confirm-title="Batalkan" data-confirm-style="warn" data-confirm-label="Ya, Batalkan" >
              @csrf
              <input type="hidden" name="domain_id" value="{{ $domain->id }}">
              <button type="submit" class="w-full btn btn-danger-soft !justify-start"><i class="fa-solid fa-xmark text-xs"></i> Batalkan</button>
            </form>
          @endif
          <a href="{{ route('admin.domain.edit.page', $domain) }}" class="w-full btn btn-outline !justify-start"><i class="fa-regular fa-pen-to-square text-xs"></i> Edit Data</a>
        </div>
      </div>
    </div>
  </div>

@endsection
