<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Presenze {{ $selectedYear }}-{{ $selectedMonth }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 2px; text-align: center; font-size: 8px; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .festivo { background-color: #fef2f2; color: #dc2626; }
        .fuori-assunzione { background-color: #f3f4f6; color: #9ca3af; }
        .malattia { background-color: #eff6ff; color: #1d4ed8; }
        .ferie { background-color: #fefce8; color: #92400e; }
        .presente { font-weight: bold; }
        .gruppo { background-color: #1f2937; color: white; font-weight: bold; padding: 5px; }
        .intestazione { font-size: 14px; font-weight: bold; text-align: center; padding: 10px; }
        .dipendente { text-align: left; font-weight: bold; }
        .attivita { color: #3b82f6; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">GESTIONE PRESENZE {{ strtoupper(Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('it')->isoFormat('MMMM YYYY')) }}</h2>
    
    <table>
        <thead>
            <tr>
                <th style="text-align:left; min-width:150px;">DIPENDENTE</th>
                @foreach($giorni as $giorno)
                    @php $isFestivo = in_array($giorno->format('m-d'), $festiviNazionali) || $giorno->isSunday(); @endphp
                    <th style="{{ $isFestivo ? 'background-color:#fef2f2; color:#dc2626;' : '' }} width:22px;">
                        {{ $giorno->format('j') }}
                        <br><small>{{ ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'][$giorno->dayOfWeek] }}</small>
                    </th>
                @endforeach
                <th style="min-width:40px;">N.GG</th>
            </tr>
        </thead>
        <tbody>
            @foreach($staffPerGruppo as $gruppoNome => $persone)
                <tr>
                    <td colspan="{{ count($giorni) + 2 }}" class="gruppo">
                        {{ $gruppoNome }}
                        <span style="color:#9ca3af; font-weight:normal;">
                            ({{ $persone->count() }} {{ $persone->count() == 1 ? 'dipendente' : 'dipendenti' }})
                        </span>
                    </td>
                </tr>
                @foreach($persone as $person)
                    @php
                        $personPresenze = $presenzeMatrix[$person->id_personale] ?? [];
                        $nGG = $totaliGiornate[$person->id_personale] ?? 0;
                    @endphp
                    <tr>
                        <td class="dipendente">{{ strtoupper($person->CognomePers) }} {{ $person->NomePers }}</td>
                        @foreach($giorni as $giorno)
                            @php
                                $dk = $giorno->format('Y-m-d');
                                $isFestivo = in_array($giorno->format('m-d'), $festiviNazionali) || $giorno->isSunday();
                                $cellData = $personPresenze[$dk] ?? null;
                                $isInAssunzione = $cellData['is_in_assunzione'] ?? false;
                                $causale = $cellData['causale'] ?? null;
                                $isPresent = $cellData['checked'] ?? false;
                                $hasActivity = $cellData['has_activity'] ?? false;
                                
                                $class = '';
                                $text = '';
                                
                                if ($isFestivo) {
                                    $class = 'festivo';
                                } elseif (!$isInAssunzione) {
                                    $class = 'fuori-assunzione';
                                } elseif ($causale === 'malattia') {
                                    $class = 'malattia';
                                    $text = 'M';
                                } elseif ($causale === 'ferie') {
                                    $class = 'ferie';
                                    $text = 'F';
                                } elseif ($causale === 'permesso') {
                                    $class = 'ferie';
                                    $text = 'P';
                                } elseif ($isPresent) {
                                    $class = 'presente';
                                    $text = 'X';
                                } elseif ($hasActivity) {
                                    $class = 'attivita';
                                    $text = '●';
                                }
                            @endphp
                            <td class="{{ $class }}">{{ $text }}</td>
                        @endforeach
                        <td style="font-weight:bold;">{{ $nGG }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>