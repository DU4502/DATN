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

    public function nearestBranches(Request $request)
    {
        $this->ensureCustomer();

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');

        $branches = \App\Models\Branch::availableForLocation()
            ->get()
            ->map(function (\App\Models\Branch $branch) use ($lat, $lng) {
                $distanceKm = $branch->distanceTo($lat, $lng);
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address,
                    'phone' => $branch->phone,
                    'distance' => round($distanceKm, 2),
                    'distance_text' => round($distanceKm, 1) . ' km',
                ];
            })
            ->sortBy('distance')
            ->values()
            ->take(3);

        return response()->json([
            'success' => true,
            'branches' => $branches,
        ]);
    }

    public function getOrCreateConversation(Request $request)
    {
        $this->ensureCustomer();

        $conversation = auth()->user()->conversations()
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$conversation) {
            $conversation = auth()->user()->conversations()->create([
                'subject' => 'Hỗ trợ khách hàng',
                'status' => 'open',
                'branch_id' => null,
            ]);
        }

        $conversation->load('branch');

        return response()->json([
            'conversation_id' => $conversation->id,
            'branch_id' => $conversation->branch_id,
            'branch_name' => $conversation->branch?->name,
            'success' => true,
        ]);
    }

    public function selectBranch(Request $request)
    {
        $this->ensureCustomer();

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $branch = \App\Models\Branch::findOrFail($request->branch_id);

        $conversation->update([
            'branch_id' => $branch->id,
            'last_message_at' => now(),
        ]);

        session(['nearest_branch_id' => $branch->id]);

        $systemContent = "Bạn đã được kết nối với Chi nhánh " . $branch->name . ".\nNhân viên sẽ hỗ trợ bạn trong giây lát.";

        // Find staff/admin assigned to this branch, or any staff user
        $staffUser = \App\Models\User::whereIn('role_id', [2, 3, 4])
            ->where('branch_id', $branch->id)
            ->first()
            ?? \App\Models\User::whereIn('role_id', [2, 3, 4])->first()
            ?? auth()->user();

        $message = $conversation->messages()->create([
            'sender_id' => $staffUser->id,
            'content' => $systemContent,
        ]);

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {
            // Broadcasting optional when Reverb/queue is not configured
        }

        return response()->json([
            'success' => true,
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'message' => MessageResource::toPublicArray($message),
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

        if ($request->input('mark_as_read')) {
            $conversation->messages()
                ->where('is_read', false)
                ->where('sender_id', '!=', auth()->id())
                ->update(['is_read' => true]);
        }

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

        if (empty($request->input('content')) && !$request->hasFile('attachment')) {
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
            'content' => $request->input('content'),
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
