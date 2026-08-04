@extends('layouts.admin')

@section('title', $client->exists ? 'Edit Klien' : 'Tambah Klien')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">{{ $client->exists ? 'Edit Klien' : 'Tambah Klien Baru' }}</h1>
    <p class="text-sm text-slate-500 mt-1">Lengkapi data pelanggan di bawah ini.</p>
  </div>

  <form method="POST" action="{{ $client->exists ? route('admin.client.update', $client) : route('admin.client.add') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    @if ($client->exists) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="name" value="{{ old('name', $client->name) }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $client->email) }}" class="form-input" required>
        @error('email') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">No. Telepon</label>
        <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" class="form-input">
        @error('phone') <p class="form-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="form-label">Perusahaan (opsional)</label>
        <input type="text" name="company" value="{{ old('company', $client->company) }}" class="form-input">
        @error('company') <p class="form-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <div>
      <label class="form-label">Alamat</label>
      <textarea name="address" rows="2" class="form-input">{{ old('address', $client->address) }}</textarea>
      @error('address') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="form-label">Kota</label>
        <input type="text" name="city" value="{{ old('city', $client->city) }}" class="form-input">
      </div>
      <div>
        <label class="form-label">Negara</label>
        <input type="text" name="country" value="{{ old('country', $client->country ?? 'Indonesia') }}" class="form-input">
      </div>
      <div>
        <label class="form-label">Status</label>
        <select name="status" class="form-input">
          <option value="active" @selected(old('status', $client->status) === 'active')>Aktif</option>
          <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Nonaktif</option>
        </select>
      </div>
    </div>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-check text-xs"></i> {{ $client->exists ? 'Simpan Perubahan' : 'Simpan Klien' }}
      </button>
      <a href="{{ route('admin.clients') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
