<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use App\Models\Server;
use App\Services\Billing\HourlyRateCalculator;
use Illuminate\View\View;

/**
 * Menu khusus layanan VPS/VM -- terpisah dari Hosting Account biasa
 * karena cara kerjanya beda total: ditagih per jam dari saldo (bukan
 * invoice bulanan), spesifikasinya per-VM (bukan nama paket WHM), dan
 * hidup di provider cloud (bukan server cPanel).
 */
class VpsController extends Controller
{
    public function index(): View
    {
        // Server bertipe cloud -- untuk sekarang idcloudhost, tapi
        // sengaja pakai whereIn supaya provider cloud lain nanti
        // cukup ditambahkan ke daftar ini tanpa ubah query.
        $cloudServerIds = Server::whereIn('panel', ['idcloudhost'])->pluck('id');

        $accounts = HostingAccount::whereIn('server_id', $cloudServerIds)
            ->with(['client', 'serverModel'])
            ->latest()
            ->paginate(20);

        // Tarif dihitung per baris supaya tidak memanggil kalkulator
        // berulang kali di dalam view.
        $rates = [];
        foreach ($accounts as $account) {
            $rates[$account->id] = $this->rateFor($account);
        }

        $allActive = HostingAccount::whereIn('server_id', $cloudServerIds)->with('serverModel')->get();

        $stats = [
            'total'          => $allActive->count(),
            'active'         => $allActive->where('status', 'active')->count(),
            'deposit'        => $allActive->where('billing_mode', 'deposit')->count(),
            'hourly_revenue' => $allActive->where('status', 'active')->sum(fn ($a) => $this->rateFor($a) ?? 0),
        ];

        return view('admin.vps.index', compact('accounts', 'rates', 'stats'));
    }

    /**
     * Sama logikanya dengan ChargeHourlyUsage::effectiveRate() --
     * hitung dari kartu harga server kalau ada spek VM, kalau tidak
     * pakai tarif flat manual.
     */
    private function rateFor(HostingAccount $account): ?float
    {
        if ($account->serverModel && $account->hasVmSpec()) {
            $rate = HourlyRateCalculator::calculate($account->serverModel, $account->vmSpec());

            if ($rate > 0) {
                return $rate;
            }
        }

        return $account->hourly_rate ? (float) $account->hourly_rate : null;
    }
}
