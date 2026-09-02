<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\TimeSlot;
use App\Models\Owner;
use App\Http\Requests\StoreReservationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReservationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Liste des rÃƒÂ©servations du joueur connectÃƒÂ©
     */
    public function index(Request $request)
    {
        $reservations = $request->user()->reservations()
            ->with(['field', 'timeSlot'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($reservations);
    }

    /**
     * Liste des rÃƒÂ©servations pour le loueur connectÃƒÂ©
     */
    public function ownerIndex(Request $request)
    {
        $owner = Owner::where('user_id', $request->user()->id)->first();
        if (!$owner) return response()->json([]);

        $reservations = Reservation::whereHas('field', function($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })
            ->with(['user', 'field', 'timeSlot', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($reservations);
    }

    /**
     * DÃƒÂ©tails d'une rÃƒÂ©servation
     */
    public function show(Request $request, $id)
    {
        $reservation = Reservation::with(['field', 'timeSlot', 'payment'])->findOrFail($id);
        $this->authorize('view', $reservation);

        return response()->json($reservation);
    }

    /**
     * CrÃƒÂ©er une rÃƒÂ©servation (Anti double-booking via transactions & locks)
     */
    public function store(StoreReservationRequest $request)
    {
        try {
            $reservation = DB::transaction(function () use ($request) {
                // VERROUILLAGE PESSIMISTE : on verrouille la ligne du crÃƒÂ©neau en BDD
                // Personne d'autre ne peut lire/modifier ce crÃƒÂ©neau jusqu'ÃƒÂ  la fin de la transaction.
                $slot = TimeSlot::where('id', $request->time_slot_id)->lockForUpdate()->firstOrFail();

                if (!$slot->isAvailable()) {
                    // Annulation de la transaction, le crÃƒÂ©neau est dÃƒÂ©jÃƒÂ  pris !
                    abort(409, 'Ce crÃƒÂ©neau n\'est plus disponible.');
                }

                $user = $request->user();
                $field = $slot->field;
                $price = $slot->effectivePrice();
                
                // Calcul de la commission du loueur (ex: 10%)
                $owner = $field->owner;
                $commission = $price * ($owner->commission_rate / 100);

                // 1. CrÃƒÂ©er la rÃƒÂ©servation en 'pending'
                $reservation = Reservation::create([
                    'user_id'      => $user->id,
                    'field_id'     => $field->id,
                    'time_slot_id' => $slot->id,
                    'status'       => 'pending',
                    'total_price'  => $price,
                    'commission'   => $commission,
                    'notes'        => $request->notes,
                ]);

                // On NE met PAS le crÃƒÂ©neau en 'reserved' ici car il n'a pas encore payÃƒÂ© l'acompte.
                // Il restera 'available' jusqu'au paiement.

                return $reservation;
            });

            return response()->json([
                'message'     => 'RÃƒÂ©servation mise en attente de paiement.',
                'reservation' => $reservation->load(['field', 'timeSlot'])
            ], 201);

        } catch (QueryException $e) {
            // SÃƒÂ©curitÃƒÂ© niveau 2 : La base de donnÃƒÂ©es a rejetÃƒÂ© l'insertion (Unique Index)
            return response()->json([
                'message' => 'Conflit de rÃƒÂ©servation. Le crÃƒÂ©neau a ÃƒÂ©tÃƒÂ© rÃƒÂ©servÃƒÂ© ÃƒÂ  la mÃƒÂªme milliseconde par un autre joueur.'
            ], 409);
        } catch (\Exception $e) {
            // RÃƒÂ©cupÃƒÂ¨re l'abort(409)
            if ($e->getCode() == 409 || $e->getMessage() == 'Ce crÃƒÂ©neau n\'est plus disponible.') {
                return response()->json(['message' => 'Ce crÃƒÂ©neau n\'est plus disponible.'], 409);
            }
            throw $e;
        }
    }

    /**
     * Annuler une rÃƒÂ©servation
     */
    public function cancel(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        $this->authorize('cancel', $reservation);

        if (!$reservation->canBeCancelled()) {
            return response()->json(['message' => 'Cette rÃƒÂ©servation ne peut plus ÃƒÂªtre annulÃƒÂ©e.'], 400);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancel_reason' => 'AnnulÃƒÂ©e par le joueur'
            ]);

            // LibÃƒÂ©rer le crÃƒÂ©neau
            $reservation->timeSlot->update(['status' => 'available']);
        });

        return response()->json(['message' => 'RÃƒÂ©servation annulÃƒÂ©e avec succÃƒÂ¨s.']);
    }

    /**
     * Confirmer une rÃƒÂ©servation (Action du Loueur)
     */
    public function confirm(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        $this->authorize('confirm', $reservation);

        if (!$reservation->isPending()) {
            return response()->json(['message' => 'La rÃƒÂ©servation n\'est pas en attente.'], 400);
        }

        $reservation->update(['status' => 'confirmed']);

        return response()->json([
            'message' => 'RÃƒÂ©servation confirmÃƒÂ©e.',
            'reservation' => $reservation
        ]);
    }
}



