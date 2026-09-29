# Projekt Golfplatz

Neutraler Blueprint für Golfclub-Websites auf Basis von WordPress + Etch. Alle Inhalte sind Platzhalter („Golfclub Musterclub“); Clubdaten stehen zentral in `prototype/src/data.mjs` (Repo) bzw. auf der Einstellungsseite „Clubdaten“ (WordPress), Farben in `wordpress/etch/acss-farben.mjs`. Einrichtung für einen Club: [docs/neuer-club.md](docs/neuer-club.md). Keine Clubdaten ins Markup schreiben.

Stack und Konventionen: @docs/development-environment.md

Seiten, Templates, Inhalte und Verknüpfungen: [docs/seitenstruktur.md](docs/seitenstruktur.md). Konzept Birdiebook (Platz & Bahnen am Handy): [docs/konzept-birdiebook.md](docs/konzept-birdiebook.md).

Stand der Umsetzung (angelegte Beitragstypen/Felder, offene Handgriffe): [docs/umsetzung.md](docs/umsetzung.md). Klickbarer Prototyp aller Seiten: [prototype/README.md](prototype/README.md).

## Arbeitsweise
- Antworte auf Deutsch.
- Halte dich an die Konventionen aus der Stack-Doku (u. a. BEM).
- Keine Shortcodes, wenn es als Etch-Komponente geht: PHP liefert nur Daten (Etch-Filter `etch/dynamic_data/option` bzw. `etch/dynamic_data/post`), Markup baut die Etch-Komponente.
- Neue oder geänderte Funktionen im Handbuch für die Redaktion nachtragen: [docs/handbuch.md](docs/handbuch.md) (erscheint im Backend unter „Handbuch“).
- Farben immer aus dem ACSS-Farbsystem (`--primary*`, `--secondary*` …), nie eigene Farbvariablen oder Hex-Werte im CSS.