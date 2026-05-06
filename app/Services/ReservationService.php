// app/Services/ReservationService.php
<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\Guest;
use App\Models\Inventory;
use App\Models\Member;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Reservation;
use App\Models\ReservationDetail;
use App\Models\RoomType;
use App\Exceptions\InventoryConflictException;
use App\Exceptions\BusinessException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly CancelFeeService $cancelFeeService,
        private readonly ReservationNumberService $reservationNumberService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * 予約作成（メインフロー）
     */
    public function createReservation(array $data, string $channel = 'DIRECT'): Reservation
    {
        $checkInDate = Carbon::parse($data['check_in_date']);
        $checkOutDate = Carbon::parse($data['check_out_date']);
        $nights = $checkInDate->diffInDays($checkOutDate);

        // バリデーション
        $this->validateReservationDates($checkInDate, $checkOutDate);

        $facility = Facility::where('facility_id', $data['facility_id'])->firstOrFail();
        $roomType = RoomType::where('room_type_id', $data['room_type_id'])
            ->where('facility_id', $data['facility_id'])
            ->where('is_active', true)
            ->firstOrFail();
        $plan = Plan::where('plan_id', $data['plan_id'])
            ->where('facility_id', $data['facility_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $this->validatePlan($plan, $checkInDate, $checkOutDate, $data['adult_count']);

        $reservation = DB::transaction(function () use (
            $data, $checkInDate, $checkOutDate, $nights,
            $facility, $roomType, $plan, $channel
        ) {
            // 在庫ロック & 確認
            $inventories = $this->inventoryService->lockInventory(
                $roomType->room_type_id,
                $checkInDate,
                $checkOutDate
            );

            // ゲスト処理
            $guest = $this->findOrCreateGuest($data['guest']);

            // 会員チェック
            $memberId = null;
            if (!empty($data['member_id'])) {
                $member = Member::find($data['member_id']);
                if ($member && $member->guest_id === $guest->guest_id) {
                    $memberId = $member->member_id;
                }
            }

            // 予約番号生成
            $reservationNo = $this->reservationNumberService->generate();

            // 日別料金計算
            $dailyPrices = $this->calculateDailyPrices(
                $plan,
                $roomType,
                $checkInDate,
                $checkOutDate,
                $data['adult_count'],
                $data['child_count'] ?? 0
            );
            $totalAmount = array_sum(array_column($dailyPrices, 'amount'));

            // キャンセルポリシー取得
            $cancelPolicy = $plan->cancelPolicy
                ?? \App\Models\CancelPolicy::where('facility_id', $facility->facility_id)
                    ->where('is_default', true)
                    ->first();

            // 予約作成
            $reservation = Reservation::create([
                'reservation_no' => $reservationNo,
                'facility_id' => $facility->facility_id,
                'guest_id' => $guest->guest_id,
                'member_id' => $memberId,
                'channel' => $channel,
                'channel_reservation_no' => $data['channel_reservation_no'] ?? null,
                'status' => 'CONFIRMED',
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'nights' => $nights,
                'adult_count' => $data['adult_count'],
                'child_count' => $data['child_count'] ?? 0,
                'total_amount' => $totalAmount,
                'special_requests' => $data['special_requests'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'confirmed_at' => now(),
                'created_by' => $data['created_by'] ?? 'GUEST',
                'cancel_policy_applied' => $cancelPolicy?->name,
            ]);

            // 予約明細作成
            foreach ($dailyPrices as $dp) {
                ReservationDetail::create([
                    'reservation_id' => $reservation->reservation_id,
                    'room_type_id' => $roomType->room_type_id,
                    'plan_id' => $plan->plan_id,
                    'night_date' => $dp['date'],
                    'daily_amount' => $dp['amount'],
                    'adult_count' => $data['adult_count'],
                    'child_count' => $data['child_count'] ?? 0,
                ]);
            }

            // 在庫更新
            $this->inventoryService->incrementBookedCount($inventories);

            return $reservation;
        });

        // 監査ログ
        $this->auditLogService->log(
            action: 'CREATE',
            resource: 'reservation',
            resourceId: $reservation->reservation_id,
            newValue: $reservation->toArray(),
            actorId: $data['created_by'] ?? null
        );

        return $reservation->load(['guest', 'facility', 'details.roomType', 'details.plan']);
    }

    /**
     * 予約キャンセル
     */
    public function cancelReservation(
        Reservation $reservation,
        string $email,
        ?string $cancelReason = null,
        bool $isAdmin = false
    ): Reservation {
        if (!$reservation->canCancel()) {
            throw new BusinessException(
                'CANCEL_NOT_ALLOWED',
                'この予約はキャンセルできません。'
            );
        }

        if (!$isAdmin && $reservation->guest->email !== $email) {
            throw new BusinessException(
                'PERMISSION_DENIED',
                '予約者のメールアドレスが一致しません。'
            );
        }

        $cancelFeeResult = $this->cancelFeeService->calculate(
            $reservation->check_in_date,
            $reservation->total_amount
        );

        // 在庫計算用にroomTypeIdを取得
        $roomTypeId = $reservation->details->first()?->room_type_id;

        DB::transaction(function () use ($reservation, $cancelFeeResult, $cancelReason, $roomTypeId) {
            $reservation->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancel_fee' => $cancelFeeResult['fee'],
                'cancel_reason' => $cancelReason,
                'cancel_policy_applied' => $cancelFeeResult['policy'],
            ]);

            if ($roomTypeId) {
                $this->inventoryService->decrementBookedCount(
                    $roomTypeId,
                    $reservation->check_in_date,
                    $reservation->check_out_date
                );
            }
        });

        $this->auditLogService->log(
            action: 'CANCEL',
            resource: 'reservation',
            resourceId: $reservation->reservation_id,
            oldValue: ['status' => 'CONFIRMED'],
            newValue: ['status' => 'CANCELLED', 'cancel_fee' => $cancelFeeResult['fee']]
        );

        return $reservation->fresh();
    }

    /**
     * 予約変更
     */
    public function changeReservation(Reservation $reservation, array $data, string $email): Reservation
    {
        if (!$reservation->canChange()) {
            throw new BusinessException(
                'CHANGE_PERIOD_EXPIRED',
                '宿泊' . config('hotel.change_limit_days') . '日前を過ぎているため、変更を承ることができません。'
            );
        }

        if ($reservation->guest->email !== $email) {
            throw new BusinessException(
                'PERMISSION_DENIED',
                '予約者のメールアドレスが一致しません。'
            );
        }

        $oldCheckIn = $reservation->check_in_date;
        $oldCheckOut = $reservation->check_out_date;
        $roomTypeId = $reservation->details->first()?->room_type_id;

        $newCheckIn = isset($data['new_check_in_date']) ? Carbon::parse($data['new_check_in_date']) : $oldCheckIn;
        $newCheckOut = isset($data['new_check_out_date']) ? Carbon::parse($data['new_check_out_date']) : $oldCheckOut;

        $this->validateReservationDates($newCheckIn, $newCheckOut);

        DB::transaction(function () use ($reservation, $data, $newCheckIn, $newCheckOut, $oldCheckIn, $oldCheckOut, $roomTypeId) {
            if ($roomTypeId && ($newCheckIn != $oldCheckIn || $newCheckOut != $oldCheckOut)) {
                // 旧在庫を戻す
                $this->inventoryService->decrementBookedCount($roomTypeId, $oldCheckIn, $oldCheckOut);

                // 新在庫ロック
                $inventories = $this->inventoryService->lockInventory($roomTypeId, $newCheckIn, $newCheckOut);
                $this->inventoryService->incrementBookedCount($inventories);

                $nights = $newCheckIn->diffInDays($newCheckOut);
                $plan = Plan::find($reservation->details->first()->plan_id);
                $roomType = RoomType::find($roomTypeId);

                $dailyPrices = $this->calculateDailyPrices(
                    $plan,
                    $roomType,
                    $newCheckIn,
                    $newCheckOut,
                    $data['new_adult_count'] ?? $reservation->adult_count,
                    $data['new_child_count'] ?? $reservation->child_count
                );
                $totalAmount = array_sum(array_column($dailyPrices, 'amount'));

                $reservation->details()->delete();
                foreach ($dailyPrices as $dp) {
                    ReservationDetail::create([
                        'reservation_id' => $reservation->reservation_id,
                        'room_type_id' => $roomTypeId,
                        'plan_id' => $plan->plan_id,
                        'night_date' => $dp['date'],
                        'daily_amount' => $dp['amount'],
                        'adult_count' => $data['new_adult_count'] ?? $reservation->adult_count,
                        'child_count' => $data['new_child_count'] ?? $reservation->child_count,
                    ]);
                }

                $reservation->update([
                    'check_in_date' => $newCheckIn,
                    'check_out_date' => $newCheckOut,
                    'nights' => $nights,
                    'total_amount' => $totalAmount,
                    'adult_count' => $data['new_adult_count'] ?? $reservation->adult_count,
                    'child_count' => $data['new_child_count'] ?? $reservation->child_count,
                ]);
            }

            if (isset($data['new_special_requests'])) {
                $reservation->update(['special_requests' => $data['new_special_requests']]);
            }
        });

        return $reservation->fresh();
    }

    /**
     * 日別料金計算
     */
    public function calculateDailyPrices(
        Plan $plan,
        RoomType $roomType,
        Carbon $checkIn,
        Carbon $checkOut,
        int $adultCount,
        int $childCount = 0
    ): array {
        $prices = [];
        $date = $checkIn->copy();

        while ($date->lt($checkOut)) {
            $planPrice = PlanPrice::where('plan_id', $plan->plan_id)
                ->where('room_type_id', $roomType->room_type_id)
                ->where('start_date', '<=', $date->format('Y-m-d'))
                ->where('end_date', '>=', $date->format('Y-m-d'))
                ->where('is_active', true)
                ->get()
                ->first(fn($p) => $p->appliesToDate($date));

            if (!$planPrice) {
                throw new BusinessException(
                    'PLAN_NOT_AVAILABLE',
                    "指定日({$date->format('Y-m-d')})の料金が設定されていません。"
                );
            }

            $amount = $planPrice->base_price
                + ($planPrice->adult_price * $adultCount)
                + ($planPrice->child_price * $childCount);

            $prices[] = [
                'date' => $date->format('Y-m-d'),
                'amount' => (int) $amount,
            ];

            $date->addDay();
        }

        return $prices;
    }

    private function validateReservationDates(Carbon $checkIn, Carbon $checkOut): void
    {
        if ($checkIn->startOfDay()->lt(now()->startOfDay())) {
            throw new BusinessException('INVALID_DATE', 'チェックイン日は今日以降の日付を入力してください。');
        }
        if ($checkOut->lte($checkIn)) {
            throw new BusinessException('INVALID_DATE', 'チェックアウト日はチェックイン日より後の日付を選択してください。');
        }
    }

    private function validatePlan(Plan $plan, Carbon $checkIn, Carbon $checkOut, int $adultCount): void
    {
        $nights = $checkIn->diffInDays($checkOut);

        if ($plan->available_from && $checkIn->lt($plan->available_from)) {
            throw new BusinessException('PLAN_NOT_AVAILABLE', 'このプランはまだ利用できません。');
        }
        if ($plan->available_to && $checkOut->gt($plan->available_to)) {
            throw new BusinessException('PLAN_NOT_AVAILABLE', 'このプランの販売期間が終了しています。');
        }
        if ($nights < $plan->min_nights) {
            throw new BusinessException('PLAN_NOT_AVAILABLE', "このプランは最低{$plan->min_nights}泊以上のご予約が必要です。");
        }
        if ($plan->max_nights && $nights > $plan->max_nights) {
            throw new BusinessException('PLAN_NOT_AVAILABLE', "このプランは最大{$plan->max_nights}泊までのご予約です。");
        }
    }

    private function findOrCreateGuest(array $guestData): Guest
    {
        $guest = Guest::where('email', $guestData['email'])->first();

        if ($guest && !$guest->is_anonymized) {
            $guest->update([
                'last_name' => $guestData['last_name'],
                'first_name' => $guestData['first_name'],
                'last_name_kana' => $guestData['last_name_kana'] ?? $guest->last_name_kana,
                'first_name_kana' => $guestData['first_name_kana'] ?? $guest->first_name_kana,
                'phone' => $guestData['phone'],
                'nationality' => $guestData['nationality'] ?? $guest->nationality,
            ]);
            return $guest;
        }

        return Guest::create([
            'last_name' => $guestData['last_name'],
            'first_name' => $guestData['first_name'],
            'last_name_kana' => $guestData['last_name_kana'] ?? null,
            'first_name_kana' => $guestData['first_name_kana'] ?? null,
            'email' => $guestData['email'],
            'phone' => $guestData['phone'],
            'nationality' => $guestData['nationality'] ?? null,
            'preferred_language' => $guestData['preferred_language'] ?? 'ja',
        ]);
    }
}