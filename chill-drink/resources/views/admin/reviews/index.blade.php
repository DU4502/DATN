@extends('layouts.admin')

@section('page-title', 'Bình luận & đánh giá')
@section('search-placeholder', 'Tìm sản phẩm, khách hàng, nội dung...')

@section('content')
<section class="mb-4">
    <h2 class="h2 fw-bold mb-1">Bình luận & đánh giá</h2>
    <p class="text-secondary mb-0">Theo dõi phản hồi của khách hàng theo từng sản phẩm.</p>
</section>

<section class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="admin-card admin-metric">
            <span class="admin-icon-dot"><i class="bi bi-chat-square-text"></i></span>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tổng bình luận</p>
                <p class="admin-value mb-0">{{ $totalReviews }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-card admin-metric">
            <span class="admin-icon-dot"><i class="bi bi-star-fill"></i></span>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Điểm trung bình</p>
                <p class="admin-value mb-0">{{ number_format($averageRating, 1, ',', '.') }}/5</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-card admin-metric">
            <span class="admin-icon-dot"><i class="bi bi-calendar-week"></i></span>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tuần này</p>
                <p class="admin-value mb-0">{{ $weekReviews }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-card admin-metric">
            <span class="admin-icon-dot"><i class="bi bi-calendar3"></i></span>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tháng này</p>
                <p class="admin-value mb-0">{{ $monthReviews }}</p>
            </div>
        </div>
    </div>
</section>

<section class="admin-card">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 p-4 border-bottom">
        <div>
            <h3 class="h4 fw-bold mb-1">Danh sách phản hồi</h3>
            <p class="text-secondary mb-0">Ảnh sản phẩm được thu nhỏ để bảng dễ đọc và cân đối.</p>
        </div>
        <div class="admin-review-filters">
            <span class="admin-filter-pill active">Tất cả</span>
            <span class="admin-filter-pill">5 sao</span>
            <span class="admin-filter-pill">Cần xem lại</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Khách hàng</th>
                    <th class="text-center">Đánh giá</th>
                    <th>Bình luận</th>
                    <th class="text-end">Ngày gửi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="admin-review-thumb">
                                    @if($review->product)
                                        <x-product-image
                                            :sku="$review->product->sku ?? null"
                                            :name="$review->product->name"
                                            :alt="$review->product->name"
                                            :category="$review->product->category?->name"
                                            class="w-100 h-100"
                                            style="object-fit: contain;"
                                            :width="180"
                                        />
                                    @else
                                        <i class="bi bi-cup-hot"></i>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="fw-bold text-truncate">{{ $review->product->name ?? 'Sản phẩm đã xóa' }}</div>
                                    <small class="text-secondary">{{ $review->product->category->name ?? 'Chưa phân loại' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold d-block">{{ $review->user->name ?? 'Khách hàng' }}</span>
                            <small class="text-secondary">{{ $review->user->email ?? '' }}</small>
                        </td>
                        <td class="text-center">
                            <span class="admin-rating">
                                <i class="bi bi-star-fill"></i>
                                {{ $review->rating }}/5
                            </span>
                        </td>
                        <td class="text-secondary" style="max-width: 360px;">{{ $review->comment ?: 'Không có nội dung.' }}</td>
                        <td class="text-end text-secondary">{{ optional($review->created_at)->format('d/m/Y H:i') ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-5">
                            <span class="admin-icon-dot mx-auto mb-3" style="width: 58px; height: 58px;"><i class="bi bi-chat-square-heart"></i></span>
                            <div class="fw-bold text-dark mb-1">
                                {{ $hasReviewTable ? 'Chưa có bình luận/đánh giá' : 'Chưa có bảng dữ liệu đánh giá' }}
                            </div>
                            <div>
                                {{ $hasReviewTable ? 'Phản hồi mới của khách hàng sẽ xuất hiện tại đây.' : 'Giao diện đã sẵn sàng; khi backend có bảng reviews, dữ liệu sẽ tự hiển thị.' }}
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-top" style="background: var(--admin-soft-2);">
        <p class="text-secondary mb-0">Đang hiển thị {{ $reviews->count() }} phản hồi</p>
        {{ $reviews->links() }}
    </div>
</section>
@endsection
