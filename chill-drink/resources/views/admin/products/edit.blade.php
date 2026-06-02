@extends('layouts.admin')

@section('page-title', 'Sửa sản phẩm')

@section('content')
<form action="{{ route('admin.products.update', ['product' => $product->id, 'page' => request('page')]) }}" method="POST" enctype="multipart/form-data">
    @method('PUT')
    <input type="hidden" name="return_page" value="{{ request('page') }}">
    @include('admin.products._form', ['submitLabel' => 'Cập nhật sản phẩm'])
</form>
@endsection
