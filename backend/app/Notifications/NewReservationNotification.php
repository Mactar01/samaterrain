<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Reservation;
use App\Channels\FcmChannel;

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
        return ['database', FcmChannel::class];
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
        
        $creationTime = $this->reservation->created_at ? $this->reservation->created_at->format('H:i') : now()->format('H:i');
        
        return [
            'reservation_id' => $this->reservation->id,
            'message'        => "✅ $playerName a confirmé une réservation (faite à $creationTime) pour le $date à $time !",
            'amount'         => $this->reservation->total_price,
            'field_id'       => $this->reservation->timeSlot->field_id ?? null,
            'type'           => 'new_reservation'
        ];
    }

    /**
     * Format for FCM
     */
    public function toFcm(object $notifiable): array
    {
        $this->reservation->loadMissing('user', 'timeSlot.field');
        $playerName = $this->reservation->user->name ?? 'Un joueur';
        
        $creationTime = $this->reservation->created_at ? $this->reservation->created_at->format('H:i') : now()->format('H:i');
        
        return [
            'title' => 'Nouvelle Réservation !',
            'body' => "✅ À $creationTime, $playerName vient de réserver un créneau. Acompte payé : {$this->reservation->total_price} FCFA.",
            'data' => [
                'type' => 'new_reservation',
                'reservation_id' => (string) $this->reservation->id
            ]
        ];
    }
}
