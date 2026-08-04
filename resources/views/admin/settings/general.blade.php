@extends('layouts.admin')
@section('title', 'Pengaturan Umum')
@section('content')
  @include('admin.settings._nav')

  @php use App\Models\Setting; @endphp

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Pengaturan Umum</h1>
    <p class="text-sm text-slate-500 mt-1">Identitas bisnis yang tampil di halaman publik dan email.</p>
  </div>

  <form method="POST" action="{{ route('admin.settings.general.update') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama Situs</label>
        <input type="text" name="site_name" value="{{ old('site_name', Setting::get('site_name', config('app.name'))) }}" class="form-input" required>
        @error('site_name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Tagline</label>
        <input type="text" name="site_tagline" value="{{ old('site_tagline', Setting::get('site_tagline')) }}" class="form-input" placeholder="Hosting cepat & terjangkau">
      </div>
    </div>

    <div>
      <label class="form-label">Nama Perusahaan (untuk invoice)</label>
      <input type="text" name="company_name" value="{{ old('company_name', Setting::get('company_name')) }}" class="form-input" placeholder="PT Contoh Hosting">
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Email Support</label>
        <input type="email" name="support_email" value="{{ old('support_email', Setting::get('support_email')) }}" class="form-input" placeholder="support@contoh.com">
        @error('support_email') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Telepon Support</label>
        <input type="text" name="support_phone" value="{{ old('support_phone', Setting::get('support_phone')) }}" class="form-input" placeholder="+62 811 2345 678">
      </div>
    </div>

    <div>
      <label class="form-label">Alamat Perusahaan</label>
      <textarea name="company_address" rows="2" class="form-input">{{ old('company_address', Setting::get('company_address')) }}</textarea>
    </div>

    <div>
      <label class="form-label">Teks Footer</label>
      <input type="text" name="footer_text" value="{{ old('footer_text', Setting::get('footer_text')) }}" class="form-input" placeholder="© {{ date('Y') }} Nama Perusahaan. Semua hak dilindungi.">
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Simpan Pengaturan</button>
  </form>
@endsection
