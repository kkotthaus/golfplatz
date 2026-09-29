# Prototyp Golfclub-Website

Klickbarer, statischer Prototyp aller Seiten der Sitemap. Er ist die Vorlage für den Aufbau in Etch. Alle Inhalte sind neutrale Platzhalter („Golfclub Musterclub“) aus `src/data.mjs`, der zentralen Konfiguration des Clubs.

## Starten

```bash
node prototype/src/build.mjs
```

Danach den Server `golfclub` aus `.claude/launch.json` starten (http-server auf Port 8080) und `http://localhost:8080/prototype/` öffnen. Es gibt keine Abhängigkeiten, Node reicht.

- `?gesperrt=platz,range,kurzspiel,proshop,trolley,buggy` (beliebige Auswahl) simuliert Schnellsperren, `?gruens=winter` die Wintergrüns. Buggies sind in den Beispieldaten gesperrt.
- Der Knopf in der Top-Bar schaltet zwischen Hell (Standard) und Dunkel um.
- Die Sperrungen liegen relativ zu „heute“, damit der Platzstatus immer etwas zeigt.

## Aufbau

| Pfad | Inhalt |
| --- | --- |
| `src/data.mjs` | Beispielinhalte, gegliedert wie die Meta-Box-Felder |
| `src/components.mjs` | Bausteine (später Etch-Komponenten) |
| `src/build.mjs` | Layout und alle Seiten, schreibt `**/index.html` und `assets/js/data.js` |
| `assets/css/farben.css` | Generiert: ACSS-Farbvariablen mit `light-dark()` wie in WordPress (aus `wordpress/etch/acss-farben.mjs`) |
| `assets/css/tokens.css` | Ersatz für die übrigen ACSS-Variablen (Abstände, Schrift, Radius) |
| `assets/css/main.css` | Komponenten-CSS nach BEM |
| `assets/js/zeiten.js` | Öffnungszeiten-Logik (Standard, Ausnahmen, jetzt geöffnet), im Browser und im Build genutzt; in WordPress dieselbe Logik in PHP |
| `assets/js/main.js` | Navigation, Platzstatus, Spielvorgaben-Rechner, Tabs |

Die erzeugten HTML-Dateien nicht von Hand bearbeiten, sondern `src/` ändern und neu bauen.

## Seiten

Startseite (mit Platzstatus) · Platz & Bahnen · 18 Bahnseiten · Spielvorgaben mit Rechner · Greenfee & Preise · Mitgliedschaft mit Aufnahmeantrag · Turniere (Platzhalter für PC CADDIE) · Golfschule · Mannschaften, 10 Mannschaftsseiten, alle Ligaspiele, Spielberichte · Restaurant & Veranstaltungen · News mit Artikelseiten · Club & Kontakt · Mitgliederbereich (Login) · Impressum, Datenschutz

## Komponenten → Etch

| Block (BEM) | Etch / Bibliothek | Datenquelle |
| --- | --- | --- |
| `main-nav` | EtchMegaMenuPro (umgesetzt, `wordpress/etch/header.mjs`) | Menüpunkte im Generator |
| `top-bar`, `course-status` | eigene Komponente | Sperrungen und Schnellsperre |
| `status-board`, `status-entry` | eigene Komponente, Loop | `sperrung` und die Einstellungsseite `platzstatus` |
| `guest-info` | Komponente „Als Gast spielen“ (`GastInfo`) | `clubdaten` (`anmeldung_*`, `ruhetag_hinweis`, E-Mail, Öffnungszeiten) – keine Startzeitbuchung |
| `hole-card`, `hole-detail`, `hole-facts`, `hole-video` | Loop bzw. Single-Template | `spielbahn` |
| `scorecard`, `rating-table` | Loop | `spielbahn`, Einstellungsseite `abschlaege` |
| `calculator`, `hcp-table` | eigene Komponente mit JS | Einstellungsseite `abschlaege` |
| `tabs`, `accordion` | OhMyEtch Tabs / Accordion | – |
| `price-table`, `price-card` | Loop | `preis` + `preiskategorie` |
| `course-card` | Loop | `kurs` |
| `person-card` | Loop | `person` + `personengruppe` |
| `team-card`, `match-table`, `roster`, `report-list` | Loops | `mannschaft`, `ligaspiel`, `spieler`, `spielbericht` |
| `news-card` | Loop | Beiträge |
| `embed-placeholder` | Einbettung PC CADDIE | Einstellungsseite `club` (`pccaddie_code`) |
| `hole-strip`, `entry-card`, `stats`, `menu-card` | eigene Komponenten | – |

## Bewusste Entscheidungen

- **Keine externen Dienste:** Systemschriften statt Google Fonts, kein Kartendienst und keine Videoplattform. Damit ist im Prototyp kein Cookie-Consent nötig.
- **Bahnvideos:** `<video controls preload="none" poster="…">` aus der Mediathek. Im Prototyp steht dort ein Platzhalter.
- **Bahngrafiken:** Sie werden aus den Bahndaten als SVG erzeugt und dienen nur als Platzhalter für die echten Luftbilder.
- **Formulare** senden nichts ab. In WordPress übernehmen das ein Formular-Plugin und der WordPress-Login.
