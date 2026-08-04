<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\HostingAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function services(Request $request): View
    {
        $services = Auth::guard('client')->user()
            ->hostingAccounts()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.services.index', compact('services'));
    }

    public function service(HostingAccount $service): View
    {
        // Pastikan layanan ini benar-benar milik klien yang sedang login.
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        $service->load('orders');

        return view('client.services.show', compact('service'));
    }

    public function domains(Request $request): View
    {
        $domains = Auth::guard('client')->user()
            ->domains()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.domains.index', compact('domains'));
    }

    public function domain(Domain $domain): View
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        return view('client.domains.show', compact('domain'));
    }
}
