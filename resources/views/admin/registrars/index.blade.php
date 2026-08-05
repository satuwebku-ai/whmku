@extends('layouts.admin')

@section('title', 'Registrar Domain')

@section('content')

  @include('admin.domains._nav')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Registrar Domain</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola koneksi ke Namecheap, Liqu.id, atau ResellBiz untuk registrasi domain otomatis.</p>
    </div>
    <a href="{{ route('admin.registrars.create') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Tambah Registrar
    </a>
  </div>

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Nama</th>
            <th class="px-5 py-2.5 font-semibold">Provider</th>
            <th class="px-5 py-2.5 font-semibold">Mode / Endpoint</th>
            <th class="px-5 py-2.5 font-semibold text-center">TLD</th>
            <th class="px-5 py-2.5 font-semibold text-center">Domain</th>
            <th class="px-5 py-2.5 font-semibold">Cek Terakhir</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($registrars as $registrar)
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3 font-medium text-slate-700">
                {{ $registrar->name }}
                @if ($registrar->is_default)
                  <span class="badge badge-active ml-1">Default</span>
                @endif
              </td>
              <td class="px-5 py-3 text-slate-600">
                {{ ['namecheap' => 'Namecheap', 'liquid' => 'Liqu.id', 'resellbiz' => 'ResellBiz'][$registrar->provider] ?? ucfirst($registrar->provider) }}
              </td>
              <td class="px-5 py-3 text-slate-600 text-xs">
                @if ($registrar->provider === 'namecheap')
                  {{ $registrar->sandbox ? 'Sandbox' : 'Production' }}
                @else
                  {{ \Illuminate\Support\Str::limit(preg_replace('#^https?://#', '', (string) $registrar->api_url), 28) ?: '—' }}
                @endif
              </td>
              <td class="px-5 py-3 text-center text-slate-600">{{ $registrar->tlds_count }}</td>
              <td class="px-5 py-3 text-center text-slate-600">{{ $registrar->domains_count }}</td>
              <td class="px-5 py-3 text-slate-500 text-xs">
                @if ($registrar->last_checked_at)
                  {{ $registrar->last_checked_at->diffForHumans() }}
                  <br>
                  <span class="{{ $registrar->last_check_status === 'ok' ? 'text-emerald-600' : 'text-rose-500' }}">
                    {{ $registrar->last_check_status === 'ok' ? 'Terhubung' : \Illuminate\Support\Str::limit($registrar->last_check_status, 40) }}
                  </span>
                @else
                  Belum pernah dicek
                @endif
              </td>
              <td class="px-5 py-3">
                <span class="badge {{ $registrar->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $registrar->is_active ? 'Aktif' : 'Nonaktif' }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <form method="POST" action="{{ route('admin.registrars.test-connection', $registrar) }}">
                    @csrf
                    <button type="submit" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Tes Koneksi">
                      <i class="fa-solid fa-plug text-xs"></i>
                    </button>
                  </form>
                  @if ($registrar->provider === 'liquid')
                    <form method="POST" action="{{ route('admin.registrars.sync-tlds', $registrar) }}"
                          data-confirm="Impor daftar TLD dari registrar ini? Harga TLD yang sudah ada tidak akan diubah." data-confirm-title="Sinkronkan TLD" data-confirm-style="info" data-confirm-label="Ya, Impor" >
                      @csrf
                      <button type="submit" class="w-8 h-8 rounded-lg border border-indigo-200 hover:bg-indigo-50 flex items-center justify-center text-indigo-600" title="Sinkronkan daftar TLD">
                        <i class="fa-solid fa-rotate text-xs"></i>
                      </button>
                    </form>
                  @endif
                  <a href="{{ route('admin.registrars.edit', $registrar) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Edit">
                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.registrars.destroy', $registrar) }}" data-confirm="Hapus registrar ini?" data-confirm-title="Hapus Data" data-confirm-style="danger" data-confirm-label="Ya, Hapus" >
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-400">Belum ada registrar terhubung.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($registrars->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $registrars->links() }}</div>
    @endif
  </div>

@endsection
