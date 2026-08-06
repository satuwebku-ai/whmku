@extends('layouts.admin')

@section('title', 'Aktivitas')

@section('content')

  @include('admin.activities._nav')

  <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">Aktivitas Aplikasi</h1>
      <p class="text-sm text-slate-500 mt-1">Catatan kejadian penting: pesanan, pembayaran, tiket, dan pendaftaran klien.</p>
    </div>
    <div class="flex items-center gap-2">
      @if ($counts['unread'] > 0)
        <form method="POST" action="{{ route('admin.activities.read-all') }}">
          @csrf
          <button type="submit" class="btn btn-outline"><i class="fa-solid fa-check-double text-xs"></i> Tandai Semua Dibaca</button>
        </form>
      @endif
      <form method="POST" action="{{ route('admin.activities.clear-old') }}"
            data-confirm="Hapus catatan yang sudah dibaca dan berumur lebih dari 30 hari?"
            data-confirm-title="Bersihkan Catatan Lama" data-confirm-style="warn" data-confirm-label="Ya, Bersihkan">
        @csrf
        <button type="submit" class="btn btn-outline"><i class="fa-regular fa-trash-can text-xs"></i> Bersihkan Lama</button>
      </form>
    </div>
  </div>

  <div class="flex items-center gap-1 mb-5 overflow-x-auto">
    <a href="{{ route('admin.activities') }}"
       class="px-3 py-1.5 text-xs font-medium rounded-full whitespace-nowrap {{ ! request('type') && ! request('unread') ? 'bg-accent text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
      Semua ({{ $counts['all'] }})
    </a>
    <a href="{{ route('admin.activities', ['unread' => 1]) }}"
       class="px-3 py-1.5 text-xs font-medium rounded-full whitespace-nowrap {{ request('unread') ? 'bg-accent text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
      Belum Dibaca ({{ $counts['unread'] }})
    </a>
    @foreach (['order' => 'Order', 'payment' => 'Pembayaran', 'ticket' => 'Tiket', 'client' => 'Klien', 'invoice' => 'Invoice'] as $t => $label)
      <a href="{{ route('admin.activities', ['type' => $t]) }}"
         class="px-3 py-1.5 text-xs font-medium rounded-full whitespace-nowrap {{ request('type') === $t ? 'bg-accent text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
        {{ $label }}
      </a>
    @endforeach
  </div>

  <div class="card overflow-hidden">
    <div class="divide-y divide-slate-100">
      @forelse ($activities as $activity)
        <div class="flex items-start gap-4 px-5 py-4 {{ $activity->read_at ? '' : 'bg-indigo-50/30' }}">
          <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $activity->level_class }}">
            <i class="fa-solid {{ $activity->icon }} text-sm"></i>
          </span>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-800">
              {{ $activity->title }}
              @unless ($activity->read_at)
                <span class="ml-1 w-2 h-2 rounded-full bg-accent inline-block align-middle"></span>
              @endunless
            </p>
            @if ($activity->description)
              <p class="text-xs text-slate-500 mt-0.5">{{ $activity->description }}</p>
            @endif
            <p class="text-[11px] text-slate-400 mt-1">{{ $activity->created_at->diffForHumans() }}</p>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            @if ($activity->link)
              <a href="{{ $activity->link }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500" title="Buka">
                <i class="fa-solid fa-arrow-right text-xs"></i>
              </a>
            @endif
            <form method="POST" action="{{ route('admin.activity.delete', $activity) }}">
              @csrf @method('DELETE')
              <button type="submit" class="w-8 h-8 rounded-lg border border-rose-200 hover:bg-rose-50 flex items-center justify-center text-rose-500" title="Hapus">
                <i class="fa-regular fa-trash-can text-xs"></i>
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="px-5 py-12 text-center">
          <p class="text-slate-500 text-sm mb-1">Belum ada aktivitas tercatat.</p>
          <p class="text-xs text-slate-400">Kejadian akan muncul di sini saat ada pesanan, pembayaran, atau tiket masuk.</p>
        </div>
      @endforelse
    </div>

    @if ($activities->hasPages())
      <div class="px-5 py-4 border-t border-slate-100">{{ $activities->links() }}</div>
    @endif
  </div>

@endsection
