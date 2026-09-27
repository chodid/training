# Arbeitsweise für Claude in diesem Repo

## Rückfragen
- Fragen immer mit kurzer Erklärung des Hintergrunds stellen.
- Antwortmöglichkeiten als klickbare Auswahl anbieten (AskUserQuestion), nicht als Freitext-Liste.
- Immer eine Empfehlung abgeben: empfohlene Option zuerst, mit „(Empfehlung)" markiert, und kurz begründen.
- Keine eigenen Annahmen bei Unklarheiten; größere Aufgaben vor der Umsetzung zusammenfassen und bestätigen lassen.

## Codearbeit
- Maßgeblich: `docs/konzept/konzept-ki-personal-trainer.md`. Arbeitspakete (AP) in der dort festgelegten Reihenfolge.
- Nach jedem AP bzw. jeder Änderung: Version in `server/src/App.php` hochstufen, `CHANGELOG.md`, README und Konzept (Statusblock, `probleme_loesungen`, Änderungsprotokoll) nachziehen, `docs/pruefung/pruefprotokoll.md` aktualisieren; Dokumente auf Konsistenz prüfen.
- Tests: `cd server && vendor/bin/phpunit` (Integrationstests mit `TEST_DB_*`-Variablen gegen MySQL/MariaDB).
- Änderungen per Pull Request nach `main`; ein Merge auf `main` deployt automatisch.
