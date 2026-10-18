# etch-nodes – gemeinsame Standards für Etch-/WordPress-Projekte

Diese Datei wird aus einem Projekt importiert (`@etch-nodes/CLAUDE.md`). Alle Verweise sind relativ zu diesem Ordner.

**Die Standards in etch-nodes sind führend** für alle Projekte: Stack, Konventionen, Technik, Barrierefreiheit und Betrieb gelten so, wie sie hier stehen. Ein Projekt weicht nur bei Projektspezifischem ab (Namen, Domains, Prefix, Plugin-Versionen, Komponenten-IDs, Inhalte) – das steht in der CLAUDE.md des Projekts und hat dort Vorrang. Widerspricht eine Projekt-Doku einem Standard, gilt der Standard; die Projekt-Doku wird angepasst. **Design** (Farben und Farbregeln, Farben im hellen und dunklen Schema, Kontrastwerte, Schriften, Schatten, Rundungen, Abstands- und Größenwerte, ACSS-Einstellungen, Logo, Bildsprache, Gestaltung der Komponenten) steht nie hier, sondern immer lokal im Projekt – jede Website hat ihr eigenes Design. `<prefix>` bzw. `<PREFIX>` in diesen Dokumenten steht für das Kürzel des Projekts.

## Stack und Regeln

@docs/development-environment.md
@docs/konventionen.md
@docs/betrieb.md

## Arbeitsweise

- Antworte auf Deutsch.
- Keine Shortcodes, wenn es als Etch-Komponente geht: PHP liefert nur Daten, Markup baut die Komponente.
- Das Repo ist die Quelle: Generator → Build → Sync. Inhalte nie direkt als MCP-Parameter übergeben.
- Primär mit den Klassen, Variablen und Einstellungen von Automatic.css arbeiten; eigene Klassen und Variablen nur, wenn ACSS es nicht abdeckt (siehe [docs/konventionen.md](docs/konventionen.md#css)).
- Für interaktive Bausteine (Accordion, Tabs, Dialog, Drawer, Lightbox, Breadcrumbs, Inhaltsverzeichnis, Facets) OhMyEtch-Komponenten nutzen, nicht selbst bauen – **außer Navigation/Menüs** (keine OhMyEtch-Navigation). **Slider und Karussells immer mit Slider Pro for Etch** (`dwc-slider-pro-etch`), nicht mit dem OhMyEtch-Carousel. Siehe [docs/konventionen.md](docs/konventionen.md#komponenten).
- Fremde Sammlungen von Etch-Komponenten (z. B. UP Studio) sind Quellen, kein Standard: bewertet und mit Prüfablauf in [docs/quellen.md](docs/quellen.md). Bei jeder Update-Runde, mindestens einmal im Monat, dort auf Neues prüfen und melden; nichts ohne Auftrag übernehmen, nie direkt in eine Kundenseite einfügen.
- Formulare immer mit **WS Form** (`ws-form`, vorerst freie Version), kein anderes Formular-Plugin und kein eigener Versand. Siehe [docs/konventionen.md](docs/konventionen.md#formulare).
- Ein neu aufgenommenes Plugin immer auch in die Plugin-Liste eintragen (Stack-Tabelle, `blueprint/plugins.conf` des Server-Werkzeugs samt ZIP, zentrale Übersicht). Siehe [docs/development-environment.md](docs/development-environment.md#plugin-liste).
- Text auf Seiten, die im Etch-Editor gepflegt werden, lässt sich dort mit dem Plugin Etch Text Editor bearbeiten; Farbe nur über ACSS-Variablen („var“), und Schließen speichert die ganze Seite. Nicht für Seiten aus dem Repo. Siehe [docs/konventionen.md](docs/konventionen.md#etch-editor-canvas).
- Gestaltung (Farben, Schriften, Look) nach der Design-Doku des Projekts.
- Schriften immer über den **Etch Font Manager** einbinden (selbst gehostet, auch Google Fonts), nie per eigenem `@font-face` oder externem Schriftdienst; Zuordnung zu Text/Überschriften nur in den ACSS-Einstellungen. Siehe [docs/konventionen.md](docs/konventionen.md#schriften).
- Jede Website unterstützt Hell und Dunkel (ACSS `light dark`, folgt dem Gerät); ein Umschalter ist optional – gibt es ihn, lässt er sich im Backend ein- und ausschalten. Siehe [docs/konventionen.md](docs/konventionen.md#helldunkel).
- Jede Website kennzeichnet KI-erzeugte oder -veränderte Bilder und Videos (Mediathek-Feld `ki_art`, Symbol am Bild, Hinweis im Alternativtext). Symbol, Farben und Texte legt das Projekt fest. Siehe [docs/konventionen.md](docs/konventionen.md#ki-kennzeichnung).
- Jede Seite hat einen Skip-Link „Zum Inhalt springen“ auf `<main id="main">` (mit EMMP dessen eingebauten). Siehe [docs/konventionen.md](docs/konventionen.md#barrierefreiheit).
- Jede Website hat im Backend eine Technik-Doku für Administratoren (Quelle `docs/technik.md`, Snippet `<prefix>-technik.php`); bei Änderungen an Plugins, Snippets, CSS oder Skripten mitpflegen. Siehe [docs/konventionen.md](docs/konventionen.md#backend).
- Auf jeder Website läuft das eigene Plugin Bausteine Backend (`bausteine-backend`): Es blendet den Block „Individuelle Felder“ aus, öffnet bei Etch-Komponenten die Seitenleiste, räumt deren Felder auf und fängt eine abgelaufene Anmeldung im Etch-Editor ab. Kein Snippet `<prefix>-backend.php` mehr. Siehe [docs/konventionen.md](docs/konventionen.md#backend).
- PHP-Snippets WPCodeBox-tauglich schreiben (`define()` statt `const`, kein `__DIR__`). Snippets immer in WPCodeBox anlegen, nie als mu-plugin oder Lader (einzige Ausnahme: das Umgebungs-mu-plugin des Server-Werkzeugs, siehe [docs/betrieb.md](docs/betrieb.md#php-als-wpcodebox-snippets)).
- **Bei einem neuen Projekt** (bzw. in der ersten Sitzung) zuerst die WPCodeBox-Einstellungen prüfen und Abweichungen melden: MCP an, genau die vorgesehenen Werkzeuge frei, Delete/Run Snippet nicht frei, kein Lader. Prüfliste: [docs/betrieb.md](docs/betrieb.md#wpcodebox-einstellungen-prüfen-bei-jedem-neuen-projekt). Freigaben setzt der Nutzer selbst in der Oberfläche.
- Dieser Ordner ist ein git subtree (siehe [README.md](README.md)). Änderungen hier nur, wenn sie für alle Projekte gelten, und generisch formuliert – ohne Projektnamen, URLs, Prefixe oder Pfade eines Projekts.
