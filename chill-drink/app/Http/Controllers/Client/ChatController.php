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

        $user = auth()->user();

        // --- Xác định branch_id theo ưu tiên ---
        // 1. Đơn hàng gần nhất còn đang xử lý của user
        $activeOrder = $user->orders()
            ->whereNotNull('branch_id')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->first();

        $targetBranchId = $activeOrder?->branch_id
            ?? session('nearest_branch_id')
            ?? null;

        // 2. Fallback: chi nhánh đầu tiên đang hoạt động
        if (!$targetBranchId) {
            $targetBranchId = \App\Models\Branch::where('status', true)->value('id');
        }

        // 3. Kiểm tra chi nhánh đã chọn có đang mở không
        //    Nếu đóng → tìm chi nhánh gần nhất đang mở dựa vào vị trí GPS (session)
        if ($targetBranchId) {
            $targetBranch = \App\Models\Branch::find($targetBranchId);

            if (!$targetBranch || !$targetBranch->status) {
                $nearestOpen = $this->findNearestOpenBranch();
                if ($nearestOpen) {
                    $targetBranchId = $nearestOpen->id;
                } else {
                    // Tất cả chi nhánh đều đóng — lấy bất kỳ chi nhánh nào còn tồn tại
                    $targetBranchId = \App\Models\Branch::value('id');
                }
            }
        }

        // --- Tìm conversation open hiện có ---
        $conversation = $user->conversations()
            ->where('status', 'open')
            ->latest()
            ->first();

        $isNew = false;

        if (!$conversation) {
            $conversation = $user->conversations()->create([
                'subject' => 'Hỗ trợ khách hàng',
                'status'  => 'open',
                'branch_id' => $targetBranchId,
            ]);
            $isNew = true;
        } elseif ($targetBranchId && !$conversation->branch_id) {
            // Conversation cũ chưa có branch → assign ngay
            $conversation->update(['branch_id' => $targetBranchId]);
            $isNew = true;
        }

        // --- Gửi system message chào khi branch vừa được assign ---
        if ($isNew && $targetBranchId) {
            $branch = \App\Models\Branch::find($targetBranchId);

            if ($branch) {
                $staffUser = \App\Models\User::whereIn('role_id', [2, 3, 4])
                    ->where('branch_id', $branch->id)
                    ->first()
                    ?? \App\Models\User::whereIn('role_id', [2, 3, 4])->first()
                    ?? $user;

                $systemContent = "Xin chào! Bạn đang được kết nối với Chi nhánh {$branch->name}.\nNhân viên sẽ hỗ trợ bạn trong giây lát.";

                if ($activeOrder && $activeOrder->branch_id === $targetBranchId) {
                    $systemContent = "Xin chào! Bạn có đơn hàng #{$activeOrder->id} tại Chi nhánh {$branch->name}.\nNhân viên sẽ hỗ trợ bạn trong giây lát.";
                } elseif ($activeOrder && $activeOrder->branch_id !== $targetBranchId) {
                    // Chi nhánh của đơn hàng đã đóng, đang chuyển sang chi nhánh khác
                    $systemContent = "Xin chào! Chi nhánh xử lý đơn hàng của bạn hiện đóng cửa.\nBạn đang được kết nối với Chi nhánh {$branch->name} để được hỗ trợ.";
                }

                $message = $conversation->messages()->create([
                    'sender_id' => $staffUser->id,
                    'content'   => $systemContent,
                ]);

                DB::table('conversations')
                    ->where('id', $conversation->id)
                    ->update(['last_message_at' => now()]);

                $message->load(['sender', 'displayAsSender']);

        $systemMsgId = $message->id;
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {}
        unset($systemMsgId);
            }
        }

        $conversation->load('branch');

        return response()->json([
            'conversation_id' => $conversation->id,
            'branch_id'       => $conversation->branch_id,
            'branch_name'     => $conversation->branch?->name ?? '',
            'success'         => true,
        ]);
    }

    /**
     * Tìm chi nhánh mở gần nhất dựa theo tọa độ user (DB → session → fallback).
     */
    private function findNearestOpenBranch(): ?\App\Models\Branch
    {
        $branches = \App\Models\Branch::where('status', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($branches->isEmpty()) {
            return null;
        }

        // Ưu tiên tọa độ lưu trong DB user, rồi mới đến session
        $user = auth()->user();
        $lat  = $user?->latitude ?? session('user_lat');
        $lng  = $user?->longitude ?? session('user_lng');

        if ($lat && $lng) {
            return $branches
                ->sortBy(fn ($b) => $b->distanceTo((float) $lat, (float) $lng))
                ->first();
        }

        // Không có tọa độ → trả về chi nhánh đầu tiên đang mở
        return $branches->first();
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

        // Cập nhật last_message_at nhanh nhất bằng raw SQL ngay sau khi tạo message
        DB::table('conversations')
            ->where('id', $conversation->id)
            ->update(['last_message_at' => now()]);

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {}

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
            // Mở lại conversation bằng Eloquent (cần update status)
            $conversation->update(['status' => 'open']);
        }

        // Cập nhật last_message_at nhanh nhất bằng raw SQL ngay sau khi tạo message
        DB::table('conversations')
            ->where('id', $conversation->id)
            ->update(['last_message_at' => now()]);

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {}

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
