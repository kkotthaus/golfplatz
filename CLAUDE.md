# etch-nodes – gemeinsame Standards für Etch-/WordPress-Projekte

Diese Datei wird aus einem Projekt importiert (`@etch-nodes/CLAUDE.md`). Alle Verweise sind relativ zu diesem Ordner.

Projektspezifisches (Namen, Domains, Prefix, Plugin-Versionen, Komponenten-IDs) steht in der CLAUDE.md des Projekts und hat dort Vorrang. **Design** (Farben und Farbregeln, Farben im hellen und dunklen Schema, Kontrastwerte, Schriften, Schatten, Rundungen, Abstands- und Größenwerte, ACSS-Einstellungen, Logo, Bildsprache, Gestaltung der Komponenten) steht nie hier, sondern immer lokal im Projekt – jede Website hat ihr eigenes Design. `<prefix>` bzw. `<PREFIX>` in diesen Dokumenten steht für das Kürzel des Projekts.

## Stack und Regeln

@docs/development-environment.md
@docs/konventionen.md
@docs/betrieb.md

## Arbeitsweise

- Antworte auf Deutsch.
- Keine Shortcodes, wenn es als Etch-Komponente geht: PHP liefert nur Daten, Markup baut die Komponente.
- Das Repo ist die Quelle: Generator → Build → Sync. Inhalte nie direkt als MCP-Parameter übergeben.
- Primär mit den Klassen, Variablen und Einstellungen von Automatic.css arbeiten; eigene Klassen und Variablen nur, wenn ACSS es nicht abdeckt (siehe [docs/konventionen.md](docs/konventionen.md#css)).
- Für interaktive Bausteine (Accordion, Tabs, Dialog, Drawer, Lightbox, Carousel, Breadcrumbs, Inhaltsverzeichnis, Facets) OhMyEtch-Komponenten nutzen, nicht selbst bauen – **außer Navigation/Menüs** (keine OhMyEtch-Navigation). Siehe [docs/konventionen.md](docs/konventionen.md#komponenten).
- Gestaltung (Farben, Schriften, Look) nach der Design-Doku des Projekts.
- Jede Website unterstützt Hell und Dunkel (ACSS `light dark`, folgt dem Gerät); ein Umschalter ist optional. Siehe [docs/konventionen.md](docs/konventionen.md#helldunkel).
- PHP-Snippets WPCodeBox-tauglich schreiben (`define()` statt `const`, kein `__DIR__`).
- Dieser Ordner ist ein git subtree (siehe [README.md](README.md)). Änderungen hier nur, wenn sie für alle Projekte gelten, und generisch formuliert – ohne Projektnamen, URLs, Prefixe oder Pfade eines Projekts.
