<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('client.profile.edit', ['client' => Auth::guard('client')->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255', 'unique:clients,email,' . $client->id],
            'phone'   => ['required', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city'    => ['nullable', 'string', 'max:120'],
            'state'   => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        $client->update($data);

        return back()->with('success', 'Data profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (! Hash::check($data['current_password'], $client->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        $client->update(['password' => $data['password']]);

        return back()->with('success', 'Password berhasil diganti.');
    }
}
