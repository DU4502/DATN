# SYSTEM ISSUES REPORT
*Được tạo bởi: Antigravity*

> **Cập nhật kiểm chứng:** 31/07/2026. Báo cáo này đã được đối chiếu lại với mã nguồn hiện tại, kiểm tra PHP syntax, Vite build, route list và PHPUnit. Các mục bên dưới là vấn đề còn tồn tại hoặc rủi ro cần xử lý; những mục đã lỗi thời được ghi rõ trong bảng này.
>
> **Kết quả kiểm tra:** PHP syntax 281/281 file đạt; Vite build đạt; route list chạy thành công với 182 route; PHPUnit 125 pass / 0 fail, 519 assertions. Database runtime đã xác minh qua `php artisan migrate:status`, các migration đều ở trạng thái `Ran`.

## Bảng đối chiếu trạng thái các mục có thay đổi

| Mục | Trạng thái sau khi đối chiếu | Ghi chú |
|---|---|---|
| 1 | Đã sửa | `ToppingController::destroy()` kiểm tra `order_item_toppings` trước khi xóa; topping đã có trong lịch sử đơn hàng sẽ được giữ lại và yêu cầu chuyển sang ngưng bán. |
| 2 | Đã sửa | `BranchSlideController::restore()` kiểm tra `sort_order` đang được dùng bởi slide active cùng chi nhánh trước khi khôi phục. |
| 3 | Đã sửa | `ChatController::selectBranch()` và `ChatHelper` không dùng tài khoản khách hàng làm người gửi tin hệ thống; nếu không có staff phù hợp thì bỏ qua system message. |
| 4 | Đã sửa | `AdminChatController::reply()` dùng transaction và `lockForUpdate()`, đồng thời kiểm tra lại quyền sau khi lấy lock trước khi gán `cskh_id` và tạo message. |
| 5 | Đã sửa | `DashboardController::orderCountFor()` chỉ đếm đơn `completed`, đồng bộ với cách tính doanh thu. |
| 6 | Đã sửa | `GroupOrderController` lọc trực tiếp theo `group_orders.branch_id`; Admin thiếu chi nhánh nhận danh sách rỗng và không xem được chi tiết ngoài phạm vi. |
| 15 | Không còn đúng theo mô tả cũ | `ChatController` hiện không có `geocodeAddress()`; không có bằng chứng về request Nominatim tại vị trí cũ. |
| 23 | Đã sửa | `ConversationClosed` được broadcast, lỗi broadcast được ghi log và thao tác đóng chat có audit log. |
| 29 | Đã sửa | Admin có thể hủy đơn `completed`; luồng hủy hoàn tồn kho, giảm voucher và thu hồi điểm đã cộng. |
| 33 | Đã sửa | Tin nhắn mới vào conversation đã đóng sẽ mở lại conversation trước khi ghi message. |
| 46 | Đã xác minh | Migration guest đổi `orders.user_id` thành nullable và `nullOnDelete`; runtime đã xác nhận migration chạy thành công. |
| 51 | Đã sửa | Đăng ký chuyển tới trang xác nhận email; checkout và loyalty points yêu cầu middleware `verified`. |
| 55 | Đã sửa | Group order hiện có transaction và khóa bản ghi trong các luồng cập nhật chính. |
| 56 | Đã sửa | Model `Order` tự động gán `delivered_at` khi trạng thái chuyển sang `delivered`. |
| 58 | Đã sửa | Checkout hiện khóa product bằng `lockForUpdate()` trong transaction. |

Các mục không nằm trong bảng trên vẫn được xem là còn tồn tại cho đến khi có kiểm tra hoặc test xác nhận ngược lại.

## 1. Foreign Key Constraint Violation khi Xóa Topping *(đã sửa)*
- **Mức độ ảnh hưởng trước khi sửa:** Critical
- **Vị trí:** `app/Http/Controllers/Admin/ToppingController.php` (hàm `destroy`) và `database/migrations/2026_05_17_121757_create_order_item_toppings_table.php`.
- **Nguyên nhân:** `order_item_toppings.topping_id` cố ý giữ khóa ngoại không cascade để bảo toàn lịch sử đơn hàng, nhưng controller trước đây gọi `$topping->delete()` mà không kiểm tra dữ liệu lịch sử.
- **Cách xử lý hiện tại:** `destroy()` kiểm tra sự tồn tại của bản ghi trong `order_item_toppings` trước khi xóa. Nếu topping đã được dùng, request quay về danh sách với thông báo lỗi và không xóa dữ liệu. Nếu chưa được dùng, controller mới detach khỏi sản phẩm rồi xóa topping.
- **Tương thích schema:** Model `Topping` tắt quản lý timestamps tự động vì bảng `toppings` chỉ có `created_at`, không có `updated_at`; nhờ đó các thao tác CRUD không tự phát sinh cột không tồn tại.
- **Kiểm thử:** `Tests\Feature\Admin\ToppingManagementTest::test_admin_cannot_delete_topping_used_in_order_history` xác nhận topping đã dùng không bị xóa và không phát sinh lỗi foreign key.
  ```php
  public function destroy(Topping $topping)
  {
      $hasOrderHistory = DB::table('order_item_toppings')
          ->where('topping_id', $topping->id)
          ->exists();

      if ($hasOrderHistory) {
          return redirect()->route('admin.toppings.index')
              ->with('error', 'Không thể xóa Topping đã xuất hiện trong lịch sử đơn hàng. Vui lòng chuyển sang ngưng bán.');
      }

      $topping->products()->detach();
      $topping->delete();

      return redirect()->route('admin.toppings.index')->with('success', 'Xóa Topping thành công!');
  }
  ```

## 2. Unchecked Restore Logic Error gây trùng `sort_order` Slide *(đã sửa)*
- **Mức độ ảnh hưởng trước khi sửa:** Critical
- **Vị trí:** `app/Http/Controllers/Admin/BranchSlideController.php` (hàm `restore`).
- **Nguyên nhân:** Luồng tạo mới/cập nhật đã kiểm tra `sort_order`, nhưng restore trước đây gọi `$slide->restore()` trực tiếp nên có thể khôi phục một slide vào vị trí đã được slide active khác sử dụng.
- **Cách xử lý hiện tại:** Trước khi restore, controller tìm slide chưa bị soft-delete cùng `branch_id` và `sort_order` (không tính chính slide đang khôi phục). Nếu có xung đột, request bị chặn và trả thông báo lỗi; nếu không có, slide được restore bình thường.
- **Kiểm thử:** `Tests\Feature\Admin\BranchSlideManagementTest::test_admin_cannot_restore_slide_with_an_active_duplicate_sort_order` xác nhận slide xung đột vẫn ở trạng thái soft-deleted.
  ```php
  public function restore($id)
  {
      $user = auth()->user();
      $slide = BranchSlide::withTrashed()->findOrFail($id);

      if (!$user->isSuperAdmin() && $slide->branch_id !== $user->branch_id) {
          abort(403, 'Bạn không có quyền khôi phục slide của chi nhánh khác.');
      }

      $sortOrderInUse = BranchSlide::query()
          ->where('branch_id', $slide->branch_id)
          ->where('sort_order', $slide->sort_order)
          ->where('id', '!=', $slide->id)
          ->exists();

      if ($sortOrderInUse) {
          return redirect()->back()->with('error', "Không thể khôi phục! Thứ tự hiển thị {$slide->sort_order} đang được sử dụng bởi slide khác.");
      }

      $slide->restore();

      return redirect()->back()->with('success', 'Đã khôi phục slide thành công!');
  }
  ```

## 3. Improper Fallback Assignment trong Khởi tạo Chatbot *(đã sửa)*
> **Cập nhật 31/07/2026:** Cả `ChatController::selectBranch()` và `ChatHelper::ensureChatWithOrderBranch()` đều không còn fallback về tài khoản khách hàng.
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Client/ChatController.php` (hàm `selectBranch`) và `app/Support/ChatHelper.php`.
- **Cơ chế trước khi sửa:** Khi không tìm thấy staff, luồng cũ có thể dùng `auth()->user()` làm người gửi tin nhắn Hệ Thống chào mừng, khiến khách hàng trở thành người gửi tin cho chính mình.
  ```php
  $staffUser = \App\Models\User::whereIn('role_id', [2, 3, 4])->where('branch_id', $branch->id)->first()
      ?? \App\Models\User::whereIn('role_id', [2, 3, 4])->first()
      ?? \App\Models\User::whereIn('role_id', [2, 3, 4])->where('is_active', true)->first();
  ```
  Nếu không có staff đang hoạt động, hệ thống không tạo system message.
- **Cách xử lý hiện tại:** Chỉ chọn staff đang hoạt động (`role_id` 2/3/4), ưu tiên staff cùng chi nhánh. Nếu không có staff, conversation vẫn được tạo nhưng bỏ qua system message; tuyệt đối không dùng tài khoản khách hàng làm `sender_id`.
- **Kiểm thử:** `Tests\Feature\ChatHelperTest::test_order_chat_does_not_use_customer_as_system_message_sender_when_no_staff_exists` xác nhận không có message với `sender_id` là khách hàng.
## 4. Race Condition / Unlocked Assignment trong CSKH Reply Chat *(đã sửa)*
> **Cập nhật 31/07/2026:** Luồng reply đã được bọc trong transaction; conversation được khóa trước khi kiểm tra assignment và ghi message.
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Admin/AdminChatController.php` (hàm `reply`, dòng 120–122)
- **Cơ chế hoạt động:** Khi nhân viên CSKH gửi tin nhắn trả lời một hội thoại chưa có người phụ trách (`!$conversation->cskh_id`), controller tự động cập nhật hội thoại đó cho CSKH hiện tại:
  `if (!$conversation->cskh_id && !$user->isSuperAdmin()) { $conversation->update(['cskh_id' => $user->id]); }`
  Thao tác này thực hiện không qua Database Locking (`lockForUpdate`) hay Transaction.
- **Hậu quả:** Khi 2 CSKH cùng mở một hội thoại chưa assign và cùng bấm gửi tin nhắn phản hồi ở gần như cùng một thời điểm, request của người tới sau sẽ ghi đè `cskh_id`, làm mất quyền quản lý của CSKH gửi tin nhắn trước đó mà không có cảnh báo.
- **Cách xử lý hiện tại:** `reply()` lấy lại conversation bằng `lockForUpdate()` trong transaction, kiểm tra lại `canReply()` trên bản ghi đã khóa, rồi mới gán `cskh_id`, mở lại conversation nếu cần và tạo message. Request đến sau khi conversation đã được assign sẽ bị từ chối `403`, không thể ghi đè người phụ trách.
  ```php
  $message = DB::transaction(function () use ($conversation, $user, $request) {
      $lockedConversation = Conversation::query()
          ->lockForUpdate()
          ->findOrFail($conversation->id);
      abort_unless($this->canReply($lockedConversation), 403);
      if (! $lockedConversation->cskh_id && ! $user->isSuperAdmin()) {
          $lockedConversation->update(['cskh_id' => $user->id]);
      }
      return $this->createMessage($lockedConversation, [
          'sender_id' => $user->id,
          'content' => $request->content,
      ]);
  });
  ```

