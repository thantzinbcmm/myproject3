// routes/console.php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 毎日 03:00 JST にバックアップ実行
Schedule::command('backup:database')->dailyAt('03:00')->timezone('Asia/Tokyo');

// 毎日 02:00 に3ヶ月分の在庫を事前生成
Schedule::command('inventory:generate --months=3')->dailyAt('02:00')->timezone('Asia/Tokyo');

// 毎月1日に監査ログクリーンアップ
Schedule::command('audit:clean --days=365')->monthlyOn(1, '04:00')->timezone('Asia/Tokyo');