<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\Hosting\HostingPanelFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function index(): View
    {
        $servers = Server::withCount('hostingAccounts')->latest()->paginate(10);

        return view('admin.servers.index', compact('servers'));
    }

    public function indexBootstrap(): View
    {
        $servers = Server::withCount('hostingAccounts')->latest()->paginate(10);

        return view('admin.servers.index', compact('servers'));
    }

    public function create(): View
    {
        return view('admin.servers.form', ['server' => new Server()]);
    }

    public function createBootstrap(): View
    {
        return view('admin.servers.form', ['server' => new Server()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['hostname'] = $data['hostname'] ?? '';
        $data['api_username'] = $data['api_username'] ?? '';
        $data['verify_ssl'] = $request->boolean('verify_ssl');
        $data['is_active'] = $request->boolean('is_active', true);

        Server::create($data);

        return redirect()->route('admin.servers.index')->with('success', 'Server berhasil ditambahkan.');
    }

    public function edit(Server $server): View
    {
        return view('admin.servers.form', compact('server'));
    }

    public function editBootstrap(Server $server): View
    {
        return view('admin.servers.form', compact('server'));
    }

    public function update(Request $request, Server $server): RedirectResponse
    {
        $data = $this->validated($request, updating: true);
        $data['hostname'] = $data['hostname'] ?? '';
        $data['api_username'] = $data['api_username'] ?? '';
        $data['verify_ssl'] = $request->boolean('verify_ssl');
        $data['is_active'] = $request->boolean('is_active');

        // Kalau field token dikosongkan saat edit, jangan timpa token yang sudah tersimpan.
        if (empty($data['api_token'])) {
            unset($data['api_token']);
        }

        $server->update($data);

        return redirect()->route('admin.servers.index')->with('success', 'Server berhasil diperbarui.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        if ($server->hostingAccounts()->exists()) {
            return back()->with('error', 'Server tidak bisa dihapus karena masih punya hosting account terhubung.');
        }

        $server->delete();

        return redirect()->route('admin.servers.index')->with('success', 'Server berhasil dihapus.');
    }

    public function testConnection(Server $server): RedirectResponse
    {
        $result = HostingPanelFactory::make($server)->testConnection();

        $server->update([
            'last_checked_at' => now(),
            'last_check_status' => $result['success'] ? 'ok' : $result['message'],
        ]);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Koneksi ke server berhasil.' : 'Koneksi gagal: ' . $result['message']
        );
    }

    /**
     * Login sekali klik ke WHM server ini, tanpa perlu masukkan
     * username/password manual -- pakai API Token yang sudah tersimpan.
     */
    public function loginWhm(Server $server): RedirectResponse
    {
        $panel = HostingPanelFactory::make($server);

        if (! method_exists($panel, 'createWhmSsoSession')) {
            return back()->with('error', 'Panel ' . $server->panel . ' belum mendukung login sekali klik ke WHM.');
        }

        $result = $panel->createWhmSsoSession();

        if (! $result['success']) {
            return back()->with('error', 'Gagal membuat sesi login WHM: ' . $result['message']);
        }

        return redirect()->away($result['url']);
    }

    /**
     * Bandingkan nama panel_package yang diketik di form Produk dengan
     * paket yang BENAR-BENAR ada di server — sumber error paling sering
     * saat provisioning otomatis gagal diam-diam.
     */
    public function diagnostics(Server $server): View
    {
        if ($server->panel === 'idcloudhost') {
            return view('admin.servers.diagnostics-idcloudhost', $this->idCloudHostDiagnosticsData($server));
        }

        return view('admin.servers.diagnostics', $this->diagnosticsData($server));
    }

    public function diagnosticsBootstrap(Server $server): View
    {
        if ($server->panel === 'idcloudhost') {
            return view('admin.servers.diagnostics-idcloudhost', $this->idCloudHostDiagnosticsData($server));
        }

        return view('admin.servers.diagnostics', $this->diagnosticsData($server));
    }

    private function diagnosticsData(Server $server): array
    {
        // IDCloudHost bukan panel di server yang sudah ada -- tidak
        // punya konsep "paket" atau "akun" seperti cPanel/WHM, jadi
        // dapat tampilan Diagnosa tersendiri: daftar VM & OS tersedia,
        // bukan perbandingan paket/akun yang tidak relevan untuknya.
        if ($server->panel === 'idcloudhost') {
            return $this->idCloudHostDiagnosticsData($server);
        }

        $service = HostingPanelFactory::make($server);

        $packages = [];
        $apiError = null;

        if (method_exists($service, 'listPackages')) {
            try {
                $result = $service->listPackages();
                $packages = $result['success'] ? $result['packages'] : [];

                if (! $result['success']) {
                    $apiError = $result['message'];
                }
            } catch (\Throwable $e) {
                $apiError = $e->getMessage();
            }
        } else {
            $apiError = 'Panel ' . ucfirst($server->panel) . ' belum mendukung daftar paket lewat sistem.';
        }

        // Produk yang menunjuk ke server ini, dan apakah panel_package-nya
        // benar-benar ada di daftar paket sungguhan di atas.
        $products = \App\Models\Product::where('server_id', $server->id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'panel_package' => $p->panel_package,
                'matches' => blank($p->panel_package) ? null : in_array($p->panel_package, $packages, true),
            ]);

        // Bandingkan catatan Hosting Account kita dengan akun yang
        // BENAR-BENAR ada di server — supaya kelihatan kalau ada yang
        // "menurut kita ada, tapi sebenarnya tidak pernah dibuat", atau
        // sebaliknya (ada di server tapi tidak tercatat di sistem kita).
        $whmDomains = [];
        $accountsError = null;

        if (method_exists($service, 'listAccounts')) {
            try {
                $result = $service->listAccounts();

                if ($result['success']) {
                    $whmDomains = array_column($result['accounts'], 'domain');
                } else {
                    $accountsError = $result['message'];
                }
            } catch (\Throwable $e) {
                $accountsError = $e->getMessage();
            }
        }

        $ourAccounts = \App\Models\HostingAccount::where('server_id', $server->id)
            ->get(['id', 'domain', 'status', 'provision_status'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'domain' => $a->domain,
                'status' => $a->status,
                'provision_status' => $a->provision_status,
                'ada_di_whm' => in_array($a->domain, $whmDomains, true),
            ]);

        // Akun yang ada di server tapi TIDAK tercatat di sistem kita sama
        // sekali — biasanya dibuat manual langsung di WHM, di luar Lumora.
        $orphanWhmDomains = array_diff($whmDomains, $ourAccounts->pluck('domain')->all());

        return compact('server', 'packages', 'apiError', 'products', 'ourAccounts', 'orphanWhmDomains', 'accountsError');
    }

    private function validated(Request $request, bool $updating = false): array
    {
        // IDCloudHost memakai field hostname & api_username secara
        // berbeda (slug lokasi opsional + billing account id opsional),
        // bukan hostname server & username API biasa -- jadi keduanya
        // tidak wajib khusus untuk provider ini. Lihat IdCloudHostService.
        $isIdCloudHost = $request->input('panel') === 'idcloudhost';

        return $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'hostname'     => [$isIdCloudHost ? 'nullable' : 'required', 'string', 'max:255'],
            'ns1'          => ['nullable', 'string', 'max:255'],
            'ns2'          => ['nullable', 'string', 'max:255'],
            'port'         => ['required', 'integer', 'min:1', 'max:65535'],
            'panel'        => ['required', 'in:cpanel,directadmin,plesk,idcloudhost'],
            'api_username' => [$isIdCloudHost ? 'nullable' : 'required', 'string', 'max:100'],
            'api_token'    => [$updating ? 'nullable' : 'required', 'string'],
            'verify_ssl'   => ['nullable', 'boolean'],
            'max_accounts' => ['nullable', 'integer', 'min:1'],
            'price_per_vcpu_hour' => ['nullable', 'numeric', 'min:0'],
            'price_per_ram_gb_hour' => ['nullable', 'numeric', 'min:0'],
            'price_per_storage_gb_hour' => ['nullable', 'numeric', 'min:0'],
            'price_per_backup_gb_hour' => ['nullable', 'numeric', 'min:0'],
            'price_per_snapshot_gb_hour' => ['nullable', 'numeric', 'min:0'],
            'price_windows_license_per_vcpu_hour' => ['nullable', 'numeric', 'min:0'],
            'is_active'    => ['nullable', 'boolean'],
        ]);
    }

    /**
     * Data Diagnosa khusus IDCloudHost -- daftar VM sungguhan di
     * akun/lokasi ini, plus daftar OS yang bisa dipilih saat membuat
     * produk VPS baru. Dibuat terpisah dari diagnosticsData() karena
     * "paket" & "akun" ala cPanel tidak berlaku untuk provider ini.
     */
    private function idCloudHostDiagnosticsData(Server $server): array
    {
        $service = new \App\Services\Hosting\IdCloudHostService($server);

        $vmResult = $service->listVms();
        $vms = $vmResult['success'] ? $vmResult['raw'] : [];
        $vmError = $vmResult['success'] ? null : $vmResult['message'];

        $osResult = $service->listOsImages();
        $osImages = $osResult['success'] ? $osResult['raw'] : [];
        $osError = $osResult['success'] ? null : $osResult['message'];

        $products = \App\Models\Product::where('server_id', $server->id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'spec' => json_decode((string) $p->panel_package, true),
            ]);

        return compact('server', 'vms', 'vmError', 'osImages', 'osError', 'products');
    }
}
