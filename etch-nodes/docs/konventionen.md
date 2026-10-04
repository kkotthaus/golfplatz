# Konventionen für Etch-Projekte

Regeln für Komponenten, Block-Markup, Daten, CSS, Hell/Dunkel und KI-Kennzeichnung. Platzhalter: `<prefix>` steht für das Kürzel des jeweiligen Projekts (Datenschlüssel, Funktionsnamen, Ordner), `<PREFIX>` für dessen Konstanten.

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
- **OhMyEtch zuerst für interaktive Bausteine.** Accordion, Tabs, Dialog, Drawer, Lightbox, Carousel, Breadcrumbs, Table of Contents und Facets (Filter, Suche, Pagination) von OhMyEtch verwenden statt eigener Lösungen mit eigenem JavaScript – sie bringen Tastaturbedienung, ARIA und Animation mit. Eigene Komponenten liefern nur das Aussehen (BEM-Klassen bzw. die `ome-*`-Klassen im globalen Stylesheet). **Ausnahme: Navigation und Menüs** – die OhMyEtch-Elemente Navigation Menu und Navigation Menu Mobile werden nicht genutzt; Navigation per EMMP oder eigener Komponente.
- **OhMyEtch einbinden per Key statt ID:** Platzhalter `"__REF_<Key>__"` mit dem Komponenten-Key (z. B. `OmeAccordion`, `OmeAccordionItem`, `OmeAccordionHeader`, `OmeAccordionTrigger`, `OmeAccordionContent`, `OmeBreadcrumbs`, `OmeTableOfContents`); der Sync löst ihn über `etch_component_html_key` zur ID der Installation auf. Gruppen-Eigenschaften als `{{…}}`-JSON (z. B. `settings: '{{"type":"multiple"}}'`), nur die geänderten Werte. Beim Accordion-Trigger kommt `content.label` nicht an – Frage als Slot-Inhalt des Triggers übergeben.
- **Weitere Fremdkomponenten** (EMMP, EtchSliderPro) werden per WordPress-ID eingebunden, mit Slots über `etch/slot-content`. Nie löschen und neu anlegen, sonst stimmt die ID nicht mehr. Nach einer Migration die IDs prüfen (Duplicator erhält sie). Die verwendeten IDs im Projekt dokumentieren.

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

- **Automatic.css zuerst.** Vorhandene ACSS-Klassen (z. B. `btn--primary`, `btn--outline`, `btn--s`) und ACSS-Variablen (Farben, `--space-*`, `--section-space-*`, `--text-*`, `--h1`…`--h6`, `--radius*`, `--content-width`, `--gutter`, `--grid-gap`, `--transition`, `--btn-*`) haben Vorrang. Was sich über die ACSS-Einstellungen regeln lässt (z. B. Rundung, Schriftstärke und Innenabstand der Buttons), wird dort eingestellt – per Datei im Repo und MCP-Funktion übertragen, nicht im eigenen CSS nachgebaut. Eigene Klassen und Variablen nur, wenn ACSS dafür nichts bietet oder es nicht funktioniert; den Grund dann kurz im CSS kommentieren. Keine eigenen Klassen, die ACSS-Klassen nachbilden (etwa eigene Button-Klassen).
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
- Eigene Tokens nur für das, was ACSS nicht kennt (z. B. Schriften, Schatten) – vorher in den ACSS-Variablen nachsehen. Abstände, Schriftgrößen, Radius und Container kommen aus ACSS. Welche Werte gelten (Farben, Schriften, Schatten, ACSS-Einstellungen), legt das Design des Projekts fest.
- **EMMP anpassen** über seine Variablen (`--menu-item-clr`, `--menu-cta-bg`, `--dropdown-content-bg` …), gesetzt mit doppelter Klasse (`.dwc-top-level-items-vars.dwc-top-level-items-vars`), damit sie unabhängig von der Ladereihenfolge gewinnen. Werte nach dem Design des Projekts.
- EMMP markiert den aktiven Menüpunkt selbst mit `aria-current="page"`, aber nur bei exakt gleicher URL (Elternbereiche auf Unterseiten nicht).
- **Tabellarische Daten** in Komponenten als CSS-Grid aus `div`s mit Tabellen-Rollen (`role="table|row|columnheader|rowheader|cell"`, Name per `aria-label`). Breite Tabellen seitlich scrollbar, erste Spalte bei Bedarf fixiert.

## Hell/Dunkel

**Standard für jede Website:** helles und dunkles Farbschema. Welche Farben in welchem Schema gelten, legt das Design des Projekts fest.

