# Projekt Golfplatz

Neutraler Blueprint für Golfclub-Websites auf Basis von WordPress + Etch. Alle Inhalte sind Platzhalter („Golfclub Musterclub“); Clubdaten stehen zentral in `prototype/src/data.mjs` (Repo) bzw. auf der Einstellungsseite „Clubdaten“ (WordPress), Farben in `wordpress/etch/acss-farben.mjs`. Einrichtung für einen Club: [docs/neuer-club.md](docs/neuer-club.md). Keine Clubdaten ins Markup schreiben.

Gemeinsame Standards (Stack, Konventionen, Betrieb): @etch-nodes/CLAUDE.md

Projektspezifisch (Plugin-Versionen, Prefix, Komponenten-IDs): @docs/development-environment.md

Farben und Farbregeln (nur lokal, nicht in etch-nodes): @docs/farben.md

Seiten, Templates, Inhalte und Verknüpfungen: [docs/seitenstruktur.md](docs/seitenstruktur.md). Konzept Birdiebook (Platz & Bahnen am Handy): [docs/konzept-birdiebook.md](docs/konzept-birdiebook.md).

Stand der Umsetzung (angelegte Beitragstypen/Felder, offene Handgriffe): [docs/umsetzung.md](docs/umsetzung.md). Klickbarer Prototyp aller Seiten: [prototype/README.md](prototype/README.md).

## Arbeitsweise
- Neue oder geänderte Funktionen im Handbuch für die Redaktion nachtragen: [docs/handbuch.md](docs/handbuch.md) (erscheint im Backend unter „Handbuch“).
- `etch-nodes/` ist ein git subtree. Dort nur allgemeine, generische Standards ändern (siehe [etch-nodes/README.md](etch-nodes/README.md)); Projektspezifisches gehört in `docs/`.
