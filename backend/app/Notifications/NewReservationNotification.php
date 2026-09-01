<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Reservation;

class NewReservationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $reservation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // En prod on pourrait ajouter 'mail' ou 'firebase'
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->reservation->loadMissing('user', 'timeSlot.field');
        
        $playerName = $this->reservation->user->name ?? 'Un joueur';
        $time = $this->reservation->timeSlot->start_time ?? '';
        $date = \Carbon\Carbon::parse($this->reservation->timeSlot->date)->format('d/m/Y');
        
        return [
            'reservation_id' => $this->reservation->id,
            'message'        => "✅ $playerName a confirmé une réservation pour le $date à $time !",
            'amount'         => $this->reservation->total_price,
            'field_id'       => $this->reservation->timeSlot->field_id ?? null,
            'type'           => 'new_reservation'
        ];
    }
}
