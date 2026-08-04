<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function tickets(Request $request): View
    {
        return $this->renderList($request, null);
    }

    public function open(Request $request): View
    {
        return $this->renderList($request, 'open');
    }

    public function answered(Request $request): View
    {
        return $this->renderList($request, 'answered');
    }

    public function customerReply(Request $request): View
    {
        return $this->renderList($request, 'customer_reply');
    }

    public function closed(Request $request): View
    {
        return $this->renderList($request, 'closed');
    }

    private function renderList(Request $request, ?string $status): View
    {
        $tickets = Ticket::query()
            ->with(['client', 'assignee'])
            ->withCount('replies')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('ticket_number', 'like', "%{$request->search}%")
                  ->orWhere('subject', 'like', "%{$request->search}%");
            }))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority))
            // Tiket yang butuh perhatian naik ke atas, lalu urut balasan terbaru.
            ->orderByRaw("FIELD(status, 'customer_reply', 'open', 'answered', 'closed')")
            ->orderByDesc('last_reply_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.tickets.index', ['tickets' => $tickets, 'activeStatus' => $status]);
    }

    public function details(Ticket $ticket): View
    {
        $ticket->load(['client', 'assignee', 'replies.admin', 'replies.client', 'hostingAccount', 'domain', 'invoice']);
        $admins = Admin::where('is_active', true)->orderBy('name')->get();

        return view('admin.tickets.details', compact('ticket', 'admins'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();

        return view('admin.tickets.form', ['ticket' => new Ticket(), 'clients' => $clients]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id'  => ['required', 'exists:clients,id'],
            'subject'    => ['required', 'string', 'max:255'],
            'department' => ['required', 'in:support,billing,sales,abuse'],
            'priority'   => ['required', 'in:low,medium,high,urgent'],
            'message'    => ['required', 'string'],
        ]);

        $ticket = Ticket::create([
            'client_id'  => $data['client_id'],
            'subject'    => $data['subject'],
            'department' => $data['department'],
            'priority'   => $data['priority'],
            'status'     => 'open',
        ]);

        // Pesan pertama dicatat atas nama klien, karena tiket dibuatkan
        // admin mewakili keluhan klien.
        $ticket->replies()->create([
            'client_id' => $data['client_id'],
            'message'   => $data['message'],
        ]);

        return redirect()->route('admin.tickets.details', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} berhasil dibuat.");
    }

    /**
     * Balas tiket sebagai staf, atau simpan catatan internal.
     */
    public function reply(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ticket_id'        => ['required', 'exists:tickets,id'],
            'message'          => ['required', 'string'],
            'is_internal_note' => ['nullable', 'boolean'],
            'attachment'       => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,txt,log,zip'],
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);
        $isNote = $request->boolean('is_internal_note');

        $reply = new TicketReply([
            'admin_id'         => Auth::guard('admin')->id(),
            'message'          => $data['message'],
            'is_internal_note' => $isNote,
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $reply->attachment_path = $file->store('ticket-attachments', 'public');
            $reply->attachment_name = $file->getClientOriginalName();
        }

        $ticket->replies()->save($reply);

        // Catatan internal tidak mengubah status tiket — klien tidak
        // melihatnya, jadi tiket tetap dianggap belum dijawab.
        if (! $isNote) {
            $ticket->update([
                'status' => 'answered',
                'last_reply_at' => now(),
            ]);
        }

        return back()->with('success', $isNote ? 'Catatan internal tersimpan.' : 'Balasan terkirim.');
    }

    /**
     * Tutup tiket.
     */
    public function close(Request $request): RedirectResponse
    {
        $ticket = Ticket::findOrFail($request->input('ticket_id'));

        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('success', "Tiket {$ticket->ticket_number} ditutup.");
    }

    /**
     * Buka kembali tiket yang sudah ditutup.
     */
    public function reopen(Request $request): RedirectResponse
    {
        $ticket = Ticket::findOrFail($request->input('ticket_id'));

        $ticket->update(['status' => 'customer_reply', 'closed_at' => null, 'last_reply_at' => now()]);

        return back()->with('success', "Tiket {$ticket->ticket_number} dibuka kembali.");
    }

    /**
     * Tugaskan tiket ke staf tertentu.
     */
    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ticket_id'   => ['required', 'exists:tickets,id'],
            'assigned_to' => ['nullable', 'exists:admins,id'],
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);
        $ticket->update(['assigned_to' => $data['assigned_to'] ?: null]);

        return back()->with('success', $data['assigned_to']
            ? 'Tiket ditugaskan ke ' . ($ticket->fresh()->assignee->name ?? 'staf') . '.'
            : 'Penugasan tiket dilepas.');
    }

    /**
     * Ubah prioritas tiket.
     */
    public function priority(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ticket_id' => ['required', 'exists:tickets,id'],
            'priority'  => ['required', 'in:low,medium,high,urgent'],
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);
        $ticket->update(['priority' => $data['priority']]);

        return back()->with('success', 'Prioritas tiket diperbarui.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $ticket->delete();

        return redirect()->route('admin.tickets')->with('success', 'Tiket berhasil dihapus.');
    }
}
