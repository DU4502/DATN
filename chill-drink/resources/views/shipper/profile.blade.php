@extends('layouts.shipper')

@section('title', 'Hồ sơ Shipper')

@section('content')

<div class="container-fluid">

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-user me-2"></i>
                Hồ sơ Shipper
            </h5>
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('shipper.profile.update') }}">

                @csrf
                @method('PUT')

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Họ và tên
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name', $shipper->name) }}"
                               required>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Email
                        </label>

                        <input type="email"
                               class="form-control"
                               value="{{ $shipper->email }}"
                               disabled>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Số điện thoại
                        </label>

                        <input type="text"
                               name="phone"
                               class="form-control"
                               value="{{ old('phone', $shipper->phone) }}">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Mã Shipper
                        </label>

                        <input type="text"
                               class="form-control"
                               value="{{ $shipperInfo->code ?? '---' }}"
                               disabled>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Loại phương tiện
                        </label>

                        <select name="vehicle_type"
                                class="form-select">

                            <option value="bike"
                                @selected(($shipperInfo->vehicle_type ?? '') === 'bike')>
                                Xe máy
                            </option>

                            <option value="car"
                                @selected(($shipperInfo->vehicle_type ?? '') === 'car')>
                                Ô tô
                            </option>

                        </select>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Biển số xe
                        </label>

                        <input type="text"
                               name="license_plate"
                               class="form-control"
                               value="{{ old('license_plate', $shipperInfo->license_plate ?? '') }}">

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Địa chỉ
                        </label>

                        <textarea name="address"
                                  class="form-control"
                                  rows="3">{{ old('address', $shipper->address) }}</textarea>

                    </div>

                    <div class="col-12">

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="fas fa-save me-1"></i>
                            Lưu thay đổi

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection