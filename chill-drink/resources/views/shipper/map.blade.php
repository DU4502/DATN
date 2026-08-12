@extends('layouts.shipper')

@section('title', 'Bản đồ giao hàng')

@section('content')

<div class="container-fluid">

    {{-- ================= HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                Bản đồ giao hàng
            </h4>

            <p class="text-muted mb-0">
                Vị trí khách hàng và vị trí hiện tại của bạn
            </p>
        </div>

        <a href="{{ route('shipper.orders.show', $order->id) }}"
           class="btn btn-outline-secondary">

            <i class="bi bi-arrow-left me-1"></i>
            Quay lại đơn hàng

        </a>

    </div>


    {{-- ================= THÔNG TIN ĐƠN ================= --}}
    <div class="card shadow-sm border-0 mb-3">

        <div class="card-body">

            <div class="row g-3">

                {{-- Mã đơn --}}
                <div class="col-md-3">

                    <small class="text-muted">
                        Mã đơn
                    </small>

                    <div class="fw-bold text-primary">

                        #{{ $order->order_code ?? $order->id }}

                    </div>

                </div>


                {{-- Khách hàng --}}
                <div class="col-md-3">

                    <small class="text-muted">
                        Khách hàng
                    </small>

                    <div class="fw-bold">

                        {{ $order->customer_name ?? 'Khách hàng' }}

                    </div>

                </div>


                {{-- Số điện thoại --}}
                <div class="col-md-3">

                    <small class="text-muted">
                        Số điện thoại
                    </small>

                    @if(!empty($order->phone))

                        <div>

                            <a href="tel:{{ $order->phone }}"
                               class="text-decoration-none">

                                <i class="bi bi-telephone text-success me-1"></i>

                                {{ $order->phone }}

                            </a>

                        </div>

                    @else

                        <div class="text-muted">
                            Chưa có
                        </div>

                    @endif

                </div>


                {{-- Trạng thái --}}
                <div class="col-md-3">

                    <small class="text-muted">
                        Trạng thái
                    </small>

                    <div>

                        <span class="badge bg-warning text-dark">

                            <i class="bi bi-truck me-1"></i>

                            Đang giao

                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ================= ĐỊA CHỈ KHÁCH ================= --}}
    <div class="alert alert-light border shadow-sm">

        <div class="d-flex align-items-start">

            <i class="bi bi-geo-alt-fill text-danger fs-4 me-2"></i>

            <div>

                <strong>
                    Địa chỉ giao hàng
                </strong>

                <div class="text-muted">

                    {{ $order->address ?? 'Chưa có địa chỉ' }}

                </div>

            </div>

        </div>

    </div>


    {{-- ================= BẢN ĐỒ ================= --}}
    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0 fw-bold">

                    <i class="bi bi-map me-2 text-primary"></i>

                    Bản đồ giao hàng

                </h5>

                <span class="badge bg-success">

                    GPS

                </span>

            </div>

        </div>


        <div class="card-body p-0">

            <div id="map"
                 style="
                    width: 100%;
                    height: 550px;
                    border-radius: 0 0 8px 8px;
                 ">
            </div>

        </div>

    </div>


    {{-- ================= CHÚ THÍCH ================= --}}
    <div class="card shadow-sm border-0 mt-3">

        <div class="card-body">

            <div class="row">

                <div class="col-md-6">

                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>

                    <strong>
                        Vị trí khách hàng
                    </strong>

                </div>

                <div class="col-md-6">

                    <i class="bi bi-bicycle text-primary me-2"></i>

                    <strong>
                        Vị trí của bạn
                    </strong>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- LEAFLET CSS --}}
{{-- ========================================================= --}}

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>


{{-- ========================================================= --}}
{{-- LEAFLET JS --}}
{{-- ========================================================= --}}

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>


