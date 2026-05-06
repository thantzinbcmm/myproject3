// app/Http/Controllers/Api/Admin/AdminReservationController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\CreateAdminReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Services\CancelFeeService;
use App\Services\AuditLogService;
use App\Notifications\ReservationConfirmed;
use App\Notifications\ReservationCancelled;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly CancelFeeService $cancelFeeService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * GET /api/v1/admin/reservations
     */
    public function index(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $query = Reservation::with(['guest', 'details.roomType', 'details.plan'])
            ->orderByDesc('created_at');

        // 施設フィルタ
        if ($admin->isSuperAdmin()) {
            if ($request->facility_id) {
                $query->where('facility_id', $request->facility_id);
            }
        } else {
            $query->where('facility_id', $admin->facility_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->check_in_date_from) {
            $query->where('check_in_date', '>=', $request->check_in_date_from);
        }
        if ($request->check_in_date_to) {
            $query->where('check_in_date', '<=', $request->check_in_date_to);
        }
        if ($request->reservation_no) {
            $query->where('reservation_no', 'like', "%{$request->reservation_no}%");
        }
        if ($request->channel) {
            $query->where('channel', $request->channel);
        }
        if ($request->guest_name) {
            $query->whereHas('guest', function ($q) use ($request) {
                $q->where(\Illuminate\Support\Facades\DB::raw("CONCAT(last_name, first_name)"), 'like', "%{$request->guest_name}%");
            });
        }

        $paginated = $query->paginate($perPage);

        $data = $paginated->map(function ($r) {
            $firstDetail = $r->details->first();
            return [
                'reservationId' => $r->reservation_id,
                'reservationNo' => $r->reservation_no,
                'status' => $r->status,
                'checkInDate' => $r->check_in_date->format('Y-m-d'),
                'checkOutDate' => $r->check_out_date->format('Y-m-d'),
                'nights' => $r->nights,
                'guestName' => $r->guest->full_name,
                'roomTypeName' => $firstDetail?->roomType?->name,
                'planName' => $firstDetail?->plan?->name,
                'totalAmount' => $r->total_amount,
                'channel' => $r->channel,
                'createdAt' => $r->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'pagination' => [
                    'total' => $paginated->total(),
                    'page' => $paginated->currentPage(),
                    'perPage' => $paginated->perPage(),
                    'totalPages' => $paginated->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/reservations/{reservationId}
     */
    public function show(string $reservationId): JsonResponse
    {
        $admin = auth('admin')->user();
        $reservation = Reservation::with([
            'guest', 'facility', 'details.roomType', 'details.plan', 'groupReservation'
        ])->findOrFail($reservationId);

        if (!$admin->canAccessFacility($reservation->facility_id)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません'],
            ], 403);
        }

        $firstDetail = $reservation->details->first();

        return response()->json([
            'success' => true,
            'data' => [
                'reservationId' => $reservation->reservation_id,
                'reservationNo' => $reservation->reservation_no,
                'status' => $reservation->status,
                'facility' => [
                    'name' => $reservation->facility->name,
                    'address' => $reservation->facility->address,
                    'phoneNumber' => $reservation->facility->phone_number,
                ],
                'guest' => [
                    'guestId' => $reservation->guest->guest_id,
                    'lastName' => $reservation->guest->last_name,
                    'firstName' => $reservation->guest->first_name,
                    'email' => $reservation->guest->email,
                    'phone' => $reservation->guest->phone,
                ],
                'roomType' => ['name' => $firstDetail?->roomType?->name],
                'plan' => ['name' => $firstDetail?->plan?->name],
                'checkInDate' => $reservation->check_in_date->format('Y-m-d'),
                'checkOutDate' => $reservation->check_out_date->format('Y-m-d'),
                'nights' => $reservation->nights,
                'adultCount' => $reservation->adult_count,
                'childCount' => $reservation->child_count,
                'totalAmount' => $reservation->total_amount,
                'cancelFee' => $reservation->cancel_fee,
                'cancelledAt' => $reservation->cancelled_at?->toIso8601String(),
                'channel' => $reservation->channel,
                'channelReservationNo' => $reservation->channel_reservation_no,
                'specialRequests' => $reservation->special_requests,
                'internalNotes' => $reservation->internal_notes,
                'groupReservationId' => $reservation->group_reservation_id,
                'details' => $reservation->details->map(fn($d) => [
                    'detailId' => $d->detail_id,
                    'nightDate' => $d->night_date->format('Y-m-d'),
                    'roomTypeName' => $d->roomType?->name,
                    'planName' => $d->plan?->name,
                    'dailyAmount' => $d->daily_amount,
                ]),
                'createdAt' => $reservation->created_at?->toIso8601String(),
                'updatedAt' => $reservation->updated_at?->toIso8601String(),
                'createdBy' => $reservation->created_by,
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/reservations
     */
    public function store(CreateAdminReservationRequest $request): JsonResponse
    {
        $admin = auth('admin')->user();
        $data = $request->validated();
        $data['created_by'] = $admin->admin_id;

        $reservation = $this->reservationService->createReservation($data, $data['channel']);
        $reservation->guest->notify(new ReservationConfirmed($reservation));

        return response()->json(['success' => true, 'data' => ['reservationId' => $reservation->reservation_id, 'reservationNo' => $reservation->reservation_no]], 201);
    }

    /**
     * PUT /api/v1/admin/reservations/{reservationId}/cancel
     */
    public function cancel(Request $request, string $reservationId): JsonResponse
    {
        $request->validate(['cancel_reason' => 'nullable|string|max:500']);

        $reservation = Reservation::with(['guest', 'details'])->findOrFail($reservationId);
        $admin = auth('admin')->user();

        if (!$admin->canAccessFacility($reservation->facility_id)) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $reservation = $this->reservationService->cancelReservation(
            $reservation,
            $reservation->guest->email,
            $request->cancel_reason,
            isAdmin: true
        );

        $reservation->guest->notify(new ReservationCancelled($reservation));

        return response()->json([
            'success' => true,
            'data' => [
                'reservationId' => $reservation->reservation_id,
                'status' => $reservation->status,
                'cancelFee' => $reservation->cancel_fee,
            ],
        ]);
    }

    /**
     * PUT /api/v1/admin/reservations/{reservationId}/checkin
     */
    public function checkIn(string $reservationId): JsonResponse
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->status !== 'CONFIRMED') {
            return response()->json(['success' => false, 'error' => ['code' => 'CANNOT_CHANGE_STATUS', 'message' => 'チェックインできないステータスです']], 422);
        }

        $reservation->update(['status' => 'CHECKIN', 'checkin_at' => now()]);

        $this->auditLogService->log(
            action: 'CHECKIN',
            resource: 'reservation',
            resourceId: $reservation->reservation_id,
            newValue: ['status' => 'CHECKIN'],
            actorId: auth('admin')->id()
        );

        return response()->json(['success' => true, 'data' => ['status' => 'CHECKIN']]);
    }

    /**
     * PUT /api/v1/admin/reservations/{reservationId}/checkout
     */
    public function checkOut(string $reservationId): JsonResponse
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->status !== 'CHECKIN') {
            return response()->json(['success' => false, 'error' => ['code' => 'CANNOT_CHANGE_STATUS', 'message' => 'チェックアウトできないステータスです']], 422);
        }

        $reservation->update(['status' => 'CHECKOUT', 'checkout_at' => now()]);

        return response()->json(['success' => true, 'data' => ['status' => 'CHECKOUT']]);
    }

    /**
     * PATCH /api/v1/admin/reservations/{reservationId}/notes
     */
    public function updateNotes(Request $request, string $reservationId): JsonResponse
    {
        $request->validate(['internal_notes' => 'nullable|string|max:1000']);
        $reservation = Reservation::findOrFail($reservationId);
        $reservation->update(['internal_notes' => $request->internal_notes, 'updated_by' => auth('admin')->id()]);
        return response()->json(['success' => true]);
    }
}