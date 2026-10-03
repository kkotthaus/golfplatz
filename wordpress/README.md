# WordPress-Aufbau per MCP

Seiten, Etch-Templates und das CSS werden im Repo als Code beschrieben, gebaut und über den MCP-Adapter in die lokale WordPress-Installation (golfplatz.local) übertragen.

## Bausteine

| Pfad | Zweck |
| --- | --- |
| `snippets/golfplatz-mcp.php` | WPCodeBox-Snippet: eng begrenzte MCP-Funktionen (nur Administratoren), nur für die Entwicklung |
| `snippets/golfplatz-platzstatus.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: berechnet Platzstatus, Ampel, Hero-Kurzfassung, Fahnenpositionen und Öffnungszeiten und stellt sie Etch als Daten bereit: `{options.golfplatz.platzstatus.…}` (inkl. `ampel`, `kurz`, `fahnen`) und `{options.golfplatz.zeiten}`. Keine Shortcodes – das Markup bauen die Komponenten Platzstatus, PlatzstatusKurz, Ampel, Oeffnungszeiten, OeffnungszeitenAlle. |
| `snippets/golfplatz-farbschema.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: Skripte zum Umschalter Hell/Dunkel (Knopf: Etch-Komponente FarbschemaUmschalter, `[data-scheme-toggle]`). Setzt die ACSS-Klasse `scheme--dark` am `<html>`, merkt sich die Wahl im Browser und setzt sie schon im `<head>`, damit nichts aufblitzt. |
| `etch/lib.mjs` | Erzeugt Etch-Block-Markup (`etch/element`, `etch/text`, `etch/raw-html`, `etch/condition`, `etch/loop`) |
| `etch/templates.mjs` | Feste Seiten (`pages`) und Templates (`templates`) |
| `etch/header.mjs` | Header mit Hauptnavigation auf Basis von EtchMegaMenuPro |
| `etch/platzstatus.mjs` | Komponenten Platzstatus (Startseite), PlatzstatusKurz (Hero), Ampel (Top-Bar, App-Leiste) aus `{options.golfplatz.platzstatus.*}` |
| `etch/preise.mjs` | Komponente Preistabelle (Eigenschaft `kategorie`: greenfee, turnier, kooperationen, leihe): Matrix Mo–So/Feiertag, wenn die Kategorie Tage hat, sonst Liste. Daten `{options.golfplatz.preise}` |
| `snippets/golfplatz-preise.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: Preise je Preiskategorie als `{options.golfplatz.preise.jahr|spalten|tabellen[key, name, matrix, bloecke[titel, zusatz, einheit, zellen[wert], unter[…]], zeilen[…], karten[titel, betrag, einheit, zusatz, aufnahme, leistungen[text], hervorheben, mod, button, button_mod]]}`, Texte fertig („40 €“, „auf Anfrage“, „18 Loch“). Tage aus „Gültig an“, Varianten als Unterzeilen. Dazu `{options.golfplatz.twilight.…}`: Regel aus den Clubdaten und heutige Startzeit (Sonnenuntergang am Platz minus `twilight_stunden`). Keine Shortcodes. |
| `etch/preise.mjs` (Preiskarten) | Komponente Preiskarten: Preise einer Kategorie als Karten (`kategorie`, `ziel`), genutzt auf `/mitgliedschaft/` |
| `etch/zeiten.mjs` | Komponenten Oeffnungszeiten (ein Bereich, Eigenschaften `bereich`, `variante`, `liste`, `titel`) und OeffnungszeitenAlle aus `{options.golfplatz.zeiten}` |
| `etch/birdiebook.mjs` | Komponenten Birdiebook (EtchSliderPro), Scorekarte, Rating, SpielvorgabenRechner, SpielvorgabenTabellen und das Bahnenraster für `/platz/` |
| `etch/zaehlkarte.mjs` | Komponente Zählkarte: Handicap-Index und Abschlag wählen, Vorgabeschläge je Loch (nach Loch-HCP, Plus-Vorgabe ab HCP 18 zurück), Schläge eintragen → Netto je Loch, Stableford brutto/netto, Summen Out/In/Gesamt. Daten `{options.golfplatz.platz.spielvorgaben}` und `{options.golfplatz.platz.zaehlkarte}`; Skript `prototype/assets/js/zaehlkarte.js` (build kopiert es nach `dist/`, eingebunden von `golfplatz-birdiebook.php`), Eingaben nur im Browser (localStorage); „Ergebnis teilen“ als HTML-Datei (Web Share API) bzw. HTML-Tabelle in der Zwischenablage, „Als HTML speichern“ als Download, Link `#runde=…` |
| `etch/lochwettspiel.mjs` | Komponente Lochwettspiel: Turnierbaum eines Jahres (Eigenschaft `jahr`: `aktuell` oder Jahreszahl) aus `{options.golfplatz.lochwettspiele}`; bei mehr als 16 Teams frühere Runden als aufklappbare Listen (`<details>`), Baum ab Achtelfinale |
| `snippets/golfplatz-lochwettspiel.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: rechnet aus Teams, Spielzeiträumen und Ergebnissen des Beitragstyps `lochwettspiel` den Turnierbaum (Setzung mit Freilosen, Sieger rücken weiter, Status je Runde und Spiel relativ zu heute) und liefert ihn als `{options.golfplatz.lochwettspiele}`. Keine Shortcodes. Bei Seitencache `/turniere/` höchstens einen Tag cachen. |
| `etch/mannschaften.mjs` | Bausteine der Templates `archive-mannschaft` (Übersicht + `#ligaspiele`), `single-mannschaft` und `single-spielbericht`; Ligaspiel-Tabelle als CSS-Grid |
| `snippets/golfplatz-mannschaften.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: Mannschaften, Ligaspiele und Berichte als `{options.golfplatz.mannschaften}`, je Mannschaft `{this.golfplatz.team}`, je Bericht `{this.golfplatz.bericht}`; Spieler nur mit Einwilligung. Keine Shortcodes. |
| `snippets/golfplatz-liga-sync.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: Abgleich der Ligaspiele mit dem Ligaportal des Landesverbands auf liga.golf (GraphQL; Adressen und Suchbegriff in den Clubdaten › Gäste & Systeme, leer = aus): Mannschaften, Spieltage, Orte, Ergebnisse, Gastclubs; täglich per Cron, von Hand unter Mannschaften → Verband-Abgleich, per MCP `golfplatz/liga-sync` |
| `snippets/golfplatz-turniere.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: liest Turnierkalender und Ergebnisliste aus PC CADDIE://online (Club-Kennung `pccaddie_code`) sowie die Kalender der Partnerclubs (Clubdaten `partnerclubs`; ohne Kennung kein stündlicher Abruf), Beitragstyp `turnier`, stündlich per Cron, von Hand unter Turniere → PC-CADDIE-Abgleich, per MCP `golfplatz/turniere-sync`; Daten `{options.golfplatz.turniere}` |
| `snippets/golfplatz-tee-belegung.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: erzeugt aus den Turnieren des Heimatclubs Sperrungen von Abschlag 1/10 nach Regeln (Einstellungsseite `tee-belegung` unter Sperrungen, Option `tee_belegung`) und Ausnahmen am Turnier (`tb_startform`, `tb_tee`, `tb_vorlauf`, `tb_dauer`); nach jedem PC-CADDIE-Abgleich (Hook `golfplatz_pcc_nach_abgleich`) und beim Speichern. Erkennung der Sperrungen über `sperr_quelle` = `turnier:<ID>:<tee>` |
| `snippets/golfplatz-golfschule.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: Kurse (`kurs`) als `{options.golfplatz.kurse.liste[]}` und je Kurs `{this.golfplatz.kurs}` (Kursart, Text, Preis „35 €“/„auf Anfrage“/„kostenlos“, Dauer, max. Teilnehmer, künftige Termine, Golflehrer, Anmeldung); Kurse mit nur vergangenen Terminen fallen aus der Liste |
| `etch/golfschule.mjs` | Komponente Kurskarten, Anmeldehinweis und Inhalt des Templates `single-kurs` |
| `snippets/golfplatz-dashboard.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: Dashboard-Widget „Platzstatus“ (rechts oben, Daten aus `golfplatz_platzstatus_etch()`: Ampel, Spielbedingungen, Fahnen, heute/morgen, Übungsanlagen, Knöpfe Platzstatus/Neue Sperrung) und Widget „Termine der nächsten 14 Tage“ (oben links): Turniere des Heimatclubs mit Status und den Tee-Sperrungen aus `sperr_quelle`, übrige Sperrungen, Ligaspiele, Lochwettspiel-Fristen, aktive Schnellsperren; Links zum Bearbeiten, wenn erlaubt (`edit_posts` oder `edit_sperrungen`) |
| `snippets/golfplatz-handbuch.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: Backend-Seite „Handbuch“ (und Dashboard-Hinweis) mit der Bedienungsanleitung aus `docs/handbuch.md`; nur für Benutzer mit `edit_posts` oder `edit_sperrungen`. Inhalt `wp-content/golfplatz/handbuch.php` (mit Schutzzeile) erzeugt `etch/build.mjs` über `etch/handbuch.mjs` |
| `snippets/golfplatz-news.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: News-Beiträge unter `/news/<slug>/`, Beitragsliste mit Kategorie-Filter und Seitenzahlen, Mitglieder-Sperre ohne Volltext; Daten `{options.golfplatz.news}` und `{this.golfplatz.news}` |
| `etch/news.mjs` | Komponente Newskarten (Startseite und `/news/`), Bausteine für die Übersicht und das Template `single-post` |
| `snippets/golfplatz-club.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: Personen je Liste (Vorstand, Team, Captains, Golfschule, Jugend) und Anfahrt (Routenlink, Texte, Lageplan) für `/club/`; Daten `{options.golfplatz.personen}` und `{options.golfplatz.anfahrt}`. Dazu der Auftritt des Clubs `{options.golfplatz.club}` (Logo bzw. Wortmarke, Region, clubeigene Texte) und die Feldgruppe „Clubdaten · Auftritt & Texte“ |
| `etch/club.mjs` | Komponente Personenkarten und Anfahrt-Baustein für `/club/` |
| `etch/turniere.mjs` | Komponenten Turnierkalender (Heimatclub), Platzbelegung (Partnerclubs, Tabelle Tag × Club) und Turnierergebnisse für `/turniere/` |
| `etch/loops.mjs` | Etch-Loops (Presets in `etch_loops`), z. B. `gp-bahnen` über alle Spielbahnen |
| `snippets/golfplatz-birdiebook.php` | WPCodeBox-Snippet, **gehört auf die Live-Seite**: Bahndaten je Spielbahn als `{this|item.golfplatz.plan|status|fahne|entfernungen}` (Filter `etch/dynamic_data/post`), Scorekarte und Rating als `{options.golfplatz.platz.…}`, dazu Birdiebook-Skript und Web-App-Manifest. Keine Shortcodes. |
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

Danach `etch/dist/*` nach `wp-content/golfplatz/` kopieren (Konstante `GOLFPLATZ_DATEN`). Das Handbuch liegt dort als `handbuch.php` mit Schutzzeile, damit es über die URL nicht abrufbar ist.

**PHP als WPCodeBox-Snippets** (seit 2026-09-29, lokal umgestellt):

- Alle Dateien aus `snippets/` laufen als PHP-Snippets in WPCodeBox, Ordner „Golfplatz“.
  - Titel = „Plugin Name“ aus dem Dateikopf, Schlagwörter `golfplatz` und der Dateiname (daran erkennt der Abgleich das Snippet).
  - Ausführung: „Always“, Einfügepunkt **Root**.
  - Der Ordner `wp-content/mu-plugins/` ist leer.
- Das Repository ist die Quelle. Ablauf nach einer Änderung:
  1. `node wordpress/etch/build.mjs` (kopiert die Dateien nach `dist/snippets/`).
  2. `dist` nach `wp-content/golfplatz/` kopieren.
  3. MCP `golfplatz/snippets-sync` aufrufen. Er legt fehlende Snippets an und aktualisiert geänderten Code, alles über die WPCodeBox-Funktionen `wpcodebox/*`, also mit deren Freigaben und Protokoll.
  - Mit `aktivieren: true` schaltet er deaktivierte Snippets ein, aber nur, wenn keine gleichnamige Datei in `wp-content/mu-plugins/` liegt (sonst doppelte Funktionen).
  - `nur: ["golfplatz-turniere"]` beschränkt den Lauf auf einzelne Dateien.
- Regeln für den PHP-Code (`eval()`, `define()` statt `const`, kein `__DIR__`, Fehlerstatus `enabled`): [etch-nodes/docs/betrieb.md](../etch-nodes/docs/betrieb.md#php-als-wpcodebox-snippets). Statt `__DIR__` hier `GOLFPLATZ_DATEN` (`wp-content/golfplatz`).
- In den WPCodeBox-MCP-Einstellungen freigegeben: Create Folder, Create/Update/Enable/Disable Snippet.
- **`golfplatz-mcp` nur in der Entwicklung:** Das Snippet registriert seine Funktionen nur, wenn `wp_get_environment_type()` „local“ oder „development“ meldet (Local: „local“; live ohne Angabe „production“). Bewusst live nutzen: `define( 'GOLFPLATZ_MCP_LIVE', true );` in der `wp-config.php`.

## Veröffentlichen mit Duplicator

Die Live-Seite entsteht als Kopie der lokalen Seite mit **Duplicator Pro** (Dateien und Datenbank). Damit kommen WPCodeBox und seine Snippets (Tabellen `wp_wpcb_*`), der Ordner `wp-content/golfplatz/` und die Einstellungen automatisch mit. Duplicator ersetzt die Adresse `golfplatz.local` durch die Live-Domain.

**Vorher (lokal):**

1. `node wordpress/etch/build.mjs`, `dist` nach `wp-content/golfplatz/` kopieren, `golfplatz/sync-from-files` (`all`) und `golfplatz/snippets-sync` ausführen.
2. `wpcodebox/list-snippets` prüfen: alle Golfplatz-Snippets `enabled`.
3. Beispiel- und Testinhalte entfernen (siehe „Offen“ in `docs/umsetzung.md`: Beispiel-News, Beispiel-Lochwettspiele 2025/2026, Beispiel-Sperrungen).
4. Paket in Duplicator Pro erstellen; `wp-content/golfplatz` und die Tabellen `wp_wpcb_*` nicht ausschließen.

**Nachher (live):**

1. **WPCodeBox › MCP:** Endpunkt ausschalten bzw. die schreibenden Werkzeuge (Create/Update/Enable Snippet) entziehen. Die Freigaben stehen in der Datenbank (`wpcb_mcp_enabled`, `wpcb_mcp_allowed_tools`) und kommen sonst mit.
2. Plugin **MCP Adapter** deaktivieren, wenn live keine KI-Werkzeuge gebraucht werden.
3. Prüfen, dass live **nicht** `WP_ENVIRONMENT_TYPE` = `local`/`development` gesetzt ist (sonst wäre `golfplatz-mcp` aktiv).
4. Startseite (Platzstatus), `/turniere/`, Handbuch im Backend ansehen. Die Abgleiche (PC CADDIE stündlich, Liga täglich) laufen über WP-Cron beim ersten Seitenaufruf an.

**Achtung nach dem Livegang:** Ein erneutes Veröffentlichen mit Duplicator überschreibt die **komplette Live-Datenbank**, also auch alles, was inzwischen live gepflegt wurde (Platzstatus, Nachrichten, Sperrungen, Turnier-Absagen, Benutzer). Spätere Code-Änderungen deshalb nicht per Duplicator übertragen, sondern gezielt:

- **PHP:** Snippet im WPCodeBox-Editor der Live-Seite aktualisieren, per WPCodeBox Cloud abgleichen oder vorübergehend `GOLFPLATZ_MCP_LIVE` setzen und `golfplatz/snippets-sync` nutzen.
- **Etch-Seiten, Komponenten, CSS:** entsprechend `golfplatz/sync-from-files` live oder im Etch-Editor.

Anschließend die MCP-Funktion `golfplatz/sync-from-files` aufrufen (`what`: `all`, `loops`, `components`, `templates`, `pages` oder `stylesheet`). Vorhandene Templates und Seiten werden per Slug bzw. Pfad aktualisiert, nicht doppelt angelegt.

**Wichtig:** Änderungen, die jemand im Etch-Editor an Templates oder Seiten macht, überschreibt der nächste Sync. Entweder im Repo **oder** im Editor arbeiten, nicht beides für dieselbe Seite.

## MCP-Funktionen (`golfplatz/…`)

| Funktion | Zweck |
| --- | --- |
| `list-content` | Seiten, Templates oder Komponenten auflisten |
| `get-content` | Block-Markup einer Seite, eines Templates oder einer Komponente lesen |
| `save-page`, `save-template`, `save-component`, `save-stylesheet` | einzeln speichern |
| `sync-from-files` | alles aus `wp-content/golfplatz/` übernehmen: erst Komponenten (per Key), dann Templates und Seiten, in denen `"__REF_<Key>__"` durch die Komponenten-ID ersetzt wird, dann das Stylesheet |
| `import-content` | Einträge aus `wp-content/golfplatz/daten/<typ>.json` anlegen oder aktualisieren (Schlüsselfeld aus der Datei, z. B. `bahn_nummer`). Erlaubt: `spielbahn`, `sperrung`, `person`, `preis`, `kurs`, `lochwettspiel`. Optional `content` (Beitragstext). Optional `terms` je Eintrag, z. B. `{ "personengruppe": ["Vorstand"] }` – fehlende Begriffe werden angelegt. Die Datei baut `etch/build.mjs` aus `prototype/src/data.mjs`. |
| `import-settings` | Felder aus `daten/einstellungen-<seite>.json` in `clubdaten` oder `platzstatus` schreiben; `null` entfernt ein Feld. **Mit `felder: [...]` nur die genannten Felder übernehmen** – ohne schreibt der Import alle Felder der Datei und überschreibt, was im Admin gepflegt wurde. |
| `save-settings-page` | Meta-Box-Einstellungsseite anlegen oder ändern (wie der Builder, bleibt im Builder bearbeitbar) |
| `acss-colors` | Farbeinstellungen von Automatic.css schreiben (nur Farbschlüssel: `color-*`, `option-*-clr`, OKLCH je Abstufung inkl. `-alt`, Farbschema `auto-color-scheme`, `website-color-scheme`, `color-scheme-force-*`, Button-Textfarben `btn-primary-text`, `btn-secondary-text` samt Hover), ACSS erzeugt danach sein CSS neu. `aus_datei: true` liest `daten/acss-farben.json`. Hex-Farben laufen über `API::update_settings()`, alles andere direkt über `Database_Settings`, weil die API jeden Schlüssel mit „color-“ als Hex-Farbe prüft. |
| `flush-permalinks` | wie „Einstellungen → Permalinks → Speichern“ |
| `set-front-page` | statische Startseite setzen |
| `snippets-sync` | PHP-Dateien aus `wp-content/golfplatz/snippets/` in WPCodeBox anlegen bzw. aktualisieren (Ordner „Golfplatz“, Einfügepunkt Root), optional `aktivieren` und `nur` |

Die Funktionen erlauben nur die Beitragstypen `page`, `wp_template` und `wp_block`. Templates und Stylesheets laufen über die REST-Routen von Etch selbst. Eine allgemeine REST-Weiterleitung gibt es bewusst nicht, weil sie die Sicherheitsprüfung als zu breite Angriffsfläche abgelehnt hat.

Für `spielbahn` sind zusätzlich die Meta-Box-eigenen Funktionen eingeschaltet (`meta-box/get-post-spielbahn`, `create-post-spielbahn`, `update-post-spielbahn`).

## Regeln für das Block-Markup

Allgemeine Regeln (Serialisierung der Attribute, `__REF_<Key>__`, keine Inhalte direkt per MCP, Template-Dateinamen, BEM, Loops und Bedingungen): [etch-nodes/docs/konventionen.md](../etch-nodes/docs/konventionen.md). Projektspezifisch, mit den Helfern aus `etch/lib.mjs`:

- **Daten:** `{options.golfplatz.…}`, `{this|item.golfplatz.…}`; WYSIWYG-Felder über `raw()`.
- **Listen und Varianten:** `loop({ target: 'options.golfplatz.…', itemId: 'x' }, …)`, verschachtelt `loop({ target: 'x.liste', … })`; `wenn('x.flag', …)`, sonst-Zweig `wenn('x.flag', …, 'isFalsy')`, Vergleich `wenn('z.key', …, '===', 'props.bereich')`.
- **Komponenten einbinden:** `komponente('<Key>', { … })`; eingebundene Komponenten stehen in `components` (`etch/komponenten.mjs`) vorne.
- **Clubdaten** nie als Text ins Markup schreiben, sondern immer `club('<feld>')` bzw. `telHref('<feld>')` verwenden.
- **Loops:** `loop({ loopId: 'gp-bahnen' }, [...])`, Preset in `etch/loops.mjs`. Im Loop zusätzlich die berechneten Bahndaten `{item.golfplatz.…}`.

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

Allgemein: [etch-nodes/docs/konventionen.md](../etch-nodes/docs/konventionen.md#css) und [docs/farben.md](../docs/farben.md). Im Projekt:

- Palette in `etch/acss-farben.mjs`, Farben und Zuordnung in [docs/farben.md](../docs/farben.md).
- `etch/css/tokens.css` neutralisiert den `<section>`-Standard von ACSS/Etch (`section:where([class])`).
- Icons als Etch-Elemente über `icon()` in `lib.mjs`; die Bahngrafik ist HTML/CSS.
