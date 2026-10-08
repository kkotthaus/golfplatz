# Technik der Website

> Für Administratoren. Erklärt, wie die Website des Golfclubs aufgebaut ist und wie Plugins, Snippets, CSS und Skripte zusammenarbeiten. Die Bedienung für die Redaktion steht im Handbuch. Quelle: `docs/technik.md` im Repository des Clubs (Grundlage: Blueprint golfplatz) – Änderungen dort, nicht hier (die Seite wird beim Build erzeugt). Ein abgeleiteter Club ergänzt hier seine eigenen Snippets, Plugins und Umgebungen.

## Überblick

| Schicht | Baustein | Aufgabe |
| --- | --- | --- |
| 1. Grundlage | WordPress, Theme „Etch Theme“ | Seiten, Nachrichten (Beiträge), eigene Beitragstypen, Benutzer mit Rollen, Mediathek |
| 2. Aufbau | Etch | Seiten, Templates, Komponenten, Loops |
| 3. Gestaltungssystem | Automatic.css (ACSS) 4 | Farben (hell und dunkel), Buttons, Schrift, Abstände |
| 4. Eigenes Aussehen | Etch-Stylesheet „Golfplatz“ | Tokens, Komponenten-CSS, Anpassung des Mega-Menüs |
| 5. Fertige Bausteine | EtchMegaMenuPro (EMMP), Slider Pro for Etch, OhMyEtch | Header und Navigation, Slider, Accordion, Brotkrumen, Lightbox |
| 6. Daten und Logik | Snippets in WPCodeBox (Ordner „Golfplatz“) | Platzstatus, Turniere, Preise, Mannschaften, Nachrichten … – liefern fertige Werte an Etch |
| 7. Externe Quellen | PC CADDIE://online, Ligaportal des Landesverbands (liga.golf) | Turnierkalender und Ergebnisse, Ligaspiele – automatisch per WP-Cron |
| 8. Betrieb | Duplicator, User Role Editor | Sicherung, Rollen für Redaktion und Platzstatus |

**Grundsatz:** PHP rechnet, Etch gestaltet. Die Snippets erzeugen kein Markup und keine Shortcodes; sie stellen Werte als „dynamische Daten“ bereit (`{options.golfplatz.<bereich>.…}`, je Beitrag `{this.golfplatz.…}`), fertig formatiert („35 €“, „auf Anfrage“). Die Komponenten zeigen sie an. Clubdaten (Name, Adresse, Telefon …) kommen immer aus der Einstellungsseite „Clubdaten“, nie aus dem Markup.

## Woher kommt was?

| Bereich | Gepflegt | Hinweis |
| --- | --- | --- |
| Seiten, Templates, Komponenten, Stylesheet „Golfplatz“ | Repository | Änderungen im Etch-Editor überschreibt die nächste Übertragung |
| Snippets (WPCodeBox, Ordner „Golfplatz“) | Repository | |
| ACSS-Einstellungen (Farben, Buttons, Schrift) | Repository | nicht im Backend ändern |
| Dateien in `wp-content/golfplatz/` (Build, Medien, Handbuch, diese Seite) | Repository | |
| **Clubdaten, Platzstatus, Sperrungen, Öffnungszeiten** | **Backend** | Einstellungsseiten und Beitragstyp „Sperrung“ |
| **Nachrichten, Preise, Personen, Kurse, Lochwettspiel, Spieler, Spielberichte** | **Backend** | Beitragstypen mit Feldern (Meta Box) |
| Turniere, Ligaspiele, Mannschaften | **automatisch** | aus PC CADDIE bzw. vom Landesverband; nicht von Hand überschreiben |

## Plugins

