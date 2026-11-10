<?php

namespace App\Notifications;

use App\Models\School;
use App\Models\SchoolUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffPortalInvite extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public School $school,
        public SchoolUser $schoolUser,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('login', [
            'invite' => $this->schoolUser->invite_token,
        ]);

        return (new MailMessage)
            ->subject("You're invited to {$this->school->name} on NexaEdu")
            ->greeting('Welcome to NexaEdu')
            ->line("You have been invited to access {$this->school->name}'s school portal.")
            ->action('Accept invitation', $url)
            ->line('Use this invitation to complete your access setup.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'school_id' => $this->school->id,
            'school_user_id' => $this->schoolUser->id,
        ];
    }
}
