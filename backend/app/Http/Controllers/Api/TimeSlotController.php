<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\TimeSlot;
use App\Http\Requests\StoreTimeSlotRequest;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TimeSlotController extends Controller
{
    use AuthorizesRequests;

    /**
     * Liste des créneaux d'un terrain (filtrable par date)
     */
    public function index(Request $request, $fieldId)
    {
        $field = Field::findOrFail($fieldId);
        
        $query = $field->timeSlots();

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        } else {
            // Par défaut, à partir d'aujourd'hui
            $query->where('date', '>=', now()->toDateString());
        }

        $slots = $query->orderBy('date')->orderBy('start_time')->get();

        return response()->json($slots);
    }

    /**
     * Ajouter un créneau à un terrain
     */
    public function store(StoreTimeSlotRequest $request, $fieldId)
    {
        $field = Field::findOrFail($fieldId);
        $this->authorize('update', $field);

        // Vérifier si un créneau existe déjà sur cette plage (simple check)
        $exists = $field->timeSlots()
            ->where('date', $request->date)
            ->where('start_time', $request->start_time)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Un créneau existe déjà à cette date et heure.'], 409);
        }

        $slot = $field->timeSlots()->create([
            'date'       => $request->date,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
            'price'      => $request->price,
            'status'     => 'available'
        ]);

        return response()->json([
            'message' => 'Créneau ajouté avec succès.',
            'slot'    => $slot
        ], 201);
    }

    /**
     * Supprimer un créneau
     */
    public function destroy($fieldId, $slotId)
    {
        $field = Field::findOrFail($fieldId);
        $this->authorize('update', $field);

        $slot = $field->timeSlots()->findOrFail($slotId);

        if (!$slot->isAvailable()) {
            return response()->json(['message' => 'Impossible de supprimer un créneau déjà réservé.'], 403);
        }

        $slot->delete();

        return response()->json(['message' => 'Créneau supprimé avec succès.']);
    }
}