## 5. Data Inconsistency / Unfiltered Metric trên Admin Dashboard *(đã sửa)*
> **Cập nhật 31/07/2026:** Bộ đếm đơn hàng Dashboard đã lọc `status = 'completed'`, cùng phạm vi nghiệp vụ với chỉ số doanh thu.
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Admin/DashboardController.php` (hàm `orderCountFor`, dòng 269–288)
- **Cơ chế trước khi sửa:** Doanh thu (`revenueFor`) chỉ tính đơn `completed`, nhưng `orderCountFor` đếm toàn bộ bản ghi `orders`, bao gồm đơn hủy và đơn chưa hoàn tất.
  ```php
  private function orderCountFor(?Carbon $from, ?Carbon $to): int
  {
      $query = Order::query();
      $query = $this->applyBranchScope($query);
      if ($from && $to && Schema::hasColumn('orders', 'created_at')) {
          $query->whereBetween('created_at', [$from, $to]);
      }
      return $query->count();
  }
  ```
- **Cách xử lý hiện tại:** Sau khi áp dụng branch scope và khoảng thời gian, truy vấn thêm `where('status', 'completed')`.
- **Kiểm thử:** `Tests\Feature\Admin\DashboardMetricsTest::test_dashboard_order_count_matches_completed_revenue_orders` xác nhận đơn đã hủy không làm tăng bộ đếm.
  ```php
  private function orderCountFor(?Carbon $from, ?Carbon $to): int
  {
      $query = Order::query();
      $query = $this->applyBranchScope($query);

      if (Schema::hasColumn('orders', 'status')) {
          $query->where('status', 'completed');
      }

      if ($from && $to && Schema::hasColumn('orders', 'created_at')) {
          $query->whereBetween('created_at', [$from, $to]);
      }

      return $query->count();
  }
  ```

## 6. Missing Scope Restriction / Authorization Bypass trong Đơn hàng nhóm *(đã sửa)*
> **Cập nhật 31/07/2026:** Danh sách và chi tiết group order đều áp dụng branch isolation bằng `group_orders.branch_id`.
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Admin/GroupOrderController.php` (hàm `applyBranchScope`, dòng 77–85 & hàm `show`, dòng 54–58)
- **Cơ chế hoạt động:** Trong hàm `applyBranchScope()`, nếu Admin (`role_id = 2`) chưa được gán `branch_id` (`$user->branch_id` bằng null), hàm trả về `$query` gốc không qua bộ lọc chi nhánh:
  `if (! $user->branch_id) { return $query; }`
  Đồng thời trong hàm `show()`, điều kiện kiểm tra phân quyền viết: `if ($groupOrder->order && ...)` làm cho các đơn hàng nhóm chưa đặt hàng (`order` bằng null) bị bỏ qua kiểm tra quyền.
- **Hậu quả:** Một Admin chưa gán chi nhánh hoặc Admin chi nhánh này có thể truy cập và xem toàn bộ Đơn hàng nhóm của tất cả các chi nhánh khác trong hệ thống, vi phạm nguyên tắc đóng đóng dữ liệu theo chi nhánh (Branch Isolation).
- **Cách xử lý hiện tại:** `applyBranchScope()` trả về query rỗng nếu Admin chưa có chi nhánh, hoặc lọc trực tiếp `where('branch_id', $user->branch_id)`. Hàm `show()` từ chối Admin không có chi nhánh hoặc group order thuộc chi nhánh khác.
- **Kiểm thử:** `Tests\Feature\Admin\GroupOrderManagementTest` xác nhận Admin không xem được group order của chi nhánh khác và Admin chưa gán chi nhánh không thấy dữ liệu.
  ```php
  private function applyBranchScope($query)
  {
      $user = auth()->user();
      if ($user->isSuperAdmin()) {
          return $query;
      }

      if (! $user->branch_id) {
          return $query->whereRaw('1 = 0');
      }

      return $query->where('branch_id', $user->branch_id);
  }
  ```

## 7. Missing Rate Limiter cho Chat Message Endpoint *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `routes/web.php` (dòng 110–116)
- **Cơ chế hoạt động:** Route `POST /chat/send` tiếp nhận tin nhắn từ khách hàng chỉ được bảo vệ bởi middleware `auth`. Không có bất kỳ middleware hạn chế tần suất gọi request (Rate Limiting/Throttle) nào được thiết lập.
- **Hậu quả:** Người dùng hoặc kẻ xấu có thể viết script gửi hàng nghìn tin nhắn chat trong vài giây, gây quá tải Web server, làm cạn kiệt tài nguyên Database và làm treo giao diện của nhân viên CSKH đang trực chat.
- **Cách khắc phục:** Áp dụng middleware `throttle` cho nhóm route chat.
  ```php
  Route::middleware(['auth', 'throttle:15,1'])->prefix('chat')->name('chat.')->group(function () {
      Route::get('/', [ChatController::class, 'getOrCreateConversation'])->name('index');
      Route::get('/nearest-branches', [ChatController::class, 'nearestBranches'])->name('nearest-branches');
      Route::post('/select-branch', [ChatController::class, 'selectBranch'])->name('select-branch');
      Route::get('/messages', [ChatController::class, 'messages'])->name('messages');
      Route::post('/send', [ChatController::class, 'send'])->name('send');
  });
  ```

## 8. Unhandled Exception / Silent Fail trong WebSocket Broadcasting *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Client/ChatController.php` (dòng 168, 264) & `app/Http/Controllers/Admin/AdminChatController.php` (dòng 220)
- **Cơ chế hoạt động:** Lệnh phát sự kiện WebSocket real-time `broadcast(new MessageSent($message))->toOthers()` được bọc trong khối try-catch:
  `try { broadcast(...); } catch (\Throwable) {}`
  Khối catch hoàn toàn rỗng và không ghi bất kỳ nhật ký lỗi nào.
- **Hậu quả:** Khi dịch vụ WebSocket (Reverb/Pusher) bị dừng, sai cấu hình TLS hoặc đứt kết nối mạng, sự kiện phát tin nhắn bị thất bại âm thầm. Nhân viên CSKH hoặc khách hàng không nhận được tin nhắn real-time, và đội ngũ phát triển không hề hay biết sự cố để xử lý do không có Log lỗi.
- **Cách khắc phục:** Ghi thông tin lỗi vào `Log::error()` khi quá trình broadcast thất bại.
  ```php
  try {
      broadcast(new MessageSent($message))->toOthers();
  } catch (\Throwable $e) {
      \Illuminate\Support\Facades\Log::error('Chat Broadcast Error: ' . $e->getMessage(), [
          'message_id' => $message->id,
          'conversation_id' => $message->conversation_id,
      ]);
  }
  ```

## 9. Missing Moderation Capability cho Đánh giá Sản phẩm *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/ReviewController.php` (toàn bộ controller)
- **Cơ chế hoạt động:** `ReviewController` trong thư mục Admin chỉ định nghĩa duy nhất method `index()`, phục vụ việc hiển thị danh sách đánh giá sản phẩm. Controller hoàn toàn thiếu các hàm `destroy()`, `update()`, hoặc `toggleStatus()`.
- **Hậu quả:** Admin không có công cụ để ẩn, duyệt hoặc xóa các đánh giá rác, đánh giá chứa từ ngữ thô tục, xúc phạm hoặc cạnh tranh không lành mạnh trên trang sản phẩm.
- **Cách khắc phục:** Bổ sung phương thức xóa và ẩn đánh giá trong `ReviewController`.
  ```php
  public function destroy(Review $review)
  {
      $review->delete();
      return redirect()->back()->with('success', 'Đã xóa đánh giá thành công.');
  }

  public function toggleStatus(Review $review)
  {
      $review->update(['status' => !$review->status]);
      return redirect()->back()->with('success', 'Đã cập nhật trạng thái hiển thị đánh giá.');
  }
  ```

## 10. Double Redirect / Session Flash Loss khi thêm Chi nhánh *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/BranchController.php` (hàm `store`, dòng 73 & `index`, dòng 19)
- **Cơ chế hoạt động:** Sau khi tạo mới chi nhánh thành công, `BranchController::store` điều hướng về `admin.branches.index` kèm Flash Session: `return redirect()->route('admin.branches.index')->with('success', ...);`. Tuy nhiên, tại `BranchController::index`, code lại tiếp tục thực hiện một chuyển hướng khác: `return redirect()->to(route('admin.super-admin') . '#branch-ranking');`.
- **Hậu quả:** Việc thực hiện Chuyển hướng 2 lần liên tiếp (Double Redirect) làm dữ liệu Flash Session `success` bị xóa khỏi bộ nhớ trước khi view kịp render, khiến SuperAdmin không bao giờ nhìn thấy thông báo *"Thêm chi nhánh thành công!"*.
- **Cách khắc phục:** Chuyển hướng thẳng tới route đích cuối cùng `admin.super-admin`.
  ```php
  return redirect()->to(route('admin.super-admin') . '#branch-ranking')
      ->with('success', 'Thêm chi nhánh thành công!');
  ```

## 11. Unsanitized Data Creation / Orphan Branch với Tọa độ Null *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (hàm `storeAdmin`, dòng 144–154)
- **Cơ chế hoạt động:** Khi SuperAdmin tạo một tài khoản Admin mới, hệ thống tự động khởi tạo một chi nhánh tương ứng trong DB:
  `Branch::create(['name' => "Chi nhánh - {$admin->name}", 'code' => "ADM{$admin->id}", 'address' => 'Không áp dụng', ...]);`
  Chi nhánh này được tạo với `latitude` và `longitude` mang giá trị `null`.
- **Hậu quả:** Chi nhánh ảo này lập tức xuất hiện trên giao diện phía Client (Chatbot / Tìm chi nhánh gần nhất). Khi ứng dụng tính khoảng cách tọa độ bằng công thức Haversine, giá trị `null` khiến kết quả trả về là `NaN/INF`, dẫn đến việc hiển thị chi nhánh rác với địa chỉ "Không áp dụng" cho khách hàng.
- **Cách khắc phục:** Đặt trạng thái chi nhánh tự động này là `status = false` (ngưng hoạt động) cho đến khi SuperAdmin cập nhật đủ tọa độ chuẩn.
  ```php
  $branch = Branch::create([
      'name' => "Chi nhánh - {$admin->name}",
      'code' => "ADM{$admin->id}",
      'email' => $admin->email,
      'phone' => null,
      'address' => 'Chưa cập nhật địa chỉ',
      'latitude' => null,
      'longitude' => null,
      'status' => false,
  ]);
  ```

