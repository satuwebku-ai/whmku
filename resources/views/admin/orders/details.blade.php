@extends('layouts.admin')

@section('title', 'Detail Order #' . $order->order_number)

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="{{ route('admin.orders') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Order</a>
      <h1 class="text-xl font-bold text-slate-800 mt-1">Order #{{ $order->order_number }}</h1>
    </div>
    <span class="badge badge-{{ $order->status }} !text-sm !px-3 !py-1">{{ ucfirst($order->status) }}</span>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Informasi Order</h2>
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Klien</dt>
            <dd class="text-slate-700 font-medium">{{ $order->client->name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Produk</dt>
            <dd class="text-slate-700 font-medium">{{ $order->product_name }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Tipe</dt>
            <dd class="text-slate-700 font-medium capitalize">{{ $order->order_type }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Jumlah</dt>
            <dd class="text-slate-700 font-medium">Rp {{ number_format($order->amount, 0, ',', '.') }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Hosting Account Terkait</dt>
            <dd class="text-slate-700 font-medium">
              @if ($order->hostingAccount)
                <a href="{{ route('admin.hosting-accounts.details', $order->hostingAccount) }}" class="text-accent hover:underline">{{ $order->hostingAccount->domain }}</a>
              @else
                —
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Domain Terkait</dt>
            <dd class="text-slate-700 font-medium">
              @if ($order->domain)
                <a href="{{ route('admin.domains.details', $order->domain) }}" class="text-accent hover:underline">{{ $order->domain->domain_name }}</a>
                <span class="badge badge-{{ $order->domain->provision_status === 'registered' ? 'active' : ($order->domain->provision_status === 'failed' ? 'suspended' : 'pending') }} ml-1">{{ $order->domain->provision_status }}</span>
              @else
                —
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Invoice Terkait</dt>
            <dd class="text-slate-700 font-medium">
              @php $orderInvoice = $order->resolvedInvoice(); @endphp
              @if ($orderInvoice)
                <a href="{{ route('admin.invoices.details', $orderInvoice) }}" class="text-accent hover:underline">{{ $orderInvoice->invoice_number }}</a>
              @else
                —
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Dibuat</dt>
            <dd class="text-slate-700 font-medium">{{ $order->created_at->format('d M Y H:i') }}</dd>
          </div>
        </dl>
      </div>

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Catatan Internal</h2>
        <form method="POST" action="{{ route('admin.order.notes') }}">
          @csrf
          <input type="hidden" name="order_id" value="{{ $order->id }}">
          <textarea name="internal_notes" rows="4" class="form-input" placeholder="Catatan staf tentang order ini (tidak terlihat klien)...">{{ old('internal_notes', $order->internal_notes) }}</textarea>
          <button type="submit" class="btn btn-outline mt-3"><i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Catatan</button>
        </form>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Aksi</h2>
        <div class="space-y-2">
          @if ($order->status !== 'active')
            <form method="POST" action="{{ route('admin.order.accept') }}">
              @csrf
              <input type="hidden" name="order_id" value="{{ $order->id }}">
              <button type="submit" class="w-full btn btn-primary !justify-start"><i class="fa-solid fa-check text-xs"></i> Terima & Aktifkan</button>
            </form>
          @endif
          @if ($order->status !== 'pending')
            <form method="POST" action="{{ route('admin.order.mark.pending') }}">
              @csrf
              <input type="hidden" name="order_id" value="{{ $order->id }}">
              <button type="submit" class="w-full btn btn-outline !justify-start"><i class="fa-solid fa-clock text-xs"></i> Kembalikan ke Pending</button>
            </form>
          @endif
          @if ($order->status !== 'cancelled')
            <form method="POST" action="{{ route('admin.order.cancel') }}" data-confirm="Batalkan order ini?" data-confirm-title="Batalkan" data-confirm-style="warn" data-confirm-label="Ya, Batalkan" >
              @csrf
              <input type="hidden" name="order_id" value="{{ $order->id }}">
              <button type="submit" class="w-full btn btn-danger-soft !justify-start"><i class="fa-solid fa-xmark text-xs"></i> Batalkan Order</button>
            </form>
          @endif
          <a href="{{ route('admin.order.edit.page', $order) }}" class="w-full btn btn-outline !justify-start"><i class="fa-regular fa-pen-to-square text-xs"></i> Edit Data Order</a>
        </div>
      </div>
    </div>
  </div>

@endsection
