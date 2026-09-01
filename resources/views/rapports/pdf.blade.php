<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 35px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.5;
        }

        /* En-tête */
        .header {
            width: 100%;
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 14px;
            margin-bottom: 24px;
        }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; }
        .brand {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: 0.5px;
        }
        .brand-sub {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        .doc-title {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #1e3a5f;
        }
        .doc-meta {
            text-align: right;
            font-size: 9px;
            color: #6b7280;
            margin-top: 3px;
        }

        /* Cartes résumé */
.summary-table { width: 100%; margin-bottom: 24px; }
.summary-table > tbody > tr > td { padding: 0 6px; }

.summary-card {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
}
.summary-card-inner {
    width: 100%;
    border-collapse: collapse;
}
.summary-accent {
    width: 4px;
    padding: 0;
}
.summary-content {
    padding: 12px 14px;
}
.summary-label {
    font-size: 8.5px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #6b7280;
    margin-bottom: 4px;
}
.summary-value {
    font-size: 16px;
    font-weight: bold;
}
.accent-entree { background: #16a34a; }
.accent-sortie { background: #dc2626; }
.accent-net { background: #1e3a5f; }
.summary-entree .summary-value { color: #16a34a; }
.summary-sortie .summary-value { color: #dc2626; }
.summary-net .summary-value { color: #1e3a5f; }

        /* Tableau des mouvements */
        table.mouvements {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.mouvements thead th {
            background: #1e3a5f;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 8px;
            text-align: left;
        }
        table.mouvements tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #eef0f2;
            font-size: 10px;
        }
        table.mouvements tbody tr:nth-child(even) { background: #f8f9fb; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .badge-entree { background: #dcfce7; color: #16a34a; }
        .badge-sortie { background: #fee2e2; color: #dc2626; }

        .montant-entree { color: #16a34a; font-weight: bold; }
        .montant-sortie { color: #dc2626; font-weight: bold; }

        .detail-source {
            font-size: 8.5px;
            color: #6b7280;
            font-style: italic;
        }

        /* Pied de page */
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #9ca3af;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td width="55%">
                    <div class="brand">E-KASH</div>
                    <div class="brand-sub">GreenPay &amp; DA Digit All</div>
                </td>
                <td width="45%">
                    <div class="doc-title">Rapport de mouvements de caisse</div>
                    <div class="doc-meta">
                        Type : {{ ucfirst($typeMouvement) }}<br>
                        Période : {{ \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') }}<br>
                        Généré le {{ now()->format('d/m/Y à H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

 <table class="summary-table">
    <tr>
        <td width="33%">
            <table class="summary-card">
                <tr>
                    <td class="summary-accent accent-entree"></td>
                    <td class="summary-content summary-entree">
                        <div class="summary-label">Total entrées</div>
                        <div class="summary-value">{{ number_format($totalEntrees, 0, ',', ' ') }} FCFA</div>
                    </td>
                </tr>
            </table>
        </td>
        <td width="33%">
            <table class="summary-card">
                <tr>
                    <td class="summary-accent accent-sortie"></td>
                    <td class="summary-content summary-sortie">
                        <div class="summary-label">Total sorties</div>
                        <div class="summary-value">{{ number_format($totalSorties, 0, ',', ' ') }} FCFA</div>
                    </td>
                </tr>
            </table>
        </td>
        <td width="34%">
            <table class="summary-card">
                <tr>
                    <td class="summary-accent accent-net"></td>
                    <td class="summary-content summary-net">
                        <div class="summary-label">Solde net de la période</div>
                        <div class="summary-value">{{ number_format($totalEntrees - $totalSorties, 0, ',', ' ') }} FCFA</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

    @if ($mouvements->isEmpty())
        <div class="empty-state">Aucun mouvement enregistré sur cette période.</div>
    @else
        <table class="mouvements">
            <thead>
                <tr>
                    <th width="9%">Date</th>
                    <th width="8%">Type</th>
                    <th width="13%">Caisse</th>
                    <th width="30%">Libellé</th>
                    <th width="25%">Détail source</th>
                    <th width="15%" style="text-align:right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($mouvements as $m)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($m['date'])->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge {{ $m['type'] === 'entree' ? 'badge-entree' : 'badge-sortie' }}">
                            {{ $m['type'] === 'entree' ? 'Entrée' : 'Sortie' }}
                        </span>
                    </td>
                    <td>{{ $m['caisse'] }}</td>
                    <td>{{ $m['libelle'] }}</td>
                    <td class="detail-source">{{ $m['detail_source'] ?? '—' }}</td>
                    <td style="text-align:right;" class="{{ $m['type'] === 'entree' ? 'montant-entree' : 'montant-sortie' }}">
                        {{ $m['type'] === 'entree' ? '+' : '−' }} {{ number_format($m['montant'], 0, ',', ' ') }} FCFA
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        E-kash — Système de gestion de caisse GreenPay &amp; DA Digit All — Document généré automatiquement
    </div>

</body>
</html>