@extends('layouts.client')

@section('title', 'Quên Mật Khẩu')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-3">Quên mật khẩu</h1>
                        <p class="text-secondary">Nhập email của bạn, hệ thống sẽ gửi liên kết đặt lại mật khẩu.</p>

                        @if(session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf
                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Gửi liên kết đặt lại</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
