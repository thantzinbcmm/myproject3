// app/Http/Controllers/Api/ReservationController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateReservationRequest;
use App\Http\Requests\Api\CancelReservationRequest;
use App\Http\Requests\Api\ChangeReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Services\CancelFeeService;
use App\Notifications\ReservationConfirmed;
use App\Notifications\ReservationCancelled;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly CancelFeeService $cancelFeeService,
    ) {}

    /**
     * POST /api/v1/reservations
     */
    public function store(CreateReservationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = 'GUEST';

        $reservation = $this->reservationService->createReservation($data, 'DIRECT');

        // 確認メール送信（キュー経由）
        $reservation->guest->notify(new ReservationConfirmed($reservation));

        return response()->json([
            'success' => true,
            'data' => $this->formatReservationResponse($reservation),
        ], 201);
    }

    /**
     * GET /api/v1/reservations/{reservationNo}
     */
    public function show(string $reservationNo): JsonResponse
    {
        $request = app('request');
        $email = $request->query('email');

        if (!$email) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'メールアドレスが必要です'],
            ], 400);
        }

        $reservation = Reservation::where('reservation_no', $reservationNo)
            ->with(['guest', 'facility', 'details.roomType', 'details.plan', 'details.plan.cancelPolicy'])
            ->firstOrFail();

        if ($reservation->guest->email !== $email) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'PERMISSION_DENIED', 'message' => '予約情報が見つかりません'],
            ], 403);
        }

        $cancelFee = $this->cancelFeeService->calculate(
            $reservation->check_in_date,
            $reservation->total_amount
        );

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
                'checkInDate' => $reservation->check_in_date->format('Y-m-d'),
                'checkOutDate' => $reservation->check_out_date->format('Y-m-d'),
                'nights' => $reservation->nights,
                'adultCount' => $reservation->adult_count,
                'childCount' => $reservation->child_count,
                'roomType' => ['name' => $firstDetail?->roomType?->name],
                'plan' => [
                    'name' => $firstDetail?->plan?->name,
                    'mealType' => $firstDetail?->plan?->meal_type,
                ],
                'guest' => [
                    'lastName' => $reservation->guest->last_name,
                    'firstName' => $reservation->guest->first_name,
                    'email' => $reservation->guest->email,
                    'phone' => $reservation->guest->phone,
                ],
                'totalAmount' => $reservation->total_amount,
                'specialRequests' => $reservation->special_requests,
                'cancelPolicy' => [
                    'freeCancelUntil' => $this->cancelFeeService->getFreeCancelUntil($reservation->check_in_date)->format('Y-m-d'),
                ],
                'canChange' => $reservation->canChange(),
                'canCancel' => $reservation->canCancel(),
                'estimatedCancelFee' => $cancelFee['fee'],
            ],
        ]);
    }

    /**
     * PUT /api/v1/reservations/{reservationId}/cancel
     */
    public function cancel(CancelReservationRequest $request, string $reservationId): JsonResponse
    {
        $reservation = Reservation::with(['guest', 'details'])->findOrFail($reservationId);

        $reservation = $this->reservationService->cancelReservation(
            $reservation,
            $request->email,
            $request->cancel_reason
        );

        $reservation->guest->notify(new ReservationCancelled($reservation));

        return response()->json([
            'success' => true,
            'data' => [
                'reservationId' => $reservation->reservation_id,
                'reservationNo' => $reservation->reservation_no,
                'status' => $reservation->status,
                'cancelFee' => $reservation->cancel_fee,
                'cancelFeeRate' => $this->getCancelFeeRate($reservation->cancel_policy_applied),
                'cancelledAt' => $reservation->cancelled_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * PUT /api/v1/reservations/{reservationId}/change
     */
    public function change(ChangeReservationRequest $request, string $reservationId): JsonResponse
    {
        $reservation = Reservation::with(['guest', 'details'])->findOrFail($reservationId);

        $reservation = $this->reservationService->changeReservation(
            $reservation,
            $request->validated(),
            $request->email
        );

        return response()->json([
            'success' => true,
            'data' => [
                'reservationId' => $reservation->reservation_id,
                'reservationNo' => $reservation->reservation_no,
                'status' => $reservation->status,
                'checkInDate' => $reservation->check_in_date->format('Y-m-d'),
                'checkOutDate' => $reservation->check_out_date->format('Y-m-d'),
                'totalAmount' => $reservation->total_amount,
            ],
        ]);
    }

    private function formatReservationResponse(Reservation $reservation): array
    {
        $firstDetail = $reservation->details->first();
        $cancelPolicy = $firstDetail?->plan?->cancelPolicy;
        $freeCancelUntil = $this->cancelFeeService->getFreeCancelUntil($reservation->check_in_date);

        return [
            'reservationId' => $reservation->reservation_id,
            'reservationNo' => $reservation->reservation_no,
            'status' => $reservation->status,
            'checkInDate' => $reservation->check_in_date->format('Y-m-d'),
            'checkOutDate' => $reservation->check_out_date->format('Y-m-d'),
            'nights' => $reservation->nights,
            'totalAmount' => $reservation->total_amount,
            'guest' => [
                'lastName' => $reservation->guest->last_name,
                'firstName' => $reservation->guest->first_name,
                'email' => $reservation->guest->email,
            ],
            'plan' => ['name' => $firstDetail?->plan?->name],
            'roomType' => ['name' => $firstDetail?->roomType?->name],
            'cancelPolicy' => [
                'freeCancelUntil' => $freeCancelUntil->format('Y-m-d'),
                'rules' => [
                    ['daysBeforeCheckin' => 2, 'chargeRate' => 0],
                    ['daysBeforeCheckin' => 1, 'chargeRate' => 50],
                    ['daysBeforeCheckin' => 0, 'chargeRate' => 100],
                ],
            ],
        ];
    }

    private function getCancelFeeRate(?string $policy): float
    {
        return match ($policy) {
            'SAME_DAY', 'NOSHOW' => 100,
            'DAY_BEFORE' => 50,
            default => 0,
        };
    }
}