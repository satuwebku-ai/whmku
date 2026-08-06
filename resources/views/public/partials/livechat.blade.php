@php
  use App\Models\Setting;

  $provider   = Setting::get('livechat_provider', 'none');
  $propertyId = Setting::get('livechat_property_id');
  $waNumber   = Setting::get('livechat_whatsapp');
  $greeting   = Setting::get('livechat_greeting', 'Halo, saya ingin bertanya tentang layanan hosting.');

  $siteName    = Setting::get('site_name', config('app.name'));
  $themeColor  = Setting::get('theme_color', '#6366F1');
  $supportMail = Setting::get('support_email');
  $jamOperasi  = Setting::get('support_hours');
@endphp

@if ($provider === 'tawkto' && $propertyId)
  <script>
    var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
    (function(){
      var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
      s1.async = true;
      s1.src = 'https://embed.tawk.to/{{ $propertyId }}';
      s1.charset = 'UTF-8';
      s1.setAttribute('crossorigin', '*');
      s0.parentNode.insertBefore(s1, s0);
    })();
  </script>

@elseif ($provider === 'crisp' && $propertyId)
  <script>
    window.$crisp = [];
    window.CRISP_WEBSITE_ID = "{{ $propertyId }}";
    (function(){
      var d = document, s = d.createElement("script");
      s.src = "https://client.crisp.chat/l.js";
      s.async = 1;
      d.getElementsByTagName("head")[0].appendChild(s);
    })();
  </script>

