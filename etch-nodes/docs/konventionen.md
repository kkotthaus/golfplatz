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
- **OhMyEtch zuerst für interaktive Bausteine.** Accordion, Tabs, Dialog, Drawer, Lightbox, Breadcrumbs, Table of Contents und Facets (Filter, Suche, Pagination) von OhMyEtch verwenden statt eigener Lösungen mit eigenem JavaScript – sie bringen Tastaturbedienung, ARIA und Animation mit. Eigene Komponenten liefern nur das Aussehen (BEM-Klassen bzw. die `ome-*`-Klassen im globalen Stylesheet). **Ausnahme: Navigation und Menüs** – die OhMyEtch-Elemente Navigation Menu und Navigation Menu Mobile werden nicht genutzt; Navigation per EMMP oder eigener Komponente.
- **OhMyEtch einbinden per Key statt ID:** Platzhalter `"__REF_<Key>__"` mit dem Komponenten-Key (z. B. `OmeAccordion`, `OmeAccordionItem`, `OmeAccordionHeader`, `OmeAccordionTrigger`, `OmeAccordionContent`, `OmeBreadcrumbs`, `OmeTableOfContents`); der Sync löst ihn über `etch_component_html_key` zur ID der Installation auf. Gruppen-Eigenschaften als `{{…}}`-JSON (z. B. `settings: '{{"type":"multiple"}}'`), nur die geänderten Werte. Beim Accordion-Trigger kommt `content.label` nicht an – Frage als Slot-Inhalt des Triggers übergeben.
- **Slider und Karussells: Slider Pro for Etch** (Plugin `dwc-slider-pro-etch` mit den Komponenten DWC Slider Wrapper, DWC Slider, DWC Slide, DWC Slider Nav Button, Pagination, Progress, Play-Pause) – Standard für alle Slider, auch für Karten-Reihen (z. B. Personen, Bewertungen). Nicht das OhMyEtch-Carousel und kein eigenes JavaScript.
  - Aufbau: `DwcSliderWrapper` (Slot `Sliders_and_Controls`) → `DwcSlider` (Slots `Top__Controls`, `Slides`, `Bottom__Controls`) → je Eintrag `DwcSlide` (Slot `Content`); für dynamische Inhalte ein `etch/loop` im Slot `Slides` um den `DwcSlide`.
  - Einbinden per Key wie OhMyEtch (`"__REF_DwcSlider__"` usw., der Sync löst über `etch_component_html_key` auf).
  - Responsive Werte als Kurzform `"BASIS lg:X md:Y sm:Z"` (max-width), z. B. `layout.slidesPerPage: "4 lg:3 md:2 sm:1"`; die Grenzen je Slider über `breakpoints.laptopLg / tabletMd / phoneSm` (px), sonst 1120/1024/640.
  - Barrierefreiheit: `ariaLabel` setzen; Autoplay nur, wenn das Design es verlangt, dann mit Pause-Knopf (`autoplay.playPauseButton`).
