<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentGatewayFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function invoices(Request $request): View
    {
        $invoices = Auth::guard('client')->user()
            ->invoices()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.invoices.index', compact('invoices'));
    }

    public function invoice(Invoice $invoice): View
    {
        abort_unless($invoice->client_id === Auth::guard('client')->id(), 403);

        $invoice->load(['order', 'items.order']);

        $gateways = PaymentGateway::where('is_active', true)->orderBy('sort_order')->get();

        // Pembayaran yang masih menunggu, supaya klien bisa melanjutkan
        // ke link yang sama alih-alih membuat transaksi baru terus-menerus.
        $pendingPayment = Payment::where('invoice_id', $invoice->id)
            ->whereIn('status', ['initiated', 'pending'])
            ->latest()
            ->first();

        return view('client.invoices.show', compact('invoice', 'gateways', 'pendingPayment'));
    }

    /**
     * Klien memilih gateway dan memulai pembayaran.
     */
    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->client_id === Auth::guard('client')->id(), 403);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice ini sudah lunas.');
        }

        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'Invoice ini sudah dibatalkan dan tidak bisa dibayar.');
        }

        $data = $request->validate([
            'payment_gateway_id' => ['required', 'exists:payment_gateways,id'],
        ]);

        $gateway = PaymentGateway::where('is_active', true)->findOrFail($data['payment_gateway_id']);

        $amount = (float) $invoice->total;
        $fee = $gateway->calculateFee($amount);

        $payment = Payment::create([
            'invoice_id'         => $invoice->id,
            'client_id'          => $invoice->client_id,
            'payment_gateway_id' => $gateway->id,
            'amount'             => $amount,
            'fee'                => $fee,
            'total'              => $amount + $fee,
            'currency'           => $gateway->currency,
            'status'             => 'initiated',
        ]);

        $result = PaymentGatewayFactory::make($gateway)->createTransaction($payment);

        if (! $result['success']) {
            $payment->update(['status' => 'failed', 'gateway_response' => ['error' => $result['message']]]);

            return back()->with('error', 'Gagal memulai pembayaran: ' . $result['message']);
        }

        $payment->update([
            'payment_url'      => $result['payment_url'],
            'external_id'      => $result['external_id'],
            'gateway_response' => $result['raw'],
        ]);

        // Gateway otomatis → langsung arahkan ke halaman pembayaran.
        if ($result['payment_url']) {
            return redirect()->away($result['payment_url']);
        }

        // Transfer manual → kembali ke invoice dengan instruksi transfer.
        return back()->with('success', 'Silakan lakukan transfer sesuai instruksi di bawah, lalu konfirmasi ke tim kami.');
    }

    /**
     * Unduh invoice sebagai PDF.
     */
    public function downloadPdf(Invoice $invoice): Response
    {
        abort_unless($invoice->client_id === Auth::guard('client')->id(), 403);

        $invoice->load(['order', 'items.order', 'client']);

        $pdf = Pdf::loadView('client.invoices.pdf', compact('invoice'))->setPaper('a4');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