<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | TỌA ĐỘ KHÁCH HÀNG
    |--------------------------------------------------------------------------
    */

    const customerLat =
        Number(@json($order->customer_latitude));

    const customerLng =
        Number(@json($order->customer_longitude));


    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA TỌA ĐỘ
    |--------------------------------------------------------------------------
    */

    if (
        !customerLat ||
        !customerLng ||
        isNaN(customerLat) ||
        isNaN(customerLng)
    ) {

        document.getElementById('map').innerHTML = `
            <div class="d-flex
                        justify-content-center
                        align-items-center
                        h-100">

                <div class="text-center text-muted">

                    <i class="bi bi-geo-alt-fill
                              text-danger
                              fs-1">
                    </i>

                    <h5 class="mt-3">
                        Chưa có vị trí khách hàng
                    </h5>

                    <p class="mb-0">
                        Đơn hàng chưa có tọa độ
                        latitude / longitude.
                    </p>

                </div>

            </div>
        `;

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | KHỞI TẠO MAP
    |--------------------------------------------------------------------------
    */

    const map = L.map('map');


    /*
    |--------------------------------------------------------------------------
    | OPEN STREET MAP
    |--------------------------------------------------------------------------
    */

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,

            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    /*
    |--------------------------------------------------------------------------
    | MARKER KHÁCH HÀNG
    |--------------------------------------------------------------------------
    */

    const customerMarker = L.marker([
        customerLat,
        customerLng
    ]).addTo(map);


    /*
    |--------------------------------------------------------------------------
    | POPUP KHÁCH HÀNG
    |--------------------------------------------------------------------------
    */

    customerMarker.bindPopup(`

        <div style="min-width: 220px">

            <h6 class="fw-bold text-danger">

                <i class="bi bi-person-fill me-1"></i>

                Vị trí khách hàng

            </h6>

            <hr class="my-2">

            <div class="mb-1">

                <strong>
                    {{ $order->customer_name ?? 'Khách hàng' }}
                </strong>

            </div>

            <div class="text-muted mb-2">

                <i class="bi bi-geo-alt me-1"></i>

                {{ $order->address ?? 'Chưa có địa chỉ' }}

            </div>

            @if(!empty($order->phone))

                <a href="tel:{{ $order->phone }}"
                   class="btn btn-sm btn-success">

                    <i class="bi bi-telephone me-1"></i>

                    Gọi khách

                </a>

            @endif

        </div>

    `);


    /*
    |--------------------------------------------------------------------------
    | ĐƯA MAP ĐẾN VỊ TRÍ KHÁCH
    |--------------------------------------------------------------------------
    */

    map.setView([
        customerLat,
        customerLng
    ], 16);


    /*
    |--------------------------------------------------------------------------
    | MỞ POPUP
    |--------------------------------------------------------------------------
    */

    customerMarker.openPopup();


    /*
    |--------------------------------------------------------------------------
    | LẤY GPS SHIPPER
    |--------------------------------------------------------------------------
    */

    if (navigator.geolocation) {

        navigator.geolocation.watchPosition(

            function (position) {

                const shipperLat =
                    position.coords.latitude;

                const shipperLng =
                    position.coords.longitude;


                /*
                |--------------------------------------------------------------------------
                | TẠO / CẬP NHẬT MARKER SHIPPER
                |--------------------------------------------------------------------------
                */

                if (window.shipperMarker) {

                    window.shipperMarker.setLatLng([
                        shipperLat,
                        shipperLng
                    ]);

                } else {

                    window.shipperMarker =
                        L.marker([
                            shipperLat,
                            shipperLng
                        ]).addTo(map);

                    window.shipperMarker.bindPopup(`

                        <div>

                            <strong>
                                <i class="bi bi-bicycle
                                          text-primary me-1">
                                </i>

                                Vị trí của bạn
                            </strong>

                        </div>

                    `);

                }


                /*
                |--------------------------------------------------------------------------
                | GỬI VỊ TRÍ SHIPPER VỀ SERVER
                |--------------------------------------------------------------------------
                */

                fetch(
                    "{{ route('shipper.location') }}",
                    {
                        method: 'POST',

                        headers: {

                            'Content-Type':
                                'application/json',

                            'X-CSRF-TOKEN':
                                '{{ csrf_token() }}',

                            'Accept':
                                'application/json'

                        },

                        body: JSON.stringify({

                            latitude:
                                shipperLat,

                            longitude:
                                shipperLng

                        })

                    }
                )
                .then(response => response.json())
                .then(data => {

                    console.log(
                        'Đã cập nhật vị trí:',
                        data
                    );

                })
                .catch(error => {

                    console.error(
                        'Lỗi cập nhật vị trí:',
                        error
                    );

                });

            },

            function (error) {

                console.error(
                    'Không lấy được GPS:',
                    error
                );

            },

            {

                enableHighAccuracy: true,

                maximumAge: 5000,

                timeout: 10000

            }

        );

    } else {

        alert(
            'Thiết bị của bạn không hỗ trợ GPS.'
        );

    }

});

</script>

@endsection