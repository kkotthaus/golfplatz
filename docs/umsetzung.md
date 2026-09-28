# Umsetzung – Stand 2026-09-24

Was in WordPress (golfplatz.local) bereits angelegt ist, was noch von Hand zu tun ist und wie der Prototyp nach Etch übertragen wird. Fachlicher Rahmen: [projekt.md](projekt.md).

## In WordPress angelegt (per MCP, Meta Box)

### Beitragstypen

| Slug | Name | Öffentlich | URL | Hinweis |
| --- | --- | :---: | --- | --- |
| `spielbahn` | Spielbahnen | ✓ | `/platz/bahn/<nr>/` | 18 Einträge „Bahn 1“ … „Bahn 18“, Slug = Bahnnummer. Die Bahnen haben keine Namen. |
| `sperrung` | Sperrungen | – | – | eigene Capability `sperrung`/`sperrungen` (siehe unten) |
| `mannschaft` | Mannschaften | ✓ | `/mannschaften/<slug>/` | Archiv `/mannschaften/` |
| `spieler` | Spieler | – | – | Untermenü von Mannschaften |
| `ligaspiel` | Ligaspiele | – | – | Untermenü von Mannschaften |
| `spielbericht` | Spielberichte | ✓ | `/spielberichte/<slug>/` | Untermenü von Mannschaften |
| `kurs` | Kurse (Menü „Golfschule“) | ✓ | `/golfschule/kurs/<slug>/` | |
| `preis` | Preise | – | – | Reihenfolge über „Reihenfolge“ (menu_order) |
| `person` | Personen (Menü „Team & Vorstand“) | – | – | Vorstand, Sekretariat, Pros, Team |
| `turnier` | Turniere (Menü „Turniere (PC CADDIE)“) | – | – | aus PC CADDIE, stündlich abgeglichen; nicht von Hand ändern |
| `gastclub` | Gastclubs (Untermenü von Mannschaften) | – | – | Austragungsorte der Ligaspiele mit Website; legt der Verband-Abgleich an |
| `lochwettspiel` | Lochwettspiele (Menü „Lochwettspiel“) | ✓ | `/turniere/lochwettspiel/<jahr>/` | ein Eintrag je Jahr, Slug = Jahr; 2 Beispiele (2025, 2026) |

News sind normale **Beiträge** (`post`).

### Taxonomien

| Slug | für | Begriffe anlegen |
| --- | --- | --- |
| `preiskategorie` | `preis` | angelegt: Greenfee (13), Turnier-Greenfee (4), Kooperation (5), Leihgeräte (4), Mitgliedschaft (2) – alle Preise von dreibaeumen.de importiert |
| `personengruppe` | `person` | angelegt: Vorstand, Betreibergesellschaft (Graham Thomas, Geschäftsführer Dohrmann Golfplatz AG), Clubmanagement, Sekretariat, Service & Proshop, Golfschule, Greenkeeping, Captains. 16 Personen von dreibaeumen.de › Club importiert (`golfplatz/import-content`, Typ `person`); eine Person kann mehreren Gruppen angehören. |

### Feldgruppen

| Gruppe | Ziel | Felder (IDs) |
| --- | --- | --- |
| Spielbahn | `spielbahn` | `bahn_nummer`, `bahn_par_herren`, `bahn_par_damen`, `bahn_hcp`, `laenge_gelb`, `laenge_blau`, `laenge_rot`, `laenge_orange`, `bahn_beschreibung`, `bahn_spieltipp`, `bahn_grafik`, `bahn_bilder`, `bahn_video` (nur MP4), `bahn_video_poster` |
| Sperrung | `sperrung` | `sperr_bereich` (`abschlag_1`, `abschlag_10`, `platz`, `range`, `kurzspiel`, `proshop`, `trolley`, `buggy`), `sperr_beginn`, `sperr_ende` (beide als Unix-Timestamp), `sperr_grund` |
| Mannschaft | `mannschaft` | `mannschaft_altersklasse`, `mannschaft_nummer`, `mannschaft_geschlecht`, `mannschaft_liga` (setzt der Abgleich), `mannschaft_spielfuehrer` → Spieler, `mannschaft_kader` → Spieler (mehrfach), `mannschaft_verband_wettbewerb`, `mannschaft_verband_team` |
| Spieler | `spieler` | `spieler_vorname`, `spieler_nachname`, `spieler_geschlecht`, `spieler_jahrgang`, `spieler_einwilligung` |
| Ligaspiel | `ligaspiel` | `ligaspiel_mannschaft` → Mannschaft, `ligaspiel_spieltag`, `ligaspiel_termin` (Timestamp), `ligaspiel_spielort`, `ligaspiel_heimspiel`, `ligaspiel_ergebnis`, `ligaspiel_platzierung`, `ligaspiel_saison`, `ligaspiel_liga`, `ligaspiel_verband_link`, `ligaspiel_extern_id` (Verbands-Kennung, schreibgeschützt) |
| Gastclub | `gastclub` | `gastclub_website` (Titel = Clubname wie beim Verband) |
| Turnier | `turnier` | alle schreibgeschützt, aus PC CADDIE: `turnier_extern_id`, `turnier_beginn`, `turnier_hat_uhrzeit`, `turnier_kategorien` (D, H, S, J, C, M, T), `turnier_untertitel`, `turnier_spielform`, `turnier_loecher`, `turnier_vorgabewirksam`, `turnier_gaeste`, `turnier_anmeldeschluss`, `turnier_teilnehmer_max`, `turnier_plaetze_frei`, `turnier_link_anmeldung`, `_details`, `_ausschreibung`, `_ergebnisse`, `_startliste` |
| Spielbericht | `spielbericht` | `bericht_ligaspiel` → Ligaspiel, `bericht_bilder` |
| Kurs | `kurs` | `kurs_typ`, `kurs_preis` (leer = auf Anfrage), `kurs_max_teilnehmer`, `kurs_dauer`, `kurs_trainer` → Person, `kurs_termine` (Gruppe, klonbar: `von`, `bis`, `uhrzeit`), `kurs_anmeldung` |
| Preis | `preis` | `preis_betrag`, `preis_auf_anfrage`, `preis_einheit` (`runde18`, `runde9`, `runde`, `tag`, `monat`, `jahr`, `einmalig`), `preis_tage` (Gültig an), `preis_zusatz`, `preis_hervorheben`, `preis_aufnahme`, `preis_leistungen` |
| Person | `person` | `person_funktion`, `person_email`, `person_telefon`, `person_text` |
| Lochwettspiel | `lochwettspiel` | `lw_jahr`, `lw_spielform` (`vierball`, `vierer`, `chapman`, `greensome`), `lw_hinweis`, `lw_runden` (klonbar: `name`, `von`, `bis`), `lw_teams` (klonbar: `spieler_1`, `spieler_2`, `name`, `position`, `team_id` automatisch/ausgeblendet), `lw_pruefung` (Kasten „Stand“), `lw_spiele` (klonbar: `paarung` = „<runde>:<team_id des Siegers>“, `ergebnis`, `datum`) |
| Sichtbarkeit | `post`, `page` | `nur_mitglieder` |
| Platzstatus | Einstellungsseite `platzstatus` | Schnellsperren je `<bereich>_gesperrt` + Grund + Ende für `platz`, `range`, `kurzspiel`, `proshop` (`*_sperrgrund`, `*_gesperrt_bis`) und `trolley`, `buggy` (`*_grund`, `*_bis`); `gruens` (`sommer`/`winter`), `gruens_hinweis` |

