@extends('client.layout')
@section('title', 'Checkout')

@section('content')

  <a href="{{ route('cart.index') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Keranjang</a>

  <h1 class="text-xl font-bold text-slate-800 mt-2 mb-6">Konfirmasi Pesanan</h1>

  @if ($issues)
    <div class="card p-5 mb-5 border-rose-200 bg-rose-50/60">
      <p class="text-sm font-semibold text-rose-700 mb-2"><i class="fa-solid fa-circle-exclamation"></i> Ada item yang belum bisa diproses:</p>
      <ul class="list-disc pl-5 space-y-1 text-sm text-rose-600">
        @foreach ($issues as $issue)
          <li>{{ $issue }}</li>
        @endforeach
      </ul>
      <a href="{{ route('cart.index') }}" class="btn btn-outline mt-3 !text-rose-600 !border-rose-200">Perbaiki di Keranjang</a>
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">

      {{-- Ringkasan item --}}
      <div class="card p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Ringkasan Pesanan</h2>
        <div class="divide-y divide-slate-100">
          @foreach ($items as $item)
            <div class="py-3 flex items-start justify-between gap-4">
              <div>
                @if ($item['type'] === 'product')
                  <p class="text-sm font-medium text-slate-700">{{ $item['name'] }}</p>
                  <p class="text-xs text-slate-400">{{ \App\Models\Product::CYCLES[$item['billing_cycle']] ?? $item['billing_cycle'] }}</p>
                  @if (!empty($item['domain_name']))
                    <p class="text-xs text-slate-400 mt-0.5">
                      <i class="fa-solid fa-globe text-[10px]"></i>
                      {{ $item['domain_mode'] === 'register' ? 'Daftar domain baru' : 'Pakai domain sendiri' }}: {{ $item['domain_name'] }}
                    </p>
                  @endif
                @else
                  <p class="text-sm font-medium text-slate-700">{{ $item['domain_name'] }}</p>
                  <p class="text-xs text-slate-400">Registrasi domain — {{ $item['years'] }} tahun</p>
                @endif
              </div>
              <p class="text-sm font-semibold text-slate-700 shrink-0">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
            </div>
          @endforeach
        </div>
        <div class="pt-4 mt-2 border-t border-slate-100 space-y-1.5">
          <div class="flex justify-between text-sm text-slate-500">
            <span>Subtotal</span>
            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
          </div>
          @if ($coupon)
            <div class="flex justify-between text-sm text-emerald-600">
              <span>Kupon {{ $coupon->code }} ({{ $coupon->value_label }})</span>
              <span>- Rp {{ number_format($discount, 0, ',', '.') }}</span>
            </div>
          @endif
          <div class="flex justify-between font-bold text-slate-800 text-base pt-1.5">
            <span>Total</span>
            <span>Rp {{ number_format($subtotal - $discount, 0, ',', '.') }}</span>
          </div>
        </div>
      </div>

      {{-- Data penagihan --}}
      <div class="card p-6">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-sm font-semibold text-slate-800">Data Penagihan</h2>
          <a href="{{ route('client.profile') }}" class="text-xs text-accent hover:underline">Edit Profil</a>
        </div>
        <dl class="grid sm:grid-cols-2 gap-3 text-sm">
          <div><dt class="text-slate-400 text-xs">Nama</dt><dd class="text-slate-700">{{ $client->name }}</dd></div>
          <div><dt class="text-slate-400 text-xs">Email</dt><dd class="text-slate-700">{{ $client->email }}</dd></div>
          <div><dt class="text-slate-400 text-xs">Telepon</dt><dd class="text-slate-700">{{ $client->phone ?: '—' }}</dd></div>
          <div><dt class="text-slate-400 text-xs">Alamat</dt><dd class="text-slate-700">{{ $client->address ?: '—' }}, {{ $client->city }}</dd></div>
        </dl>

        @if ($items->contains(fn ($i) => ($i['type'] ?? null) === 'domain' || ($i['domain_mode'] ?? null) === 'register'))
          @if (blank($client->state) || blank($client->postal_code))
            <div class="mt-3 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5 text-xs text-amber-800">
              <i class="fa-solid fa-triangle-exclamation"></i>
              Ada pendaftaran domain di pesanan ini. Provinsi & kode pos Anda belum lengkap — data ini wajib untuk registrasi
              domain (WHOIS). Lengkapi dulu di <a href="{{ route('client.profile') }}" class="underline font-medium">halaman Profil</a>
              supaya domain bisa diproses otomatis setelah pembayaran.
            </div>
          @endif
        @endif
      </div>
    </div>

    <div class="space-y-5">
      {{-- Kupon --}}
      <div class="card p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Kode Kupon</h2>

        @if ($coupon)
          <div class="flex items-center justify-between rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2.5">
            <div>
              <p class="text-sm font-semibold text-emerald-700">{{ $coupon->code }}</p>
              <p class="text-xs text-emerald-600">Potongan {{ $coupon->value_label }} diterapkan</p>
            </div>
            <form method="POST" action="{{ route('client.checkout.coupon.remove') }}">
              @csrf @method('DELETE')
              <button type="submit" class="text-xs text-emerald-700 hover:underline">Batalkan</button>
            </form>
          </div>
        @else
          <form method="POST" action="{{ route('client.checkout.coupon') }}" class="flex gap-2">
            @csrf
            <input type="text" name="code" placeholder="Masukkan kode kupon" class="form-input flex-1 uppercase" style="text-transform:uppercase">
            <button type="submit" class="btn btn-outline shrink-0">Pakai</button>
          </form>
        @endif
      </div>

      <div class="card p-6 sticky top-24">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Selesaikan Pesanan</h2>
        <p class="text-xs text-slate-500 mb-4">
          Setelah dikonfirmasi, invoice akan dibuat dan Anda diarahkan ke halaman pembayaran.
          Layanan aktif otomatis begitu invoice lunas.
        </p>

        <form method="POST" action="{{ route('client.checkout.store') }}">
          @csrf
          <button type="submit" class="btn btn-primary w-full" {{ $issues ? 'disabled' : '' }}>
            <i class="fa-solid fa-check text-xs"></i> Buat Pesanan
          </button>
        </form>
      </div>
    </div>
  </div>

@endsection
