// app/Http/Requests/Api/Admin/CreateAdminReservationRequest.php
<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdminReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'uuid', 'exists:facilities,facility_id'],
            'check_in_date' => ['required', 'date_format:Y-m-d'],
            'check_out_date' => ['required', 'date_format:Y-m-d', 'after:check_in_date'],
            'room_type_id' => ['required', 'uuid', 'exists:room_types,room_type_id'],
            'plan_id' => ['required', 'uuid', 'exists:plans,plan_id'],
            'adult_count' => ['required', 'integer', 'min:1', 'max:10'],
            'child_count' => ['nullable', 'integer', 'min:0'],
            'channel' => ['required', 'in:DIRECT,PHONE,RAKUTEN,JALAN,AGENCY,CORPORATE,OTHER'],
            'channel_reservation_no' => ['nullable', 'string', 'max:100'],
            'guest.last_name' => ['required', 'string', 'max:50'],
            'guest.first_name' => ['required', 'string', 'max:50'],
            'guest.email' => ['required', 'email', 'max:255'],
            'guest.phone' => ['required', 'string'],
            'special_requests' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}