// app/Services/AuthService.php
<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\Member;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * ゲスト（会員）ログイン
     */
    public function memberLogin(string $email, string $password): array
    {
        $member = Member::where('email', $email)->first();

        if (!$member || !$member->is_active) {
            throw new BusinessException('INVALID_CREDENTIALS', 'メールアドレスまたはパスワードが正しくありません。');
        }

        if ($member->isLocked()) {
            throw new BusinessException('ACCOUNT_LOCKED', 'アカウントがロックされています。しばらくしてからお試しください。');
        }

        if (!Hash::check($password, $member->password_hash)) {
            $this->handleMemberLoginFailure($member);
            throw new BusinessException('INVALID_CREDENTIALS', 'メールアドレスまたはパスワードが正しくありません。');
        }

        // 成功処理
        $member->update([
            'login_failed_count' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);

        $token = auth('member')->login($member);

        return [
            'accessToken' => $token,
            'refreshToken' => $this->generateRefreshToken($member, 'member'),
            'member' => [
                'memberId' => $member->member_id,
                'memberNumber' => $member->member_number,
                'email' => $member->email,
                'lastName' => $member->guest->last_name ?? '',
                'firstName' => $member->guest->first_name ?? '',
                'memberRank' => $member->member_rank,
            ],
        ];
    }

    /**
     * 管理者ログイン
     */
    public function adminLogin(string $username, string $password): array
    {
        $admin = AdminUser::with('role')->where('username', $username)->first();

        if (!$admin || !$admin->is_active) {
            throw new BusinessException('INVALID_CREDENTIALS', 'ユーザー名またはパスワードが正しくありません。');
        }

        if ($admin->isLocked()) {
            throw new BusinessException('ACCOUNT_LOCKED', 'アカウントがロックされています。しばらくしてからお試しください。');
        }

        if (!Hash::check($password, $admin->password_hash)) {
            $this->handleAdminLoginFailure($admin);
            throw new BusinessException('INVALID_CREDENTIALS', 'ユーザー名またはパスワードが正しくありません。');
        }

        $admin->update([
            'login_failed_count' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);

        $ttl = config('hotel.jwt.admin_ttl', 480);
        $token = auth('admin')->setTTL($ttl)->login($admin);

        return [
            'accessToken' => $token,
            'refreshToken' => $this->generateRefreshToken($admin, 'admin'),
            'admin' => [
                'adminId' => $admin->admin_id,
                'username' => $admin->username,
                'role' => $admin->role->role_name,
                'facilityId' => $admin->facility_id,
            ],
        ];
    }

    private function handleMemberLoginFailure(Member $member): void
    {
        $maxAttempts = config('hotel.account_lock.member_max_attempts', 5);
        $lockMinutes = config('hotel.account_lock.member_lock_minutes', 30);

        $failedCount = $member->login_failed_count + 1;
        $lockedUntil = null;

        if ($failedCount >= $maxAttempts) {
            $lockedUntil = now()->addMinutes($lockMinutes);
        }

        $member->update([
            'login_failed_count' => $failedCount,
            'locked_until' => $lockedUntil,
        ]);
    }

    private function handleAdminLoginFailure(AdminUser $admin): void
    {
        $maxAttempts = config('hotel.account_lock.admin_max_attempts', 3);
        $lockMinutes = config('hotel.account_lock.admin_lock_minutes', 60);

        $failedCount = $admin->login_failed_count + 1;
        $lockedUntil = null;

        if ($failedCount >= $maxAttempts) {
            $lockedUntil = now()->addMinutes($lockMinutes);
        }

        $admin->update([
            'login_failed_count' => $failedCount,
            'locked_until' => $lockedUntil,
        ]);
    }

    private function generateRefreshToken($user, string $guard): string
    {
        // リフレッシュトークンはTTLを長めに設定した別トークン
        $ttl = $guard === 'admin'
            ? config('hotel.jwt.admin_refresh_ttl', 1440)
            : config('jwt.refresh_ttl', 10080);

        return auth($guard)->setTTL($ttl)->login($user);
    }
}