# WordPress-Aufbau per MCP

Seiten, Etch-Templates und das CSS werden im Repo als Code beschrieben, gebaut und über den MCP-Adapter in die lokale WordPress-Installation (golfplatz.local) übertragen.

## Bausteine

| Pfad | Zweck |
| --- | --- |
| `mu-plugins/golfplatz-mcp.php` | Must-Use-Plugin: eng begrenzte MCP-Funktionen (nur Administratoren), nur für die Entwicklung |
| `mu-plugins/golfplatz-platzstatus.php` | Must-Use-Plugin, **gehört auf die Live-Seite**: berechnet Platzstatus, Ampel, Hero-Kurzfassung, Fahnenpositionen und Öffnungszeiten und stellt sie Etch als Daten bereit: `{options.golfplatz.platzstatus.…}` (inkl. `ampel`, `kurz`, `fahnen`) und `{options.golfplatz.zeiten}`. Keine Shortcodes – das Markup bauen die Komponenten Platzstatus, PlatzstatusKurz, Ampel, Oeffnungszeiten, OeffnungszeitenAlle. |
| `mu-plugins/golfplatz-farbschema.php` | Must-Use-Plugin, **gehört auf die Live-Seite**: Skripte zum Umschalter Hell/Dunkel (Knopf: Etch-Komponente FarbschemaUmschalter, `[data-scheme-toggle]`). Setzt die ACSS-Klasse `scheme--dark` am `<html>`, merkt sich die Wahl im Browser und setzt sie schon im `<head>`, damit nichts aufblitzt. |
| `etch/lib.mjs` | Erzeugt Etch-Block-Markup (`etch/element`, `etch/text`, `etch/raw-html`, `etch/condition`, `etch/loop`) |
| `etch/templates.mjs` | Feste Seiten (`pages`) und Templates (`templates`) |
| `etch/header.mjs` | Header mit Hauptnavigation auf Basis von EtchMegaMenuPro |
| `etch/platzstatus.mjs` | Komponenten Platzstatus (Startseite), PlatzstatusKurz (Hero), Ampel (Top-Bar, App-Leiste) aus `{options.golfplatz.platzstatus.*}` |
| `etch/preise.mjs` | Komponente Preistabelle (Eigenschaft `kategorie`: greenfee, turnier, kooperationen, leihe): Matrix Mo–So/Feiertag, wenn die Kategorie Tage hat, sonst Liste. Daten `{options.golfplatz.preise}` |
| `mu-plugins/golfplatz-preise.php` | Must-Use-Plugin, **gehört auf die Live-Seite**: Preise je Preiskategorie als `{options.golfplatz.preise.jahr|spalten|tabellen[key, name, matrix, bloecke[titel, zusatz, einheit, zellen[wert], unter[…]], zeilen[…], karten[titel, betrag, einheit, zusatz, aufnahme, leistungen[text], hervorheben, mod, button, button_mod]]}`, Texte fertig („40 €“, „auf Anfrage“, „18 Loch“). Tage aus „Gültig an“, Varianten als Unterzeilen. Dazu `{options.golfplatz.twilight.…}`: Regel aus den Clubdaten und heutige Startzeit (Sonnenuntergang am Platz minus `twilight_stunden`). Keine Shortcodes. |
| `etch/preise.mjs` (Preiskarten) | Komponente Preiskarten: Preise einer Kategorie als Karten (`kategorie`, `ziel`), genutzt auf `/mitgliedschaft/` |
| `etch/zeiten.mjs` | Komponenten Oeffnungszeiten (ein Bereich, Eigenschaften `bereich`, `variante`, `liste`, `titel`) und OeffnungszeitenAlle aus `{options.golfplatz.zeiten}` |
| `etch/birdiebook.mjs` | Komponenten Birdiebook (EtchSliderPro), Scorekarte, Rating, SpielvorgabenRechner, SpielvorgabenTabellen und das Bahnenraster für `/platz/` |
| `etch/zaehlkarte.mjs` | Komponente Zählkarte: Handicap-Index und Abschlag wählen, Vorgabeschläge je Loch (nach Loch-HCP, Plus-Vorgabe ab HCP 18 zurück), Schläge eintragen → Netto je Loch, Stableford brutto/netto, Summen Out/In/Gesamt. Daten `{options.golfplatz.platz.spielvorgaben}` und `{options.golfplatz.platz.zaehlkarte}`; Skript `prototype/assets/js/zaehlkarte.js` (build kopiert es nach `dist/`, eingebunden von `golfplatz-birdiebook.php`), Eingaben nur im Browser (localStorage); „Ergebnis teilen“ als HTML-Datei (Web Share API) bzw. HTML-Tabelle in der Zwischenablage, „Als HTML speichern“ als Download, Link `#runde=…` |
| `etch/lochwettspiel.mjs` | Komponente Lochwettspiel: Turnierbaum eines Jahres (Eigenschaft `jahr`: `aktuell` oder Jahreszahl) aus `{options.golfplatz.lochwettspiele}`; bei mehr als 16 Teams frühere Runden als aufklappbare Listen (`<details>`), Baum ab Achtelfinale |
| `mu-plugins/golfplatz-lochwettspiel.php` | Must-Use-Plugin, **gehört auf die Live-Seite**: rechnet aus Teams, Spielzeiträumen und Ergebnissen des Beitragstyps `lochwettspiel` den Turnierbaum (Setzung mit Freilosen, Sieger rücken weiter, Status je Runde und Spiel relativ zu heute) und liefert ihn als `{options.golfplatz.lochwettspiele}`. Keine Shortcodes. Bei Seitencache `/turniere/` höchstens einen Tag cachen. |
| `etch/mannschaften.mjs` | Bausteine der Templates `archive-mannschaft` (Übersicht + `#ligaspiele`), `single-mannschaft` und `single-spielbericht`; Ligaspiel-Tabelle als CSS-Grid |
| `mu-plugins/golfplatz-mannschaften.php` | Must-Use-Plugin, **gehört auf die Live-Seite**: Mannschaften, Ligaspiele und Berichte als `{options.golfplatz.mannschaften}`, je Mannschaft `{this.golfplatz.team}`, je Bericht `{this.golfplatz.bericht}`; Spieler nur mit Einwilligung. Keine Shortcodes. |
| `mu-plugins/golfplatz-liga-sync.php` | Must-Use-Plugin, **gehört auf die Live-Seite**: Abgleich der Ligaspiele mit gvnrw.liga.golf (GraphQL): Mannschaften, Spieltage, Orte, Ergebnisse, Gastclubs; täglich per Cron, von Hand unter Mannschaften → Verband-Abgleich, per MCP `golfplatz/liga-sync` |
| `mu-plugins/golfplatz-turniere.php` | Must-Use-Plugin, **gehört auf die Live-Seite**: liest Turnierkalender und Ergebnisliste aus PC CADDIE://online (Club-Kennung `pccaddie_code`), Beitragstyp `turnier`, stündlich per Cron, von Hand unter Turniere → PC-CADDIE-Abgleich, per MCP `golfplatz/turniere-sync`; Daten `{options.golfplatz.turniere}` |
| `etch/turniere.mjs` | Komponenten Turnierkalender und Turnierergebnisse für `/turniere/` |
| `etch/loops.mjs` | Etch-Loops (Presets in `etch_loops`), z. B. `gp-bahnen` über alle Spielbahnen |
| `mu-plugins/golfplatz-birdiebook.php` | Must-Use-Plugin, **gehört auf die Live-Seite**: Bahndaten je Spielbahn als `{this|item.golfplatz.plan|status|fahne|entfernungen}` (Filter `etch/dynamic_data/post`), Scorekarte und Rating als `{options.golfplatz.platz.…}`, dazu Birdiebook-Skript und Web-App-Manifest. Keine Shortcodes. |
| `etch/css/emmp.css` | EtchMegaMenuPro-Farben und -Schriftgrößen auf ACSS-Farben setzen |
| `etch/komponenten.mjs` | Etch-Komponenten (`components`), eingebunden mit `komponente('<Key>')` |
| `etch/css/tokens.css` | Tokens, die Automatic.css nicht kennt (Abschlagfarben, Schriften, Schatten) – keine Farbwerte |
| `etch/kontrast.mjs` | Prüft die Kontraste (WCAG 2.1 AA) aller wichtigen Farbkombinationen in Hell und Dunkel; Exit-Code 1 bei Fehlern |
| `etch/acss-farben.mjs` | Club-Palette für das ACSS-Farbsystem; daraus entstehen `dist/daten/acss-farben.json` (für `golfplatz/acss-colors`) und `prototype/assets/css/farben.css` |
| `etch/build.mjs` | Schreibt `etch/dist/`: `template-<slug>.html`, `page-<slug>.html`, `component-<key>.html`, `golfplatz.css`, `manifest.json` (mit Loops), `daten/*.json` |

