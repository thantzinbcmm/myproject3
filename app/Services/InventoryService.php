// app/Services/InventoryService.php
<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * 在庫の空き確認（ロックなし）
     */
    public function checkAvailability(
        string $roomTypeId,
        Carbon $checkInDate,
        Carbon $checkOutDate
    ): bool {
        $dates = $this->getDateRange($checkInDate, $checkOutDate);

        foreach ($dates as $date) {
            $inventory = Inventory::where('room_type_id', $roomTypeId)
                ->where('date', $date->format('Y-m-d'))
                ->first();

            if (!$inventory || !$inventory->isAvailable()) {
                return false;
            }
        }
        return true;
    }

    /**
     * 在庫ロック（トランザクション内で使用）
     */
    public function lockInventory(
        string $roomTypeId,
        Carbon $checkInDate,
        Carbon $checkOutDate
    ): array {
        $dates = $this->getDateRange($checkInDate, $checkOutDate);
        $inventories = [];

        foreach ($dates as $date) {
            $inventory = Inventory::where('room_type_id', $roomTypeId)
                ->where('date', $date->format('Y-m-d'))
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new \RuntimeException("在庫レコードが存在しません: {$date->format('Y-m-d')}");
            }

            if (!$inventory->isAvailable()) {
                throw new \App\Exceptions\InventoryConflictException(
                    "在庫がありません: {$date->format('Y-m-d')}"
                );
            }

            $inventories[] = $inventory;
        }

        return $inventories;
    }

    /**
     * 予約数を増加させる（楽観的ロック）
     */
    public function incrementBookedCount(array $inventories): void
    {
        foreach ($inventories as $inventory) {
            $updated = Inventory::where('inventory_id', $inventory->inventory_id)
                ->where('version', $inventory->version)
                ->update([
                    'booked_count' => DB::raw('booked_count + 1'),
                    'version' => DB::raw('version + 1'),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                throw new \App\Exceptions\InventoryConflictException(
                    '在庫の更新に失敗しました。在庫が変更されています。'
                );
            }
        }
    }

    /**
     * 予約数を減少させる（キャンセル時）
     */
    public function decrementBookedCount(string $roomTypeId, Carbon $checkInDate, Carbon $checkOutDate): void
    {
        $dates = $this->getDateRange($checkInDate, $checkOutDate);

        foreach ($dates as $date) {
            Inventory::where('room_type_id', $roomTypeId)
                ->where('date', $date->format('Y-m-d'))
                ->where('booked_count', '>', 0)
                ->update([
                    'booked_count' => DB::raw('booked_count - 1'),
                    'version' => DB::raw('version + 1'),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * 在庫の初期化（新しい客室タイプ登録時）
     */
    public function initializeInventory(
        string $roomTypeId,
        string $facilityId,
        int $totalCount,
        Carbon $from,
        Carbon $to
    ): void {
        $date = $from->copy();
        while ($date->lte($to)) {
            Inventory::firstOrCreate(
                ['room_type_id' => $roomTypeId, 'date' => $date->format('Y-m-d')],
                [
                    'facility_id' => $facilityId,
                    'total_count' => $totalCount,
                    'booked_count' => 0,
                    'closed_count' => 0,
                    'stop_sale' => false,
                    'version' => 0,
                ]
            );
            $date->addDay();
        }
    }

    /**
     * 在庫更新（管理者）
     */
    public function updateInventory(
        string $facilityId,
        string $roomTypeId,
        array $updates
    ): int {
        $updated = 0;
        foreach ($updates as $update) {
            $inventory = Inventory::where('room_type_id', $roomTypeId)
                ->where('date', $update['date'])
                ->first();

            if (!$inventory) {
                continue;
            }

            $data = [];
            if (isset($update['closed_count'])) {
                $data['closed_count'] = $update['closed_count'];
            }
            if (isset($update['stop_sale'])) {
                $data['stop_sale'] = $update['stop_sale'];
            }
            if (!empty($data)) {
                $data['version'] = DB::raw('version + 1');
                $inventory->update($data);
                $updated++;
            }
        }
        return $updated;
    }

    /**
     * カレンダー用在庫取得
     */
    public function getCalendar(
        string $facilityId,
        int $year,
        int $month,
        ?string $roomTypeId = null
    ): array {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $query = Inventory::with('roomType')
            ->where('facility_id', $facilityId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($roomTypeId) {
            $query->where('room_type_id', $roomTypeId);
        }

        $inventories = $query->get()->groupBy('room_type_id');

        $result = [];
        foreach ($inventories as $rtId => $items) {
            $calendar = [];
            foreach ($items as $item) {
                $lowestPrice = $this->getLowestPrice($rtId, Carbon::parse($item->date));
                $calendar[] = [
                    'date' => $item->date->format('Y-m-d'),
                    'availableCount' => $item->available_count,
                    'lowestPrice' => $lowestPrice,
                    'stopSale' => $item->stop_sale,
                ];
            }
            $result[] = [
                'roomTypeId' => $rtId,
                'name' => $items->first()->roomType->name ?? '',
                'calendar' => $calendar,
            ];
        }

        return $result;
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

    private function getLowestPrice(string $roomTypeId, Carbon $date): ?int
    {
        $prices = \App\Models\PlanPrice::where('room_type_id', $roomTypeId)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->get();

        $dayMap = [1 => 'MON', 2 => 'TUE', 3 => 'WED', 4 => 'THU', 5 => 'FRI', 6 => 'SAT', 0 => 'SUN'];
        $dayOfWeek = $dayMap[$date->dayOfWeek];

        $minPrice = null;
        foreach ($prices as $price) {
            $days = explode(',', $price->day_of_week);
            if (in_array($dayOfWeek, $days)) {
                if ($minPrice === null || $price->base_price < $minPrice) {
                    $minPrice = (int) $price->base_price;
                }
            }
        }
        return $minPrice;
    }
}