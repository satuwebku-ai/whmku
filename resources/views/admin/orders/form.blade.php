@extends('layouts.admin')

@section('title', $order->exists ? 'Edit Order' : 'Buat Order')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $order->exists ? 'Edit Order' : 'Buat Order Baru' }}</h1>
    @if ($order->exists)
      <p class="text-sm text-slate-500 mt-1">Nomor order: <span class="font-medium text-slate-700">#{{ $order->order_number }}</span></p>
    @else
      <p class="text-sm text-slate-500 mt-1">Nomor order akan dibuat otomatis.</p>
    @endif
  </div>

  <form method="POST" action="{{ $order->exists ? route('admin.order.update', $order) : route('admin.order.add') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    @if ($order->exists) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Klien</label>
        <select name="client_id" class="form-input" required>
          <option value="">Pilih klien</option>
          @foreach ($clients as $client)
            <option value="{{ $client->id }}" @selected(old('client_id', $order->client_id) == $client->id)>{{ $client->name }}</option>
          @endforeach
        </select>
        @error('client_id') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Hosting Account Terkait (opsional)</label>
        <select name="hosting_account_id" class="form-input">
          <option value="">— Tidak terkait —</option>
          @foreach ($hostingAccounts as $ha)
            <option value="{{ $ha->id }}" @selected(old('hosting_account_id', $order->hosting_account_id) == $ha->id)>{{ $ha->domain }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama Produk</label>
        <input type="text" name="product_name" value="{{ old('product_name', $order->product_name) }}" placeholder="Cloud Hosting - Pro" class="form-input" required>
        @error('product_name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Tipe Order</label>
        <select name="order_type" class="form-input">
          <option value="hosting" @selected(old('order_type', $order->order_type) === 'hosting')>Hosting</option>
          <option value="domain" @selected(old('order_type', $order->order_type) === 'domain')>Domain</option>
          <option value="vps" @selected(old('order_type', $order->order_type) === 'vps')>VPS</option>
          <option value="other" @selected(old('order_type', $order->order_type) === 'other')>Lainnya</option>
        </select>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Jumlah (Rp)</label>
        <input type="number" step="0.01" name="amount" value="{{ old('amount', $order->amount) }}" class="form-input" required>
        @error('amount') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Status</label>
        <select name="status" class="form-input">
          <option value="pending" @selected(old('status', $order->status) === 'pending')>Pending</option>
          <option value="active" @selected(old('status', $order->status) === 'active')>Aktif</option>
          <option value="suspended" @selected(old('status', $order->status) === 'suspended')>Suspended</option>
          <option value="cancelled" @selected(old('status', $order->status) === 'cancelled')>Cancelled</option>
        </select>
      </div>
    </div>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.orders') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