Das CSS der Komponenten ist dasselbe wie im Prototyp (`prototype/assets/css/main.css`). Es landet in Etch als globales Stylesheet „Golfplatz“.

## Ablauf

```bash
node wordpress/etch/build.mjs
```

Danach `etch/dist/*` nach `wp-content/mu-plugins/golfplatz/` und `mu-plugins/golfplatz-mcp.php` nach `wp-content/mu-plugins/` kopieren. Anschließend die MCP-Funktion `golfplatz/sync-from-files` aufrufen (`what`: `all`, `loops`, `components`, `templates`, `pages` oder `stylesheet`). Vorhandene Templates und Seiten werden per Slug bzw. Pfad aktualisiert, nicht doppelt angelegt.

**Wichtig:** Änderungen, die jemand im Etch-Editor an Templates oder Seiten macht, überschreibt der nächste Sync. Entweder im Repo **oder** im Editor arbeiten, nicht beides für dieselbe Seite.

## MCP-Funktionen (`golfplatz/…`)

| Funktion | Zweck |
| --- | --- |
| `list-content` | Seiten, Templates oder Komponenten auflisten |
| `get-content` | Block-Markup einer Seite, eines Templates oder einer Komponente lesen |
| `save-page`, `save-template`, `save-component`, `save-stylesheet` | einzeln speichern |
| `sync-from-files` | alles aus `mu-plugins/golfplatz/` übernehmen: erst Komponenten (per Key), dann Templates und Seiten, in denen `"__REF_<Key>__"` durch die Komponenten-ID ersetzt wird, dann das Stylesheet |
| `import-content` | Einträge aus `mu-plugins/golfplatz/daten/<typ>.json` anlegen oder aktualisieren (Schlüsselfeld aus der Datei, z. B. `bahn_nummer`). Erlaubt: `spielbahn`, `sperrung`, `person`, `preis`, `kurs`, `lochwettspiel`. Optional `content` (Beitragstext). Optional `terms` je Eintrag, z. B. `{ "personengruppe": ["Vorstand"] }` – fehlende Begriffe werden angelegt. Die Datei baut `etch/build.mjs` aus `prototype/src/data.mjs`. |
| `import-settings` | Felder aus `daten/einstellungen-<seite>.json` in `clubdaten` oder `platzstatus` schreiben; `null` entfernt ein Feld. **Mit `felder: [...]` nur die genannten Felder übernehmen** – ohne schreibt der Import alle Felder der Datei und überschreibt, was im Admin gepflegt wurde. |
| `save-settings-page` | Meta-Box-Einstellungsseite anlegen oder ändern (wie der Builder, bleibt im Builder bearbeitbar) |
| `acss-colors` | Farbeinstellungen von Automatic.css schreiben (nur Farbschlüssel: `color-*`, `option-*-clr`, OKLCH je Abstufung inkl. `-alt`, Farbschema `auto-color-scheme`, `website-color-scheme`, `color-scheme-force-*`, Button-Textfarben `btn-primary-text`, `btn-secondary-text` samt Hover), ACSS erzeugt danach sein CSS neu. `aus_datei: true` liest `daten/acss-farben.json`. Hex-Farben laufen über `API::update_settings()`, alles andere direkt über `Database_Settings`, weil die API jeden Schlüssel mit „color-“ als Hex-Farbe prüft. |
| `flush-permalinks` | wie „Einstellungen → Permalinks → Speichern“ |
| `set-front-page` | statische Startseite setzen |

