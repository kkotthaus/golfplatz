# Neuen Club einrichten

Dieses Repository ist ein neutraler Blueprint. Alle Inhalte sind Platzhalter („Golfclub Musterclub“, Musterstraße 1, 12345 Musterstadt, Adressen unter `example.org`, Telefon `01234 …`). Funktionen, Templates, Seitenstruktur und Einstellungsseiten sind vollständig und müssen für einen neuen Club nicht angefasst werden.

## Wo die Clubdaten stehen

| Was | Datei bzw. Ort | Hinweis |
| --- | --- | --- |
| Name, Adresse, Kontakt, Rechtliches, Geo, Texte, Anbindungen | `prototype/src/data.mjs` › `club` | zentrale Konfiguration im Repo; `build.mjs` schreibt daraus `dist/daten/einstellungen-clubdaten.json` |
| Scorekarte (Abschläge, CR/Slope, Bahnen) | `data.mjs` › `abschlaege`, `rohBahnen` | |
| Öffnungszeiten, Platzstatus | `data.mjs` › `oeffnungszeiten`, `platzstatus` | |
| Preise, Kurse, Personen | `data.mjs` › `preise`, `kurse`, `kurseAnmeldung`, `personen` | |
| Mannschaften, Ligaspiele, News, Lochwettspiele, Sperrungen | `data.mjs` | Beispiele; mit Ligaportal kommen Mannschaften und Ligaspiele vom Verband |
| Farben | `wordpress/etch/acss-farben.mjs` › `palette` (Primary, Secondary …) | danach `node wordpress/etch/kontrast.mjs` – muss fehlerfrei sein |
| Logo | WordPress › Clubdaten › Club › **Logo** / **Logo für dunkle Flächen** | ohne Logo: Wortmarke aus „Name im Logo“ + Ort · Region |
| Website-Icon | WordPress › Design › Website-Informationen | auch Icon der Birdiebook-Web-App |

Im Markup (Templates, Komponenten) steht kein Clubname, Ort oder clubeigener Text. Alles kommt über `club('<feld>')` bzw. `{options.golfplatz.club.…}` aus den Clubdaten.

## Anbindungen (leer = aus)

| Anbindung | Felder | Wirkung ohne Eintrag |
| --- | --- | --- |
| PC CADDIE://online | `pccaddie_code` (Clubdaten › Gäste & Systeme) | kein stündlicher Turnierabruf, Turnierkalender leer |
| Partnerclubs (Platzbelegung) | `partnerclubs` | Platzbelegung zeigt nur den eigenen Platz |
| Ligaportal des Landesverbands (liga.golf) | `verband_name`, `verband_liga_web`, `verband_liga_api`, `verband_suchbegriff` | kein Liga-Abgleich; Mannschaften und Ligaspiele von Hand |

## Ablauf

1. `prototype/src/data.mjs` und `wordpress/etch/acss-farben.mjs` anpassen.
2. `node wordpress/etch/kontrast.mjs`, dann `node prototype/src/build.mjs` und `node wordpress/etch/build.mjs`.
3. `wordpress/etch/dist/*` nach `wp-content/golfplatz/` kopieren.
4. MCP: `golfplatz/snippets-sync`, `golfplatz/sync-from-files` (`all`), `golfplatz/acss-colors` (`aus_datei: true`), `golfplatz/import-settings` für `clubdaten` und `platzstatus`, `golfplatz/import-content` für `spielbahn`, `person`, `preis`, `kurs`, `post`, `lochwettspiel`, `sperrung` und – ohne Ligaportal – `mannschaft`, dann `ligaspiel`.
5. Vorher die Platzhalter-Inhalte löschen, die der Club nicht übernimmt (Beispiel-News, Beispiel-Sperrungen, Beispiel-Lochwettspiele, Platzhalter-Mannschaften). `import-content` aktualisiert nur per Schlüssel, es löscht nichts.
6. Logo und Website-Icon im Backend hochladen, Texte unter Clubdaten › Club › Auftritt & Texte prüfen.
7. Einstellungen › Allgemein: Titel der Website setzen.

## Achtung beim Übernehmen in ein Club-Repository

Ein Club-Repository (z. B. per Remote `blueprint`) bekommt mit `git pull blueprint main` auch die Platzhalter. Konflikte entstehen dann nur in `prototype/src/data.mjs`, `wordpress/etch/acss-farben.mjs` und den erzeugten Dateien (`prototype/**/index.html`, `wordpress/etch/dist/`). Dort die Club-Fassung behalten und danach neu bauen.