### Zentrale Clubdaten (Einstellungsseite `clubdaten`)

Alle Stammdaten des Clubs stehen an **einer** Stelle: WordPress-Menü **„Clubdaten“**. Seiten, Templates und Komponenten lesen sie von dort, nichts davon wird in Seiten abgetippt. Nur „Platzstatus“ ist eine eigene Seite, damit die Rolle „Platzstatus“ sperren darf, ohne an die übrigen Clubdaten zu kommen.

| Tab | Feldgruppe | Felder |
| --- | --- | --- |
| Club | Clubdaten · Club | `club_name`, `club_kurzname`, `club_claim`, `club_gegruendet`, `club_mitglieder`, `club_jugend`, `club_logo`, `club_logo_hell` |
| Kontakt & Anfahrt | Clubdaten · Kontakt & Anfahrt | `club_strasse`, `club_plz`, `club_ort`, `club_telefon`, `club_fax`, `club_email`, `club_anfahrt_auto`, `club_anfahrt_oepnv`, `club_karte`, `club_routenlink` |
| Öffnungszeiten | Clubdaten · Öffnungszeiten | Je Bereich (`sekretariat`, `range`, `kurzspiel`, `proshop`, `restaurant`): `zeiten_<bereich>_standard` (klonbar: `tage` = mo…so, `von`, `bis`), `zeiten_<bereich>_ausnahmen` (klonbar: `titel`, `von`, `bis` als JJJJ-MM-TT, `geschlossen`, `zeiten` wie Standard), `zeiten_<bereich>_hinweis`. Eine Ausnahme ersetzt an ihren Tagen die Standardzeiten. |
| Gäste & Systeme | Clubdaten · Gäste & Systeme | `anmeldung_telefon`, `anmeldung_hinweis`, `ruhetag_hinweis`, `pccaddie_code`. Keine Startzeitbuchung – der Club verzichtet bewusst auf feste Startzeiten. |
| Restaurant | Clubdaten · Restaurant | `restaurant_name`, `restaurant_telefon`, `restaurant_hinweis`, `restaurant_speisekarte` |
| Platz & Abschläge | Clubdaten · Platz & Abschläge | je `abschlag_gelb`/`_blau`/`_rot`/`_orange` eine Gruppe mit `geschlecht` (für wen bewertet), `cr`, `slope`, `par`. Längen werden aus den Bahnen summiert. |
| Rechtliches | Clubdaten · Rechtliches | `recht_vertretung`, `recht_registergericht`, `recht_registernummer`, `recht_ust_id`, `recht_verantwortlich`, `recht_datenschutz_kontakt` |
| Social Media | Clubdaten · Social Media | `social_instagram`, `social_facebook`, `social_youtube` |

**Abruf in Etch:** `{options.metabox.clubdaten.<feld>}`, z. B. `{options.metabox.clubdaten.club_telefon}`. Öffnungszeiten über die Etch-Komponenten „Öffnungszeiten“ (Eigenschaft `bereich`, z. B. `sekretariat`) bzw. „Öffnungszeiten alle“. Das Modul `golfplatz-platzstatus.php` berechnet „Jetzt geöffnet/geschlossen“, die nächste Öffnung und die kommenden Ausnahmen, berücksichtigt Sperren aus dem Platzstatus und liefert alles als `{options.golfplatz.zeiten}`. Im Generator: `club('club_telefon')`, `telHref('club_telefon')` (siehe `wordpress/etch/lib.mjs`).

**Komponenten, die die Clubdaten nutzen:** „Footer“ (`SiteFooter`), „Als Gast spielen“ (`GastInfo`, Eigenschaft `anker`), „Öffnungszeiten“ (`Oeffnungszeiten`, Eigenschaften `bereich` und `variante`).