- ACSS-Einstellungen `auto-color-scheme: on` und `website-color-scheme: light dark`. Ohne Wahl folgt die Seite der Einstellung des Geräts (`prefers-color-scheme`).
- Im CSS nur ACSS-Farbvariablen verwenden. ACSS rechnet sie mit `light-dark()` und tauscht im dunklen Schema die Abstufungen (`--white` wird schwarz). Feste Farbwerte wirken in beiden Schemata gleich und brechen das dunkle Schema.
- Flächen, die in beiden Schemata gleich aussehen sollen (z. B. dunkle Markenflächen mit heller Schrift), über die ACSS-Einstellung `color-scheme-force-light-selectors` festlegen, nicht mit eigenen Farben.
- **Bilder je Schema** (z. B. Logo in heller und dunkler Variante): beide Bilder ausgeben und per CSS-Klasse umschalten, nicht per `<picture>`/`<source media="(prefers-color-scheme: dark)">` – die Media-Query sieht nur das Gerät, nicht einen Umschalter:
  ```css
  html .scheme-bild--dunkel { display: none; }
  @media (prefers-color-scheme: dark) {
    html:not(.scheme--light) .scheme-bild--hell { display: none; }
    html:not(.scheme--light) .scheme-bild--dunkel { display: block; }
  }
  html.scheme--dark .scheme-bild--hell { display: none; }
  html.scheme--dark .scheme-bild--dunkel { display: block; }
  ```
  `html` vor der Klasse, damit die Regel gegen BEM-Klassen mit eigenem `display` gewinnt.
- Kontraste in **beiden** Schemata prüfen (WCAG 2.1 AA), auch die festgelegten Flächen.

**Optional: Umschalter** (je Projekt entscheiden):

- Schaltet die ACSS-Klassen `scheme--light` bzw. `scheme--dark` am `<html>`. Damit rechnen alle ACSS-Farben im gewählten Schema.
- Die Wahl im `localStorage` speichern (Schlüssel z. B. `<prefix>-farbschema`) und per Inline-Skript im `<head>` setzen, bevor die Seite gezeichnet wird – sonst blitzt kurz das andere Schema auf. Entspricht die neue Wahl der Geräteeinstellung, die gespeicherte Wahl löschen; die Seite folgt dann wieder dem Gerät.
- Schaltfläche `<button type="button">` in einer von zwei Varianten – je Projekt eine wählen, nicht mischen:
  - **Schalter mit festem Namen:** `aria-pressed="true|false"` und ein gleichbleibender, sichtbar verborgener Name (z. B. „Dunkles Design“); den Zustand meldet `aria-pressed`. Passt zu einem reinen Symbol-Knopf. Symbol je Zustand per CSS über `[aria-pressed]`.
  - **Aktion mit sichtbarer Beschriftung:** Die sichtbare Beschriftung nennt das Ziel und wechselt mit dem Zustand („Dunkel“ ↔ „Hell“), ein sichtbar verborgenes Präfix ergänzt den Namen (z. B. „Farbschema “). Der zugängliche Name entsteht aus dem Text – **kein** `aria-pressed` (sonst widersprechen sich Name und Zustand) und kein `aria-label`. Den Zustand für das CSS als Datenattribut setzen (z. B. `data-farbschema="hell|dunkel"`), das Symbol hängt daran. Optional ein `title` mit der Aktion.
  - In beiden Varianten den Zustand aus dem tatsächlich wirksamen Schema ableiten (Klasse am `<html>`, sonst Geräteeinstellung) und bei einem Wechsel der Geräteeinstellung (`matchMedia(…).addEventListener('change', …)`) neu anzeigen. Ohne JavaScript ausblenden (Klasse `js` am `<html>`) oder mit einer Beschriftung ausliefern, die ohne Skript nicht irreführt.
- `localStorage`-Zugriffe in `try`/`catch` (gesperrter Speicher, private Fenster); die Umschaltung funktioniert dann für die aktuelle Seite trotzdem.
- In der Datenschutzerklärung erwähnen: Die Wahl wird nur lokal im Browser gespeichert, nicht übertragen; unbedingt erforderlich für die gewünschte Funktion (§ 25 Abs. 2 Nr. 2 TDDDG).

## KI-Kennzeichnung

**Standard für jede Website:** Bilder und Videos, die mit KI erzeugt oder verändert wurden, sind auf der Website als solche gekennzeichnet (EU-KI-Verordnung Art. 50, seit 2. August 2026; dazu Wettbewerbsrecht, wenn Bilder sonst über Tatsachen täuschen könnten). **Form und Farbe des Symbols sowie alle Texte** (Kennung, Erklärung, Beschriftungen im Backend, Handbuch) legt das Projekt fest. Hier stehen nur Datenmodell, Technik und Barrierefreiheit.

**Datenmodell** (Meta Box, im Code registriert, Snippet `<prefix>-ki.php`, gehört auf die Live-Seite):

- Felder am Anhang (`attachment`, auch im Medien-Dialog: `media_modal`):
  - `ki_art` – Auswahl der KI-Nutzung mit festen Schlüsseln `ai` (KI wurde verwendet), `generated` (vollständig von KI erzeugt), `modified` (durch KI verändert); **leer = keine KI**. Maßgeblich ist nur dieses Feld.
  - `ki_werkzeug` – optional, Name des Werkzeugs.
  - `ki_position` – optional, Position des Symbols an diesem Bild (überschreibt die Einstellung, z. B. wenn das Symbol etwas Wichtiges verdeckt).
