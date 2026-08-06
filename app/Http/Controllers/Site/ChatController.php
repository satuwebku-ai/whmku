<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Endpoint untuk widget chat di halaman publik dan area klien.
 *
 * Memakai polling, bukan WebSocket: hosting cPanel umumnya tidak mengizinkan
 * proses yang berjalan terus-menerus, sehingga WebSocket tidak bisa dipakai.
 * Polling tiap beberapa detik jauh lebih sederhana dan cukup untuk volume
 * percakapan sebuah penyedia hosting kecil-menengah.
 */
class ChatController extends Controller
{
    /**
     * Ambil percakapan berjalan beserta pesannya.
     */
    public function fetch(Request $request): JsonResponse
    {
        $conversation = $this->findConversation($request);

        if (! $conversation) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
                'greeting' => $this->greetingLines(),
            ]);
        }

        $afterId = (int) $request->input('after', 0);

        $messages = $conversation->messages()
            ->with('admin')
            ->when($afterId, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(100)
            ->get();

        // Pesan dari admin dianggap terbaca begitu widget mengambilnya.
        if ($conversation->unread_for_user > 0 && $afterId === 0) {
            $conversation->update(['unread_for_user' => 0]);
        } elseif ($messages->where('sender', 'admin')->isNotEmpty()) {
            $conversation->decrement('unread_for_user', min(
                $messages->where('sender', 'admin')->count(),
                $conversation->unread_for_user
            ));
        }

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
            ],
            'messages' => $messages->map->toWidgetArray()->values(),
            'greeting' => $conversation->messages()->exists() ? [] : $this->greetingLines(),
        ]);
    }

    /**
     * Kirim pesan. Percakapan dibuat otomatis pada pesan pertama.
     */
    public function send(Request $request): JsonResponse
    {
        // Batasi supaya widget tidak bisa dipakai membanjiri database.
        $key = 'chat-send|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json([
                'ok' => false,
                'message' => 'Terlalu banyak pesan. Tunggu sebentar lalu coba lagi.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            'name'    => ['nullable', 'string', 'max:100'],
            'email'   => ['nullable', 'email', 'max:255'],
            // Bukti transfer dan tangkapan layar kendala teknis.
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'attachment.max' => 'Ukuran berkas maksimal 5 MB.',
            'attachment.mimes' => 'Berkas harus berupa gambar (JPG/PNG/WEBP) atau PDF.',
        ]);

        if (blank($data['message'] ?? null) && ! $request->hasFile('attachment')) {
            return response()->json(['ok' => false, 'message' => 'Tulis pesan atau lampirkan berkas.'], 422);
        }

        $conversation = $this->findConversation($request) ?? $this->createConversation($request, $data);

        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'open']);
        }

        $message = new ChatMessage([
            'sender' => 'user',
            'message' => $data['message'] ?? null,
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $message->attachment_path = $file->store('chat', 'public');
            $message->attachment_name = $file->getClientOriginalName();
            $message->attachment_mime = $file->getMimeType();
        }

        $conversation->messages()->save($message);

        $conversation->increment('unread_for_admin');
        $conversation->update(['last_message_at' => now()]);

        // Catat sekali per percakapan saja, supaya daftar aktivitas tidak
        // dibanjiri satu baris per pesan.
        if ($conversation->messages()->where('sender', 'user')->count() === 1) {
            ActivityLog::record(
                'ticket',
                'Chat baru dari ' . $conversation->display_name,
                Str::limit($data['message'] ?? 'Mengirim lampiran', 80),
                route('admin.chats.show', $conversation),
                'warning',
                $conversation->client_id,
            );
        }

        return response()->json([
            'ok' => true,
            'message' => $message->load('admin')->toWidgetArray(),
        ]);
    }

    /**
     * Cari percakapan milik pengunjung/klien saat ini.
     */
    private function findConversation(Request $request): ?ChatConversation
    {
        if ($client = Auth::guard('client')->user()) {
            return ChatConversation::where('client_id', $client->id)->latest('id')->first();
        }

        $token = $request->session()->get('chat_token');

        return $token ? ChatConversation::where('guest_token', $token)->first() : null;
    }

    private function createConversation(Request $request, array $data): ChatConversation
    {
        $client = Auth::guard('client')->user();

        $token = $client ? null : Str::random(48);

        if ($token) {
            $request->session()->put('chat_token', $token);
        }

        return ChatConversation::create([
            'guest_token' => $token,
            'client_id' => $client?->id,
            'name' => $client?->name ?? ($data['name'] ?? null),
            'email' => $client?->email ?? ($data['email'] ?? null),
            'status' => 'open',
            'last_message_at' => now(),
            'page_url' => $request->headers->get('referer'),
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * Pesan sambutan otomatis, diatur admin di Pengaturan → Live Chat.
     */
    private function greetingLines(): array
    {
        $lines = [];

        $sambutan = Setting::get('chat_greeting_1', 'Selamat datang! Ada yang bisa kami bantu?');
        $promo = Setting::get('chat_greeting_2');

        if (filled($sambutan)) {
            $lines[] = $sambutan;
        }

        if (filled($promo)) {
            $lines[] = $promo;
        }

        return $lines;
    }
}
