<?php

declare(strict_types=1);

namespace Training\View;

/** Deutsche Beschriftungen und Icons für die Aufzählungen aus Abschnitt 7 und 7.2. */
final class Labels
{
    public const TYPES = [
        'ausdauer' => ['Ausdauer', 'run'],
        'kraft' => ['Kraft', 'barbell'],
        'klettern' => ['Klettern', 'mountain'],
        'haltung' => ['Haltung', 'yoga'],
        'mobilitaet' => ['Mobilität', 'stretching-2'],
        'ruhe' => ['Ruhe', 'zzz'],
    ];

    /** Status → [Text, Badge-Klasse, Icon|null] (Branding Abschnitt 4) */
    public const STATUS = [
        'geplant' => ['geplant', 'neutral', null],
        'erledigt' => ['erledigt', 'success', 'check'],
        'teilweise' => ['teilweise', 'warning', null],
        'ausgelassen' => ['ausgelassen', 'neutral', null],
        'verschoben' => ['verschoben', 'info', null],
    ];

    public const LOCATIONS = [
        'finger_ringband' => 'Finger, Ringband',
        'finger_gelenk' => 'Finger, Gelenk',
        'handgelenk' => 'Handgelenk',
        'ellbogen_medial' => 'Ellbogen innen',
        'ellbogen_lateral' => 'Ellbogen außen',
        'schulter' => 'Schulter',
        'nacken' => 'Nacken',
        'lws' => 'Lendenwirbelsäule',
        'huefte' => 'Hüfte',
        'knie' => 'Knie',
        'achillessehne' => 'Achillessehne',
        'wade' => 'Wade',
        'schienbein' => 'Schienbein',
        'fuss' => 'Fuß',
        'patellasehne' => 'Patellasehne',
        'sprunggelenk' => 'Sprunggelenk',
        'bws' => 'Brustwirbelsäule',
        'sonstiges' => 'Sonstiges',
    ];

    /** Warnzeichen im Morgen-Check-in (AP-12, E-07): Wert → Text. Gesetzt → abklaerung_empfohlen. */
    public const WARNINGS = [
        'sehne_scharfer_schmerz_kraftverlust' => 'Sehne: plötzlicher scharfer Schmerz, Bein gestreckt nicht anhebbar',
        'knie_schwellung_erguss' => 'Knie: Schwellung oder Erguss',
        'knie_ruhe_oder_nachtschmerz' => 'Knie: Ruhe- oder Nachtschmerz',
        'blockade_knie_oder_osg' => 'Blockade im Knie oder Sprunggelenk',
        'arm_ausstrahlung_kribbeln_schwaeche' => 'Arm: Ausstrahlung, Kribbeln oder Schwäche',
        'schwindel_sehstoerung_bei_nackenuebung' => 'Schwindel oder Sehstörung bei Nackenübung',
    ];

    /** Ampel des Morgentests → [Text, Badge-Klasse] */
    public const AMPEL = [
        'gruen' => ['grün', 'success'],
        'gelb' => ['gelb', 'warning'],
        'rot' => ['rot', 'error'],
        'keine_daten' => ['keine Daten', 'neutral'],
    ];

    public const SIDES = ['L' => 'Links', 'R' => 'Rechts', 'beide' => 'Beide', 'na' => 'n. z.'];
    public const SIDES_SHORT = ['L' => 'links', 'R' => 'rechts', 'beide' => 'beidseitig', 'na' => ''];
    public const TIMINGS = ['waehrend' => 'Während', 'danach' => 'Danach', 'naechster_morgen' => 'Nächster Morgen', 'ruhe' => 'In Ruhe'];
    public const DEVIATIONS = ['' => 'Keine Abweichung', 'zeit' => 'Zeit', 'ermuedung' => 'Ermüdung', 'schmerz' => 'Schmerz', 'wetter' => 'Wetter', 'sonstiges' => 'Sonstiges'];
    public const SPECIFICITY = ['spezifisch' => 'spezifisch', 'halbspezifisch' => 'halbspezifisch', 'unspezifisch' => 'unspezifisch'];
    public const BLOCK_KINDS = [
        'hangboard' => 'Hangboard',
        'campus' => 'Campus',
        'bouldern_volumen' => 'Bouldern Volumen',
        'bouldern_limit' => 'Bouldern Limit',
        'ausdauer_route' => 'Ausdauer Route',
        'technik' => 'Technik',
        'zugkraft' => 'Zugkraft',
        'antagonisten' => 'Antagonisten',
    ];
    public const GRIPS = ['halbkrimp' => 'Halbkrimp', 'offen' => 'offen', 'vollkrimp' => 'Vollkrimp', 'zange' => 'Zange'];

    /** Übungskatalog (AP-16): Kategorie → [Text, Icon] */
    public const EXERCISE_CATEGORIES = [
        'kraft' => ['Kraft', 'barbell'],
        'haltung' => ['Haltung', 'yoga'],
        'mobilitaet' => ['Mobilität', 'stretching-2'],
        'hangboard' => ['Hangboard', 'mountain'],
        'campus' => ['Campus', 'mountain'],
        'zugkraft' => ['Zugkraft', 'mountain'],
        'antagonisten' => ['Antagonisten', 'mountain'],
    ];

    public const EXERCISE_PATTERNS = [
        'druecken_horizontal' => 'Drücken horizontal', 'druecken_vertikal' => 'Drücken vertikal',
        'ziehen_horizontal' => 'Ziehen horizontal', 'ziehen_vertikal' => 'Ziehen vertikal',
        'knie_dominant' => 'kniedominant', 'huefte_dominant' => 'hüftdominant', 'rumpf' => 'Rumpf', 'schulter' => 'Schulter',
        'bws_haltung' => 'BWS/Haltung', 'unterarm_finger' => 'Unterarm/Finger', 'sprunggelenk_fuss' => 'Sprunggelenk/Fuß',
        'mobilitaet' => 'Mobilität', 'sonstiges' => 'Sonstiges',
    ];

    public const EQUIPMENT = [
        'koerpergewicht' => 'Körpergewicht', 'band' => 'Band', 'kettlebell' => 'Kettlebell', 'kurzhantel' => 'Kurzhantel',
        'langhantel' => 'Langhantel', 'klimmzugstange' => 'Klimmzugstange', 'ringe' => 'Ringe', 'hangboard' => 'Hangboard',
        'campusboard' => 'Campusboard', 'box' => 'Box', 'matte' => 'Matte', 'faszienrolle' => 'Faszienrolle', 'stab' => 'Stab',
        'gymnastikball' => 'Gymnastikball', 'gewichtsweste' => 'Gewichtsweste', 'sonstiges' => 'Sonstiges',
    ];

    /** Konfidenz → [Text, Badge-Klasse] */
    public const KONFIDENZ = [
        'hoch' => ['Konfidenz hoch', 'success'],
        'mittel' => ['Konfidenz mittel', 'info'],
        'niedrig' => ['Konfidenz niedrig', 'warning'],
        'einschaetzung' => ['Einschätzung', 'neutral'],
    ];
}
