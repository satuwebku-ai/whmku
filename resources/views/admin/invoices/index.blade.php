@extends('layouts.admin')

@section('title', 'Invoice')

@section('content')

  @include('admin.invoices._nav')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Invoice</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola tagihan dan status pembayaran klien.</p>
    </div>
    <a href="{{ route('admin.invoice.add.page') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Buat Invoice
    </a>
  </div>

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor invoice..." class="form-input sm:max-w-xs">
      <button type="submit" class="btn btn-outline">Cari</button>
      @if (request('search'))
        <a href="{{ url()->current() }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">No. Invoice</th>
            <th class="px-5 py-2.5 font-semibold">Klien</th>
            <th class="px-5 py-2.5 font-semibold">Jatuh Tempo</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Total</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($invoices as $invoice)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">
                <a href="{{ route('admin.invoices.details', $invoice) }}" class="hover:text-accent">{{ $invoice->invoice_number }}</a>
              </td>
              <td class="px-5 py-3 text-slate-600">{{ $invoice->client->name ?? '—' }}</td>
              <td class="px-5 py-3 text-slate-600">{{ $invoice->due_date->format('d M Y') }}</td>
              <td class="px-5 py-3">
                <span class="badge badge-{{ $invoice->is_overdue ? 'overdue' : $invoice->status }}">
                  {{ $invoice->is_overdue ? 'Overdue' : ucfirst($invoice->status) }}
                </span>
              </td>
              <td class="px-5 py-3 text-right text-slate-700">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.invoices.details', $invoice) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Detail">
                    <i class="fa-regular fa-eye text-xs"></i>
                  </a>
                  <a href="{{ route('admin.invoice.edit.page', $invoice) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.invoice.delete', $invoice) }}" data-confirm="Hapus invoice ini?" data-confirm-title="Hapus Data" data-confirm-style="danger" data-confirm-label="Ya, Hapus" >
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Tidak ada invoice di kategori ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($invoices->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $invoices->links() }}</div>
    @endif
  </div>

@endsection