Die Funktionen erlauben nur die Beitragstypen `page`, `wp_template` und `wp_block`. Templates und Stylesheets laufen über die REST-Routen von Etch selbst. Eine allgemeine REST-Weiterleitung gibt es bewusst nicht, weil sie die Sicherheitsprüfung als zu breite Angriffsfläche abgelehnt hat.

Für `spielbahn` sind zusätzlich die Meta-Box-eigenen Funktionen eingeschaltet (`meta-box/get-post-spielbahn`, `create-post-spielbahn`, `update-post-spielbahn`).

## Regeln für das Block-Markup

- **Backslashes in Attributen:** Die Template-Route von Etch speichert ohne `wp_slash()`. Das Plugin maskiert deshalb alle Inhalte vorab, und `lib.mjs` serialisiert Attribute wie WordPress selbst (JSON-Escapes für Anführungszeichen, `--`, `<`, `>`, `&`). So bleiben auch die Gruppen-Eigenschaften der EMMP-Komponenten intakt.
- **Dynamische Daten:** `{this.title}`, Meta-Box-Felder als `{this.metabox.<feld_id>}`, WYSIWYG-Felder über `raw()` (`etch/raw-html`).
- **Keine Shortcodes.** Rechnet PHP etwas, stellt es die Werte als Etch-Daten bereit (`etch/dynamic_data/option` → `{options.golfplatz.…}`, `etch/dynamic_data/post` → `{this|item.golfplatz.…}`); das Markup steht in der Komponente. Listen: `loop({ target: 'options.golfplatz.…', itemId: 'x' }, …)`, verschachtelt `loop({ target: 'x.liste', … })`. Varianten: `wenn('x.flag', …)`, sonst-Zweig `wenn('x.flag', …, 'isFalsy')`, Vergleich zweier Werte `wenn('z.key', …, '===', 'props.bereich')`. Modifier als Datenfeld in der Klasse (`condition--{b.mod}`).
- **Komponenten in Komponenten:** `komponente('<Key>', { … })` funktioniert auch innerhalb einer Komponente. Der Sync ersetzt `__REF_<key>__` der Reihe nach, deshalb stehen eingebundene Komponenten in `components` vorne.
- **Clubdaten** nie als Text ins Markup schreiben, sondern immer `club('<feld>')` bzw. `telHref('<feld>')` verwenden.
- **Bedingungen:** `wenn('this.metabox.feld', [...])` blendet einen Abschnitt aus, wenn das Feld leer ist.
- **Loops:** `loop({ loopId: 'gp-bahnen' }, [...])`, Preset in `etch/loops.mjs`. Im Loop `{item.metabox.<feld>}`, `{item.permalink.relative}` und die berechneten Bahndaten `{item.golfplatz.…}`.
- **Inhalte nicht direkt per MCP-Aufruf übergeben:** Beim direkten Übergeben an `save-page` gehen die Backslashes aus `\u0022` verloren. Immer über Generator, Datei und `sync-from-files` arbeiten.
- **Template-Dateien** heißen `template-<slug>.html`, damit ein Template wie `page-birdiebook` nicht mit der Seiten-Datei `page-birdiebook.html` kollidiert.
- Styles hängen an den BEM-Klassen im globalen Stylesheet, nicht an Etch-Style-IDs.

