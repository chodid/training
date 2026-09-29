#!/bin/bash
# Stellt die Steuerung der Extraktion (U2) in einer neuen Sitzung wieder her.
# Verwendung: bash docs/extraktion/steuerung/wiederherstellen.sh <neues-scratchpad-verzeichnis> [<datum JJJJ-MM-TT>]
# - kopiert docs/extraktion/steuerung/scratchpad/ nach <neues-scratchpad-verzeichnis>
# - ersetzt den alten absoluten Scratchpad-Pfad in allen Skripten und Auftragsdateien
# - setzt im Briefing das Extraktionsdatum (Front matter `datum`) auf <datum> (Standard: heute)
# - installiert poppler-utils (pdftotext, pdfinfo), falls nicht vorhanden
# - trägt den lokalen git-Ausschluss für laufende Quellordner ein
set -e
ALT=/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad
NEU=${1:?Ziel-Scratchpad angeben}; NEU=${NEU%/}
DATUM=${2:-$(date +%F)}
REPO=$(git -C "$(dirname "$0")" rev-parse --show-toplevel)
mkdir -p "$NEU"
cp -r "$REPO/docs/extraktion/steuerung/scratchpad/." "$NEU/"
grep -rl "$ALT" "$NEU" | xargs -r sed -i "s#$ALT#$NEU#g"
sed -i "s/datum: 2026-09-29/datum: $DATUM/" "$NEU/u2/brief.md"
chmod +x "$NEU"/*.sh
command -v pdftotext >/dev/null || { apt-get update -qq && apt-get install -y -qq poppler-utils >/dev/null; }
grep -qx 'docs/extraktion/\*/L-\*/' "$REPO/.git/info/exclude" || echo 'docs/extraktion/*/L-*/' >> "$REPO/.git/info/exclude"
# laufende Einheiten einer abgebrochenen Sitzung zurück an den Anfang der Warteschlange
if [ -s "$NEU/u2/running.txt" ]; then cat "$NEU/u2/running.txt" "$NEU/u2/queue.txt" > "$NEU/u2/q.tmp"; mv "$NEU/u2/q.tmp" "$NEU/u2/queue.txt"; : > "$NEU/u2/running.txt"; fi
echo "Steuerung wiederhergestellt in $NEU (Datum $DATUM); Warteschlange: $(wc -l < "$NEU/u2/queue.txt") Einheiten"
