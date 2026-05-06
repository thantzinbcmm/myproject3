// app/Http/Controllers/Api/Admin/AdminMemberController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMemberController extends Controller
{
    public function __construct(
        private readonly MemberService $memberService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $query = Member::with('guest');

        if ($request->email) {
            $query->where('email', 'like', "%{$request->email}%");
        }
        if ($request->member_number) {
            $query->where('member_number', 'like', "%{$request->member_number}%");
        }
        if ($request->guest_name) {
            $query->whereHas('guest', fn($q) => $q->where(\Illuminate\Support\Facades\DB::raw("CONCAT(last_name, first_name)"), 'like', "%{$request->guest_name}%"));
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->map(fn($m) => [
                'memberId' => $m->member_id,
                'memberNumber' => $m->member_number,
                'email' => $m->email,
                'lastName' => $m->guest->last_name,
                'firstName' => $m->guest->first_name,
                'memberRank' => $m->member_rank,
                'isActive' => $m->is_active,
                'createdAt' => $m->created_at?->toIso8601String(),
            ]),
            'meta' => ['pagination' => [
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'perPage' => $paginated->perPage(),
                'totalPages' => $paginated->lastPage(),
            ]],
        ]);
    }

    public function anonymize(string $memberId): JsonResponse
    {
        $admin = auth('admin')->user();
        if (!$admin->isSuperAdmin()) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $member = Member::findOrFail($memberId);
        $this->memberService->anonymize($member);

        return response()->json(['success' => true, 'data' => ['message' => '個人情報を削除しました']]);
    }
}