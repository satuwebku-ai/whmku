@php
  $unit = [
    'monthly' => '/bulan', 'quarterly' => '/3 bulan',
    'semi_annually' => '/6 bulan', 'annually' => '/tahun',
  ];
  $cycles = $product->availableCycles();
  $firstCycleKey = array_key_first($cycles);
@endphp

<a href="{{ route('catalog.product', [$product->category->slug, $product->slug]) }}"
   class="card p-6 flex flex-col hover:border-accent/40 transition-colors relative">
  @if ($product->is_featured && $product->isInStock())
    <span class="absolute top-4 right-4 badge badge-active"><i class="fa-solid fa-star text-[9px]"></i> Unggulan</span>
  @elseif (! $product->isInStock())
    <span class="absolute top-4 right-4 badge badge-suspended">Stok Habis</span>
  @endif

  <h3 class="font-semibold text-slate-800 mb-1">{{ $product->name }}</h3>
  @if ($product->tagline)
    <p class="text-sm text-slate-500 mb-4">{{ $product->tagline }}</p>
  @endif

  @if ($product->features)
    <ul class="space-y-2 mb-5 flex-1 pl-0" style="list-style:none">
      @foreach (array_slice($product->features, 0, 4) as $feature)
        <li class="flex items-start gap-2.5 text-xs text-slate-600 leading-relaxed">
          <i class="fa-solid fa-check text-emerald-500 shrink-0" style="width:14px;margin-top:2px;text-align:center"></i>
          <span class="min-w-0 break-words">{{ $feature }}</span>
        </li>
      @endforeach
    </ul>
  @else
    <div class="flex-1"></div>
  @endif

  <div class="pt-4 border-t border-slate-100">
    @if ($product->starting_price !== null)
      <p class="text-2xl font-bold text-slate-800">
        Rp {{ number_format($product->starting_price, 0, ',', '.') }}
        <span class="text-xs font-normal text-slate-400">{{ $unit[$firstCycleKey] ?? '' }}</span>
      </p>
    @else
      <p class="text-sm text-rose-500">Harga belum tersedia</p>
    @endif
    <span class="btn {{ $product->isInStock() ? 'btn-primary' : 'btn-outline pointer-events-none opacity-60' }} w-full mt-3">
      {{ $product->isInStock() ? 'Lihat Detail' : 'Stok Habis' }}
    </span>
  </div>
</a>
