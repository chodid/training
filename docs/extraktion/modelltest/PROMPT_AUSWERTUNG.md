# Prompt Auswertungs-Instanz

Alles ab der Linie einfügen. Empfohlen ist ein Modell, das PDF-Seiten als Bild lesen kann, mit hoher Denktiefe.

---

**Modelltest Extraktion – blinde Auswertung**

Diese Nachricht ist nur die Vorbereitung. **Lies jetzt noch keine Datei, führe keine Befehle aus und lade nichts.** Antworte nur mit „Bereit – Auswertung“. Erst auf meine Nachricht „Go“ beginnst du mit Phase A.

**Worum es geht**

Mehrere Instanzen haben dieselben 12 Buchkapitel bzw. Artikel nach demselben Briefing extrahiert. Jede Instanz hat einen Lauf-Buchstaben: `V`, `K`, `T`, `F`, `R`. Du bewertest alle Läufe **blind** am Original. Danach legst du mir als Athleten ausgewählte Stellen vor; meine Entscheidungen bilden den Goldstandard. Am Ende schreibst du einen Bericht mit einer Empfehlung je Quellentyp. Welcher Lauf zu welchem Modell gehört, erfährst du erst am Ende von mir.

**Wo was liegt** (GitHub-Repo `chodid/training`, Grundlage Branch `claude/ecstatic-johnson-g3mxel`)

- Aufbau, Ablauf und **Bewertungsschema** (Urteile, Kennzahlen, Maßstab): `docs/extraktion/modelltest/README.md`, vor allem Abschnitte 4 und 5.
- Regeln, nach denen extrahiert wurde: `docs/extraktion/modelltest/briefing.md`.
- Einheiten mit Kategorie (erzählend, Scan, dichte Tabellen, Review mit vielen Kennzahlen): `docs/extraktion/modelltest/einheiten.md`. Dazu die Aufträge unter `docs/extraktion/modelltest/auftraege/`, mit Hinweisen zum Seitenbezug.
- Originale: die Eingabedateien unter `docs/literatur/` (Pfade in `einheiten.md`).
- Läufe: `docs/extraktion/modelltest/lauf-<X>/<EINHEIT>.md`. Sie können auf verschiedenen Remote-Branches liegen.
- Deine Ablage: `docs/extraktion/modelltest/auswertung/`.

**Blindheit (verbindlich):**
- Keine Commit-Historie und keine Commit-Metadaten lesen (`git log`, `git blame`, `git show` usw.). Nur Dateiinhalte verwenden.
- Nichts unter `docs/extraktion/` außerhalb von `modelltest/` lesen. Nichts unter `docs/wissen/`, `docs/konzept/` und keine Chatprotokolle anderer Instanzen.
- Keine Vermutungen über die Modelle anstellen oder notieren.

**Phase A – Referenz und Urteile (nach „Go“)**

1. Holen und einsammeln:
   - `git fetch origin` (alle Branches).
   - Grundlage `origin/claude/ecstatic-johnson-g3mxel` in deinen Arbeitsbranch übernehmen.
   - Auf allen Remote-Branches nach `docs/extraktion/modelltest/lauf-*/` suchen, z. B. `git ls-tree -r --name-only <branch> docs/extraktion/modelltest/`. Fehlende Läufe mit `git checkout <branch> -- docs/extraktion/modelltest/lauf-<X>` übernehmen.
   - Erwartet werden 5 Läufe × 12 Dateien. Fehlt etwas, mir melden und auf meine Antwort warten.
   - `poppler-utils` (pdftotext, pdftoppm) bei Bedarf installieren.
2. README (Abschnitte 4, 5), Briefing und `einheiten.md` vollständig lesen.
3. Formprüfung je Datei nach README 5, Punkt 7. Ergebnis nach `auswertung/form.md`.
4. Je Einheit, **mit frischem Kontext je Einheit** (Unteragent mit demselben Modell wie du, höchstens 4 gleichzeitig; sonst nacheinander):
   1. **Original vollständig lesen**, PDF-Seiten als Bild. Die gedruckten Seitenzahlen bestimmen.
   2. **Zusammenführen:** Alle Aussagen, Zahlen und Protokolle, Definitionen sowie markierten Stellen (`unsicher`, Offene Stellen, Lücken) aller 5 Läufe in inhaltlich gleiche Einträge bündeln. Jeder Eintrag bekommt eine Referenz-ID `<EINHEIT>-R<nn>` mit Kategorie (`zahl_protokoll`, `kernaussage`, `nebenaussage`, `definition`).
   3. **Am Original prüfen:** Je Eintrag und je Lauf, der ihn enthält, ein Urteil nach README 5: `korrekt`, `ungenau`, `stelle_falsch`, `zahl_falsch`, `sinn_falsch`, `nicht_im_text` oder `nicht_pruefbar`. Dazu die richtige Fassung mit gedruckter Seite und deine Sicherheit (`hoch`, `mittel`, `niedrig`).
   4. **Eigene Durchsicht:** Das Original Seite für Seite nach Zahlen, Protokollen, Definitionen und Kernaussagen durchgehen, die **kein** Lauf enthält. Sie als Referenzeinträge mit Herkunft „nur Referenz“ aufnehmen.
   5. **Fallen:** Widersprüche und Fehler in der Quelle selbst feststellen (Text gegen Tabelle oder Abbildung, Summen, Vorzeichen, falsche Verweise), jeweils am Original bestätigt. Je Lauf festhalten, ob er sie markiert hat.
   6. Ergebnis nach `auswertung/referenz/<EINHEIT>.md`: Referenzeinträge mit richtiger Fassung, Seite, Urteilen je Lauf, Sicherheit und Fallen.
   7. Je Urteil eine Zeile an `auswertung/urteile.csv` anhängen, mit den Spalten `einheit;kategorie_einheit;ref_id;kategorie_eintrag;seite;lauf;enthalten;urteil;sicherheit`. Bei `enthalten=nein` bleibt das Urteil leer.
