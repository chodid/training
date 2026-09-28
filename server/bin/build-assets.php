<?php

declare(strict_types=1);

/**
 * Kopiert die Gestaltungsgrundlage aus docs/branding/ nach server/public/assets/ (Branding-Dokument
 * Abschnitt 7.1) und das Athletenprofil aus docs/athlet/ nach server/resources/athlet/ (AP-05). Einzige Quelle bleibt docs/branding/; public/assets/ ist ein Build-Ergebnis und nicht im Repo.
 * Aufruf (aus dem Repo-Wurzelverzeichnis oder server/): php server/bin/build-assets.php
 */

$repo = dirname(__DIR__, 2);
$src = $repo . '/docs/branding';
$dst = dirname(__DIR__) . '/public/assets';

$copies = [
    'chadid-design-system/styles.css' => 'ds/styles.css',
    'chadid-design-system/tokens/*.css' => 'ds/tokens/',
    'chadid-design-system/fonts/*' => 'ds/fonts/',
    'mockups/app.css' => 'app.css',
    'mockups/icons/*.svg' => 'icons/',
    'mockups/icons/LICENSE' => 'icons/LICENSE',
    'chadid-design-system/assets/logo/lama-symbol-kopf.svg' => 'lama-kopf.svg',
];

if (!is_dir($src)) {
    fwrite(STDERR, "Quelle fehlt: $src\n");
    exit(1);
}

$count = 0;
foreach ($copies as $from => $to) {
    $files = glob($src . '/' . $from) ?: [];
    if ($files === []) {
        fwrite(STDERR, "Keine Dateien für $from\n");
        exit(1);
    }
    foreach ($files as $file) {
        $target = str_ends_with($to, '/') ? $dst . '/' . $to . basename($file) : $dst . '/' . $to;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true)) {
            fwrite(STDERR, "Ordner nicht anlegbar: " . dirname($target) . "\n");
            exit(1);
        }
        if (!copy($file, $target)) {
            fwrite(STDERR, "Kopieren fehlgeschlagen: $file\n");
            exit(1);
        }
        $count++;
    }
}

echo "Assets: $count Dateien nach public/assets kopiert.\n";

// Athletenprofil für das MCP-Tool get_athlete_profile (AP-05): docs/athlet/*.md → server/resources/athlet/ (außerhalb des Docroots).
$profileDir = dirname(__DIR__) . '/resources/athlet';
if (!is_dir($profileDir) && !mkdir($profileDir, 0755, true)) {
    fwrite(STDERR, "Ordner nicht anlegbar: $profileDir\n");
    exit(1);
}
foreach (glob($profileDir . '/*.md') ?: [] as $old) {
    unlink($old);
}
$profiles = glob($repo . '/docs/athlet/*.md') ?: [];
foreach ($profiles as $file) {
    copy($file, $profileDir . '/' . basename($file));
}
echo 'Athletenprofil: ' . count($profiles) . " Datei(en) nach resources/athlet kopiert.\n";
