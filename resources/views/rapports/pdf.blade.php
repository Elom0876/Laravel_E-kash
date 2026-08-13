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
    </style>
</head>
<body>
    <h1>Rapport de caisse — E-kash</h1>
    <p class="periode">Type : {{ ucfirst($typeMouvement) }} — Période : {{ $periode['debut'] }} au {{ $periode['fin'] }}</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Caisse</th>
                <th>Libellé</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($mouvements as $m)
            <tr>
                <td>{{ \Carbon\Carbon::parse($m['date'])->format('d/m/Y') }}</td>
                <td>{{ ucfirst($m['type']) }}</td>
                <td>{{ $m['caisse'] }}</td>
                <td>{{ $m['libelle'] }}</td>
                <td>{{ number_format($m['montant'], 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totaux">
        <p>Total entrées : {{ number_format($totalEntrees, 0, ',', ' ') }} FCFA</p>
        <p>Total sorties : {{ number_format($totalSorties, 0, ',', ' ') }} FCFA</p>
    </div>
</body>
</html>