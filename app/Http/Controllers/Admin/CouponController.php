<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function coupons(Request $request): View
    {
        $coupons = Coupon::query()
            ->when($request->search, fn ($q) => $q->where('code', 'like', '%' . strtoupper($request->search) . '%'))
            ->withCount('invoices')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('admin.coupons.form', ['coupon' => new Coupon()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);

        Coupon::create($data);

        return redirect()->route('admin.coupons')->with('success', 'Kupon berhasil dibuat.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validated($request, $coupon->id);
        $data['is_active'] = $request->boolean('is_active');

        $coupon->update($data);

        return redirect()->route('admin.coupons')->with('success', 'Kupon berhasil diperbarui.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        if ($coupon->invoices()->exists()) {
            return back()->with('error', 'Kupon tidak bisa dihapus karena sudah pernah dipakai. Nonaktifkan saja.');
        }

        $coupon->delete();

        return redirect()->route('admin.coupons')->with('success', 'Kupon berhasil dihapus.');
    }

    public function status(Request $request): RedirectResponse
    {
        $coupon = Coupon::findOrFail($request->input('coupon_id'));
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back()->with('success', "Kupon {$coupon->code} berhasil " . ($coupon->is_active ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code'                    => ['required', 'string', 'max:50', 'unique:coupons,code' . ($ignoreId ? ",{$ignoreId}" : '')],
            'type'                    => ['required', 'in:percent,fixed'],
            'value'                   => ['required', 'numeric', 'min:0.01'],
            'min_order'               => ['nullable', 'numeric', 'min:0'],
            'max_discount'            => ['nullable', 'numeric', 'min:0'],
            'usage_limit'             => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_client'  => ['required', 'integer', 'min:1'],
            'starts_at'               => ['nullable', 'date'],
            'expires_at'              => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active'               => ['nullable', 'boolean'],
        ], [
            'value.min' => 'Nilai kupon harus lebih dari 0.',
        ]);
    }
}
