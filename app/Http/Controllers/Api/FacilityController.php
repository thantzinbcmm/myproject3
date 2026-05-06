// app/Http/Controllers/Api/FacilityController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;

class FacilityController extends Controller
{
    /**
     * GET /api/v1/facilities
     */
    public function index(): JsonResponse
    {
        $facilities = Facility::where('is_active', true)->get();

        $data = $facilities->map(fn($f) => [
            'facilityId' => $f->facility_id,
            'facilityCode' => $f->facility_code,
            'name' => $f->name,
            'address' => $f->address,
            'phoneNumber' => $f->phone_number,
            'checkInTime' => $f->check_in_time,
            'checkOutTime' => $f->check_out_time,
            'isActive' => $f->is_active,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/v1/facilities/{facilityId}
     */
    public function show(string $facilityId): JsonResponse
    {
        $f = Facility::where('facility_id', $facilityId)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'facilityId' => $f->facility_id,
                'facilityCode' => $f->facility_code,
                'names' => [
                    'ja' => $f->name_ja,
                    'en' => $f->name_en,
                    'zhCn' => $f->name_zh_cn,
                    'zhTw' => $f->name_zh_tw,
                    'ko' => $f->name_ko,
                    'my' => $f->name_my,
                ],
                'address' => $f->address,
                'phoneNumber' => $f->phone_number,
                'email' => $f->email,
                'checkInTime' => $f->check_in_time,
                'checkOutTime' => $f->check_out_time,
                'isActive' => $f->is_active,
            ],
        ]);
    }
}