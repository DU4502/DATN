<div class="bg-dark text-white vh-100 d-flex flex-column shadow"
     style="width:260px;">

    {{-- ================= LOGO ================= --}}
    <div class="text-center py-4 border-bottom border-secondary">

        <h3 class="fw-bold mb-0">

            <i class="fa-solid fa-motorcycle text-warning me-2"></i>

            SHIPPER

        </h3>

        <small class="text-secondary">
            Quản lý giao hàng
        </small>

    </div>


    {{-- ================= MENU ================= --}}
    <ul class="nav flex-column px-2 mt-3">

        {{-- Dashboard --}}
        <li class="nav-item mb-1">

            <a href="{{ route('shipper.dashboard') }}"
               class="nav-link text-white rounded px-3 py-3">

                <i class="fa-solid fa-house me-3"></i>

                Dashboard

            </a>

        </li>


        {{-- Đơn hàng --}}
        <li class="nav-item mb-1">

            <a href="{{ route('shipper.orders') }}"
               class="nav-link text-white rounded px-3 py-3">

                <i class="fa-solid fa-box me-3"></i>

                Đơn hàng

            </a>

        </li>


        {{-- Bản đồ --}}
        <li class="nav-item mb-1">

            <a href="{{ route('shipper.map') }}"
               class="nav-link text-white rounded px-3 py-3">

                <i class="fa-solid fa-location-dot me-3"></i>

                Bản đồ

            </a>

        </li>


        {{-- Lịch sử --}}
        <li class="nav-item mb-1">

            <a href="{{ route('shipper.history') }}"
               class="nav-link text-white rounded px-3 py-3">

                <i class="fa-solid fa-clock-rotate-left me-3"></i>

                Lịch sử

            </a>

        </li>


        {{-- Cá nhân --}}
        <li class="nav-item mb-1">

            <a href="{{ route('shipper.profile') }}"
               class="nav-link text-white rounded px-3 py-3">

                <i class="fa-solid fa-user me-3"></i>

                Cá nhân

            </a>

        </li>

    </ul>


    {{-- ================= ĐĂNG XUẤT ================= --}}
    <div class="mt-auto p-3 border-top border-secondary">

        <form action="{{ route('logout') }}"
              method="POST">

            @csrf

            <button type="submit"
                    class="btn btn-danger w-100 py-2 rounded"
                    onclick="return confirm('Bạn có chắc muốn đăng xuất không?')">

                <i class="fa-solid fa-right-from-bracket me-2"></i>

                Đăng xuất

            </button>

        </form>

    </div>

</div>


{{-- ================= STYLE ================= --}}
<style>

    .nav-link {
        transition: all 0.2s ease;
    }

    .nav-link:hover {
        background-color: #343a40;
        color: #ffc107 !important;
        padding-left: 20px !important;
    }

    .nav-link i {
        width: 20px;
        text-align: center;
    }

</style>