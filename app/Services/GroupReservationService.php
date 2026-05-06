// app/Services/GroupReservationService.php
<?php

namespace App\Services;

use App\Models\GroupReservation;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GroupReservationService
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly GroupReservationNumberService $groupReservationNumberService,
    ) {}

    public function create(array $data, string $adminId): GroupReservation
    {
        return DB::transaction(function () use ($data, $adminId) {
            // 代表者ゲスト処理
            $guest = \App\Models\Guest::firstOrCreate(
                ['email' => $data['contact']['email']],
                [
                    'last_name' => $data['contact']['last_name'],
                    'first_name' => $data['contact']['first_name'],
                    'phone' => $data['contact']['phone'],
                ]
            );

            $groupNo = $this->groupReservationNumberService->generate();

            $nights = Carbon::parse($data['check_in_date'])
                ->diffInDays(Carbon::parse($data['check_out_date']));

            $groupReservation = GroupReservation::create([
                'group_reservation_no' => $groupNo,
                'facility_id' => $data['facility_id'],
                'group_name' => $data['group_name'],
                'contact_guest_id' => $guest->guest_id,
                'status' => 'CONFIRMED',
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'total_rooms' => count($data['rooms']),
                'notes' => $data['notes'] ?? null,
                'created_by' => $adminId,
            ]);

            $reservations = [];
            foreach ($data['rooms'] as $room) {
                $guestName = $room['guest_name'] ?? null;
                $guestData = $guestName ? ['last_name' => $guestName, 'first_name' => ''] : [
                    'last_name' => $data['contact']['last_name'],
                    'first_name' => $data['contact']['first_name'],
                ];
                $guestData['email'] = $data['contact']['email'];
                $guestData['phone'] = $data['contact']['phone'];

                $reservation = $this->reservationService->createReservation([
                    'facility_id' => $data['facility_id'],
                    'check_in_date' => $data['check_in_date'],
                    'check_out_date' => $data['check_out_date'],
                    'room_type_id' => $room['room_type_id'],
                    'plan_id' => $room['plan_id'],
                    'adult_count' => $room['adult_count'],
                    'child_count' => $room['child_count'] ?? 0,
                    'guest' => $guestData,
                    'created_by' => $adminId,
                ], 'PHONE');

                $reservation->update(['group_reservation_id' => $groupReservation->group_reservation_id]);
                $reservations[] = $reservation;
            }

            return $groupReservation->load('reservations');
        });
    }
}