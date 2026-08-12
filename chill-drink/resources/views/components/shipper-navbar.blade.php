<nav class="navbar navbar-expand-lg bg-white shadow-sm">

<div class="container-fluid">

<span class="navbar-brand">

Dashboard Shipper

</span>

<div class="ms-auto">

<img src="{{ Auth::user()->avatar ?? 'https://i.pravatar.cc/100' }}"
width="45"
class="rounded-circle">

<span class="ms-2">

{{ Auth::user()->name }}

</span>

</div>

</div>

</nav>