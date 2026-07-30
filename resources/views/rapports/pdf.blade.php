{{-- resources/views/rapports/pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .periode { color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .totaux { margin-top: 16px; font-weight: bold; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Rapport de caisse — E-kash</h1>
    <p class="periode">Période : {{ $periode['debut'] }} au {{ $periode['fin'] }}</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Demandeur</th>
                <th>Entreprise</th>
                <th>Motif</th>
                <th>Montant estimé</th>
                <th>Montant réel</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($demandes as $d)
            <tr>
                <td>{{ $d->created_at->format('d/m/Y') }}</td>
                <td>{{ $d->user->name }}</td>
                <td>{{ $d->entreprise->nom }}</td>
                <td>{{ $d->motif }}</td>
                <td>{{ number_format($d->montant_estime, 0, ',', ' ') }} FCFA</td>
                <td>{{ $d->depense ? number_format($d->depense->montant_reel, 0, ',', ' ') . ' FCFA' : '—' }}</td>
                <td>{{ ucfirst($d->statut) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totaux">
        <p>Total demandes : {{ $demandes->count() }}</p>
        <p>Total montant estimé : {{ number_format($totalEstime, 0, ',', ' ') }} FCFA</p>
        <p>Total montant réel : {{ number_format($totalReel, 0, ',', ' ') }} FCFA</p>
    </div>
</body>
</html>