## 12. Client-Side Memory Reset & Sequential Fetch Delay *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `resources/views/components/chatbox.blade.php` (Alpine.js state & hàm `activateSupportChat`, dòng 143)
- **Cơ chế hoạt động:** Khi người dùng bấm F5 nạp lại trang, toàn bộ state trong bộ nhớ RAM Javascript (`conversationId`, `messages[]`) bị làm trống. Khi mở ô chat, Alpine.js phải gửi 2 request AJAX tuần tự (`GET /chat` để lấy ID hội thoại, sau đó `GET /chat/messages` để lấy tin nhắn).
- **Hậu quả:** Trong khoảng 0.3s - 0.5s chờ 2 request AJAX hoàn tất, khung chatbox hiển thị trạng thái trắng hoặc xoay spinner "Đang tải tin nhắn...", tạo cảm giác giật lag cho người dùng.
- **Cách khắc phục:** Lưu `conversationId`, `branchId` và các tin nhắn gần nhất vào `localStorage` của trình duyệt để render tức thì (0ms) khi mở chatbox.
  ```javascript
  // Lưu cache tin nhắn khi fetch thành công
  localStorage.setItem('cached_chat_messages', JSON.stringify(this.messages));
  localStorage.setItem('cached_conversation_id', this.conversationId);
  ```

## 13. Missing Input Normalization cho Mã Voucher *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/VoucherController.php` (hàm `validatedData`, dòng 106–112)
- **Cơ chế hoạt động:** Khi Admin tạo hoặc sửa Voucher, controller lấy trực tiếp chuỗi `code` từ request mà không qua bước chuẩn hóa (làm sạch khoảng trắng thừa và chuyển thành chữ hoa).
- **Hậu quả:** Nếu Admin vô tình nhập ` giam20k ` (chữ thường và có khoảng trắng), chuỗi này được lưu nguyên bản vào DB. Khi khách hàng gõ mã `GIAM20K` ở trang checkout, hệ thống báo mã không tồn tại.
- **Cách khắc phục:** Chuẩn hóa mã voucher trước khi kiểm tra validation và lưu DB.
  ```php
  if ($request->has('code')) {
      $request->merge([
          'code' => strtoupper(trim((string) $request->input('code'))),
      ]);
  }
  ```

## 14. Missing Lower Bound Validation cho `usage_limit` Voucher *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/VoucherController.php` (hàm `validatedData`, dòng 117)
- **Cơ chế hoạt động:** Khi cập nhật một Voucher đã có lượt sử dụng (`used_count > 0`), controller chỉ kiểm tra `usage_limit` là số nguyên `>= 0` mà không đối chiếu với số lần voucher đã được dùng thực tế.
- **Hậu quả:** Admin có thể hạ `usage_limit` xuống 5 trong khi voucher đó đã được khách hàng sử dụng 10 lần (`used_count = 10`), gây sai lệch logic thống kê sử dụng voucher.
- **Cách khắc phục:** Bổ sung kiểm tra `usage_limit` không được nhỏ hơn `used_count` hiện tại.
  ```php
  $validator->after(function ($validator) use ($request, $voucher) {
      if ($voucher && $request->filled('usage_limit')) {
          $newLimit = (int) $request->input('usage_limit');
          if ($newLimit > 0 && $newLimit < (int) $voucher->used_count) {
              $validator->errors()->add('usage_limit', "Giới hạn sử dụng không thể nhỏ hơn số lượt đã dùng hiện tại ({$voucher->used_count}).");
          }
      }
  });
  ```

## 15. Short Timeout & Missing Retry cho Geocoding API *(không còn khớp code hiện tại)* *(đã sửa)*
> **Cập nhật 31/07/2026:** Không tìm thấy hàm `geocodeAddress()` trong `Client/ChatController` hiện tại. Mục này không nên dùng làm kết luận lỗi runtime; chỉ giữ lại như ghi chú lịch sử cần loại bỏ khi dọn báo cáo.
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Client/ChatController.php` (hàm `geocodeAddress`, dòng 75)
- **Cơ chế hoạt động:** Trong hàm `geocodeAddress`, request gọi tới OpenStreetMap Nominatim API thiết lập `timeout(4)` quá ngắn và không có cơ chế tự động thử lại (Retry).
- **Hậu quả:** Khi đường truyền mạng bị chậm hoặc dịch vụ OpenStreetMap phản hồi mất hơn 4 giây, request bị ngắt đột ngột và hệ thống nhảy về vị trí mặc định, làm sai lệch danh sách chi nhánh gần nhất của khách hàng.
- **Cách khắc phục:** Tăng thời gian timeout lên 8 giây và bổ sung cơ chế `retry(2, 200)`.
  ```php
  $response = \Illuminate\Support\Facades\Http::withHeaders([
      'User-Agent' => 'ChillDrink/1.0 (contact@chilldrink.com)',
  ])->timeout(8)->retry(2, 200)->get('https://nominatim.openstreetmap.org/search', [
      'q' => $address,
      'format' => 'json',
      'limit' => 1,
  ]);
  ```

## 16. Unsanitized Original Filename cho File Đính kèm Chat *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Client/ChatController.php` (hàm `sendMessage`, dòng 245)
- **Cơ chế hoạt động:** Khi người dùng đính kèm file trong tin nhắn chat, controller lấy trực tiếp tên file gốc `$file->getClientOriginalName()` để lưu vào cột `attachment_name` trong DB.
- **Hậu quả:** Tên file chứa ký tự đặc biệt, thẻ HTML hoặc ký tự lạ có thể làm hỏng định dạng hiển thị giao diện chatbox hoặc tiềm ẩn nguy cơ XSS khi render ở các phần hiển thị không qua escape.
- **Cách khắc phục:** Làm sạch tên file trước khi lưu trữ bằng `strip_tags` hoặc `Str::ascii`.
  ```php
  if ($request->hasFile('attachment')) {
      $file = $request->file('attachment');
      $cleanName = strip_tags($file->getClientOriginalName());
      $attachmentName = preg_replace('/[^\w\s\.-]/u', '_', $cleanName);
      $attachmentPath = $file->store('chat-attachments', 'public');
  }
  ```

## 17. Missing Role Restriction cho Catalog dùng chung *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/ProductController.php`, `CategoryController.php`, `VoucherController.php`
- **Cơ chế hoạt động:** Các controller quản lý Sản phẩm, Danh mục, Voucher sử dụng chung middleware `admin`. Bất kỳ tài khoản nào có vai trò Admin (`role_id = 2`) thuộc bất kỳ chi nhánh nào đều có toàn quyền Thêm, Sửa, Xóa catalog sản phẩm dùng chung.
- **Hậu quả:** Admin thuộc Chi nhánh A có thể vô tình hoặc cố ý thay đổi giá sản phẩm hoặc xóa danh mục dùng chung của toàn hệ thống, làm ảnh hưởng trực tiếp đến hoạt động kinh doanh của Chi nhánh B.
- **Cách khắc phục:** Ràng buộc quyền chỉnh sửa Catalog toàn hệ thống chỉ dành cho SuperAdmin hoặc Admin có quyền hạn đặc biệt.

## 18. Missing Event Notification khi thay đổi Vai trò hoặc Khóa tài khoản *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/UserController.php` (hàm `update`, dòng 103 & `toggleStatus`, dòng 132)
- **Cơ chế hoạt động:** Khi SuperAdmin thực hiện thay đổi vai trò người dùng (ví dụ: chuyển từ Admin xuống Customer) hoặc tiến hành Khóa tài khoản, controller chỉ ghi bản ghi vào bảng `system_logs` mà không phát ra bất kỳ thông báo hay Email nào.
- **Hậu quả:** Người dùng bị khóa tài khoản hoặc hạ quyền truy cập không hề hay biết lý do cho đến khi cố gắng đăng nhập lại và bị hệ thống từ chối.
- **Cách khắc phục:** Bổ sung sự kiện gửi Email thông báo khi tài khoản bị thay đổi trạng thái hoặc vai trò.
  ```php
  // Gửi email thông báo khi khóa/mở khóa tài khoản
  \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\AccountStatusChangedMail($user, $newStatus));
  ```

---

# CÁC LỖI & ĐỀ XUẤT CẢI THIỆN RIÊNG CHO LUỒNG ADMIN (ADMIN FLOW ISSUES & IMPROVEMENTS)

> **Ghi chú phân biệt:** Phần này tổng hợp riêng biệt tất cả các lỗi logic, sự cố vi phạm dữ liệu, lỗ hổng phân quyền và các điểm cần nâng cấp trải nghiệm (UX/Performance) được tìm thấy trong luồng Quản trị (Admin / Super Admin / CSKH Module).

## 19. Foreign Key Constraint Violation khi Force Delete Sản phẩm đã có trong Lịch sử Đơn hàng *(đã sửa)*
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Admin/ProductController.php` (hàm `forceDelete`, dòng 398–417) & `database/migrations/2026_05_17_121748_create_order_items_table.php` (dòng 23)
- **Cơ chế hoạt động:** Trong `ProductController::forceDelete`, hệ thống gọi lệnh xóa vĩnh viễn sản phẩm khỏi Database (`$product->forceDelete()`). Tuy nhiên, trong bảng `order_items` (chi tiết đơn hàng), khóa ngoại `product_id` chỉ được định nghĩa `$table->foreign('product_id')->references('id')->on('products');` mà KHÔNG CÓ `onDelete('cascade')` hay `onDelete('set null')`.
- **Hậu quả:** Khi Admin thực hiện xóa vĩnh viễn một sản phẩm từng được đặt mua trong các đơn hàng lịch sử, Database sẽ từ chối và ném ra lỗi `Integrity constraint violation: 1451`, khiến màn hình Admin bị sập 500 Internal Server Error.
- **Cách khắc phục:** Kiểm tra xem sản phẩm đã từng tồn tại trong bảng `order_items` hay chưa trước khi gọi `forceDelete()`. Nếu sản phẩm đã nằm trong đơn hàng, chặn thao tác xóa vĩnh viễn và yêu cầu giữ sản phẩm trong trạng thái Soft Delete (thùng rác).
  ```php
  public function forceDelete(string $id)
  {
      $product = Product::withTrashed()->whereKey($id)->orWhere('slug', $id)->firstOrFail();

      $hasOrders = \Illuminate\Support\Facades\DB::table('order_items')
          ->where('product_id', $product->id)
          ->exists();

      if ($hasOrders) {
          return redirect()->route('admin.products.trash')
              ->with('error', 'Không thể xóa vĩnh viễn! Sản phẩm này đã tồn tại trong lịch sử đơn hàng của khách hàng. Vui lòng duy trì lưu trữ trong thùng rác.');
      }

      if ($product->image) {
          Storage::disk('public')->delete($product->image);
      }

      $product->forceDelete();

      return redirect()->route('admin.products.trash')
          ->with('success', 'Đã xóa vĩnh viễn sản phẩm!');
  }
  ```

## 20. Thiếu Logic Hoàn trả Tồn kho & Số lượt Voucher khi Admin Hủy Đơn hàng *(đã sửa)*
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Admin/OrderController.php` (hàm `updateStatus`, dòng 248–313)
- **Cơ chế hoạt động:** Khi Admin cập nhật trạng thái đơn hàng sang `CANCELLED` (`Hủy đơn`), controller chỉ cập nhật cột `status` và `cancellation_reason`. Hệ thống hoàn toàn không thực hiện cộng trả lại số lượng tồn kho (`stock`) cho từng sản phẩm trong đơn, cũng như không hoàn trả lại lượt sử dụng (`used_count`) cho Voucher nếu đơn hàng có áp dụng mã giảm giá.
- **Hậu quả:** 
  1. Số lượng sản phẩm tồn kho bị giảm vô lý sau mỗi đơn bị hủy, dẫn đến tình trạng báo "Hết hàng" trên website dù thực tế sản phẩm vẫn còn nguyên trong kho.
  2. Số lượt sử dụng voucher bị tính sai, làm thất thoát mã giảm giá của khách hàng và gây sai lệch báo cáo thống kê voucher.
