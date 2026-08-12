@extends('client.layout')
@section('title', 'Upgrade Paket — ' . $service->domain)

@section('content')
  <a href="{{ route('client.services.show', $service) }}" class="text-xs text-slate-400 hover:text-slate-600">
    &larr; Kembali ke {{ $service->domain }}
  </a>

  <div class="mt-2 mb-5">
    <h1 class="text-xl font-bold text-slate-800">Upgrade Paket</h1>
    <p class="text-sm text-slate-500 mt-1">
      Paket saat ini: <b>{{ $service->product?->name ?? $service->package }}</b> —
      Rp {{ number_format((float) $service->price, 0, ',', '.') }} / {{ $service->billing_cycle === 'monthly' ? 'bulan' : $service->billing_cycle }}
    </p>
  </div>

  @if ($options->isEmpty())
    <div class="card p-8 text-center">
      <i class="fa-solid fa-circle-info text-2xl text-slate-300 mb-3"></i>
      <p class="text-slate-600 font-medium mb-1">Belum ada paket upgrade yang tersedia</p>
      <p class="text-sm text-slate-400">Paket Anda mungkin sudah yang tertinggi, atau hubungi support untuk pilihan lain.</p>
    </div>
  @else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach ($options as $option)
        @php
          $newPrice = $option->priceForCycle($service->billing_cycle);
          $prorated = $service->prorateUpgrade($option);
        @endphp
        <div class="card p-5 flex flex-col">
          <h2 class="font-semibold text-slate-800 mb-1">{{ $option->name }}</h2>
          <p class="text-xs text-slate-500 mb-3">{{ $option->tagline }}</p>

          @if ($option->features)
            <ul class="text-xs text-slate-600 space-y-1 mb-4 flex-1">
              @foreach (array_slice($option->features, 0, 4) as $feature)
                <li><i class="fa-solid fa-check text-emerald-500 text-[10px]"></i> {{ $feature }}</li>
              @endforeach
            </ul>
          @endif

          <div class="border-t border-slate-100 pt-3 mt-auto">
            <p class="text-sm text-slate-700">
              Rp {{ number_format($newPrice, 0, ',', '.') }} / {{ $service->billing_cycle === 'monthly' ? 'bulan' : $service->billing_cycle }}
            </p>
            <p class="text-xs text-accent font-medium mt-0.5">
              + Rp {{ number_format($prorated, 0, ',', '.') }} sekarang (prorata sisa siklus)
            </p>

            <form method="POST" action="{{ route('client.services.upgrade.request', $service) }}" class="mt-3">
              @csrf
              <input type="hidden" name="product_id" value="{{ $option->id }}">
              <button type="submit" class="btn btn-primary w-full">Pilih Paket Ini</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>

    <p class="text-xs text-slate-400 mt-5">
      Biaya di atas adalah selisih prorata untuk sisa hari sampai jatuh tempo berikutnya
      ({{ $service->next_due_date?->format('d M Y') }}) — bukan harga paket baru dari awal siklus.
      Mulai perpanjangan berikutnya, tagihan otomatis memakai harga paket baru.
    </p>
  @endif
@endsection
