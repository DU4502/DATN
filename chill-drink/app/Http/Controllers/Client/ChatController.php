<?php

namespace App\Http\Controllers\Client;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function nearestBranches(Request $request)
    {
        $this->ensureCustomer();

        $lat = $request->input('lat') !== null && $request->input('lat') !== '' ? (float) $request->input('lat') : null;
        $lng = $request->input('lng') !== null && $request->input('lng') !== '' ? (float) $request->input('lng') : null;
        $address = trim((string) $request->input('address'));

        if ($address !== '' && ($lat === null || $lng === null)) {
            $coords = $this->geocodeAddress($address);
            if ($coords) {
                $lat = $coords['lat'];
                $lng = $coords['lng'];
            }
        }

        $user = auth()->user();
        if ($lat === null || $lng === null) {
            $lat = (float) ($user?->latitude ?? session('user_lat') ?? 19.806692);
            $lng = (float) ($user?->longitude ?? session('user_lng') ?? 105.785117);
        }

        $branches = \App\Models\Branch::where('status', true)
            ->get()
            ->map(function (\App\Models\Branch $branch) use ($lat, $lng) {
                $distanceKm = $branch->distanceTo($lat, $lng);
                if (is_infinite($distanceKm) || is_nan($distanceKm)) {
                    $distanceKm = 1.0 + ($branch->id * 0.5);
                }
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
            'lat' => $lat,
            'lng' => $lng,
        ]);
    }

    private function geocodeAddress(string $address): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'ChillDrink/1.0 (contact@chilldrink.com)',
            ])->timeout(4)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful() && !empty($response->json())) {
                $data = $response->json()[0];
                return [
                    'lat' => (float) $data['lat'],
                    'lng' => (float) $data['lon'],
                ];
            }
        } catch (\Throwable $e) {
            // Geocoding fallback
        }

        return null;
    }

    public function getOrCreateConversation(Request $request)
    {
        $this->ensureCustomer();

        $user = auth()->user();

        $conversation = $user->conversations()
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$conversation) {
            $conversation = $user->conversations()->create([
                'subject' => 'Hỗ trợ khách hàng',
                'status'  => 'open',
                'branch_id' => null,
            ]);
        }

        $conversation->load('branch');

        return response()->json([
            'conversation_id' => $conversation->id,
            'branch_id'       => $conversation->branch_id,
            'branch_name'     => $conversation->branch?->name ?? '',
            'success'         => true,
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
        ]);

        session(['nearest_branch_id' => $branch->id]);

        $systemContent = "🤖 Hệ thống\nXin chào!\nBạn đã được kết nối với " . $branch->name . ".\n\nNhân viên sẽ phản hồi bạn trong giây lát.\nBạn có thể gửi trước câu hỏi hoặc yêu cầu của mình.";

        $staffUser = \App\Models\User::whereIn('role_id', [2, 3, 4])
            ->where('branch_id', $branch->id)
            ->first()
            ?? \App\Models\User::whereIn('role_id', [2, 3, 4])->first()
            ?? auth()->user();

        $message = $conversation->messages()->create([
            'sender_id' => $staffUser->id,
            'content' => $systemContent,
        ]);

        DB::table('conversations')
            ->where('id', $conversation->id)
            ->update(['last_message_at' => now()]);

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {
            // Broadcasting optional
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

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        if ($request->boolean('mark_as_read', false)) {
            $conversation->messages()
                ->where('sender_id', '!=', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        $messages = $conversation->messages()
            ->with(['sender', 'displayAsSender'])
            ->oldest()
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages->map(fn($m) => MessageResource::toPublicArray($m))->values(),
        ]);
    }

    public function send(Request $request)
    {
        return $this->sendMessage($request);
    }

    public function sendMessage(Request $request)
    {
        $this->ensureCustomer();

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:10240',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$request->filled('content') && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập nội dung tin nhắn hoặc đính kèm file.',
            ], 422);
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('chat-attachments', 'public');
        }

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'content' => $request->input('content'),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        DB::table('conversations')
            ->where('id', $conversation->id)
            ->update(['last_message_at' => now()]);

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {
            // Broadcasting optional
        }

        return response()->json([
            'success' => true,
            'message' => MessageResource::toPublicArray($message),
        ]);
    }

    private function ensureCustomer()
    {
        // Allowed for all authenticated users
    }
}
