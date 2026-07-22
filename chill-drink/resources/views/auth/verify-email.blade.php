@extends('layouts.client')

@section('title', 'Xác Thực Email')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-3">Xác thực email</h1>
                        <p class="text-secondary">Vui lòng kiểm tra email và bấm vào liên kết xác thực tài khoản.</p>

                        @if (session('status') == 'verification-link-sent')
                            <div class="alert alert-success">Liên kết xác thực mới đã được gửi tới email của bạn.</div>
                        @endif

                        <div class="d-flex flex-wrap gap-3">
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Gửi lại email xác thực</button>
                            </form>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary">Đăng xuất</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
