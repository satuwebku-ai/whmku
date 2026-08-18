@extends('layouts.admin')

@section('title', 'Detail Domain — ' . $domain->domain_name)

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="{{ route('admin.domains') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Domain</a>
      <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $domain->domain_name }}</h1>
    </div>
    <span class="badge badge-{{ $domain->status === 'expired' ? 'suspended' : $domain->status }} !text-sm !px-3 !py-1">{{ ucfirst($domain->status) }}</span>
  </div>

  @if ($domain->provision_status === 'needs_documents' || $domain->documents->isNotEmpty())
    <div class="card p-5 mb-5 {{ $domain->provision_status === 'needs_documents' ? 'border-amber-200 bg-amber-50/60' : '' }}">
      <div class="flex items-center justify-between mb-3 flex-wrap gap-3">
        <h2 class="text-sm font-semibold {{ $domain->provision_status === 'needs_documents' ? 'text-amber-800' : 'text-slate-800' }}">
          <i class="fa-solid fa-file-lines"></i> Dokumen Persyaratan Domain
        </h2>
        @if ($domain->provision_status === 'needs_documents')
          <form method="POST" action="{{ route('admin.domains.verify-documents', $domain) }}"
                data-confirm="Tandai dokumen untuk &quot;{{ $domain->domain_name }}&quot; sudah lengkap dan lanjutkan pendaftaran?"
                data-confirm-title="Dokumen Lengkap?" data-confirm-style="success" data-confirm-label="Ya, Lanjutkan">
            @csrf
            <button type="submit" class="btn btn-primary !py-1.5 !px-3 text-xs">
              <i class="fa-solid fa-check text-xs"></i> Dokumen Lengkap, Lanjutkan
            </button>
          </form>
        @endif
      </div>

      @forelse ($domain->documents as $doc)
        <div class="flex items-center justify-between py-2.5 border-b border-slate-100 last:border-0">
          <a href="{{ route('admin.domain-documents.file', $doc) }}" target="_blank" class="text-sm text-slate-700 hover:text-accent flex items-center gap-2 min-w-0">
            <i class="fa-solid fa-file text-slate-300 shrink-0"></i>
            <span class="truncate">{{ $doc->original_name }}</span>
          </a>
          <form method="POST" action="{{ route('admin.domain-documents.review', $doc) }}" class="flex items-center gap-2 shrink-0">
            @csrf
            <select name="status" class="form-input !text-xs !py-1">
              <option value="pending" @selected($doc->status === 'pending')>Menunggu</option>
              <option value="approved" @selected($doc->status === 'approved')>Setuju</option>
              <option value="rejected" @selected($doc->status === 'rejected')>Tolak</option>
            </select>
            <input type="text" name="admin_note" value="{{ $doc->admin_note }}" placeholder="Catatan (kalau ditolak)" class="form-input !text-xs !py-1 w-40">
            <button type="submit" class="btn btn-outline !py-1 !px-2 text-xs">Simpan</button>
          </form>
        </div>
      @empty
        <p class="text-sm text-slate-400">Klien belum mengunggah dokumen apa pun.</p>
      @endforelse
    </div>
  @endif

  @if ($domain->is_transfer && $domain->provision_status === 'transfer_pending')
    <div class="card p-4 mb-5 border-amber-200 bg-amber-50/60">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <p class="text-sm text-amber-800">
          <i class="fa-solid fa-clock"></i>
          Permintaan transfer sudah dikirim, menunggu persetujuan pemilik domain di registrar lama
          (biasanya 5–7 hari). Cek status di dashboard Liqu.id, lalu konfirmasi di sini kalau sudah selesai.
        </p>
        <form method="POST" action="{{ route('admin.domains.transfer-complete', $domain) }}"
              data-confirm="Konfirmasi transfer &quot;{{ $domain->domain_name }}&quot; sudah benar-benar selesai di Liqu.id?"
              data-confirm-title="Konfirmasi Transfer Selesai" data-confirm-style="success" data-confirm-label="Ya, Sudah Selesai">
          @csrf
          <button type="submit" class="btn btn-primary !py-1.5 !px-3 text-xs shrink-0">
            <i class="fa-solid fa-check text-xs"></i> Tandai Transfer Selesai
          </button>
        </form>
      </div>
    </div>
  @endif

  @if ($domain->status === 'expired' && $domain->registrar)
    <div class="card p-4 mb-5 border-rose-200 bg-rose-50/60">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <p class="text-sm text-rose-800">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Domain ini sudah kedaluwarsa. Masih mungkin dipulihkan lewat masa tenggang registrar
          (biasanya ~30 hari sejak kedaluwarsa, beda-beda tiap TLD) — ada biaya tambahan dari registrar.
        </p>
        <form method="POST" action="{{ route('admin.domains.restore', $domain) }}"
              data-confirm="Coba pulihkan &quot;{{ $domain->domain_name }}&quot; dari masa tenggang? Registrar mungkin mengenakan biaya tambahan."
              data-confirm-title="Pulihkan Domain" data-confirm-style="warn" data-confirm-label="Ya, Coba Pulihkan">
          @csrf
          <button type="submit" class="btn !bg-rose-600 !text-white !border-rose-600 !py-1.5 !px-3 text-xs shrink-0">
            <i class="fa-solid fa-rotate-left text-xs"></i> Coba Pulihkan
          </button>
        </form>
      </div>
    </div>
  @endif

  @if ($domain->provision_status === 'failed')
    <div class="card p-4 mb-5 border-rose-200 bg-rose-50/60">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
          <p class="text-sm text-rose-800 font-medium">
            <i class="fa-solid fa-circle-exclamation"></i> Pendaftaran domain gagal
          </p>
          <p class="text-xs text-rose-600 mt-0.5">{{ $domain->provision_message }}</p>
        </div>
        <form method="POST" action="{{ route('admin.domains.retry', $domain) }}"
              data-confirm="Coba daftarkan &quot;{{ $domain->domain_name }}&quot; lagi sekarang?" data-confirm-title="Coba Ulang" data-confirm-style="info" data-confirm-label="Ya, Coba Lagi">
          @csrf
          <button type="submit" class="btn !bg-rose-600 !text-white !border-rose-600 !py-1.5 !px-3 text-xs shrink-0">
            <i class="fa-solid fa-rotate-right text-xs"></i> Coba Daftarkan Ulang
          </button>
        </form>
      </div>
    </div>
  @endif

  @if ($domain->provision_status === 'needs_eligibility')
    @php
      $tldExt = ltrim($domain->tld?->extension ?? '', '.');
      $hasExample = in_array($tldExt, ['us', 'asia']);
      $exampleValue = ['us' => 'us_purpose=business&us_category=citizen', 'asia' => 'asia_contact_id=0'][$tldExt] ?? null;
    @endphp
    <div class="card p-5 mb-5 border-amber-200 bg-amber-50/60">
      <h2 class="text-sm font-semibold text-amber-800 mb-1">
        <i class="fa-solid fa-clipboard-list"></i> Butuh Data Kelayakan (Eligibility)
      </h2>
      <p class="text-sm text-amber-700 mb-4">
        Domain <b>.{{ $tldExt }}</b> mewajibkan data kelayakan tambahan dari registry aslinya sebelum bisa
        didaftarkan — belum otomatis diproses, menunggu diisi di sini.
        @if (! $hasExample)
          <br><b>Perhatian:</b> saya tidak punya format resmi untuk <code>.{{ $tldExt }}</code> —
          cek dulu di dashboard Liqu.id atau tanya support mereka sebelum mengisi, supaya tidak salah format.
        @endif
      </p>
      <form method="POST" action="{{ route('admin.domains.eligibility', $domain) }}" class="grid sm:grid-cols-2 gap-3">
        @csrf
        <div>
          <label class="form-label">Eligibility Criteria</label>
          <input type="text" name="eligibility_criteria" value="{{ old('eligibility_criteria', $hasExample ? $tldExt : '') }}" class="form-input" placeholder="{{ $tldExt }}">
        </div>
        <div>
          <label class="form-label">Extra Data</label>
          <input type="text" name="eligibility_extra" value="{{ old('eligibility_extra') }}" class="form-input" placeholder="{{ $exampleValue ?? 'format sesuai TLD, cek dokumentasi Liqu.id' }}">
        </div>
        @error('eligibility_criteria') <p class="form-error sm:col-span-2">{{ $message }}</p> @enderror
        @error('eligibility_extra') <p class="form-error sm:col-span-2">{{ $message }}</p> @enderror
        <button type="submit" class="btn btn-primary sm:col-span-2 justify-self-start">
          <i class="fa-solid fa-check text-xs"></i> Simpan &amp; Coba Daftarkan
        </button>
      </form>
    </div>
  @endif

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Informasi Domain</h2>
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Klien</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->client->name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Registrar</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->registrar->name ?? 'Manual' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Tanggal Register</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->register_date?->format('d M Y') ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Jatuh Tempo</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->expiry_date?->format('d M Y') ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Auto Renew</dt>
            <dd class="text-slate-700 font-medium">{{ $domain->auto_renew ? 'Ya' : 'Tidak' }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">WHOIS Privacy</dt>
            <dd class="text-slate-700 font-medium">
              {{ $domain->hasActivePrivacy() ? 'Aktif' : 'Nonaktif' }}
              @if ($domain->privacy_expires_at)
                <span class="text-xs text-slate-400 font-normal">
                  (s.d. {{ $domain->privacy_expires_at->format('d M Y') }})
                </span>
              @endif
              @if ($domain->privacy_invoice_id)
                <span class="badge badge-pending !text-[10px] ml-1">Menunggu bayar</span>
              @endif

              @if (! is_null($privacyAtRegistrar) && $privacyAtRegistrar !== $domain->hasActivePrivacy())
                <p class="text-xs text-rose-600 font-normal mt-1">
                  <i class="fa-solid fa-triangle-exclamation"></i>
                  Di registrar: <b>{{ $privacyAtRegistrar ? 'Aktif' : 'Nonaktif' }}</b> — tidak cocok.
                  @if ($privacyAtRegistrar)
                    Kita masih ditagih registrar padahal klien tidak membayar.
                  @else
                    Klien sudah bayar tapi belum aktif di registrar.
                  @endif
                </p>
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Nameserver</dt>
            <dd class="text-slate-700 font-medium">
              @if (! empty($domain->nameservers))
                @foreach ($domain->nameservers as $ns)
                  <span class="block text-sm font-mono">{{ $ns }}</span>
                @endforeach
              @else
                <span class="text-rose-500 text-sm">
                  <i class="fa-solid fa-triangle-exclamation"></i> Belum diatur
                </span>
                @if ($domain->registrar && $domain->registrar->default_ns1)
                  <form method="POST" action="{{ route('admin.domains.apply-default-ns', $domain) }}" class="mt-1"
                        data-confirm="Terapkan nameserver default ({{ $domain->registrar->default_ns1 }}) ke domain ini?" data-confirm-title="Terapkan Nameserver Default" data-confirm-style="info" data-confirm-label="Ya, Terapkan">
                    @csrf
                    <button type="submit" class="text-xs text-accent hover:underline">Terapkan Nameserver Default</button>
                  </form>
                @endif
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs mb-0.5">Order Terkait</dt>
            <dd class="text-slate-700 font-medium">
              @if ($domain->order)
                <a href="{{ route('admin.orders.details', $domain->order) }}" class="text-accent hover:underline">#{{ $domain->order->order_number }}</a>
              @else
                —
              @endif
            </dd>
          </div>
        </dl>

        @if ($domain->provision_message)
          <div class="mt-4 pt-4 border-t border-slate-100 text-sm">
            <span class="text-slate-400 text-xs">Status Registrasi Terakhir</span>
            <p class="{{ $domain->provision_status === 'registered' ? 'text-emerald-600' : 'text-rose-600' }} mt-0.5">{{ $domain->provision_message }}</p>
          </div>
        @endif
      </div>

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Catatan Internal</h2>
        <form method="POST" action="{{ route('admin.domain.notes') }}">
          @csrf
          <input type="hidden" name="domain_id" value="{{ $domain->id }}">
          <textarea name="internal_notes" rows="4" class="form-input" placeholder="Catatan staf tentang domain ini...">{{ old('internal_notes', $domain->internal_notes) }}</textarea>
          <button type="submit" class="btn btn-outline mt-3"><i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Catatan</button>
        </form>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Aksi</h2>
        <div class="space-y-2">
          @if ($domain->registrar)
            <form method="POST" action="{{ route('admin.domains.renew', $domain) }}" data-confirm="Perpanjang domain ini 1 tahun via registrar?" data-confirm-title="Perpanjang Domain" data-confirm-style="info" data-confirm-label="Ya, Perpanjang" >
              @csrf
              <button type="submit" class="w-full btn btn-primary !justify-start"><i class="fa-solid fa-rotate text-xs"></i> Perpanjang 1 Tahun</button>
            </form>
          @endif
          @if ($domain->status !== 'cancelled')
            <form method="POST" action="{{ route('admin.domain.cancel') }}" data-confirm="Batalkan domain ini?" data-confirm-title="Batalkan" data-confirm-style="warn" data-confirm-label="Ya, Batalkan" >
              @csrf
              <input type="hidden" name="domain_id" value="{{ $domain->id }}">
              <button type="submit" class="w-full btn btn-danger-soft !justify-start"><i class="fa-solid fa-xmark text-xs"></i> Batalkan</button>
            </form>
          @endif
          <a href="{{ route('admin.domain.edit.page', $domain) }}" class="w-full btn btn-outline !justify-start"><i class="fa-regular fa-pen-to-square text-xs"></i> Edit Data</a>
        </div>
      </div>
    </div>
  </div>

@endsection
