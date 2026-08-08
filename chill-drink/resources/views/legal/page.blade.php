@extends('layouts.client')

@php
    $pages = [
        'terms' => ['Điều khoản sử dụng', 'Quy định sử dụng dịch vụ Chill Drink', ['Khách hàng cung cấp thông tin chính xác khi đặt hàng.', 'Đơn hàng được xác nhận theo trạng thái hiển thị trong hệ thống.', 'Chill Drink có quyền từ chối đơn có dấu hiệu gian lận hoặc vi phạm quy định.']],
        'returns' => ['Chính sách đổi trả', 'Hỗ trợ khi đơn hàng có vấn đề', ['Khách hàng có thể gửi báo lỗi thiếu món, sai món hoặc chất lượng đồ uống từ lịch sử đơn hàng.', 'Yêu cầu cần mô tả rõ ràng để nhân viên kiểm tra.', 'Phương án đổi món hoặc hoàn tiền được quyết định sau khi đối chiếu đơn hàng.']],
        'privacy' => ['Chính sách quyền riêng tư', 'Cách Chill Drink sử dụng dữ liệu cá nhân', ['Hệ thống chỉ lưu thông tin cần thiết để phục vụ đơn hàng và tài khoản.', 'Khách hàng có thể tải dữ liệu cá nhân hoặc yêu cầu xóa tài khoản.', 'Thông tin không được chia sẻ cho bên thứ ba ngoài mục đích xử lý đơn hàng.']],
    ];
    [$title, $subtitle, $items] = $pages[$page] ?? $pages['terms'];
@endphp
@section('title', $title)
@section('content')
<section class="py-5"><div class="container" style="max-width:800px"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-md-5"><p class="text-primary fw-semibold mb-1">Chill Drink</p><h1 class="h2 fw-bold">{{ $title }}</h1><p class="text-secondary">{{ $subtitle }}</p><ol class="mt-4 mb-0">@foreach($items as $item)<li class="mb-3">{{ $item }}</li>@endforeach</ol></div></div></div></section>
@endsection
