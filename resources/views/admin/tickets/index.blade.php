@extends('layouts.admin')

@section('title', 'Support Ticket')

@section('content')

  @include('admin.tickets._nav')

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Support Ticket</h1>
      <p class="text-sm text-slate-500 mt-1">Tiket yang butuh perhatian otomatis muncul di urutan atas.</p>
    </div>
    <a href="{{ route('admin.ticket.add.page') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Buat Tiket
    </a>
  </div>

  <div class="card overflow-hidden">
    <form method="GET" class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor tiket / subjek..." class="form-input sm:max-w-xs">
      <select name="priority" class="form-input sm:max-w-[160px]">
        <option value="">Semua Prioritas</option>
        <option value="urgent" @selected(request('priority') === 'urgent')>Urgent</option>
        <option value="high" @selected(request('priority') === 'high')>High</option>
        <option value="medium" @selected(request('priority') === 'medium')>Medium</option>
        <option value="low" @selected(request('priority') === 'low')>Low</option>
      </select>
      <button type="submit" class="btn btn-outline">Filter</button>
      @if (request('search') || request('priority'))
        <a href="{{ url()->current() }}" class="btn btn-outline">Reset</a>
      @endif
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-slate-400 uppercase tracking-wide bg-slate-50">
            <th class="px-5 py-2.5 font-semibold">Tiket</th>
            <th class="px-5 py-2.5 font-semibold">Klien</th>
            <th class="px-5 py-2.5 font-semibold">Departemen</th>
            <th class="px-5 py-2.5 font-semibold">Prioritas</th>
            <th class="px-5 py-2.5 font-semibold">Ditugaskan</th>
            <th class="px-5 py-2.5 font-semibold">Status</th>
            <th class="px-5 py-2.5 font-semibold">Update</th>
            <th class="px-5 py-2.5 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($tickets as $ticket)
            <tr class="hover:bg-slate-50/60 {{ $ticket->needsAttention() ? 'bg-amber-50/30' : '' }}">
              <td class="px-5 py-3">
                <a href="{{ route('admin.tickets.details', $ticket) }}" class="font-medium text-slate-700 hover:text-accent">
                  {{ $ticket->subject }}
                </a>
                <p class="text-xs text-slate-400">{{ $ticket->ticket_number }} · {{ $ticket->replies_count }} balasan</p>
              </td>
              <td class="px-5 py-3 text-slate-600">{{ $ticket->client->name ?? '—' }}</td>
              <td class="px-5 py-3 text-slate-600 capitalize">{{ $ticket->department }}</td>
              <td class="px-5 py-3"><span class="badge badge-{{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span></td>
              <td class="px-5 py-3 text-slate-600">{{ $ticket->assignee->name ?? '—' }}</td>
              <td class="px-5 py-3"><span class="badge badge-{{ $ticket->status_badge }}">{{ $ticket->status_label }}</span></td>
              <td class="px-5 py-3 text-slate-500 text-xs">{{ $ticket->last_reply_at?->diffForHumans() ?? '—' }}</td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.tickets.details', $ticket) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Buka">
                    <i class="fa-regular fa-comments text-xs"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.ticket.delete', $ticket) }}" data-confirm="Hapus tiket ini beserta semua balasannya?" data-confirm-title="Hapus Data" data-confirm-style="danger" data-confirm-label="Ya, Hapus" >
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                      <i class="fa-regular fa-trash-can text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-400">Tidak ada tiket di kategori ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($tickets->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $tickets->links() }}</div>
    @endif
  </div>

@endsection
