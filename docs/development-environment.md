# Entwicklungsumgebung – projektspezifisch

Stack, allgemeine Konventionen und Betrieb stehen in [etch-nodes](../etch-nodes/CLAUDE.md) (git subtree). Hier steht nur, was für dieses Projekt gilt; Farben in [farben.md](farben.md). Projekt-Prefix (`<prefix>` in etch-nodes): `golfplatz`.

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
| **Etch Font Manager** | `etch-font-manager` | 1.0.16 | aktiv | Hostet die Schriften selbst (`wp-content/fonts/`): **Fraunces** (Überschriften, vorgeladen) und **Lora** (Fließtext), Google Fonts, Subset latin, variabel, normal und kursiv. Keine Rolle gesetzt – Zuordnung in `acss-schrift.mjs` |
| **Duplicator Pro** | `duplicator-pro` | 5.0.4 | aktiv | |
| **MCP Adapter** | `mcp-adapter` | 0.6.1 | aktiv | |
| **Uplink Editorial Title** | `uplink-editorial-title` | 1.1.2 | aktiv | _Zweck noch zu dokumentieren._ |
| **Uplink Unified Ops Center** | `uplink-unified-ops-center` | 1.5.0 | aktiv | _Zweck noch zu dokumentieren._ |

## Projektspezifische Konventionen

- **Dynamische Daten:** global `{options.golfplatz.<bereich>.…}`, je Beitrag `{this.golfplatz.…}` bzw. `{item.golfplatz.…}`. Generator der Komponenten: `wordpress/etch/*.mjs`.
- **WPCodeBox:** Datenordner `wp-content/golfplatz` über die Konstante `GOLFPLATZ_DATEN` (statt `__DIR__`). Snippet-Sync per MCP `golfplatz/snippets-sync`.
- **Tabellarische Daten** als CSS-Grid mit Tabellen-Rollen, wie in Preistabelle, Scorekarte und Rating.
- **OhMyEtch:** Häufige Fragen, Saisons (Mannschaft) und frühere Runden (Lochwettspiel) als Accordion, Brotkrumen als Breadcrumbs – eingebunden per Key (`ome()` in `lib.mjs`, Sync löst `__REF_Ome…__` auf). Brotkrumen: Standard automatisch; Kurs, Spielbahn, Lochwettspiel mit Zwischenstufe über „Manual Links“ (`krumenArt: 'zwischen'`); Beiträge und Spielberichte mit eigener Liste (`krumenArt: 'eigen'`), weil OhMyEtch bei Beiträgen „Blog“ einsetzt bzw. die Mannschaft dynamisch ist. Eigenschaften `styling.*` und `content.label` kommen bei Etch nicht an: Klassen über eine Hülle um das Accordion und Spans im Trigger. Der Prototyp behält `<details>` und die eigene Brotkrumen-Liste.
- **Buttons:** nur ACSS-Klassen (`btn--primary`, `btn--secondary`, `btn--primary btn--outline`, `btn--primary-light` und `btn--primary-light btn--outline` auf grünen Flächen, Größe `btn--s`). Aussehen (Schriftstärke, Laufweite, Innenabstand) in `wordpress/etch/acss-buttons.mjs`, übertragen mit `golfplatz/acss-colors` (`aus_datei: true`). Der Prototyp bildet die ACSS-Buttons in `prototype/assets/css/acss-buttons.css` nach (generiert).
- **Schriften** (Standard aus etch-nodes, [konventionen.md › Schriften](../etch-nodes/docs/konventionen.md#schriften)): Überschriften **Fraunces** (Stärke 500), Fließtext **Lora**, beide aus Google Fonts über den Etch Font Manager selbst gehostet; Fallback Georgia/Times New Roman. Zuordnung und Stärke in `wordpress/etch/acss-schrift.mjs`, übertragen mit `golfplatz/acss-colors` (`aus_datei: true`). Lizenzen: `wordpress/lizenzen/`. Einrichten je Umgebung: im Font Manager Google Fonts › Fraunces und Lora (Subset latin, variabel), Preload nur für Fraunces – oder per WP-CLI `wp eval-file` mit den REST-Routen `/etch-font-manager/v1/google/install` und `/families`. Abgeleitete Clubs können eigene Schriften wählen (Design des Clubs).
- **Farben:** siehe [farben.md](farben.md).
