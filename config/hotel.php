// config/hotel.php
<?php

return [
    'facility_max_count' => env('FACILITY_MAX_COUNT', 5),
    'room_max_count' => env('ROOM_MAX_COUNT', 100),

    'supported_locales' => ['ja', 'en', 'zh-CN', 'zh-TW', 'ko', 'my'],
    'default_locale' => 'ja',

    'reservation_no_prefix' => 'BMM',

    'cancel_policy' => [
        'free_days_before' => 5,
        'half_charge_days_before' => 1,
        'half_charge_rate' => 50,
        'full_charge_rate' => 100,
    ],

    'change_limit_days' => 3,

    'channels' => [
        'DIRECT', 'PHONE', 'RAKUTEN', 'JALAN', 'AGENCY', 'CORPORATE', 'OTHER'
    ],

    'rate_limits' => [
        'login' => env('RATE_LIMIT_LOGIN', 5),
        'booking' => env('RATE_LIMIT_BOOKING', 10),
        'search' => env('RATE_LIMIT_SEARCH', 60),
        'api' => env('RATE_LIMIT_API', 100),
    ],

    'check_in_time' => '15:00:00',
    'check_out_time' => '11:00:00',

    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],

    'bcrypt_rounds' => 12,

    'admin_password_change_days' => 90,

    'account_lock' => [
        'member_max_attempts' => 5,
        'member_lock_minutes' => 30,
        'admin_max_attempts' => 3,
        'admin_lock_minutes' => 60,
    ],

    'backup' => [
        'retention_days' => env('BACKUP_RETENTION_DAYS', 7),
    ],
];