@extends('layouts.admin')

@section('page-title', 'Sửa voucher')
@section('hide-topbar-search', true)

@section('content')
<section class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-primary" title="Quay lại">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <p class="admin-kicker mb-1">Voucher</p>
        <h2 class="h2 fw-bold mb-0">Sửa mã {{ $voucher->code }}</h2>
    </div>
</section>

<section class="admin-card p-4 p-lg-5">
    <form action="{{ route('admin.vouchers.update', $voucher) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.vouchers._form')
    </form>
</section>
@endsection
