# Betrieb: PHP, MCP, Veröffentlichen

## PHP als WPCodeBox-Snippets

- Eigene PHP-Erweiterungen laufen als Snippets in WPCodeBox (ein Ordner je Projekt, eine Datei im Repo = ein Snippet). Ausführung „Always“, Einfügepunkt **Root**.
- **Snippets werden immer in WPCodeBox angelegt** – in jeder Umgebung und von Anfang an, auch lokal in der Entwicklung. Keine Lader, kein mu-plugin und kein `require` der Repo-Dateien, auch nicht übergangsweise (doppelte Funktionen, und ein Fehler legt sonst die ganze Seite lahm statt nur das Snippet).
- **Einzige Ausnahme: ein Umgebungs-mu-plugin aus dem Server-Werkzeug** (nicht aus dem Projekt). Es regelt nur, was je Umgebung (`WP_ENVIRONMENT_TYPE`) anders ist – z. B. Hinweis auf die Umgebung, noindex, Mails abfangen, Cache und Tracking außerhalb von live, keine automatischen Updates –, enthält keinen Projekt-Code und lädt keine Repo-Dateien. Installiert und aktualisiert wird es vom Werkzeug, nie von Hand.
- **Einrichtung eines Projekts:** In der Entwicklung die WPCodeBox-MCP-Werkzeuge freigeben (List/Get, Create Folder, Create/Update/Enable/Disable Snippet). Das Snippet mit den eigenen MCP-Funktionen (`<prefix>-mcp.php`, enthält den Snippet-Sync) einmal direkt anlegen – per `wpcodebox/create-snippet` oder in der WPCodeBox-Oberfläche – und einschalten; alle weiteren Snippets legt dann der Snippet-Sync an. Neue Datei im Repo = neues Snippet per Sync, nie von Hand ins Dateisystem.
- Das Repository ist die Quelle. Abgleich per MCP-Funktion, die über die WPCodeBox-Abilities (`wpcodebox/*`) arbeitet, damit deren Freigaben, Rechte und Protokoll greifen. Erkennung des Snippets z. B. über Schlagwörter (Projekt-Kürzel + Dateiname), Titel aus „Plugin Name“ im Dateikopf.
- WPCodeBox führt den Code per **`eval()` in einem try-Block** aus. Daraus folgt:
  - **Kein `const` auf oberster Ebene**, sondern `define( 'NAME', … )`. `const` ist im Block ein Syntaxfehler, und WPCodeBox schaltet das Snippet ab.
  - **Kein `__DIR__`.** Mitgelieferte Dateien liegen in einem festen Ordner unter `wp-content/<prefix>/`, erreichbar über eine Konstante.
  - Funktionen gelten erst ab ihrer Definition. Beim Laden nichts aufrufen, was weiter unten definiert ist; Hooks sind unkritisch.
  - Jede Datei beginnt mit `defined( 'ABSPATH' ) || exit;`, weil sie auch als Build-Kopie im öffentlichen Ordner liegen kann.
- Snippets mit Fehler schaltet WPCodeBox selbst ab. Prüfen über `wpcodebox/list-errored-snippets` bzw. `enabled` in `wpcodebox/list-snippets`. Das Feld `error` bleibt nach einer Korrektur stehen, bis das Snippet einmal in der Oberfläche gespeichert wird – maßgeblich ist `enabled`.
- Schreibende WPCodeBox-MCP-Funktionen (Create/Update/Enable/Disable Snippet) müssen in den WPCodeBox-MCP-Einstellungen freigegeben sein; die Freigaben stehen in der Datenbank (`wpcb_mcp_enabled`, `wpcb_mcp_allowed_tools`).

### WPCodeBox-Einstellungen prüfen (bei jedem neuen Projekt)

Vor dem ersten Snippet und bei der ersten Sitzung in einem Projekt prüfen, und bei Abweichung melden. Freigaben sind Sicherheitseinstellungen: der Nutzer setzt sie in der Oberfläche (WPCodeBox › Einstellungen › MCP, speichern), nicht per Datenbank oder Skript.

