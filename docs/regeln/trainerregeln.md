---
titel: Trainerregeln
bezug: docs/konzept/konzept-ki-personal-trainer.md, Abschnitt 14 (Struktur), AP-07
stand: Vorabkapitel 9 „Übungskatalog“ (AP-16, 2026-09-29); Kapitel 1–8 folgen in AP-07 (Projekt-Chat)
---

# Trainerregeln

Jede Regel mit `id`, `regel`, `quelle` bzw. `konfidenz` (Konzept Abschnitt 14). Die Kapitel 1–8 formuliert AP-07 im
Projekt-Chat aus. Kapitel 9 steht vorab, weil die MCP-Tools des Übungskatalogs (AP-16) es voraussetzen; AP-07 übernimmt
es unverändert oder passt es in Rücksprache mit dem Athleten an.

## 9. Übungskatalog (AP-16, D-64 bis D-69)

Wortlaut aus `docs/konzept/uebungskatalog.md` Abschnitt 8.

```yaml
- id: R-UEB-10
  regel: Vor jeder Wochenplanung wird für jede Kraft-, Haltungs-, Mobilitäts- und Kletterübung (hangboard, campus, zugkraft, antagonisten) find_exercise aufgerufen; vorhandene oder ähnliche Einträge werden verwendet, Varianten über variant_of angelegt.
  konfidenz: Verfahrensregel
- id: R-UEB-11
  regel: Neue Übungen werden nach Bestätigung des Wochenplans mit upsert_exercise angelegt; hinweis_chat wird dem Athleten gezeigt. Warnungen aus write_week_plan werden im selben Chat aufgelöst (Anlegen oder begründeter Freitext).
  konfidenz: Verfahrensregel
- id: R-UEB-12
  regel: Jeder Katalogeintrag nennt mindestens eine Quelle und eine Konfidenz; Ausführung und Fehlerquellen stammen aus der Wissensbasis oder sind als Einschätzung gekennzeichnet; Links werden nicht erfunden (nur URLs, die die Instanz tatsächlich kennt oder nachgeschlagen hat – der Server prüft die Erreichbarkeit, nicht den Inhalt).
  quelle: D-13 (Zitierregel), D-62 e
- id: R-UEB-13
  regel: Der Abschnitt „vorsicht“ nennt bei Übungen mit Bezug zu Knie, Sprunggelenk oder Fingern die relevante Reha-Regel (Block R) und die Schmerzgrenze.
  quelle: Block R (L-R-02, L-R-13), Abschnitt 14 Schmerzregeln
- id: R-UEB-14
  regel: Die Einheit beschreibt nur Dosierung (Sätze, Wiederholungen, Last, Tempo, Pause) und einen kurzen Hinweis; Ausführungsdetails gehören in den Katalog.
  konfidenz: Verfahrensregel
```

Hinweise zur Umsetzung (Tools): Nach R-UEB-11 legt `upsert_exercise` an und liefert `hinweis_chat`; Warnungen aus
`write_week_plan` erscheinen als `warnungen` mit `hinweis_warnungen`. Für R-UEB-12 prüft der Server nur die
Erreichbarkeit (Videos über oEmbed); ein defekter Link setzt die Übung auf „Links prüfen“.
