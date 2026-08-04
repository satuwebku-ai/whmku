@extends('layouts.admin')

@section('title', $invoice->exists ? 'Edit Invoice' : 'Buat Invoice')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $invoice->exists ? 'Edit Invoice' : 'Buat Invoice Manual' }}</h1>
    @if ($invoice->exists)
      <p class="text-sm text-slate-500 mt-1">No. Invoice: <span class="font-medium text-slate-700">{{ $invoice->invoice_number }}</span></p>
    @else
      <p class="text-sm text-slate-500 mt-1">Nomor invoice akan dibuat otomatis (format INV-{{ date('Y') }}-xxxx).</p>
    @endif
  </div>

  <form method="POST" action="{{ $invoice->exists ? route('admin.invoice.update', $invoice) : route('admin.invoice.add') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    @if ($invoice->exists) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Klien</label>
        <select name="client_id" class="form-input" required>
          <option value="">Pilih klien</option>
          @foreach ($clients as $client)
            <option value="{{ $client->id }}" @selected(old('client_id', $invoice->client_id) == $client->id)>{{ $client->name }}</option>
          @endforeach
        </select>
        @error('client_id') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Order Terkait (opsional)</label>
        <select name="order_id" class="form-input">
          <option value="">— Tidak terkait —</option>
          @foreach ($orders as $order)
            <option value="{{ $order->id }}" @selected(old('order_id', $invoice->order_id) == $order->id)>#{{ $order->order_number }} — {{ $order->product_name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Jumlah / Subtotal (Rp)</label>
        <input type="number" step="0.01" name="amount" value="{{ old('amount', $invoice->amount) }}" class="form-input" required>
        @error('amount') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Pajak (Rp, opsional)</label>
        <input type="number" step="0.01" name="tax" value="{{ old('tax', $invoice->tax ?? 0) }}" class="form-input">
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Tanggal Terbit</label>
        <input type="date" name="issue_date" value="{{ old('issue_date', optional($invoice->issue_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="form-input" required>
        @error('issue_date') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Jatuh Tempo</label>
        <input type="date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d') ?? now()->addDays(7)->format('Y-m-d')) }}" class="form-input" required>
        @error('due_date') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Status</label>
        <select name="status" class="form-input">
          <option value="unpaid" @selected(old('status', $invoice->status) === 'unpaid')>Unpaid</option>
          <option value="paid" @selected(old('status', $invoice->status) === 'paid')>Paid</option>
          <option value="overdue" @selected(old('status', $invoice->status) === 'overdue')>Overdue</option>
          <option value="cancelled" @selected(old('status', $invoice->status) === 'cancelled')>Cancelled</option>
        </select>
      </div>
      <div>
        <label class="form-label">Metode Pembayaran (opsional)</label>
        <input type="text" name="payment_method" value="{{ old('payment_method', $invoice->payment_method) }}" placeholder="Transfer Bank / Midtrans / dll" class="form-input">
      </div>
    </div>

    <div>
      <label class="form-label">Catatan (opsional)</label>
      <textarea name="notes" rows="2" class="form-input">{{ old('notes', $invoice->notes) }}</textarea>
    </div>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan</button>
      <a href="{{ route('admin.invoices') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
