@extends('client.layout')
@section('title', 'Addons — ' . $service->domain)

@section('content')
  <a href="{{ route('client.services.show', $service) }}" class="text-xs text-slate-400 hover:text-slate-600">
    &larr; Kembali ke {{ $service->domain }}
  </a>

  <div class="mt-2 mb-5">
    <h1 class="text-xl font-bold text-slate-800">Addons — {{ $service->domain }}</h1>
    <p class="text-sm text-slate-500 mt-1">Fitur tambahan yang bisa dipasang di layanan hosting ini.</p>
  </div>

  @if ($attached->isNotEmpty())
    <div class="card overflow-hidden mb-6">
      <div class="px-5 py-3 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-800">Addon Terpasang</h2>
      </div>
      <div class="divide-y divide-slate-100">
        @foreach ($attached->whereIn('status', ['pending_payment', 'active']) as $item)
          <div class="flex items-center justify-between px-5 py-3">
            <div>
              <p class="text-sm text-slate-700">{{ $item->name }}</p>
              <p class="text-xs text-slate-400">
                Rp {{ number_format($item->price, 0, ',', '.') }} / {{ $service->billing_cycle === 'monthly' ? 'bulan' : $service->billing_cycle }}
                @if ($item->status === 'pending_payment')
                  — <span class="text-amber-600">menunggu pembayaran</span>
                @endif
              </p>
            </div>
            <div class="flex items-center gap-2">
              @if ($item->status === 'pending_payment' && $item->invoice)
                <a href="{{ route('client.invoices.show', $item->invoice) }}" class="btn btn-primary !py-1.5 !px-3 text-xs">Bayar</a>
              @else
                <span class="badge badge-active">Aktif</span>
              @endif
              <form method="POST" action="{{ route('client.services.addons.cancel', $item) }}"
                    data-confirm="Hentikan addon {{ $item->name }}? Tidak akan ikut ditagih lagi di perpanjangan berikutnya." data-confirm-title="Hentikan Addon" data-confirm-style="danger" data-confirm-label="Ya, Hentikan">
                @csrf
                <button type="submit" class="w-7 h-7 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500">
                  <i class="fa-regular fa-trash-can text-xs"></i>
                </button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  @if ($available->isEmpty())
    <div class="card p-8 text-center">
      <i class="fa-solid fa-puzzle-piece text-2xl text-slate-300 mb-3"></i>
      <p class="text-slate-600 font-medium mb-1">Belum ada addon tersedia</p>
      <p class="text-sm text-slate-400">Semua addon yang ada sudah terpasang, atau belum ada addon untuk siklus tagihan layanan Anda.</p>
    </div>
  @else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach ($available as $addon)
        @php
          $cyclePrice = $addon->priceForCycle($service->billing_cycle);
          $prorated = $service->prorateAddon($addon);
        @endphp
        <div class="card p-5 flex flex-col">
          <h2 class="font-semibold text-slate-800 mb-1">{{ $addon->name }}</h2>
          @if ($addon->description)
            <p class="text-xs text-slate-500 mb-3 flex-1">{{ $addon->description }}</p>
          @endif

          <div class="border-t border-slate-100 pt-3 mt-auto">
            <p class="text-sm text-slate-700">
              Rp {{ number_format($cyclePrice, 0, ',', '.') }} / {{ $service->billing_cycle === 'monthly' ? 'bulan' : $service->billing_cycle }}
            </p>
            <p class="text-xs text-accent font-medium mt-0.5">
              + Rp {{ number_format($prorated, 0, ',', '.') }} sekarang (prorata sisa siklus)
            </p>

            <form method="POST" action="{{ route('client.services.addons.request', $service) }}" class="mt-3">
              @csrf
              <input type="hidden" name="addon_id" value="{{ $addon->id }}">
              <button type="submit" class="btn btn-primary w-full">Pasang Addon Ini</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif
@endsection
