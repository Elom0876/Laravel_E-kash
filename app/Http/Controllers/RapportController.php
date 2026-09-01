<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Demande;
use App\Models\Depense;
use App\Models\Caisse;
use App\Models\Approvisionnement;
use App\Models\Emprunt;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class RapportController extends Controller
{
    // Rapport principal avec filtres (liste des demandes)
    public function index(Request $request)
    {
        [$demandes, $debut, $fin] = $this->recupererDemandesFiltrees($request);

        return response()->json([
            'periode' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'total_demandes' => $demandes->count(),
            'total_montant_estime' => $demandes->sum('montant_estime'),
            'total_montant_reel' => $demandes->sum(fn($d) => $d->depense?->montant_reel ?? 0),
            'par_statut' => $demandes->groupBy('statut')->map->count(),
            'demandes' => $demandes,
        ]);
    }

    // Tableau de bord synthétique (dashboard superviseur)
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
            'en_attente_justification' => Demande::where('statut', 'acceptee')->count(),
        ]);
    }
    private function construireDetailSource(Approvisionnement $a): ?string
    {
        if ($a->source_type === 'directe') {
            return 'Virement bancaire (' . $a->compte_bancaire . ')';
        }

        if ($a->mode_reglement === 'cheque') {
            return 'Chèque n°' . $a->numero_cheque;
        }

        if ($a->mode_reglement === 'espece') {
            return 'Espèces déposées par ' . $a->depose_par;
        }

        return null;
    }

    // Rapport des mouvements de caisse (entrées / sorties / tout)
    public function mouvements(Request $request)
    {
        [$mouvements, $debut, $fin, $type] = $this->recupererMouvementsFiltres($request);

        return response()->json([
            'type_mouvement' => $type,
            'periode' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'total_entrees' => $mouvements->where('type', 'entree')->sum('montant'),
            'total_sorties' => $mouvements->where('type', 'sortie')->sum('montant'),
            'mouvements' => $mouvements,
        ]);
    }

    // Export PDF des mouvements
    public function exporterPdf(Request $request)
    {
        [$mouvements, $debut, $fin, $type] = $this->recupererMouvementsFiltres($request);

        $pdf = Pdf::loadView('rapports.pdf', [
            'mouvements' => $mouvements,
            'periode' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'typeMouvement' => $type,
            'totalEntrees' => $mouvements->where('type', 'entree')->sum('montant'),
            'totalSorties' => $mouvements->where('type', 'sortie')->sum('montant'),
        ]);

        return $pdf->download('rapport-ekash-' . now()->format('Y-m-d') . '.pdf');
    }

    // Export Excel des mouvements
    public function exporterExcel(Request $request)
    {
        [$mouvements] = $this->recupererMouvementsFiltres($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(['Date', 'Type', 'Catégorie', 'Caisse', 'Libellé', 'Source', 'Détail source', 'Montant'], null, 'A1');

        $ligne = 2;
        foreach ($mouvements as $m) {
            $sheet->fromArray([
                Carbon::parse($m['date'])->format('d/m/Y'),
                ucfirst($m['type']),
                $m['categorie'],
                $m['caisse'],
                $m['libelle'],
                $m['source_type'] ? ucfirst($m['source_type']) : '',
                $m['detail_source'] ?? '',
                $m['montant'],
            ], null, 'A' . $ligne);
            $ligne++;
        }

        $nomFichier = 'rapport-ekash-' . now()->format('Y-m-d') . '.xlsx';
        $cheminTemp = storage_path('app/' . $nomFichier);

        $writer = new Xlsx($spreadsheet);
        $writer->save($cheminTemp);

        return response()->download($cheminTemp)->deleteFileAfterSend(true);
    }

    // --- Méthodes privées communes ---

    private function recupererDemandesFiltrees(Request $request)
    {
        $validated = $request->validate([
            'periode' => 'nullable|in:jour,semaine,mois,personnalise',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'entreprise_id' => 'nullable|exists:entreprises,id',
            'user_id' => 'nullable|exists:users,id',
            'statut' => 'nullable|in:en_attente,acceptee,rejetee,preuve_envoyee,terminee,preuve_rejetee',
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

        return [$query->latest()->get(), $debut, $fin];
    }

    private function recupererMouvementsFiltres(Request $request): array
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

        if (in_array($type, ['sorties', 'tout'])) {
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
                    'source_type' => null,
                    'detail_source' => null,
                ]);

            $empruntsDonnes = Emprunt::with('caissePreteuse.entreprise', 'caisseEmprunteuse')
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
                    'source_type' => null,
                    'detail_source' => null,
                ]);

            $remboursementsPayes = Emprunt::with('caisseEmprunteuse.entreprise', 'caissePreteuse')
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
                    'source_type' => null,
                    'detail_source' => null,
                ]);

            $mouvements = $mouvements->merge($depenses)->merge($empruntsDonnes)->merge($remboursementsPayes);
        }

        if (in_array($type, ['entrees', 'tout'])) {
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
                    'source_type' => $a->source_type,
                    'detail_source' => $this->construireDetailSource($a),
                ]);
            $empruntsRecus = Emprunt::with('caisseEmprunteuse.entreprise', 'caissePreteuse')
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
                    'source_type' => null,
                    'detail_source' => null,
                ]);

            $remboursementsRecus = Emprunt::with('caissePreteuse.entreprise', 'caisseEmprunteuse')
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
                    'source_type' => null,
                    'detail_source' => null,
                ]);

            $mouvements = $mouvements->merge($approvisionnements)->merge($empruntsRecus)->merge($remboursementsRecus);
        }

        $mouvements = $mouvements->sortByDesc('date')->values();

        return [$mouvements, $debut, $fin, $type];
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
