# Quellen für Etch-Komponenten

Fremde Sammlungen von Etch-Komponenten und -Snippets, die regelmäßig auf Neues geprüft werden. Eine Quelle ist kein Standard: Was daraus genutzt wird, wird angepasst und übernommen (siehe [Übernehmen](#übernehmen)). Wo der Stack schon etwas vorgibt (OhMyEtch, Slider Pro, EMMP, WS Form, eigene Plugins), gilt der Stack.

## FlyingW Studio (flyingw.studio, früher UP Studio)

| | |
| --- | --- |
| Adresse | [flyingw.studio](https://flyingw.studio/) – Bibliothek unter `/library/`. Bis Anfang November 2026 UP Studio unter `uplinkpress.studio`; die alte Adresse leitet dauerhaft (301) auf die neue. |
| Herausgeber | Stephen Walker, „A FlyingW initiative“ ([FlyingW.Press](https://flyingw.press), früher UplinkPress) |
| Lizenz | **Offen (seit 05.11.2026).** Bis zum Umzug stand je Komponente CC0 1.0 (gemeinfrei). Seit dem Umzug zeigen die Seiten nur „© 2024–2026 Stephen Walker“, ohne Lizenzhinweis und ohne Lizenzseite. Bis das geklärt ist, gilt nichts von dort als frei nutzbar; nur eine Fassung, die nachweislich unter CC0 bezogen wurde (Lizenz und Datum in der Komponente vermerkt), bleibt CC0. |
| Bezug | Paketdatei `<Name>.clipboard.txt` von der Seite der Komponente, in Etch einfügen (früher Knopf „Copy the JSON“). Bei manchen zusätzlich PHP als Code-Block. |
| Gebaut mit | Etch 1.7, Etch Theme, Automatic.css 4.0 (Variablen mit Rückfallwerten) |
| Versionen | Jede Komponentenseite hat Version und Changelog; Updates kommen nicht von selbst. |
| Neues abfragen | `https://flyingw.studio/wp-json/wp/v2/library?per_page=100&_fields=slug,title,date,modified,link` (alle Einträge mit Datum der Anlage und letzten Änderung). Einen Feed gibt es seit dem Umzug nicht mehr. |

### Bewertung (Stand 05.11.2026, 72 Einträge)

Geprüft wurde der Code hinter „Copy the JSON“ (Stand 17.10.2026, 70 Einträge): externe Quellen, Shortcodes, Sprache, Rücksicht auf „weniger Bewegung“. Am 02.11.2026 wurden beim Umzug fast alle Einträge geändert; an den bewerteten Komponenten war dabei inhaltlich nichts Neues zu sehen. Neu seit 17.10. sind Workbench und Flexi Card Cinque Terre (siehe unten). Vor einer Übernahme die Lizenz klären (siehe oben).

**Brauchbar bei Bedarf** – füllt eine Lücke im Stack; vor dem Einsatz übernehmen und anpassen:

| Komponente | Wofür | Hinweise |
| --- | --- | --- |
| Data Table (`etch-data-table`) | Tabellen aus einem Etch-Loop mit Suche, Sortierung, Seitenwechsel (z. B. Preislisten, Termine, Ergebnisse) | Barrierefrei (`aria-sort`, Live-Region, `scope`). Alle Zeilen stehen im HTML (Loop ohne Begrenzung) – nur für überschaubare Mengen. Texte ins Deutsche. Nutzt ein echtes `<table>`, der Standard für Tabellen in Komponenten ist ein Grid mit Tabellen-Rollen (siehe [konventionen.md](konventionen.md#css)) – vor dem ersten Einsatz entscheiden, ob für sortier- und durchsuchbare Tabellen `<table>` gilt. |
| Video Player Lecce | Selbst gehostete Videos, Untertitel (`track`), Hintergrundvideo | Ohne fremde Server; beachtet „weniger Bewegung“. |
| Video Player Mola | YouTube-Video, das erst nach Klick lädt (youtube-nocookie) | Das Vorschaubild kommt vor dem Klick von `i.ytimg.com` – nur mit lokalem Vorschaubild verwenden. |
| Image Comparison | Vorher/Nachher-Bild mit Schieber | Tastaturbedienung vor dem Einsatz prüfen. |
| Countdown Timer | Countdown bis zu einem Termin | Texte ins Deutsche. |
| Unit Counter, FW Counter, FW Counter – Basic | Zahlen, die beim Scrollen hochzählen | Zahlenformat über `Intl`, beachtet „weniger Bewegung“. |
| Scroll to Top, In Content Scroll to Top | Knopf „nach oben“ | – |
| Print Button | Knopf „Seite drucken“ | – |
| Social Sharing Button | Teilen-Links (Facebook, LinkedIn, Xing, WhatsApp, Mastodon u. a.) | Nur Links, keine Skripte der Netzwerke – datenschutzfreundlich. |
| QR Code | QR-Code einer Adresse, optional zum Herunterladen | Erzeugt im Browser, kein fremder Dienst. |

**Nicht nutzen – der Stack deckt es ab:**

- Accordions (Simple, Responsive, Nested, Show/Hide for SN Accordions), Breadcrumbs Appia Antica I/II, In Page Navigation, Content Filter Rimini, Galerien mit Lightbox (Milano, Venezia, Casale) → OhMyEtch.
- Carousel Alpha Plus, Feature Slider Padua, Slider Section Aviano, Mission Viewer Alpha, Continuous Scroller, Marquee → Slider Pro for Etch; Laufband nur, wenn Slider Pro es nicht kann.
- Open Street Map, Multi Map (OSM), Geo Map Genoa → laden Leaflet von unpkg und Kacheln direkt von OpenStreetMap, die Karte arbeitet zudem mit Shortcode. Stattdessen das eigene Karten-Plugin (Kacheln über die eigene Website).
- Date Time Formatter → Sprache fest `en-US`; Datumsformate über die Etch-Modifier.
- Popover San Marino → erst prüfen, ob OhMyEtch (Dialog, Popover) reicht.
- FW Post Navigator (vorheriger/nächster Beitrag, mit PHP) → vorheriger/nächster Beitrag als dynamische Daten (siehe [konventionen.md](konventionen.md#dynamische-daten-loops-bedingungen)).
- Workbench (1.0.0, 02.11.2026) → Schaufenster für Komponenten (Live-Vorschau im iframe, Code-Tabs, Gerätegrößen); zum Vorstellen von Komponenten, nicht für Kundenseiten.

**Design-Muster – nur als Anregung:** Hero-, Section-, Card-, Feature-, Team-, CTA-, Footer- und Button-Muster (darunter Flexi Card Cinque Terre: Karte, deren Hintergrundfarbe ein Skript aus einer Palette verteilt – ohne Kontrastprüfung, ohne JavaScript durchsichtig), Divider, Avatar, Figure und Effekte (Ambient Backgrounds, Kinetic Grid, Trick Cards, Headliner, Section Separator). Design gehört ins Projekt, Seiten entstehen per Generator; Muster nicht als Komponente übernehmen.

## Etch Developer (etchdeveloper.com)

| | |
| --- | --- |
| Adresse | [etchdeveloper.com/components/](https://etchdeveloper.com/components/) – außerdem Snippets (`/snippets/`) und Plugins (`/plugins/`) |
| Herausgeber | Etch Developer, unabhängige Seite (nicht vom Hersteller von Etch); Unterstützung über Ko-fi (tomchmiel) |
| Lizenz | FSL-1.1-MIT je Komponente: kostenlos auf beliebig vielen eigenen und Kundenseiten, Ändern erlaubt; nicht verkaufen, auch nicht geändert als Komponente, und nicht in einem konkurrierenden Produkt anbieten. Jede Fassung wird zwei Jahre nach Erscheinen MIT. Mitgelieferte fremde Bibliotheken behalten ihre Lizenz. |
| Bezug | Knopf „Copy“ oder `.json` herunterladen, im Etch-Canvas einfügen (Strg+V), speichern. CSS und Skript stecken in der Komponente, kein Plugin, kein PHP. Updates kommen nicht von selbst (löschen und neu einfügen). |
| Gebaut mit | Etch 1.7.1, WordPress 7.1; Variablen `--ed-<komponente>-*`, ACSS-4-Variablen mit Rückfallwerten (ACSS optional) |
| Versionen | Jede Komponentenseite hat Version und Changelog. Alle Komponenten sind Beta, bis die fehlenden Tests mit Screenreadern und echten Geräten gemacht sind. |
| Neues abfragen | `https://etchdeveloper.com/wp-json/wp/v2/components?per_page=100&_fields=slug,title,date,modified,link`, Feed: `https://etchdeveloper.com/components/feed/`. Snippets und Plugins unter `/wp-json/wp/v2/snippet` und `/wp-json/wp/v2/plugins`. |

### Bewertung (Stand 05.11.2026, 8 Komponenten)

Allen gemeinsam: keine externen Quellen (Bibliotheken mitgeliefert), Tastatur, Fokusring und „weniger Bewegung“ berücksichtigt, Texte meist englisch vorbelegt, aber in den Eigenschaften änderbar.

**Brauchbar bei Bedarf** – vor dem Einsatz übernehmen und anpassen:

| Komponente | Wofür | Hinweise |
| --- | --- | --- |
| Read More (`ed-read-more`) | Lange Texte gekürzt zeigen, mit „Weiterlesen“ aufklappen (z. B. Lebensläufe, lange Beschreibungen); „Alle öffnen/schließen“ möglich | Lücke im Stack (das Accordion blendet ganz aus, hier bleibt der Anfang sichtbar). Höhenbasiert, Knopf nur, wenn der Text länger ist; echter `<button>` mit `aria-expanded`/`aria-controls`, abgeschnittener Text bleibt für Screenreader lesbar, ohne JavaScript alles sichtbar. Ausblendung als Maske, passt auf jeden Hintergrund. Suche im Browser öffnet abgeschnittene Treffer nicht. |
| Video Player (`ed-video-player`) | YouTube/Vimeo mit Einwilligung, eigene Videos (MP4/WebM, HLS), Hintergrundvideo, Untertitel (WebVTT) | Mit „Privacy Consent“ geht vor der Zustimmung keine Anfrage an YouTube/Vimeo, auch kein Vorschaubild; an einen vorhandenen Cookie-Banner anbindbar. Damit besser als FlyingW „Mola“. Aber schwer (ca. 260 KB, Lite ohne HLS ca. 70 KB), lädt auf jeder Seite mit Video; Texte der Bedienleiste nur englisch. Für eigene Videos ohne YouTube reicht FlyingW „Lecce“. Erst ab einer stabilen Fassung einsetzen. |
| Audio Player (`ed-audio-player`) | MP3/WAV abspielen (Podcast, vorgelesene Texte), kompakt oder als Karte, Download, Position merken | Bedienung folgt der Sprache der Seite. Lücke im Stack. |
| Before / After Image | Vorher/Nachher-Bild mit Schieber, Zoom, Vollbild | Schieber per Tastatur (Pfeile, Pos1/Ende), als Slider benannt; ohne JavaScript beide Bilder nebeneinander. Gegenüber FlyingW „Image Comparison“ vorziehen. |
| Animated Counter | Zahlen, die beim Scrollen hochzählen | Endwert steht im HTML, Screenreader lesen nur den Endwert. Tausender- und Dezimaltrenner einzeln wählbar (für Deutsch: Punkt und Komma setzen). |
| Back to Top | Knopf „nach oben“ mit Fortschrittsring | Setzt den Fokus danach auf die Hauptüberschrift. Name „Back to top“ im Projekt ins Deutsche setzen; „nur beim Hochscrollen zeigen“ nicht nutzen (Screenreader). Alternative zu FlyingW „Scroll to Top“. |
| Code Block | Programmcode mit Farben, Kopieren, Zeilennummern | Nur für Seiten, die Code zeigen (z. B. Anleitungen). Highlight.js mitgeliefert. |

**Nicht nutzen bzw. nur nach Prüfung:**

- Comments → Gravatar lädt Bilder von fremden Servern, solange „Avatare anzeigen“ in WordPress an ist; keine Einwilligung für Name/E-Mail. Nur mit Avataren aus, Hinweis zum Datenschutz am Formular und Spamschutz; Kommentare sind auf den Websites ohnehin selten.
- Snippets Polylang, Rank Math, Slim SEO → nicht im Stack. Plugin Link Picker → erst prüfen, ob der Etch-Editor es braucht.

## Übernehmen

- Nie direkt in eine Kundenseite einfügen. Erst auf der Testseite für Etch einfügen und prüfen, dann als eigene Komponente im Repo der eigenen Komponenten übernehmen und von dort in die Projekte (Generator → Build → Sync).
- Anpassen: Texte und Feldnamen ins Deutsche, Key mit eigenem Namen, keine externen Quellen (CDN, Schriften, Vorschaubilder), Farben nur über ACSS-Variablen, Hell/Dunkel prüfen, Tastatur und Screenreader prüfen, „weniger Bewegung“ beachten.
- Lizenz beachten: CC0 (FlyingW Studio, nur Fassungen von vor dem Umzug, siehe oben) ist frei; FSL-1.1-MIT (Etch Developer) erlaubt Nutzung und Änderung auf Kundenseiten, aber nicht den Verkauf als Komponente – Lizenztext der Fassung in der Komponente lassen.
- Im Kopf der Komponente (bzw. in der `description`) Quelle und Version vermerken, z. B. „nach FlyingW (früher UP Studio) Data Table 1.0.0 (CC0)“ oder „nach Etch Developer Read More 1.0.0-beta.4 (FSL-1.1-MIT)“. So lässt sich bei der Prüfung erkennen, ob es eine neuere Fassung gibt.

## Regelmäßig prüfen

Bei jeder Update-Runde, mindestens einmal im Monat:

1. Einträge abfragen (Adresse unter „Neues abfragen“) und alle mit `modified` nach dem Stand der Bewertung heraussuchen.
2. Neue Einträge wie oben einordnen (brauchbar, Stack deckt es ab, Design-Muster) und den Code hinter „Copy the JSON“ auf externe Quellen, Shortcodes und Sprache prüfen.
3. Geänderte Einträge, die übernommen wurden, mit der vermerkten Version vergleichen (Changelog auf der Seite) und eine neue Fassung melden.
4. Bewertung und Stand hier nachtragen.

Die zentrale Übersicht der Projekte fragt die Quellen selbst ab (höchstens alle 6 Stunden, mit „Aktualisieren“ sofort) und zeigt an der Karte dieses Repos, wie viele Einträge seit dem Stand der Bewertung neu oder geändert sind. Maßgeblich ist das Datum „Stand“ in der Überschrift der Bewertung – nach jeder Prüfung nachtragen. Der Knopf „Prüfen“ startet Claude mit diesem Ablauf.

Claude meldet Neues mit kurzer Einschätzung und übernimmt nichts ohne Auftrag.