- Einstellungsseite unter **Medien › KI-Kennzeichnung** (Recht `edit_pages`): Position, Stil, Größe, Werkzeug nennen ja/nein. Welche Stile und Größen es gibt, bestimmt das Projekt.
- Optional ein eigenes Symbol als Bild in den Stammdaten des Projekts (z. B. das offizielle EU-Symbol, sobald es veröffentlicht ist); ohne Bild erscheint das Text-Kürzel des Projekts.

**Technik:**

- Eine PHP-Funktion `<prefix>_ki_daten( $attachment_id )` liefert alles, was die Darstellung braucht: `hat`, `kurz`, `logo`, `hat_logo`, `label`, `zusatz`, `text`, `mod` (Klassen-Modifier für Position, Stil, Größe), `alt` (Hinweis für den Alternativtext). Ungültige oder leere Werte → `hat: false`.
- **Etch-Komponenten:** Die Daten je Bild als dynamische Daten mitgeben (z. B. `{item.<prefix>.bild_ki.…}` aus dem `etch/dynamic_data/post`-Filter) und das Symbol über einen Generator-Baustein einsetzen (z. B. `kiPlakette('<pfad>')`), in einer Bedingung auf `….hat`.
- **Bilder aus dem Editor** (Bild, Beitragsbild, Cover) per `render_block`-Filter mit `WP_HTML_Tag_Processor`: Klasse `ki-bild` am äußeren Element, Bild (samt Link) in `span.ki-bild__rahmen` als Bezug für die Position, Symbol direkt danach. Gleiches Markup wie in den Komponenten.
- **Markup (BEM):** `.ki-plakette` mit Modifiern für Position/Stil/Größe (`ki-plakette--<wert>`, `ki-plakette--logo` bei Bild-Symbol), darin `.ki-plakette__icon` (Kürzel oder `.ki-plakette__logo`) und `.ki-plakette__text` (Kennung und Erklärung). Das Symbol ist immer sichtbar; Kennung und Erklärung klappen beim Darüberfahren bzw. Antippen auf (`:hover`, `:active`), Animation bei `prefers-reduced-motion` aus. Abstand zur Bildecke an deren Rundung ausrichten.
- Farben der Stile nur aus ACSS-Variablen; Lesbarkeit auf beliebigen Bildern sicherstellen (deckender Hintergrund statt Transparenz) und in beiden Farbschemata prüfen.

**Barrierefreiheit:**

- Das sichtbare Symbol ist `aria-hidden="true"` (mit `title` für Maus-Nutzer). Für Screenreader wird der Hinweis an den **Alternativtext** des Bildes angehängt (`<alt> – <Kennung>: <Erklärung>`), auch bei Bildern aus dem Editor.
- Bei Bildern ohne eigenen Alternativtext (dekorativ) trotzdem den Hinweis als Alternativtext setzen – die Kennzeichnung ist eine Information.

**Redaktion:** Im Handbuch des Projekts erklären, wann welche Stufe gilt, und darauf hinweisen, dass Fotos echter Personen nicht ohne deren Einwilligung in KI-Dienste hochgeladen oder per KI verändert werden.

## Meta Box

- **Beziehungen** zwischen Beitragstypen als Feld vom Typ **Post** statt MB Relationships, wenn Strukturen per MCP angelegt werden – Relationships lassen sich darüber nicht anlegen.
- **Datumsfelder in Gruppen** speichert Meta Box im Anzeigeformat (z. B. `25.09.2026`), auch wenn als Speicherformat `Y-m-d` eingestellt ist. Beim Lesen beide Formate (und Zeitstempel) akzeptieren.
- **Einstellungsseiten** so anlegen, wie es der Meta-Box-Builder tut, damit sie im Builder bearbeitbar bleiben.
- Ein Teil des Datenmodells steht nur in der Datenbank (Builder). Beitragstypen, Taxonomien, Feldgruppen und Einstellungsseiten deshalb per Export-Skript (WP-CLI `wp eval-file`, nur lesend, im Backend-Kontext) als JSON ins Repo holen und nach jeder Änderung neu exportieren.
- Import-Dateien beim Import nur gezielt übernehmen (Feldliste), sonst überschreibt ein Import, was im Backend gepflegt wurde.

## Barrierefreiheit

- Ziel ist WCAG 2.1 AA. Farbkontraste regelt das Projekt, geprüft in beiden Farbschemata (siehe [Hell/Dunkel](#helldunkel)).
- Ausgeblendete Elemente (z. B. inaktive Slides, geschlossene Menüs) dürfen per Tab nicht erreichbar sein.
- Der zugängliche Name von Schaltflächen entspricht dem sichtbaren Text (WCAG 2.5.3); kein abweichendes `aria-label`.
- Tabellarische Daten mit Tabellen-Rollen (siehe [CSS](#css)), Screenreader-Ansagen bei dynamischen Wechseln (z. B. „Element 3 von 18“).
