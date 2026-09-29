---
titel: Trainerregeln
bezug: docs/konzept/konzept-ki-personal-trainer.md, Abschnitt 14 (Struktur), AP-07
stand: Vorabkapitel 9 „Übungskatalog“ (AP-16) und 10 „Übergabe, Revision, Bilanz und Zielklärung“ (AP-15), beide 2026-09-29; Kapitel 1–8 folgen in AP-07 (Projekt-Chat)
---

# Trainerregeln

Jede Regel mit `id`, `regel`, `quelle` bzw. `konfidenz` (Konzept Abschnitt 14). Die Kapitel 1–8 formuliert AP-07 im
Projekt-Chat aus. Die Kapitel 9 und 10 stehen vorab, weil die MCP-Tools des Übungskatalogs (AP-16) bzw. der Übergabe
(AP-15) sie voraussetzen; AP-07 übernimmt sie unverändert oder passt sie in Rücksprache mit dem Athleten an.

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

## 10. Übergabe, Revision, Bilanz und Zielklärung (AP-15, D-72 bis D-78)

Wortlaut aus `docs/konzept/blockbilanz.md` Abschnitt 8.2. Die Nummern R-UEB-01 bis R-UEB-06 (Übergabe) überschneiden
sich nicht mit R-UEB-10 bis R-UEB-14 (Kapitel 9, Übungskatalog). Bis AP-07 vorliegt, gelten zusätzlich die
Tool-Beschreibungen von `get_handover` und `write_block_review`.

```yaml
- id: R-UEB-01
  regel: Jede Planungssitzung beginnt mit get_handover; Fälligkeiten werden dem Athleten vor dem Wochenvorschlag genannt.
  konfidenz: Verfahrensregel
- id: R-UEB-02
  regel: Eine Zielklärung wird nie ohne den Athleten geschrieben; jede Entscheidung nennt mindestens eine verworfene Alternative oder „keine“.
  konfidenz: Verfahrensregel
- id: R-UEB-03
  regel: Die Bilanz bewertet jedes Ziel der Zielklärung; „nicht bewertbar“ braucht einen Grund (fehlender Test, fehlende Daten).
  konfidenz: Verfahrensregel
- id: R-UEB-04
  regel: Änderungen an Trainerregeln entstehen nur aus einer Bilanz (annahmen_geaendert mit vorschlag_trainerregel) oder einem Schmerzereignis, nie aus einer Wochenplanung.
  konfidenz: Verfahrensregel
- id: R-UEB-05
  regel: Revisionen ändern Belastung, nicht Ziele; wer Ziele ändern will, macht eine Zielklärung (auch außerplanmäßig, E-03).
  quelle: L-P03/L-P04 (Belastungssteuerung), Einschätzung
- id: R-UEB-06
  regel: Wochenpläne über das Blockende hinaus gibt es nicht ohne bestätigte Zielklärung des Folgeblocks (Tool lehnt ab, E-19).
  konfidenz: Verfahrensregel
```

Hinweise zur Umsetzung (Tools): `get_handover` liefert die Fälligkeiten unter `faellig` (Bilanz, Zielklärung, Revision
mit Grund und Satz). `write_block_review` schreibt Entwürfe (`status: entwurf`) und nach Bestätigung des Athleten
`status: bestaetigt` (`reason` ab Fassung 2); das Schema der Zielklärung verlangt je Entscheidung mindestens einen
Eintrag in `verworfen` (R-UEB-02), das der Bilanz je Ziel `bewertung` und `grund` (R-UEB-03). R-UEB-06 setzt
`write_week_plan` mit dem Fehler `blockwechsel_erforderlich` durch.