## Stand 2026-09-25

| Was | Status |
| --- | --- |
| Einstellungsseiten | `clubdaten` (8 Tabs, zentrale Clubdaten mit Platzhalterwerten) und `platzstatus` |
| Komponenten | „Footer“ (122), „Als Gast spielen“ (121, früher „Startzeit reservieren“), „Öffnungszeiten Sekretariat“ (120), alle lesen aus `clubdaten` |
| Spielbahnen | alle 18 angelegt („Bahn 1“ … „Bahn 18“, `/platz/bahn/1/` … `/platz/bahn/18/`), Daten aus dem Prototyp |
| Template `single-spielbahn` | angelegt (ID 109), mit Footer |
| Startseite `/` | angelegt (ID 153), als Startseite gesetzt: Hero mit Platzstatus-Kurzfassung, ausführlicher Platzstatus, Einstiege, „Als Gast spielen“. News, Platz-Teaser, Ligaspiele und Restaurant folgen mit den Loops. |
| Platzstatus | serverseitig über `golfplatz-platzstatus.php`, 6 Beispiel-Sperrungen (`beispiel-1` … `beispiel-6`, relativ zum Importtag, laufen also ab) |
| Seite `/platz/` | angelegt (ID 111), Seitenkopf und Platzbeschreibung |
| Seite `/greenfee/` | angelegt (ID 124), Seitenkopf, „Als Gast spielen“ (`#spielen`), Öffnungszeiten; Preisliste fehlt noch (Loop) |
| Platzhalter-Seiten | Spielvorgaben, Mitgliedschaft, Turniere, Golfschule, Restaurant, Aktuelles, Club & Kontakt, Mitgliederbereich, Impressum, Datenschutz – nur Seitenkopf, damit kein Menüpunkt ins Leere führt |
| Header | EtchMegaMenuPro im Index- und im Spielbahn-Template (siehe unten) |
| Stylesheet „Golfplatz“ | angelegt (`db4715d`), enthält `tokens.css`, das Prototyp-CSS und `emmp.css` |
| Loops (Bahnkarten, Scorekarte, Preise …) | offen, Etch-Loops liegen in der Option `etch_loops`, dafür fehlt noch eine Funktion |

### Header mit EtchMegaMenuPro

