// app/Http/Controllers/Api/Admin/AdminFacilityController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFacilityController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(): JsonResponse
    {
        $facilities = Facility::all();
        return response()->json([
            'success' => true,
            'data' => $facilities->map(fn($f) => [
                'facilityId' => $f->facility_id,
                'facilityCode' => $f->facility_code,
                'nameJa' => $f->name_ja,
                'nameEn' => $f->name_en,
                'address' => $f->address,
                'phoneNumber' => $f->phone_number,
                'isActive' => $f->is_active,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        if (!$admin->isSuperAdmin()) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $currentCount = Facility::count();
        if ($currentCount >= config('hotel.facility_max_count', 5)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FACILITY_LIMIT_EXCEEDED', 'message' => '施設数の上限（5施設）に達しています'],
            ], 422);
        }

        $request->validate([
            'facility_code' => 'required|string|max:20|unique:facilities,facility_code',
            'name_ja' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        $facility = Facility::create([
            'facility_code' => $request->facility_code,
            'name_ja' => $request->name_ja,
            'name_en' => $request->name_en,
            'name_zh_cn' => $request->name_zh_cn,
            'name_zh_tw' => $request->name_zh_tw,
            'name_ko' => $request->name_ko,
            'name_my' => $request->name_my,
            'postal_code' => $request->postal_code,
            'address' => $request->address,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'check_in_time' => $request->check_in_time ?? '15:00:00',
            'check_out_time' => $request->check_out_time ?? '11:00:00',
            'is_active' => true,
            'created_by' => $admin->admin_id,
        ]);

        $this->auditLogService->log('CREATE', 'facility', $facility->facility_id, null, ['facility_code' => $facility->facility_code], $admin->admin_id);

        return response()->json(['success' => true, 'data' => ['facilityId' => $facility->facility_id]], 201);
    }

    public function update(Request $request, string $facilityId): JsonResponse
    {
        $admin = auth('admin')->user();
        if (!$admin->isSuperAdmin()) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $facility = Facility::findOrFail($facilityId);
        $old = $facility->toArray();

        $facility->update(array_filter($request->only([
            'name_ja', 'name_en', 'name_zh_cn', 'name_zh_tw', 'name_ko', 'name_my',
            'address', 'phone_number', 'email', 'check_in_time', 'check_out_time', 'is_active',
        ]), fn($v) => $v !== null));

        $facility->update(['updated_by' => $admin->admin_id]);
        $this->auditLogService->log('UPDATE', 'facility', $facilityId, $old, $facility->toArray(), $admin->admin_id);

        return response()->json(['success' => true, 'data' => ['facilityId' => $facilityId]]);
    }
}