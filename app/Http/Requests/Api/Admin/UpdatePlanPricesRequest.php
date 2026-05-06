// app/Http/Requests/Api/Admin/UpdatePlanPricesRequest.php
<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.room_type_id' => ['required', 'uuid', 'exists:room_types,room_type_id'],
            'prices.*.start_date' => ['required', 'date_format:Y-m-d'],
            'prices.*.end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:prices.*.start_date'],
            'prices.*.day_of_week' => ['nullable', 'array'],
            'prices.*.day_of_week.*' => ['in:MON,TUE,WED,THU,FRI,SAT,SUN'],
            'prices.*.base_price' => ['required', 'integer', 'min:0'],
            'prices.*.adult_price' => ['nullable', 'integer', 'min:0'],
            'prices.*.child_price' => ['nullable', 'integer', 'min:0'],
        ];
    }
}