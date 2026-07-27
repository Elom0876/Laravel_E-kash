<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Depense;
use App\Models\Demande;
use App\Models\Caisse;
use Carbon\Carbon;

class RapportController extends Controller
{
    // Rapport principal avec filtres
    public function index(Request $request)
    {
        $validated = $request->validate([
            'periode' => 'nullable|in:jour,semaine,mois,personnalise',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'entreprise_id' => 'nullable|exists:entreprises,id',
            'user_id' => 'nullable|exists:users,id',
            'statut' => 'nullable|in:en_attente,validee,rejetee,justifiee,cloturee',
        ]);

        [$debut, $fin] = $this->resoudrePeriode($validated);

        $query = Demande::with('user', 'entreprise', 'depense')
            ->whereBetween('created_at', [$debut, $fin]);

        if (!empty($validated['entreprise_id'])) {
            $query->where('entreprise_id', $validated['entreprise_id']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (!empty($validated['statut'])) {
            $query->where('statut', $validated['statut']);
        }

        $demandes = $query->latest()->get();

        return response()->json([
            'periode' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'total_demandes' => $demandes->count(),
            'total_montant_estime' => $demandes->sum('montant_estime'),
            'total_montant_reel' => $demandes->sum(fn($d) => $d->depense?->montant_reel ?? 0),
            'par_statut' => $demandes->groupBy('statut')->map->count(),
            'demandes' => $demandes,
        ]);
    }

    // Tableau de bord synthétique (utilisé sur le dashboard superviseur)
    public function tableauDeBord()
    {
        $caisses = Caisse::with('entreprise')->get();

        $debutMois = Carbon::now()->startOfMonth();
        $finMois = Carbon::now()->endOfMonth();

        $depensesMois = Depense::whereBetween('date_depense', [$debutMois, $finMois])->get();

        return response()->json([
            'caisses' => $caisses,
            'solde_total' => $caisses->sum('solde'),
            'sorties_mois' => $depensesMois->sum('montant_reel'),
            'en_attente_justification' => Demande::where('statut', 'validee')->count(),
        ]);
    }

    private function resoudrePeriode(array $validated): array
    {
        $periode = $validated['periode'] ?? 'mois';

        return match ($periode) {
            'jour' => [Carbon::today(), Carbon::today()->endOfDay()],
            'semaine' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'personnalise' => [
                Carbon::parse($validated['date_debut'] ?? Carbon::now()->startOfMonth()),
                Carbon::parse($validated['date_fin'] ?? Carbon::now())->endOfDay(),
            ],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }
}
