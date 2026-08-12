@extends('client.layout')
@section('title', 'Email Forwarding — ' . $domain->domain_name)

@section('content')
  <a href="{{ route('client.domains.show', $domain) }}" class="text-xs text-slate-400 hover:text-slate-600">
    &larr; Kembali ke {{ $domain->domain_name }}
  </a>

  <div class="mt-2 mb-5">
    <h1 class="text-xl font-bold text-slate-800">Email Forwarding — {{ $domain->domain_name }}</h1>
    <p class="text-sm text-slate-500 mt-1">
      Teruskan email yang masuk ke alamat @{{ $domain->domain_name }} ke email lain — tanpa perlu hosting email sendiri.
    </p>
  </div>

  @if ($warning)
    <div class="card p-4 mb-5 border-amber-200 bg-amber-50/60 text-sm text-amber-800">
      <i class="fa-solid fa-triangle-exclamation"></i> {{ $warning }}
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
      <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100">
          <h2 class="text-sm font-semibold text-slate-800">Forwarding Aktif</h2>
        </div>
        <div class="divide-y divide-slate-100">
          @forelse ($forwards as $fwd)
            <div class="flex items-center justify-between px-5 py-3">
              <div class="text-sm min-w-0">
                <p class="font-medium text-slate-700 truncate">{{ $fwd['email'] }}</p>
                <p class="text-xs text-slate-400">
                  <i class="fa-solid fa-arrow-right text-[9px]"></i> {{ $fwd['forward_to'] }}
                </p>
              </div>
              <form method="POST" action="{{ route('client.domains.email-forwarding.delete', $domain) }}"
                    data-confirm="Hapus forwarding untuk {{ $fwd['email'] }}?" data-confirm-title="Hapus Email Forwarding" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
                @csrf @method('DELETE')
                <input type="hidden" name="email" value="{{ $fwd['email'] }}">
                <button type="submit" class="w-7 h-7 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500 shrink-0">
                  <i class="fa-regular fa-trash-can text-xs"></i>
                </button>
              </form>
            </div>
          @empty
            <p class="px-5 py-10 text-center text-slate-400 text-sm">Belum ada email forwarding.</p>
          @endforelse
        </div>
      </div>
    </div>

    <div>
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Tambah Forwarding</h2>
        <form method="POST" action="{{ route('client.domains.email-forwarding.add', $domain) }}" class="space-y-3">
          @csrf
          <div>
            <label class="form-label">Alamat di Domain Ini</label>
            <div class="flex items-center gap-1">
              <input type="text" name="email" placeholder="info" class="form-input">
              <span class="text-sm text-slate-400 shrink-0">@{{ $domain->domain_name }}</span>
            </div>
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="form-label">Teruskan ke Email</label>
            <input type="email" name="forward_to" placeholder="tujuan@gmail.com" class="form-input">
            @error('forward_to') <p class="form-error">{{ $message }}</p> @enderror
          </div>
          <button type="submit" class="btn btn-primary w-full">
            <i class="fa-solid fa-plus text-xs"></i> Tambah
          </button>
        </form>
      </div>
    </div>
  </div>
@endsection
