// app/Http/Requests/Api/CancelReservationRequest.php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CancelReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}