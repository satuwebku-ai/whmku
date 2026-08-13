@extends('layouts.admin')

@section('title', 'Konsol Web')

@section('content')

  <div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Konsol Web</h1>
    <p class="text-sm text-slate-500 mt-1">
      Jalankan perintah pemeliharaan tanpa perlu Terminal/SSH — berguna kalau hosting-mu tidak menyediakan akses itu.
      Cuma perintah yang aman & sudah ditentukan yang bisa dijalankan dari sini.
    </p>
  </div>

  @if (session('output'))
    <div class="card p-4 mb-5 bg-slate-800 text-slate-100 font-mono text-xs whitespace-pre-wrap overflow-x-auto">{{ session('output') ?: '(tidak ada keluaran)' }}</div>
  @endif

  <div class="card p-5 max-w-xl">
    <form method="POST" action="{{ route('admin.console.run') }}" id="consoleForm" class="space-y-4">
      @csrf
      <div>
        <label class="form-label">Pilih Perintah</label>
        <select name="command" id="commandSelect" class="form-input" required>
          <option value="">— Pilih —</option>
          @foreach ($commands as $cmd => $desc)
            <option value="{{ $cmd }}" @selected(old('command') === $cmd)>{{ $cmd }}</option>
          @endforeach
        </select>
        <p id="commandDesc" class="text-[11px] text-slate-400 mt-1"></p>
      </div>

      <div id="argumentField" class="hidden">
        <label class="form-label">Alamat Email Tujuan</label>
        <input type="email" name="argument" value="{{ old('argument') }}" class="form-input" placeholder="kamu@email.com">
      </div>

      <div id="dryField" class="hidden">
        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="dry" value="1" checked class="rounded border-slate-300 text-accent focus:ring-accent/40">
          Mode aman (simulasi) — tidak benar-benar mengubah apa pun, cuma menunjukkan yang akan terjadi
        </label>
        <p class="text-[11px] text-amber-600 mt-1">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Hilangkan centang untuk benar-benar menjalankan (perpanjangan/suspend/pengingat sungguhan).
        </p>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-terminal text-xs"></i> Jalankan
      </button>
    </form>
  </div>

  <script>
    const descriptions = @json($commands);
    const dryRunCommands = @json($dryRunCommands);

    const select = document.getElementById('commandSelect');
    const descEl = document.getElementById('commandDesc');
    const argField = document.getElementById('argumentField');
    const dryField = document.getElementById('dryField');

    function sync() {
      const cmd = select.value;
      descEl.textContent = descriptions[cmd] || '';
      argField.classList.toggle('hidden', cmd !== 'lumora:test-mail');
      dryField.classList.toggle('hidden', !dryRunCommands.includes(cmd));
    }

    select.addEventListener('change', sync);
    sync();
  </script>

@endsection
