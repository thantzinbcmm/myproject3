// app/Http/Controllers/Api/Admin/AdminGroupReservationController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\CreateGroupReservationRequest;
use App\Models\GroupReservation;
use App\Services\GroupReservationService;
use Illuminate\Http\JsonResponse;

class AdminGroupReservationController extends Controller
{
    public function __construct(
        private readonly GroupReservationService $groupReservationService,
    ) {}

    public function store(CreateGroupReservationRequest $request): JsonResponse
    {
        $admin = auth('admin')->user();

        if (!$admin->canAccessFacility($request->facility_id)) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $groupReservation = $this->groupReservationService->create(
            $request->validated(),
            $admin->admin_id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'groupReservationId' => $groupReservation->group_reservation_id,
                'groupReservationNo' => $groupReservation->group_reservation_no,
                'reservations' => $groupReservation->reservations->map(fn($r) => [
                    'reservationId' => $r->reservation_id,
                    'reservationNo' => $r->reservation_no,
                ]),
            ],
        ], 201);
    }

    public function index(): JsonResponse
    {
        $admin = auth('admin')->user();
        $query = GroupReservation::with(['contactGuest', 'reservations']);

        if (!$admin->isSuperAdmin()) {
            $query->where('facility_id', $admin->facility_id);
        }

        $groups = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $groups->map(fn($g) => [
                'groupReservationId' => $g->group_reservation_id,
                'groupReservationNo' => $g->group_reservation_no,
                'groupName' => $g->group_name,
                'status' => $g->status,
                'checkInDate' => $g->check_in_date->format('Y-m-d'),
                'checkOutDate' => $g->check_out_date->format('Y-m-d'),
                'totalRooms' => $g->total_rooms,
                'contactName' => $g->contactGuest?->full_name,
            ]),
        ]);
    }
}