- **Cách khắc phục:** Bổ sung logic hoàn tồn kho sản phẩm và giảm `used_count` voucher bọc trong DB Transaction khi đơn chuyển sang `CANCELLED`.
  ```php
  if ($newStatus === OrderStatus::CANCELLED && $oldStatus !== OrderStatus::CANCELLED) {
      \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
          foreach ($order->orderItems as $item) {
              if ($item->product) {
                  $item->product->increment('stock', $item->quantity);
              }
          }

          if ($order->coupon_id) {
              \App\Models\Voucher::where('id', $order->coupon_id)
                  ->where('used_count', '>', 0)
                  ->decrement('used_count');
          }
      });
  }
  ```

## 21. Thiếu AJAX/JSON Response Handler khi Cập nhật Trạng thái Đơn hàng từ Bảng Real-time *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/OrderController.php` (hàm `updateStatus`, dòng 248–313)
- **Cơ chế hoạt động:** Màn hình quản trị đơn hàng Real-time thường gọi request AJAX để chuyển nhanh trạng thái đơn hàng (ví dụ: bấm nút "Xác nhận", "Giao hàng"). Tuy nhiên, trong `OrderController::updateStatus`, toàn bộ các nhánh trả về kết quả thành công hoặc thất bại đều dùng `redirect()->back()->with(...)` (HTTP 302 Redirect).
- **Hậu quả:** Khi JS Frontend gửi AJAX request, response nhận được là một trang HTML redirect đầy đủ thay vì dữ liệu JSON cấu trúc. Điều này gây lỗi parse JSON trên giao diện JS, làm đứt đoạn hiệu ứng cập nhật real-time và không hiển thị được thông báo lỗi cho Admin.
- **Cách khắc phục:** Kiểm tra nếu request là AJAX / JSON thì trả về response JSON phù hợp.
  ```php
  if ($request->wantsJson() || $request->ajax()) {
      return response()->json([
          'success' => true,
          'message' => "Đã cập nhật trạng thái đơn hàng thành: {$statusLabel}",
          'order_id' => $order->id,
          'status' => $newStatus,
      ]);
  }
  ```

## 22. Thiếu Phân trang (Pagination) gây Quá tải Bộ nhớ khi Tải Danh sách Slide Banner *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/BranchSlideController.php` (hàm `index`, dòng 32 & `trash`, dòng 169)
- **Cơ chế hoạt động:** Trong `BranchSlideController`, danh sách slide quảng cáo được lấy bằng câu lệnh `$branch->slides()->get()` và `$branch->slides()->onlyTrashed()->get()`. Code không áp dụng phân trang (`paginate()`) hay giới hạn số bản ghi.
- **Hậu quả:** Theo thời gian sử dụng, khi một chi nhánh tạo ra hàng trăm banner quảng cáo, việc nạp toàn bộ slide kèm ảnh dung lượng lớn trong một request duy nhất sẽ tiêu tốn bộ nhớ RAM server, làm chậm tốc độ tải trang admin và có thể làm đơ trình duyệt của Admin.
- **Cách khắc phục:** Thay thế `.get()` bằng `.paginate(10)->withQueryString()` cho cả màn hình danh sách slide và thùng rác slide.
  ```php
  $slides = $branch ? $branch->slides()->paginate(10)->withQueryString() : collect();
  ```

## 23. Thiếu Event Broadcasting & System Audit Log khi Đóng Cuộc trò chuyện CSKH *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/AdminChatController.php` (hàm `close`, dòng 136–143)
- **Cơ chế hoạt động:** Khi nhân viên CSKH bấm nút "Đóng cuộc trò chuyện", controller chỉ thực hiện `update(['status' => 'closed'])` và redirect về trang trước. Hệ thống không phát bất kỳ sự kiện WebSocket nào để thông báo cho khách hàng và cũng không ghi log nhật ký hệ thống `SystemLog`.
- **Hậu quả:** 
  1. Phía ô chat của khách hàng vẫn hiển thị trạng thái đang mở, khiến khách hàng tiếp tục gõ tin nhắn trả lời mà không biết cuộc trò chuyện đã kết thúc.
  2. SuperAdmin không thể kiểm tra hoặc truy vết nhân viên CSKH nào đã đóng hội thoại nào trong các báo cáo vận hành.
- **Cách khắc phục:** Phát sự kiện WebSocket thông báo chat closed và ghi log nhật ký hệ thống.
  ```php
  public function close(Conversation $conversation)
  {
      $this->authorizeView($conversation);

      $conversation->update(['status' => 'closed']);

      try {
          broadcast(new \App\Events\ConversationClosed($conversation))->toOthers();
      } catch (\Throwable $e) {}

      SystemLog::record(
          auth()->user(),
          "Đã đóng cuộc trò chuyện #{$conversation->id} của khách hàng {$conversation->user?->name}",
          'chat',
          'info'
      );

      return back()->with('success', 'Cuộc trò chuyện đã được đóng!');
  }
  ```

## 24. Thiếu Ràng buộc Phân quyền Xóa Chi nhánh với Dữ liệu Chat History & Banner Slide *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/BranchController.php` (hàm `destroy`, dòng 236–254)
- **Cơ chế hoạt động:** Trước khi xóa một chi nhánh, `BranchController::destroy` chỉ kiểm tra xem chi nhánh đó có `$branch->users()->exists()` hoặc `$branch->orders()->exists()` hay không. Controller bỏ qua việc kiểm tra các dữ liệu thuộc về chi nhánh khác như `slides` (Banner slide) hay `conversations` (Lịch sử chat CSKH).
- **Hậu quả:** Nếu chi nhánh chưa có đơn hàng nhưng đã có slide hoặc cuộc trò chuyện chat, SuperAdmin bấm xóa chi nhánh sẽ khiến các bản ghi `BranchSlide` và `Conversation` bị mồ côi (`orphan records`). Khi hệ thống truy vấn thông tin chi nhánh của slide/chat đó sẽ gây ra lỗi `Attempt to read property "name" on null`.
- **Cách khắc phục:** Thêm kiểm tra tồn tại của `slides()` và `conversations()` trước khi cho phép xóa chi nhánh.
  ```php
  if ($branch->slides()->exists() || $branch->conversations()->exists()) {
      return redirect()->route('admin.branches.index')
          ->with('error', 'Không thể xóa! Chi nhánh này vẫn còn chứa dữ liệu Slide quảng cáo hoặc Lịch sử chat CSKH.');
  }
  ```

## 25. Không Chuẩn hóa (Trim) Dữ liệu Nhập vào Tên Sản phẩm & Danh mục gây Lỗi Slug và Tìm kiếm *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/ProductController.php` (dòng 138, 255) & `CategoryController.php` (dòng 63, 108)
- **Cơ chế hoạt động:** Khi Admin tạo mới hoặc cập nhật Sản phẩm/Danh mục, controller lấy trực tiếp chuỗi `$validated['name']` từ input request mà không gọi `trim()`.
- **Hậu quả:** Nếu Admin lỡ tay dán chuỗi có khoảng trắng thừa ở đầu/cuối (ví dụ: `"  Trà Sữa Oolong  "`), tên lưu vào DB sẽ bị dính khoảng trắng, tạo ra chuỗi slug bị thừa gạch nối (`--tra-sua-oolong--`). Điều này làm xấu liên kết SEO, gây lỗi tìm kiếm chính xác và làm lệch định dạng hiển thị UI.
- **Cách khắc phục:** Làm sạch khoảng trắng thừa trước khi xử lý validation và lưu DB.
  ```php
  $request->merge([
      'name' => trim((string) $request->input('name')),
  ]);
  ```

## 26. Thiếu Chức năng Reset Mật khẩu Admin Cấp dưới dành cho Super Admin *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (toàn bộ controller)
- **Cơ chế hoạt động:** Màn hình Super Admin (`/admin/super-admin`) hỗ trợ tạo tài khoản Admin (`storeAdmin`), đổi chi nhánh (`updateBranch`), đổi vai trò (`updateRole`), nhưng hoàn toàn không có phương thức hỗ trợ Đặt lại mật khẩu (Reset Password) cho tài khoản Admin thuộc quyền quản lý.
- **Hậu quả:** Khi một Admin chi nhánh quên mật khẩu đăng nhập, SuperAdmin không có giao diện thao tác trực tiếp trên hệ thống mà phải truy cập Database MySQL hoặc sử dụng command line, gây gián đoạn công việc quản lý bán hàng tại chi nhánh.
- **Cách khắc phục:** Bổ sung hàm `resetAdminPassword(Request $request, User $user)` trong `SuperAdminController` cho phép SuperAdmin đặt lại mật khẩu mới cho Admin cấp dưới.
  ```php
  public function resetAdminPassword(Request $request, User $user)
  {
      abort_unless(auth()->user()->isSuperAdmin(), 403);

      $validated = $request->validate([
          'password' => ['required', 'string', 'min:8', 'confirmed'],
      ]);

      $user->update([
          'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
      ]);

      SystemLog::record(auth()->user(), "Đã đặt lại mật khẩu cho Admin {$user->email}", 'security', 'warning');

      return back()->with('success', "Đã đặt lại mật khẩu thành công cho tài khoản {$user->email}");
  }
  ```

## 27. Thiếu Bộ lọc Nâng cao (Số sao, Sản phẩm, Từ khóa) cho Màn hình Quản lý Đánh giá *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/ReviewController.php` (hàm `index`, dòng 15–25)
- **Cơ chế hoạt động:** `ReviewController::index` hiện tại chỉ truy vấn danh sách đánh giá mặc định mà không nhận bất kỳ tham số filter nào từ `$request` (như `rating`, `product_id`, `q`).
- **Hậu quả:** Khi số lượng đánh giá sản phẩm tăng lên hàng ngàn bản ghi, Admin không thể lọc nhanh các đánh giá 1-2 sao (đánh giá tiêu cực) để ưu tiên xử lý hoặc ẩn hiển thị, làm giảm hiệu quả kiểm duyệt nội dung.
- **Cách khắc phục:** Thêm bộ lọc đa điều kiện trong `ReviewController::index`.
  ```php
  public function index(Request $request)
  {
      $reviews = Review::with(['user', 'product'])
          ->when($request->filled('rating'), fn ($q) => $q->where('rating', $request->rating))
          ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
          ->when($request->filled('q'), function ($q) use ($request) {
              $q->where('comment', 'like', "%{$request->q}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->q}%"));
          })
          ->latest()
          ->paginate(15)
          ->withQueryString();

      return view('admin.reviews.index', compact('reviews'));
  }
  ```

