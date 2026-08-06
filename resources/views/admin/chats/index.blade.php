@extends('layouts.admin')

@section('title', 'Live Chat')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Live Chat</h1>
    <p class="text-sm text-slate-500 mt-1">Percakapan dari widget chat di halaman publik dan area klien.</p>
  </div>

  <div class="flex items-center gap-1 mb-5">
    <a href="{{ route('admin.chats') }}"
       class="px-3 py-1.5 text-xs font-medium rounded-full {{ request('status') !== 'closed' ? 'bg-accent text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
      Aktif ({{ $counts['open'] }})
      @if ($counts['unread'] > 0)
        <span class="ml-1 px-1.5 rounded-full bg-rose-500 text-white">{{ $counts['unread'] }}</span>
      @endif
    </a>
    <a href="{{ route('admin.chats', ['status' => 'closed']) }}"
       class="px-3 py-1.5 text-xs font-medium rounded-full {{ request('status') === 'closed' ? 'bg-accent text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
      Ditutup ({{ $counts['closed'] }})
    </a>
  </div>

  <div class="card overflow-hidden">
    <div class="divide-y divide-slate-100">
      @forelse ($conversations as $chat)
        <a href="{{ route('admin.chats.show', $chat) }}"
           class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50/60 {{ $chat->unread_for_admin > 0 ? 'bg-indigo-50/30' : '' }}">
          <span class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center shrink-0">
            {{ $chat->initials }}
          </span>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-800 truncate">
              {{ $chat->display_name }}
              @if ($chat->client_id)
                <span class="badge badge-active ml-1">Klien</span>
              @else
                <span class="badge badge-inactive ml-1">Tamu</span>
              @endif
            </p>
            <p class="text-xs text-slate-500 truncate">
              {{ \Illuminate\Support\Str::limit(optional($chat->messages()->latest('id')->first())->message ?? 'Lampiran', 70) }}
            </p>
          </div>

          <div class="text-right shrink-0">
            <p class="text-[11px] text-slate-400">{{ $chat->last_message_at?->diffForHumans() }}</p>
            @if ($chat->unread_for_admin > 0)
              <span class="inline-block mt-1 min-w-[20px] px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-bold">
                {{ $chat->unread_for_admin }}
              </span>
            @endif
          </div>
        </a>
      @empty
        <div class="px-5 py-12 text-center">
          <p class="text-slate-500 text-sm mb-1">Belum ada percakapan.</p>
          <p class="text-xs text-slate-400">
            Pastikan widget sudah aktif di <a href="{{ route('admin.settings.livechat') }}" class="text-accent hover:underline">Pengaturan → Live Chat</a>.
          </p>
        </div>
      @endforelse
    </div>

    @if ($conversations->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $conversations->links() }}</div>
    @endif
  </div>

@endsection
