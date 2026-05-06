// app/Services/MemberService.php
<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Member;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Hash;

class MemberService
{
    public function __construct(
        private readonly MemberNumberService $memberNumberService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function register(array $data): Member
    {
        if (Member::where('email', $data['email'])->exists()) {
            throw new BusinessException('VALIDATION_ERROR', 'このメールアドレスは既に登録されています。');
        }

        $this->validatePassword($data['password']);

        $guest = Guest::create([
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'last_name_kana' => $data['last_name_kana'] ?? null,
            'first_name_kana' => $data['first_name_kana'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'nationality' => $data['nationality'] ?? null,
            'preferred_language' => $data['preferred_language'] ?? 'ja',
        ]);

        $member = Member::create([
            'guest_id' => $guest->guest_id,
            'member_number' => $this->memberNumberService->generate(),
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password'], ['rounds' => config('hotel.bcrypt_rounds', 12)]),
            'member_rank' => 'STANDARD',
            'email_verified' => false,
            'is_active' => true,
        ]);

        $this->auditLogService->log(
            action: 'CREATE',
            resource: 'member',
            resourceId: $member->member_id,
            newValue: ['email' => $member->email, 'member_number' => $member->member_number]
        );

        return $member;
    }

    public function validatePassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new BusinessException('VALIDATION_ERROR', 'パスワードは8文字以上である必要があります。');
        }
        if (!preg_match('/[a-zA-Z]/', $password)) {
            throw new BusinessException('VALIDATION_ERROR', 'パスワードには英字を含める必要があります。');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new BusinessException('VALIDATION_ERROR', 'パスワードには数字を含める必要があります。');
        }
    }

    public function anonymize(Member $member): void
    {
        $member->guest->anonymize();
        $member->update(['is_active' => false]);

        $this->auditLogService->log(
            action: 'ANONYMIZE',
            resource: 'member',
            resourceId: $member->member_id,
            newValue: ['is_active' => false]
        );
    }
}