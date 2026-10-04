# Farben und Barrierefreiheit

Farben und Farbregeln dieses Projekts. Sie stehen bewusst nicht in etch-nodes, sondern nur hier.

## Farben dieses Projekts

Clubfarben zentral in `wordpress/etch/acss-farben.mjs`, übernehmen per MCP `golfplatz/acss-colors`.
- Blueprint: Primary `#2E6B4E` (Golf-Grün), Secondary `#2C5A85` (Blau).
- Zuordnung im Design: `--secondary-ultra-light` Seitenhintergrund, `--base-ultra-light` Flächen, `--base-light` Rahmen, `--base-semi-light` gedämpfter Text und Rahmen von Eingabefeldern, `--base-semi-dark` Nebentext, `--info` Wintergrüns.
- `--primary` nur als Fläche mit weißer Schrift (`--white`) oder für große Schrift/Grafik; grüne Schrift und Links immer `--primary-dark`. `--secondary` nie als Schrift auf Grün oder dunklen Flächen – dort `--white` bzw. `--secondary-light`.
- Ausnahmen von „nur ACSS-Farben“: Abschlagfarben (`--tee-*`) und Illustrationen (Bahngrafik, Platzhalter-Verläufe).
- Immer hell gerechnete Bereiche in `immerHell` in `wordpress/etch/acss-farben.mjs` eintragen.
- Kontrastprüfung: `node wordpress/etch/kontrast.mjs` – muss ohne Fehler durchlaufen.

## Farben nur aus Automatic.css

- Im CSS nur ACSS-Farbvariablen verwenden: `--primary*`, `--secondary*`, `--base*`, `--success*`, `--warning*`, `--danger*`, `--info*`, `--white`, `--black`. Keine eigenen Farbvariablen, keine Hex-Werte für Oberflächenfarben. Das gilt auch für EMMP-Variablen und Illustrationen, soweit möglich (Mischungen per `color-mix()` aus ACSS-Farben).
- Ausnahmen nur für Farben mit fester, fachlicher Bedeutung, die nicht zum Design gehören. Diese als eigene Tokens dokumentieren.
- **Die Werte werden ausschließlich in ACSS gepflegt.** Empfohlen: die Palette als Datei im Repo halten und per MCP-Funktion in die ACSS-Einstellungen schreiben, sodass Repo und ACSS nicht auseinanderlaufen.
- Rollen im Design festlegen und dokumentieren, z. B.: Seitenhintergrund, Flächen, Rahmen, gedämpfter Text, Nebentext. CSS nach Rolle schreiben, nicht nach Farbton.

## ACSS v4: Eigenheiten der Farbeinstellungen

- ACSS v4 rechnet mit **OKLCH-Schlüsseln je Abstufung**: `<farbe>[-<stufe>]-l|c|h-oklch` (dazu `-alt`-Varianten). `color-<farbe>` allein ändert nichts.
- `option-palette-unify-*-lightness` muss **aus** sein, sonst überschreibt ACSS die gesetzten Helligkeiten.
- Beim Schreiben per Code: Hex-Farben über die ACSS-API, alle anderen Farbschlüssel direkt in die Datenbank-Einstellungen, weil die API jeden Schlüssel mit `color-` als Hex-Farbe validiert. Nur Farbschlüssel freigeben (`color-*`, `option-*-clr`, OKLCH-Schlüssel, Farbschema, Button-Textfarben). ACSS danach das CSS neu erzeugen lassen.

## Hell/Dunkel über ACSS

- Ohne Wahl folgt die Seite dem Gerät (ACSS-Website-Schema „light dark“, Standard aus etch-nodes). Der Umschalter setzt die ACSS-Klasse `scheme--light` bzw. `scheme--dark` am `<html>`. ACSS rechnet jede Farbvariable mit `light-dark()` und tauscht im dunklen Schema die Abstufungen (`-ultra-light` ↔ `-ultra-dark`, `-light` ↔ `-dark`, `-semi-light` ↔ `-semi-dark`); `--white` wird dunkel, `--black` hell.
- Deshalb **nie davon ausgehen, dass `--white` weiß ist.** Beispiel: Karte `--white`, Text auf `--primary` in `--white` – beides tauscht korrekt.
- Bereiche, die immer hell gerechnet werden sollen (dunkle Markenflächen, feste Illustrationen, Footer), über ACSS „Force light selectors“ eintragen – nicht mit eigenen Farben lösen. Feste Werte ohne Umschaltung liefern die Referenz-Tokens `--<farbe>[-<stufe>]-ref`.
- Umschalter: Knopf als Etch-Komponente (z. B. `[data-scheme-toggle]`), Skripte per PHP-Hook. Die gespeicherte Wahl (localStorage `golfplatz-farbschema`: `hell`/`dunkel`) **schon im `<head>`** setzen, damit nicht kurz das andere Schema aufblitzt. Entspricht die Wahl dem Gerät, wird sie gelöscht. Logo und Grautöne für dunkle Flächen schalten per Media-Query **und** Klasse um (`html:not(.scheme--light)` bzw. `html.scheme--dark`), nicht nur über `.scheme--dark`. Zugänglicher Name aus dem sichtbaren Text (WCAG 2.5.3), nicht abweichend per `aria-label`.

## Kontrast (WCAG 2.1 AA)

- Text 4,5:1, große Schrift und Grafik/Rahmen von Bedienelementen 3:1 – in **beiden** Farbschemata.
- Typische Fallen der ACSS-Standards (knapp unter 4,5:1 je nach Palette):
  - Linkfarbe `var(--primary)` → stattdessen `--primary-dark`, Hover entsprechend.
  - Button-Textfarbe `-ultra-light` → `var(--white)` (`btn-primary-text`, `btn-secondary-text` samt Hover).
  - Mittlere Hauptfarbe als Schrift auf hellem Grund oder als Schrift auf anderen Farbflächen.
- Status (offen, gesperrt, abgesagt …) **nie nur über Farbe**, immer auch als Text.
- Danger-Farbe von der Markenfarbe unterscheidbar halten, wenn die Marke selbst rot ist.
- Alle verwendeten Kombinationen (Vordergrund, Hintergrund, Mindestwert, Verwendung) in einem **Prüfskript** im Projekt pflegen, das die OKLCH-Werte so rechnet wie ACSS (inkl. Tausch im dunklen Schema) und bei Fehlern mit Exit-Code 1 endet. Nach jeder Farbänderung ausführen; zusätzlich Browser-Audit bei 375, 768, 1280 und 1920 px.
- Ausgeblendete Elemente (z. B. inaktive Slides) dürfen per Tab nicht erreichbar sein.
- Links im Fließtext (`p`, `li`, `dd` ohne Klasse) sind unterstrichen, nicht nur farbig (WCAG 1.4.1).
- Klickflächen mindestens 24 px (WCAG 2.5.8), z. B. Telefon/E-Mail im Footer mit Innenabstand.
- Kein `aria-label`, das vom sichtbaren Text abweicht (WCAG 2.5.3) – auch nicht am Logo-Link.
- Nebentext auf getönten Zeilen (z. B. Heimspiel in der Ligatabelle) mit `--base-semi-dark`, nicht `--base-semi-light`.
