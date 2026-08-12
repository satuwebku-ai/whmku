@extends('layouts.admin')

@section('title', 'Profil Klien — ' . $client->name)

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="{{ route('admin.clients') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Klien</a>
      <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $client->name }}</h1>
      @if ($client->company)
        <p class="text-sm text-slate-500">{{ $client->company }}</p>
      @endif
    </div>
    <span class="badge badge-{{ $client->status }} !text-sm !px-3 !py-1">{{ $client->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
  </div>

  <div class="grid sm:grid-cols-3 gap-4 mb-5">
    <div class="card p-4 text-center">
      <p class="text-2xl font-bold text-slate-800">{{ $client->hosting_accounts_count }}</p>
      <p class="text-xs text-slate-500 mt-0.5">Hosting Account</p>
    </div>
    <div class="card p-4 text-center">
      <p class="text-2xl font-bold text-slate-800">{{ $client->orders_count }}</p>
      <p class="text-xs text-slate-500 mt-0.5">Order</p>
    </div>
    <div class="card p-4 text-center">
      <p class="text-2xl font-bold text-slate-800">{{ $client->invoices_count }}</p>
      <p class="text-xs text-slate-500 mt-0.5">Invoice</p>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Informasi Kontak</h2>
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
          <div><dt class="text-slate-400 text-xs mb-0.5">Email</dt><dd class="text-slate-700 font-medium">{{ $client->email }}</dd></div>
          <div><dt class="text-slate-400 text-xs mb-0.5">Telepon</dt><dd class="text-slate-700 font-medium">{{ $client->phone ?? '—' }}</dd></div>
          <div class="sm:col-span-2"><dt class="text-slate-400 text-xs mb-0.5">Alamat</dt><dd class="text-slate-700 font-medium">{{ $client->address ?? '—' }}, {{ $client->city }}, {{ $client->country }}</dd></div>
        </dl>
      </div>

      @if ($client->orders->isNotEmpty())
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-3">Order Terbaru</h2>
          <div class="divide-y divide-slate-100">
            @foreach ($client->orders as $order)
              <a href="{{ route('admin.orders.details', $order) }}" class="flex items-center justify-between py-2.5 text-sm hover:text-accent">
                <span>#{{ $order->order_number }} — {{ $order->product_name }}</span>
                <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
              </a>
            @endforeach
          </div>
        </div>
      @endif

      @if ($client->invoices->isNotEmpty())
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-3">Invoice Terbaru</h2>
          <div class="divide-y divide-slate-100">
            @foreach ($client->invoices as $invoice)
              <a href="{{ route('admin.invoices.details', $invoice) }}" class="flex items-center justify-between py-2.5 text-sm hover:text-accent">
                <span>{{ $invoice->invoice_number }}</span>
                <span class="badge badge-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
              </a>
            @endforeach
          </div>
        </div>
      @endif

      @if ($client->hostingAccounts->isNotEmpty())
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-3">Hosting Account</h2>
          <div class="divide-y divide-slate-100">
            @foreach ($client->hostingAccounts as $account)
              <a href="{{ route('admin.hosting-accounts.details', $account) }}" class="flex items-center justify-between py-2.5 text-sm hover:text-accent">
                <span>{{ $account->domain }}</span>
                <span class="badge badge-{{ $account->status }}">{{ ucfirst($account->status) }}</span>
              </a>
            @endforeach
          </div>
        </div>
      @endif

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Catatan Internal</h2>
        <form method="POST" action="{{ route('admin.client.notes') }}">
          @csrf
          <input type="hidden" name="client_id" value="{{ $client->id }}">
          <textarea name="internal_notes" rows="4" class="form-input" placeholder="Catatan staf tentang klien ini...">{{ old('internal_notes', $client->internal_notes) }}</textarea>
          <button type="submit" class="btn btn-outline mt-3"><i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Catatan</button>
        </form>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-1">Saldo Klien</h2>
        <p class="text-2xl font-bold text-slate-800 mb-4">Rp {{ number_format((float) $client->balance, 0, ',', '.') }}</p>

        <form method="POST" action="{{ route('admin.client.balance.adjust', $client) }}" class="space-y-2 mb-4">
          @csrf
          <div class="grid grid-cols-2 gap-2">
            <input type="number" name="amount" step="1000" placeholder="Nominal (- untuk kurangi)" required
                   class="form-input !text-xs col-span-2">
          </div>
          <input type="text" name="description" placeholder="Alasan (mis. Refund invoice INV-2026-0003)" required
                 class="form-input !text-xs">
          @error('amount') <p class="form-error">{{ $message }}</p> @enderror
          @error('description') <p class="form-error">{{ $message }}</p> @enderror
          <button type="submit" class="btn btn-outline w-full !text-xs">
            <i class="fa-solid fa-sliders text-xs"></i> Sesuaikan Saldo
          </button>
        </form>

        @if ($client->balanceLogs->isNotEmpty())
          <div class="border-t border-slate-100 pt-3 space-y-2 max-h-56 overflow-y-auto">
            @foreach ($client->balanceLogs as $log)
              <div class="flex items-center justify-between text-xs">
                <div class="min-w-0">
                  <p class="text-slate-600 truncate">{{ $log->description }}</p>
                  <p class="text-slate-400">{{ $log->created_at->format('d M Y H:i') }}</p>
                </div>
                <span class="font-medium shrink-0 ml-2 {{ $log->amount >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                  {{ $log->amount >= 0 ? '+' : '' }}{{ number_format($log->amount, 0, ',', '.') }}
                </span>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-xs text-slate-400 border-t border-slate-100 pt-3">Belum ada riwayat saldo.</p>
        @endif
      </div>

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Aksi</h2>
        <div class="space-y-2">
          <form method="POST" action="{{ route('admin.client.status') }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <button type="submit" class="w-full btn {{ $client->status === 'active' ? 'btn-danger-soft' : 'btn-primary' }} !justify-start">
              <i class="fa-solid {{ $client->status === 'active' ? 'fa-user-slash' : 'fa-user-check' }} text-xs"></i>
              {{ $client->status === 'active' ? 'Nonaktifkan Klien' : 'Aktifkan Klien' }}
            </button>
          </form>
          <a href="{{ route('admin.client.edit.page', $client) }}" class="w-full btn btn-outline !justify-start"><i class="fa-regular fa-pen-to-square text-xs"></i> Edit Data Klien</a>

          @if (auth('admin')->user()->canManage())
            <form method="POST" action="{{ route('admin.client.impersonate', $client) }}"
                  data-confirm="Anda akan masuk ke akun {{ $client->name }} ({{ $client->email }}). Aktivitas ini tercatat di log Aktivitas. Lanjutkan?"
                  data-confirm-title="Login sebagai Klien" data-confirm-style="warn" data-confirm-label="Ya, Masuk">
              @csrf
              <button type="submit" class="w-full btn btn-outline !justify-start !text-amber-700 !border-amber-200 hover:!bg-amber-50">
                <i class="fa-solid fa-user-shield text-xs"></i> Login sebagai Klien Ini
              </button>
            </form>
            <p class="text-[11px] text-slate-400 px-1">
              Berguna untuk troubleshooting. Tercatat di menu Aktivitas dan Percobaan Login.
            </p>
          @endif
        </div>
      </div>
    </div>
  </div>

@endsection
