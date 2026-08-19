@extends('layouts.admin')

@section('title', 'Tiket ' . $ticket->ticket_number)

@section('content')

  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="{{ route('admin.tickets') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Tiket</a>
      <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $ticket->subject }}</h1>
      <p class="text-sm text-slate-500">{{ $ticket->ticket_number }} · dibuat {{ $ticket->created_at->format('d M Y H:i') }}</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="badge badge-{{ $ticket->priority_badge }} !text-sm !px-3 !py-1">{{ ucfirst($ticket->priority) }}</span>
      <span class="badge badge-{{ $ticket->status_badge }} !text-sm !px-3 !py-1">{{ $ticket->status_label }}</span>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

      {{-- Percakapan --}}
      <div class="space-y-3">
        @foreach ($ticket->replies as $reply)
          <div class="card p-5 {{ $reply->is_internal_note ? 'border-amber-200 bg-amber-50/40' : '' }} {{ $reply->isFromStaff() && ! $reply->is_internal_note ? 'border-indigo-100 bg-indigo-50/30' : '' }}">
            <div class="flex items-center justify-between mb-2.5">
              <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0
                             {{ $reply->isFromStaff() ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-200 text-slate-600' }}">
                  {{ strtoupper(substr($reply->author_name, 0, 2)) }}
                </span>
                <div>
                  <p class="text-sm font-semibold text-slate-700">
                    {{ $reply->author_name }}
                    <span class="text-xs font-normal text-slate-400">{{ $reply->isFromStaff() ? '(Staf)' : '(Klien)' }}</span>
                  </p>
                  <p class="text-xs text-slate-400">{{ $reply->created_at->format('d M Y H:i') }}</p>
                </div>
              </div>
              @if ($reply->is_internal_note)
                <span class="badge badge-pending"><i class="fa-solid fa-lock"></i> Catatan Internal</span>
              @endif
            </div>

            <div class="text-sm text-slate-600 whitespace-pre-line leading-relaxed">{{ $reply->message }}</div>

            @if ($reply->attachment_path)
              <a href="{{ asset('storage/' . $reply->attachment_path) }}" target="_blank"
                 class="inline-flex items-center gap-2 mt-3 text-xs text-accent hover:underline">
                <i class="fa-solid fa-paperclip"></i> {{ $reply->attachment_name }}
              </a>
            @endif
          </div>
        @endforeach
      </div>

      {{-- Form balasan --}}
      @if (! $ticket->isClosed())
        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-800 mb-3">Balas Tiket</h2>
          <form method="POST" action="{{ route('admin.ticket.reply') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">

            <textarea name="message" rows="5" class="form-input" placeholder="Tulis balasan untuk klien..." required>{{ old('message') }}</textarea>
            @error('message') <p class="form-error">{{ $message }}</p> @enderror

            <div>
              <label class="form-label">Lampiran (opsional, maks 5MB)</label>
              <input type="file" name="attachment" class="form-input text-xs">
              @error('attachment') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
              <input type="checkbox" name="is_internal_note" value="1" class="rounded border-slate-300 text-accent focus:ring-accent/40">
              Simpan sebagai catatan internal (tidak terlihat klien, status tiket tidak berubah)
            </label>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane text-xs"></i> Kirim Balasan</button>
          </form>
        </div>
      @else
        <div class="card p-5 text-center text-sm text-slate-500">
          Tiket ini sudah ditutup {{ $ticket->closed_at?->diffForHumans() }}. Buka kembali untuk membalas.
        </div>
      @endif
    </div>

    {{-- Sidebar aksi --}}
    <div class="space-y-5">
      @if ($ticket->domain && str_starts_with($ticket->subject, 'Permintaan Kode Transfer'))
        <div class="card p-5 border-amber-200 bg-amber-50/40">
          <h2 class="text-sm font-semibold text-amber-800 mb-1">
            <i class="fa-solid fa-key"></i> Permintaan Kode Transfer
          </h2>
          <p class="text-xs text-amber-700 mb-3">
            Domain: <a href="{{ route('admin.domains.details', $ticket->domain) }}" class="underline">{{ $ticket->domain->domain_name }}</a>
          </p>

          @if (session('preview_transfer_code'))
            <div class="rounded-lg bg-slate-800 text-white px-3 py-2.5 text-sm mb-3">
              <p class="text-slate-300 text-[11px] mb-1">Kode transfer (pratinjau — belum terkirim ke klien):</p>
              <p class="font-mono font-bold tracking-wide">{{ session('preview_transfer_code') }}</p>
            </div>
            <form method="POST" action="{{ route('admin.tickets.approve-transfer-code', $ticket) }}"
                  data-confirm="Kirim kode ini ke email klien sekarang?" data-confirm-title="Setujui & Kirim" data-confirm-style="warn" data-confirm-label="Ya, Kirim ke Klien">
              @csrf
              <input type="hidden" name="code" value="{{ session('preview_transfer_code') }}">
              <button type="submit" class="btn btn-primary w-full !text-sm">
                <i class="fa-solid fa-paper-plane text-xs"></i> Setujui & Kirim ke Email Klien
              </button>
            </form>
          @else
            <form method="POST" action="{{ route('admin.tickets.preview-transfer-code', $ticket) }}">
              @csrf
              <button type="submit" class="btn btn-outline w-full !text-sm !border-amber-300 !text-amber-700 hover:!bg-amber-100">
                <i class="fa-solid fa-eye text-xs"></i> Lihat Kode Dulu
              </button>
            </form>
          @endif
        </div>
      @endif

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Informasi</h2>
        <dl class="space-y-3 text-sm">
          <div>
            <dt class="text-slate-400 text-xs">Klien</dt>
            <dd class="text-slate-700 font-medium">
              @if ($ticket->client)
                <a href="{{ route('admin.clients.details', $ticket->client) }}" class="text-accent hover:underline">{{ $ticket->client->name }}</a>
              @else
                —
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs">Departemen</dt>
            <dd class="text-slate-700 font-medium capitalize">{{ $ticket->department }}</dd>
          </div>
          <div>
            <dt class="text-slate-400 text-xs">Balasan Terakhir</dt>
            <dd class="text-slate-700 font-medium">{{ $ticket->last_reply_at?->diffForHumans() ?? '—' }}</dd>
          </div>
        </dl>
      </div>

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Kelola</h2>
        <div class="space-y-3">

          <form method="POST" action="{{ route('admin.ticket.assign') }}">
            @csrf
            <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
            <label class="form-label">Tugaskan ke</label>
            <div class="flex gap-2">
              <select name="assigned_to" class="form-input">
                <option value="">— Belum ditugaskan —</option>
                @foreach ($admins as $admin)
                  <option value="{{ $admin->id }}" @selected($ticket->assigned_to == $admin->id)>{{ $admin->name }}</option>
                @endforeach
              </select>
              <button type="submit" class="btn btn-outline shrink-0"><i class="fa-solid fa-check text-xs"></i></button>
            </div>
          </form>

          <form method="POST" action="{{ route('admin.ticket.priority') }}">
            @csrf
            <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
            <label class="form-label">Prioritas</label>
            <div class="flex gap-2">
              <select name="priority" class="form-input">
                <option value="low" @selected($ticket->priority === 'low')>Low</option>
                <option value="medium" @selected($ticket->priority === 'medium')>Medium</option>
                <option value="high" @selected($ticket->priority === 'high')>High</option>
                <option value="urgent" @selected($ticket->priority === 'urgent')>Urgent</option>
              </select>
              <button type="submit" class="btn btn-outline shrink-0"><i class="fa-solid fa-check text-xs"></i></button>
            </div>
          </form>

          <div class="pt-2 border-t border-slate-100">
            @if ($ticket->isClosed())
              <form method="POST" action="{{ route('admin.ticket.reopen') }}">
                @csrf
                <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                <button type="submit" class="w-full btn btn-primary !justify-start"><i class="fa-solid fa-rotate-left text-xs"></i> Buka Kembali</button>
              </form>
            @else
              <form method="POST" action="{{ route('admin.ticket.close') }}" data-confirm="Tutup tiket ini?" data-confirm-title="Tutup Tiket" data-confirm-style="warn" data-confirm-label="Ya, Tutup" >
                @csrf
                <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                <button type="submit" class="w-full btn btn-danger-soft !justify-start"><i class="fa-solid fa-check-double text-xs"></i> Tutup Tiket</button>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection
