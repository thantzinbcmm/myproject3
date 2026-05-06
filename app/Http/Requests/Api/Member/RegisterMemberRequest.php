// app/Http/Requests/Api/Member/RegisterMemberRequest.php
<?php

namespace App\Http\Requests\Api\Member;

use Illuminate\Foundation\Http\FormRequest;

class RegisterMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name_kana' => ['required', 'string', 'max:50'],
            'first_name_kana' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:members,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['required', 'string', 'regex:/^[\d\-]{10,15}$/'],
            'nationality' => ['nullable', 'string', 'size:2'],
            'preferred_language' => ['nullable', 'string', 'in:ja,en,zh-CN,zh-TW,ko,my'],
        ];
    }
}