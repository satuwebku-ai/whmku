@extends('layouts.admin')

@section('title', 'Detail Hosting Account — ' . $account->domain)

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="{{ route('admin.hosting-accounts') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Hosting Account</a>
      <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $account->domain }}</h1>
    </div>
    <span class="badge badge-{{ $account->status }} !text-sm !px-3 !py-1">{{ ucfirst($account->status) }}</span>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Informasi Akun</h2>
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Klien</dt>
            <dd class="text-slate-700 font-medium">{{ $account->client->name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Paket</dt>
            <dd class="text-slate-700 font-medium">{{ $account->package }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Server</dt>
            <dd class="text-slate-700 font-medium">{{ $account->serverModel->name ?? 'Manual (tidak terhubung)' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Username Panel</dt>
            <dd class="text-slate-700 font-medium">{{ $account->username ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Harga</dt>
            <dd class="text-slate-700 font-medium">Rp {{ number_format($account->price, 0, ',', '.') }} / {{ str_replace('_', ' ', $account->billing_cycle) }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Jatuh Tempo</dt>
            <dd class="text-slate-700 font-medium">{{ $account->next_due_date?->format('d M Y') ?? '—' }}</dd>
          </div>
        </dl>

        @if ($account->provision_message)
          <div class="mt-4 pt-4 border-t border-slate-100 text-sm">
            <span class="text-slate-400 text-xs">Status Provisioning Terakhir</span>
            <p class="{{ $account->provision_status === 'provisioned' ? 'text-emerald-600' : 'text-rose-600' }} mt-0.5">{{ $account->provision_message }}</p>
          </div>
        @endif
      </div>

      @if ($account->orders->isNotEmpty())
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-3">Order Terkait</h2>
          <div class="divide-y divide-slate-100">
            @foreach ($account->orders as $order)
              <a href="{{ route('admin.orders.details', $order) }}" class="flex items-center justify-between py-2.5 text-sm hover:text-accent">
                <span>#{{ $order->order_number }} — {{ $order->product_name }}</span>
                <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
              </a>
            @endforeach
          </div>
        </div>
      @endif

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Catatan Internal</h2>
        <form method="POST" action="{{ route('admin.hosting-account.notes') }}">
          @csrf
          <input type="hidden" name="hosting_account_id" value="{{ $account->id }}">
          <textarea name="internal_notes" rows="4" class="form-input" placeholder="Catatan staf tentang akun ini...">{{ old('internal_notes', $account->internal_notes) }}</textarea>
          <button type="submit" class="btn btn-outline mt-3"><i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Catatan</button>
        </form>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Aksi</h2>
        <div class="space-y-2">
          @if ($account->serverModel && $account->username)
            @if ($account->status !== 'suspended' && $account->status !== 'terminated')
              <form method="POST" action="{{ route('admin.hosting-accounts.suspend', $account) }}" data-confirm="Suspend akun ini di server?" data-confirm-title="Suspend Layanan" data-confirm-style="warn" data-confirm-label="Ya, Suspend" >
                @csrf
                <button type="submit" class="w-full btn btn-outline !justify-start !text-amber-600 !border-amber-200 hover:!bg-amber-50"><i class="fa-solid fa-pause text-xs"></i> Suspend</button>
              </form>
            @endif
            @if ($account->status === 'suspended')
              <form method="POST" action="{{ route('admin.hosting-accounts.unsuspend', $account) }}">
                @csrf
                <button type="submit" class="w-full btn btn-primary !justify-start"><i class="fa-solid fa-play text-xs"></i> Unsuspend</button>
              </form>
            @endif
            @if ($account->status !== 'terminated')
              <form method="POST" action="{{ route('admin.hosting-accounts.terminate', $account) }}" data-confirm="Terminate akun ini? Akan DIHAPUS dari server dan tidak bisa dikembalikan." data-confirm-title="Hapus Data" data-confirm-style="danger" data-confirm-label="Ya, Hapus" >
                @csrf
                <button type="submit" class="w-full btn btn-danger-soft !justify-start"><i class="fa-solid fa-power-off text-xs"></i> Terminate</button>
              </form>
            @endif
          @else
            <p class="text-xs text-slate-400">Akun manual (tidak terhubung ke server), aksi API tidak tersedia. Ubah status lewat form Edit.</p>
          @endif
          <a href="{{ route('admin.hosting-account.edit.page', $account) }}" class="w-full btn btn-outline !justify-start"><i class="fa-regular fa-pen-to-square text-xs"></i> Edit Data</a>
        </div>
      </div>
    </div>
  </div>

@endsection