## 28. Lock Account Bypass / Khóa Tài khoản nhưng Session vẫn giữ quyền Truy cập Admin *(đã sửa)*
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Middleware/AdminMiddleware.php` (dòng 19), `CskhMiddleware.php` (dòng 13), `SuperAdminMiddleware.php` (dòng 13)
- **Cơ chế hoạt động:** Khi SuperAdmin thực hiện khóa tài khoản một Admin hoặc CSKH (`is_active = false`), trạng thái được ghi vào Database. Tuy nhiên, cả 3 middleware bảo mật `AdminMiddleware`, `CskhMiddleware`, `SuperAdminMiddleware` chỉ kiểm tra vai trò `role_id` mà hoàn toàn bỏ qua việc kiểm tra `is_active === true`.
- **Hậu quả:** Nhân viên hoặc Admin đã bị SuperAdmin khóa tài khoản vẫn có thể tiếp tục xem, sửa, xóa dữ liệu nhạy cảm của hệ thống nếu phiên làm việc (Session/Cookie) của họ chưa bị hủy hoặc chưa hết hạn.
- **Cách khắc phục:** Thêm kiểm tra `is_active` vào tất cả các middleware quản trị hệ thống.
  ```php
  if (!auth()->check() || !auth()->user()->is_active || !auth()->user()->isAdmin()) {
      auth()->logout();
      return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa hoặc không có quyền truy cập.');
  }
  ```

## 29. Lỗi Không Thu hồi Điểm thưởng khi Hủy Đơn Completed & Nhân bản Điểm Tích lũy *(đã sửa)*
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Models/Order.php` (hàm `awardLoyaltyPoints`, dòng 213–236) & `app/Http/Controllers/Admin/OrderController.php` (dòng 305)
- **Cơ chế hoạt động:** 
  1. Khi đơn hàng được chuyển sang trạng thái `COMPLETED`, controller tự động gọi `awardLoyaltyPoints()`. Nếu sau đó Admin chuyển trạng thái đơn hàng sang `CANCELLED` (Hủy đơn/Hoàn tiền), hệ thống không có logic thu hồi lại số điểm thưởng đã cấp trước đó.
  2. Nếu trạng thái đơn hàng bị Admin chuyển qua lại giữa `COMPLETED` và các trạng thái khác nhiều lần, hàm `awardLoyaltyPoints()` không kiểm tra sự tồn tại của bản ghi giao dịch trong `point_transactions`, dẫn đến việc điểm thưởng bị cộng trùng nhiều lần.
- **Hậu quả:** Khách hàng có thể gian lận nhận điểm thưởng từ các đơn hàng bị hủy hoặc được cộng x2, x3 số điểm cho cùng một đơn hàng, dùng điểm đó đổi voucher miễn phí gây thiệt hại doanh thu.
- **Cách khắc phục:** Kiểm tra giao dịch trước khi cộng điểm và bổ sung hàm thu hồi điểm khi đơn bị hủy.
  ```php
  public function awardLoyaltyPoints(): void
  {
      if (!$this->user_id) return;
      
      $alreadyAwarded = PointTransaction::where('reference_type', 'order')
          ->where('reference_id', $this->id)
          ->where('type', 'earn')
          ->exists();

      if ($alreadyAwarded) return;

      $points = $this->pointsEarnable();
      if ($points > 0) {
          LoyaltyPoint::getOrCreateForUser($this->user_id)->addPoints(
              points: $points,
              type: 'earn',
              description: "Hoàn thành đơn hàng #{$this->id}",
              referenceType: 'order',
              referenceId: $this->id
          );
      }
  }
  ```

## 30. Lỗi Mặc định sai Trạng thái khi Tạo mới Topping ở dạng Ngưng bán *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/ToppingController.php` (hàm `store`, dòng 39)
- **Cơ chế hoạt động:** Trong `ToppingController::store`, giá trị `status` được gán: `$validated['status'] = $request->has('status') ? (bool)$request->status : true;`. Khi Admin bỏ tích chọn checkbox "Hiển thị" (nghĩa là muốn tạo Topping ngưng bán ngay từ đầu), `$request->has('status')` trả về `false`, khiến code nhảy vào nhánh mặc định `: true`.
- **Hậu quả:** Mọi Topping được tạo mới khi bỏ tích chọn trạng thái đều bị ép lưu thành `status = true` (Kích hoạt), khiến Admin không bao giờ khởi tạo được một Topping ở trạng thái Ẩn/Ngưng bán.
- **Cách khắc phục:** Sử dụng `$request->boolean('status')` để cast chính xác giá trị boolean từ request.
  ```php
  $validated['status'] = $request->boolean('status');
  ```

## 31. Đứt đoạn Đồng bộ Real-time Trạng thái Đơn hàng khi Polling *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/OrderController.php` (hàm `recent`, dòng 129)
- **Cơ chế hoạt động:** Endpoint polling `/admin/orders/recent` chỉ truy vấn các đơn hàng có `where('id', '>', $afterId)`. Cơ chế này chỉ bắt được các đơn hàng *mới được tạo*, nhưng bỏ qua hoàn toàn các đơn hàng cũ vừa được cập nhật trạng thái.
- **Hậu quả:** Khi Admin A đổi trạng thái một đơn hàng từ "Chờ xác nhận" sang "Đang chuẩn bị", request polling của Admin B sẽ không nhận được thông tin cập nhật này. Giao diện quản lý đơn của Admin B bị sai lệch trạng thái thực tế với Admin A cho đến khi bấm F5 nạp lại trang.
- **Cách khắc phục:** Cho phép polling lọc thêm các đơn hàng có `updated_at > last_polled_at`.
  ```php
  $orders = Order::query()
      ->with(['user', 'branch', 'address', 'orderItems.product', 'orderItems.productSize.size'])
      ->where('status', '!=', OrderStatus::AWAITING_EMAIL_CONFIRMATION)
      ->where(function ($query) use ($afterId, $updatedAfter) {
          if ($afterId > 0) $query->where('id', '>', $afterId);
          if ($updatedAfter) $query->orWhere('updated_at', '>', $updatedAfter);
      });
  ```

## 32. Authorization Bypass / Xem Đơn hàng Nhóm của Chi nhánh khác khi Đơn chưa Checkout *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/GroupOrderController.php` (hàm `show`, dòng 55)
- **Cơ chế hoạt động:** Trong `GroupOrderController::show`, việc phân quyền viết: `if ($groupOrder->order && $user->branch_id && $groupOrder->order->branch_id !== $user->branch_id)`. Khi đơn hàng nhóm đang diễn ra hoặc chưa tạo đơn hàng chính thức (`$groupOrder->order` bằng null), điều kiện kiểm tra bị bỏ qua hoàn toàn.
- **Hậu quả:** Admin thuộc Chi nhánh A có thể truy cập xem danh sách thành viên, giỏ hàng nhóm và tin nhắn nội bộ của Đơn hàng nhóm thuộc Chi nhánh B khi nhóm đó chưa bấm chốt đơn.
- **Cách khắc phục:** Kiểm tra trực tiếp thuộc tính `$groupOrder->branch_id`.
  ```php
  if (!$user->isSuperAdmin() && $user->branch_id && $groupOrder->branch_id !== $user->branch_id) {
      abort(403, 'Bạn không có quyền xem đơn nhóm của chi nhánh khác.');
  }
  ```

## 33. Mất Tin nhắn Mới của Khách hàng khi Gửi vào Cuộc trò chuyện đã Đóng *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Client/ChatController.php` (hàm `sendMessage`, dòng 249) & `AdminChatController.php` (dòng 151)
- **Cơ chế hoạt động:** Khi CSKH bấm "Đóng chat", hội thoại chuyển sang `status = 'closed'`. Khi khách hàng nhắn tin lại sau đó, `ChatController::sendMessage` ghi tin nhắn mới nhưng KHÔNG tự động chuyển `status` của conversation từ `'closed'` về `'open'`. Trong khi đó, màn hình CSKH chỉ nạp các hội thoại `where('status', 'open')`.
- **Hậu quả:** Tin nhắn mới của khách hàng hoàn toàn biến mất khỏi danh sách chờ trả lời của nhân viên CSKH. Khách hàng chờ phản hồi vô ích mà nhân viên không hề biết có câu hỏi mới.
- **Cách khắc phục:** Tự động mở lại hội thoại khi khách hàng gửi tin nhắn mới.
  ```php
  if ($conversation->status === 'closed') {
      $conversation->update(['status' => 'open']);
  }
  ```

## 34. Thiếu Kiểm tra Ràng buộc & Không Hoàn điểm khi SuperAdmin Xóa Voucher *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/VoucherController.php` (hàm `destroy`, dòng 94) & `database/migrations/2026_05_17_121831_create_user_vouchers_table.php` (dòng 22)
- **Cơ chế hoạt động:** Khi Admin chọn xóa một Voucher, hệ thống thực hiện `$voucher->delete()`. Vì bảng `user_vouchers` cài đặt `onDelete('cascade')`, tất cả bản ghi voucher đã đổi trong ví của người dùng bị xóa sạch khỏi Database.
- **Hậu quả:** Khách hàng bị mất voucher trong ví và mất luôn số điểm tích lũy đã dùng để đổi voucher trước đó mà không nhận được bất kỳ lời giải thích hay hoàn điểm nào.
- **Cách khắc phục:** Chặn xóa Voucher nếu đã được khách hàng sở hữu trong ví, chỉ cho phép chuyển sang `status = false`.
  ```php
  public function destroy(Voucher $voucher): RedirectResponse
  {
      if ($voucher->userVouchers()->exists()) {
          return redirect()->route('admin.vouchers.index')
              ->with('error', 'Không thể xóa! Voucher này đã được khách hàng lưu/đổi vào ví. Vui lòng chuyển trạng thái sang Tắt.');
      }

      $voucher->delete();

      return redirect()->route('admin.vouchers.index')->with('success', 'Đã xóa voucher.');
  }
  ```

