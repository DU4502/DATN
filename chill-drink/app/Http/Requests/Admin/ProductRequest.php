<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('name') || $this->filled('slug')) {
            $this->merge([
                'slug' => Str::slug($this->input('slug') ?: $this->input('name')),
            ]);
        }

        $this->merge([
            'status' => $this->boolean('status'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product instanceof Product
            ? $product->id
            : (is_numeric($product)
                ? (int) $product
                : Product::query()->where('slug', (string) $product)->value('id'));
        $imageRules = $this->hasFile('image')
            ? ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048']
            : ['nullable', 'string', 'url', 'max:2048'];

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'image' => $imageRules,
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'description' => ['nullable', 'string', 'max:5000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục không hợp lệ.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.max' => 'Tên sản phẩm không được vượt quá :max ký tự.',
            'slug.required' => 'Vui lòng nhập đường dẫn sản phẩm.',
            'slug.unique' => 'Đường dẫn sản phẩm đã tồn tại.',
            'image.url' => 'Ảnh sản phẩm phải là một URL hợp lệ.',
            'image.image' => 'Tệp tải lên phải là hình ảnh.',
            'image.mimes' => 'Ảnh sản phẩm chỉ chấp nhận định dạng: jpeg, jpg, png, webp.',
            'image.max' => 'Ảnh sản phẩm không được vượt quá 2MB hoặc URL quá dài.',
            'price.required' => 'Vui lòng nhập giá bán.',
            'price.numeric' => 'Giá bán phải là số.',
            'price.min' => 'Giá bán không được âm.',
            'stock.required' => 'Vui lòng nhập tồn kho.',
            'stock.integer' => 'Tồn kho phải là số nguyên.',
            'stock.min' => 'Tồn kho không được âm.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',
        ];
    }
}
