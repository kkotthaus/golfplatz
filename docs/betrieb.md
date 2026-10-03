# Betrieb: PHP, MCP, Veröffentlichen

## PHP als WPCodeBox-Snippets

- Eigene PHP-Erweiterungen laufen als Snippets in WPCodeBox (ein Ordner je Projekt, eine Datei im Repo = ein Snippet). Ausführung „Always“, Einfügepunkt **Root**. Kein paralleler Einsatz derselben Datei als mu-plugin (doppelte Funktionen).
- Das Repository ist die Quelle. Abgleich per MCP-Funktion, die über die WPCodeBox-Abilities (`wpcodebox/*`) arbeitet, damit deren Freigaben, Rechte und Protokoll greifen. Erkennung des Snippets z. B. über Schlagwörter (Projekt-Kürzel + Dateiname), Titel aus „Plugin Name“ im Dateikopf.
- WPCodeBox führt den Code per **`eval()` in einem try-Block** aus. Daraus folgt:
  - **Kein `const` auf oberster Ebene**, sondern `define( 'NAME', … )`. `const` ist im Block ein Syntaxfehler, und WPCodeBox schaltet das Snippet ab.
  - **Kein `__DIR__`.** Mitgelieferte Dateien liegen in einem festen Ordner unter `wp-content/<prefix>/`, erreichbar über eine Konstante.
  - Funktionen gelten erst ab ihrer Definition. Beim Laden nichts aufrufen, was weiter unten definiert ist; Hooks sind unkritisch.
  - Jede Datei beginnt mit `defined( 'ABSPATH' ) || exit;`, weil sie auch als Build-Kopie im öffentlichen Ordner liegen kann.
- Snippets mit Fehler schaltet WPCodeBox selbst ab. Prüfen über `wpcodebox/list-errored-snippets` bzw. `enabled` in `wpcodebox/list-snippets`. Das Feld `error` bleibt nach einer Korrektur stehen, bis das Snippet einmal in der Oberfläche gespeichert wird – maßgeblich ist `enabled`.
- Schreibende WPCodeBox-MCP-Funktionen (Create/Update/Enable/Disable Snippet) müssen in den WPCodeBox-MCP-Einstellungen freigegeben sein; die Freigaben stehen in der Datenbank (`wpcb_mcp_enabled`, `wpcb_mcp_allowed_tools`).

## Eigene MCP-Funktionen

- Registrierung über die Abilities API (`wp_register_ability`, eigene Kategorie), ausgeliefert über den MCP Adapter.
- **Eng begrenzen:** nur Administratoren (`manage_options`), nur freigegebene Beitragstypen (z. B. `page`, `wp_template`, `wp_block` und ausdrücklich gelistete eigene Typen), feste Etch-REST-Routen intern aufrufen. **Keine allgemeine REST-Weiterleitung** – zu breite Angriffsfläche.
- **Nur in der Entwicklung aktiv:** Funktionen nur registrieren, wenn `wp_get_environment_type()` `local` oder `development` meldet. Bewusste Nutzung live nur über eine Konstante in der `wp-config.php` (z. B. `<PREFIX>_MCP_LIVE`).
- Lesende und schreibende Funktionen trennen (readonly-Kennzeichnung). Schreibende Imports wiederholbar machen (anlegen oder per Schlüsselfeld aktualisieren, nichts löschen).
- Typischer Satz: Inhalte auflisten/lesen, Seite/Template/Komponente/Stylesheet speichern, Sync aus dem Build-Ordner (Reihenfolge: Loops → Komponenten → Templates und Seiten mit `__REF_<Key>__`-Ersetzung → Stylesheet), Import von Inhalten und Einstellungen aus JSON, ACSS-Einstellungen, Permalinks, Startseite, Snippet-Sync.

## Ablauf Build → WordPress

1. Generator bauen (schreibt `dist/`).
2. `dist/` nach `wp-content/<prefix>/` kopieren (lokal) bzw. per SSH/tar hochladen (Staging). Dateien, die nicht mehr gebaut werden, melden statt löschen. Dateien mit Inhalt, der nicht öffentlich abrufbar sein soll, als `.php` mit Schutzzeile ablegen.
3. Snippet-Sync, dann Inhalts-Sync ausführen – per MCP oder per WP-CLI (`wp eval`) mit denselben Funktionen.

## Veröffentlichen mit Duplicator Pro

Die Live-Seite entsteht als Kopie der lokalen Seite (Dateien und Datenbank). WPCodeBox-Snippets (Tabellen `wp_wpcb_*`), `wp-content/<prefix>/`, Einstellungen und Beitrags-IDs kommen mit; Duplicator ersetzt die Adresse in der ganzen Datenbank (auch serialisiert).

**Vorher (lokal):**

1. Build, Kopie nach `wp-content/<prefix>/`, Sync aller Inhalte und Snippets.
2. In WPCodeBox prüfen, dass alle Projekt-Snippets `enabled` sind.
3. Beispiel- und Testinhalte entfernen; Test- und Beispielseiten nicht veröffentlicht lassen (sonst in der Sitemap).
4. SEOPress prüfen: Sitemap-Inhalte, noindex für Autoren-, Datums-, Kategorie- und Schlagwort-Archive und interne Beitragstypen.
5. Paket erstellen; `wp-content/<prefix>` und `wp_wpcb_*` nicht ausschließen. Im Installer die neue Adresse mit `https://` angeben.
6. Bei Ersatz einer bestehenden Seite: alte Seite vollständig sichern, zu übernehmende Inhalte klären, zuerst auf einer Test-Subdomain installieren, alte Adressen per 301 (SEOPress-Weiterleitungen) umleiten.

**Nachher (live):**

1. WPCodeBox-MCP-Endpunkt ausschalten bzw. schreibende Werkzeuge entziehen.
2. MCP Adapter deaktivieren, wenn live keine KI-Werkzeuge gebraucht werden.
3. Prüfen, dass live **nicht** `WP_ENVIRONMENT_TYPE` = `local`/`development` gesetzt ist.
4. Anwendungspasswörter für den MCP-Zugriff widerrufen; Benutzer und Admin-Passwörter prüfen (kommen aus der lokalen Seite mit).
5. „Suchmaschinen davon abhalten …“ ausschalten.
6. SMTP einrichten und testen, Admin-E-Mail auf eine echte Adresse setzen.
7. Lizenzen aller Pro-Plugins auf der neuen Domain aktivieren.
8. ACSS-Einstellungen einmal speichern (CSS neu erzeugen) und Permalinks speichern.
9. Installer-Dateien von Duplicator löschen.
10. Cron: `define( 'DISABLE_WP_CRON', true );` und beim Hoster einen Cronjob alle 5–15 Minuten auf `/wp-cron.php`.
11. Sitemap in der Google Search Console einreichen, in den ersten Wochen das 404-Protokoll ansehen.

**Nach dem Livegang nie wieder per Duplicator veröffentlichen** – das überschreibt die komplette Live-Datenbank mit allem, was live gepflegt wurde. Spätere Änderungen gezielt übertragen: PHP im WPCodeBox-Editor bzw. per WPCodeBox Cloud oder kurzzeitig freigeschaltetem Snippet-Sync; Etch-Inhalte per Sync oder im Editor.

## Caching

Seiten mit Daten, die relativ zu „jetzt“ berechnet werden (Status, Fristen, „heute geöffnet“), vom Seitencache ausnehmen oder sehr kurz cachen (z. B. 5 Minuten); tagesabhängige Seiten höchstens einen Tag.
