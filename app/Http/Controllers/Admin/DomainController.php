<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Registrar;
use App\Models\Tld;
use App\Services\Domain\DomainRegistrarFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainController extends Controller
{
    public function domains(Request $request): View
    {
        return $this->renderList($request, null);
    }

    public function pending(Request $request): View
    {
        return $this->renderList($request, 'pending');
    }

    public function active(Request $request): View
    {
        return $this->renderList($request, 'active');
    }

    public function expired(Request $request): View
    {
        return $this->renderList($request, 'expired');
    }

    public function cancelled(Request $request): View
    {
        return $this->renderList($request, 'cancelled');
    }

    private function renderList(Request $request, ?string $status): View
    {
        $domains = Domain::query()
            ->with(['client', 'registrar'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->search, fn ($q) => $q->where('domain_name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.domains.index', ['domains' => $domains, 'activeStatus' => $status]);
    }

    public function details(Domain $domain): View
    {
        $domain->load(['client', 'registrar', 'tld', 'order']);

        return view('admin.domains.details', compact('domain'));
    }

    /**
     * Halaman "Cek Domain" — search ketersediaan domain via API registrar.
     */
    public function search(Request $request): View
    {
        $results = null;
        $query = $request->input('domain');

        if ($query) {
            $registrar = Registrar::where('is_default', true)->where('is_active', true)->first()
                ?? Registrar::where('is_active', true)->first();

            if (! $registrar) {
                $results = ['success' => false, 'message' => 'Belum ada registrar aktif. Tambahkan registrar dulu.', 'results' => []];
            } else {
                $base = preg_replace('/\.[a-z.]+$/i', '', $query);
                $tlds = Tld::where('is_active', true)->pluck('extension');
                $candidates = $tlds->isNotEmpty()
                    ? $tlds->map(fn ($ext) => $base . $ext)->values()->all()
                    : [$query];

                $results = DomainRegistrarFactory::make($registrar)->checkAvailability($candidates);
            }
        }

        $tldPrices = Tld::where('is_active', true)->orderBy('extension')->get()->keyBy('extension');

        return view('admin.domains.search', compact('results', 'query', 'tldPrices'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();
        $registrars = Registrar::where('is_active', true)->orderBy('name')->get();
        $tlds = Tld::where('is_active', true)->orderBy('extension')->get();

        return view('admin.domains.form', [
            'domain' => new Domain(),
            'clients' => $clients,
            'registrars' => $registrars,
            'tlds' => $tlds,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['auto_renew'] = $request->boolean('auto_renew', true);
        $data['whois_privacy'] = $request->boolean('whois_privacy');
        $registerNow = $request->boolean('register_now');

        $data['provision_status'] = 'manual';
        $data['provision_message'] = null;

        if ($registerNow) {
            if (! $data['registrar_id']) {
                return back()->withInput()->with('error', 'Pilih registrar untuk registrasi otomatis.');
            }

            $registrar = Registrar::findOrFail($data['registrar_id']);

            $contact = $request->validate([
                'contact_first_name' => ['required', 'string', 'max:100'],
                'contact_last_name'  => ['required', 'string', 'max:100'],
                'contact_address'    => ['required', 'string', 'max:255'],
                'contact_city'       => ['required', 'string', 'max:100'],
                'contact_state'      => ['required', 'string', 'max:100'],
                'contact_postal_code' => ['required', 'string', 'max:20'],
                'contact_country'    => ['required', 'string', 'max:2'],
                'contact_phone'      => ['required', 'string', 'max:30'],
                'contact_email'      => ['required', 'email', 'max:255'],
            ]);

            $result = DomainRegistrarFactory::make($registrar)->registerDomain([
                'domain' => $data['domain_name'],
                'years'  => $data['years'],
                'whois_privacy' => $data['whois_privacy'],
                'contact' => [
                    'first_name' => $contact['contact_first_name'],
                    'last_name' => $contact['contact_last_name'],
                    'address' => $contact['contact_address'],
                    'city' => $contact['contact_city'],
                    'state' => $contact['contact_state'],
                    'postal_code' => $contact['contact_postal_code'],
                    'country' => $contact['contact_country'],
                    'phone' => $contact['contact_phone'],
                    'email' => $contact['contact_email'],
                ],
            ]);

            $data['provision_status'] = $result['success'] ? 'registered' : 'failed';
            $data['provision_message'] = $result['message'];

            if ($result['success']) {
                $data['status'] = 'active';
                $data['register_date'] = now();
                $data['expiry_date'] = now()->addYears((int) $data['years']);
            }

            $domain = Domain::create($data);

            return $result['success']
                ? redirect()->route('admin.domains.details', $domain)->with('success', 'Domain berhasil didaftarkan otomatis lewat registrar.')
                : redirect()->route('admin.domain.edit.page', $domain)->with('error', 'Data tersimpan, tapi registrasi otomatis GAGAL: ' . $result['message']);
        }

        Domain::create($data);

        return redirect()->route('admin.domains')->with('success', 'Domain berhasil dicatat (manual, tanpa registrasi otomatis).');
    }

    public function edit(Domain $domain): View
    {
        $clients = Client::orderBy('name')->get();
        $registrars = Registrar::where('is_active', true)->orderBy('name')->get();
        $tlds = Tld::where('is_active', true)->orderBy('extension')->get();

        return view('admin.domains.form', compact('domain', 'clients', 'registrars', 'tlds'));
    }

    public function update(Request $request, Domain $domain): RedirectResponse
    {
        $data = $this->validated($request);
        $data['auto_renew'] = $request->boolean('auto_renew');
        $data['whois_privacy'] = $request->boolean('whois_privacy');

        $domain->update($data);

        return redirect()->route('admin.domains')->with('success', 'Domain berhasil diperbarui.');
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        $domain->delete();

        return redirect()->route('admin.domains')->with('success', 'Data domain berhasil dihapus (catatan: pendaftaran di registrar TIDAK ikut dibatalkan).');
    }

    public function renew(Domain $domain): RedirectResponse
    {
        if (! $domain->registrar) {
            return back()->with('error', 'Domain ini tidak terhubung ke registrar (manual), tidak bisa diperpanjang otomatis.');
        }

        $result = DomainRegistrarFactory::make($domain->registrar)->renewDomain($domain->domain_name, 1);

        if ($result['success']) {
            $domain->update([
                'expiry_date' => $domain->expiry_date?->addYear() ?? now()->addYear(),
                'provision_status' => 'registered',
                'provision_message' => $result['message'],
            ]);

            return back()->with('success', 'Domain berhasil diperpanjang 1 tahun.');
        }

        $domain->update(['provision_message' => $result['message']]);

        return back()->with('error', 'Gagal memperpanjang domain: ' . $result['message']);
    }

    /**
     * Batalkan domain (ubah status jadi cancelled, tidak menghapus data).
     */
    public function cancel(Request $request): RedirectResponse
    {
        $domain = Domain::findOrFail($request->input('domain_id'));
        $domain->update(['status' => 'cancelled']);

        return back()->with('success', "Domain {$domain->domain_name} dibatalkan.");
    }

    /**
     * Simpan catatan internal staf untuk domain ini.
     */
    public function notes(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'domain_id' => ['required', 'exists:domains,id'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $domain = Domain::findOrFail($data['domain_id']);
        $domain->update(['internal_notes' => $data['internal_notes']]);

        return back()->with('success', 'Catatan berhasil disimpan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id'      => ['required', 'exists:clients,id'],
            'registrar_id'   => ['nullable', 'exists:registrars,id'],
            'tld_id'         => ['nullable', 'exists:tlds,id'],
            'domain_name'    => ['required', 'string', 'max:255'],
            'price'          => ['required', 'numeric', 'min:0'],
            'years'          => ['required', 'integer', 'min:1', 'max:10'],
            'status'         => ['required', 'in:pending,active,expired,cancelled'],
            'expiry_date'    => ['nullable', 'date'],
        ]);
    }
}