**Quelle der Daten:** Clubdaten, Öffnungszeiten, Scorekarte 2024, Greenfee und Rechtliches stammen von [dreibaeumen.de](https://dreibaeumen.de/) (Stand 2026-09-25) und werden mit `golfplatz/import-settings` bzw. `golfplatz/import-content` aus `prototype/src/data.mjs` übernommen. Preise und Golfschule-Angebote (Schnupperkurs 35 €, Unterricht und DGV-Platzreife auf Anfrage) ebenfalls von dort. Die ordentliche Mitgliedschaft läuft über eine Aktie der Dohrmann Golfplatz AG oder eine Spielberechtigung; ihr Preis steht nicht auf der Website und ist deshalb „auf Anfrage“. Offen und vom Club zu liefern: Gründungsjahr, Anfahrtsbeschreibung, Bahnbeschreibungen, Restaurant, Öffnungszeiten Kurzspielbereich, Mannschaften.

### Entscheidung: Beziehungen als Post-Felder

Die Beziehungen (Kader, Spielführer, Ligaspiel → Mannschaft, Spielbericht → Ligaspiel, Kurs → Trainer) sind als Meta-Box-Felder vom Typ **Post** umgesetzt und nicht mit MB Relationships. Grund: Über die MCP-Schnittstelle lassen sich keine Relationships anlegen. Die Mannschaft eines Spielberichts ergibt sich aus dem Ligaspiel. Soll später doch MB Relationships verwendet werden, lassen sich die Felder ersetzen, solange noch keine Inhalte gepflegt sind.

### Farben: ACSS-Farbsystem

Alle Farben stehen in **Automatic.css → Farben** (Primary Clubgrün `#1D8475`, Secondary Clubrot `#A31C39`, Base, Success, Warning, Danger, Info), Unify-Lightness ist aus. Das CSS (`golfplatz.css`, EMMP-Variablen) nutzt nur ACSS-Variablen. Ändern: Palette in `wordpress/etch/acss-farben.mjs` anpassen, `node wordpress/etch/acss-farben.mjs` ausgeben und mit `golfplatz/acss-colors` übernehmen – oder direkt in den ACSS-Einstellungen, dann die Palette in der Datei nachziehen.

**Barrierefreiheit:** Alle Text-/Hintergrund-Kombinationen erfüllen WCAG 2.1 AA in Hell und Dunkel (Prüfskript `node wordpress/etch/kontrast.mjs`; zusätzlich per Browser-Audit auf Startseite, Greenfee, Platz, Club, Mitgliedschaft, Golfschule und den Prototyp-Seiten geprüft, Stand 2026-09-26). Umgesetzt: grüne Schrift in `--primary-dark`; Seiten-Hero und Tabellenköpfe auf `#1D8475` mit weißer Schrift; Top-Bar und Footer auf `--primary-ultra-dark`; ACSS-Buttons mit weißer Schrift (`btn-primary-text`, `btn-secondary-text` usw. = `var(--white)`, die ACSS-Vorgabe `-ultra-light` hatte nur 4,15:1); Danger etwas oranger (`#b42318`) als das Clubrot, damit Sperren nicht wie Markenfarbe wirken; Nebentext und Eingabefeld-Rahmen dunkler. Die Landschaftsgrafik im Startseiten-Hero (Platzhalter bis zum Foto) nutzt ebenfalls nur ACSS-Farben: Himmel und Hügel sind aus `--primary` und `--primary-ultra-light` gemischt, die Fahne ist Clubrot. Weil Text und Buttons je nach Bildschirm auf den Hügeln liegen, bleiben die Hügel hell. Der Button „Mitglied werden“ hat dort einen weißen Hintergrund. Geprüft bei 375, 768, 1280 und 1920 px.

**Hell/Dunkel:** In ACSS ist „Auto Color Scheme“ an (`auto-color-scheme`, Website-Schema „light only“, Referenz-Tokens an). Hell ist Standard. Der Knopf in der Top-Bar (Etch-Komponente FarbschemaUmschalter, Skripte im mu-plugin `golfplatz-farbschema.php`) setzt `scheme--dark` am `<html>` und merkt sich die Wahl im Browser (`localStorage`, Schlüssel `golfplatz-farbschema`); die Systemeinstellung des Besuchers wird bewusst nicht übernommen. Die Dunkel-Werte (Hauptfarben, Seitenhintergrund, Flächen, Rahmen) stehen unter `dunkel` bzw. `ultra-dark`/`dark` in `acss-farben.mjs`. Top-Bar, Footer, Seiten-Hero, Startseiten-Hero und die Illustrationen bleiben hell gerechnet (ACSS „Force light selectors“).

**Übernehmen:** `node wordpress/etch/build.mjs` schreibt `dist/daten/acss-farben.json`; nach dem Kopieren `golfplatz/acss-colors` mit `aus_datei: true` aufrufen.

### Birdiebook und Platz & Bahnen

Umsetzung des [Konzepts](konzept-birdiebook.md), Stand 2026-09-26.

- **Seite `/platz/`:**
  - Seitenkopf mit „Birdiebook öffnen“ und „Zur Scorekarte“.
  - Birdiebook.
  - Platzbeschreibung mit Course & Slope Rating (Komponente Rating).
  - Alle 18 Bahnen als Karten (Etch-Loop `gp-bahnen`).
  - Scorekarte mit Out/In/Gesamt (Komponente Scorekarte).
- **Vollbild `/platz/birdiebook/`:**
  - Template `page-birdiebook`: schmale App-Leiste mit Kurzname, Platzstatus-Ampel und „Platz & Bahnen ✕“; kein Menü, kein Footer.
  - Web-App-Manifest (`/?golfplatz_manifest=1`) für „Zum Home-Bildschirm“; Themenfarbe aus ACSS.
  - Die Icons kommen aus dem Website-Icon, das noch **nicht gesetzt** ist (Design › Website-Informationen, mind. 512 × 512 px).
- **Etch:**
  - Komponente „Birdiebook“ (Generator `wordpress/etch/birdiebook.mjs`) aus DWC Slider Wrapper (240) und DWC Slider (241).
  - Die Slides erzeugt der Loop `gp-bahnen` (`wordpress/etch/loops.mjs`: `spielbahn`, sortiert nach `bahn_nummer`).
  - Felder im Loop: `{item.metabox.<feld>}`, Link: `{item.permalink.relative}`.
- **Live-Modul `mu-plugins/golfplatz-birdiebook.php`:**
  - Daten je Bahn als `{item.golfplatz.…}`: `plan` (Par, Verlauf, Fahnenlage, Hindernisse, Bild), `status` (Platz- oder Abschlagsperre, Wintergrün, frei), `fahne`, `entfernungen`.
  - Scorekarte und Rating als `{options.golfplatz.platz.…}`.
  - Das Markup (Grafik, Status, Liste, Tabellen) steht in den Etch-Komponenten.
  - Skript: Abschlag-Wahl (merkt sich die Wahl), Direktlink `#bahn-7`, zuletzt gesehene Bahn, Vor/Zurück, Nummernleiste, Ansage „Bahn 7 von 18“ für Screenreader, deutsche Slide-Beschriftungen. Nutzt `window.SplideComponent.ready()` von EtchSliderPro, sonst das native Karussell.
- **Neue Felder an `spielbahn`** (Abschnitt „Birdiebook“):
  - `bahn_richtung` (gerade, Dogleg links/rechts)
  - `gruen_tiefe`
  - `bahn_grafik_hoch` (Bild hochkant)
  - `bahn_hindernisse`: klonbare Gruppe aus `bezeichnung`, `art` (Bunker, Wasser, Aus, Bäume, Marker), `seite` und `bis_gruenmitte`.
  - Alle Felder sind noch leer, der Club liefert die Werte. Ohne Werte zeigt die Grafik nur Abschlag, Fairway und Grün.
- **ACSS:** Linkfarbe `link-color` = `var(--primary-dark)`, Hover `var(--primary-hover)`. Der Standard `var(--primary)` hatte nur 4,33:1.
- **Geprüft** (Handy 375 px und Desktop 1280 px, Hell und Dunkel):
  - Direktlink, Abschlag Rot (Länge und Par Damen), Nummernsprung, Weiter/Zurück, Fortsetzen der letzten Bahn.
  - Ausgeblendete Bahnen sind per Tab nicht erreichbar.
  - Kontrast-Audit ohne Fund auf `/`, `/platz/` und `/platz/birdiebook/`.
  - Die Hindernis-Darstellung wurde mit Testwerten an Bahn 7 geprüft; die Testwerte sind wieder entfernt.
- **Offen:**
  - Entfernungen, Grüntiefe, Verlauf und Grafiken vom Club einpflegen.
  - Website-Icon setzen.
  - Bahnsperren einzeln (`sperr_bereich` = `bahn_1` … `bahn_18`) nur nach Absprache.
  - Offline-Nutzung (Service Worker) ist nicht umgesetzt.
  - Der Prototyp hat das Birdiebook noch nicht.
  - Die Testseite „Slider-Test“ (ID 254, Entwurf) kann gelöscht werden.

### Greenfee & Preise

- **Seite `/greenfee/`:**
  - Seitenkopf.
  - Preise als kompakte **Matrix** wie auf dreibaeumen.de: je Tarif eine Zeile, Spalten Mo–Sa und So/Feiertag, Beträge mit €-Zeichen („80 €“). Varianten wie „mit DGV-Ausweis „R““ stehen als Unterzeile unter ihrem Tarif. Das gilt für „Greenfee <Jahr>“ (mit Fußnote), „Greenfee bei Turnieren“ und die Kooperationen.
  - „Leihgeräte“ haben keine Tage und bleiben eine Liste; die Einheit steht in der Leistung („E-Buggy 18 Loch“, „Trolley pro Runde“), rechts nur der Preis.
  - Die Wochentage liest die Website aus „Gültig an“: „Mo–Fr“, „Sa, So, Feiertag“, „freitags“, „mittwochs, freitags“, „täglich“, „werktags“. Unterzeile wird ein Tarif, dessen Name mit einem anderen Tarif beginnt. Tarife mit gleichem Namen und anderer Einheit bleiben getrennt (Offenes Turnier 18/9 Loch). Nicht angebotene Tage zeigen „–“.
  - Auf dem Handy scrollt die Matrix waagerecht, die Tarif-Spalte bleibt stehen.
  - Daneben der Kasten „Gut zu wissen“: Hinweise plus Ruhetag-Hinweis.
  - „Als Gast spielen“, Öffnungszeiten aller Bereiche.
  - „Greenfee für Mitglieder unserer Partnerclubs“ mit Einleitung und Tabelle.
- **Daten:**
  - Preise aus dem Beitragstyp `preis`, gruppiert nach `preiskategorie`, Reihenfolge über „Reihenfolge“ (menu_order).
  - „Auf Anfrage“ gilt, wenn `preis_auf_anfrage` an oder der Betrag leer ist.
  - Aufbereitet von `golfplatz-preise.php`, angezeigt mit der Etch-Komponente Preistabelle.
- **Neue Clubdaten-Felder** (Tab „Gäste & Systeme“): `greenfee_hinweise` (klonbar, „Gut zu wissen“), `greenfee_fussnote`, `kooperationen_hinweis`. Werte von dreibaeumen.de übernommen.
- **Twilight:** Die Regel steht in den Clubdaten (Tab „Gäste & Systeme“: `twilight_regel`, `twilight_stunden`), der Ort in „Kontakt & Anfahrt“ (`club_geo`, Breite/Länge, gesetzt auf Hückeswagen 51.145, 7.344).
  - Unter der Greenfee-Matrix steht „Twilight: Täglich bei Start ab drei Stunden vor Sonnenuntergang. Heute ab 16:18 Uhr (Sonnenuntergang 19:18 Uhr).“
  - Den Sonnenuntergang berechnet `golfplatz-preise.php` mit `date_sun_info()` für den Platz, Daten unter `{options.golfplatz.twilight.regel|hat_zeit|ab|sonnenuntergang}`. Der Google-Link aus dem Original ist damit überflüssig.
  - Ohne Stunden oder Koordinaten erscheint nur die Regel.
  - Bei einem Seitencache auf der Live-Seite die Greenfee-Seite höchstens einen Tag cachen, sonst stimmt die Uhrzeit nicht.
- **Hinweis zum Import:** Beim Übernehmen der drei Felder am 2026-09-27 hat `import-settings` alle Clubdaten aus dem Repository neu geschrieben. Im Admin geänderte Clubdaten wären dabei überschrieben worden. Seitdem kann `import-settings` mit `felder` gezielt einzelne Felder übernehmen.
- Mitgliedschaftspreise (Kategorie „Mitgliedschaft“) stehen auf `/mitgliedschaft/` (siehe unten).

### Turniere aus PC CADDIE

Stand 2026-09-28, `mu-plugins/golfplatz-turniere.php`.

- **Quelle:** die öffentlichen Seiten von PC CADDIE://online, die der Club schon auf dreibaeumen.de einbindet: Turnierkalender `pccaddie.net/clubs/<kennung>/app.php?cat=ts_calendar` (kommende Turniere) und Ergebnisliste `…?cat=ts_resultlist` (gespielte, 2024 bis heute). Club-Kennung **0494538** in den Clubdaten (`pccaddie_code`, Tab Gäste & Systeme). Gelesen werden die Tabellenzeilen `tr.pcco-xcal-list-item` (`data-id`, `data-kat`, `<time datetime>`, Anmeldeschluss, Teilnehmer, freie Plätze, Spielform, Löcher, Handicap-relevant, Links). Ändert PC CADDIE den Aufbau, meldet der Abgleich „Keine Turniere gelesen“ und lässt die Daten stehen.
- **Abgleich:** stündlich per WP-Cron (`golfplatz_pcc_sync`), von Hand unter **Turniere (PC CADDIE) → PC-CADDIE-Abgleich** oder per MCP `golfplatz/turniere-sync`. Je Turnier ein Eintrag `turnier` (Schlüssel = PC-CADDIE-Kennung); nur Geändertes wird geschrieben. Kommende Turniere, die aus dem Kalender verschwinden, gelten als abgesagt und kommen in den Papierkorb.
- **GOLFHOCHZEHN-Partnerclubs** (seit 2026-09-28): Clubdaten → Gäste & Systeme → **Partnerclubs** (klonbar: `name`, `kurzname`, `pccaddie_code`, `website`, `kalender_link`). Mit PC-CADDIE-Kennung werden die kommenden Turniere des Partners stündlich mit abgeglichen (Ergebnisse nur für den eigenen Club); ohne Kennung erscheint nur ein Link auf den Kalender des Clubs. Einige Clubs haben den Standardkalender abgeschaltet – dann liest der Abgleich die Ansicht `ts_calendar_turn_only`. Einträge `turnier` tragen `turnier_club` und `turnier_club_name`.
  - NRW-Mitglieder von GOLFHOCHZEHN (golfhochzehn.de/clubs, 16 Clubs, davon 10 in NRW): Dreibäumen (0494538), Velbert – Gut Kuhlendahl (0494503), Marienfeld (0494449), Gut Berge Gevelsberg/Wetter (0494529), Schloss Haag (0494523), Schwarze Heide Bottrop-Kirchhellen (0494412), Varmert (0494483), Golfpark Renneshof (0494587), Land-Golf-Club Schloss Moyland (0494439, der Verein – nicht die Betreiber-GmbH 0494594), Siegen-Olpe (kein PC CADDIE, PDF-Wettspielkalender). Außerhalb NRW: Thülsfelder Talsperre, Gifhorn, Gut Brettberg Lohne, Hainmühlen, Oberneuland, Verden.
  - Kategorien vergibt jeder Club anders (Dreibäumen D/H/S/J/C…, Schloss Haag DAM/HER/SEN, Varmert keine). Der Abgleich vereinheitlicht die Kürzel und erkennt Damen, Herren, Senioren (auch AK 50/65) und Jugend zusätzlich am Turniernamen. „Club“ gibt es nur beim eigenen Club.
  - Anzeige: **Turnierkalender** (Liste) und **Ergebnisse** nur für den Heimatclub. Heimatclub und Partnerclubs erscheinen zusammen in der **Platzbelegung** (Etch-Komponente „Platzbelegung“, Abschnitt `#platzbelegung`): Tabelle Tag × Club über 4 Wochen ab Montag der laufenden Woche – **erste Spalte der Heimatclub** (Kopf in Clubrot), dann die Partnerclubs –, Zelle = Turniere des Tages (Uhrzeit, Name, Löcher, gelb) oder „frei“; Wochenenden und heute hervorgehoben, Clubnamen im Kopf verlinken auf die Club-Website, Blättern per `?ab=JJJJ-MM-TT` (nie vor die laufende Woche). Gedacht zur Planung eines Besuchs – ob der Platz ganz oder nur zeitweise belegt ist, steht nicht in PC CADDIE. Daten `{options.golfplatz.turniere.belegung}`. Tabelle als CSS-Grid mit Tabellen-Rollen, erste Spalte beim seitlichen Scrollen fest.
  - Erster Abgleich: 9 Clubs, 147 kommende Turniere (Dreibäumen 28, Velbert 25, Schloss Haag 23, Gut Berge 18, Marienfeld 14, Schwarze Heide 13, Varmert 13, Schloss Moyland 10, Renneshof 3).
- **Nicht übernommen:** Namen aus Melde-, Start- und Ergebnislisten (Datenschutz) – nur Links dorthin. Anmelden, Abmelden und Ergebnisse bleiben bei PC CADDIE (Login dort).
- **Anzeige** auf `/turniere/`: Etch-Komponenten **Turnierkalender** (kommende Turniere nach Monat, Filter `?kategorie=d|h|s|j|c`, „Heute auf dem Platz“, Anmeldeschluss, freie Plätze bzw. „ausgebucht“, Buttons Anmelden, Ausschreibung, Details) und **Turnierergebnisse** (gespielte Turniere eines Jahres, `?jahr=2025`, Link „Ergebnisliste“). Daten `{options.golfplatz.turniere}` (`monate[]`, `filter[]`, `gespielt[]`, `jahre[]`, `heute[]`, `naechste[]` für die Startseite).
- **Erster Abgleich** 2026-09-28: 287 Turniere (28 kommende, 259 gespielte 2024–2026); zweiter Lauf ohne Änderung. Geprüft auf golfplatz.local, 1280 px und 375 px.
- **Offen:** mit PC CADDIE bzw. dem Club klären, ob das Auslesen der HTML-Seiten in Ordnung ist oder eine offizielle Schnittstelle genutzt werden soll; „Nächste Turniere“ auf der Startseite (Daten liegen unter `naechste[]`). Kategorien G, E, V von PC CADDIE sind nicht beschriftet und werden ignoriert.

### Mannschaften & Ligaspiele

**Ligaspiele vom Golfverband NRW** (seit 2026-09-28, `mu-plugins/golfplatz-liga-sync.php`):

- **Quelle:** gvnrw.liga.golf lädt seine Tabellen von `https://gvnrw-backend.liga.golf` (GraphQL, ohne Anmeldung): `findLeagues(year)` (Wettbewerbe und Ligen), `findLeagueResult` (Tabelle einer Liga: Spieltage mit Datum, Werte je Team, `homeTeam` = Gastgeber), `findTeamResult(teamId)` (je Spieltag der Austragungsort mit vollem Clubnamen). Die Schnittstelle ist **nicht offiziell dokumentiert**; beim Golfverband NRW klären, ob der tägliche Abruf in Ordnung ist.
- **Suche:** alle Ligen eines Jahres nach Teams mit dem Suchbegriff aus den Clubdaten (`verband_suchbegriff`, „Dreibäumen“), rund 250 Abrufe. Das Ergebnis steht je Jahr in der Option `golfplatz_liga_teams`; danach werden nur noch die bekannten Teams abgefragt (etwa 25 Abrufe).
- **Zuordnung:** Mannschaft = Wettbewerb ohne Jahr + Teamname (`mannschaft_verband_wettbewerb`, `mannschaft_verband_team`; „Dreibäumen 1“ gilt wie „Dreibäumen“). Die Jugendliga zählt über alle Stufen als ein Wettbewerb „Jugendliga“. Fehlt die Mannschaft, legt der Abgleich sie an (Titel z. B. „AK50 Herren“, „AK65/2 Herren“, „Jugend 1“, „Willy-Schniewind-Preis“) – Titel, Reihenfolge und Foto danach frei änderbar.
- **Ligaspiele:** je Team und Spieltag ein Eintrag mit Verbands-Kennung (`gvnrw:<liga>:<team>:<spieltag>`). Übernommen werden Spieltag, Datum (ohne Uhrzeit, liefert der Verband nicht), Spielort, Heimspiel (Ort enthält den Suchbegriff), Ergebnis (Schläge über CR bzw. Brutto bei der Jugend), Tagesplatzierung aus der Spalte „Punkte“, Saison, Liga und Link zur Tabelle. Spieltage ohne Ort und Ergebnis (Team spielt nicht) entfallen. Spielberichte hängen am Ligaspiel und bleiben erhalten; ein Spiel wird nur geschrieben, wenn sich etwas geändert hat.
- **Gastclubs:** Jeder fremde Austragungsort landet unter Mannschaften → Gastclubs. Die Website dort einmal eintragen, dann ist der Spielort auf der Website verlinkt. Die Schnittstelle liefert keine Websites. Stand 2026-09-28: 54 Gastclubs aus 2023–2026, 52 Websites eingetragen (jede per Abruf mit passendem Seitentitel oder per Websuche belegt). Offen: **Golfclub Bonn-Godesberg in Wachtberg** (gcbg.de steht zum Verkauf) und **Gut Köbbinghof** (keine eindeutige Adresse gefunden). Vergleich der Namen ohne HTML-Entities („&“ speichert WordPress als „&amp;amp;“).
- **Erster Abgleich** 2026-09-28: 13 Mannschaften (DGL Damen/Herren, Willy-Schniewind-Preis, AK30 Damen/Herren, AK50 Damen, AK50/1 und /2 Herren, AK65 Damen, AK65/1 und /2 Herren, Jugend 1 und 2), 218 Ligaspiele 2023–2026. Teams mit Zusatz „(abgemeldet …)“ bekommen keine Spiele (AK65 Damen 2023). Ein zweiter Lauf ändert nichts (geprüft für 2023, 2024, 2026).
- **Ablauf:** täglich per WP-Cron (`golfplatz_liga_sync`, 5:30 Uhr) für die laufende Saison; von Hand unter **Mannschaften → Verband-Abgleich** (Saison wählen, optional „alle Ligen neu durchsuchen“, z. B. bei einer neuen Mannschaft) oder per MCP `golfplatz/liga-sync` (`jahr`, `suche`). Protokoll der letzten 20 Läufe auf derselben Seite.
- **Anzeige:** Übersicht mit Saison-Auswahl (`/mannschaften/?saison=2025#ligaspiele`, Standard: laufendes Jahr), Mannschaftsseite je Saison ein aufklappbarer Block mit Liga und Link „Tabelle beim Golfverband NRW“. Spiele von Mannschaften, die nicht veröffentlicht sind, erscheinen nirgends.
- **Die Platzhalter aus dem Prototyp** (10 Mannschaften, 50 Ligaspiele, 69 Spieler, 2 Berichte) liegen seit 2026-09-28 im Papierkorb; `build.mjs` erzeugt dafür keine Importdateien mehr.

Stand 2026-09-28, Aufbau wie im Prototyp.

- **Übersicht `/mannschaften/`** (Template `archive-mannschaft`): Seitenkopf mit Anzahl, Mannschaftskarten in fester Reihenfolge (Feld „Reihenfolge“/menu_order, dann Titel) mit Liga, Spielführer und nächstem Spiel bzw. „Saison beendet“. Darunter **„Alle Ligaspiele“ als Abschnitt `#ligaspiele`** (kommende und vergangene Spiele, Heimspiele hervorgehoben). Damit ist der offene Punkt „URL Alle Ligaspiele“ gelöst: keine eigene Seite `/mannschaften/ligaspiele/`, die mit den Mannschafts-URLs kollidieren würde.
- **Mannschaft `/mannschaften/<slug>/`** (Template `single-mannschaft`): Liga, Mannschaftsfoto (Beitragsbild, nur wenn gesetzt), Ligaspiele chronologisch, Spielberichte (nur wenn vorhanden), Spielführer und Kader. **Spieler erscheinen nur mit `spieler_einwilligung`**, auch der Spielführer.
- **Spielbericht `/spielberichte/<slug>/`** (Template `single-spielbericht`): Mannschaft, Spieltag und Datum über das Ligaspiel, Autor (WordPress-Benutzer), Spielort, Platzierung, Text aus dem Editor, Bilder aus `bericht_bilder`, Rücklink zur Mannschaft.
- **Daten:** `golfplatz-mannschaften.php` liefert `{options.golfplatz.mannschaften}` (`anzahl_text`, `liste[]`, `kommende[]`, `vergangene[]`), `{this.golfplatz.team}` und `{this.golfplatz.bericht}`. Ergebnis: Platzierung („1. Platz“), sonst `ligaspiel_ergebnis`. Ein Spiel gilt bis zum Ende seines Spieltags als kommend.
- **Etch:** Bausteine in `wordpress/etch/mannschaften.mjs`; Ligaspiel-Tabellen als CSS-Grid mit Tabellen-Rollen (`.match-table--grid`).
- **Beispieldaten** aus dem Prototyp (Platzhalter!): 10 Mannschaften, 69 Spieler (10 ohne Einwilligung, zum Prüfen des Filters), 50 Ligaspiele der Saison 2026, 2 Spielberichte. Import in dieser Reihenfolge: `golfplatz/import-content` mit `spieler`, `mannschaft`, `ligaspiel`, `spielbericht` – Verweise stehen in den Dateien als `{"@post": "<typ>:<slug>"}` und werden beim Import zur ID.
- **Geprüft** am 2026-09-28 auf golfplatz.local (1280 px und 375 px): Übersicht, Mannschaft AK50/1 Herren (6 von 7 Spielern mit Einwilligung), Spielbericht mit Rücklink, keine Konsolenfehler.
- **Offen:**
  - Echte Mannschaften, Spieler (mit Einwilligung), Ligaspiele und Berichte vom Club; die Platzhalter vorher löschen.
  - Startseite „Nächste Ligaspiele“ ist noch nicht eingebaut; die Daten liegen schon unter `{options.golfplatz.mannschaften.kommende}`.
  - Im Menü ist „Mannschaften“ auf Mannschafts- und Berichtsseiten nicht markiert (EMMP markiert nur exakt gleiche URLs).

### Mitgliedschaft `/mitgliedschaft/`

Stand 2026-09-28, Aufbau wie im Prototyp.

- **Modelle:** Etch-Komponente „Preiskarten“ (`wordpress/etch/preise.mjs`, Eigenschaften `kategorie` = `mitgliedschaft`, `ziel` = `#antrag`). Daten aus den Preisen der Kategorie „Mitgliedschaft“: `golfplatz-preise.php` liefert je Kategorie zusätzlich `karten[]` (Titel, Betrag bzw. „auf Anfrage“, Einheit, Zusatz, Aufnahmegebühr aus `preis_aufnahme`, Leistungen aus `preis_leistungen` – eine je Zeile –, Hervorhebung aus `preis_hervorheben` mit „Beliebt“). Button „Gespräch vereinbaren“ bei „auf Anfrage“, sonst „Anfragen“.
- **In drei Schritten:** Kennenlernen (Link Golfschule), persönliches Gespräch, Aufnahmeantrag.
- **Aufnahmeantrag** (`#antrag`): Ansprechpartner, Telefon und E-Mail aus den Clubdaten, Aufnahmeantrag als PDF (dreibaeumen.de), häufige Fragen als Akkordeon (`<details>`).
- **Offen:** Das Antragsformular aus dem Prototyp fehlt, weil kein Formular-Plugin installiert ist. Sobald eines feststeht, das Formular mit den Modellen aus `preis` und Datenschutzhinweis ergänzen. Das PDF liegt noch auf dreibaeumen.de, besser in die Mediathek übernehmen.
- **CSS:** `.price-card__button` setzt `--btn-width: 100%`, weil ACSS die Buttonbreite über diese Variable steuert und nach dem Golfplatz-Stylesheet lädt. Abstände in der Karte sind ausdrücklich gesetzt (ACSS setzt Überschriften- und Absatzabstände auf 0).

### Lochwettspiel (Turnierbaum)

Einmal im Jahr, Teams aus zwei Spielern, K.-o.-System. Jede Runde hat einen vorgeschriebenen Spielzeitraum. Stand 2026-09-28.

- **Pflege** (Menü „Lochwettspiel“, je Jahr ein Eintrag):
  - `lw_teams`: Teams in Reihenfolge der Setzliste. Ohne „Platz im Tableau“ setzt die Website nach Standard-Setzung (1 gegen 16, 8 gegen 9 …); die Freilose fallen auf die ersten Teams und nie zwei aufeinander. Mit Platz (1 … n) gilt die Auslosung.
  - `lw_runden`: Spielzeitraum je Runde (erste Zeile = 1. Runde). „Von“ leer = Tag nach dem Ende der Vorrunde. Name leer = Achtelfinale, Viertelfinale, Halbfinale, Finale bzw. „1. Runde“.
  - `lw_spiele`: je Spiel eine Zeile. In „Spiel und Sieger“ steht jedes Spiel zweimal, einmal je möglichem Sieger, z. B. „Spiel 11 (Finale): Koch / Richter gegen Becker / Wolf → Sieger: Koch / Richter“. Dazu Ergebnis („3 & 2“, „1 auf“, „19. Loch“, „kampflos“) und Datum. Der Sieger rückt von selbst weiter.
  - Die Liste füllt `golfplatz-lochwettspiel.php` (`rwmb_normalize_lw_spiele_field`) aus dem gespeicherten Stand. Spiele der nächsten Runde erscheinen, sobald die Ergebnisse davor gespeichert sind. Ein gespeicherter Eintrag, der nicht mehr passt (weil ein Ergebnis davor geändert wurde), bleibt mit „⚠ passt zu keinem Spiel mehr“ sichtbar.
  - Jedes Team hat eine feste `team_id` (Feld ausgeblendet, vergeben beim Speichern). Namen korrigieren oder Teams umsortieren lässt die Ergebnisse gültig. Kommt eine ID einmal nicht mit, übernimmt das Team die ID des bisherigen Teams mit denselben Spielern.
  - Kasten **„Stand“** über den Ergebnissen: Tabelle aller Paarungen (Spiel, Runde, beide Teams, Sieger mit ✔ und Ergebnis bzw. offen/Frist) sowie Ergebnisse, die zu keinem Spiel passen (Team hatte Freilos, ist ausgeschieden, Gegner steht noch nicht fest, doppelter Eintrag). Solche Einträge erscheinen nicht im Baum. Der Kasten zeigt den Stand beim Öffnen; nach dem Speichern neu laden.
  - Die Reihenfolge der Teams (Setzliste) bestimmt das Tableau – nach Beginn der ersten Runde nicht mehr ändern.
  - Die Ausschreibung steht im Beitragstext.
- **Daten:** `golfplatz-lochwettspiel.php` rechnet den Baum und liefert `{options.golfplatz.lochwettspiele}` (neuestes Jahr zusätzlich unter `key` = `aktuell`): `jahr`, `spielform`, `teams_text`, `status`, `hinweis`, `jahre[]`, `runden[]` (`name`, `zeitraum`, `mod`/`status`: läuft, Frist abgelaufen, abgeschlossen, beginnt am …; `lage`; `paare[].spiele[]` mit `label`, `mod`, `status`, `aria` und `seiten[]`), `sieger`.
- **Spielstatus:** gespielt, offen (bis Fristende), Frist abgelaufen (Frist vorbei, kein Ergebnis), wartet (Gegner steht noch nicht fest oder Runde beginnt später), Freilos. Status immer als Text, nie nur über Farbe.
- **Größe:** beliebig viele Teams (ab 2). Das Tableau wird auf die nächste Zweierpotenz aufgefüllt (Freilose). Der Turnierbaum zeigt höchstens 8 Spiele in der ersten Spalte (`GOLFPLATZ_LW_BAUM_MAX_SPIELE`), also ab dem Achtelfinale; bis 16 Teams ist das das ganze Turnier. Frühere Runden (1. Runde, 2. Runde …) stehen darüber als aufklappbare Rundenlisten mit allen Spielen im Raster; die laufende Runde ist aufgeklappt, in der Kopfzeile stehen Zeitraum, Status und „32 Spiele · 8 offen · 4 Freilose“. Im Admin stehen offene Spiele zuerst (Tabelle und Auswahl), gespielte sind eingeklappt.
- **Etch:** Komponente „Lochwettspiel“ (`wordpress/etch/lochwettspiel.mjs`, Eigenschaft `jahr`: `aktuell` oder Jahreszahl; im Template `{this.metabox.lw_jahr}`). Grafik: je Runde eine Spalte, Linien per CSS (`.bracket`), auf schmalen Bildschirmen seitlich scrollbar. Screenreader lesen je Spiel einen Satz („Spiel 3: … gegen …, Sieger … 3 & 2“).
- **Eingebunden:** `/turniere/` (Abschnitt `#lochwettspiel`, aktuelles Jahr) und Template `single-lochwettspiel`.
- **Geprüft** am 2026-09-28 mit einem Test-Turnier aus 64 Teams (1. Runde gespielt, 2. Runde halb): Rundenlisten, Baum ab Achtelfinale, Handy 375 px, Eingabemaske (96 Einträge, offene zuerst). Das Test-Turnier liegt im Papierkorb. Logik zusätzlich mit 17, 100 und 256 Teams. Außerdem Eingabemaske serverseitig gerendert (Paarungstabelle, Auswahl „Spiel und Sieger“ mit gespeicherten Werten vorausgewählt, `team_id` im Formular), Speichern simuliert (fehlende ID, umbenanntes Team, neues Team). Auf golfplatz.local mit den Beispielen 2026 (12 Teams, 4 Freilose, Finale läuft) und 2025 (8 Teams, beendet): Hell/Dunkel, 375 px und 1280/1440 px, keine Konsolenfehler. Die Baumlogik außerdem mit 1, 3 und 11 Teams und abgelaufenen Fristen.
- **Offen:** Die Beispiel-Teams sind erfunden; vor dem Livegang löschen (`/turniere/lochwettspiel/2025/`, `/2026/`). Rechte für die Spielleitung (eigene Rolle) nach Bedarf mit User Role Editor. Der Prototyp zeigt den Turnierbaum nicht.

### Fahnenpositionen

Jedes Grün hat **6 nummerierte Fahnenpositionen (1–6)**. Gesteckt wird eine Position für alle Grüns, einzelne Grüns können abweichen.

- **Steckpläne** auf der Einstellungsseite „Platzstatus“, Abschnitt „Fahnenpositionen“ (`pin_plaene`, klonbar):
  - Je Plan: `gueltig_ab` (Datum), `position` (1–6) für alle Grüns, `hinweis` und `ausnahmen` (klonbar: `bahn`, `position`).
  - Es gilt der neueste Plan, dessen Datum erreicht ist (ab 0 Uhr Ortszeit). Pläne für spätere Tage lassen sich vorab eintragen und erscheinen erst an ihrem Datum. Bis dahin bleibt der bisherige Plan sichtbar.
  - Gibt es keinen gültigen Plan, erscheinen keine Fahnenpositionen. Alte Pläne dürfen gelöscht werden.
  - Meta Box speichert das Datum in der Gruppe im Anzeigeformat („25.09.2026“), obwohl als Speicherformat „Y-m-d“ eingestellt ist. `golfplatz_datum_iso()` liest deshalb beide Formate, dazu Zeitstempel.
- **Einmalig je Grün** an der Spielbahn, Abschnitt „Pin-Positionen“ (`pin_positionen`, bis zu 6 Einträge): `position`, `tiefe` (vorne/Mitte/hinten), `seite` (links/Mitte/rechts), optional `meter` ab Grünanfang. Solange die Lage fehlt, zeigt die Website nur die Nummer, und die Fahne in der Birdiebook-Grafik steht in der Mitte des Grüns.
- **Ausgabe:**
  - Startseite, Platzstatus-Block: Standard und Ausnahmen sind Kacheln in der Reihe der Spielbedingungen, zum Beispiel „Fahnenposition · Position 2“ (grün) und „Bahn 6 · Position 4“ (rot getönt). Darunter steht nur ein Hinweis aus dem Steckplan bzw. die Lage der Position, falls gepflegt.
  - Hero: ein grüner Chip „Fahnen Position 2“ und je Ausnahme ein eigener roter Chip „Bahn 6: Fahne Position 4“.
  - Birdiebook: „Fahne: Position 5 · hinten rechts“ (ohne Datum, das Steckdatum wird nirgends angezeigt), dazu die Fahne an der gepflegten Stelle der Grafik.
- **Geprüft** am 2026-09-26 mit Testwerten, die danach wieder entfernt wurden:
  - Standard 3, Bahn 7 auf Position 5 mit Lage.
  - Pläne gestern, heute und morgen: Es gilt der Plan von heute.
  - Ohne heutigen Plan gilt der von gestern.
  - Liegt nur ein Plan in der Zukunft, erscheint nichts.
- **Offen:** Der Club trägt die Lage der 6 Positionen je Grün ein, zum Beispiel aus seinem Pin-Plan.

### Platzstatus als Etch-Komponente

Der große Platzstatus-Block der Startseite ist jetzt die Etch-Komponente „Platzstatus“ (`wordpress/etch/platzstatus.mjs`). Die Berechnung bleibt in `golfplatz-platzstatus.php`, die Daten liegen unter `{options.golfplatz.platzstatus.*}` (Filter `etch/dynamic_data/option`). Einzelheiten stehen in `wordpress/README.md`.

## Noch von Hand zu erledigen

1. ~~Einstellungsseiten anlegen~~ – erledigt am 2026-09-25 per MCP: `clubdaten` (mit Tabs) und `platzstatus` (Capability `edit_sperrungen`). Beide sind unter Meta Box → Einstellungsseiten bearbeitbar.
2. **Rechte für Sperrungen** (User Role Editor Pro): Der Beitragstyp `sperrung` hat eigene Capabilities. **Auch Administratoren sehen ihn erst, wenn sie diese Rechte haben.**
   - Rolle „Platzstatus“ anlegen mit `read`, `edit_sperrungen`, `edit_others_sperrungen`, `edit_published_sperrungen`, `publish_sperrungen`, `delete_sperrungen`, `delete_others_sperrungen`, `delete_published_sperrungen`, `read_private_sperrungen`
   - ~~Dieselben Rechte der Rolle „Administrator“ geben~~ – erledigt am 2026-09-26: `golfplatz-platzstatus.php` gibt Administratoren die Rechte von `sperrung` automatisch (sonst fehlten Menü „Sperrungen“ und Seite „Platzstatus“).
3. ~~Permalinks speichern~~ – erledigt per MCP (`golfplatz/flush-permalinks`).
4. **Taxonomie-Begriffe** anlegen (siehe oben).
5. **Mitglieder-Rolle:** Für den Mitgliederbereich eine Rolle „Mitglied“ (nur `read`) anlegen. Inhalte mit `nur_mitglieder` = an werden in Etch nur angemeldeten Benutzern gezeigt.

## Seiten und Templates per MCP

Seit 2026-09-25 legt ein Must-Use-Plugin zusätzliche MCP-Funktionen an. Damit werden Seiten, Etch-Templates und das CSS aus dem Repo übertragen. Ablauf und Stand: [wordpress/README.md](../wordpress/README.md).

## Prototyp → Etch

Der Prototyp unter [`prototype/`](../prototype/README.md) zeigt alle Seiten mit Beispielinhalten. Die Klassennamen (BEM) und die Token-Namen (ACSS) sind so gewählt, dass sich Markup und CSS direkt in Etch-Komponenten übernehmen lassen.

### Abfragen für die Etch-Templates

| Ausgabe | Abfrage |
| --- | --- |
| Platzstatus heute/morgen | `sperrung` mit `sperr_ende` > jetzt und `sperr_beginn` < Ende von morgen, sortiert nach `sperr_beginn`. Davor die Schnellsperre, falls `platz_gesperrt` an ist und `platz_gesperrt_bis` leer oder in der Zukunft liegt. Tagesgrenzen in der Zeitzone der Website. Seite nicht cachen oder Cache kurz halten. |
| Scorekarte, Bahnübersicht | `spielbahn`, sortiert nach `bahn_nummer` |
| Spielvorgaben | CR/Slope/Par aus `clubdaten` (Tab „Platz & Abschläge“). Umgesetzt: `golfplatz_spielvorgaben_etch()` in `golfplatz-birdiebook.php` liefert `{options.golfplatz.platz.spielvorgaben}` (Werte + Tabelle HI → Spielvorgabe), Komponenten SpielvorgabenRechner, SpielvorgabenTabellen und Verweis auf die eigene Seite `/platz/zaehlkarte/` (Komponente Zaehlkarte), Rechner-/Reiter-Skript im Footer |
| Mannschaftsseite | Kader/Spielführer aus den Feldern, nur Spieler mit `spieler_einwilligung`. Ligaspiele: `ligaspiel` mit `ligaspiel_mannschaft` = aktuelle Mannschaft. Berichte: `spielbericht` mit `bericht_ligaspiel` IN (IDs dieser Ligaspiele) |
| Ligaspiele gesamt | `ligaspiel`, sortiert nach `ligaspiel_termin`, geteilt in kommende und vergangene |
| Greenfee / Mitgliedschaft | `preis` nach `preiskategorie`, sortiert nach `menu_order` |
