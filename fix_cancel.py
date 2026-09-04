# -*- coding: utf-8 -*-
import re

with open("backend/app/Http/Controllers/Api/ReservationController.php", "r", encoding="utf-8") as f:
    content = f.read()

new_cancel = """    public function cancel(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        $this->authorize('cancel', $reservation);

        if ($reservation->status === 'cancelled') {
            return response()->json(['message' => 'Cette réservation est déjà annulée.'], 400);
        }

        DB::transaction(function () use ($reservation, $request) {
            $isOwner = $request->user()->isOwner();
            $reason = $isOwner ? 'Annulée par le gérant' : 'Annulée par le joueur';

            $reservation->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancel_reason' => $reason
            ]);

            if ($reservation->timeSlot) {
                $reservation->timeSlot->update(['status' => 'available']);
            }
        });

        return response()->json(['message' => 'Réservation annulée avec succès.']);
    }"""

pattern = re.compile(r'    public function cancel\(Request \$request, \$id\).*?(?=\n    public function|\Z)', re.DOTALL)
content = pattern.sub(new_cancel, content)

with open("backend/app/Http/Controllers/Api/ReservationController.php", "w", encoding="utf-8") as f:
    f.write(content)
print("Updated successfully")
