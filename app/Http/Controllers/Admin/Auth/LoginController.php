<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Notifications\SendOtpCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($credentials['username']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."]);
        }

        // Validasi kredensial tanpa langsung membuat sesi login, supaya
        // akun ber-2FA belum dianggap masuk sebelum OTP diverifikasi.
        if (! Auth::guard('admin')->validate($credentials)) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Username atau password salah.']);
        }

        RateLimiter::clear($throttleKey);

        $admin = Admin::where('username', $credentials['username'])->firstOrFail();

        if (! $admin->is_active) {
            return back()->withErrors([
                'username' => 'Akun admin ini sedang dinonaktifkan. Hubungi superadmin.',
            ]);
        }

        // ── Jalur 2FA ──
        if ($admin->two_factor_enabled) {
            $code = $admin->generateOtp();

            try {
                $admin->notify(new SendOtpCode($code));
            } catch (Throwable $e) {
                Log::error('Gagal mengirim OTP: ' . $e->getMessage(), ['admin_id' => $admin->id]);

                return back()->withErrors([
                    'username' => 'Kode verifikasi gagal dikirim. Periksa konfigurasi email server, atau hubungi superadmin.',
                ]);
            }

            // Simpan hanya ID di session — belum login sampai OTP benar.
            $request->session()->put('otp.admin_id', $admin->id);
            $request->session()->put('otp.remember', $request->boolean('remember'));

            return redirect()->route('admin.otp.challenge');
        }

        // ── Jalur normal tanpa 2FA ──
        Auth::guard('admin')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        $this->recordLogin($admin, $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function recordLogin(Admin $admin, Request $request): void
    {
        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();
    }
}
