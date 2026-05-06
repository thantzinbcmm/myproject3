// app/Http/Controllers/Api/Admin/AdminInventoryController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\UpdateInventoryRequest;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * PUT /api/v1/admin/inventory
     */
    public function update(UpdateInventoryRequest $request): JsonResponse
    {
        $admin = auth('admin')->user();

        if (!$admin->canAccessFacility($request->facility_id)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません'],
            ], 403);
        }

        $updated = $this->inventoryService->updateInventory(
            $request->facility_id,
            $request->room_type_id,
            $request->updates
        );

        return response()->json(['success' => true, 'data' => ['updated' => $updated]]);
    }

    /**
     * GET /api/v1/admin/inventory/calendar
     */
    public function calendar(Request $request): JsonResponse
    {
        $request->validate([
            'facility_id' => 'required|uuid|exists:facilities,facility_id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'room_type_id' => 'nullable|uuid',
        ]);

        $admin = auth('admin')->user();
        $facilityId = $request->facility_id;

        if (!$admin->canAccessFacility($facilityId)) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $data = $this->inventoryService->getCalendar(
            $facilityId,
            (int) $request->year,
            (int) $request->month,
            $request->room_type_id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'year' => (int) $request->year,
                'month' => (int) $request->month,
                'roomTypes' => $data,
            ],
        ]);
    }
}