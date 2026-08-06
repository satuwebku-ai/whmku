<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = ChatConversation::query()
            ->with('client')
            ->when($request->status === 'closed', fn ($q) => $q->where('status', 'closed'))
            ->when($request->status !== 'closed', fn ($q) => $q->where('status', 'open'))
            ->orderByDesc('unread_for_admin')
            ->orderByDesc('last_message_at')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'open' => ChatConversation::open()->count(),
            'unread' => ChatConversation::where('unread_for_admin', '>', 0)->count(),
            'closed' => ChatConversation::where('status', 'closed')->count(),
        ];

        return view('admin.chats.index', compact('conversations', 'counts'));
    }

    public function show(ChatConversation $chat): View
    {
        $chat->load(['client', 'messages.admin']);

        // Dibuka admin = pesan pengunjung sudah dibaca.
        $chat->update(['unread_for_admin' => 0]);

        return view('admin.chats.show', ['chat' => $chat]);
    }

    /**
     * Ambil pesan baru (dipakai polling di halaman detail).
     */
    public function poll(Request $request, ChatConversation $chat): JsonResponse
    {
        $afterId = (int) $request->input('after', 0);

        $messages = $chat->messages()
            ->with('admin')
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get();

        if ($messages->where('sender', 'user')->isNotEmpty()) {
            $chat->update(['unread_for_admin' => 0]);
        }

        return response()->json([
            'messages' => $messages->map->toWidgetArray()->values(),
            'status' => $chat->status,
        ]);
    }

    public function reply(Request $request, ChatConversation $chat): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        if (blank($data['message'] ?? null) && ! $request->hasFile('attachment')) {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Pesan kosong.'], 422)
                : back()->with('error', 'Tulis pesan atau lampirkan berkas.');
        }

        $message = new ChatMessage([
            'sender' => 'admin',
            'admin_id' => Auth::guard('admin')->id(),
            'message' => $data['message'] ?? null,
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $message->attachment_path = $file->store('chat', 'public');
            $message->attachment_name = $file->getClientOriginalName();
            $message->attachment_mime = $file->getMimeType();
        }

        $chat->messages()->save($message);
        $chat->increment('unread_for_user');
        $chat->update(['last_message_at' => now(), 'status' => 'open']);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message->load('admin')->toWidgetArray()]);
        }

        return back();
    }

    public function close(ChatConversation $chat): RedirectResponse
    {
        $chat->update(['status' => 'closed']);

        return redirect()->route('admin.chats')->with('success', 'Percakapan ditutup.');
    }

    public function destroy(ChatConversation $chat): RedirectResponse
    {
        $chat->delete();

        return redirect()->route('admin.chats')->with('success', 'Percakapan dihapus.');
    }
}
