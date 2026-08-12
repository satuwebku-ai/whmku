@extends('public.layout')

@php
  $seoTitle = 'Keranjang Belanja';
@endphp

@section('content')

  <h1 class="text-2xl font-bold text-slate-800 mb-6">Keranjang Belanja</h1>

  <div class="grid lg:grid-cols-4 gap-6">
    {{-- Sidebar: kategori & aksi cepat — tetap tampil meski keranjang kosong,
         supaya pengunjung bisa lanjut menjelajah tanpa jalan buntu. --}}
    <div class="space-y-5">
      @if ($categories->isNotEmpty())
        <div class="card overflow-hidden">
          <div class="px-4 py-3 bg-slate-800 text-white text-sm font-semibold">Kategori Layanan</div>
          <div class="divide-y divide-slate-100">
            @foreach ($categories as $category)
              <a href="{{ route('catalog.category', $category->slug) }}" class="flex items-center justify-between px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-accent">
                {{ $category->name }}
                <span class="text-xs text-slate-400">{{ $category->products_count }}</span>
              </a>
            @endforeach
          </div>
        </div>
      @endif

      <div class="card overflow-hidden">
        <div class="px-4 py-3 bg-slate-800 text-white text-sm font-semibold">Aksi</div>
        <div class="divide-y divide-slate-100">
          <a href="{{ route('domain.search') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-accent">
            <i class="fa-solid fa-globe w-4 text-center"></i> Daftarkan Domain Baru
          </a>
          <a href="{{ route('catalog.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-accent">
            <i class="fa-solid fa-server w-4 text-center"></i> Lihat Paket Hosting
          </a>
          <span class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-accent bg-accent/5">
            <i class="fa-solid fa-cart-shopping w-4 text-center"></i> Keranjang ({{ count($items) }})
          </span>
        </div>
      </div>
    </div>

    <div class="lg:col-span-3">
  @if (empty($items))
    <div class="card p-12 text-center">
      <i class="fa-solid fa-cart-shopping text-3xl text-slate-300 mb-3"></i>
      <p class="text-slate-500 mb-4">Keranjang Anda masih kosong.</p>
      <a href="{{ route('catalog.index') }}" class="btn btn-primary">Lihat Paket Hosting</a>
    </div>
  @else
    <div class="grid lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-3">
        @foreach ($items as $item)
          <div class="card p-5 flex items-start justify-between gap-4 flex-wrap">
            <div class="flex items-start gap-3 min-w-0">
              <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $item['type'] === 'domain' ? 'bg-cyan-100 text-cyan-600' : 'bg-indigo-100 text-indigo-600' }}">
                <i class="fa-solid {{ $item['type'] === 'domain' ? 'fa-globe' : 'fa-server' }}"></i>
              </span>
              <div class="min-w-0">
                @if ($item['type'] === 'product')
                  <p class="font-semibold text-slate-800">{{ $item['name'] }}</p>

                  <form method="POST" action="{{ route('cart.update-cycle') }}" class="mt-1.5">
                    @csrf
                    <input type="hidden" name="key" value="{{ $item['key'] }}">
                    <select name="billing_cycle" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-md px-2 py-1 text-slate-600">
                      @foreach (\App\Models\Product::CYCLES as $ck => $label)
                        <option value="{{ $ck }}" @selected($item['billing_cycle'] === $ck)>{{ $label }}</option>
                      @endforeach
                    </select>
                  </form>

                  @if (!empty($item['domain_name']))
                    <p class="text-xs text-slate-400 mt-1.5">
                      <i class="fa-solid fa-globe text-[10px]"></i>
                      {{ $item['domain_mode'] === 'register' ? 'Daftar baru' : 'Domain sendiri' }}: {{ $item['domain_name'] }}
                    </p>
                  @endif
                @else
                  <p class="font-semibold text-slate-800">{{ $item['domain_name'] }}</p>
                  <form method="POST" action="{{ route('cart.update-years') }}" class="mt-1.5">
                    @csrf
                    <input type="hidden" name="key" value="{{ $item['key'] }}">
                    <select name="years" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-md px-2 py-1 text-slate-600">
                      @for ($y = 1; $y <= 10; $y++)
                        <option value="{{ $y }}" @selected($item['years'] == $y)>{{ $y }} Tahun</option>
                      @endfor
                    </select>
                  </form>

                  {{-- DNS Management — informasional, selalu aktif untuk
                       domain baru (DNS bisa dikelola lewat panel setelah
                       domain aktif, tidak ada biaya tambahan). --}}
                  <label class="flex items-center gap-1.5 text-xs text-slate-500 w-fit mt-1.5">
                    <input type="checkbox" checked disabled class="rounded border-slate-300 text-emerald-500">
                    DNS Management <span class="text-emerald-600 font-medium">(Gratis)</span>
                  </label>

                  {{-- ID Protection (WHOIS Privacy) — hanya berlaku untuk
                       domain baru, bukan yang sudah dimiliki klien. --}}
                  <form method="POST" action="{{ route('cart.toggle-privacy') }}" class="mt-1">
                    @csrf
                    <input type="hidden" name="key" value="{{ $item['key'] }}">
                    <label class="flex items-center gap-1.5 text-xs text-slate-500 cursor-pointer w-fit">
                      <input type="checkbox" onchange="this.form.submit()" @checked($item['whois_privacy'] ?? false)
                             class="rounded border-slate-300 text-accent focus:ring-accent/40">
                      ID Protection (sembunyikan data WHOIS)
                      @if (($item['whois_privacy_price'] ?? 0) > 0)
                        <span class="text-slate-400">
                          (+Rp {{ number_format($item['whois_privacy_price'], 0, ',', '.') }}/thn)
                        </span>
                      @else
                        <span class="text-emerald-600 font-medium">(Gratis)</span>
                      @endif
                    </label>
                  </form>
                @endif
              </div>
            </div>

            <div class="text-right shrink-0">
              <p class="font-semibold text-slate-800">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
              @if (!empty($item['setup_fee']))
                <p class="text-[11px] text-slate-400">+ setup Rp {{ number_format($item['setup_fee'], 0, ',', '.') }}</p>
              @endif
              <form method="POST" action="{{ route('cart.remove') }}" class="mt-2">
                @csrf
                <input type="hidden" name="key" value="{{ $item['key'] }}">
                <button type="submit" class="text-xs text-rose-500 hover:text-rose-700">
                  <i class="fa-regular fa-trash-can"></i> Hapus
                </button>
              </form>
            </div>
          </div>
        @endforeach

        <form method="POST" action="{{ route('cart.clear') }}"
              data-confirm="Kosongkan seluruh keranjang?" data-confirm-title="Kosongkan Keranjang"
              data-confirm-style="danger" data-confirm-label="Ya, Kosongkan">
          @csrf
          <button type="submit" class="text-xs text-slate-400 hover:text-rose-500">
            <i class="fa-regular fa-trash-can"></i> Kosongkan keranjang
          </button>
        </form>
      </div>

      <div>
        <div class="card p-6 sticky top-24">
          <h2 class="text-sm font-semibold text-slate-800 mb-4">Ringkasan</h2>
          <div class="flex justify-between text-sm mb-2">
            <span class="text-slate-500">Subtotal</span>
            <span class="text-slate-700 font-medium">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
          </div>
          <p class="text-[11px] text-slate-400 mb-4">Biaya setup (jika ada) & pajak dihitung saat checkout.</p>

          <a href="{{ route('client.checkout') }}" class="btn btn-primary w-full">
            <i class="fa-solid fa-lock text-xs"></i> Lanjut ke Checkout
          </a>
          <a href="{{ route('catalog.index') }}" class="btn btn-outline w-full mt-2">Lanjut Belanja</a>
        </div>
      </div>
    </div>
  @endif
    </div>
  </div>

@endsection
