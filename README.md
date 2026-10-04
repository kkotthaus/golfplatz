# etch-nodes

Gemeinsame Standards für WordPress-Projekte mit Etch, Automatic.css v4, Meta Box und WPCodeBox: Stack, Konventionen und Betrieb. Die Inhalte sind generisch und enthalten keine Projektdaten und kein Design. Das Design (Farben, Schriften, Look) legt jede Website lokal ab.

| Datei | Inhalt |
| --- | --- |
| [CLAUDE.md](CLAUDE.md) | Einstieg für Claude Code, importiert die Doku |
| [docs/development-environment.md](docs/development-environment.md) | Stack und Plugins |
| [docs/konventionen.md](docs/konventionen.md) | Komponenten, Block-Markup, dynamische Daten, CSS, Meta Box |
| [docs/betrieb.md](docs/betrieb.md) | WPCodeBox-Snippets, MCP-Funktionen, Build/Sync, Duplicator, Caching |

## In ein Projekt einbinden (git subtree)

Das Repo liegt im Projekt unter `<projekt>/etch-nodes/`. Einmalig im Projekt-Root:

```bash
git remote add etch-nodes https://github.com/kkotthaus/etch-nodes.git
git subtree add --prefix=etch-nodes etch-nodes main --squash
```

`--squash` legt die Historie von etch-nodes als einen Commit ab. Bei allen späteren `pull`/`push` ebenfalls `--squash` verwenden bzw. konsequent weglassen – nicht mischen.

Danach in der `CLAUDE.md` des Projekts importieren:

```markdown
@etch-nodes/CLAUDE.md
```

Die Imports in `etch-nodes/CLAUDE.md` sind relativ zum Ordner `etch-nodes/` und funktionieren deshalb ohne Anpassung. Projektspezifisches (Prefix, Versionen, Komponenten-IDs) und das gesamte Design gehören in die Projekt-Doku, nicht hierher.

## Aktualisieren

Neuen Stand von etch-nodes ins Projekt holen (Arbeitsverzeichnis sauber):

```bash
git fetch etch-nodes
git subtree pull --prefix=etch-nodes etch-nodes main --squash
```

Wurde das Remote in einem frischen Klon des Projekts noch nicht angelegt, zuerst `git remote add` wie oben.

## Änderungen zurückgeben

Am besten direkt in diesem Repo ändern und anschließend in den Projekten `subtree pull` ausführen. Wurde doch im Projekt unter `etch-nodes/` geändert:

```bash
git subtree push --prefix=etch-nodes etch-nodes <branch>
```

Dann in etch-nodes einen Pull Request von `<branch>` nach `main` stellen. Commits im Projekt, die `etch-nodes/` betreffen, möglichst getrennt von anderen Änderungen halten.

## Regeln für dieses Repo

- Nur Standards, die für alle Etch-Projekte gelten.
- Kein Design: keine Farben, Farbregeln, Schriften, Schatten, konkreten Abstands- oder Größenwerte, ACSS-Einstellungswerte, Logos oder Gestaltungsvorgaben. Hier stehen nur Technik und Arbeitsweise (wie man ACSS, Etch und Komponenten nutzt), nicht wie eine Website aussieht.
- Generisch formulieren: keine Projektnamen, Domains, Prefixe, Pfade, IDs oder Versionsstände. Platzhalter `<prefix>` / `<PREFIX>` für das Projektkürzel.
- Verweise immer relativ innerhalb von `etch-nodes/`.