- Beschrieben in `etch/header.mjs`: Top-Bar (Platzstatus-Ampel, Telefon, Mitglieder-Login), Logo, Hauptnavigation mit Dropdown „Golf spielen“, letzter Menüpunkt „Als Gast spielen“ als Button, mobiler Menü-Button „Menü/Schließen“.
- Die EMMP-Komponenten sind in Etch hinterlegt und werden per WordPress-ID eingebunden: Header 71, Nav 69, Dropdown 68, Menu Item 67, Mobile Toggle 70. Nach einer Migration die IDs prüfen.
- Menüpunkte ändern: `navigation` in `etch/header.mjs`, dann bauen und synchronisieren.
- Farben und Schriftgröße: `etch/css/emmp.css` setzt die EMMP-Variablen (`--menu-cta-bg`, `--menu-item-hover-clr` …) auf ACSS-Farben.
- **Offen:** EMMP markiert den aktiven Menüpunkt nur bei exakt gleicher URL. Auf Unterseiten wie `/platz/bahn/9/` ist der Elternbereich noch nicht markiert.
- **Offen:** Der Wischhinweis im mobilen Untermenü („Swipe > back to …“) steht fest in der EMMP-Nav-Komponente und ist englisch.

### Platzstatus: PHP rechnet, Etch gestaltet

Der Status rechnet relativ zu „jetzt“: Schnellsperre vor geplanter Sperre, abgelaufene Sperren fallen weg, Aufteilung in heute und morgen, Öffnungszeiten, Fahnenpositionen. Diese Logik bleibt im PHP-Modul `golfplatz-platzstatus.php`.

Das Markup des großen Blocks ist dagegen die **Etch-Komponente „Platzstatus“** (Generator `etch/platzstatus.mjs`).
- **Daten:** Das Modul stellt die fertigen Werte über den Etch-Filter `etch/dynamic_data/option` als `{options.golfplatz.platzstatus.<feld>}` bereit, im Builder und auf der Website. Darunter liegen:
  - `stand`, `platz_gesperrt`, `platz_gesperrt_grund`, `platz_gesperrt_bis`
  - `bedingungen[]` mit `mod`, `label`, `wert` und `info`
  - `fahnen`: `vorhanden`, `label`, `info` (Hinweis aus dem Steckplan), `standard` („Position 3“), `standard_nr`, `hat_ausnahmen`, `ausnahmen[]` (`bahn`, `nr`, `text`, `lage` wie „hinten rechts“) und `hinweis`
  - `tage[]` mit `label`, `datum`, `frei` und `eintraege[]` (`bereich`, `zeit`, `grund`, `laeuft`, `klasse`)
  - `einrichtungen[]` mit `name`, `mod`, `zustand` und `infos[]`
- **Aufbau der Komponente:** Sie baut daraus mit Loops (`target`) und Bedingungen dasselbe BEM-Markup wie vorher. Texte, Reihenfolge und Überschriften lassen sich im Builder ändern.
- **Ampel und Hero:** Ampel (`ampel.zustand`, `ampel.text`) und Kurzfassung (`kurz.*`: `zustand`, `text`, `hat_heute`, `heute[]`, `mehr`, `chips[]`) liegen im selben Datensatz.
- **Geprüft (2026-09-26):** Alle früheren Shortcodes sind durch Komponenten ersetzt. Die Ausgabe ist Baustein für Baustein identisch mit der Shortcode-Version: Hero, Ampel, Umschalter, Platzstatus, Footer, Gast-Info, Öffnungszeiten, Rating, Scorekarte, Bahnkarten, Birdiebook, App-Leiste. Sperren, Fahnen und Hindernisse wurden mit Testwerten geprüft; die Testwerte sind wieder entfernt. Es gibt keine Shortcodes mehr.

**Caching:** Wird auf der Live-Seite ein Seitencache eingesetzt, die Startseite ausnehmen oder den Cache sehr kurz halten (z. B. 5 Minuten), sonst ist der Platzstatus veraltet.

### Etch- und ACSS-Eigenheiten

- **Farben nur aus ACSS.** Das CSS nutzt ausschließlich ACSS-Farbvariablen (Konvention in `docs/development-environment.md`). ACSS v4 rechnet mit OKLCH-Schlüsseln je Abstufung (`<farbe>[-<stufe>]-l|c|h-oklch`); `color-<farbe>` allein ändert nichts. `option-palette-unify-*-lightness` muss aus sein, sonst überschreibt ACSS die Helligkeiten.

- ACSS/Etch geben jedem `<section>` per `:where()` `display:flex`, `gap` und seitliches Padding. `etch/css/tokens.css` neutralisiert das für Abschnitte mit Klasse (`section:where([class])`).
- Etch filtert SVG aus Raw-HTML. Icons deshalb als Etch-Elemente (`icon()` in `lib.mjs`) oder per CSS-Maske; die Bahngrafik ist HTML/CSS.
