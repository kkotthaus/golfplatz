# Konventionen für Etch-Projekte

Regeln für Komponenten, Block-Markup, Daten und CSS. Platzhalter: `<prefix>` steht für das Kürzel des jeweiligen Projekts (Datenschlüssel, Funktionsnamen, Ordner), `<PREFIX>` für dessen Konstanten.

## Architektur

- **Etch-Komponenten statt Shortcodes.** Alles, was als Etch-Komponente gebaut werden kann, wird als Etch-Komponente gebaut. Rechnet PHP etwas (Status relativ zu „jetzt“, Summen, Öffnungszeiten), stellt es nur die fertigen Werte als dynamische Daten bereit:
  - global über den Filter `etch/dynamic_data/option` → `{options.<prefix>.<bereich>.…}`
  - je Beitrag über `etch/dynamic_data/post` → `{this.<prefix>.…}`, im Loop `{item.<prefix>.…}`
  - Markup, Texte und Klassen stehen in der Komponente und bleiben im Builder bearbeitbar.
  - PHP-Hooks ohne Markup im Inhalt (Skripte im Footer, Manifest, Head-Skripte) sind erlaubt.
- **PHP rechnet, Etch gestaltet.** Werte kommen fertig formatiert aus PHP („40 €“, „auf Anfrage“), damit die Komponente keine Logik braucht.
- **Das Repository ist die Quelle.** Seiten, Templates, Komponenten, Loops und das globale Stylesheet entstehen per Generator im Repo, werden gebaut (`dist/`) und per Sync nach WordPress übertragen. Der Sync überschreibt Änderungen aus dem Etch-Editor. Für dieselbe Seite **entweder** im Repo **oder** im Editor arbeiten, nie beides. Wurde doch im Editor geändert: zuerst ins Repo übernehmen, dann syncen.

## Komponenten

- Jede eigene Komponente hat einen festen **Key** ohne Leerzeichen. Über ihn findet der Sync die Komponente in WordPress wieder. Den Key nie ändern.
- Jede Komponente hat eine aussagekräftige `description`: Zweck und „Daten: …“ (woher die Daten kommen). Sie erscheint im Etch-Editor und eignet sich als Quelle für ein generiertes Komponenten-Handbuch.
- Eigenschaften (`properties`) immer mit sinnvollem Standardwert.
- **Einbinden eigener Komponenten:** Die WordPress-ID ist beim Bauen unbekannt. Das Markup enthält den Platzhalter `"__REF_<Key>__"`, den der Sync durch die echte ID ersetzt. Das funktioniert auch in Komponenten; die Ersetzung läuft der Reihe nach, deshalb stehen eingebundene Komponenten in der Liste **vor** den Komponenten, die sie nutzen.
- **Fremdkomponenten** (EMMP, EtchSliderPro, OhMyEtch) werden per WordPress-ID eingebunden, mit Slots über `etch/slot-content`. Nie löschen und neu anlegen, sonst stimmt die ID nicht mehr. Nach einer Migration die IDs prüfen (Duplicator erhält sie). Die verwendeten IDs im Projekt dokumentieren.

## Block-Markup

- Format wie der Etch-Editor selbst speichert: `etch/element`, `etch/text`, `etch/raw-html`, `etch/condition`, `etch/loop`, `etch/component`, `etch/slot-content`.
- **Attribute wie `serialize_block_attributes()` serialisieren:** JSON mit `"` für `\"`, `--` für `--`, `<`, `>`, `&`. Die Template-Route von Etch speichert ohne `wp_slash()`; der Sync muss Inhalte deshalb vorab maskieren.
- **Inhalte nie direkt als Parameter eines MCP-Aufrufs übergeben** – dabei gehen die Backslashes aus `"` verloren. Immer Generator → Datei → Sync.
- Etch filtert **SVG aus Raw-HTML**. Icons als Etch-Elemente (`svg`/`path` als `etch/element`) oder per CSS-Maske.
- Dateinamen im Build: `template-<slug>.html`, `page-<slug>.html`, `component-<Key>.html`. Das Präfix `template-` verhindert, dass ein Template wie `page-<x>` mit der Seiten-Datei `page-<x>.html` kollidiert.
- Seiten und Templates per Slug bzw. Pfad aktualisieren, nicht doppelt anlegen.

## Dynamische Daten, Loops, Bedingungen

- Beitrag: `{this.title}`, Meta-Box-Felder `{this.metabox.<feld_id>}`, WYSIWYG-Felder über `etch/raw-html`.
- Einstellungsseite (Meta Box): `{options.metabox.<seiten_id>.<feld>}`. Stammdaten (Telefon, Adresse …) nie als Text ins Markup schreiben, immer aus der Einstellungsseite lesen. Modifier sind möglich, z. B. für `tel:`-Links `{….telefon.replaceAll(' ', '')}`.
- **Loops:** gespeicherte Presets (Option `etch_loops`, `loopId`) für WP-Queries, `target` für Listen aus dynamischen Daten. Verschachtelt `target: '<itemId>.liste'`. Im Loop `{item.metabox.<feld>}`, `{item.permalink.relative}`.
- **Bedingungen:** ohne Operator `isTruthy` (Abschnitt fehlt, wenn das Feld leer ist), Sonst-Zweig mit `isFalsy`, Vergleich z. B. `z.key === props.bereich`.
- **Varianten über Klassen-Modifier als Datenfeld:** `class="status status--{x.mod}"` statt verschiedener Markup-Zweige.

