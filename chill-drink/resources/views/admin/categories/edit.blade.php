@extends('layouts.admin')

@section('content')
<div class="container mt-4">
    <h1>Sửa danh mục</h1>
    <hr>

   <form action="{{ route('admin.categories.update', $category->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label font-weight-bold">Tên danh mục</label>
            <input type="text"
                   id="name"
                   name="name"
                   class="form-control"
                   value="{{ old('name', $category->name) }}"
                   required>
        </div>

        <div class="mb-3">
            <label for="slug" class="form-label font-weight-bold">Slug</label>
            <input type="text"
                   id="slug"
                   name="slug"
                   class="form-control"
                   value="{{ old('slug', $category->slug) }}"
                   required>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label font-weight-bold">Trạng thái</label>
            <select name="status" id="status" class="form-select form-control">
                <option value="1" {{ old('status', $category->status) == 1 ? 'selected' : '' }}>Hiển thị (Ẩn/Hiện)</option>
                <option value="0" {{ old('status', $category->status) == 0 ? 'selected' : '' }}>Khóa / Ẩn</option>
            </select>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                Cập nhật
            </button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                Quay lại
            </button>
        </div>
    </form>
</div>

{{-- Script tự động tạo Slug từ Tên khi chỉnh sửa --}}
<script>
    document.getElementById('name').addEventListener('keyup', function() {
        let title = this.value;
        let slug = title.toLowerCase();

        // Chuyển ký tự tiếng Việt có dấu thành không dấu
        slug = slug.replace(/á|à|ả|ạ|ã|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/gi, 'a');
        slug = slug.replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/gi, 'e');
        slug = slug.replace(/i|í|ì|ỉ|ĩ|ị/gi, 'i');
        slug = slug.replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/gi, 'o');
        slug = slug.replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/gi, 'u');
        slug = slug.replace(/ý|ỳ|ỷ|ỹ|ỵ/gi, 'y');
        slug = slug.replace(/đ/gi, 'd');

        // Xóa ký tự đặc biệt, thay khoảng trắng bằng dấu gạch ngang
        slug = slug.replace(/\s+/g, '-');
        slug = slug.replace(/[^a-z0-9\-]/g, '');
        slug = slug.replace(/\-{2,}/g, '-');
        slug = slug.trim('-');

        document.getElementById('slug').value = slug;
    });
</script>
@endsection
