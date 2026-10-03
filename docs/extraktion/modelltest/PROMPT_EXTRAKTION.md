# Prompt Extraktions-Instanz (für jedes Modell identisch)

Vor dem Einfügen nur in der **ersten Zeile** den Buchstaben des Laufs eintragen (`K`, `T`, `F` oder `R`); sonst nichts ändern. Alles ab der Linie einfügen.

---

**Dein Lauf-Code: X**   ← hier den Buchstaben eintragen

**Modelltest Extraktion**

Diese Nachricht ist nur die Vorbereitung. **Lies jetzt noch keine Datei, führe keine Befehle aus und lade nichts.** Antworte nur mit „Bereit – Lauf“ und deinem Lauf-Code. Erst auf meine Nachricht „Go“ beginnst du mit Schritt 1.

**Worum es geht**

Du extrahierst 12 Buchkapitel bzw. Artikel aus Fachliteratur in strukturierte Markdown-Dateien. Das ist Teil eines **blinden Modellvergleichs**: Andere Instanzen bearbeiten dieselben Einheiten, eine eigene Instanz bewertet alle Ergebnisse am Original-PDF. Es zählen Genauigkeit (Zahlen, Seitenangaben), Vollständigkeit, das Erkennen von Widersprüchen in der Quelle und die Einhaltung der Regeln, nicht die Geschwindigkeit.

**Wo was liegt** (GitHub-Repo `chodid/training`, **dein Branch `modelltest-lauf-` + dein Lauf-Code als Kleinbuchstabe**, z. B. `modelltest-lauf-k`; er enthält nur die Testunterlagen und die Eingabedateien)

- Regeln: `docs/extraktion/modelltest/briefing.md`. Vollständig lesen und befolgen. Es enthält Template, Feldregeln, Leseregeln, Abbruchregel und Rückgabeformat.
- Einheiten: `docs/extraktion/modelltest/einheiten.md`, eine Liste der 12 Einheiten.
- Auftrag je Einheit: `docs/extraktion/modelltest/auftraege/<EINHEIT>.md`. Er nennt Eingabedatei(en), Kapitel, Hinweis zum Seitenbezug, Quelle (Stufe, Zweck, Themenfelder) und Ausgabepfad. Der Platzhalter `<LAUF-CODE>` dort und im Briefing steht für deinen Lauf-Code aus der ersten Zeile.
- Eingabedateien: unter `docs/literatur/` (PDF; bei Einheit 1 Markdown mit Ansichts-PDF).
- Ablage: **nur** `docs/extraktion/modelltest/lauf-<LAUF-CODE>/<EINHEIT>.md` (mit deinem Lauf-Code, z. B. `lauf-K/`), eine Datei je Einheit.

**Ablauf nach „Go“**

1. Arbeitsstand holen:
   - Nur deinen Branch holen: `git fetch origin modelltest-lauf-<code>` und auschecken, z. B. `git checkout -B modelltest-lauf-<code> origin/modelltest-lauf-<code>`. `<code>` ist dein Lauf-Code als Kleinbuchstabe.
   - Gibt deine Umgebung einen anderen Arbeitsbranch vor, diesen auf denselben Stand setzen: `git checkout -B <arbeitsbranch> origin/modelltest-lauf-<code>`.
   - **Keine anderen Branches holen, auschecken oder lesen.** Dein Arbeitsverzeichnis muss danach genau dem Stand deines Branches entsprechen; prüfe das mit `ls docs/extraktion/`. Dort darf nur `modelltest/` liegen. Liegt dort mehr, nicht weiterlesen und mich fragen.
   - Prüfen, ob `pdftotext`/`pdftoppm` vorhanden sind. Falls nicht und es möglich ist, `poppler-utils` installieren.
2. `docs/extraktion/modelltest/briefing.md` und `docs/extraktion/modelltest/einheiten.md` vollständig lesen.
3. Die 12 Einheiten bearbeiten, **jede mit frischem Kontext**:
   - **Wenn deine Umgebung Unteragenten kennt:**
     - Je Einheit einen Unteragenten starten, höchstens 4 gleichzeitig.
     - Ausdrücklich **dasselbe Modell wie du selbst** mit derselben Denktiefe einstellen, nicht ein voreingestelltes anderes Modell erben lassen.
     - Auftrag an den Unteragenten: „Lies `docs/extraktion/modelltest/auftraege/<EINHEIT>.md` und führe den Auftrag vollständig aus. Er verweist auf das Briefing `docs/extraktion/modelltest/briefing.md`, das du zuerst vollständig liest und befolgst. Dein Lauf-Code ist `<dein Buchstabe>`; setze ihn überall für den Platzhalter `<LAUF-CODE>` ein.“ (Buchstaben einsetzen.)
   - **Sonst:** Einheiten nacheinander bearbeiten. Nach jeder Einheit die Datei schreiben und für die nächste nichts aus früheren Einheiten verwenden.
   - Je Einheit das ganze Kapitel bzw. den ganzen Artikel lesen. PDFs möglichst als Seitenbilder, wie im Briefing beschrieben; die `lesemethode` muss stimmen.
4. Prüfung vor dem Commit:
   - Alle 12 Dateien vorhanden.
   - Front matter vollständig mit `modell: lauf-<LAUF-CODE>` und `datum: 2026-10-04`.
   - Alle sechs Abschnitte vorhanden, leere mit „- keine“.
   - Aussagentabelle mit 7 Spalten.
   - `typ` nur `befund`/`modell`/`praxis`/`definition`/`methodik`.
   - `unsicher` nur `true`/`false`.
   - Kein Modell- oder Anbietername in den Dateien.
   - Fehler selbst korrigieren. **Inhalte nicht** mit anderen Läufen oder Extraktionen abgleichen.
5. Commit und Push:
   - Nur den Ordner `docs/extraktion/modelltest/lauf-<LAUF-CODE>/` committen, mit der Nachricht `docs(modelltest): Lauf <LAUF-CODE> – 12 Extraktionen`.
   - **Kein Modell- oder Anbietername in der Commit-Nachricht**, auch keine Co-Authored-By-Zeile mit Modellnamen (blinder Vergleich).
   - Push nach `modelltest-lauf-<code>` (`git push origin HEAD:modelltest-lauf-<code>`). Lässt deine Umgebung das nicht zu, in deinen vorgegebenen Arbeitsbranch pushen und den Namen im Abschluss nennen.
6. Abschluss **nur im Chat**, nicht im Repo:
   - (a) Branch und Commit-Hash.
   - (b) Je Einheit die Rückgabe laut Briefing: Pfad, Seiten und Lesemethode, Aussagen/unsicher/offene Stellen, Seitenbezug, Probleme.
   - (c) **Laufprotokoll:** Start- und Endzeit, Dauer, verbrauchte Tokens (gesamt und, wenn sichtbar, je Einheit), Zahl der Unteragenten, Abbrüche oder Wiederholungen.
   - (d) Abweichungen vom Ablauf.

**Grenzen:**
- Keine anderen Dateien unter `docs/extraktion/` lesen oder ändern, insbesondere keine anderen `lauf-*`-Ordner und keine Extraktionen in den Blockordnern.
- Nichts unter `docs/wissen/` oder `docs/konzept/` lesen.
- Keine Websuche, keine Ergänzung aus eigenem Wissen, keine Pull Requests.
- Bei echten Unklarheiten im Ablauf mich fragen. Bei Unklarheiten in der Quelle nicht raten, sondern sie nach den Regeln des Briefings markieren.
