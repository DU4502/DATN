@extends('layouts.admin')

@section('page-title', 'Thêm sản phẩm')

@section('content')
<form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @include('admin.products._form', ['submitLabel' => 'Thêm sản phẩm'])
</form>
@endsection