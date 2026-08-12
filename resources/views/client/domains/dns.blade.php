@extends('client.layout')
@section('title', 'DNS — ' . $domain->domain_name)

@section('content')
  <a href="{{ route('client.domains.show', $domain) }}" class="text-xs text-slate-400 hover:text-slate-600">
    &larr; Kembali ke {{ $domain->domain_name }}
  </a>

  <div class="mt-2 mb-5">
    <h1 class="text-xl font-bold text-slate-800">Kelola DNS — {{ $domain->domain_name }}</h1>
    <p class="text-sm text-slate-500 mt-1">
      Hanya berlaku kalau domain memakai nameserver bawaan registrar. Kalau nameserver sudah
      diarahkan ke penyedia lain (mis. server hosting Anda), kelola DNS di sana, bukan di sini.
    </p>
  </div>

  @if ($warning)
    <div class="card p-4 mb-5 border-amber-200 bg-amber-50/60 text-sm text-amber-800">
      <i class="fa-solid fa-triangle-exclamation"></i>
      Sebagian data tidak bisa diambil: {{ $warning }}
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
      <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100">
          <h2 class="text-sm font-semibold text-slate-800">Record Saat Ini</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
                <th class="px-5 py-2.5 font-semibold">Tipe</th>
                <th class="px-5 py-2.5 font-semibold">Host</th>
                <th class="px-5 py-2.5 font-semibold">Nilai</th>
                <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @forelse ($records as $record)
                <tr class="hover:bg-slate-50/60">
                  <td class="px-5 py-3"><span class="badge badge-inactive">{{ $record['type'] }}</span></td>
                  <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $record['hostname'] }}</td>
                  <td class="px-5 py-3 font-mono text-xs text-slate-600 break-all">
                    {{ $record['value'] }}
                    @if ($record['priority'])
                      <span class="text-slate-400">(prioritas {{ $record['priority'] }})</span>
                    @endif
                  </td>
                  <td class="px-5 py-3 text-right">
                    <form method="POST" action="{{ route('client.domains.dns.delete', $domain) }}"
                          data-confirm="Hapus record {{ $record['type'] }} {{ $record['hostname'] }}?"
                          data-confirm-title="Hapus Record DNS" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
                      @csrf @method('DELETE')
                      <input type="hidden" name="type" value="{{ $record['type'] }}">
                      <input type="hidden" name="hostname" value="{{ $record['hostname'] }}">
                      <input type="hidden" name="value" value="{{ $record['value'] }}">
                      <button type="submit" class="w-7 h-7 rounded-lg border border-rose-200 hover:bg-rose-50 inline-flex items-center justify-center text-rose-500">
                        <i class="fa-regular fa-trash-can text-xs"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">Belum ada record DNS kustom.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div>
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Tambah Record</h2>

        <form method="POST" action="{{ route('client.domains.dns.add', $domain) }}" class="space-y-3" id="dnsForm">
          @csrf

          <div>
            <label class="form-label">Tipe</label>
            <select name="type" id="dnsType" class="form-input">
              @foreach ($types as $t)
                <option value="{{ $t }}">{{ $t }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="form-label">Host</label>
            <input type="text" name="hostname" required placeholder="@ atau www" class="form-input">
            <p class="text-[11px] text-slate-400 mt-1">Isi "@" untuk domain utama.</p>
          </div>

          <div>
            <label class="form-label">Nilai</label>
            <input type="text" name="value" required placeholder="192.0.2.1" class="form-input">
          </div>

          <div id="priorityField" class="hidden">
            <label class="form-label">Prioritas (khusus MX)</label>
            <input type="number" name="priority" min="0" value="10" class="form-input">
          </div>

          @error('type') <p class="form-error">{{ $message }}</p> @enderror
          @error('hostname') <p class="form-error">{{ $message }}</p> @enderror
          @error('value') <p class="form-error">{{ $message }}</p> @enderror

          <button type="submit" class="btn btn-primary w-full">
            <i class="fa-solid fa-plus text-xs"></i> Tambah Record
          </button>
        </form>
      </div>

      <div class="card p-4 mt-5 text-xs text-slate-500 leading-relaxed">
        <p class="font-semibold text-slate-700 mb-1">Contoh nilai per tipe</p>
        <p><b>A</b> — alamat IP server, mis. <code>203.0.113.10</code></p>
        <p><b>AAAA</b> — alamat IPv6 server, mis. <code>2001:db8::1</code></p>
        <p><b>CNAME</b> — nama domain lain, mis. <code>contoh.com</code></p>
        <p><b>MX</b> — server email, mis. <code>mail.contoh.com</code></p>
        <p><b>TXT</b> — teks verifikasi, mis. <code>v=spf1 include:_spf.google.com ~all</code></p>
      </div>
    </div>
  </div>

  <script>
    // Kolom prioritas hanya relevan untuk MX.
    (function () {
      const type = document.getElementById('dnsType');
      const field = document.getElementById('priorityField');

      function sync() { field.classList.toggle('hidden', type.value !== 'MX'); }

      type.addEventListener('change', sync);
      sync();
    })();
  </script>
@endsection
