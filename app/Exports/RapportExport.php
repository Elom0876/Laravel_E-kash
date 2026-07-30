<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RapportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $demandes;

    public function __construct($demandes)
    {
        $this->demandes = $demandes;
    }

    public function collection()
    {
        return $this->demandes;
    }

    public function headings(): array
    {
        return ['Date', 'Demandeur', 'Entreprise', 'Motif', 'Montant estimé', 'Montant réel', 'Statut'];
    }

    public function map($demande): array
    {
        return [
            $demande->created_at->format('d/m/Y'),
            $demande->user->name,
            $demande->entreprise->nom,
            $demande->motif,
            $demande->montant_estime,
            $demande->depense?->montant_reel ?? '',
            ucfirst($demande->statut),
        ];
    }
}
