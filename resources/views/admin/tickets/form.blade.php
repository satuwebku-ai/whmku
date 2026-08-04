@extends('layouts.admin')

@section('title', 'Buat Tiket')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Buat Tiket</h1>
    <p class="text-sm text-slate-500 mt-1">Untuk mencatat keluhan klien yang masuk lewat jalur lain (telepon, WhatsApp, dsb).</p>
  </div>

  <form method="POST" action="{{ route('admin.ticket.add') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf

    <div>
      <label class="form-label">Klien</label>
      <select name="client_id" class="form-input" required>
        <option value="">Pilih klien</option>
        @foreach ($clients as $client)
          <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }} ({{ $client->email }})</option>
        @endforeach
      </select>
      @error('client_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="form-label">Subjek</label>
      <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Website tidak bisa diakses" class="form-input" required>
      @error('subject') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Departemen</label>
        <select name="department" class="form-input">
          <option value="support" @selected(old('department') === 'support')>Technical Support</option>
          <option value="billing" @selected(old('department') === 'billing')>Billing</option>
          <option value="sales" @selected(old('department') === 'sales')>Sales</option>
          <option value="abuse" @selected(old('department') === 'abuse')>Abuse</option>
        </select>
      </div>
      <div>
        <label class="form-label">Prioritas</label>
        <select name="priority" class="form-input">
          <option value="low" @selected(old('priority') === 'low')>Low</option>
          <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
          <option value="high" @selected(old('priority') === 'high')>High</option>
          <option value="urgent" @selected(old('priority') === 'urgent')>Urgent</option>
        </select>
      </div>
    </div>

    <div>
      <label class="form-label">Pesan / Keluhan</label>
      <textarea name="message" rows="5" class="form-input" placeholder="Tuliskan keluhan klien..." required>{{ old('message') }}</textarea>
      @error('message') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check text-xs"></i> Buat Tiket</button>
      <a href="{{ route('admin.tickets') }}" class="btn btn-outline">Batal</a>
    </div>
  </form>

@endsection
