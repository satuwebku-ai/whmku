<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registrar;
use App\Services\Domain\DomainRegistrarFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrarController extends Controller
{
    public function index(): View
    {
        $registrars = Registrar::withCount(['tlds', 'domains'])->latest()->paginate(10);

        return view('admin.registrars.index', compact('registrars'));
    }

    public function create(): View
    {
        return view('admin.registrars.form', ['registrar' => new Registrar()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sandbox'] = $request->boolean('sandbox', true);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            Registrar::query()->update(['is_default' => false]);
        }

        Registrar::create($data);

        return redirect()->route('admin.registrars.index')->with('success', 'Registrar berhasil ditambahkan.');
    }

    public function edit(Registrar $registrar): View
    {
        return view('admin.registrars.form', compact('registrar'));
    }

    public function update(Request $request, Registrar $registrar): RedirectResponse
    {
        $data = $this->validated($request, updating: true);
        $data['sandbox'] = $request->boolean('sandbox');
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        if ($data['is_default']) {
            Registrar::query()->where('id', '!=', $registrar->id)->update(['is_default' => false]);
        }

        $registrar->update($data);

        return redirect()->route('admin.registrars.index')->with('success', 'Registrar berhasil diperbarui.');
    }

    public function destroy(Registrar $registrar): RedirectResponse
    {
        if ($registrar->domains()->exists() || $registrar->tlds()->exists()) {
            return back()->with('error', 'Registrar tidak bisa dihapus karena masih dipakai oleh TLD/domain.');
        }

        $registrar->delete();

        return redirect()->route('admin.registrars.index')->with('success', 'Registrar berhasil dihapus.');
    }

    public function testConnection(Registrar $registrar): RedirectResponse
    {
        $result = DomainRegistrarFactory::make($registrar)->testConnection();

        $registrar->update([
            'last_checked_at' => now(),
            'last_check_status' => $result['success'] ? 'ok' : $result['message'],
        ]);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Koneksi ke Namecheap berhasil.' : 'Koneksi gagal: ' . $result['message']
        );
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'provider'     => ['required', 'in:namecheap,resellbiz,liquid'],
            // API URL opsional: kalau kosong, service memakai default
            // (Namecheap & Liqu.id sudah punya URL bawaan sandbox/produksi).
            'api_url'      => ['nullable', 'url', 'max:255'],
            'api_username' => ['required', 'string', 'max:100'],
            'api_key'      => [$updating ? 'nullable' : 'required', 'string'],
            'username'     => ['nullable', 'string', 'max:100'],
            'client_ip'    => ['nullable', 'ip'],
            'sandbox'      => ['nullable', 'boolean'],
            'is_active'    => ['nullable', 'boolean'],
            'is_default'   => ['nullable', 'boolean'],
        ]);
    }
}
