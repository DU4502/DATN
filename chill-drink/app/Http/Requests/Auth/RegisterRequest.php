<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge([
                'email' => Str::lower($this->input('email')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{9,30}$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'area' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
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
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.string' => 'Họ tên không hợp lệ.',
            'name.max' => 'Họ tên không được vượt quá :max ký tự.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.string' => 'Số điện thoại không hợp lệ.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.string' => 'Email không hợp lệ.',
            'email.lowercase' => 'Email phải viết thường.',
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá :max ký tự.',
            'email.unique' => 'Email này đã được đăng ký.',
            'area.string' => 'Khu vực không hợp lệ.',
            'area.max' => 'Khu vực không được vượt quá :max ký tự.',
            'address.string' => 'Địa chỉ không hợp lệ.',
            'address.max' => 'Địa chỉ không được vượt quá :max ký tự.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string' => 'Mật khẩu không hợp lệ.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
        ];
    }
}
