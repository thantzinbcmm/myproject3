// app/Http/Requests/Api/ChangeReservationRequest.php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChangeReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'new_check_in_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'new_check_out_date' => ['nullable', 'date_format:Y-m-d', 'after:new_check_in_date'],
            'new_adult_count' => ['nullable', 'integer', 'min:1', 'max:10'],
            'new_child_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'new_special_requests' => ['nullable', 'string', 'max:500'],
        ];
    }
}