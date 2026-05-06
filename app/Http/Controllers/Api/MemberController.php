// app/Http/Controllers/Api/MemberController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Member\RegisterMemberRequest;
use App\Models\Reservation;
use App\Services\MemberService;
use App\Notifications\MemberRegistered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(
        private readonly MemberService $memberService,
    ) {}

    /**
     * POST /api/v1/members/register
     */
    public function register(RegisterMemberRequest $request): JsonResponse
    {
        $member = $this->memberService->register($request->validated());

        // 確認メール送信
        $member->notify(new MemberRegistered($member));

        return response()->json([
            'success' => true,
            'data' => [
                'memberId' => $member->member_id,
                'memberNumber' => $member->member_number,
                'email' => $member->email,
                'message' => '確認メールを送信しました',
            ],
        ], 201);
    }

    /**
     * GET /api/v1/members/me/reservations
     */
    public function myReservations(Request $request): JsonResponse
    {
        $member = auth('member')->user();

        $reservations = Reservation::where('member_id', $member->member_id)
            ->with(['facility', 'details.roomType', 'details.plan'])
            ->orderByDesc('check_in_date')
            ->get();

        $data = $reservations->map(function ($r) {
            $firstDetail = $r->details->first();
            return [
                'reservationId' => $r->reservation_id,
                'reservationNo' => $r->reservation_no,
                'status' => $r->status,
                'facilityName' => $r->facility->name,
                'checkInDate' => $r->check_in_date->format('Y-m-d'),
                'checkOutDate' => $r->check_out_date->format('Y-m-d'),
                'roomTypeName' => $firstDetail?->roomType?->name,
                'planName' => $firstDetail?->plan?->name,
                'totalAmount' => $r->total_amount,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/v1/members/me
     */
    public function me(Request $request): JsonResponse
    {
        $member = auth('member')->user()->load('guest');

        return response()->json([
            'success' => true,
            'data' => [
                'memberId' => $member->member_id,
                'memberNumber' => $member->member_number,
                'email' => $member->email,
                'memberRank' => $member->member_rank,
                'lastName' => $member->guest->last_name,
                'firstName' => $member->guest->first_name,
                'lastNameKana' => $member->guest->last_name_kana,
                'firstNameKana' => $member->guest->first_name_kana,
                'phone' => $member->guest->phone,
                'emailVerified' => $member->email_verified,
            ],
        ]);
    }
}