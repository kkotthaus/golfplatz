# etch-nodes – gemeinsame Standards für Etch-/WordPress-Projekte

Diese Datei wird aus einem Projekt importiert (`@etch-nodes/CLAUDE.md`). Alle Verweise sind relativ zu diesem Ordner.

Projektspezifisches (Namen, Domains, Prefix, Farben, Plugin-Versionen, Komponenten-IDs) steht in der CLAUDE.md des Projekts und hat dort Vorrang. `<prefix>` bzw. `<PREFIX>` in diesen Dokumenten steht für das Kürzel des Projekts.

## Stack und Regeln

@docs/development-environment.md
@docs/konventionen.md
@docs/farben-barrierefreiheit.md
@docs/betrieb.md

## Arbeitsweise

- Antworte auf Deutsch.
- Keine Shortcodes, wenn es als Etch-Komponente geht: PHP liefert nur Daten, Markup baut die Komponente.
- Das Repo ist die Quelle: Generator → Build → Sync. Inhalte nie direkt als MCP-Parameter übergeben.
- Farben nur aus dem ACSS-Farbsystem; nach Farbänderungen den Kontrast in Hell und Dunkel prüfen.
- PHP-Snippets WPCodeBox-tauglich schreiben (`define()` statt `const`, kein `__DIR__`).
- Dieser Ordner ist ein git subtree (siehe [README.md](README.md)). Änderungen hier nur, wenn sie für alle Projekte gelten, und generisch formuliert – ohne Projektnamen, URLs, Prefixe oder Pfade eines Projekts.
