@extends('layouts.admin')

@section('title', 'Percobaan Login')

@section('content')

  @include('admin.admins._nav')

  <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Percobaan Login</h1>
      <p class="text-sm text-slate-500 mt-1">Siapa saja yang mencoba masuk, berhasil maupun gagal.</p>
    </div>
    <form method="POST" action="{{ route('admin.login-attempts.clear') }}"
          data-confirm="Hapus catatan yang lebih dari 30 hari?"
          data-confirm-title="Bersihkan Catatan" data-confirm-style="warn" data-confirm-label="Ya, Bersihkan">
      @csrf
      <button type="submit" class="btn btn-outline"><i class="fa-regular fa-trash-can text-xs"></i> Bersihkan Lama</button>
    </form>
  </div>

  @if ($suspicious->isNotEmpty())
    <div class="card p-5 mb-5 border-rose-200 bg-rose-50/40">
      <h2 class="text-sm font-semibold text-rose-800 mb-1">
        <i class="fa-solid fa-triangle-exclamation"></i> IP dengan Kegagalan Beruntun (24 jam terakhir)
      </h2>
      <p class="text-xs text-rose-700 mb-3">
        Pola ini biasanya berarti ada yang mencoba menebak password. CAPTCHA otomatis aktif untuk IP tersebut.
      </p>
      <div class="flex flex-wrap gap-2">
        @foreach ($suspicious as $ip)
          <span class="px-3 py-1.5 rounded-lg bg-white border border-rose-200 text-xs">
            <span class="font-mono text-slate-700">{{ $ip->ip_address }}</span>
            <span class="text-rose-600 font-semibold ml-1">{{ $ip->jumlah }}× gagal</span>
          </span>
        @endforeach
      </div>
    </div>
  @endif

  <div class="grid sm:grid-cols-3 gap-4 mb-5">
    <div class="card p-4">
      <p class="text-xs text-slate-400">Total Tercatat</p>
      <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($counts['total']) }}</p>
    </div>
    <div class="card p-4">
      <p class="text-xs text-slate-400">Gagal (24 jam)</p>
      <p class="text-2xl font-bold text-rose-600 mt-1">{{ number_format($counts['failed']) }}</p>
    </div>
    <div class="card p-4">
      <p class="text-xs text-slate-400">Berhasil (24 jam)</p>
      <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($counts['success']) }}</p>
    </div>
  </div>

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari username, email, atau IP..." class="form-input sm:max-w-xs">
      <select name="guard" class="form-input sm:max-w-[140px]">
        <option value="">Semua area</option>
        <option value="admin" @selected(request('guard') === 'admin')>Admin</option>
        <option value="client" @selected(request('guard') === 'client')>Klien</option>
      </select>
      <select name="result" class="form-input sm:max-w-[140px]">
        <option value="">Semua hasil</option>
        <option value="failed" @selected(request('result') === 'failed')>Gagal</option>
        <option value="success" @selected(request('result') === 'success')>Berhasil</option>
      </select>
      <button type="submit" class="btn btn-outline">Terapkan</button>
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Waktu</th>
            <th class="px-5 py-2.5 font-semibold">Identitas</th>
            <th class="px-5 py-2.5 font-semibold">Area</th>
            <th class="px-5 py-2.5 font-semibold">IP</th>
            <th class="px-5 py-2.5 font-semibold">Hasil</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($attempts as $attempt)
            <tr class="hover:bg-slate-50/60 {{ $attempt->successful ? '' : 'bg-rose-50/20' }}">
              <td class="px-5 py-3 text-slate-600 text-xs whitespace-nowrap">
                {{ $attempt->created_at->format('d M H:i:s') }}
                <span class="block text-slate-400">{{ $attempt->created_at->diffForHumans() }}</span>
              </td>
              <td class="px-5 py-3">
                <span class="font-mono text-slate-700 text-xs">{{ $attempt->identifier }}</span>
              </td>
              <td class="px-5 py-3">
                <span class="badge badge-inactive">{{ $attempt->guard === 'admin' ? 'Admin' : 'Klien' }}</span>
              </td>
              <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $attempt->ip_address ?: '—' }}</td>
              <td class="px-5 py-3">
                @if ($attempt->reason === 'impersonated')
                  <span class="badge badge-pending" title="Bukan login klien biasa — akun diakses admin lewat fitur Login sebagai Klien">
                    <i class="fa-solid fa-user-shield"></i> {{ $attempt->reason_label }}
                  </span>
                @elseif ($attempt->successful)
                  <span class="badge badge-active"><i class="fa-solid fa-check"></i> Berhasil</span>
                @else
                  <span class="badge badge-suspended">{{ $attempt->reason_label }}</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Belum ada catatan percobaan login.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($attempts->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $attempts->links() }}</div>
    @endif
  </div>

@endsection
