@extends('client.layout')
@section('title', 'Domain Saya')

@section('content')
  <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Domain Saya</h1>
      <p class="text-sm text-slate-500 mt-1">Daftar domain yang Anda daftarkan.</p>
    </div>
    <form method="GET">
      <select name="status" class="form-input !py-2 text-sm" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="active" @selected(request('status') === 'active')>Aktif</option>
        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
        <option value="expired" @selected(request('status') === 'expired')>Expired</option>
      </select>
    </form>
  </div>

  <div class="space-y-3">
    @forelse ($domains as $domain)
      <a href="{{ route('client.domains.show', $domain) }}" class="card p-5 flex items-center justify-between gap-4 hover:border-accent/40 transition-colors">
        <div class="min-w-0">
          <p class="font-semibold text-slate-800 truncate">{{ $domain->domain_name }}</p>
          <p class="text-xs text-slate-400 mt-1">
            @if ($domain->expiry_date)
              Berlaku sampai {{ $domain->expiry_date->format('d M Y') }}
            @else
              Tanggal kedaluwarsa belum tercatat
            @endif
          </p>
        </div>
        <div class="text-right shrink-0">
          <span class="badge badge-{{ $domain->status === 'expired' ? 'expired' : $domain->status }}">{{ ucfirst($domain->status) }}</span>
          @if ($domain->is_expiring_soon)
            <p class="text-[11px] text-amber-600 font-medium mt-1">Segera perpanjang</p>
          @endif
        </div>
      </a>
    @empty
      <div class="card p-10 text-center">
        <p class="text-slate-400 text-sm">Anda belum punya domain.</p>
      </div>
    @endforelse
  </div>

  @if ($domains->hasPages())
    <div class="mt-5">{{ $domains->links() }}</div>
  @endif
@endsection
