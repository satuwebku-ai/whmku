<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Registrar;
use App\Models\Tld;
use App\Services\Domain\AvailabilityService;
use App\Services\Domain\DomainRegistrarFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainController extends Controller
{
    /**
     * Batas jumlah ekstensi yang dicek dalam satu pencarian.
     *
     * Tanpa batas ini, 399 TLD aktif akan dikirim sekaligus — URL jadi
     * terlalu panjang (HTTP 414) dan registrar kena rate limit.
     */
    private const MAX_TLD_PER_SEARCH = 20;

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
    public function search(Request $request, AvailabilityService $checker): View
    {
        $results = null;
        $query = $request->input('domain');

        if ($query) {
            $base = strtolower(preg_replace('/[^a-z0-9\-]/i', '', explode('.', trim($query))[0]));

            // Kalau admin mengetik domain lengkap (mis. "saya.com"),
            // cek ekstensi itu saja — lebih cepat dan lebih relevan.
            $typedExt = str_contains($query, '.') ? '.' . \Illuminate\Support\Str::after($query, '.') : null;

            $tldQuery = Tld::where('is_active', true)->where('register_price', '>', 0);

            if ($typedExt) {
                $tldQuery->where('extension', $typedExt);
            }

            $tlds = $tldQuery->orderBy('register_price')
                ->limit(self::MAX_TLD_PER_SEARCH)
                ->pluck('extension');

            if ($base === '') {
                $results = ['success' => false, 'message' => 'Nama domain tidak valid.', 'results' => [], 'unknown' => []];
            } else {
                $candidates = $tlds->isNotEmpty()
                    ? $tlds->map(fn ($ext) => $base . $ext)->values()->all()
                    : [$query];

                // Memakai RDAP publik, bukan API registrar — bebas rate limit
                // dan tidak menghabiskan kuota reseller untuk sekadar mengecek.
                $results = $checker->check($candidates);
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

    /**
     * Transfer domain butuh persetujuan pemilik lama di registrar
     * sebelumnya — tidak ada webhook dari Liqu.id yang memberi tahu kita
     * kapan itu selesai, jadi admin yang memastikan manual (login ke
     * Liqu.id, cek status domainnya "Live"), baru menandai selesai di sini.
     */
    /**
     * Domain yang baru kedaluwarsa biasanya masih bisa dipulihkan lewat
     * "masa tenggang" registri (redemption period) sebelum benar-benar
     * dilepas ke publik — dengan biaya tambahan dari registrar. Jendela
     * waktunya beda-beda tiap TLD, jadi tombol ini bisa saja tetap gagal
     * kalau masa tenggangnya sudah lewat.
     */
    public function restore(Domain $domain): RedirectResponse
    {
        if ($domain->status !== 'expired' || ! $domain->registrar) {
            return back()->with('error', 'Cuma domain berstatus kedaluwarsa dan terhubung registrar yang bisa dipulihkan.');
        }

        $service = \App\Services\Domain\DomainRegistrarFactory::make($domain->registrar);

        if (! method_exists($service, 'restoreDomain')) {
            return back()->with('error', 'Registrar domain ini belum mendukung pemulihan domain lewat sistem.');
        }

        $result = $service->restoreDomain($domain->domain_name);

        if (! $result['success']) {
            return back()->with('error', 'Gagal memulihkan domain: ' . $result['message'] . ' (kemungkinan masa tenggang sudah lewat).');
        }

        $domain->update([
            'status' => 'active',
            'provision_status' => 'registered',
            'provision_message' => 'Dipulihkan dari masa tenggang oleh ' . (auth('admin')->user()->name ?? 'admin'),
        ]);

        \App\Models\ActivityLog::record(
            'domain',
            'Domain dipulihkan dari kedaluwarsa: ' . $domain->domain_name,
            'Dipulihkan manual oleh admin',
            route('admin.domains.details', $domain),
            'success',
            $domain->client_id,
        );

        return back()->with('success', 'Domain berhasil dipulihkan dan diaktifkan kembali.');
    }

    public function markTransferComplete(Domain $domain): RedirectResponse
    {
        if (! $domain->is_transfer || $domain->provision_status !== 'transfer_pending') {
            return back()->with('error', 'Domain ini tidak sedang menunggu konfirmasi transfer.');
        }

        $domain->update([
            'status' => 'active',
            'provision_status' => 'registered',
            'provision_message' => 'Transfer dikonfirmasi selesai secara manual oleh admin.',
            'register_date' => $domain->register_date ?: now(),
            'expiry_date' => $domain->expiry_date ?: now()->addYears(max($domain->years ?: 1, 1)),
        ]);

        \App\Models\ActivityLog::record(
            'domain',
            'Transfer domain dikonfirmasi selesai: ' . $domain->domain_name,
            'Ditandai manual oleh ' . (auth('admin')->user()->name ?? 'admin'),
            route('admin.domains.details', $domain),
            'success',
            $domain->client_id,
        );

        return back()->with('success', 'Transfer domain ditandai selesai dan diaktifkan.');
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
