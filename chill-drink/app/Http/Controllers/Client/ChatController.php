<?php

namespace App\Http\Controllers\Client;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getOrCreateConversation(Request $request)
    {
        $this->ensureCustomer();

        $conversation = auth()->user()->conversations()
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$conversation) {
            $branchId = $request->branch_id 
                ?? auth()->user()->branch_id 
                ?? auth()->user()->orders()->latest()->value('branch_id')
                ?? \App\Models\Branch::first()->id ?? 1;

            $conversation = auth()->user()->conversations()->create([
                'subject' => 'Hỗ trợ khách hàng',
                'status' => 'open',
                'branch_id' => $branchId,
            ]);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'success' => true,
        ]);
    }

    public function messages(Request $request)
    {
        $this->ensureCustomer();

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $messages = $conversation->messages()
            ->with(['sender', 'displayAsSender'])
            ->get();

        return response()->json([
            'messages' => $messages->map(
                fn (Message $message) => MessageResource::toPublicArray($message)
            ),
            'success' => true,
        ]);
    }

    public function send(Request $request)
    {
        $this->ensureCustomer();

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        if (empty($request->content) && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập nội dung hoặc đính kèm file.',
            ], 422);
        }

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat-attachments', 'public');
            $attachmentName = $request->file('attachment')->getClientOriginalName();
        }

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'content' => $request->content,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        if ($conversation->status === 'closed') {
            $conversation->update([
                'status' => 'open',
                'last_message_at' => now(),
            ]);
        } else {
            $conversation->update(['last_message_at' => now()]);
        }

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {
            // Broadcasting optional when Reverb/queue is not configured
        }

        return response()->json([
            'message' => MessageResource::toPublicArray($message),
            'success' => true,
        ]);
    }
    protected function ensureCustomer(): void
    {
        abort_unless(auth()->user()?->isCustomer(), 403);
    }
}
