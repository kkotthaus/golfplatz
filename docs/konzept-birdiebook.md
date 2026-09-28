# Konzept: Platz & Bahnen als Birdiebook

Stand 2026-09-26 · Status: **umgesetzt (Phasen 1–3, Felder aus Phase 4)** – Einzelheiten in [umsetzung.md](umsetzung.md)

**Abweichungen vom Entwurf**
- **Nummernleiste:** eigene Buttons statt eines zweiten EtchSliderPro-Sliders (Thumbnail). So haben die Nummern echte Button-Beschriftungen und `aria-current` und bleiben per Tastatur erreichbar; das Skript scrollt die aktuelle Nummer in die Mitte.
- **Vor/Zurück:** eigene Knöpfe statt „DWC Slider Nav Button“. Dessen festes englisches `aria-label` („go to next slide“) würde die sichtbare Beschriftung „Bahn 8“ überdecken (WCAG 2.5.3).
- **Ausweichlösung:** Lädt das Skript von EtchSliderPro nicht, übernimmt ein natives Wisch-Karussell (CSS Scroll-Snap) mit derselben Bedienung; ohne JavaScript stehen die Bahnen untereinander.
- **Bahngrafik:** wird aus den Daten als HTML/CSS gezeichnet, nicht als SVG (Etch filtert SVG in Raw-HTML). Ein hochgeladenes Bild (`bahn_grafik_hoch`) ersetzt sie.

## Ausgangslage

- `/platz/` zeigt in WordPress bisher nur Seitenkopf und Einleitungstext. Die Bahnkarten, die Scorekarte und die Rating-Tabelle aus dem Prototyp fehlen noch: Die Etch-Loops über `spielbahn` sind noch nicht gebaut (offener Punkt in [umsetzung.md](umsetzung.md)).
- Die 18 Bahnen liegen als Beitragstyp `spielbahn` vor. Felder: Nummer, Par Herren/Damen, HCP, Längen Gelb/Blau/Rot/Orange, Beschreibung, Spieltipp, Grafik, Bilder, Video. Die Bahnen haben keine Namen.
- In Etch sind die Komponenten von **EtchSliderPro** hinterlegt: Slider Wrapper (240), Slider (241), Slide (242), Progress (243), Play-Pause (244), Pagination (245) und Nav Button (246).

## Ziel

Die Seite soll auf dem Handy während der Runde als **Birdiebook** funktionieren:

- Bahn für Bahn wischen.
- Die Längen gelten für den eigenen Abschlag.
- Alles Wichtige ist mit einem Blick und mit einer Hand erreichbar, auch bei Sonne.
- Auf dem Desktop bleibt die Seite eine normale Informationsseite.

## Nutzung auf dem Platz (Anforderungen)

| Situation | Anforderung | Lösung |
| --- | --- | --- |
| Am Abschlag, Handy in einer Hand | Nächste Bahn ohne Suchen | Wischen oder großer „Weiter“-Knopf unten (Daumenzone) |
| Einstieg mitten in der Runde (z. B. an Tee 10) | Direkt zu jeder Bahn | Nummernleiste 1–18 (Thumbnail-Slider), Direktlink `…#bahn-10` |
| Verschiedene Abschläge | Längen passend zum eigenen Tee | Abschlag-Wahl Gelb/Blau/Rot/Orange, wird im Browser gemerkt; Par wechselt bei Rot/Orange auf Par Damen |
| Sonne, kurzer Blick | Große Zahlen, hoher Kontrast | Länge und Par sehr groß; AA-Kontraste (siehe `kontrast.mjs`), heller Modus als Standard |
| Schlechter Empfang | Seite muss schnell sein | Leichte SVG-Grafiken, Bilder nur bei Bedarf laden, Video nur auf Knopfdruck; Phase 2: offline über PWA |
| Bahn gesperrt / Wintergrüns | Aktuelle Lage sehen | Status aus `platzstatus`/`sperrung` direkt auf der Bahnkarte |
| Barrierefreiheit | Bedienbar ohne Wischgeste | Sichtbare Vor/Zurück-Knöpfe (WCAG 2.5.1), Tastatur, Screenreader-Ansage „Bahn 7 von 18“, kein Autoplay, `prefers-reduced-motion` |

## Aufbau der Seite `/platz/`