| Plugin | Aufgabe | Zusammenspiel |
| --- | --- | --- |
| **Etch** | Builder | liest die Daten der Snippets über `etch/dynamic_data/option` und `etch/dynamic_data/post` |
| **Etch Theme** | leerer Theme-Rahmen | |
| **Automatic.css** | Gestaltungssystem | lädt nach dem Stylesheet „Golfplatz“; Farbschema „light dark“ mit Umschalter |
| **EtchMegaMenuPro** (Etch-Komponenten) | Header, Mega-Menü, Mobilmenü, Skip-Link | eingebunden per WordPress-ID – Komponenten nie löschen und neu anlegen |
| **Slider Pro for Etch** | alle Slider | ohne das Plugin bleiben Slider stehen |
| **OhMyEtch** | Accordion (FAQ, Saisons, Runden), Brotkrumen, Lightbox | eingebunden per Key |
| **Etch Font Manager** | Schriften selbst hosten (Blueprint: Fraunces und Lora) | Zuordnung in den ACSS-Einstellungen |
| **Meta Box AIO** | Beitragstypen, Felder, Einstellungsseiten | |
| **User Role Editor Pro** | Rollen, z. B. nur Platzstatus pflegen (`edit_sperrungen`) | |
| **WPCodeBox 2** | führt die Snippets aus (Ordner „Golfplatz“) | Snippets mit Fehler schaltet WPCodeBox selbst ab |
| **Duplicator Pro** | Sicherung, Umzug | nach dem Livegang nie mehr ein lokales Paket live einspielen |
| **MCP Adapter** | Zugang für KI-Werkzeuge | **nicht auf 0.7.x aktualisieren** |
| Uplink Media Bridge, Editorial Title, Unified Ops Center | Hilfen für Etch im Backend | für die Website nicht nötig |

## Snippets (WPCodeBox, Ordner „Golfplatz“)

### Platz und Spielbetrieb

| Snippet | Aufgabe |
| --- | --- |
| **golfplatz-platzstatus** | rechnet Platzstatus, Fahnenpositionen und Öffnungszeiten aus Sperrungen, „Platzstatus“ und Clubdaten (`{options.golfplatz.platzstatus.…}`, `{options.golfplatz.zeiten}`) |
| **golfplatz-tee-belegung** | erzeugt aus den Turnieren automatisch Sperrungen von Abschlag 1 und 10 |
| **golfplatz-birdiebook** | Daten der 18 Spielbahnen, Scorekarte und Rating; Skript des Birdiebooks und Web-App-Manifest |
| **golfplatz-dashboard** | Dashboard-Kästen „Platzstatus“ und „Termine der nächsten 14 Tage“ |

### Turniere und Mannschaften

| Snippet | Aufgabe |
| --- | --- |
| **golfplatz-turniere** | liest Turnierkalender und Ergebnisse aus PC CADDIE://online und legt je Turnier einen Eintrag an bzw. aktualisiert ihn (WP-Cron) |
| **golfplatz-liga-sync** | holt Spieltage, Spielorte und Ergebnisse aus dem Ligaportal des Landesverbands (WP-Cron, auch von Hand) |
| **golfplatz-mannschaften** | Mannschaften, Kader, Ligaspiele, Spielberichte als Daten |
| **golfplatz-lochwettspiel** | rechnet den Turnierbaum des Lochwettspiels (K.-o.-System, Freilose) |

### Club, Angebote, Inhalte

| Snippet | Aufgabe |
| --- | --- |
| **golfplatz-club** | Personen und Anfahrt als Daten |
| **golfplatz-preise** | Preise als Tabellen bzw. Karten |
| **golfplatz-golfschule** | Kurse mit Preis als Text und nur künftigen Terminen |
| **golfplatz-news** | Nachrichten unter `/news/<slug>/`, Liste mit Filter und Blättern |

### Darstellung und Backend