| Prüfpunkt | Soll in der Entwicklung | Soll live |
| --- | --- | --- |
| WPCodeBox 2 | aktiv | aktiv |
| MCP (`wpcb_mcp_enabled`) | an | aus |
| Lesende Werkzeuge: List Snippets, Get Snippet, List Folders, List Tags, Get Revisions, List Errored Snippets, Search Snippets | frei | – |
| Schreibende Werkzeuge: Create Folder, Create Snippet, Update Snippet, Enable Snippet, Disable Snippet | frei | entzogen (nur kurzzeitig für eine Übertragung) |
| **Delete Snippet, Run Snippet** | **nicht frei** | nicht frei |
| Lader/mu-plugin, das Repo-Dateien lädt | keiner (erlaubt nur das Umgebungs-mu-plugin des Server-Werkzeugs) | keiner (ebenso) |
| Projekt-Snippets | im Ordner des Projekts, alle `enabled`, `list-errored-snippets` leer | ebenso |

So geht die Prüfung per MCP: `mcp-adapter-discover-abilities` listet nur freigegebene Werkzeuge. Erwartet sind genau `wpcodebox/list-snippets`, `get-snippet`, `list-folders`, `list-tags`, `get-revisions`, `list-errored-snippets`, `search-snippets`, `create-folder`, `create-snippet`, `update-snippet`, `enable-snippet`, `disable-snippet` – `wpcodebox/delete-snippet` und `wpcodebox/run-snippet` dürfen nicht auftauchen. Danach `wpcodebox/list-folders`, `list-snippets` und `list-errored-snippets` sowie `wp-content/mu-plugins/` (kein Lader) ansehen.

Hinweis: Update Snippet auf eingeschaltete Snippets braucht zusätzlich die Freigabe von Enable Snippet (sonst schlägt die Aktualisierung fehl).

## Eigene MCP-Funktionen

- Registrierung über die Abilities API (`wp_register_ability`, eigene Kategorie), ausgeliefert über den MCP Adapter.
- **Eng begrenzen:** nur Administratoren (`manage_options`), nur freigegebene Beitragstypen (z. B. `page`, `wp_template`, `wp_block` und ausdrücklich gelistete eigene Typen), feste Etch-REST-Routen intern aufrufen. **Keine allgemeine REST-Weiterleitung** – zu breite Angriffsfläche.
- **Nur in der Entwicklung aktiv:** Funktionen nur registrieren, wenn `wp_get_environment_type()` `local` oder `development` meldet. Bewusste Nutzung live nur über eine Konstante in der `wp-config.php` (z. B. `<PREFIX>_MCP_LIVE`).
- Lesende und schreibende Funktionen trennen (readonly-Kennzeichnung). Schreibende Imports wiederholbar machen (anlegen oder per Schlüsselfeld aktualisieren, nichts löschen).
- Typischer Satz: Inhalte auflisten/lesen, Seite/Template/Komponente/Stylesheet speichern, Sync aus dem Build-Ordner (Reihenfolge: Loops → Komponenten → Templates und Seiten mit `__REF_<Key>__`-Ersetzung → Stylesheet), Import von Inhalten und Einstellungen aus JSON, ACSS-Einstellungen, Permalinks, Startseite, Snippet-Sync.
- **Selbstsigniertes Zertifikat lokal:** `<prefix>-mcp.php` schaltet nur bei `wp_get_environment_type()` = `local` die Zertifikatsprüfung für Aufrufe an die eigene Adresse ab (Filter `http_request_args`, Host = Host von `home_url()`). Sonst scheitern Plugins, die die eigene Seite aufrufen (z. B. das SEOPress-Audit).

## SEOPress-Funktionen (MCP)

SEOPress (ab 10.3) registriert eigene Funktionen `seopress/*` in der Abilities API und bringt zusätzlich einen eigenen MCP-Server mit (`/wp-json/seopress/mcp/v1`, OAuth oder Anwendungspasswort).

