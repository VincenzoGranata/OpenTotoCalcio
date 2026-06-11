<?php

function traduciSquadra($nome) {
    $mappa = [
        'Mexico' => 'Messico',
        'South Africa' => 'Sudafrica',
        'South Korea' => 'Corea del Sud',
        'Czech Republic' => 'Repubblica Ceca',
        'Canada' => 'Canada',
        'Bosnia and Herzegovina' => 'Bosnia ed Erzegovina',
        'United States' => 'Stati Uniti',
        'Paraguay' => 'Paraguay',
        'Haiti' => 'Haiti',
        'Scotland' => 'Scozia',
        'Australia' => 'Australia',
        'Turkey' => 'Turchia',
        'Brazil' => 'Brasile',
        'Morocco' => 'Marocco',
        'Qatar' => 'Qatar',
        'Switzerland' => 'Svizzera',
        'Ivory Coast' => 'Costa d\'Avorio',
        'Ecuador' => 'Ecuador',
        'Germany' => 'Germania',
        'CuraÃ§ao' => 'Curaçao',
        'Netherlands' => 'Paesi Bassi',
        'Japan' => 'Giappone',
        'Sweden' => 'Svezia',
        'Tunisia' => 'Tunisia',
        'Iran' => 'Iran',
        'New Zealand' => 'Nuova Zelanda',
        'Spain' => 'Spagna',
        'Cape Verde' => 'Capo Verde',
        'Belgium' => 'Belgio',
        'Egypt' => 'Egitto',
        'Saudi Arabia' => 'Arabia Saudita',
        'Uruguay' => 'Uruguay',
        'France' => 'Francia',
        'Senegal' => 'Senegal',
        'Iraq' => 'Iraq',
        'Norway' => 'Norvegia',
        'Argentina' => 'Argentina',
        'Algeria' => 'Algeria',
        'Austria' => 'Austria',
        'Jordan' => 'Giordania',
        'Portugal' => 'Portogallo',
        'Democratic Republic of the Congo' => 'Repubblica Democratica del Congo',
        'England' => 'Inghilterra',
        'Croatia' => 'Croazia',
        'Uzbekistan' => 'Uzbekistan',
        'Colombia' => 'Colombia',
        'Ghana' => 'Ghana',
        'Panama' => 'Panama',
        'South Korea' => 'Corea del Sud',
        'Curaçao' => 'Curaçao',
    ];

    return $mappa[$nome] ?? $nome;
}

function formatStato($stato, $minuto = null) {
    return match ($stato) {
        'scheduled' => 'Programmata',
        'ongoing' => $minuto ? $minuto . '\'' : 'In corso',
        'finished' => 'Terminata',
        default => $stato,
    };
}

function formatStatoBadge($stato, $minuto = null, $dataPartita = null) {
    if ($stato === 'finished') {
        return '<span class="badge bg-success">Terminata</span>';
    }
    if ($stato === 'ongoing') {
        $txt = $minuto ? $minuto . '\'' : 'In corso';
        return '<span class="badge bg-warning text-dark">' . $txt . '</span>';
    }
    $data = $dataPartita ? date('d/m H:i', strtotime($dataPartita)) : '';
    return '<span class="badge bg-secondary">' . $data . '</span>';
}

function formatoOraItaliana($dataPartita) {
    if (!$dataPartita) return '';
    $dt = new DateTime($dataPartita);
    return $dt->format('d/m/Y H:i');
}

function formatoOraBreve($dataPartita) {
    if (!$dataPartita) return '';
    $dt = new DateTime($dataPartita);
    return $dt->format('d/m H:i');
}
