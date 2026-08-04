<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function invoices(Request $request): View
    {
        return $this->renderList($request, null);
    }

    public function unpaid(Request $request): View
    {
        return $this->renderList($request, 'unpaid');
    }

    public function paid(Request $request): View
    {
        return $this->renderList($request, 'paid');
    }

    public function overdue(Request $request): View
    {
        return $this->renderList($request, 'overdue');
    }

    public function cancelled(Request $request): View
    {
        return $this->renderList($request, 'cancelled');
    }

    private function renderList(Request $request, ?string $status): View
    {
        $invoices = Invoice::query()
            ->with('client')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->search, fn ($q) => $q->where('invoice_number', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.invoices.index', ['invoices' => $invoices, 'activeStatus' => $status]);
    }

    public function details(Invoice $invoice): View
    {
        $invoice->load(['client', 'order']);

        return view('admin.invoices.details', compact('invoice'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();
        $orders = Order::orderBy('order_number')->get();

        return view('admin.invoices.form', ['invoice' => new Invoice(), 'clients' => $clients, 'orders' => $orders]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Invoice::create($data);

        return redirect()->route('admin.invoices')->with('success', 'Invoice berhasil dibuat.');
    }

    public function edit(Invoice $invoice): View
    {
        $clients = Client::orderBy('name')->get();
        $orders = Order::orderBy('order_number')->get();

        return view('admin.invoices.form', ['invoice' => $invoice, 'clients' => $clients, 'orders' => $orders]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['status'] === 'paid' && ! $invoice->paid_at) {
            $data['paid_at'] = now();
        }

        $invoice->update($data);

        return redirect()->route('admin.invoices')->with('success', 'Invoice berhasil diperbarui.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('admin.invoices')->with('success', 'Invoice berhasil dihapus.');
    }

    /**
     * Tandai invoice lunas.
     */
    public function markPaid(Request $request): RedirectResponse
    {
        $invoice = Invoice::findOrFail($request->input('invoice_id'));
        $invoice->update([
            'status' => 'paid',
            'paid_at' => $invoice->paid_at ?? now(),
            'payment_method' => $request->input('payment_method', $invoice->payment_method ?? 'Manual'),
        ]);

        return back()->with('success', "Invoice {$invoice->invoice_number} ditandai lunas.");
    }

    /**
     * Tandai invoice belum lunas (batalkan status lunas).
     */
    public function markUnpaid(Request $request): RedirectResponse
    {
        $invoice = Invoice::findOrFail($request->input('invoice_id'));
        $invoice->update(['status' => 'unpaid', 'paid_at' => null]);

        return back()->with('success', "Invoice {$invoice->invoice_number} ditandai belum lunas.");
    }

    /**
     * Batalkan invoice.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $invoice = Invoice::findOrFail($request->input('invoice_id'));
        $invoice->update(['status' => 'cancelled']);

        return back()->with('success', "Invoice {$invoice->invoice_number} dibatalkan.");
    }

    /**
     * Simpan catatan invoice (memakai kolom "notes" yang sudah ada sejak Fase 2).
     */
    public function invoiceNotes(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);
        $invoice->update(['notes' => $data['notes']]);

        return back()->with('success', 'Catatan invoice berhasil disimpan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id'      => ['required', 'exists:clients,id'],
            'order_id'       => ['nullable', 'exists:orders,id'],
            'amount'         => ['required', 'numeric', 'min:0'],
            'tax'            => ['nullable', 'numeric', 'min:0'],
            'status'         => ['required', 'in:unpaid,paid,overdue,cancelled'],
            'issue_date'     => ['required', 'date'],
            'due_date'       => ['required', 'date', 'after_or_equal:issue_date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string'],
        ]);
    }
}
