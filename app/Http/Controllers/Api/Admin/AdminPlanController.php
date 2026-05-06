// app/Http/Controllers/Api/Admin/AdminPlanController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\CreatePlanRequest;
use App\Http\Requests\Api\Admin\UpdatePlanPricesRequest;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanRoomType;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPlanController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * GET /api/v1/admin/plans
     */
    public function index(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        $query = Plan::query();

        if ($admin->isSuperAdmin()) {
            if ($request->facility_id) {
                $query->where('facility_id', $request->facility_id);
            }
        } else {
            $query->where('facility_id', $admin->facility_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        $plans = $query->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $plans->map(fn($p) => [
                'planId' => $p->plan_id,
                'planCode' => $p->plan_code,
                'name' => $p->name,
                'mealType' => $p->meal_type,
                'availableFrom' => $p->available_from?->format('Y-m-d'),
                'availableTo' => $p->available_to?->format('Y-m-d'),
                'isPublic' => $p->is_public,
                'isActive' => $p->is_active,
            ]),
        ]);
    }

    /**
     * POST /api/v1/admin/plans
     */
    public function store(CreatePlanRequest $request): JsonResponse
    {
        $data = $request->validated();
        $admin = auth('admin')->user();

        if (!$admin->canAccessFacility($data['facility_id'])) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $plan = Plan::create([
            'facility_id' => $data['facility_id'],
            'plan_code' => $data['plan_code'],
            'name_ja' => $data['names']['ja'],
            'name_en' => $data['names']['en'],
            'name_zh_cn' => $data['names']['zh_cn'] ?? null,
            'name_zh_tw' => $data['names']['zh_tw'] ?? null,
            'name_ko' => $data['names']['ko'] ?? null,
            'name_my' => $data['names']['my'] ?? null,
            'description_ja' => $data['descriptions']['ja'] ?? null,
            'description_en' => $data['descriptions']['en'] ?? null,
            'meal_type' => $data['meal_type'],
            'min_nights' => $data['min_nights'] ?? 1,
            'max_nights' => $data['max_nights'] ?? null,
            'available_from' => $data['available_from'] ?? null,
            'available_to' => $data['available_to'] ?? null,
            'cancel_policy_id' => $data['cancel_policy_id'] ?? null,
            'is_public' => $data['is_public'] ?? true,
            'is_active' => true,
            'created_by' => $admin->admin_id,
        ]);

        // 客室タイプ関連付け
        if (!empty($data['room_type_ids'])) {
            foreach ($data['room_type_ids'] as $roomTypeId) {
                \App\Models\PlanRoomType::create([
                    'plan_id' => $plan->plan_id,
                    'room_type_id' => $roomTypeId,
                ]);
            }
        }

        $this->auditLogService->log(
            action: 'CREATE',
            resource: 'plan',
            resourceId: $plan->plan_id,
            newValue: ['plan_code' => $plan->plan_code],
            actorId: $admin->admin_id
        );

        return response()->json([
            'success' => true,
            'data' => ['planId' => $plan->plan_id, 'planCode' => $plan->plan_code],
        ], 201);
    }

    /**
     * PUT /api/v1/admin/plans/{planId}
     */
    public function update(Request $request, string $planId): JsonResponse
    {
        $plan = Plan::findOrFail($planId);
        $admin = auth('admin')->user();

        if (!$admin->canAccessFacility($plan->facility_id)) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $plan->update([
            'is_public' => $request->is_public ?? $plan->is_public,
            'is_active' => $request->is_active ?? $plan->is_active,
            'updated_by' => $admin->admin_id,
        ]);

        return response()->json(['success' => true, 'data' => ['planId' => $plan->plan_id]]);
    }

    /**
     * PUT /api/v1/admin/plans/{planId}/prices
     */
    public function updatePrices(UpdatePlanPricesRequest $request, string $planId): JsonResponse
    {
        $plan = Plan::findOrFail($planId);
        $admin = auth('admin')->user();

        if (!$admin->canAccessFacility($plan->facility_id)) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $updated = 0;
        foreach ($request->prices as $priceData) {
            $dayOfWeek = !empty($priceData['day_of_week'])
                ? implode(',', $priceData['day_of_week'])
                : 'MON,TUE,WED,THU,FRI,SAT,SUN';

            PlanPrice::create([
                'plan_id' => $plan->plan_id,
                'room_type_id' => $priceData['room_type_id'],
                'start_date' => $priceData['start_date'],
                'end_date' => $priceData['end_date'],
                'day_of_week' => $dayOfWeek,
                'base_price' => $priceData['base_price'],
                'adult_price' => $priceData['adult_price'] ?? 0,
                'child_price' => $priceData['child_price'] ?? 0,
                'is_active' => true,
            ]);
            $updated++;
        }

        return response()->json(['success' => true, 'data' => ['updated' => $updated]]);
    }
}