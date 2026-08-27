<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Balasan otomatis di live chat lewat Claude (Anthropic API).
 *
 * Sengaja SINKRON (dipanggil langsung dalam permintaan HTTP saat
 * pengunjung kirim pesan, bukan lewat antrean/queue worker) --
 * konsisten dengan keputusan arsitektur chat ini sendiri (polling,
 * bukan WebSocket) karena shared hosting umumnya tidak mengizinkan
 * proses latar belakang yang jalan terus-menerus.
 */
class AiChatService
{
    public function enabled(): bool
    {
        return Setting::get('ai_chat_enabled', '0') === '1' && filled(Setting::get('ai_chat_api_key'));
    }

    /**
     * Hasilkan balasan bot untuk percakapan ini, lalu SIMPAN sebagai
     * ChatMessage (sender=bot) -- supaya pemanggilnya (ChatController)
     * tidak perlu tahu detail format respons Anthropic, cukup panggil
     * lalu percakapan sudah terupdate.
     *
     * Return null kalau bot tidak seharusnya membalas (nonaktif, sudah
     * ditangani admin, atau API gagal) -- percakapan tetap berjalan
     * normal tanpa balasan bot, TIDAK melempar error ke pengunjung.
     */
    public function reply(ChatConversation $conversation): ?\App\Models\ChatMessage
    {
        if (! $this->enabled()) {
            return null;
        }

        // Begitu admin manapun pernah membalas, bot berhenti otomatis --
        // staf manusia sudah mengambil alih, bot tidak boleh menimpa.
        if ($conversation->messages()->where('sender', 'admin')->exists()) {
            return null;
        }

        $history = $conversation->messages()
            ->whereIn('sender', ['user', 'bot'])
            ->orderBy('id')
            ->limit(20) // cukup untuk konteks, tanpa membengkakkan biaya token per pesan
            ->get();

        if ($history->isEmpty()) {
            return null;
        }

        $messages = $history->map(fn ($m) => [
            'role' => $m->sender === 'bot' ? 'assistant' : 'user',
            'content' => $m->message ?: '(mengirim lampiran/berkas)',
        ])->values()->all();

        $result = $this->callAnthropic($messages);

        if (! $result) {
            return null;
        }

        $message = $conversation->messages()->create([
            'sender' => 'bot',
            'message' => $result,
        ]);

        $conversation->increment('unread_for_user');
        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    private function callAnthropic(array $messages): ?string
    {
        $apiKey = Setting::get('ai_chat_api_key');
        $model = Setting::get('ai_chat_model', 'claude-sonnet-4-6');
        $systemPrompt = $this->systemPrompt();

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
                ->timeout(25)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 500,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::warning('AI chat bot: Anthropic API menolak', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            $text = collect($response->json('content', []))
                ->where('type', 'text')
                ->pluck('text')
                ->implode("\n");

            return trim($text) ?: null;
        } catch (Throwable $e) {
            Log::warning('AI chat bot: gagal menghubungi Anthropic — ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Konteks bisnis untuk bot -- diatur admin (Pengaturan → Live
     * Chat), BUKAN ditulis tetap di kode, karena tiap bisnis hosting
     * beda produk/kebijakan/harga. Tanpa ini diisi, bot akan menjawab
     * generik tanpa tahu apa-apa soal layanan yang sebenarnya dijual.
     */
    private function systemPrompt(): string
    {
        $konteks = Setting::get('ai_chat_context', '');
        $namaSitus = Setting::get('site_name', 'layanan hosting ini');

        $dasar = "Anda adalah asisten chat otomatis untuk {$namaSitus}, sebuah penyedia layanan hosting & VPS.\n\n"
            . "Aturan:\n"
            . "- Jawab singkat, ramah, dan dalam Bahasa Indonesia.\n"
            . "- Kalau tidak yakin atau pertanyaannya di luar kemampuan Anda (butuh akses akun, pembatalan, refund, atau masalah teknis mendalam), katakan dengan jujur bahwa staf manusia akan segera membantu -- jangan mengarang jawaban.\n"
            . "- Jangan pernah meminta atau menyebutkan password, nomor kartu, OTP, atau data sensitif lain.\n"
            . "- Jangan menjanjikan harga/diskon yang tidak disebutkan dalam konteks di bawah.";

        if (filled($konteks)) {
            $dasar .= "\n\nInformasi tentang bisnis ini (dari admin):\n" . $konteks;
        }

        return $dasar;
    }
}
