@extends('client.layout')
@section('title', 'Layanan Saya')

@section('content')
  <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Layanan Saya</h1>
      <p class="text-sm text-slate-500 mt-1">Daftar akun hosting Anda.</p>
    </div>
    <form method="GET">
      <select name="status" class="form-input !py-2 text-sm" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="active" @selected(request('status') === 'active')>Aktif</option>
        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
        <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
        <option value="terminated" @selected(request('status') === 'terminated')>Terminated</option>
      </select>
    </form>
  </div>

  <div class="space-y-3">
    @forelse ($services as $service)
      <a href="{{ route('client.services.show', $service) }}" class="card p-5 flex items-center justify-between gap-4 hover:border-accent/40 transition-colors">
        <div class="min-w-0">
          <p class="font-semibold text-slate-800 truncate">{{ $service->domain }}</p>
          <p class="text-sm text-slate-500">{{ $service->package }}</p>
          @if ($service->next_due_date)
            <p class="text-xs text-slate-400 mt-1">Jatuh tempo berikutnya: {{ $service->next_due_date->format('d M Y') }}</p>
          @endif
        </div>
        <div class="text-right shrink-0">
          <span class="badge badge-{{ $service->status }}">{{ ucfirst($service->status) }}</span>
          <p class="text-sm font-semibold text-slate-700 mt-1.5">Rp {{ number_format($service->price, 0, ',', '.') }}</p>
          <p class="text-[11px] text-slate-400">{{ str_replace('_', ' ', $service->billing_cycle) }}</p>
        </div>
      </a>
    @empty
      <div class="card p-10 text-center">
        <p class="text-slate-400 text-sm">Anda belum punya layanan hosting.</p>
      </div>
    @endforelse
  </div>

  @if ($services->hasPages())
    <div class="mt-5">{{ $services->links() }}</div>
  @endif
@endsection
