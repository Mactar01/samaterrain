<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Reservation;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->isOwner()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ownerId = $user->owner->id;

        // Base query for confirmed/completed reservations belonging to this owner
        $baseQuery = Reservation::query()
            ->join('time_slots', 'reservations.time_slot_id', '=', 'time_slots.id')
            ->join('fields', 'time_slots.field_id', '=', 'fields.id')
            ->where('fields.owner_id', $ownerId)
            ->whereIn('reservations.status', ['confirmed', 'completed']);

        // 1. Total Reservations
        $totalReservations = (clone $baseQuery)->count();

        // 2. Total Revenue
        $totalRevenue = (clone $baseQuery)->sum('reservations.total_price');

        // 3. Revenue by month
        $driver = DB::connection()->getDriverName();
        $monthlyQuery = clone $baseQuery;
        
        if ($driver === 'sqlite') {
            $monthlyRevenue = $monthlyQuery
                ->selectRaw("strftime('%Y-%m', time_slots.date) as month, SUM(reservations.total_price) as revenue")
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(6)
                ->get();
        } else {
            $monthlyRevenue = $monthlyQuery
                ->selectRaw("DATE_FORMAT(time_slots.date, '%Y-%m') as month, SUM(reservations.total_price) as revenue")
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(6)
                ->get();
        }

        // 4. Revenue by field
        $revenueByField = (clone $baseQuery)
            ->selectRaw("fields.name, SUM(reservations.total_price) as revenue")
            ->groupBy('fields.id', 'fields.name')
            ->get();

        return response()->json([
            'total_reservations' => $totalReservations,
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'revenue_by_field' => $revenueByField
        ]);
    }
}
