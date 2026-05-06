// app/Http/Requests/Api/Admin/CreateGroupReservationRequest.php
<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'uuid', 'exists:facilities,facility_id'],
            'group_name' => ['required', 'string', 'max:200'],
            'contact.last_name' => ['required', 'string', 'max:50'],
            'contact.first_name' => ['required', 'string', 'max:50'],
            'contact.email' => ['required', 'email'],
            'contact.phone' => ['required', 'string'],
            'check_in_date' => ['required', 'date_format:Y-m-d'],
            'check_out_date' => ['required', 'date_format:Y-m-d', 'after:check_in_date'],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.room_type_id' => ['required', 'uuid', 'exists:room_types,room_type_id'],
            'rooms.*.plan_id' => ['required', 'uuid', 'exists:plans,plan_id'],
            'rooms.*.adult_count' => ['required', 'integer', 'min:1'],
            'rooms.*.child_count' => ['nullable', 'integer', 'min:0'],
            'rooms.*.guest_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}