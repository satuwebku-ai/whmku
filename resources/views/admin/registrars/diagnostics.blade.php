@extends('layouts.admin')

@section('title', 'Diagnosa Registrar — ' . $registrar->name)

@section('content')

  <a href="{{ route('admin.registrars.index') }}" class="text-xs text-slate-400 hover:text-slate-600">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Registrar
  </a>
  <h1 class="text-xl font-bold text-slate-800 mt-1 mb-2">Diagnosa — {{ $registrar->name }}</h1>
  <p class="text-sm text-slate-500 mb-6">
    Data langsung dari API {{ ucfirst($registrar->provider) }} — cuma membaca, tidak mengubah apa pun di akunmu.
  </p>

  @if (! empty($apiErrors))
    @foreach ($apiErrors as $err)
      <div class="card p-3 mb-3 border-rose-200 bg-rose-50/60 text-sm text-rose-800">
        <i class="fa-solid fa-circle-exclamation"></i> {{ $err }}
      </div>
    @endforeach
  @endif

  <div class="grid lg:grid-cols-2 gap-5">

    {{-- Mata uang akun — ini yang paling menentukan cara baca semua angka lain --}}
    <div class="card p-5 {{ $details && $details['selling_currency'] ? 'border-emerald-200 bg-emerald-50/40' : '' }}">
      <h2 class="text-sm font-semibold text-slate-800 mb-3">Mata Uang Akun</h2>
      @if ($details && $details['selling_currency'])
        <p class="text-3xl font-bold text-slate-800 mb-2">{{ $details['selling_currency'] }}</p>
        <p class="text-xs text-slate-500">
          Semua angka harga &amp; saldo dari API ini dalam satuan <b>{{ $details['selling_currency'] }}</b>.
          @if ($details['selling_currency'] === 'USD')
            <br>Artinya kolom "Customer Price" di dashboard Liqu.id juga dalam USD — bukan ribuan Rupiah,
            meski labelnya tertulis begitu.
          @endif
        </p>
      @else
        <p class="text-slate-300 text-sm">Tidak bisa diambil — lihat pesan galat di atas.</p>
      @endif

      @if ($details && ($details['name'] || $details['company']))
        <div class="mt-4 pt-4 border-t border-slate-200/60 text-xs text-slate-500">
          @if ($details['name']) <p>Nama: {{ $details['name'] }}</p> @endif
          @if ($details['company']) <p>Perusahaan: {{ $details['company'] }}</p> @endif
        </div>
      @endif
    </div>

    {{-- Saldo --}}
    <div class="card p-5">
      <h2 class="text-sm font-semibold text-slate-800 mb-3">Saldo Deposit</h2>
      @if ($balance)
        <p class="text-3xl font-bold {{ $balance['balance'] < 20 ? 'text-rose-600' : 'text-slate-800' }} mb-2">
          {{ $details['selling_currency'] ?? '' }} {{ number_format($balance['balance'], 2) }}
        </p>
        @if ($balance['balance'] < 20)
          <p class="text-xs text-rose-600">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Saldo tipis. Registrasi satu domain .com saja butuh sekitar USD 53 —
            pastikan deposit cukup sebelum ada klien yang membeli.
          </p>
        @endif
      @else
        <p class="text-slate-300 text-sm">Tidak bisa diambil — lihat pesan galat di atas.</p>
      @endif
    </div>
  </div>

  {{-- Contoh format harga mentah --}}
  <div class="card p-5 mt-5">
    <h2 class="text-sm font-semibold text-slate-800 mb-1">Contoh Format Harga (data mentah)</h2>
    <p class="text-xs text-slate-500 mb-3">
      Tiga baris pertama dari daftar harga akunmu — untuk memastikan format angka yang sebenarnya
      dikembalikan API, bukan tebakan.
    </p>
    <pre class="bg-slate-800 text-slate-100 p-4 rounded-lg text-xs overflow-x-auto">{{ json_encode($priceSample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
  </div>

@endsection
