# Entwicklungsumgebung – projektspezifisch

Stack, allgemeine Konventionen, Farbregeln und Betrieb stehen in [etch-nodes](../etch-nodes/CLAUDE.md) (git subtree). Hier steht nur, was für dieses Projekt gilt. Projekt-Prefix (`<prefix>` in etch-nodes): `golfplatz`.

## etch-nodes aktualisieren

`etch-nodes/` ist als git subtree (mit `--squash`) eingebunden. Neuen Stand holen, im Projekt-Root bei sauberem Arbeitsverzeichnis:

```bash
git fetch etch-nodes
git subtree pull --prefix=etch-nodes etch-nodes main --squash
```

Das Remote `etch-nodes` existiert nur im lokalen Klon. In einem frischen Klon zuerst anlegen:

```bash
git remote add etch-nodes https://github.com/kkotthaus/etch-nodes.git
```

Änderungen an `etch-nodes/` möglichst direkt im Repo etch-nodes machen und dann hier pullen. Zurückgeben: `git subtree push --prefix=etch-nodes etch-nodes <branch>`, danach Pull Request in etch-nodes. Projekte, die von diesem Blueprint abgeleitet sind, auf denselben Stand von etch-nodes pullen, damit Blueprint-Updates ohne Konflikt durchgehen.

## Plugin-Versionen

Stand 2026-09-24 aus `wp plugin list --status=active`. Automatische Updates sind aus.

| Baustein | Plugin-Slug | Version | Status | Anmerkung |
| --- | --- | --- | --- | --- |
| **Etch** | `etch` | 1.6.7 | aktiv | |
| **Automatic.css** | `automatic-css` | 4.0.1 | aktiv | |
| **OhMyEtch** | `oh-my-etch` | 1.6.0 | aktiv | |
| **Media Bridge for Etch** | `media-bridge-for-etch` | 2.2.3 | aktiv | Medien-Erweiterung für Etch. _Genauer Einsatzzweck im Projekt noch zu dokumentieren._ |
| **EtchSliderPro** | `dwc-slider-pro-etch` + Etch-Komponenten (IDs 240–246) | 1.2.3 | aktiv | |
| **EtchMegaMenuPro** | – (Etch-Komponenten) | – | in Etch hinterlegt | |
| **Meta Box AIO** | `meta-box-aio` | 3.12.0 | aktiv | |
| **User Role Editor Pro** | `user-role-editor-pro` | 4.66.2 | aktiv | |
| **WPCodeBox 2** | `wpcodebox2` | 1.4.1 | aktiv | Ordner „Golfplatz“, je Datei aus `wordpress/snippets/` ein Snippet |
| **Duplicator Pro** | `duplicator-pro` | 5.0.4 | aktiv | |
| **MCP Adapter** | `mcp-adapter` | 0.6.1 | aktiv | |
| **Uplink Editorial Title** | `uplink-editorial-title` | 1.1.2 | aktiv | _Zweck noch zu dokumentieren._ |
| **Uplink Unified Ops Center** | `uplink-unified-ops-center` | 1.5.0 | aktiv | _Zweck noch zu dokumentieren._ |

## Projektspezifische Konventionen

- **Dynamische Daten:** global `{options.golfplatz.<bereich>.…}`, je Beitrag `{this.golfplatz.…}` bzw. `{item.golfplatz.…}`. Generator der Komponenten: `wordpress/etch/*.mjs`.
- **WPCodeBox:** Datenordner `wp-content/golfplatz` über die Konstante `GOLFPLATZ_DATEN` (statt `__DIR__`). Snippet-Sync per MCP `golfplatz/snippets-sync`.
- **Tabellarische Daten** als CSS-Grid mit Tabellen-Rollen, wie in Preistabelle, Scorekarte und Rating.
- **Farben:** Clubfarben zentral in `wordpress/etch/acss-farben.mjs`, übernehmen per MCP `golfplatz/acss-colors`.
  - Blueprint: Primary `#2E6B4E` (Golf-Grün), Secondary `#2C5A85` (Blau).
  - Zuordnung im Design: `--secondary-ultra-light` Seitenhintergrund, `--base-ultra-light` Flächen, `--base-light` Rahmen, `--base-semi-light` gedämpfter Text und Rahmen von Eingabefeldern, `--base-semi-dark` Nebentext, `--info` Wintergrüns.
  - `--primary` nur als Fläche mit weißer Schrift (`--white`) oder für große Schrift/Grafik; grüne Schrift und Links immer `--primary-dark`. `--secondary` nie als Schrift auf Grün oder dunklen Flächen – dort `--white` bzw. `--secondary-light`.
  - Ausnahmen von „nur ACSS-Farben“: Abschlagfarben (`--tee-*`) und Illustrationen (Bahngrafik, Platzhalter-Verläufe).
  - Immer hell gerechnete Bereiche in `immerHell` in `wordpress/etch/acss-farben.mjs` eintragen.
  - Kontrastprüfung: `node wordpress/etch/kontrast.mjs` – muss ohne Fehler durchlaufen.
