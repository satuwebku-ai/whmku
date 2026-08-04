<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function clients(Request $request): View
    {
        return $this->renderList($request, null);
    }

    public function active(Request $request): View
    {
        return $this->renderList($request, 'active');
    }

    public function inactive(Request $request): View
    {
        return $this->renderList($request, 'inactive');
    }

    private function renderList(Request $request, ?string $status): View
    {
        $clients = Client::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('company', 'like', "%{$request->search}%");
            }))
            ->withCount(['hostingAccounts', 'orders', 'invoices'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.clients.index', ['clients' => $clients, 'activeStatus' => $status]);
    }

    /**
     * Halaman profil klien — ringkasan order/invoice/hosting/domain milik klien ini.
     */
    public function details(Client $client): View
    {
        $client->loadCount(['hostingAccounts', 'orders', 'invoices']);
        $client->load([
            'orders' => fn ($q) => $q->latest()->limit(5),
            'invoices' => fn ($q) => $q->latest()->limit(5),
            'hostingAccounts' => fn ($q) => $q->latest()->limit(5),
        ]);

        return view('admin.clients.details', compact('client'));
    }

    public function create(): View
    {
        return view('admin.clients.form', ['client' => new Client()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Client::create($data);

        return redirect()->route('admin.clients')->with('success', 'Klien baru berhasil ditambahkan.');
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $this->validated($request, $client->id);

        $client->update($data);

        return redirect()->route('admin.clients')->with('success', 'Data klien berhasil diperbarui.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('admin.clients')->with('success', 'Klien berhasil dihapus.');
    }

    /**
     * Toggle status aktif/nonaktif klien.
     */
    public function status(Request $request): RedirectResponse
    {
        $client = Client::findOrFail($request->input('client_id'));
        $client->update(['status' => $client->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', "Status klien {$client->name} berhasil diubah.");
    }

    /**
     * Simpan catatan internal staf untuk klien ini.
     */
    public function notes(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $client = Client::findOrFail($data['client_id']);
        $client->update(['internal_notes' => $data['internal_notes']]);

        return back()->with('success', 'Catatan berhasil disimpan.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255', 'unique:clients,email' . ($ignoreId ? ",{$ignoreId}" : '')],
            'phone'   => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city'    => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'status'  => ['required', 'in:active,inactive'],
        ]);
    }
}
