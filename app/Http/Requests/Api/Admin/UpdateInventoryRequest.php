// app/Http/Requests/Api/Admin/UpdateInventoryRequest.php
<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'uuid', 'exists:facilities,facility_id'],
            'room_type_id' => ['required', 'uuid', 'exists:room_types,room_type_id'],
            'updates' => ['required', 'array', 'min:1'],
            'updates.*.date' => ['required', 'date_format:Y-m-d'],
            'updates.*.closed_count' => ['nullable', 'integer', 'min:0'],
            'updates.*.stop_sale' => ['nullable', 'boolean'],
        ];
    }
}