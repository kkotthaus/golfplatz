# Farben und Barrierefreiheit

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

- Hell ist Standard. Dunkel = ACSS-Klasse `scheme--dark` am `<html>`. ACSS rechnet jede Farbvariable mit `light-dark()` und tauscht im dunklen Schema die Abstufungen (`-ultra-light` ↔ `-ultra-dark`, `-light` ↔ `-dark`, `-semi-light` ↔ `-semi-dark`); `--white` wird dunkel, `--black` hell.
- Deshalb **nie davon ausgehen, dass `--white` weiß ist.** Beispiel: Karte `--white`, Text auf `--primary` in `--white` – beides tauscht korrekt.
- Bereiche, die immer hell gerechnet werden sollen (dunkle Markenflächen, feste Illustrationen, Footer), über ACSS „Force light selectors“ eintragen – nicht mit eigenen Farben lösen. Feste Werte ohne Umschaltung liefern die Referenz-Tokens `--<farbe>[-<stufe>]-ref`.
- Umschalter: Knopf als Etch-Komponente (z. B. `[data-scheme-toggle]`), Skripte per PHP-Hook. Die gespeicherte Wahl (localStorage) **schon im `<head>`** setzen, damit die Seite nicht hell aufblitzt. Zugänglicher Name aus dem sichtbaren Text (WCAG 2.5.3), nicht abweichend per `aria-label`.

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
