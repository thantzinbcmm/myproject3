// app/Notifications/ReservationCancelled.php
<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCancelled extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject("【キャンセル完了】{$r->reservation_no} - " . config('app.name'))
            ->greeting("{$r->guest->last_name} {$r->guest->first_name} 様")
            ->line('ご予約のキャンセルが完了しました。')
            ->line("**予約番号:** {$r->reservation_no}")
            ->line("**キャンセル日時:** {$r->cancelled_at?->format('Y年m月d日 H:i')}")
            ->when($r->cancel_fee > 0, fn($m) => $m->line("**キャンセル料:** ¥" . number_format($r->cancel_fee)))
            ->when($r->cancel_fee === 0, fn($m) => $m->line('キャンセル料は無料です。'))
            ->salutation('BMM Hotel');
    }
}