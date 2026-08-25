<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;

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
        $contact = trim((string) $this->input('contact', ''));
        $contactType = trim((string) $this->input('contact_type', ''));

        if ($contact === '') {
            $email = trim((string) $this->input('email', ''));
            $phone = trim((string) $this->input('phone', ''));

            if ($email !== '') {
                $contact = $email;
                $contactType = 'email';
            } elseif ($phone !== '') {
                $contact = $phone;
                $contactType = 'phone';
            }
        }

        if ($contact !== '' && $contactType === '') {
            $contactType = filter_var($contact, FILTER_VALIDATE_EMAIL)
                ? 'email'
                : (preg_match('/^[0-9+\-\s().]{9,30}$/', $contact) ? 'phone' : 'invalid');
        }

        $this->merge([
            'contact' => $contact !== '' ? $contact : null,
            'contact_type' => $contactType !== '' ? $contactType : null,
            'email' => $this->filled('email') ? Str::lower($this->input('email')) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
        ]);

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
            'contact' => ['required', 'string', 'max:255'],
            'contact_type' => ['required', Rule::in(['email', 'phone'])],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{9,30}$/'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'email_verification_code' => ['nullable', 'digits:6'],
            'firebase_uid' => ['nullable', 'string', 'max:128'],
            'firebase_id_token' => ['nullable', 'string'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
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
            'contact.required' => 'Vui lòng nhập Gmail hoặc số điện thoại.',
            'contact_type.required' => 'Vui lòng nhập Gmail hoặc số điện thoại hợp lệ.',
            'contact_type.in' => 'Vui lòng nhập Gmail hoặc số điện thoại hợp lệ.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.string' => 'Số điện thoại không hợp lệ.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.string' => 'Email không hợp lệ.',
            'email.lowercase' => 'Email phải viết thường.',
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá :max ký tự.',
            'email.unique' => 'Email đã được sử dụng.',
            'email_verification_code.required' => 'Vui lòng nhập mã xác minh.',
            'email_verification_code.digits' => 'Mã xác minh phải gồm 6 chữ số.',
            'firebase_uid.max' => 'Firebase UID không được vượt quá :max ký tự.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string' => 'Mật khẩu không hợp lệ.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'terms.accepted' => 'Vui lòng đồng ý với điều khoản dịch vụ và chính sách bảo mật.',
        ];
    }
}