| # | Abschnitt | Handy | Desktop |
| --- | --- | --- | --- |
| 1 | Seitenkopf | Titel, Par/Länge; Knopf **„Birdiebook öffnen“** (springt zu #2 bzw. öffnet `/platz/birdiebook/`) | wie heute |
| 2 | **Birdiebook** (Komponente `Birdiebook`) | Slider über die volle Bildschirmhöhe, eine Bahn pro Slide | Slider mit Grafik links und Daten rechts, Tastatur ← → |
| 3 | Platzbeschreibung | Text | Text |
| 4 | Alle 18 Bahnen (`hole-card`, Loop) | kompakte Liste | Kartenraster; Klick → Bahnseite |
| 5 | Scorekarte + Course/Slope Rating (Loop) | quer scrollbar | Tabelle |

**Zusätzlich empfohlen:** eine eigene Vollbildseite `/platz/birdiebook/` mit derselben Komponente.
- Sie hat nur eine schmale Kopfleiste (Logo, Platzstatus-Ampel, Schließen) und keinen Footer.
- Man kann sie „zum Home-Bildschirm hinzufügen“, dann wirkt sie wie eine App.
- Auf `/platz/` bleibt das Birdiebook eingebettet, damit man es auch dort findet.

## Aufbau einer Bahn (Slide), Handy hochkant

```
┌──────────────────────────────┐
│ BAHN 7            ● frei      │  Kopf: Nummer groß, Status (Sperrung/Wintergrün)
│ Par 4 · HCP 11                │
│ 342 m  ab Gelb                │  Länge zum gewählten Abschlag, sehr groß
├──────────────────────────────┤
│        [ Grün ]  ← 28 m tief  │
│   B ·· 95 m zur Mitte         │  Bahngrafik hochkant: Grün oben, Abschlag unten,
│        ║                      │  Hindernisse mit Entfernung zur Grünmitte
│  W ··· 180 m zur Mitte        │
│        ║                      │
│      [Abschläge]              │
├──────────────────────────────┤
│ Tipp: Links halten, der       │  Spieltipp (kurz), „Mehr zur Bahn“ klappt
│ Wasserhügel rechts …  ▾ mehr  │  Beschreibung/Video auf (OhMyEtch Accordion)
├──────────────────────────────┤
│ Gelb Blau Rot Orange          │  Abschlag-Wahl (fest am unteren Rand)
│ ‹ 5  6 [7] 8  9 ›             │  Nummernleiste 1–18 (Thumbnail-Slider)
│ [  ‹ Bahn 6  ] [ Bahn 8 ›  ]  │  große Knöpfe in der Daumenzone
└──────────────────────────────┘
```

Die Entfernungen der Hindernisse beziehen sich auf die **Grünmitte**. Damit gelten sie für jeden Abschlag gleich, und pro Hindernis genügt ein Wert. Die Länge der Bahn wechselt mit dem gewählten Abschlag.

## Umsetzung mit EtchSliderPro

```
DWC Slider Wrapper   (Klasse birdiebook, data-arrow-keys="true")
├─ DWC Slider        Rolle main · Typ Slide · perPage 1 · Loop aus · Rewind aus
│  │                 Autoplay aus · Pagination aus · Pfeile aus (eigene Knöpfe)
│  │                 Aria Label „Birdiebook, 18 Bahnen“
│  └─ Etch-Loop über spielbahn (sortiert nach bahn_nummer)
│     └─ DWC Slide → Inhalt der Bahn (BEM: hole-sheet__*)
├─ DWC Slider        Rolle Thumbnail · perPage 7 (sm: 5) · Focus center
│  └─ Etch-Loop → DWC Slide mit Nummer (Button „Bahn 7“)
├─ DWC Slider Nav Button  previous  („‹ Bahn 6“, Text per JS aktualisiert)
├─ DWC Slider Nav Button  next      („Bahn 8 ›“)
└─ DWC Slider Progress    Zähler „7 / 18“
```

- **Einstellungen:**
  - Loop bleibt aus, weil eine Runde von 1 bis 18 läuft.
  - Autoplay bleibt aus (Barrierefreiheit).
  - Main- und Thumbnail-Slider werden laut Doku automatisch synchron, wenn beide im selben Wrapper liegen.
- **Farben:** Die Slider-Variablen (`--dot-bg`, Nav-Button-Farben) werden wie bei EtchMegaMenuPro auf ACSS-Farben gesetzt, in einer eigenen Datei `etch/css/esp.css`.
- **Abschlag-Wahl:** eine eigene kleine Komponente (Radiogruppe, kein Slider).
  - Jede Slide enthält alle vier Längen als `data-laenge-gelb` usw.
  - CSS blendet die gewählte Länge ein (`[data-tee="gelb"]` am Wrapper).
  - JS merkt sich die Wahl in `localStorage`.
  - Ohne JS ist Gelb sichtbar.
- **Direktlink und letzte Bahn:** Laut Doku unterstützt EtchSliderPro keinen Hash (`#bahn-7`).
  - Ein kleines Skript liest den Hash bzw. die zuletzt angesehene Bahn (`localStorage`) und springt dorthin.
  - Dafür löst es einen versteckten Nav Button mit „Go to Slide“ aus oder nutzt die Splide-Instanz.
  - Umgekehrt aktualisiert es beim Wischen den Hash.
- **Ohne JavaScript** müssen die 18 Bahnen untereinander lesbar bleiben (Progressive Enhancement). Wie sich EtchSliderPro dabei verhält, ist zu prüfen.
- **Umsetzungsweg:** wie beim Header über den Generator (`wordpress/etch/birdiebook.mjs`) mit `emmp()`-ähnlichem Helfer für die DWC-Komponenten; Seiten-Sync per MCP.

## Datenmodell: Ergänzungen an `spielbahn`

Alles optional. Der Club liefert die Werte, z. B. aus einem vorhandenen gedruckten Birdiebook oder aus der Vermessung durch das Greenkeeping.

| Feld (ID) | Typ | Zweck |
| --- | --- | --- |
| `bahn_grafik_hoch` | Bild/SVG | Bahngrafik hochkant (Grün oben) fürs Handy. Solange sie fehlt: die vorhandene `bahn_grafik` bzw. die aus den Daten erzeugte Platzhaltergrafik |
| `bahn_hindernisse` | Gruppe, klonbar: `art` (Bunker, Wasser, Aus, Baum, Marker), `seite` (links/mitte/rechts), `bis_gruenmitte` (m), `bezeichnung` | Entfernungen im Birdiebook, Marker in der Grafik |
| `gruen_tiefe` | Zahl (m) | Grüntiefe |
| `gruen_vorne`, `gruen_hinten` | Zahl (m, relativ zur Mitte) | optional für Anfang/Ende des Grüns |
| `bahn_richtung` | Auswahl (gerade, Dogleg links, Dogleg rechts) | kurze Beschreibung und Symbol |
| `bahn_spieltipp` | vorhanden | wird auf 1–2 Sätze begrenzt (Hinweis im Feld) |

Status je Bahn: Sperrungen gibt es heute für Abschlag 1 und 10 und für den ganzen Platz. Sollen einzelne Bahnen gesperrt werden können (z. B. „Bahn 7 Sommergrün gesperrt“), bräuchte `sperr_bereich` die Werte `bahn_1` … `bahn_18`. Das wäre eine Erweiterung, die abzustimmen ist.

## Barrierefreiheit (WCAG 2.1 AA)

- **Bedienung ohne Wischen:** Jede Wischgeste hat eine Alternative per Knopf (2.5.1).
- **Größe der Bedienelemente:** Knöpfe und Nummern sind mindestens 44 × 44 px groß (2.5.5 AAA; AA verlangt 24 px).
- **Screenreader:** Splide liefert Rollen und Beschriftungen („Bahn 7 von 18“). Die Nummern sind echte Buttons mit `aria-label`, die aktuelle Bahn hat `aria-current`.
- **Bewegung:** kein Autoplay; bei `prefers-reduced-motion` Übergänge ohne Animation.
- **Grafik:** Die Bahngrafik ist Schmuck (`aria-hidden`). Alle Entfernungen stehen zusätzlich als Text in einer Liste.
- **Farbe:** Kontraste werden mit `kontrast.mjs` geprüft; Status nie nur über Farbe.
- **Querformat und Zoom:** Die Seite funktioniert bei 200 % Zoom und im Querformat (1.4.4, 1.3.4).

## Phasen

1. **Grundlage:** Loops für Bahnkarten, Scorekarte und Rating auf `/platz/`. Danach sind die Bahnen sichtbar.
2. **Birdiebook:** Komponente mit EtchSliderPro, Abschlag-Wahl, Direktlink, Status je Bahn; eingebettet auf `/platz/`.
3. **Vollbildseite** `/platz/birdiebook/` mit Web-App-Manifest (Home-Bildschirm).
4. **Daten:** Felder für Hindernisse und Grün in Meta Box; der Club pflegt die Werte, die Grafik zeigt die Marker.
5. **Optional:** offline per Service Worker, Display wach halten (Wake Lock), GPS-Entfernung zum Grün. Für GPS bräuchte man die Koordinaten der Grüns; das wäre ein eigenes Projekt.

## Offene Fragen an den Club

1. Gibt es ein gedrucktes Birdiebook oder vermessene Entfernungen, die wir übernehmen können?
2. Gibt es Luftbilder oder Bahngrafiken, auch hochkant?
3. Soll es die eigene Vollbildseite `/platz/birdiebook/` geben (Empfehlung: ja)?
4. Sollen einzelne Bahnen gesperrt werden können?

## Risiken und Prüfpunkte

- Sind DWC Slides aus einem Etch-Loop möglich? Die Doku sagt dazu nichts. Das prüfen wir in Phase 2 als Erstes. Plan B: Der Loop erzeugt das Markup der Slides direkt (`splide__slide`), der Slider bleibt die DWC-Komponente.
- Sprung per Hash und Speichern der letzten Bahn gehen nur über eigenes Skript (siehe oben).
- Barrierefreiheit von EtchSliderPro (Beschriftungen, Fokus) im Browser prüfen und die Beschriftungen auf Deutsch setzen.