@elseif (in_array($provider, ['widget', 'whatsapp'], true))
  {{-- Live chat bawaan: percakapan tersimpan di database sendiri, tidak
       memuat script pihak ketiga, dan pesannya bisa dibalas dari menu
       Live Chat di admin panel. --}}

  <div id="chatWidget" class="fixed right-5 bottom-5 z-[90] flex flex-col items-end gap-3">

    <div id="chatPanel" class="hidden w-[330px] sm:w-[360px] rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col" style="height:min(520px,75vh)">

      {{-- Kepala --}}
      <div class="px-4 py-3 text-white shrink-0" style="background:linear-gradient(135deg,{{ $themeColor }},#4c1d95)">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-headset"></i>
            </span>
            <div class="min-w-0">
              <p class="font-bold text-sm truncate">{{ $siteName }}</p>
              <p class="text-white/70 text-[11px]">{{ $jamOperasi ?: 'Kami siap membantu Anda' }}</p>
            </div>
          </div>
          <button type="button" id="chatClose" class="text-white/70 hover:text-white shrink-0" aria-label="Tutup">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>

      {{-- Daftar pesan --}}
      <div id="chatBody" class="flex-1 overflow-y-auto px-4 py-3 space-y-3 bg-slate-50">
        <div id="chatLoading" class="text-center text-xs text-slate-400 py-4">Memuat percakapan…</div>
      </div>

      {{-- Tautan cepat --}}
      <div class="px-3 py-2 border-t border-slate-100 flex items-center gap-2 text-[11px] shrink-0 bg-white">
        @if ($waNumber)
          <a href="https://wa.me/{{ preg_replace('/\D/', '', $waNumber) }}?text={{ urlencode($greeting) }}"
             target="_blank" rel="noopener noreferrer"
             class="flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100">
            <i class="fa-brands fa-whatsapp"></i> WhatsApp
          </a>
        @endif
        @auth('client')
          <a href="{{ route('client.tickets.create') }}" class="flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200">
            <i class="fa-solid fa-ticket"></i> Tiket
          </a>
        @endauth
        @if ($supportMail)
          <a href="mailto:{{ $supportMail }}" class="flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200">
            <i class="fa-regular fa-envelope"></i> Email
          </a>
        @endif
      </div>

      {{-- Kotak kirim --}}
      <form id="chatForm" class="p-3 border-t border-slate-100 shrink-0 bg-white">
        @guest('client')
          <div id="chatIdentity" class="grid grid-cols-2 gap-2 mb-2">
            <input type="text" name="name" placeholder="Nama Anda" class="px-3 py-2 rounded-lg border border-slate-200 text-xs outline-none focus:border-accent">
            <input type="email" name="email" placeholder="Email (opsional)" class="px-3 py-2 rounded-lg border border-slate-200 text-xs outline-none focus:border-accent">
          </div>
        @endguest

        <div id="chatFileChip" class="hidden items-center gap-2 mb-2 px-2 py-1.5 rounded-lg bg-slate-100 text-xs text-slate-600">
          <i class="fa-solid fa-paperclip"></i>
          <span id="chatFileName" class="flex-1 truncate"></span>
          <button type="button" id="chatFileRemove" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="flex items-end gap-2">
          <label class="w-9 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-500 cursor-pointer shrink-0"
                 title="Lampirkan bukti transfer atau tangkapan layar">
            <i class="fa-solid fa-paperclip text-sm"></i>
            <input type="file" id="chatFile" name="attachment" accept="image/*,application/pdf" class="hidden">
          </label>

          <textarea id="chatInput" name="message" rows="1" placeholder="Tulis pesan…"
                    class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none focus:border-accent resize-none max-h-24"></textarea>

          <button type="submit" id="chatSend"
                  class="w-9 h-9 rounded-lg text-white flex items-center justify-center shrink-0 disabled:opacity-50"
                  style="background:{{ $themeColor }}">
            <i class="fa-solid fa-paper-plane text-sm"></i>
          </button>
        </div>

        <p id="chatError" class="hidden text-[11px] text-rose-600 mt-1.5"></p>
      </form>
    </div>

    {{-- Tombol pembuka --}}
    <button type="button" id="chatToggle" aria-label="Buka chat"
            class="w-14 h-14 rounded-full text-white shadow-xl flex items-center justify-center transition-transform hover:scale-105 active:scale-95 relative"
            style="background:linear-gradient(135deg,{{ $themeColor }},#4c1d95)">
      <i id="chatIcon" class="fa-solid fa-comment-dots text-xl"></i>
      <span id="chatBadge" class="hidden absolute -top-1 -right-1 min-w-[20px] h-5 px-1 bg-rose-500 text-white text-[10px] font-bold rounded-full items-center justify-center ring-2 ring-white"></span>
    </button>
  </div>

  <script>
    (function () {
      const panel  = document.getElementById('chatPanel');
      const toggle = document.getElementById('chatToggle');
      const icon   = document.getElementById('chatIcon');
      const badge  = document.getElementById('chatBadge');
      const body   = document.getElementById('chatBody');
      const form   = document.getElementById('chatForm');
      const input  = document.getElementById('chatInput');
      const fileIn = document.getElementById('chatFile');
      const chip   = document.getElementById('chatFileChip');
      const chipNm = document.getElementById('chatFileName');
      const errBox = document.getElementById('chatError');
      const sendBt = document.getElementById('chatSend');

      const token = document.querySelector('meta[name="csrf-token"]')?.content;
      const urlFetch = @json(route('chat.fetch'));
      const urlSend  = @json(route('chat.send'));

      let lastId = 0;
      let timer = null;
      let isOpen = false;

      function esc(t) {
        const d = document.createElement('div');
        d.textContent = t ?? '';
        return d.innerHTML;
      }

      function bubble(msg) {
        const mine = msg.sender === 'user';
        const bot  = msg.sender === 'bot';

        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (mine ? 'justify-end' : 'justify-start');

        let inner = '';

        if (!mine && msg.author) {
          inner += '<p class="text-[10px] text-slate-400 mb-0.5">' + esc(msg.author) + '</p>';
        }

        let cls = mine
          ? 'bg-[' + @json($themeColor) + '] text-white'
          : (bot ? 'bg-indigo-50 text-slate-700 border border-indigo-100' : 'bg-white text-slate-700 border border-slate-200');

        let content = '';

        if (msg.message) {
          // Tautan dibuat bisa diklik, tapi teksnya di-escape dulu supaya
          // pesan tidak bisa menyuntikkan HTML.
          content += esc(msg.message).replace(
            /(https?:\/\/[^\s]+)/g,
            '<a href="$1" target="_blank" rel="noopener" class="underline">$1</a>'
          ).replace(/\n/g, '<br>');
        }

        if (msg.attachment_url) {
          if (msg.is_image) {
            content += '<a href="' + msg.attachment_url + '" target="_blank" rel="noopener">'
                     + '<img src="' + msg.attachment_url + '" class="mt-1 rounded-lg max-w-full max-h-40 object-cover"></a>';
          } else {
            content += '<a href="' + msg.attachment_url + '" target="_blank" rel="noopener" class="mt-1 flex items-center gap-1.5 underline text-xs">'
                     + '<i class="fa-solid fa-file-arrow-down"></i>' + esc(msg.attachment_name) + '</a>';
          }
        }

        inner += '<div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm leading-relaxed ' + cls + '" '
               + (mine ? 'style="background:' + @json($themeColor) + '"' : '') + '>'
               + content
               + '<span class="block text-[10px] mt-1 ' + (mine ? 'text-white/60' : 'text-slate-400') + '">' + esc(msg.time || '') + '</span>'
               + '</div>';

        wrap.innerHTML = '<div class="' + (mine ? 'text-right' : '') + ' max-w-full">' + inner + '</div>';
        return wrap;
      }

      function append(msg) {
        body.appendChild(bubble(msg));
        body.scrollTop = body.scrollHeight;
        if (msg.id) lastId = Math.max(lastId, msg.id);
      }

      async function load() {
        try {
          const res = await fetch(urlFetch + '?after=' + lastId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const data = await res.json();

          document.getElementById('chatLoading')?.remove();

          // Sambutan otomatis hanya ditampilkan saat belum ada percakapan.
          if (data.greeting && data.greeting.length && lastId === 0) {
            data.greeting.forEach(function (line, i) {
              setTimeout(() => append({ sender: 'bot', message: line, time: '' }), i * 500);
            });
          }

          (data.messages || []).forEach(append);

          // Badge hanya saat panel tertutup.
          const unread = (data.messages || []).filter(m => m.sender !== 'user').length;
          if (!isOpen && unread > 0) {
            badge.textContent = unread;
            badge.classList.remove('hidden');
            badge.classList.add('flex');
          }
        } catch (e) {
          document.getElementById('chatLoading')?.remove();
        }
      }

      function startPolling() {
        if (timer) return;
        timer = setInterval(load, 5000);
      }

      function setOpen(open) {
        isOpen = open;
        panel.classList.toggle('hidden', !open);
        icon.className = open ? 'fa-solid fa-xmark text-xl' : 'fa-solid fa-comment-dots text-xl';

        if (open) {
          badge.classList.add('hidden');
          load();
          startPolling();
          setTimeout(() => input.focus(), 100);
        }
      }

      toggle.addEventListener('click', () => setOpen(panel.classList.contains('hidden')));
      document.getElementById('chatClose').addEventListener('click', () => setOpen(false));

      // Lampiran
      fileIn.addEventListener('change', function () {
        if (!fileIn.files.length) return;
        chipNm.textContent = fileIn.files[0].name;
        chip.classList.remove('hidden');
        chip.classList.add('flex');
      });

      document.getElementById('chatFileRemove').addEventListener('click', function () {
        fileIn.value = '';
        chip.classList.add('hidden');
        chip.classList.remove('flex');
      });

      // Enter mengirim, Shift+Enter baris baru.
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          form.requestSubmit();
        }
      });

      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errBox.classList.add('hidden');

        const fd = new FormData(form);

        if (!fd.get('message')?.trim() && !fileIn.files.length) return;

        sendBt.disabled = true;

        try {
          const res = await fetch(urlSend, {
            method: 'POST',
            body: fd,
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          });

          const data = await res.json();

          if (!res.ok || !data.ok) {
            errBox.textContent = data.message || 'Gagal mengirim pesan.';
            errBox.classList.remove('hidden');
            return;
          }

          append(data.message);
          input.value = '';
          fileIn.value = '';
          chip.classList.add('hidden');
          chip.classList.remove('flex');
          document.getElementById('chatIdentity')?.classList.add('hidden');
          startPolling();
        } catch (err) {
          errBox.textContent = 'Tidak bisa terhubung. Periksa koneksi Anda.';
          errBox.classList.remove('hidden');
        } finally {
          sendBt.disabled = false;
        }
      });

      // Ambil sekali di awal supaya badge muncul walau panel belum dibuka.
      load();
      startPolling();
    })();
  </script>
@endif
