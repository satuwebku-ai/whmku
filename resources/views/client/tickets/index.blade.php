@extends('client.layout')
@section('title', 'Tiket Support')

@section('content')
  <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Tiket Support</h1>
      <p class="text-sm text-slate-500 mt-1">Riwayat percakapan Anda dengan tim kami.</p>
    </div>
    <a href="{{ route('client.tickets.create') }}" class="btn btn-primary">
      <i class="fa-solid fa-plus text-xs"></i> Buat Tiket
    </a>
  </div>

  <div class="flex gap-1 mb-5">
    @php $s = request('status'); @endphp
    <a href="{{ route('client.tickets') }}" class="px-3 py-1.5 rounded-full text-xs font-medium {{ !$s ? 'bg-accent text-white' : 'bg-white border border-slate-200 text-slate-600' }}">Semua</a>
    <a href="{{ route('client.tickets', ['status' => 'open']) }}" class="px-3 py-1.5 rounded-full text-xs font-medium {{ $s === 'open' ? 'bg-accent text-white' : 'bg-white border border-slate-200 text-slate-600' }}">Aktif</a>
    <a href="{{ route('client.tickets', ['status' => 'closed']) }}" class="px-3 py-1.5 rounded-full text-xs font-medium {{ $s === 'closed' ? 'bg-accent text-white' : 'bg-white border border-slate-200 text-slate-600' }}">Ditutup</a>
  </div>

  <div class="space-y-3">
    @forelse ($tickets as $ticket)
      <a href="{{ route('client.tickets.show', $ticket) }}" class="card p-5 flex items-center justify-between gap-4 hover:border-accent/40 transition-colors">
        <div class="min-w-0">
          <p class="font-semibold text-slate-800 truncate">{{ $ticket->subject }}</p>
          <p class="text-xs text-slate-400 mt-1">
            {{ $ticket->ticket_number }} · {{ $ticket->public_replies_count }} pesan ·
            update {{ $ticket->last_reply_at?->diffForHumans() }}
          </p>
        </div>
        <div class="text-right shrink-0">
          <span class="badge badge-{{ $ticket->status_badge }}">{{ $ticket->status_label }}</span>
        </div>
      </a>
    @empty
      <div class="card p-10 text-center">
        <p class="text-slate-400 text-sm mb-3">Belum ada tiket.</p>
        <a href="{{ route('client.tickets.create') }}" class="btn btn-primary">Buat Tiket Pertama</a>
      </div>
    @endforelse
  </div>

  @if ($tickets->hasPages())
    <div class="mt-5">{{ $tickets->links() }}</div>
  @endif
@endsection
