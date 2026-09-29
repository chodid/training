---
titel: Lückenregister (Wissenslücken und Literaturbedarf)
bezug: docs/konzept/wissenskarten.md, Abschnitt 8 (8.1 Auslöser, 8.2 Klassifikation, 8.3 Schema)
stand: 2026-09-29
---

# Lückenregister

Einträge nach `docs/konzept/wissenskarten.md` 8.3. `LK`-IDs vergibt die planende Instanz bei der Einarbeitung eines Übergabedokuments (Vorschläge aus der Synthese als `LK-neu-<n>`). Die Klasse legt der Athlet fest (8.2).

Nicht beschaffte **Kernquellen** sind nach W-10 kein Lückenfall, sondern verzögern die Synthese; sie stehen in `docs/extraktion/README.md` (Zeile „Synthese startbereit“ je Zieldatei). Nicht beschaffte **optionale** Quellen werden erst als `typ: d` geführt, wenn eine Synthese-Sitzung sie für eine Lücke braucht (8.1 d).

<!-- Vorlage je Eintrag (wissenskarten.md 8.3):

- id: LK-01
  gebiet: t1-ausdauer
  karte: intensitaetsverteilung
  frage: "Intensitätsverteilung bei ≤ 4 Ausdauereinheiten/Woche (Freizeit), nicht Elite"
  typ: a | b | c | d | e        # nach 8.1
  klasse: blockierend | ergaenzend | akzeptiert
  entdeckt_in: synthese | ap07 | trainerchat
  entdeckt_am: 2026-10-xx
  betroffen: [T1-IV-03, RK2]     # Kernaussage-IDs, Regelkandidaten
  status: offen | recherche | vorgeschlagen | beschaffung | extraktion | nachtrag | geschlossen | akzeptiert
  vorhandene_option: L-T1-09     # Quelle aus optional/zurueckgestellt/„bei Bedarf“ (13.4), falls passend
  kandidaten: [neu-1]            # Arbeitsschlüssel aus dem Übergabedokument, bis L-ID vergeben
  quelle_neu: null               # L-ID nach Vergabe
  geschlossen_am: null
  geschlossen_mit: null          # Fassung der Zieldatei

-->

```yaml
[]
```
