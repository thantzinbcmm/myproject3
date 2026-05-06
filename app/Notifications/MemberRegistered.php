// app/Notifications/MemberRegistered.php
<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Member $member
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('【会員登録完了】BMM Hotel 会員登録のご確認')
            ->greeting('会員登録ありがとうございます！')
            ->line("会員番号: {$this->member->member_number}")
            ->line('会員特典をご活用いただき、より快適なご滞在をお楽しみください。')
            ->action('マイページへ', config('app.frontend_url') . '/members/mypage')
            ->salutation('BMM Hotel');
    }
}