@extends('layouts.client')

@section('title', 'Xác Nhận Mật Khẩu')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-3">Xác nhận mật khẩu</h1>
                        <p class="text-secondary">Vui lòng xác nhận mật khẩu trước khi tiếp tục.</p>

                        <form method="POST" action="{{ route('password.confirm') }}">
                            @csrf
                            <div class="mb-4">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Xác nhận</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
