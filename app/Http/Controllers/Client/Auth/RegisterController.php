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
            'country'  => $data['country'] ?? 'Indonesia',
            'password' => $data['password'],
            'status'   => 'active',
        ]);

        Auth::guard('client')->login($client);
        $request->session()->regenerate();

        return redirect()->route('client.dashboard')
            ->with('success', 'Akun berhasil dibuat. Selamat datang!');
    }
}