5. Für jeden Eintrag eine **Strittigkeit** berechnen und in `auswertung/urteile.csv` bzw. die Referenz übernehmen. Sie ist hoch, wenn die Läufe sich in Zahl, Sinn oder Stelle widersprechen, wenn deine Sicherheit `niedrig` ist oder wenn nur 1–2 Läufe den Eintrag enthalten und du ihn für korrekt hältst.
6. Kurz im Chat melden: Zahl der Einträge und Urteile je Einheit, auffällige Probleme. Dann ohne weitere Rückfrage zu Phase B.

**Phase B – Goldstandard mit dem Athleten**

1. **30 Stellen auswählen:**
   - 15 zufällig, geschichtet: mindestens 3 je Kategorie der Einheiten; nur Einträge mit Zahl oder Kernaussage. Den Zufallsstartwert in der Datei notieren.
   - 15 mit der höchsten Strittigkeit, möglichst über die Einheiten verteilt.
2. `auswertung/goldstandard.md` anlegen. Je Stelle:
   - Nummer, Einheit, Pfad des Original-PDFs mit **PDF-Seite der Datei** und gedruckter Seite, eine kurze Frage.
   - Die **konkurrierenden Fassungen** als „Fassung 1, 2, …“, in zufälliger Reihenfolge, ohne Lauf-Kennung. Fehlt einem Teil der Läufe der Eintrag, ist eine Fassung „nicht erwähnt“.
   - **Dein eigenes Urteil steht nicht in dieser Datei.** Es bleibt in der Referenz.
3. Mir die Stellen **in Blöcken zu 5** im Chat vorlegen. Ich prüfe am PDF und antworte je Stelle mit „Fassung n stimmt“, „keine stimmt: <richtig ist …>“ oder „nicht entscheidbar“. Hast du ein Werkzeug für Auswahlfragen, nutze es, mit den Fassungen als Optionen.
4. Meine Antworten in `goldstandard.md` eintragen.

**Phase C – Abgleich und Bericht**

1. Meine Entscheidungen mit deinen Urteilen vergleichen und die Übereinstimmung angeben.
   - Liegt sie unter 90 %, die Fehlerart bestimmen und die betroffenen Einträge derselben Art in allen Einheiten nachprüfen.
   - Abweichungen und Korrekturen dokumentieren in `auswertung/abgleich.md`.
2. Kennzahlen nach README 5 je Lauf, gesamt und je Kategorie, berechnen, **einschließlich der Hochrechnung der Kette Extraktion → Gegenprüfung (Kennzahlen 9–12)**. Den Prüfumfang je Einheit und Lauf aus der jeweiligen Extraktionsdatei bestimmen; den Zufallsanteil als Erwartungswert rechnen. Ergebnis nach `auswertung/kennzahlen.md`, mit Tabellen.
3. Mich fragen: „Bitte Zuordnung der Lauf-Buchstaben zu den Modellen und die Laufprotokolle (Tokens, Dauer) nennen.“ Erst danach entblinden.
4. Bericht nach `auswertung/bericht.md`:
   - Ergebnis je Kategorie.
   - Streuung zwischen zwei Läufen desselben Modells, falls vorhanden.
   - Kosten.
   - **Empfehlung je Quellentyp** (Modell und Denktiefe) nach dem Maßstab in README 5.
   - Hinweis, ob für ein günstigeres Modell eine echte Gegenprüfung (Stufe 2) sinnvoll wäre, weil es in der Hochrechnung knapp liegt.
   - Grenzen des Tests: kleine Stichprobe, ein Lauf je Modell, Auswertung durch ein Modell.
   - Die Entscheidung trifft der Athlet.
5. Committen: nur `docs/extraktion/modelltest/auswertung/` und die eingesammelten `lauf-*`-Ordner. Nachricht `docs(modelltest): Auswertung`. Push wenn erlaubt nach `claude/ecstatic-johnson-g3mxel` (vorher `git pull --rebase`), sonst in deinen Arbeitsbranch; den Branch im Chat nennen.

**Grenzen:** Keine Änderungen außerhalb von `docs/extraktion/modelltest/`. Keine Websuche, keine Pull Requests. Bei Unklarheiten im Ablauf mich fragen.
