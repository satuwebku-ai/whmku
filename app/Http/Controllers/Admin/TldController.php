<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registrar;
use App\Models\Tld;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TldController extends Controller
{
    public function index(): View
    {
        $tlds = Tld::with('registrar')->orderBy('extension')->paginate(15);

        return view('admin.tlds.index', compact('tlds'));
    }

    public function create(): View
    {
        $registrars = Registrar::where('is_active', true)->orderBy('name')->get();

        return view('admin.tlds.form', ['tld' => new Tld(), 'registrars' => $registrars]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);

        Tld::create($data);

        return redirect()->route('admin.tlds.index')->with('success', 'TLD berhasil ditambahkan.');
    }

    public function edit(Tld $tld): View
    {
        $registrars = Registrar::where('is_active', true)->orderBy('name')->get();

        return view('admin.tlds.form', ['tld' => $tld, 'registrars' => $registrars]);
    }

    public function update(Request $request, Tld $tld): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        $tld->update($data);

        return redirect()->route('admin.tlds.index')->with('success', 'TLD berhasil diperbarui.');
    }

    public function destroy(Tld $tld): RedirectResponse
    {
        $tld->delete();

        return redirect()->route('admin.tlds.index')->with('success', 'TLD berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'extension'      => ['required', 'string', 'max:30', 'unique:tlds,extension,' . $request->route('tld')?->id],
            'registrar_id'   => ['nullable', 'exists:registrars,id'],
            'register_price' => ['required', 'numeric', 'min:0'],
            'renew_price'    => ['required', 'numeric', 'min:0'],
            'transfer_price' => ['required', 'numeric', 'min:0'],
            'min_years'      => ['required', 'integer', 'min:1', 'max:10'],
            'max_years'      => ['required', 'integer', 'min:1', 'max:10'],
            'is_active'      => ['nullable', 'boolean'],
        ]);
    }
}
