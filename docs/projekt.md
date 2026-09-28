# Projekt Golfplatz – Projektbeschreibung

Fachlicher Rahmen der Website. Den technischen Stack beschreibt [development-environment.md](development-environment.md).

## Eckdaten

| Punkt | Festlegung |
| --- | --- |
| Name des Clubs | Golfclub Dreibäumen e. V. ([dreibaeumen.de](https://dreibaeumen.de/)) |
| Ort | Stoote 1, 42499 Hückeswagen (Bergisches Land) |
| Art der Anlage | Golfclub mit Mitgliedern, Gäste willkommen |
| Platz | 18 Loch |
| Sprache | Nur Deutsch |
| Launch | Kein fester Termin |

## Ziele der Website

1. **Neue Mitglieder gewinnen:** Interessenten informieren und zur Mitgliedschaft führen.
2. **Greenfee-Gäste gewinnen:** Gästen den Platz vorstellen und zeigen, wie einfach sie spielen können: keine festen Startzeiten, am Wochenende kurze Anmeldung.
3. **Mitglieder informieren:** News, Turniere und Ergebnisse für bestehende Mitglieder bereitstellen.
4. **Events und Gastronomie vermarkten:** Restaurant, Firmenevents und Feiern bewerben.

## Zielgruppen

- Interessenten an einer Mitgliedschaft
- Greenfee-Gäste
- Bestehende Mitglieder
- Gäste für Restaurant und Veranstaltungen, auch Nicht-Golfer

## Bereiche / Sitemap (Entwurf)

Inhalte je Seite, Templates und Verknüpfungen im Detail: [seitenstruktur.md](seitenstruktur.md).

- **Startseite:** mit Platzstatus und den Abschlagsperren für heute und morgen
- **Platz & Bahnen:** Platzbeschreibung, alle 18 Bahnen einzeln (Hole-by-Hole), Scorekarte und Spielvorgaben je Abschlag (siehe [Spielbahnen & Spielvorgaben](#spielbahnen--spielvorgaben))
- **Mitgliedschaft:** Mitgliedschaftsmodelle, Preise und Aufnahmeantrag
- **Greenfee & Preise:** Preisliste für Gäste und Kooperationen
- **Turniere & Kalender:** Turnierkalender, Ausschreibungen und Ergebnisse
- **Golfschule:** Pros, Kurse, Platzreife und Schnupperkurse
- **Restaurant:** Clubrestaurant mit Öffnungszeiten und Speisekarte
- **Mannschaften:** Übersicht aller Mannschaften, je Mannschaft eine Seite mit Spielführer, Kader, Ligaspielen und Spielberichten (siehe [Mannschaften & Ligabetrieb](#mannschaften--ligabetrieb))
- **News/Blog:** Aktuelles aus dem Club
- **Club & Kontakt:** Vorstand, Team, Jugend, Anfahrt und Kontakt
- **Mitgliederbereich (geschützt):** interne Inhalte nach Login

## Funktionale Anforderungen

- **Mitgliederbereich:** Mitglieder melden sich direkt auf der Website mit einem WordPress-Login an. Die geschützten Inhalte sehen nur angemeldete Mitglieder.
- **Startzeiten:** Der Club verzichtet **bewusst auf feste Startzeiten**, Startzeiten werden nicht gebucht. Die Website sagt das deutlich und nennt die empfohlene telefonische Anmeldung am Wochenende und an Feiertagen (Anmeldung 02192 8547-12) sowie den Greenkeeper-Tag am Montag.
- **Turniere:** Sie werden in **PC CADDIE** organisiert, einschließlich Ausschreibung, Meldung und Ergebnissen. Die Website pflegt Turniere deshalb nicht doppelt, sondern **bettet PC CADDIE ein**. Turnierkalender, Meldung und Ergebnisse erscheinen damit direkt auf den Seiten der Website. Die Ligaspiele der Mannschaften werden dagegen **ausschließlich auf der Website** gepflegt und nicht in PC CADDIE (siehe unten).
- **Platzstatus und Abschlagsperren:** Abschlag 1, Abschlag 10 und der ganze Platz lassen sich zeitlich sperren, die Startseite zeigt die Sperren an (siehe [Platzstatus & Abschlagsperren](#platzstatus--abschlagsperren)).
- **Pflegbare Inhalte:** News, Bahnen, Kurse und Preise werden als strukturierte Inhalte angelegt, z. B. als eigene Beitragstypen mit Meta Box, und nicht als freier Seiteninhalt.

## Spielbahnen & Spielvorgaben

### Grundsätze

- Die 18 Spielbahnen werden **zentral verwaltet**. Jede Bahn wird einmal angelegt, und alle Ausgaben lesen aus diesen Daten: Bahnseiten, Scorekarte, Platzübersicht und Spielvorgaben.
- Es gibt vier Abschläge: **Gelb, Blau, Rot, Orange**.

### Datenmodell (Vorschlag, Meta Box)

| Baustein | Felder |
| --- | --- |
| **Beitragstyp „Spielbahn“** | Bahnnummer (1–18), Name (optional), Par, HCP (Schwierigkeitsrang der Bahn, 1–18), Länge in Metern je Abschlag (Gelb, Blau, Rot, Orange), Beschreibung und Spieltipp, Bahngrafik bzw. Luftbild, Bilder, **Video** zur Bahn |
| **Einstellungsseite „Abschläge“** | Je Abschlag (Gelb, Blau, Rot, Orange) und je Damen/Herren: Course Rating (CR), Slope Rating, Par, Gesamtlänge |

### Videos

- Zu jeder Bahn gibt es ein Video. Es erscheint auf der Bahnseite.
- Die Videos liegen **lokal auf der Website**, also in der WordPress-Mediathek, und nicht bei YouTube oder Vimeo. Es werden keine externen Dienste eingebunden, deshalb ist kein Cookie-Consent nötig.
- **Feld:** Das Video wird in Meta Box als Datei-Upload angelegt. Dazu kommt ein Vorschaubild (Poster).
- **Ausgabe:** ein HTML5-`<video>` mit Steuerelementen und Posterbild. Das Video lädt erst, wenn es gestartet wird (`preload="none"`), damit die Bahnseite schnell bleibt.
- **Dateien:** Die Videos werden als MP4 (H.264) für das Web optimiert und komprimiert, z. B. in 1080p.

### Spielvorgaben

- Pro Abschlag wird die **Spielvorgabentabelle** angezeigt: welcher Handicap-Index zu welcher Spielvorgabe führt.
- Die Tabelle wird aus CR, Slope und Par errechnet und muss nicht von Hand gepflegt werden. Grundlage ist die WHS-Formel: *Spielvorgabe = Handicap-Index × Slope / 113 + (CR − Par)*, gerundet.
- **Optional:** ein Spielvorgaben-Rechner, in den man seinen Handicap-Index eingibt und dafür für alle Abschläge die Spielvorgabe erhält.
- Ändert der Verband die Werte, z. B. nach einer Neubewertung des Platzes, werden nur CR und Slope angepasst. Alle Tabellen aktualisieren sich dann automatisch.

## Platzstatus & Abschlagsperren

### Anforderungen

- **Abschlag 1** und **Abschlag 10** lassen sich einzeln sperren, jeweils mit Datum und Uhrzeit **von – bis**.
- Der **ganze Platz** lässt sich sperren, z. B. bei Unwetter oder unbespielbarem Platz. Das muss **ohne großen Aufwand** gehen, also mit wenigen Klicks und auch vom Smartphone aus.
- Zu jeder Sperre wird der **Grund** angegeben und öffentlich angezeigt, z. B. „Ligaspiel AK50/1 Männlich“, „Turnier“, „Platzpflege“ oder „Unwetter“.
- Die **Startseite** zeigt alle Sperren für **heute und morgen** an.
- **Driving Range, Kurzspielbereich und Proshop** haben eigene Öffnungszeiten (Clubdaten › Öffnungszeiten). Sie lassen sich ebenfalls sperren, per Schnellsperre oder geplant, und werden dann als gesperrt bzw. geschlossen angezeigt: im Platzstatus und bei den Öffnungszeiten.
- **Öffnungszeiten:** Jeder Bereich (Sekretariat, Driving Range, Kurzspielbereich, Proshop, Restaurant) hat **Standard-Öffnungszeiten** (Wochentage und Uhrzeit, mehrere Zeilen möglich). **Ausnahmen** gelten für einen Zeitraum (von–bis) und sind entweder „geschlossen“ oder haben eigene Zeiten (z. B. Winterzeit, Feiertage, Inventur). Die Website zeigt daraus „Jetzt geöffnet bis …“ bzw. „Geschlossen · öffnet …“ und die kommenden Ausnahmen. Eine Sperre aus dem Platzstatus hat Vorrang.
- **Trolleys** und **Buggies/E-Carts** lassen sich für den Platz sperren, z. B. bei nassem Boden.
- Zwischen **Sommer- und Wintergrüns** lässt sich umschalten, optional mit Hinweis (z. B. welche Bahnen betroffen sind).
- Die Bahnen haben **keine Namen**. Sie heißen „Bahn 1“ bis „Bahn 18“.

### Umsetzung (Meta Box, angelegt)

| Baustein | Inhalt |
| --- | --- |
| **Beitragstyp „Sperrung“** | Bereich (Abschlag 1, Abschlag 10, ganzer Platz, Driving Range, Kurzspielbereich, Proshop, Trolleys, Buggies/E-Carts), Beginn (Datum + Uhrzeit), Ende (Datum + Uhrzeit), Grund. Für geplante Sperren. |
| **Einstellungsseite „Platzstatus“** | Schnellsperren mit Schalter, Grund und optional „bis“ für Platz, Driving Range, Kurzspielbereich, Proshop, Trolleys und Buggies; Umschalter Sommer-/Wintergrüns mit Hinweis. Für kurzfristige Fälle ohne Planung, auch vom Smartphone. |

### Berechtigungen

- Sperren und die Schnellsperre dürfen **nur ausgewählte Benutzer** anlegen und bearbeiten.
- Umsetzung mit **User Role Editor Pro**:
  - Eine eigene Rolle, z. B. „Platzstatus“, bekommt nur die Rechte für den Beitragstyp „Sperrung“ und für die Einstellungsseite „Platzstatus“.
  - Die ausgewählten Benutzer erhalten diese Rolle bzw. zusätzlich zu ihrer bestehenden Rolle diese Rechte.
- Wer die Rolle nicht hat, sieht die Sperren im Backend nicht und kann sie nicht bearbeiten.

### Keine automatischen Sperren

Ligaspiele und Turniere sperren die Abschläge vorerst **nicht automatisch**. Jede Sperre wird von Hand angelegt. Eine Automatik lässt sich später ergänzen.

### Anzeige auf der Startseite

- Ein gut sichtbarer Platzstatus-Block oben auf der Startseite, gegliedert nach **heute** und **morgen**
- Pro Sperre werden Bereich, Zeitraum und Grund angezeigt.
- Eine aktive Schnellsperre steht immer an erster Stelle und ist deutlich hervorgehoben.
- Abgelaufene Sperren verschwinden automatisch. Gibt es keine Sperren, erscheint der Hinweis „Platz uneingeschränkt bespielbar“.
- Über den Tageslisten stehen die **Spielbedingungen**: Sommer-/Wintergrüns, Trolleys erlaubt/gesperrt, Buggies erlaubt/gesperrt (mit Grund).
- Darunter **Übungsanlagen & Proshop**: jeweils „geöffnet“ oder „gesperrt/geschlossen bis …“ mit Grund, dazu anstehende Sperren für heute und morgen.
- Die Ampel im Seitenkopf nennt zusätzlich „Wintergrüns“, „Trolley-Verbot“ und „Buggy-Verbot“.

## Mannschaften & Ligabetrieb

### Mannschaften

| Mannschaft | Weiblich | Männlich |
| --- | :---: | :---: |
| AK30 | ✓ | ✓ |
| AK50/1 | ✓ | ✓ |
| AK50/2 | – | ✓ |
| AK65/1 | ✓ | ✓ |
| AK65/2 | ✓ | ✓ |

Dazu kommt die **Clubmannschaft**, die es nur einmal gibt und die nicht nach Geschlecht getrennt ist. Insgesamt sind das 10 Mannschaften.

Jede Mannschaft hat einen **Spielführer**.

### Grundsätze

- **Spieler werden zentral verwaltet:** Jeder Spieler wird einmal angelegt und den Mannschaften zugeordnet. Mehrere Mannschaften pro Spieler sind möglich, z. B. Clubmannschaft und eine AK.
- **Ligaspiele werden zentral verwaltet:** Alle Termine stehen an einer Stelle, jeder ist einer Mannschaft zugeordnet.
- **Spielberichte** gehören immer zu einer Mannschaft und zu dem Ligaspiel (Termin), über das sie berichten.

### Datenmodell (Vorschlag, Meta Box + MB Relationships)

| Beitragstyp | Wichtige Felder |
| --- | --- |
| **Mannschaft** | Name, Altersklasse (Clubmannschaft, AK30, AK50, AK65), Nummer (/1, /2), Geschlecht (weiblich, männlich, gemischt), Liga/Spielklasse, Mannschaftsfoto |
| **Spieler** | Vorname, Name, Foto (optional), Geschlecht, Jahrgang bzw. Altersklasse |
| **Ligaspiel** | Datum, Uhrzeit, Spielort bzw. Austragungsclub, Spieltag, Ergebnis/Platzierung |
| **Spielbericht** | Titel, Text, Bilder, Autor |

| Beziehung | Kardinalität |
| --- | --- |
| Mannschaft ↔ Spieler (Kader) | n : m |
| Mannschaft → Spieler (Spielführer) | 1 : 1 |
| Mannschaft → Ligaspiel | 1 : n |
| Ligaspiel → Spielbericht | 1 : n |
| Mannschaft → Spielbericht | 1 : n (ergibt sich aus dem Ligaspiel) |

Auf der Mannschaftsseite erscheinen damit automatisch Spielführer, Kader, die kommenden und vergangenen Ligaspiele sowie die zugehörigen Spielberichte. Alle Ligaspiele lassen sich außerdem gesammelt in einer Terminübersicht zeigen. Die Clubturniere kommen weiterhin aus PC CADDIE.

## Content-Pflege

Die Pflege ist aufgeteilt:

- **Club (Sekretariat):** pflegt laufend News, Turniere, Ergebnisse, Preise und Speisekarte. Das Backend muss dafür für Nicht-Techniker einfach bedienbar sein.
- **Agentur:** verantwortet Struktur, Templates, Komponenten und Design.

## Design

- **Richtung:** klassisch-elegant, also traditionell und hochwertig mit gedämpften Farben.
- **Umsetzung:** über Design-Tokens in Automatic.css. Die Navigation folgt der BEM-Konvention (siehe Stack-Doku).
- **CI:** Logo, Farben und Schriften des Clubs sind noch zu klären.

## Offene Punkte

- [x] Name und Ort des Clubs: Golfclub Dreibäumen e. V., Hückeswagen
- [ ] Einbettung von PC CADDIE technisch klären: Einbettungscode bzw. Zugangsdaten vom Club, welche Module (Turnierkalender, Meldung, Ergebnisse), Anpassung an das Design, Datenschutz und Cookie-Consent
- [ ] CI-Vorgaben klären (Logo, Farben, Schriften)
- [ ] Inhalte des Mitgliederbereichs definieren
- [ ] Launch-Termin festlegen
- [ ] Festlegen, welche Benutzer die Rolle „Platzstatus“ erhalten
- [x] Spielbahnen: ein HCP-Wert (Vorgabe) je Bahn, aber getrennte Par-Werte – Bahn 9 und 13 sind für Herren Par 5, für Damen Par 4 (Par gesamt 71/69)
- [ ] Spielvorgaben: aus CR/Slope errechnen (Vorschlag) oder die Tabellen des Verbands 1:1 übernehmen? Soll es einen Spielvorgaben-Rechner geben?
- [ ] Bahnvideos: Liegen sie für alle 18 Bahnen vor? Wie groß sind die Dateien, und reichen Upload-Limit und Speicherplatz beim Hosting?
- [x] Aktuelle Werte (CR, Slope, Par, Längen): aus der Scorekarte 2024 übernommen. Gelb und Blau sind Herren-, Rot und Orange Damen-Abschläge.
- [ ] Klären, wer Spielberichte schreibt (Spielführer selbst oder das Sekretariat)
- [ ] Datenschutz: Einwilligung der Spieler zur Veröffentlichung von Namen und Fotos
