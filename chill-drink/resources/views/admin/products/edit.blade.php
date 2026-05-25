@extends('layouts.admin')

@section('page-title', 'Sửa sản phẩm')

@section('content')
<form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
    @method('PUT')
    @include('admin.products._form', ['submitLabel' => 'Cập nhật sản phẩm'])
</form>
@endsection
