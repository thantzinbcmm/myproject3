// app/Http/Controllers/Api/RoomSearchController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchRoomsRequest;
use App\Models\Facility;
use App\Models\Inventory;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\RoomType;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class RoomSearchController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService,
    ) {}

    /**
     * GET /api/v1/facilities/{facilityId}/rooms/search
     */
    public function search(SearchRoomsRequest $request, string $facilityId): JsonResponse
    {
        $facility = Facility::where('facility_id', $facilityId)
            ->where('is_active', true)
            ->firstOrFail();

        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $adultCount = (int) $request->adult_count;
        $childCount = (int) ($request->child_count ?? 0);

        $roomTypeQuery = RoomType::where('facility_id', $facilityId)
            ->where('is_active', true)
            ->where('max_capacity', '>=', $adultCount);

        if ($request->room_type_id) {
            $roomTypeQuery->where('room_type_id', $request->room_type_id);
        }

        $roomTypes = $roomTypeQuery->orderBy('sort_order')->get();

        $result = [];
        foreach ($roomTypes as $roomType) {
            // 全日程で在庫あるか確認
            $dates = $this->getDateRange($checkIn, $checkOut);
            $minAvailable = PHP_INT_MAX;
            $allAvailable = true;

            foreach ($dates as $date) {
                $inv = Inventory::where('room_type_id', $roomType->room_type_id)
                    ->where('date', $date->format('Y-m-d'))
                    ->first();

                if (!$inv || !$inv->isAvailable()) {
                    $allAvailable = false;
                    break;
                }
                $minAvailable = min($minAvailable, $inv->available_count);
            }

            if (!$allAvailable) {
                continue;
            }

            // 利用可能なプランと料金を取得
            $plans = Plan::where('facility_id', $facilityId)
                ->where('is_active', true)
                ->where('is_public', true)
                ->whereHas('roomTypes', fn($q) => $q->where('room_types.room_type_id', $roomType->room_type_id))
                ->get();

            $planData = [];
            foreach ($plans as $plan) {
                try {
                    $dailyPrices = $this->reservationService->calculateDailyPrices(
                        $plan, $roomType, $checkIn, $checkOut, $adultCount, $childCount
                    );
                    $totalAmount = array_sum(array_column($dailyPrices, 'amount'));
                    $planData[] = [
                        'planId' => $plan->plan_id,
                        'name' => $plan->name,
                        'description' => $plan->description_ja,
                        'mealType' => $plan->meal_type,
                        'totalAmount' => $totalAmount,
                        'dailyPrices' => array_map(fn($dp) => [
                            'date' => $dp['date'],
                            'amount' => $dp['amount'],
                        ], $dailyPrices),
                    ];
                } catch (\Exception) {
                    // 料金未設定のプランはスキップ
                }
            }

            if (empty($planData)) {
                continue;
            }

            $result[] = [
                'roomTypeId' => $roomType->room_type_id,
                'name' => $roomType->name,
                'description' => $roomType->description,
                'floorArea' => $roomType->floor_area,
                'standardCapacity' => $roomType->standard_capacity,
                'maxCapacity' => $roomType->max_capacity,
                'amenities' => $roomType->amenities ?? [],
                'imageUrls' => $roomType->image_urls ?? [],
                'availableCount' => $minAvailable === PHP_INT_MAX ? 0 : $minAvailable,
                'plans' => $planData,
            ];
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * GET /api/v1/facilities/{facilityId}/inventory/calendar
     */
    public function calendar(\Illuminate\Http\Request $request, string $facilityId, \App\Services\InventoryService $inventoryService): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
            'room_type_id' => 'nullable|uuid',
        ]);

        $data = $inventoryService->getCalendar(
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

    private function getDateRange(Carbon $checkIn, Carbon $checkOut): array
    {
        $dates = [];
        $date = $checkIn->copy();
        while ($date->lt($checkOut)) {
            $dates[] = $date->copy();
            $date->addDay();
        }
        return $dates;
    }
}