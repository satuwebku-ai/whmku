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
    public function index(Request $request): View
    {
        $tlds = Tld::with('registrar')
            ->when($request->search, fn ($q) => $q->where('extension', 'like', "%{$request->search}%"))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('extension')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'all'      => Tld::count(),
            'active'   => Tld::where('is_active', true)->count(),
            'inactive' => Tld::where('is_active', false)->count(),
        ];

        return view('admin.tlds.index', compact('tlds', 'counts'));
    }

    /**
     * Aktif/nonaktifkan satu TLD tanpa membuka form edit.
     */
    public function status(Request $request): RedirectResponse
    {
        $tld = Tld::findOrFail($request->input('tld_id'));

        // Mengaktifkan TLD tanpa harga jual hampir pasti tidak disengaja.
        if (! $tld->is_active && (float) $tld->register_price <= 0) {
            return back()->with('error', "TLD {$tld->extension} belum punya harga jual. Isi harganya dulu sebelum diaktifkan.");
        }

        $tld->update(['is_active' => ! $tld->is_active]);

        return back()->with('success', "TLD {$tld->extension} berhasil " . ($tld->is_active ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    /**
     * Terapkan markup ke banyak TLD sekaligus — jauh lebih praktis daripada
     * mengedit ratusan TLD hasil sinkronisasi satu per satu.
     */
    public function bulkMarkup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:1000'],
            'round_to'       => ['nullable', 'integer', 'min:0'],
            'activate'       => ['nullable', 'boolean'],
        ]);

        $percent  = (float) $data['markup_percent'];
        $roundTo  = (int) ($data['round_to'] ?? 1000);
        $activate = $request->boolean('activate');

        $updated = 0;
        $skipped = 0;

        foreach (Tld::all() as $tld) {
            $base = (float) $tld->register_price;

            // TLD tanpa harga modal tidak bisa dihitung markup-nya.
            if ($base <= 0) {
                $skipped++;
                continue;
            }

            $newPrice = $base * (1 + $percent / 100);

            if ($roundTo > 0) {
                $newPrice = ceil($newPrice / $roundTo) * $roundTo;
            }

            $tld->update([
                'register_price' => $newPrice,
                'renew_price'    => $newPrice,
                'transfer_price' => $newPrice,
                'is_active'      => $activate ? true : $tld->is_active,
            ]);

            $updated++;
        }

        $msg = "Markup {$percent}% diterapkan ke {$updated} TLD.";
        $msg .= $skipped > 0 ? " {$skipped} TLD dilewati karena harga modalnya 0." : '';

        return back()->with('success', $msg);
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
