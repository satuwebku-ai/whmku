@extends('client.layout')
@section('title', 'Saldo Saya')

@section('content')
  <div class="mb-5">
    <h1 class="text-xl font-bold text-slate-800">Saldo Saya</h1>
    <p class="text-sm text-slate-500 mt-1">Isi ulang saldo untuk membayar invoice lebih cepat, tanpa pilih metode pembayaran berulang kali.</p>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
      <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100">
          <h2 class="text-sm font-semibold text-slate-800">Riwayat Saldo</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
                <th class="px-5 py-2.5 font-semibold">Tanggal</th>
                <th class="px-5 py-2.5 font-semibold">Keterangan</th>
                <th class="px-5 py-2.5 font-semibold text-right">Jumlah</th>
                <th class="px-5 py-2.5 font-semibold text-right">Saldo Setelahnya</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @forelse ($logs as $log)
                <tr class="hover:bg-slate-50/60">
                  <td class="px-5 py-3 text-slate-500 text-xs">{{ $log->created_at->format('d M Y H:i') }}</td>
                  <td class="px-5 py-3 text-slate-700">
                    <span class="badge {{ $log->amount >= 0 ? 'badge-active' : 'badge-inactive' }} !text-[10px] mr-1">{{ $log->type_label }}</span>
                    {{ $log->description }}
                  </td>
                  <td class="px-5 py-3 text-right font-medium {{ $log->amount >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $log->amount >= 0 ? '+' : '' }}Rp {{ number_format($log->amount, 0, ',', '.') }}
                  </td>
                  <td class="px-5 py-3 text-right text-slate-500">Rp {{ number_format($log->balance_after, 0, ',', '.') }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">Belum ada riwayat saldo.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if ($logs->hasPages())
          <div class="px-5 py-3 border-t border-slate-100">{{ $logs->links() }}</div>
        @endif
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-5 bg-gradient-to-br from-accent to-indigo-700 text-white">
        <p class="text-xs text-white/70 mb-1">Saldo Tersedia</p>
        <p class="text-3xl font-bold">Rp {{ number_format((float) $client->balance, 0, ',', '.') }}</p>
      </div>

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Isi Ulang Saldo</h2>
        <form method="POST" action="{{ route('client.balance.topup') }}" class="space-y-3">
          @csrf
          <div class="grid grid-cols-3 gap-2">
            @foreach ([50000, 100000, 250000, 500000, 1000000, 2000000] as $preset)
              <button type="button" onclick="document.getElementById('topupAmount').value = {{ $preset }}"
                      class="text-xs border border-slate-200 rounded-lg py-2 hover:border-accent hover:text-accent transition-colors">
                {{ number_format($preset / 1000, 0) }}rb
              </button>
            @endforeach
          </div>
          <div>
            <label class="form-label">Nominal (Rp)</label>
            <input type="number" name="amount" id="topupAmount" min="10000" step="1000" required
                   placeholder="100000" class="form-input">
            @error('amount') <p class="form-error">{{ $message }}</p> @enderror
          </div>
          <button type="submit" class="btn btn-primary w-full">
            <i class="fa-solid fa-wallet text-xs"></i> Isi Ulang Sekarang
          </button>
        </form>
      </div>
    </div>
  </div>
@endsection
