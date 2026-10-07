<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BrandApplicationMail extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $subject, public array $lines, public string $actionUrl, public string $actionLabel)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject)->greeting('Bonjour,');
        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        return $mail->action($this->actionLabel, $this->actionUrl)->salutation('L’équipe TexTileCycle');
    }
}
