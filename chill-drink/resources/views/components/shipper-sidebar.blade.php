<div class="bg-dark text-white vh-100" style="width:260px">

    <div class="text-center py-4">

        <h3>

            <i class="fa-solid fa-motorcycle"></i>

            SHIPPER

        </h3>

    </div>

    <ul class="nav flex-column">

        <li class="nav-item">

            <a href="{{ route('shipper.dashboard') }}" class="nav-link text-white">

                <i class="fa fa-house me-2"></i>

                Dashboard

            </a>

        </li>

        <li>

            <a href="{{ route('shipper.orders') }}" class="nav-link text-white">

                <i class="fa fa-box me-2"></i>

                Đơn hàng

            </a>

        </li>

        <li>

            <a href="{{ route('shipper.map') }}" class="nav-link text-white">

                <i class="fa fa-location-dot me-2"></i>

                Bản đồ

            </a>

        </li>

        <li>

            <a href="{{ route('shipper.history') }}" class="nav-link text-white">

                <i class="fa fa-clock me-2"></i>

                Lịch sử

            </a>

        </li>

        <li>

            <a href="{{ route('shipper.profile') }}" class="nav-link text-white">

                <i class="fa fa-user me-2"></i>

                Cá nhân

            </a>

        </li>

    </ul>

</div>