{{--
  Blok verifikasi "saya bukan robot".

  Dipakai bersama di form login admin, login klien, dan pendaftaran.
  Tidak menampilkan apa-apa kalau CAPTCHA sedang tidak diperlukan —
  jadi bisa disisipkan tanpa syarat di form manapun.

  Variabel $captcha berasal dari controller (CaptchaService).
--}}

@if (!empty($captcha) && $captcha['required'])
  <div>
    @if ($captcha['recaptcha'])
      <div class="g-recaptcha" data-sitekey="{{ $captcha['site_key'] }}"></div>
      <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @else
      <label class="form-label">{{ $captcha['question'] }}</label>
      <input type="number" name="captcha_answer" required autocomplete="off"
             class="form-input" placeholder="Jawaban">
      <p class="text-[11px] text-slate-400 mt-1">
        Pertanyaan sederhana ini muncul karena ada beberapa percobaan masuk yang gagal.
      </p>
    @endif

    @error('captcha') <p class="form-error">{{ $message }}</p> @enderror
  </div>
@endif
