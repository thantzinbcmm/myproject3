// app/Http/Requests/Api/Admin/CreatePlanRequest.php
<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'uuid', 'exists:facilities,facility_id'],
            'plan_code' => ['required', 'string', 'max:20'],
            'names.ja' => ['required', 'string', 'max:200'],
            'names.en' => ['required', 'string', 'max:200'],
            'names.zh_cn' => ['nullable', 'string', 'max:200'],
            'names.zh_tw' => ['nullable', 'string', 'max:200'],
            'names.ko' => ['nullable', 'string', 'max:200'],
            'names.my' => ['nullable', 'string', 'max:200'],
            'descriptions.ja' => ['nullable', 'string'],
            'descriptions.en' => ['nullable', 'string'],
            'meal_type' => ['required', 'in:NONE,BREAKFAST,DINNER,HALF_BOARD,FULL_BOARD'],
            'min_nights' => ['nullable', 'integer', 'min:1'],
            'max_nights' => ['nullable', 'integer', 'min:1'],
            'available_from' => ['nullable', 'date_format:Y-m-d'],
            'available_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:available_from'],
            'cancel_policy_id' => ['nullable', 'uuid', 'exists:cancel_policies,cancel_policy_id'],
            'room_type_ids' => ['nullable', 'array'],
            'room_type_ids.*' => ['uuid', 'exists:room_types,room_type_id'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}