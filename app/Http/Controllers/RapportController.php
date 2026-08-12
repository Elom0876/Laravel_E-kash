<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Depense;
use App\Models\Approvisionnement;
use App\Models\Emprunt;

class RapportController extends Controller
{
    public function mouvements(Request $request)
    {
        $validated = $request->validate([
            'type_mouvement' => 'nullable|in:entrees,sorties,tout',
            'periode' => 'nullable|in:jour,semaine,mois,personnalise',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'entreprise_id' => 'nullable|exists:entreprises,id',
            'caisse_id' => 'nullable|exists:caisses,id',
        ]);

        $type = $validated['type_mouvement'] ?? 'tout';
        [$debut, $fin] = $this->resoudrePeriode($validated);

        $mouvements = collect();

        // --- SORTIES ---
        if (in_array($type, ['sorties', 'tout'])) {
            // Dépenses (demandes acceptées / clôturées)
            $depenses = Depense::with('demande.user', 'caisse.entreprise')
                ->whereBetween('date_depense', [$debut, $fin])
                ->when($request->caisse_id, fn($q) => $q->where('caisse_id', $request->caisse_id))
                ->when($request->entreprise_id, fn($q) => $q->whereHas('caisse', fn($q2) => $q2->where('entreprise_id', $request->entreprise_id)))
                ->get()
                ->map(fn($d) => [
                    'type' => 'sortie',
                    'categorie' => 'depense',
                    'date' => $d->date_depense,
                    'caisse' => $d->caisse->nom,
                    'montant' => $d->montant_reel,
                    'libelle' => $d->demande->motif . ' — ' . $d->demande->user->name,
                ]);

            // Emprunts donnés (caisse prêteuse)
            $empruntsDonnes = Emprunt::with('caissePreteuse.entreprise')
                ->whereBetween('date_emprunt', [$debut, $fin])
                ->when($request->caisse_id, fn($q) => $q->where('caisse_preteuse_id', $request->caisse_id))
                ->when($request->entreprise_id, fn($q) => $q->whereHas('caissePreteuse', fn($q2) => $q2->where('entreprise_id', $request->entreprise_id)))
                ->get()
                ->map(fn($e) => [
                    'type' => 'sortie',
                    'categorie' => 'emprunt_donne',
                    'date' => $e->date_emprunt,
                    'caisse' => $e->caissePreteuse->nom,
                    'montant' => $e->montant,
                    'libelle' => 'Emprunt vers ' . $e->caisseEmprunteuse->nom . ' — ' . $e->motif,
                ]);

            // Remboursements payés (caisse emprunteuse qui rembourse)
            $remboursementsPayes = Emprunt::with('caisseEmprunteuse.entreprise')
                ->whereNotNull('date_remboursement')
                ->whereBetween('date_remboursement', [$debut, $fin])
                ->when($request->caisse_id, fn($q) => $q->where('caisse_emprunteuse_id', $request->caisse_id))
                ->when($request->entreprise_id, fn($q) => $q->whereHas('caisseEmprunteuse', fn($q2) => $q2->where('entreprise_id', $request->entreprise_id)))
                ->get()
                ->map(fn($e) => [
                    'type' => 'sortie',
                    'categorie' => 'remboursement_paye',
                    'date' => $e->date_remboursement,
                    'caisse' => $e->caisseEmprunteuse->nom,
                    'montant' => $e->montant,
                    'libelle' => 'Remboursement à ' . $e->caissePreteuse->nom,
                ]);

            $mouvements = $mouvements->merge($depenses)->merge($empruntsDonnes)->merge($remboursementsPayes);
        }

        // --- ENTRÉES ---
        if (in_array($type, ['entrees', 'tout'])) {
            // Approvisionnements
            $approvisionnements = Approvisionnement::with('caisse.entreprise')
                ->whereBetween('date_approvisionnement', [$debut, $fin])
                ->when($request->caisse_id, fn($q) => $q->where('caisse_id', $request->caisse_id))
                ->when($request->entreprise_id, fn($q) => $q->whereHas('caisse', fn($q2) => $q2->where('entreprise_id', $request->entreprise_id)))
                ->get()
                ->map(fn($a) => [
                    'type' => 'entree',
                    'categorie' => 'approvisionnement',
                    'date' => $a->date_approvisionnement,
                    'caisse' => $a->caisse->nom,
                    'montant' => $a->montant,
                    'libelle' => $a->motif ?? 'Approvisionnement',
                ]);

            // Emprunts reçus (caisse emprunteuse)
            $empruntsRecus = Emprunt::with('caisseEmprunteuse.entreprise')
                ->whereBetween('date_emprunt', [$debut, $fin])
                ->when($request->caisse_id, fn($q) => $q->where('caisse_emprunteuse_id', $request->caisse_id))
                ->when($request->entreprise_id, fn($q) => $q->whereHas('caisseEmprunteuse', fn($q2) => $q2->where('entreprise_id', $request->entreprise_id)))
                ->get()
                ->map(fn($e) => [
                    'type' => 'entree',
                    'categorie' => 'emprunt_recu',
                    'date' => $e->date_emprunt,
                    'caisse' => $e->caisseEmprunteuse->nom,
                    'montant' => $e->montant,
                    'libelle' => 'Emprunt reçu de ' . $e->caissePreteuse->nom . ' — ' . $e->motif,
                ]);

            // Remboursements reçus (caisse prêteuse)
            $remboursementsRecus = Emprunt::with('caissePreteuse.entreprise')
                ->whereNotNull('date_remboursement')
                ->whereBetween('date_remboursement', [$debut, $fin])
                ->when($request->caisse_id, fn($q) => $q->where('caisse_preteuse_id', $request->caisse_id))
                ->when($request->entreprise_id, fn($q) => $q->whereHas('caissePreteuse', fn($q2) => $q2->where('entreprise_id', $request->entreprise_id)))
                ->get()
                ->map(fn($e) => [
                    'type' => 'entree',
                    'categorie' => 'remboursement_recu',
                    'date' => $e->date_remboursement,
                    'caisse' => $e->caissePreteuse->nom,
                    'montant' => $e->montant,
                    'libelle' => 'Remboursement de ' . $e->caisseEmprunteuse->nom,
                ]);

            $mouvements = $mouvements->merge($approvisionnements)->merge($empruntsRecus)->merge($remboursementsRecus);
        }

        $mouvements = $mouvements->sortByDesc('date')->values();

        return response()->json([
            'type_mouvement' => $type,
            'periode' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'total_entrees' => $mouvements->where('type', 'entree')->sum('montant'),
            'total_sorties' => $mouvements->where('type', 'sortie')->sum('montant'),
            'mouvements' => $mouvements,
        ]);
    }

    private function resoudrePeriode(array $validated): array
    {
        $now = now();
        switch ($validated['periode'] ?? 'jour') {
            case 'jour':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'semaine':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'mois':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            case 'personnalise':
                return [
                    $validated['date_debut'] ? \Carbon\Carbon::parse($validated['date_debut'])->startOfDay() : $now->copy()->startOfDay(),
                    $validated['date_fin'] ? \Carbon\Carbon::parse($validated['date_fin'])->endOfDay() : $now->copy()->endOfDay(),
                ];
            default:
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        }
    }
}
