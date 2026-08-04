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

    public function create(): View
    {
        return view('admin.servers.form', ['server' => new Server()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['verify_ssl'] = $request->boolean('verify_ssl');
        $data['is_active'] = $request->boolean('is_active', true);

        Server::create($data);

        return redirect()->route('admin.servers.index')->with('success', 'Server berhasil ditambahkan.');
    }

    public function edit(Server $server): View
    {
        return view('admin.servers.form', compact('server'));
    }

    public function update(Request $request, Server $server): RedirectResponse
    {
        $data = $this->validated($request, updating: true);
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

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'hostname'     => ['required', 'string', 'max:255'],
            'port'         => ['required', 'integer', 'min:1', 'max:65535'],
            'panel'        => ['required', 'in:cpanel,directadmin,plesk'],
            'api_username' => ['required', 'string', 'max:100'],
            'api_token'    => [$updating ? 'nullable' : 'required', 'string'],
            'verify_ssl'   => ['nullable', 'boolean'],
            'max_accounts' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);
    }
}
