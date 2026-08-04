@extends('client.layout')
@section('title', $ticket->ticket_number)

@section('content')
  <a href="{{ route('client.tickets') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Kembali ke Tiket</a>

  <div class="flex items-center justify-between mt-2 mb-5 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">{{ $ticket->subject }}</h1>
      <p class="text-sm text-slate-500">{{ $ticket->ticket_number }} · dibuat {{ $ticket->created_at->format('d M Y H:i') }}</p>
    </div>
    <span class="badge badge-{{ $ticket->status_badge }} !text-sm !px-3 !py-1">{{ $ticket->status_label }}</span>
  </div>

  {{-- Percakapan --}}
  <div class="space-y-3 mb-5">
    @foreach ($ticket->publicReplies as $reply)
      <div class="card p-5 {{ $reply->isFromStaff() ? 'border-indigo-100 bg-indigo-50/40' : '' }}">
        <div class="flex items-center gap-2.5 mb-2.5">
          <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0
                       {{ $reply->isFromStaff() ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-200 text-slate-600' }}">
            {{ strtoupper(substr($reply->author_name, 0, 2)) }}
          </span>
          <div>
            <p class="text-sm font-semibold text-slate-700">
              {{ $reply->isFromStaff() ? $reply->author_name . ' (Tim Support)' : 'Anda' }}
            </p>
            <p class="text-xs text-slate-400">{{ $reply->created_at->format('d M Y H:i') }}</p>
          </div>
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

  {{-- Balas --}}
  @if ($ticket->isClosed())
    <div class="card p-6 text-center">
      <p class="text-sm text-slate-500 mb-3">
        Tiket ini sudah ditutup {{ $ticket->closed_at?->diffForHumans() }}.
      </p>
      <a href="{{ route('client.tickets.create') }}" class="btn btn-primary">Buat Tiket Baru</a>
    </div>
  @else
    <div class="card p-6">
      <h2 class="text-sm font-semibold text-slate-800 mb-3">Balas</h2>
      <form method="POST" action="{{ route('client.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-3">
        @csrf
        <textarea name="message" rows="5" required class="form-input" placeholder="Tulis balasan Anda...">{{ old('message') }}</textarea>
        @error('message') <p class="form-error">{{ $message }}</p> @enderror

        <div>
          <label class="form-label">Lampiran <span class="text-slate-400 font-normal">(opsional)</span></label>
          <input type="file" name="attachment" class="form-input text-xs">
          @error('attachment') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane text-xs"></i> Kirim Balasan</button>
        </div>
      </form>

      <form method="POST" action="{{ route('client.tickets.close', $ticket) }}" class="mt-4 pt-4 border-t border-slate-100"
            onsubmit="return confirm('Tutup tiket ini? Anda tetap bisa membuat tiket baru nanti.');">
        @csrf
        <button type="submit" class="text-xs text-slate-400 hover:text-slate-600">
          <i class="fa-solid fa-check-double"></i> Masalah sudah selesai — tutup tiket ini
        </button>
      </form>
    </div>
  @endif
@endsection