| Snippet | Aufgabe |
| --- | --- |
| **golfplatz-farbschema** | Umschalter Hell/Dunkel (ACSS-Klassen `scheme--light`/`scheme--dark`, Wahl im Browser) |
| **golfplatz-scrollen** | Sprunglinks gleiten in 600 ms zum Ziel, mit Abstand zum Header |
| **golfplatz-ki** | KI-Kennzeichnung von Bildern und Videos (Mediathek-Feld, Symbol am Bild, Hinweis im Alternativtext) |
| **golfplatz-backend** | blendet den Block „Individuelle Felder“ aus (klassischer und Block-Editor) |
| **golfplatz-handbuch** | Menü „Handbuch“ für die Redaktion |
| **golfplatz-technik** | diese Seite (Unterpunkt „Technik“ im Handbuch) |
| **golfplatz-mcp** | Funktionen zum Übertragen aus dem Repository – nur in der Entwicklung aktiv |

> Ein Snippet, das WPCodeBox wegen eines Fehlers abgeschaltet hat, fehlt still – etwa der Platzstatus oder der Turnier-Abgleich. Nach Änderungen in WPCodeBox prüfen, dass alle Golfplatz-Snippets eingeschaltet sind.

## Externe Daten und Zeitpläne (WP-Cron)

| Quelle | Snippet | Ergebnis |
| --- | --- | --- |
| PC CADDIE://online (öffentliche Seiten des Clubs, Kennung in den Clubdaten) | golfplatz-turniere | Turniere mit Anmeldung, freien Plätzen, Ergebnissen; danach Tee-Sperrungen |
| Ligaportal des Landesverbands (liga.golf) | golfplatz-liga-sync | Mannschaften, Ligaspiele, Ergebnisse |

Ohne laufenden WP-Cron (live: Cronjob beim Hoster auf `/wp-cron.php`) veralten Turniere und Ligaspiele.

## CSS: wer gestaltet was

1. **Schriften** – Etch Font Manager (`wp-content/fonts/`).
2. **Etch-Stylesheets** (Etch › Stylesheets): „Golfplatz“ – eigene Tokens (`css/tokens.css`, keine Farbwerte), Komponenten-CSS aus dem Prototyp und die Anpassung des Mega-Menüs über dessen Variablen. „DWC Mega Menu“ – Stylesheet von EMMP, **immer behalten**. „Main“ und „Custom Media Definitions“ – Vorgaben von Etch.
3. **Automatic.css** – lädt danach; Einstellungen aus dem Repository.

Regeln: nur ACSS-Farbvariablen (sonst bricht das dunkle Schema), Buttons nur mit ACSS-Klassen, eigene Klassen nach BEM.

## JavaScript

| Skript | Woher |
| --- | --- |
| Mega-Menü, Mobilmenü, Skip-Link | EMMP |
| Umschalter Hell/Dunkel | golfplatz-farbschema |
| Weiches Scrollen | golfplatz-scrollen |
| Birdiebook, Zählkarte | golfplatz-birdiebook |
| Accordion, Brotkrumen, Lightbox | OhMyEtch |
| Slider | Slider Pro for Etch |

## Was man nicht tun sollte

- Seiten, Templates oder Komponenten im Etch-Editor ändern – die nächste Übertragung überschreibt das.
- EMMP- oder Slider-Komponenten löschen und neu anlegen (die Website bindet sie per ID ein); das Stylesheet „DWC Mega Menu“ löschen.
- Automatisch angelegte Turniere oder Ligaspiele von Hand umbauen – der nächste Abgleich überschreibt sie.
- Den MCP Adapter auf 0.7.x aktualisieren.
- Felder im Block „Individuelle Felder“ oder per Datenbank ändern.

## Nach Updates prüfen

- Header: Mega-Menü, Mobilmenü, Skip-Link (einmal Tab), Umschalter Hell/Dunkel
- Startseite mit Platzstatus und Öffnungszeiten, Birdiebook
- Turniere und Mannschaften (Abgleich von Hand), Lochwettspiel
- Slider, Accordions, Lightbox, Brotkrumen; beide Farbschemata
- WPCodeBox: alle Golfplatz-Snippets eingeschaltet