- **Weitere Fremdkomponenten** (EMMP) werden per WordPress-ID eingebunden, mit Slots über `etch/slot-content`. Nie löschen und neu anlegen, sonst stimmt die ID nicht mehr. Nach einer Migration die IDs prüfen (Duplicator erhält sie). Die verwendeten IDs im Projekt dokumentieren.

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
- **Loop-Parameter:** Werte in `args` dürfen Parameter enthalten, auch in verschachtelten Listen: `'$id'`, mit Standardwert `'$limit ?? 3'`. Beim Einbinden setzt das Block-Attribut `loopParams` die Werte, z. B. `loopParams: { '$id': 'this.metabox.<feld>', '$limit': 6 }` – als Ausdruck (`this.…`, `item.…`), als Zahl oder als Text in Anführungszeichen (`'"title"'`). Ergibt ein Ausdruck nichts oder einen leeren Text, lässt Etch den Parameter weg und der Standardwert gilt. Der Generator-Baustein für `etch/loop` reicht `loopParams` als Attribut durch.
- **Allgemeine Loops** statt eigener Loops für jede kleine Abfrage. Je Beitragstyp bei Bedarf (Typ immer fest, kein `any` und kein Parameter – der Sync lässt nur freigegebene Beitragstypen zu):

  | Loop-ID | `args` (zusätzlich `post_type`, `post_status: 'publish'`) | Zweck |
  | --- | --- | --- |
  | `<prefix>-<typ>-id` | `p: '$id'`, `posts_per_page: 1` | ein Beitrag per ID (z. B. aus einem Post-Feld) |
  | `<prefix>-<typ>-ids` | `post__in: '$ids'`, `orderby: 'post__in'`, `posts_per_page: -1` | mehrere Beiträge in der Reihenfolge der IDs |
  | `<prefix>-<typ>-kinder` | `post_parent: '$parent'`, `orderby: 'menu_order'`, `order: 'ASC'`, `posts_per_page: -1` | Unterseiten bzw. Kind-Beiträge |
  | `<prefix>-<typ>-verwandt` | `post__not_in: ['$post_id']`, `posts_per_page: '$limit ?? 3'`, `orderby: 'date'`, `order: 'DESC'` | neueste andere Beiträge desselben Typs |
  | `<prefix>-<typ>-verwandt-<tax>` | wie `-verwandt`, dazu `tax_query: [{ taxonomy: '<tax>', field: 'term_id', terms: '$terms' }]` | andere Beiträge mit gemeinsamem Begriff; die Term-IDs als Liste über `etch/dynamic_data/post` bereitstellen |

  Achtung: Ein leeres `post__in` ignoriert WordPress und liefert **alle** Beiträge. Loops mit `$id`/`$ids` deshalb immer in eine Bedingung auf das Feld setzen. Bei `-verwandt` immer `$post_id: 'this.id'` übergeben.