## CSS

- **Klassennamen nach BEM** (`block__element--modifier`), z. B. `main-nav__item--active`. Styles hängen an den BEM-Klassen im globalen Stylesheet, nicht an Etch-Style-IDs.
- **`body` nie mit `overflow: hidden`.** Im Etch-Builder ist `body` ein Flex-Container (`flex-direction: column`), EMMP macht `html` zu Flex mit 100 % Höhe, und der Builder gibt jedem Block `min-height: 1px`. Mit `overflow-x: hidden` schrumpft `body` auf Fensterhöhe und die Abschnitte überlappen im Builder (im Frontend fällt es nicht auf). Stattdessen:
  ```css
  body { overflow-x: clip; }
  body > * { flex-shrink: 0; }
  ```
- **`<section>`-Standard neutralisieren.** ACSS/Etch geben jedem `<section>` per `:where()` `display: flex`, `gap` und seitliches Padding. Abschnitte mit eigener BEM-Klasse bringen ihr Layout selbst mit:
  ```css
  section:where([class]) { display: block; gap: normal; padding-block: 0; padding-inline: 0; }
  section:where([class]) > :where(div, ul, ol) { gap: normal; }
  ```
  `section:where([class])` hat Spezifität 0,0,1: schlägt `:where()`, verliert gegen jede BEM-Klasse.
- ACSS setzt Überschriften- und Absatzabstände auf 0 – Abstände in Komponenten ausdrücklich setzen.
- ACSS steuert die Buttonbreite über `--btn-width` und lädt nach dem eigenen Stylesheet: `--btn-width: 100%` statt `width: 100%`.
- Eigene Tokens nur für das, was ACSS nicht kennt (z. B. Schriften, Schatten). Farben regelt das Projekt. Abstände, Schriftgrößen, Radius und Container kommen aus ACSS.
- **EMMP anpassen** über seine Variablen (`--menu-item-clr`, `--menu-cta-bg`, `--dropdown-content-bg` …), gesetzt mit doppelter Klasse (`.dwc-top-level-items-vars.dwc-top-level-items-vars`), damit sie unabhängig von der Ladereihenfolge gewinnen. Farbwerte nach den Farbregeln des Projekts.
- EMMP markiert den aktiven Menüpunkt selbst mit `aria-current="page"`, aber nur bei exakt gleicher URL (Elternbereiche auf Unterseiten nicht).
- **Tabellarische Daten** in Komponenten als CSS-Grid aus `div`s mit Tabellen-Rollen (`role="table|row|columnheader|rowheader|cell"`, Name per `aria-label`). Breite Tabellen seitlich scrollbar, erste Spalte bei Bedarf fixiert.

## Meta Box

- **Beziehungen** zwischen Beitragstypen als Feld vom Typ **Post** statt MB Relationships, wenn Strukturen per MCP angelegt werden – Relationships lassen sich darüber nicht anlegen.
- **Datumsfelder in Gruppen** speichert Meta Box im Anzeigeformat (z. B. `25.09.2026`), auch wenn als Speicherformat `Y-m-d` eingestellt ist. Beim Lesen beide Formate (und Zeitstempel) akzeptieren.
- **Einstellungsseiten** so anlegen, wie es der Meta-Box-Builder tut, damit sie im Builder bearbeitbar bleiben.
- Ein Teil des Datenmodells steht nur in der Datenbank (Builder). Beitragstypen, Taxonomien, Feldgruppen und Einstellungsseiten deshalb per Export-Skript (WP-CLI `wp eval-file`, nur lesend, im Backend-Kontext) als JSON ins Repo holen und nach jeder Änderung neu exportieren.
- Import-Dateien beim Import nur gezielt übernehmen (Feldliste), sonst überschreibt ein Import, was im Backend gepflegt wurde.

## Barrierefreiheit

- Ziel ist WCAG 2.1 AA. Farbkontraste regelt das Projekt.
- Ausgeblendete Elemente (z. B. inaktive Slides, geschlossene Menüs) dürfen per Tab nicht erreichbar sein.
- Der zugängliche Name von Schaltflächen entspricht dem sichtbaren Text (WCAG 2.5.3); kein abweichendes `aria-label`.
- Tabellarische Daten mit Tabellen-Rollen (siehe [CSS](#css)), Screenreader-Ansagen bei dynamischen Wechseln (z. B. „Element 3 von 18“).