- **Einschalten:** SEO › Erweitert › Erweitert › Abilities API › „Expose abilities to AI agents and external tools“ (Option `seopress_advanced_option_name`, Schlüssel `seopress_advanced_abilities_api_rest`). Standard ist aus.
- **In der Entwicklung an.** Die Funktionen erscheinen dann über den MCP Adapter im vorhandenen MCP-Server des Projekts (`mcp-adapter-discover-abilities`). Den eigenen SEOPress-Server lokal nicht einrichten.
- **Live aus.** Wenn live doch gebraucht: nur über den eigenen SEOPress-Server mit OAuth und Zugang „nur lesen“, als eigener Server je Website im Projekt eingetragen (nicht mit `--scope user`, wie es das SEOPress-Backend vorschlägt).
- **Nur lesen und prüfen**, nicht schreiben: Titel, Beschreibungen und strukturierte Daten kommen aus dem Repo (Generator → Build → Snippet oder Sync). Nützlich sind:
  - `seopress/get-technical-audit` – Startseite: Titel, Canonical, Indexierbarkeit, robots.txt, Sitemap, Open Graph, Sicherheits-Header, llms.txt. Ergebnis wird zwischengespeichert (Transient `seopress_pro_homepage_audit`).
  - `seopress/list-posts-noindexed`, `seopress/get-sitemap-settings`, `seopress/list-redirections` (auch erkannte 404).
  - `seopress/list-posts-missing-metadata` zeigt nur, wo in SEOPress selbst nichts eingetragen ist. Kommen Titel und Beschreibung per Filter aus dem Repo, stehen dort alle Seiten – kein Fehler.
  - `seopress/analyze-post-content` ruft die Seite selbst auf. Lokal über MCP scheitert das an der Zahl der PHP-Prozesse von Local; per WP-CLI (`wp_get_ability( … )->execute()`) geht es.
- Die KI-Funktionen (`generate-*`) brauchen einen KI-Anbieter in SEOPress und werden nicht genutzt.

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
4. SEOPress prüfen: Sitemap-Inhalte, noindex für Autoren-, Datums-, Kategorie- und Schlagwort-Archive und interne Beitragstypen (per MCP: `seopress/get-sitemap-settings`, `seopress/list-posts-noindexed`, `seopress/get-technical-audit`).
5. Paket erstellen; `wp-content/<prefix>` und `wp_wpcb_*` nicht ausschließen. Im Installer die neue Adresse mit `https://` angeben.
6. Bei Ersatz einer bestehenden Seite: alte Seite vollständig sichern, zu übernehmende Inhalte klären, zuerst auf einer Test-Subdomain installieren, alte Adressen per 301 (SEOPress-Weiterleitungen) umleiten.

**Nachher (live):**

1. WPCodeBox-MCP-Endpunkt ausschalten bzw. schreibende Werkzeuge entziehen.
2. MCP Adapter deaktivieren, wenn live keine KI-Werkzeuge gebraucht werden. SEOPress: Abilities API ausschalten (siehe [SEOPress-Funktionen](#seopress-funktionen-mcp)) und „Agent Readiness“ (Pro) prüfen – veröffentlicht sonst u. a. `/.well-known/mcp.json` und Agenten-Endpunkte.
3. Prüfen, dass live **nicht** `WP_ENVIRONMENT_TYPE` = `local`/`development` gesetzt ist.
4. Anwendungspasswörter für den MCP-Zugriff widerrufen; Benutzer und Admin-Passwörter prüfen (kommen aus der lokalen Seite mit).
5. „Suchmaschinen davon abhalten …“ ausschalten.
6. SMTP einrichten und testen, Admin-E-Mail auf eine echte Adresse setzen.
7. Lizenzen aller Pro-Plugins auf der neuen Domain aktivieren.
8. ACSS-Einstellungen einmal speichern (CSS neu erzeugen) und Permalinks speichern.
9. Installer-Dateien von Duplicator löschen.
10. Cron: `define( 'DISABLE_WP_CRON', true );` und beim Hoster einen Cronjob alle 5–15 Minuten auf `/wp-cron.php`.
11. Sitemap in der Google Search Console einreichen, in den ersten Wochen das 404-Protokoll ansehen.
12. SEOPress-Audit live laufen lassen (in der Oberfläche oder, falls freigegeben, per `seopress/get-technical-audit`): Startseite indexierbar, robots.txt erreichbar, Sicherheits-Header.

**Nach dem Livegang nie wieder per Duplicator veröffentlichen** – das überschreibt die komplette Live-Datenbank mit allem, was live gepflegt wurde. Spätere Änderungen gezielt übertragen: PHP im WPCodeBox-Editor bzw. per WPCodeBox Cloud oder kurzzeitig freigeschaltetem Snippet-Sync; Etch-Inhalte per Sync oder im Editor.

## Caching

Seiten mit Daten, die relativ zu „jetzt“ berechnet werden (Status, Fristen, „heute geöffnet“), vom Seitencache ausnehmen oder sehr kurz cachen (z. B. 5 Minuten); tagesabhängige Seiten höchstens einen Tag.
