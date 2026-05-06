// app/Http/Requests/Api/SearchRoomsRequest.php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SearchRoomsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_in_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date_format:Y-m-d', 'after:check_in_date'],
            'adult_count' => ['required', 'integer', 'min:1', 'max:10'],
            'child_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'room_type_id' => ['nullable', 'string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_in_date.required' => 'チェックイン日を入力してください',
            'check_in_date.after_or_equal' => '今日以降の日付を選択してください',
            'check_out_date.required' => 'チェックアウト日を入力してください',
            'check_out_date.after' => 'チェックアウト日はチェックイン日より後の日付を選択してください',
            'adult_count.required' => '大人人数を選択してください',
            'adult_count.min' => '1〜10名の範囲で入力してください',
            'adult_count.max' => '1〜10名の範囲で入力してください',
        ];
    }
}