## 35. Over-querying / Bị bùng nổ Query SQL khi nạp Màn hình Dashboard Admin *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/DashboardController.php` (hàm `gatherDashboardData`, dòng 90–151)
- **Cơ chế hoạt động:** Mỗi lần một Admin mở hoặc F5 trang Dashboard, controller chạy từ 40 đến hơn 60 câu lệnh SQL `COUNT`, `SUM`, `whereBetween` để tính chỉ số 4 khoảng thời gian (Hôm nay/Tuần/Tháng/Năm) và dựng vẽ biểu đồ. Code không sử dụng bất kỳ lớp Caching nào.
- **Hậu quả:** Gây nghẽn CPU/RAM của Database Server khi có nhiều Admin chi nhánh cùng theo dõi báo cáo hoặc khi dữ liệu bảng `orders` phình to lên hàng trăm ngàn dòng.
- **Cách khắc phục:** Áp dụng `Cache::remember()` với TTL từ 5–15 phút cho các báo cáo thống kê Dashboard.
  ```php
  $cacheKey = "admin_dashboard_data_branch_{$this->dashboardBranchId}_{$selectedPeriod}";
  return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(10), function () use ($selectedPeriod) {
      return $this->gatherDashboardDataRaw($selectedPeriod);
  });
  ```

---

# CÁC LỖI & ĐỀ XUẤT CẢI THIỆN RIÊNG CHO LUỒNG SUPER ADMIN (SUPER ADMIN FLOW ISSUES & IMPROVEMENTS)

> **Ghi chú phân biệt:** Phần này tổng hợp riêng biệt tất cả các lỗi logic, sự cố vi phạm dữ liệu, lỗ hổng phân quyền, điểm nghẽn hiệu năng (N+1 Query) và các tính năng cần nâng cấp dành riêng cho luồng Quản trị Tối cao (Super Admin Module).

## 36. N+1 Query Explosion & Severe Performance Degradation trong Bảng Xếp hạng Chi nhánh *(đã sửa)*
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (hàm `branchRankingStats`, dòng 437–486)
- **Cơ chế hoạt động:** Trong hàm `branchRankingStats`, controller lấy danh sách tất cả chi nhánh và dùng hàm `.map()` duyệt qua từng chi nhánh. Với MỖI chi nhánh trong vòng lặp, hệ thống lại thực hiện 5 câu truy vấn Eloquent riêng biệt (`$allOrders`, `$paidOrders`, `$completedOrders`, `$cancelledOrders`, `$admin`).
- **Hậu quả:** Khi hệ thống mở rộng có 20–30 chi nhánh, một lần SuperAdmin mở trang Dashboard sẽ phát sinh từ 120 đến hơn 180 câu lệnh SQL trùng lặp. Điều này gây treo đơ giao diện điều khiển của SuperAdmin và làm suy giảm hiệu năng toàn bộ Database Server.
- **Cách khắc phục:** Thay thế vòng lặp N+1 bằng 1 câu truy vấn gom nhóm `GROUP BY branch_id` duy nhất.
  ```php
  $branchStats = DB::table('orders')
      ->selectRaw('branch_id, COUNT(*) as total_orders, 
                  SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_orders,
                  SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders,
                  SUM(CASE WHEN payment_status = "paid" OR status = "completed" THEN total ELSE 0 END) as revenue')
      ->whereNotNull('branch_id')
      ->groupBy('branch_id')
      ->get()
      ->keyBy('branch_id');
  ```

## 37. Khóa Ngoại Duy nhất Sai Phạm vi Ngăn gán Nhiều Nhân viên cho cùng Chi nhánh *(đã sửa)*
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (hàm `updateBranch`, dòng 586–588)
- **Cơ chế hoạt động:** Quy tắc validation khi SuperAdmin gán chi nhánh cho người dùng được viết: `Rule::unique('users', 'branch_id')->ignore($user->id)->whereNotNull('branch_id')`. Điều kiện này bắt buộc giá trị `branch_id` phải là DUY NHẤT trên toàn bộ bảng `users`.
- **Hậu quả:** SuperAdmin không bao giờ gán được nhân viên thứ 2 (nhân viên CSKH, Admin phụ hoặc Staff) vào một chi nhánh đã có sẵn 1 tài khoản, làm hỏng hoàn toàn mô hình quản lý nhiều nhân viên trên 1 chi nhánh.
- **Cách khắc phục:** Loại bỏ ràng buộc `unique` trên cột `branch_id` hoặc chỉ giới hạn kiểm tra duy nhất đối với vai trò Trưởng chi nhánh (Admin `role_id = 2`).
  ```php
  'branch_id' => ['nullable', 'exists:branches,id'],
  ```

## 38. Hiển thị Mật khẩu Ảo / Nhầm lẫn Mật khẩu Admin trên Màn hình SuperAdmin Dashboard *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (hàm `storeAdmin`, dòng 135 & `branchRankingStats`, dòng 475)
- **Cơ chế hoạt động:** Khi SuperAdmin khởi tạo tài khoản Admin mới (`storeAdmin`), hệ thống mã hóa password bằng `Hash::make()` nhưng KHÔNG lưu trường `plain_password`. Tuy nhiên trong hàm `branchRankingStats`, code lại lấy giá trị hiển thị: `'admin_password' => $admin?->plain_password ?? '12345678'`.
- **Hậu quả:** Bảng quản lý chi nhánh của SuperAdmin hiển thị mật khẩu mặc định "12345678" cho tất cả các Admin mới tạo. SuperAdmin copy mật khẩu này đưa cho nhân viên nhưng nhân viên đăng nhập không thành công vì mật khẩu thực sự đặt lúc tạo khác với chuỗi ảo hiển thị.
- **Cách khắc phục:** Lưu `plain_password` khi tạo mới hoặc xóa cột hiển thị mật khẩu thô khỏi bảng thống kê để đảm bảo an toàn bảo mật.
  ```php
  $admin = User::create([
      'name' => $validated['name'],
      'email' => strtolower($validated['email']),
      'password' => Hash::make($validated['password']),
      'plain_password' => $validated['password'], // Lưu plain_password đồng bộ
      'role_id' => 2,
      'is_active' => $request->boolean('is_active', true),
  ]);
  ```

## 39. Tự động Tạo Chi nhánh Mới thiếu Tọa độ gây Xuất hiện "Chi nhánh Ma" phía Client *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (hàm `storeAdmin`, dòng 144–154)
- **Cơ chế hoạt động:** Khi SuperAdmin tạo một tài khoản Admin mới, hệ thống tự động khởi tạo 1 bản ghi `Branch` tương ứng với `address = 'Không áp dụng'`, `latitude = null`, `longitude = null` và gán trạng thái `status = true` (Kích hoạt).
- **Hậu quả:** Chi nhánh mới tạo chưa có địa chỉ thực tế lập tức xuất hiện công khai trên giao diện trang chủ/chatbox phía Client. Công thức Haversine tính khoảng cách bị lỗi `NaN/INF`, hiển thị chi nhánh rác với địa chỉ "Không áp dụng" cho khách hàng.
- **Cách khắc phục:** Đặt trạng thái ban đầu của chi nhánh tự tạo là `status = false` (Tắt) cho đến khi SuperAdmin cập nhật địa chỉ chuẩn.
  ```php
  $branch = Branch::create([
      'name' => "Chi nhánh - {$admin->name}",
      'code' => "ADM{$admin->id}",
      'email' => $admin->email,
      'address' => 'Chưa cập nhật địa chỉ',
      'status' => false, // Mặc định ngưng hoạt động cho tới khi cập nhật tọa độ
  ]);
  ```

## 40. Thiếu Nhật ký Hệ thống (System Audit Log) khi Thao tác Trực tiếp trên Chi nhánh *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/BranchController.php` (các hàm `store`, `update`, `destroy`, `toggleStatus`)
- **Cơ chế hoạt động:** Các thao tác quan trọng của SuperAdmin như Tạo chi nhánh (`store`), Sửa địa chỉ/tọa độ (`update`), Xóa chi nhánh (`destroy`) và Bật/Tắt hoạt động (`toggleStatus`) trong `BranchController` hoàn toàn không ghi log vào bảng `system_logs`.
- **Hậu quả:** Khi xảy ra sự cố xóa nhầm chi nhánh hoặc đổi tọa độ khiến khách hàng không đặt được hàng, SuperAdmin không thể xem lịch sử audit log để biết ai đã thực hiện thay đổi và thời điểm thay đổi.
- **Cách khắc phục:** Bổ sung `SystemLog::record()` vào tất cả các action ghi dữ liệu trong `BranchController`.
  ```php
  SystemLog::record(
      request()->user(),
      "Đã " . ($branch->status ? 'kích hoạt' : 'vô hiệu hóa') . " chi nhánh {$branch->name}",
      'branch',
      'info',
      ['branch_id' => $branch->id]
  );
  ```

## 41. Tính toán Sai chỉ số Tỷ lệ Hủy đơn Chi nhánh trong Báo cáo SuperAdmin Insight *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (hàm `branchInsightStats`, dòng 355)
- **Cơ chế hoạt động:** Tỷ lệ đơn hủy của chi nhánh cao nhất được tính: `round(($highestCancelledResult->cancelled_count / $totalOrders) * 100, 1)`, trong đó `$totalOrders` là tổng số đơn của toàn mạng lưới tất cả chi nhánh.
- **Hậu quả:** Một chi nhánh có 10 đơn hàng và cả 10 đơn đều bị hủy (tỷ lệ hủy thực tế là 100%), nhưng hệ thống lấy 10 chia cho 2000 đơn toàn hệ thống ra kết quả `0.5%`. Báo cáo bị sai lệch hoàn toàn, khiến SuperAdmin không phát hiện được chi nhánh đang có tỷ lệ hủy đơn bất thường.
- **Cách khắc phục:** Tính tỷ lệ hủy dựa trên tổng đơn hàng của chính chi nhánh đó.
  ```php
  $branchTotalOrders = Order::where('branch_id', $highestCancelledBranch->id)->count();
  $cancellationRate = $branchTotalOrders > 0 
      ? round(($highestCancelledResult->cancelled_count / $branchTotalOrders) * 100, 1) 
      : 0;
  ```

## 42. Thiếu Màn hình Quản lý & Lọc Nhật ký Hệ thống Audit Log dành cho SuperAdmin *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (dòng 96–98)
- **Cơ chế hoạt động:** Màn hình Super Admin Dashboard hiện tại chỉ hiển thị 8 nhật ký mới nhất bằng `SystemLog::latest()->limit(8)->get()`. Hệ thống không có trang danh sách Audit Log riêng (`/admin/system-logs`) hay bộ lọc theo hành vi, người thực hiện, địa chỉ IP hoặc khoảng thời gian.
- **Hậu quả:** Khi xảy ra sự cố bảo mật (như xóa sản phẩm, khóa tài khoản, đăng nhập bất thường), SuperAdmin không có công cụ tìm kiếm hay phân trang để tra cứu lại nhật ký hoạt động cũ.
- **Cách khắc phục:** Xây dựng màn hình và Controller quản lý `SystemLog` đầy đủ tính năng lọc và phân trang cho SuperAdmin.

