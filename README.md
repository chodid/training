# KI-Personal-Trainer

Privates Repo (D-23) für das System „KI-Personal-Trainer": PHP-Server (Webseite, MySQL, MCP-Endpunkt, OAuth) plus Dokumente (Konzept, Wissenskarten, Trainerregeln, Athletenprofil, Blockpläne).

Maßgeblich ist das Konzept: [`docs/konzept/konzept-ki-personal-trainer.md`](docs/konzept/konzept-ki-personal-trainer.md).

## Struktur

| Pfad | Inhalt | Arbeitspaket |
|---|---|---|
| `server/public/` | Docroot (einziger per HTTP erreichbarer Ordner) | AP-00 |
| `server/src/` | PHP-Quellcode | AP-00 ff. |
| `server/config/` | Konfiguration (ohne Secrets; `.env` liegt nur auf dem Server) | AP-00 |
| `server/migrations/` | Nummerierte Migrationen (D-20) | AP-00, AP-03 |
| `.github/workflows/` | Deploy-Workflow (D-17) | AP-00 |
| `docs/konzept/` | Konzeptdokument | – |
| `docs/pruefung/` | Prüfprotokoll (Abschnitt 16) | alle |
| `docs/wissen/` | Wissenskarten (Sammeldateien, 13.1) | AP-06 |
| `docs/literatur/` | Literatur-Volltexte als PDF, Open Access und gekauft (D-31); nie ins Projektwissen | AP-06 |
| `docs/regeln/` | Trainerregeln (Abschnitt 14) | AP-07 |
| `docs/athlet/` | Athletenprofil (D-15) | AP-08 |
| `docs/plaene/` | Blockpläne | AP-08 |
| `docs/branding/` | Branding-Dokument (D-19) | vor AP-04 |

Installation, Deployment und Restore-Anleitung folgen mit AP-00 bzw. AP-10.
