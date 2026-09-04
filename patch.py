import os
path = r'c:\Users\Mactar SECK\Desktop\myTerrain\backend\app\Http\Controllers\Api\AdminController.php'
with open(path, 'r', encoding='utf-8') as f:
    c = f.read()

new_methods = '''
    /**
     * Statistiques detaillees pour les graphiques.
     */
    public function getDetailedStats(): JsonResponse
    {
        \ = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        
        // Reservatons par statut
        \ = \App\Models\Reservation::select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        // Revenu mensuel (6 derniers mois)
        if (\ === 'sqlite') {
            \ = \App\Models\Reservation::where('status', 'completed')
                ->selectRaw("strftime('%Y-%m', created_at) as month, SUM(total_price) as revenue")
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(6)
                ->get();
        } else {
            \ = \App\Models\Reservation::where('status', 'completed')
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_price) as revenue")
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(6)
                ->get();
        }

        return response()->json([
            'reservations_by_status' => \,
            'monthly_revenue' => \
        ]);
    }

    /**
     * Données de facturation et gains globaux.
     */
    public function getBilling(): JsonResponse
    {
        \ = \App\Models\Reservation::whereIn('status', ['confirmed', 'completed'])->sum('total_price');
        \ = \App\Models\Reservation::whereIn('status', ['confirmed', 'completed'])->sum('commission');
        
        // Payouts en attente (ce qu'on doit aux gerants) = Revenu Global - Commission
        // (En assumant que tous les paiements ont ete faits sur la plateforme)
        \ = \ - \;

        \ = [];
        if (class_exists(\App\Models\Payment::class)) {
            \ = \App\Models\Payment::with(['reservation.user', 'reservation.field.owner'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        }

        return response()->json([
            'global_revenue' => \,
            'total_commission' => \,
            'pending_payouts' => \,
            'recent_transactions' => \
        ]);
    }
'''

last_brace_index = c.rfind('}')
c = c[:last_brace_index] + new_methods + '\n}\n'

with open(path, 'w', encoding='utf-8') as f:
    f.write(c)
