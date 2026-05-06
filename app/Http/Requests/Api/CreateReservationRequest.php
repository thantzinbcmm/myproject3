// app/Http/Requests/Api/CreateReservationRequest.php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'uuid', 'exists:facilities,facility_id'],
            'check_in_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date_format:Y-m-d', 'after:check_in_date'],
            'room_type_id' => ['required', 'uuid', 'exists:room_types,room_type_id'],
            'plan_id' => ['required', 'uuid', 'exists:plans,plan_id'],
            'adult_count' => ['required', 'integer', 'min:1', 'max:10'],
            'child_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'guest.last_name' => ['required', 'string', 'max:50'],
            'guest.first_name' => ['required', 'string', 'max:50'],
            'guest.last_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[ぁ-んァ-ヶー]+$/u'],
            'guest.first_name_kana' => ['nullable', 'string', 'max:50', 'regex:/^[ぁ-んァ-ヶー]+$/u'],
            'guest.email' => ['required', 'email:rfc', 'max:255'],
            'guest.phone' => ['required', 'string', 'regex:/^[\d\-]{10,15}$/'],
            'guest.nationality' => ['nullable', 'string', 'size:2'],
            'special_requests' => ['nullable', 'string', 'max:500'],
            'cancel_policy_agreed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'guest.last_name.required' => '姓を入力してください',
            'guest.first_name.required' => '名を入力してください',
            'guest.email.required' => 'メールアドレスを入力してください',
            'guest.email.email' => '有効なメールアドレスを入力してください',
            'guest.phone.required' => '電話番号を入力してください',
            'guest.phone.regex' => '電話番号は数字とハイフンで10〜15桁で入力してください',
            'guest.last_name_kana.regex' => 'ふりがなはひらがなまたはカタカナで入力してください',
            'cancel_policy_agreed.accepted' => 'キャンセルポリシーに同意してください',
        ];
    }
}