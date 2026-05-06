// app/Console/Commands/GenerateMonthlyInventory.php
<?php

namespace App\Console\Commands;

use App\Models\Facility;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyInventory extends Command
{
    protected $signature = 'inventory:generate {--months=3 : 何ヶ月分を生成するか}';
    protected $description = '在庫レコードを事前生成します';

    public function __construct(
        private readonly InventoryService $inventoryService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $from = Carbon::today();
        $to = Carbon::today()->addMonths($months);

        $facilities = Facility::where('is_active', true)->get();

        foreach ($facilities as $facility) {
            $roomTypes = RoomType::where('facility_id', $facility->facility_id)
                ->where('is_active', true)
                ->get();

            foreach ($roomTypes as $roomType) {
                $totalCount = Room::where('room_type_id', $roomType->room_type_id)
                    ->where('is_active', true)
                    ->count();

                $this->inventoryService->initializeInventory(
                    $roomType->room_type_id,
                    $facility->facility_id,
                    $totalCount,
                    $from,
                    $to
                );

                $this->info("在庫生成完了: {$roomType->type_code} ({$from->format('Y-m-d')} 〜 {$to->format('Y-m-d')})");
            }
        }

        return self::SUCCESS;
    }
}