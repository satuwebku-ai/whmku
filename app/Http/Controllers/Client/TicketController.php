<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function tickets(Request $request): View
    {
        $tickets = Auth::guard('client')->user()
            ->tickets()
            ->when($request->status === 'open', fn ($q) => $q->where('status', '!=', 'closed'))
            ->when($request->status === 'closed', fn ($q) => $q->where('status', 'closed'))
            ->withCount('publicReplies')
            ->orderByDesc('last_reply_at')
            ->paginate(10)
            ->withQueryString();

        return view('client.tickets.index', compact('tickets'));
    }

    public function ticket(Ticket $ticket): View
    {
        abort_unless($ticket->client_id === Auth::guard('client')->id(), 403);

        // Hanya balasan publik — catatan internal staf tidak boleh
        // terlihat oleh klien.
        $ticket->load(['publicReplies.admin', 'publicReplies.client']);

        return view('client.tickets.show', compact('ticket'));
    }

    public function create(): View
    {
        $client = Auth::guard('client')->user();

        return view('client.tickets.create', [
            'services' => $client->hostingAccounts()->get(),
            'domains'  => $client->domains()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();

        $data = $request->validate([
            'subject'            => ['required', 'string', 'max:255'],
            'department'         => ['required', 'in:support,billing,sales,abuse'],
            'priority'           => ['required', 'in:low,medium,high'],
            'message'            => ['required', 'string'],
            'hosting_account_id' => ['nullable', 'exists:hosting_accounts,id'],
            'attachment'         => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,txt,log,zip'],
        ]);

        // Cegah klien melampirkan layanan milik orang lain.
        if (! empty($data['hosting_account_id'])) {
            abort_unless(
                $client->hostingAccounts()->whereKey($data['hosting_account_id'])->exists(),
                403
            );
        }

        $ticket = Ticket::create([
            'client_id'          => $client->id,
            'subject'            => $data['subject'],
            'department'         => $data['department'],
            'priority'           => $data['priority'],
            'hosting_account_id' => $data['hosting_account_id'] ?? null,
            'status'             => 'open',
        ]);

        $reply = $ticket->replies()->make([
            'client_id' => $client->id,
            'message'   => $data['message'],
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $reply->attachment_path = $file->store('ticket-attachments', 'public');
            $reply->attachment_name = $file->getClientOriginalName();
        }

        $reply->save();

        // Beritahu admin ada tiket baru + catat ke log aktivitas.
        try {
            app(\App\Services\Notification\NotificationService::class)->ticketCreated($ticket->load('client'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notifikasi tiket gagal: ' . $e->getMessage());
        }

        return redirect()->route('client.tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} berhasil dibuat. Tim kami akan segera membalas.");
    }

    /**
     * Klien membalas tiketnya sendiri.
     */
    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->client_id === Auth::guard('client')->id(), 403);

        if ($ticket->isClosed()) {
            return back()->with('error', 'Tiket ini sudah ditutup. Silakan buat tiket baru.');
        }

        $data = $request->validate([
            'message'    => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,txt,log,zip'],
        ]);

        $reply = $ticket->replies()->make([
            'client_id' => Auth::guard('client')->id(),
            'message'   => $data['message'],
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $reply->attachment_path = $file->store('ticket-attachments', 'public');
            $reply->attachment_name = $file->getClientOriginalName();
        }

        $reply->save();

        // Status berubah supaya tiket naik ke atas antrean staf.
        $ticket->update(['status' => 'customer_reply', 'last_reply_at' => now()]);

        return back()->with('success', 'Balasan Anda terkirim.');
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->client_id === Auth::guard('client')->id(), 403);

        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('success', 'Tiket ditutup. Terima kasih.');
    }
}
