@extends('layouts.admin')

@section('title', 'Chat — ' . $chat->display_name)

@section('content')

  <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
      <a href="{{ route('admin.chats') }}" class="text-xs text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i> Kembali ke Live Chat</a>
      <h1 class="text-xl font-bold text-slate-800 mt-1">{{ $chat->display_name }}</h1>
      <p class="text-xs text-slate-500">
        @if ($chat->client)
          <a href="{{ route('admin.clients.details', $chat->client) }}" class="text-accent hover:underline">Lihat profil klien</a> ·
        @endif
        {{ $chat->email ?: 'email tidak diisi' }}
      </p>
    </div>

    <div class="flex items-center gap-2">
      @if ($chat->status === 'open')
        <form method="POST" action="{{ route('admin.chats.close', $chat) }}">
          @csrf
          <button type="submit" class="btn btn-outline"><i class="fa-solid fa-check text-xs"></i> Tutup Percakapan</button>
        </form>
      @endif
      <form method="POST" action="{{ route('admin.chats.delete', $chat) }}"
            data-confirm="Hapus percakapan ini beserta semua pesannya?"
            data-confirm-title="Hapus Percakapan" data-confirm-style="danger" data-confirm-label="Ya, Hapus">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger-soft"><i class="fa-regular fa-trash-can text-xs"></i></button>
      </form>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
      <div class="card overflow-hidden flex flex-col" style="height:min(600px,70vh)">
        <div id="adminChatBody" class="flex-1 overflow-y-auto px-5 py-4 space-y-3 bg-slate-50"></div>

        <form id="adminChatForm" class="p-3 border-t border-slate-100 bg-white">
          @csrf
          <div id="admFileChip" class="hidden items-center gap-2 mb-2 px-2 py-1.5 rounded-lg bg-slate-100 text-xs text-slate-600">
            <i class="fa-solid fa-paperclip"></i>
            <span id="admFileName" class="flex-1 truncate"></span>
            <button type="button" id="admFileRemove" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <div class="flex items-end gap-2">
            <label class="w-9 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500 cursor-pointer shrink-0">
              <i class="fa-solid fa-paperclip text-sm"></i>
              <input type="file" id="admFile" name="attachment" accept="image/*,application/pdf" class="hidden">
            </label>
            <textarea id="admInput" name="message" rows="1" placeholder="Tulis balasan… (Enter kirim, Shift+Enter baris baru)"
                      class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none focus:border-accent resize-none max-h-24"></textarea>
            <button type="submit" id="admSend" class="btn btn-primary shrink-0"><i class="fa-solid fa-paper-plane text-xs"></i></button>
          </div>
          <p id="admError" class="hidden text-[11px] text-rose-600 mt-1.5"></p>
        </form>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Informasi</h2>
        <dl class="space-y-2.5 text-sm">
          <div><dt class="text-slate-400 text-xs">Status</dt>
            <dd><span class="badge {{ $chat->status === 'open' ? 'badge-active' : 'badge-inactive' }}">{{ $chat->status === 'open' ? 'Aktif' : 'Ditutup' }}</span></dd></div>
          <div><dt class="text-slate-400 text-xs">Dimulai</dt><dd class="text-slate-700">{{ $chat->created_at->format('d M Y H:i') }}</dd></div>
          @if ($chat->page_url)
            <div><dt class="text-slate-400 text-xs">Dari halaman</dt><dd class="text-slate-700 text-xs break-all">{{ $chat->page_url }}</dd></div>
          @endif
          <div><dt class="text-slate-400 text-xs">Alamat IP</dt><dd class="text-slate-700 text-xs">{{ $chat->ip_address ?: '—' }}</dd></div>
        </dl>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const body   = document.getElementById('adminChatBody');
      const form   = document.getElementById('adminChatForm');
      const input  = document.getElementById('admInput');
      const fileIn = document.getElementById('admFile');
      const chip   = document.getElementById('admFileChip');
      const chipNm = document.getElementById('admFileName');
      const errBox = document.getElementById('admError');
      const sendBt = document.getElementById('admSend');

      const token    = document.querySelector('meta[name="csrf-token"]')?.content;
      const urlPoll  = @json(route('admin.chats.poll', $chat));
      const urlReply = @json(route('admin.chats.reply', $chat));

      let lastId = 0;

      function esc(t) { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; }

      function append(msg) {
        // Dari sudut pandang admin, pesan "admin" ada di kanan.
        const mine = msg.sender === 'admin';
        const bot  = msg.sender === 'bot';

        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (mine ? 'justify-end' : 'justify-start');

        let content = '';
        if (msg.message) {
          content += esc(msg.message)
            .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener" class="underline">$1</a>')
            .replace(/\n/g, '<br>');
        }
        if (msg.attachment_url) {
          content += msg.is_image
            ? '<a href="' + msg.attachment_url + '" target="_blank"><img src="' + msg.attachment_url + '" class="mt-1 rounded-lg max-h-48"></a>'
            : '<a href="' + msg.attachment_url + '" target="_blank" class="mt-1 flex items-center gap-1.5 underline text-xs"><i class="fa-solid fa-file-arrow-down"></i>' + esc(msg.attachment_name) + '</a>';
        }

        const cls = mine ? 'bg-accent text-white' : (bot ? 'bg-indigo-50 border border-indigo-100 text-slate-700' : 'bg-white border border-slate-200 text-slate-700');

        wrap.innerHTML = '<div class="max-w-[75%]">'
          + (!mine && msg.author ? '<p class="text-[10px] text-slate-400 mb-0.5">' + esc(msg.author) + '</p>' : '')
          + '<div class="rounded-2xl px-3 py-2 text-sm leading-relaxed ' + cls + '">' + content
          + '<span class="block text-[10px] mt-1 ' + (mine ? 'text-white/60' : 'text-slate-400') + '">' + esc(msg.time || '') + '</span></div></div>';

        body.appendChild(wrap);
        body.scrollTop = body.scrollHeight;
        if (msg.id) lastId = Math.max(lastId, msg.id);
      }

      async function poll() {
        try {
          const res = await fetch(urlPoll + '?after=' + lastId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const data = await res.json();
          (data.messages || []).forEach(append);
        } catch (e) { /* diam: percakapan tetap bisa dilanjutkan saat koneksi pulih */ }
      }

      fileIn.addEventListener('change', function () {
        if (!fileIn.files.length) return;
        chipNm.textContent = fileIn.files[0].name;
        chip.classList.remove('hidden'); chip.classList.add('flex');
      });
      document.getElementById('admFileRemove').addEventListener('click', function () {
        fileIn.value = ''; chip.classList.add('hidden'); chip.classList.remove('flex');
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
      });

      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errBox.classList.add('hidden');

        const fd = new FormData(form);
        if (!fd.get('message')?.trim() && !fileIn.files.length) return;

        sendBt.disabled = true;
        try {
          const res = await fetch(urlReply, {
            method: 'POST', body: fd,
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          });
          const data = await res.json();
          if (!res.ok || !data.ok) {
            errBox.textContent = data.message || 'Gagal mengirim.';
            errBox.classList.remove('hidden');
            return;
          }
          append(data.message);
          input.value = ''; fileIn.value = '';
          chip.classList.add('hidden'); chip.classList.remove('flex');
        } catch (err) {
          errBox.textContent = 'Tidak bisa terhubung.';
          errBox.classList.remove('hidden');
        } finally { sendBt.disabled = false; }
      });

      poll();
      setInterval(poll, 5000);
    })();
  </script>

@endsection
