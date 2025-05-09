<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ImportFailedNotification extends Notification
{
    use Queueable;

    protected string $gameInstanceId;
    protected string $roundName;
    protected string $errorMessage;

    /**
     * Create a new notification instance.
     *
     * @param string $gameInstanceId
     * @param string $roundName
     * @param string $errorMessage
     * @return void
     */
    public function __construct(string $gameInstanceId, string $roundName, string $errorMessage)
    {
        $this->gameInstanceId = $gameInstanceId;
        $this->roundName = $roundName;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Hall of Fame Generierung Failed')
            ->line("Die Hof Generierung '{$this->roundName}' (GameInstance ID: {$this->gameInstanceId}) ist fehlgeschlagen.")
            ->line('Fehlermeldung:')
            ->line($this->errorMessage)
            ->line('Bitte prüfe die Logs für weitere Details.');
    }

    /**
     * Get the array representation for database storage.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable): array
    {
        return [
            'game_instance_id' => $this->gameInstanceId,
            'round_name'       => $this->roundName,
            'error'            => $this->errorMessage,
            'timestamp'        => now(),
        ];
    }
}
