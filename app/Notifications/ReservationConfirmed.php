// app/Notifications/ReservationConfirmed.php
<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Reservation $reservation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->reservation;
        $firstDetail = $r->details->first();

        return (new MailMessage)
            ->subject("【予約確認】{$r->reservation_no} - " . config('app.name'))
            ->greeting("{$r->guest->last_name} {$r->guest->first_name} 様")
            ->line('ご予約ありがとうございます。以下の内容でご予約を承りました。')
            ->line("**予約番号:** {$r->reservation_no}")
            ->line("**チェックイン:** {$r->check_in_date->format('Y年m月d日')}")
            ->line("**チェックアウト:** {$r->check_out_date->format('Y年m月d日')}")
            ->line("**宿泊数:** {$r->nights}泊")
            ->line("**プラン:** " . ($firstDetail?->plan?->name_ja ?? ''))
            ->line("**合計金額:** ¥" . number_format($r->total_amount))
            ->line('キャンセルの場合は、キャンセルポリシーをご確認ください。')
            ->salutation('BMM Hotel');
    }
}