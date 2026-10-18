# Quellen für Etch-Komponenten

Fremde Sammlungen von Etch-Komponenten und -Snippets, die regelmäßig auf Neues geprüft werden. Eine Quelle ist kein Standard: Was daraus genutzt wird, wird angepasst und übernommen (siehe [Übernehmen](#übernehmen)). Wo der Stack schon etwas vorgibt (OhMyEtch, Slider Pro, EMMP, WS Form, eigene Plugins), gilt der Stack.

## UP Studio (uplinkpress.studio)

| | |
| --- | --- |
| Adresse | [uplinkpress.studio](https://uplinkpress.studio/) – Bibliothek unter `/library/` |
| Herausgeber | Stephen Walker, Initiative [UplinkPress](https://uplink.press) |
| Lizenz | CC0 1.0 (gemeinfrei) je Komponente – auch in Kundenprojekten frei nutzbar. Ausgenommen sind Teile, die auf der Seite als fremd gekennzeichnet sind. |
| Bezug | Knopf „Copy the JSON“ auf der Seite der Komponente (Etch-Komponente mit Stilen, zum Einfügen in Etch). Bei manchen zusätzlich PHP als Code-Block. |
| Gebaut mit | Etch 1.7, Etch Theme, Automatic.css 4.0 (Variablen mit Rückfallwerten) |
| Versionen | Jede Komponentenseite hat Version und Changelog; Updates kommen nicht von selbst. |
| Neues abfragen | `https://uplinkpress.studio/wp-json/wp/v2/library?per_page=100&_fields=slug,title,date,modified,link` (alle Einträge mit Datum der Anlage und letzten Änderung). Feed nur für neue Einträge: `https://uplinkpress.studio/library/feed/` |

### Bewertung (Stand 17.10.2026, 70 Einträge)

Geprüft wurde der Code hinter „Copy the JSON“: externe Quellen, Shortcodes, Sprache, Rücksicht auf „weniger Bewegung“.

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

**Design-Muster – nur als Anregung:** Hero-, Section-, Card-, Feature-, Team-, CTA-, Footer- und Button-Muster, Divider, Avatar, Figure und Effekte (Ambient Backgrounds, Kinetic Grid, Trick Cards, Headliner, Section Separator). Design gehört ins Projekt, Seiten entstehen per Generator; Muster nicht als Komponente übernehmen.

## Übernehmen

- Nie direkt in eine Kundenseite einfügen. Erst auf der Testseite für Etch einfügen und prüfen, dann als eigene Komponente im Repo der eigenen Komponenten übernehmen und von dort in die Projekte (Generator → Build → Sync).
- Anpassen: Texte und Feldnamen ins Deutsche, Key mit eigenem Namen, keine externen Quellen (CDN, Schriften, Vorschaubilder), Farben nur über ACSS-Variablen, Hell/Dunkel prüfen, Tastatur und Screenreader prüfen, „weniger Bewegung“ beachten.
- Im Kopf der Komponente (bzw. in der `description`) Quelle und Version vermerken, z. B. „nach UP Studio Data Table 1.0.0 (CC0)“. So lässt sich bei der Prüfung erkennen, ob es eine neuere Fassung gibt.

## Regelmäßig prüfen

Bei jeder Update-Runde, mindestens einmal im Monat:

1. Einträge abfragen (Adresse unter „Neues abfragen“) und alle mit `modified` nach dem Stand der Bewertung heraussuchen.
2. Neue Einträge wie oben einordnen (brauchbar, Stack deckt es ab, Design-Muster) und den Code hinter „Copy the JSON“ auf externe Quellen, Shortcodes und Sprache prüfen.
3. Geänderte Einträge, die übernommen wurden, mit der vermerkten Version vergleichen (Changelog auf der Seite) und eine neue Fassung melden.
4. Bewertung und Stand hier nachtragen.

Die zentrale Übersicht der Projekte fragt die Quellen selbst ab (höchstens alle 6 Stunden, mit „Aktualisieren“ sofort) und zeigt an der Karte dieses Repos, wie viele Einträge seit dem Stand der Bewertung neu oder geändert sind. Maßgeblich ist das Datum „Stand“ in der Überschrift der Bewertung – nach jeder Prüfung nachtragen. Der Knopf „Prüfen“ startet Claude mit diesem Ablauf.

Claude meldet Neues mit kurzer Einschätzung und übernimmt nichts ohne Auftrag.