- **Vorheriger/nächster Beitrag** als dynamische Daten, nicht als Shortcode. Nur für den angezeigten Beitrag rechnen (der Filter läuft auch für jeden Loop-Eintrag), und den Beitrag aus `$post_id` nehmen, nicht den globalen:
  ```php
  add_filter( 'etch/dynamic_data/post', function ( $data, $post_id ) {
  	if ( ! is_singular() || (int) $post_id !== get_queried_object_id() ) {
  		return $data;
  	}
  	$nachbar = function ( $vorher ) use ( $post_id ) {
  		global $post;
  		$alt  = $post;
  		$post = get_post( $post_id ); // get_adjacent_post() arbeitet mit dem globalen Beitrag
  		$n    = get_adjacent_post( false, '', $vorher );
  		$post = $alt;
  		return $n ? array( 'titel' => get_the_title( $n ), 'link' => wp_make_link_relative( get_permalink( $n ) ) ) : null;
  	};
  	$data['<prefix>']['vorher']  = $nachbar( true );
  	$data['<prefix>']['nachher'] = $nachbar( false );
  	return $data;
  }, 10, 2 );
  ```
  In der Komponente je Richtung eine Bedingung auf `this.<prefix>.vorher` bzw. `….nachher`; Markup als `<nav aria-label="…">` mit dem Titel als Linktext. Sollen Bilder mit, auch deren KI-Daten mitgeben (siehe [KI-Kennzeichnung](#ki-kennzeichnung)).
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
- Eigene Tokens nur für das, was ACSS nicht kennt (z. B. Schatten) – vorher in den ACSS-Variablen nachsehen. Schriften kommen aus dem Font Manager (siehe [Schriften](#schriften)). Abstände, Schriftgrößen, Radius und Container kommen aus ACSS. Welche Werte gelten (Farben, Schriften, Schatten, ACSS-Einstellungen), legt das Design des Projekts fest.
- **EMMP anpassen** über seine Variablen (`--menu-item-clr`, `--menu-cta-bg`, `--dropdown-content-bg` …), gesetzt mit doppelter Klasse (`.dwc-top-level-items-vars.dwc-top-level-items-vars`), damit sie unabhängig von der Ladereihenfolge gewinnen. Werte nach dem Design des Projekts.
- EMMP markiert den aktiven Menüpunkt selbst mit `aria-current="page"`, aber nur bei exakt gleicher URL (Elternbereiche auf Unterseiten nicht).
- **Tabellarische Daten** in Komponenten als CSS-Grid aus `div`s mit Tabellen-Rollen (`role="table|row|columnheader|rowheader|cell"`, Name per `aria-label`). Breite Tabellen seitlich scrollbar, erste Spalte bei Bedarf fixiert.

## Schriften

**Standard für jede Website:** Schriften werden über den **Etch Font Manager** verwaltet. Welche Schriften ein Projekt nutzt, legt sein Design fest.

- **Einbinden nur über den Font Manager**: Google Fonts über dessen Google-Fonts-Suche installieren (die Dateien werden heruntergeladen und selbst gehostet), eigene Schriften als WOFF2 hochladen. Kein eigenes `@font-face` im Projekt-CSS, keine Schriftdateien im Projekt-Medienordner, keine externen Schriftdienste (Google Fonts, Adobe Fonts, Bunny Fonts) – Besucher laden nichts von fremden Servern (Datenschutz).
- Möglichst **variable** Schriften und nur die nötigen **Subsets** (für Deutsch reicht `latin`: Umlaute und ß sind enthalten); so bleibt es meist bei einer Datei je Familie.
- **Preload** für die Schrift des größten sichtbaren Elements beim ersten Anzeigen (meist der Hero-Titel); `font-display: swap`; **Fallback** auf Systemschriften (z. B. `ui-sans-serif, system-ui, "Segoe UI", Roboto, Arial, sans-serif`).
- **Zuordnung (Text, Überschriften) nur in den ACSS-Einstellungen** (`text-font-family`, `heading-font-family`, dazu Stärke und Laufweite), per Datei im Repo und MCP-Funktion übertragen. Im Font Manager **keine Rolle** (Heading/Body) setzen – sonst setzen Font Manager und ACSS dieselben Variablen und überschreiben sich je nach Ladereihenfolge.
- **Lizenz** jeder Schrift als Datei im Repo ablegen (z. B. `lizenzen/<schrift>-OFL.txt`); nur Schriften mit freier Lizenz für gewerbliche Nutzung (z. B. SIL Open Font License) oder gekaufte Lizenzen.
- **Einrichtung je Umgebung** (lokal, Staging, live) gleich: Familie installieren, Preload und Fallback setzen – in der Oberfläche oder per WP-CLI über die REST-Routen des Font Managers (`/etch-font-manager/v1/google/install`, `/etch-font-manager/v1/families`). Die Schritte stehen in der Projekt-Doku. Danach ACSS-Einstellungen übertragen und den Seitencache leeren.
- Schriftwechsel im Frontend prüfen: Stärken und Laufweiten der großen Titel an die neue Schrift anpassen, Kontraste unverändert.

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
- **Prüfseite für ACSS:** Der freie [ACSS Styleguide](https://etch.manuelwill.com/acss-styleguide/) (Manuel Will, für ACSS 4) zeigt alle ACSS-Werte der Installation mit ihren berechneten Werten auf einer Seite, mit Umschalter für Hell/Dunkel, wenn das Farbschema `light dark` ist. Gut zum Prüfen von Farben, Abstufungen, Kontrasten, Schrift- und Abstandswerten nach Änderungen an den ACSS-Einstellungen. Einrichtung je Projekt in der Entwicklung (lokal, ggf. Staging): neue Seite in Etch anlegen, das JSON von dort einfügen, speichern, im Frontend ansehen (am besten abgemeldet). Die Seite ist eine **Ausnahme vom Repo-Grundsatz**: fremder Inhalt, nur im Editor, nicht im Repo und nicht im Sync. Nie veröffentlichen – vor dem Livegang auf Entwurf setzen bzw. entfernen (siehe [Betrieb](betrieb.md#veröffentlichen-mit-duplicator-pro), Test- und Beispielseiten).

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
- **Mediathek (Backend):** Die Kennzeichnung ist schon dort sichtbar, in allen Projekten gleich und neutral (nur die Kennung kommt aus den Texten des Projekts):
  - Listenansicht: Spalte `ki` (Kennung, darunter Werkzeug; `manage_media_columns`/`manage_media_custom_column`) und Filter `ki_filter` über der Liste (alle, nur mit KI, ohne KI, je Art; `restrict_manage_posts` + `pre_get_posts` mit `meta_query`).
  - Raster und Medien-Fenster: `kiArt`/`kiLabel` per `wp_prepare_attachment_for_js` in die Bilddaten, ein kleines Skript in `print_media_templates` erweitert `wp.media.view.Attachment` (Plakette `.ki-admin-plakette--kachel` auf `.attachment-preview`, neu zeichnen bei `change:kiLabel`, damit sie nach dem Speichern der Felder sofort stimmt). Stile im selben Hook, damit sie auch im Etch-Builder greifen.
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

## Backend

- **Block „Individuelle Felder“ immer ausblenden** – in allen Beitragstypen, im Block-Editor und im klassischen Editor. Er zeigt die rohen Metadaten (auch die von Meta Box und internen Funktionen, z. B. Serialisiertes) und lässt sie ohne Prüfung ändern oder löschen. Eigene Felder kommen immer über Meta Box.
- Technik im Snippet `<prefix>-backend.php`: den Kasten `postcustom` mit `remove_meta_box()` im Hook `add_meta_boxes` (späte Priorität) für alle Beitragstypen entfernen und im Filter `block_editor_settings_all` den Schlüssel `enableCustomFields` entfernen – dann verschwindet auch der Schalter „Individuelle Felder“ in den Voreinstellungen des Block-Editors.
- **Nicht** `remove_post_type_support( …, 'custom-fields' )` verwenden: Ohne diese Unterstützung liefert die REST-API registrierte Metadaten (`register_post_meta` mit `show_in_rest`) nicht mehr aus.
- **Seitenleiste bei Etch-Komponenten öffnen.** Etch-Komponenten lassen sich im Block-Editor nicht direkt im Inhalt bearbeiten, nur über die Seitenleiste. Ist sie zu, finden Redakteure die Felder nicht. Im selben Snippet `<prefix>-backend.php` öffnet ein kleines Skript die Block-Seitenleiste, sobald ein Block `etch/component` ausgewählt wird:
  ```php
  add_action( 'enqueue_block_editor_assets', function () {
  	wp_add_inline_script( 'wp-edit-post', "( function () {
  		var zuletzt = null;
  		wp.data.subscribe( function () {
  			var block = wp.data.select( 'core/block-editor' ).getSelectedBlock();
  			var id = block ? block.clientId : null;
  			if ( id === zuletzt ) { return; }
  			zuletzt = id;
  			var editor = wp.data.dispatch( 'core/edit-post' );
  			if ( block && 'etch/component' === block.name && editor && editor.openGeneralSidebar ) {
  				editor.openGeneralSidebar( 'edit-post/block' );
  			}
  		} );
  	} )();" );
  } );
  ```
  `wp-edit-post` gibt es nur im Beitrags-Editor, der Website-Editor bleibt unberührt.
- **Etch-Felder im Block-Editor aufräumen** (ebenfalls in `<prefix>-backend.php`): unter den Eigenschaften der Komponenten den technischen Schlüssel ausblenden, bei Gruppen den zusätzlichen Innenabstand entfernen, bei Bild-Eigenschaften das ID-Feld ausblenden (die Schaltfläche für die Mediathek bleibt), und die Etch-Blöcke in der Block-Auswahl ausblenden. Die Blöcke nicht abmelden, sonst lassen sich vorhandene Etch-Inhalte nicht mehr duplizieren. Hilfetexte nur in Etch-Feldern ausblenden, die von Meta Box und WordPress bleiben:
  ```php
  add_action( 'enqueue_block_editor_assets', function () {
  	wp_register_style( '<prefix>-block-editor', false );
  	wp_enqueue_style( '<prefix>-block-editor' );
  	wp_add_inline_style( '<prefix>-block-editor', '
  		[data-etch-property-type] .components-base-control__help { display: none; }
  		[data-etch-property-type="object:group"] .components-panel__body { padding-inline: 0; }
  		[data-etch-property-type="string:wpMediaId"] .components-text-control__input,
  		[data-etch-property-type="string:image"] .components-text-control__input { display: none; }
  		.block-editor-inserter__panel-header:has(+ .block-editor-inserter__panel-content [class*="editor-block-list-item-etch-"]),
  		.block-editor-inserter__panel-header:has(+ .block-editor-inserter__panel-content [class*="editor-block-list-item-etch-"]) + .block-editor-inserter__panel-content { display: none !important; }
  	' );
  } );
  ```
  Die Selektoren hängen an Etch-Interna (`data-etch-property-type`). Nach jedem Etch-Update im Block-Editor prüfen.
- **Technik-Doku im Backend für Administratoren** – auf jeder Website. Sie erklärt, wie die Website aufgebaut ist und wie Plugins, Snippets, CSS und Skripte zusammenarbeiten, und schützt so vor Änderungen an der falschen Stelle.
  - Quelle `docs/technik.md` im Projekt; der Build erzeugt daraus `<prefix>/technik.php` (HTML, mit Schutzzeile `<?php defined( 'ABSPATH' ) || exit; ?>`, damit die Datei direkt aufgerufen nichts ausliefert). Ein kleiner Markdown-Umwandler im Generator genügt: Überschriften, Absätze, Listen, Tabellen, Hinweise, **fett**, `Code`, Links – keine Code-Blöcke.
  - Snippet `<prefix>-technik.php`: Menüpunkt „Technik“ (bzw. Unterpunkt eines vorhandenen Handbuch-Menüs) und Hinweis im Dashboard, nur mit `manage_options`; Ausgabe über `wp_kses_post()`, Gestaltung mit den Farben des WordPress-Backends.
  - Kapitel: Überblick (Schichten), Woher kommt was (Repository oder Backend), Plugins, Snippets, externe Daten und Zeitpläne (falls vorhanden), CSS (Ladereihenfolge, Stylesheets), JavaScript, Datenfluss an Beispielen, was man nicht tun sollte, was nach Updates zu prüfen ist.
  - Bei jeder Änderung an Plugins, Snippets, CSS oder Skripten mitpflegen.

## Etch-Editor (Canvas)

Der Etch-Editor zeigt die Seite in einem eigenen Rahmen (Canvas) und lädt dort nicht alles, was das Frontend lädt. Fehlen Stile oder Skripte von Plugins oder aus `wp-content/<prefix>/`, sieht die Seite im Editor anders aus als im Frontend. Zwei Hooks laden sie **nur im Canvas** nach; für Frontend und Block-Editor weiter wie üblich `wp_enqueue_style()`/`wp_enqueue_script()`:

- `etch/canvas/enqueue_assets` (Action): darin normal `wp_enqueue_style()`/`wp_enqueue_script()` aufrufen; Etch übernimmt, was dort in die Warteschlange kommt.
- `etch/canvas/additional_stylesheets` (Filter): Liste von `array( 'id' => '…', 'url' => '…' )` ergänzen.

```php
add_action( 'etch/canvas/enqueue_assets', function () {
	wp_enqueue_script( '<prefix>-canvas', content_url( '<prefix>/<datei>.js' ), array(), <PREFIX>_VERSION, true );
} );
```

**Abgelaufene Anmeldung im Etch-Editor:** Läuft die Anmeldung ab (oder wird die Nonce ungültig, etwa nach einer neuen Anmeldung in einem anderen Tab), antwortet die REST-API beim Speichern mit `403 rest_cookie_invalid_nonce`, und Etch speichert nicht. Ein kleines Skript im Snippet `<prefix>-backend.php` zeigt dann das Anmeldefenster von WordPress (`interim-login`) über dem Editor, holt danach eine neue Nonce und setzt sie in `window.etchGlobal.nonce`, von wo Etch sie liest. Die Arbeit bleibt im Editor; danach noch einmal speichern. Zusätzlich übernimmt es die Nonce, die WordPress bei jeder erfolgreichen REST-Antwort im Header `X-WP-Nonce` mitschickt. Greift nur im Builder (`?etch=magic`) für Administratoren, deshalb in allen Umgebungen unschädlich:

```php
add_action( 'wp_enqueue_scripts', function () {
	if ( ! isset( $_GET['etch'] ) || 'magic' !== $_GET['etch'] || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$daten = array(
		'login' => add_query_arg( 'interim-login', '1', wp_login_url() ),
		'nonce' => add_query_arg( 'action', '<prefix>_rest_nonce', admin_url( 'admin-ajax.php' ) ),
	);
	wp_register_script( '<prefix>-etch-sitzung', false, array(), null, false );
	wp_enqueue_script( '<prefix>-etch-sitzung' );
	wp_add_inline_script( '<prefix>-etch-sitzung', 'window.<prefix>EtchSitzung = ' . wp_json_encode( $daten ) . ";\n" . <<<'JS'
( function () {
	var c = window.<prefix>EtchSitzung, offen = null, original = window.fetch;
	function nonceSetzen( n ) {
		if ( ! n ) { return; }
		if ( window.etchGlobal ) { window.etchGlobal.nonce = n; }
		if ( window.wpApiSettings ) { window.wpApiSettings.nonce = n; }
	}
	function anmelden() {
		if ( offen ) { return; }
		offen = document.createElement( 'dialog' );
		offen.setAttribute( 'aria-label', 'Anmeldung abgelaufen' );
		offen.style.cssText = 'padding:0;border:0;inline-size:min(420px,95vw);block-size:min(600px,90vh)';
		var rahmen = document.createElement( 'iframe' );
		rahmen.title = 'Anmelden';
		rahmen.src = c.login;
		rahmen.style.cssText = 'inline-size:100%;block-size:100%;border:0';
		rahmen.addEventListener( 'load', function () {
			try {
				if ( ! rahmen.contentDocument.body.classList.contains( 'interim-login-success' ) ) { return; }
			} catch ( e ) { return; }
			original( c.nonce, { credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( j ) { nonceSetzen( j.nonce ); offen.close(); offen.remove(); offen = null; } );
		} );
		offen.appendChild( rahmen );
		document.body.appendChild( offen );
		offen.showModal();
	}
	window.fetch = function () {
		return original.apply( this, arguments ).then( function ( r ) {
			nonceSetzen( r.headers.get( 'X-WP-Nonce' ) );
			if ( 403 !== r.status ) { return r; }
			return r.clone().text().then( function ( t ) {
				if ( -1 !== t.indexOf( 'rest_cookie_invalid_nonce' ) ) { anmelden(); }
				return r;
			} );
		} );
	};
} )();
JS
	);
} );
add_action( 'wp_ajax_<prefix>_rest_nonce', function () {
	wp_send_json( array( 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
} );
```

Die Texte im Dialog (`aria-label`, `title`) legt das Projekt fest. Hängt an Etch-Interna (`?etch=magic`, `window.etchGlobal.nonce`) – nach einem Etch-Update einmal prüfen: im Builder in einem zweiten Tab abmelden, dann speichern.

Das globale Stylesheet des Projekts kommt per Sync in Etch und braucht keinen dieser Hooks. Pfade über `content_url()` bzw. die Projekt-Konstante, nie über `get_stylesheet_directory_uri()` oder `__DIR__` (siehe [Betrieb](betrieb.md#php-als-wpcodebox-snippets)).

## Barrierefreiheit

- Ziel ist WCAG 2.1 AA. Farbkontraste regelt das Projekt, geprüft in beiden Farbschemata (siehe [Hell/Dunkel](#helldunkel)).
- **Link „Zum Inhalt springen“ (Skip-Link) auf jeder Seite** (WCAG 2.4.1):
  - erstes fokussierbares Element der Seite, vor der Navigation; Text legt das Projekt fest
  - Ziel ist das Hauptelement `<main id="main">` – genau ein `<main>` je Seite, im Seitenrahmen der Templates
  - **mit EMMP** dessen eingebauten Skip-Link nutzen (Header-Komponente, Gruppe `accessibilty`: `skipLink: 'true'`, `customSkipLinkParameter: 'main | <Text>'`, Format `Ziel | Text`); **ohne EMMP** eigener Link `<a class="skip-link" href="#main">…</a>` am Anfang des Headers
  - visuell verborgen, aber **sichtbar, sobald er den Fokus hat** (nicht mit `display: none` oder `visibility: hidden`, sonst ist er per Tastatur nicht erreichbar), mit sichtbarem Fokusrahmen. **ACSS 4 bringt die Klasse `.skip-link` mit** (Button-Stil, per `transform` verborgen, sichtbar bei `:focus-visible`) – nur die Klasse setzen, kein eigenes CSS dafür
  - es gibt nur einen Skip-Link: nicht zusätzlich zu dem von EMMP oder einem Plugin
  - Test: Seite laden, einmal Tab → Link erscheint; Enter → der nächste Tab landet im Inhalt, nicht in der Navigation
- Ausgeblendete Elemente (z. B. inaktive Slides, geschlossene Menüs) dürfen per Tab nicht erreichbar sein.
- Der zugängliche Name von Schaltflächen entspricht dem sichtbaren Text (WCAG 2.5.3); kein abweichendes `aria-label`.
- Tabellarische Daten mit Tabellen-Rollen (siehe [CSS](#css)), Screenreader-Ansagen bei dynamischen Wechseln (z. B. „Element 3 von 18“).