## 43. Đánh giá Sức khỏe Hệ thống (System Health) chưa Đầy đủ & Dễ gây Cảnh báo Ảo *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (hàm `systemHealth`, dòng 517–535)
- **Cơ chế hoạt động:** Widget "Sức khỏe hệ thống" chỉ kiểm tra kết nối PDO Database và dung lượng ổ đĩa. Nó bỏ qua việc kiểm tra quyền ghi thư mục `storage/app/public`, trạng thái của Queue Worker và kết nối WebSocket (Reverb).
- **Hậu quả:** Khi thư mục `storage` bị mất quyền ghi khiến khách hàng không thể tải ảnh đính kèm chat/avatar, Dashboard vẫn báo "Hệ thống bình thường", gây hiểu nhầm cho SuperAdmin khi vận hành.
- **Cách khắc phục:** Bổ sung kiểm tra Writable Storage, Queue status và Reverb WebSocket connection vào widget `systemHealth()`.

## 44. Thiếu Tính năng Chuyển quyền Nhanh (Impersonation / Login As) hỗ trợ Kiểm tra Chi nhánh *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (toàn bộ controller)
- **Cơ chế hoạt động:** SuperAdmin không có tính năng "Đăng nhập tạm thời dưới danh nghĩa Admin chi nhánh" (Impersonation) để hỗ trợ kiểm tra giao diện và xử lý sự cố khi Admin chi nhánh báo lỗi.
- **Hậu quả:** SuperAdmin phải xin mật khẩu tài khoản của Admin chi nhánh hoặc thực hiện đổi mật khẩu thủ công trong DB, gây bất tiện và vi phạm quy trình bảo mật.
- **Cách khắc phục:** Bổ sung hàm `impersonate(User $user)` cho phép SuperAdmin chuyển sang phiên làm việc của Admin chi nhánh và bấm "Thoát impersonate" để quay lại SuperAdmin.

## 45. Trùng lặp Mã Chi nhánh Tự động gây Thất bại khi Tạo Admin Mới *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/SuperAdminController.php` (hàm `storeAdmin`, dòng 146)
- **Cơ chế hoạt động:** Trong `storeAdmin`, mã chi nhánh tự động được đặt: `'code' => "ADM{$admin->id}"`. Nếu trong CSDL đã tồn tại một chi nhánh có mã `"ADM12"` do người dùng tự nhập thủ công trước đó, câu lệnh `Branch::create` sẽ bị ném lỗi SQL Duplicate Entry (`Integrity constraint violation: 1062`).
- **Hậu quả:** Lệnh tạo Admin bị dừng đột ngột, rollback transaction và hiển thị thông báo lỗi chung chung "Có lỗi xảy ra khi tạo Admin. Vui lòng thử lại" khiến SuperAdmin không biết lý do tại sao.
- **Cách khắc phục:** Sinh mã chi nhánh duy nhất bằng `Str::upper(Str::random(6))` hoặc kiểm tra tồn tại mã trước khi tạo.

---

# CÁC LỖI & ĐỀ XUẤT CẢI THIỆN RIÊNG CHO LUỒNG USER & PROFILE (USER & PROFILE FLOW ISSUES & IMPROVEMENTS)

> **Ghi chú phân biệt:** Phần này tổng hợp riêng biệt tất cả các lỗi logic, sự cố mất dữ liệu lịch sử, lỗ hổng voucher ví người dùng, điểm thưởng và trải nghiệm cá nhân (User Profile / Account / Loyalty Flow).

## 46. Mất toàn bộ Lịch sử Đơn hàng & Báo cáo Doanh thu khi Khách xóa Tài khoản Cá nhân *(cần xác nhận runtime schema)* *(đã sửa)*
> **Cập nhật 31/07/2026:** Migration `2026_07_01_000001_add_guest_checkout_to_orders_table.php` đổi `orders.user_id` thành nullable và dùng `nullOnDelete()`. Vì chưa kết nối được MySQL, chưa thể khẳng định schema đang chạy đã áp dụng migration này.
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/ProfileController.php` (hàm `destroy`, dòng 187–203) & `database/migrations/2026_05_17_121741_create_orders_table.php` (dòng 28)
- **Cơ chế hoạt động:** Khi người dùng chọn "Xóa tài khoản" trong trang Profile cá nhân, controller gọi `$user->delete()`. Do bảng `orders` khai báo khóa ngoại `$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')`, tất cả các đơn hàng lịch sử (`orders`), chi tiết đơn hàng (`order_items`), topping và doanh thu liên quan đến tài khoản đó bị XÓA SẠCH VĨNH VIỄN khỏi Database.
- **Hậu quả:** Báo cáo doanh thu, sổ sách kế toán và thống kê kinh doanh của cửa hàng bị sụt giảm vô lý và mất dấu vết lịch sử mỗi khi có khách hàng xóa tài khoản cá nhân.
- **Cách khắc phục:** Đổi điều kiện khóa ngoại `user_id` trong `orders` thành `onDelete('set null')` hoặc triển khai Soft Delete cho `User` để bảo toàn lịch sử đơn hàng và báo cáo tài chính của cửa hàng.
  ```php
  $table->integer('user_id')->nullable()->change();
  $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
  ```

## 47. Lỗi Tên cột CSDL khiến Chức năng Đổi Voucher bằng Điểm thưởng bị Sập 500 Error *(đã sửa)*
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Client/LoyaltyPointController.php` (hàm `redeemVoucher`, dòng 86, 114, 116) & `database/migrations/2026_05_17_121831_create_user_vouchers_table.php`
- **Cơ chế hoạt động:** Trong controller, code truy vấn và khởi tạo `UserVoucher` bằng các tên cột không tồn tại trong DB: `voucher_id` (tên đúng trong DB là `coupon_id`), `used_at` (tên đúng là `is_used`), `received_at` (tên đúng là `redeemed_at`).
- **Hậu quả:** Khi khách hàng bấm "Đổi Voucher" bằng điểm tích lũy, hệ thống ném ra lỗi SQL Unknown Column Exception (500 Internal Server Error). Tính năng đổi điểm thưởng lấy voucher hoàn toàn không hoạt động.
- **Cách khắc phục:** Sửa đúng tên cột trong `UserVoucher::create()` và câu lệnh query theo migration `create_user_vouchers_table`.
  ```php
  UserVoucher::create([
      'user_id' => $user->id,
      'coupon_id' => $voucher->id,
      'is_used' => 0,
      'redeemed_at' => now(),
  ]);
  ```

## 48. Lỗ hổng Dùng vô hạn Voucher đã Đổi do Không Cập nhật Trạng thái `is_used` khi Đặt hàng *(đã sửa)*
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Client/CheckoutController.php` (toàn bộ controller) & `app/Models/UserVoucher.php`
- **Cơ chế hoạt động:** Hệ thống chưa bao giờ liên kết kiểm tra hoặc cập nhật bảng `user_vouchers` trong quá trình khách hàng thực hiện checkout. Cột `user_vouchers.is_used` luôn bằng `0`.
- **Hậu quả:** Khách hàng chỉ cần bỏ điểm ra đổi 1 mã voucher duy nhất trong ví, sau đó có thể áp dụng mã voucher đó để giảm giá cho vô số đơn hàng tiếp theo mà không bao giờ bị đánh dấu là "Đã sử dụng".
- **Cách khắc phục:** Kiểm tra quyền sở hữu trong `user_vouchers` và cập nhật `is_used = 1` khi đơn hàng checkout thành công.
  ```php
  if ($user) {
      UserVoucher::where('user_id', $user->id)
          ->where('coupon_id', $voucher->id)
          ->where('is_used', 0)
          ->limit(1)
          ->update(['is_used' => 1]);
  }
  ```

## 49. Lỗi Không Ghi Nhật ký Giao dịch Point Transaction khi Chuyển đổi Khách vô danh *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Auth/GuestConvertController.php` (hàm `awardLoyaltyPoints`, dòng 120–126)
- **Cơ chế hoạt động:** Khi khách hàng tạo tài khoản từ đơn hàng vô danh (Guest Checkout Convert), controller gọi `DB::table('loyalty_points')->insert(...)` để cộng điểm trực tiếp vào cột `total_points` mà không gọi `PointTransaction::record()`.
- **Hậu quả:** Khách hàng có điểm thưởng trong số dư tổng nhưng trang "Lịch sử tích điểm" (`/loyalty-points`) lại trống rỗng, không hiển thị dòng lịch sử ghi nhận số điểm vừa nhận từ đơn hàng đầu tiên.
- **Cách khắc phục:** Gọi `LoyaltyPoint::getOrCreateForUser($userId)->addPoints(...)` để ghi nhận giao dịch vào `point_transactions`.
  ```php
  LoyaltyPoint::getOrCreateForUser($userId)->addPoints(
      points: $points,
      type: 'earn',
      description: "Tích điểm từ đơn hàng đăng ký ban đầu #{$order->id}",
      referenceType: 'order',
      referenceId: $order->id
  );
  ```

## 50. Thiếu Màn hình Quản lý Sổ địa chỉ Nhận hàng (Address Book) trong Trang Hồ sơ Cá nhân *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/ProfileController.php` (toàn bộ controller) & `routes/web.php`
- **Cơ chế hoạt động:** Địa chỉ nhận hàng hiện tại chỉ được thêm hoặc cập nhật ngay trong bước thanh toán (Checkout). Trang Hồ sơ cá nhân (`/profile`) không có giao diện và route quản lý danh sách địa chỉ (Thêm mới, Sửa, Xóa, Chọn địa chỉ mặc định).
- **Hậu quả:** Người dùng không thể chuẩn bị sẵn danh sách địa chỉ giao hàng (Nhà riêng, Công ty) trước khi mua sắm, gây bất tiện cho trải nghiệm mua hàng nhanh.
- **Cách khắc phục:** Xây dựng màn hình `ProfileAddressController` hỗ trợ CRUD địa chỉ nhận hàng trong trang Profile.

## 51. Bỏ qua Xác nhận Email đối với Tài khoản Mới đăng ký gây Nguy cơ Giả mạo Email *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Auth/RegisteredUserController.php` & `routes/web.php`
- **Cơ chế hoạt động:** Mặc dù Model `User` implements `MustVerifyEmail`, route đăng ký tự động đăng nhập người dùng (`Auth::login($user)`) và chuyển hướng ngay về trang chủ mà không bắt buộc xác nhận Email qua liên kết kích hoạt.
- **Hậu quả:** Người dùng có thể nhập bừa một Email không thuộc sở hữu của mình để đăng ký tài khoản, tích điểm và đặt hàng, tiềm ẩn nguy cơ spam tài khoản rác và giả mạo email người khác.
- **Cách khắc phục:** Bật middleware `verified` cho các route nhạy cảm (Checkout, Tích điểm) hoặc gửi email xác thực bắt buộc sau khi đăng ký.

