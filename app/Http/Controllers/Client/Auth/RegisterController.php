<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('client.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:clients,email'],
            'phone'    => ['required', 'string', 'max:30'],
            'company'  => ['nullable', 'string', 'max:255'],
            'address'  => ['nullable', 'string', 'max:500'],
            'city'     => ['nullable', 'string', 'max:120'],
            'state'    => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country'  => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms'    => ['accepted'],
        ], [
            'terms.accepted' => 'Anda harus menyetujui syarat & ketentuan untuk mendaftar.',
        ]);

        $client = Client::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'company'  => $data['company'] ?? null,
            'address'  => $data['address'] ?? null,
            'city'     => $data['city'] ?? null,
            'state'    => $data['state'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country'  => $data['country'] ?? 'Indonesia',
            'password' => $data['password'],
            'status'   => 'active',
        ]);

        // Sambutan untuk klien + pemberitahuan ke admin. Dibungkus supaya
        // pendaftaran tetap berhasil meski email/WA sedang bermasalah.
        try {
            app(\App\Services\Notification\NotificationService::class)->clientRegistered($client);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notifikasi pendaftaran gagal: ' . $e->getMessage());
        }

        Auth::guard('client')->login($client);
        $request->session()->regenerate();

        return redirect()->route('client.dashboard')
            ->with('success', 'Akun berhasil dibuat. Selamat datang!');
    }
}
