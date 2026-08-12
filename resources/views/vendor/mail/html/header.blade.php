@component('mail::header', ['url' => config('app.url')])
{{-- Logo diambil dari Pengaturan Umum -> Logo Website. Kalau belum diisi,
     otomatis jatuh ke nama situs biasa (perilaku bawaan Laravel), bukan
     kotak gambar kosong yang rusak. --}}
@php $siteLogo = \App\Models\Setting::get('site_logo'); @endphp

@if ($siteLogo)
<img src="{{ asset('storage/' . $siteLogo) }}" class="logo" alt="{{ \App\Models\Setting::get('site_name', config('app.name')) }}" style="max-height:40px;">
@else
{{ \App\Models\Setting::get('site_name', config('app.name')) }}
@endif
@endcomponent