## 52. Thiếu Chức năng Khôi phục/Mở khóa Hàng loạt Tài khoản bị Khóa dành cho SuperAdmin *(đã sửa)*
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Admin/UserController.php` (hàm `toggleStatus`)
- **Cơ chế hoạt động:** Việc khóa/mở khóa tài khoản hiện chỉ thực hiện từng tài khoản một qua nút bấm lẻ. Hệ thống thiếu tính năng chọn hàng loạt (Bulk Action) và không cho phép lọc nhanh các tài khoản đang bị khóa (`status = locked`) trên bảng điều khiển của SuperAdmin.
- **Hậu quả:** Tốn thời gian xử lý khi SuperAdmin cần mở khóa hoặc khóa hàng loạt tài khoản nghi vấn cùng lúc.
- **Cách khắc phục:** Thêm tính năng Bulk Toggle Status cho danh sách người dùng trong `UserController`.

## 53. Mock Data Giá sản phẩm Cố định (35.000đ) trong API Guest Checkout gây Sai lệch Doanh thu *(đã sửa)*
- **Mức độ ảnh hưởng:** Critical
- **Vị trí:** `app/Http/Controllers/Api/GuestCheckoutController.php` (hàm `checkout`, dòng 46–48, 72)
- **Cơ chế hoạt động:** Trong API `GuestCheckoutController::checkout()`, giá trị tổng tiền đơn hàng và giá của từng món trong `order_items` được gán cứng (hardcode) bằng `35000` (`$totalAmount += $item['quantity'] * 35000;` và `'price' => 35000`). Hệ thống không thực hiện truy vấn giá thực tế của sản phẩm từ bảng `products` hay `product_sizes`.
- **Hậu quả:** Các đơn hàng đặt qua API Guest Checkout bị gán giá trị đơn hàng sai lệch so với giá niêm yết thực tế của sản phẩm (món 65.000đ hay 15.000đ đều bị tính thành 35.000đ), dẫn đến sai lệch thống kê kế toán và thất thoát tài chính.
- **Cách khắc phục:** Truy vấn giá thực tế từ CSDL bằng cách query `Product::findOrFail($item['product_id'])` và nạp thêm giá phụ thu Size / Toppings:
  ```php
  $product = Product::findOrFail($item['product_id']);
  $itemPrice = (int) $product->price;
  $totalAmount += $item['quantity'] * $itemPrice;
  ```

## 54. Trả về Token Giả (`dummy_auth_token_string`) khi Chuyển đổi Khách sang Thành viên qua API *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Api/GuestCheckoutController.php` (hàm `convertToMember`, dòng 180)
- **Cơ chế hoạt động:** Sau khi tạo tài khoản người dùng mới thành công trong `convertToMember()`, controller trả về chuỗi token giả lập `'access_token' => 'dummy_auth_token_string'` thay vì khởi tạo token xác thực API thực tế (Sanctum/Passport Token).
- **Hậu quả:** Ứng dụng di động hoặc SPA tích hợp qua API không thể dùng token này để xác thực cho các request tiếp theo (`Authorization: Bearer ...`), dẫn đến lỗi 401 Unauthorized ngay sau khi vừa đăng ký tài khoản.
- **Cách khắc phục:** Tạo Sanctum Personal Access Token thật:
  ```php
  $token = $user->createToken('auth_token')->plainTextToken;
  ```

## 55. Race Condition khi Đặt hàng Nhóm Đồng thời trong `GroupOrderController` *(đã được xử lý trong code hiện tại)* *(đã sửa)*
> **Cập nhật 31/07/2026:** Các luồng thêm/tăng item và checkout chính hiện dùng transaction cùng `lockForUpdate()`. Cần giữ test cạnh tranh để tránh hồi quy; không xem mô tả cũ bên dưới là lỗi đang xác nhận.
- **Mức độ ảnh hưởng:** Medium
- **Vị trí:** `app/Http/Controllers/Client/GroupOrderController.php` (hàm `addItem`, dòng 132–165)
- **Cơ chế hoạt động:** Khi nhiều thành viên trong phòng đặt hàng nhóm cùng bấm "Thêm món" một lúc, controller nạp danh sách và tính lại tổng tiền phòng nhóm mà không sử dụng `DB::transaction()` và khóa dòng `GroupOrder` bằng `lockForUpdate()`.
- **Hậu quả:** Xảy ra hiện tượng Race Condition làm tổng tiền của phòng đặt hàng nhóm (`total_amount`) bị tính toán sai lệch so với tổng giá trị thực tế của các sản phẩm có trong `group_order_items`.
- **Cách khắc phục:** Bọc logic thêm item vào giao dịch DB kèm khóa dòng phòng nhóm:
  ```php
  DB::transaction(function () use ($groupOrder, $request) {
      $lockedGroupOrder = GroupOrder::where('id', $groupOrder->id)->lockForUpdate()->first();
      // Execute item creation & recalculate total
  });
  ```

## 56. Thiếu Tự động Cập nhật Thời gian `delivered_at` khi Admin Đổi Trạng thái sang Đã Giao hàng *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Admin/OrderController.php` (hàm `updateStatus`, dòng 306–309) & `app/Models/Order.php`
- **Cơ chế hoạt động:** Trong `OrderController::updateStatus()`, cột `delivered_at` chỉ được gán `$order->delivered_at = now()` khi Admin bấm chuyển trạng thái thủ công trên giao diện Admin. Nếu đơn hàng được cập nhật trạng thái `delivered` qua API hoặc các luồng tự động khác không thông qua phương thức `updateStatus()`, `delivered_at` sẽ giữ giá trị `NULL`.
- **Hậu quả:** Artisan Command `AutoCompleteDeliveredOrders` lọc đơn hàng theo điều kiện `whereNotNull('delivered_at')->where('delivered_at', '<=', now()->subMinutes(30))` sẽ bỏ qua các đơn hàng này, khiến đơn hàng bị treo vĩnh viễn ở trạng thái `delivered` và khách không bao giờ được cộng điểm thưởng Loyalty tự động.
- **Cách khắc phục:** Sử dụng Eloquent Model Event `updating` trong `Order.php` để tự động gán `delivered_at` khi `status` chuyển thành `delivered`:
  ```php
  static::updating(function (Order $order) {
      if ($order->isDirty('status') && OrderStatus::normalize($order->status) === OrderStatus::DELIVERED && is_null($order->delivered_at)) {
          $order->delivered_at = now();
      }
  });
  ```

## 57. Gán cứng Chuỗi Trạng thái Cũ (`in_progress`) sau khi Thanh toán VNPay thành công trong `VnpayController` *(đã sửa)*
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Client/VnpayController.php` (hàm `return` dòng 136 và hàm `ipn` dòng 225)
- **Cơ chế hoạt động:** Khi giao dịch VNPay thành công, controller gán `$order->update(['status' => 'in_progress'])`. Giá trị chuỗi `'in_progress'` là tên trạng thái cũ thuộc phiên bản Laravel trước. Chuỗi trạng thái tiêu chuẩn hiện tại của hệ thống trong `OrderStatus.php` cho đơn mới thanh toán là `pending` hoặc `confirmed`.
- **Hậu quả:** Đơn hàng vừa thanh toán xong bị nhảy cóc trực tiếp sang trạng thái "Đang pha chế" (`preparing`), bỏ qua bước "Chờ xác nhận" (`pending`) và "Đã xác nhận" (`confirmed`). Ngoài ra, do giá trị lưu trong DB là chuỗi thô `'in_progress'`, một số hàm truy vấn lọc trạng thái trên Admin Dashboard không nhận diện được đơn hàng mới này nếu không qua hàm `normalize()`.
- **Cách khắc phục:** Đổi trạng thái cập nhật sau khi thanh toán VNPay thành công thành `OrderStatus::PENDING` hoặc `OrderStatus::CONFIRMED`:
  ```php
  $newStatus = $order->isGuest() ? $order->status : \App\Support\OrderStatus::PENDING;
  $order->update([
      'payment_status' => 'paid',
      'status' => $newStatus,
      'vnpay_transaction_id' => $request->input('vnp_TransactionNo'),
  ]);
  ```

## 58. Thiếu Khóa Giao dịch DB khi Cập nhật Số lượng Tồn kho Sản phẩm trong Order Processing *(đã được xử lý trong code hiện tại)* *(đã sửa)*
> **Cập nhật 31/07/2026:** `CheckoutController::prepareOrderItems()` hiện khóa product bằng `lockForUpdate()` trong transaction trước khi trừ stock. Mục này đã được xử lý trong code hiện tại.
- **Mức độ ảnh hưởng:** High
- **Vị trí:** `app/Http/Controllers/Client/CheckoutController.php` (hàm `process`, dòng 290–330) & `app/Models/Product.php`
- **Cơ chế hoạt động:** Trong quá trình xử lý đơn hàng (`CheckoutController::process`), hệ thống tạo `order_items` nhưng không thực hiện kiểm tra tồn kho (`stock`) và trừ số lượng sản phẩm bằng câu lệnh `decrement('stock')` có khóa giao dịch `lockForUpdate()`.
- **Hậu quả:** Khi một sản phẩm chỉ còn 1 món trong kho nhưng có 2 khách hàng thực hiện checkout cùng một lúc, cả 2 đơn hàng đều được tạo thành công, dẫn đến tình trạng bán vượt quá số lượng tồn kho thực tế (Overselling / Negative Stock).
- **Cách khắc phục:** Kiểm tra số lượng tồn kho và thực hiện trừ kho có khóa giao dịch trong DB Transaction:
  ```php
  $product = Product::where('id', $productId)->lockForUpdate()->first();
  if ($product && $product->stock < $quantity) {
      throw new \Exception("Sản phẩm {$product->name} đã hết hàng hoặc không đủ số lượng.");
  }
  $product?->decrement('stock', $quantity);
  ```

## 59. Merge PR #27 - Staff panel and branch isolation (reviewed 31/07/2026)

- Added role `5` (`staff`), staff middleware, staff dashboard, order/group-order/chat controllers and views.
- Added migrations for status audit fields (`status_changed_at`, `status_changed_by`) and multiple users per branch.
- Resolved merge conflicts in `SuperAdminController.php` and `routes/web.php`, preserving admin password reset, impersonation and staff-management routes.
- Fixed branch isolation: staff and branch admins without an assigned branch now see no branch data and cannot manage staff/orders/chat outside their branch.
- Staff order cancellation now runs in a transaction and restores stock, voucher usage and loyalty points when applicable.
- Group-order status changes now follow the allowed transition map and write audit fields.
- Removed the public `public/fix_roles.php` database mutation endpoint; role creation is handled by migration/seeder.

## 60. Verification after merge

- PHPUnit: `125 passed`, `519 assertions`.
- PHP syntax: `285` files passed.
- Vite production build: passed.
- Route list: `206` routes loaded successfully.
- Migration status: the two new Staff migrations are currently `Pending` in the local database; run `php artisan migrate` before using the Staff panel in that environment.

## 61. SuperAdmin chat redirected to home

- Cause: `/admin/chat` uses `CskhMiddleware`, but the middleware previously accepted only CSKH (`role_id = 4`) and rejected Admin/SuperAdmin.
- Fix: Admin and SuperAdmin are now accepted by `CskhMiddleware`; branch/data authorization remains enforced inside `AdminChatController`.

