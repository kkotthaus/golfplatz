# Sicherheit und Datenschutz (Härtung)

**Standard für jede Website.** Jede Website wird gehärtet, bevor echte Daten darauf liegen, und von außen geprüft – auf Staging vor dem Umzug und **auf live nach jedem Umzug erneut**. Was davon ein Projekt braucht (z. B. Fotoschutz nur bei Fotos von Personen), steht in der Projekt-Doku. Platzhalter wie in [konventionen.md](konventionen.md): `<prefix>`, `<PREFIX>`.

Der Grundsatz: Ein fremder Besucher ohne Anmeldung erfährt nichts über Benutzer, Technik und Personen, was er nicht sehen soll – und es gibt keine Angriffsfläche, die sich mit wenig Aufwand finden lässt.

## Snippet `<prefix>-schutz.php`

Ein Snippet je Website (WPCodeBox, gehört auf die Live-Seite). Es setzt um:

**Benutzernamen nicht verraten**

- Autorenseiten (`/?author=<n>`, `/author/<name>/`) leiten auf die Startseite; oEmbed-Daten ohne Autor (`oembed_response_data`).
- Einheitliche Fehlermeldung bei der Anmeldung (Filter `authenticate`, Priorität 100: jeder Fehler wird zu „Benutzername oder Passwort falsch“).
- „Passwort vergessen“ verrät nicht, ob es einen Zugang gibt (`lostpassword_post`: bei unbekanntem Namen dieselbe Bestätigung wie bei bekanntem, `checkemail=confirm`).

**Anmeldung bremsen**

- Sperre nach 5 Fehlversuchen in 15 Minuten je IP-Adresse; jede weitere Sperre am selben Tag doppelt so lang, höchstens 24 Stunden. Gilt auch für Anwendungspasswörter.
- Gespeichert nur als Transient mit einem Hash der IP-Adresse (`hash_hmac` mit `wp_salt()`), nie die Adresse selbst; läuft von allein ab.
- Die Grenzen als Konstanten (`<PREFIX>_SCHUTZ_VERSUCHE` usw.), damit ein Projekt sie anpassen kann.

**Schnittstellen schließen**

- XML-RPC aus: `xmlrpc_enabled` → false, `xmlrpc_methods` leer, bei `XMLRPC_REQUEST` sofort 403; zusätzlich in der `.htaccess` gesperrt.
- REST-API ohne Anmeldung nur für ausdrücklich freigegebene Namensräume (`rest_authentication_errors`, Liste `<PREFIX>_SCHUTZ_REST_OFFEN`, z. B. eigene Apps, `ws-form/v1`, `oh-my-etch/v1`). Alles andere – auch `wp/v2` mit Benutzern, Medien, eigenen Beitragstypen und deren Feldern – antwortet ohne Anmeldung mit 401. **Neue öffentliche Schnittstelle → in die Liste aufnehmen**, sonst funktioniert sie für Besucher nicht.
- Eigene Schnittstellen mit Personendaten antworten mit `Cache-Control: no-store, private`.

**Keine Technik verraten**

- `wp_generator`, RSD-, REST- und Kurzlinks (`wp_shortlink_wp_head`, `wp_shortlink_header`) entfernen; `header_remove( 'X-Powered-By' )` (PHP-Version).
- **Keine Versionsnummern:** `?ver=<Version>` an Skripten und Stilen im Frontend durch eine Kennung ersetzen (`style_loader_src`/`script_loader_src`, z. B. gekürzter `hash_hmac` der Version) – Browser laden nach Updates weiter neu, Erkennungsdienste wie Wappalyzer sehen aber keine Versionen mehr. Zeitstempel bleiben.
- Dass die Seite WordPress ist (Pfade `wp-content/…`, Klassen), wird **nicht** verborgen: Das lässt sich nicht zuverlässig machen, bricht Plugins und Updates und schützt nicht. Entscheidend sind Versionsnummern, aktuelle Updates und die Härtung.
- `readme.html`, `license.txt`, `wp-config-sample.php` und Daten-Ordner des Builds, die die Website nur vom Server liest (z. B. `wp-content/<prefix>/daten/`), in der `.htaccess` sperren.

**Kopfzeilen** (bei `init` mit Priorität 0, nur im Frontend und für Anmeldeseiten)

| Kopfzeile | Wert |
| --- | --- |
| `Strict-Transport-Security` | `max-age=31536000` (nur bei HTTPS) |
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `SAMEORIGIN` (Etch-Editor und Customizer brauchen das eigene Einbetten) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), payment=(), usb=()` – eine Seite, die etwas davon braucht (z. B. Kamera einer App), erlaubt es sich selbst mit `=(self)` |

**`.htaccess`-Block**

- Das Snippet schreibt einen eigenen, markierten Block (`insert_with_markers`, Marker z. B. `<PREFIX>-Schutz`) bei `admin_init`, wenn er fehlt oder sich die Fassung geändert hat (Option mit der Fassungsnummer). So steht er nach einem Umzug wieder richtig da, sobald sich ein Administrator anmeldet.
- Inhalt: die gesperrten Dateien und Ordner (`RewriteRule … - [F,L]`), dazu die Kopfzeilen per `mod_headers` für Dateien, die nicht durch PHP laufen.
- **OpenLiteSpeed** liest Rewrite-Regeln aus der `.htaccess` (nach kurzer Zeit, ohne Neustart), übernimmt aber `Header` daraus nicht für Bilder und andere statische Dateien. Diese Kopfzeilen live in der Konfiguration des virtuellen Hosts setzen (root, macht der Nutzer). Auf nginx (lokal) gilt die `.htaccess` gar nicht – dort nur die PHP-Kopfzeilen prüfen.

## Plugin `bausteine-schutz` (in Erprobung)

Eigenes Plugin aus dem Repo bausteine (`plugins/bausteine-schutz/`, Beschreibung in dessen README). Es soll das Snippet `<prefix>-schutz.php` **und** Security Ninja ablösen, ohne fremde Dienste und ohne die Schwächen von Security Ninja:

- Das Snippet steckt vollständig darin. Projektspezifisches (offene REST-Namensräume, gesperrte Daten-Ordner, Namensräume mit `no-store`) steht in den Einstellungen (Einstellungen → Schutz) oder als Konstante in der `wp-config.php` (`BAUSTEINE_SCHUTZ_REST_OFFEN`, `BAUSTEINE_SCHUTZ_FREI`).
- Nach Fehlversuchen wird nur die Anmeldung gesperrt, nie die Website; jeder Versuch zählt einmal.
- Firewall ohne Sperre von Anfragen ohne Browser-Kennung (MCP, `curl`, Apps). Modus `auto`: lokal und Staging nur beobachten – nach einem Duplicator-Import muss nichts abgeschaltet werden.
- Ausgesperrt? Keine Hintertür, sondern zwei Notausgänge: auf der Sperrmeldung ein Link zum Entsperren per Mail an die Admin-Adresse (einmal, 15 Minuten, nur für die gesperrte Adresse) und der Notschalter `define( 'BAUSTEINE_SCHUTZ_AUS', true );` in der `wp-config.php` (Sperren und Firewall aus, die Härtung bleibt; danach wieder entfernen). Dazu wie bisher: Sperre läuft von selbst ab, `wp bausteine-schutz entsperren`, Freigabeliste.
- Virenscanner: Läuft auf dem Server ClamAV (clamd), nutzt das Plugin ihn über dessen Socket mit – Uploads werden beim Hochladen geprüft und bei einem Fund abgewiesen, die tägliche Prüfung scannt neue und geänderte Dateien und meldet Funde als Lücke. Ohne ClamAV bleibt es aus; Hinweis nur live (beim Hoster nachfragen). Der Scanner ersetzt keine Updates und keine Prüfsummen, er findet nur bekannte Schadsoftware.
- Prüfungen von innen (Dateien gegen die Prüfsummen von wordpress.org, Skripte in den Uploads, Konfiguration, Zugänge, Cron) ergänzen `tools/sicherheit-pruefen.mjs`, das weiter von außen prüft.

**Stand:** bis zur Freigabe gilt der bisherige Standard (Snippet + Security Ninja auf live). Einführung: erst auf einer Live-Seite **neben** Security Ninja, nach einigen Wochen ohne Probleme Security Ninja entfernen und das Snippet abschalten (die Prüfung meldet ein noch laufendes Snippet). Gleichzeitig mit dem Snippet zählen Fehlversuche doppelt mit.

## Security Ninja (nur live)

Auf jeder Live-Seite läuft zusätzlich **Security Ninja Premium** (`security-ninja-premium`). Es ergänzt das Snippet `<prefix>-schutz.php` (Firewall, Sperrlisten, 404-Schutz), ersetzt es aber nicht: Die Sperre des Snippets greift zuerst und gilt auch dort, wo Security Ninja nicht läuft.

- **Nur live** (`production` in der Plugin-Liste). Lokal und auf Staging aus: Die Firewall blockt Anfragen ohne Browser-Kennung, also auch den MCP-Zugang und Prüfungen mit `curl`. Kommt eine Live-Kopie per Duplicator auf lokal oder Staging, Security Ninja dort deaktivieren.
- **Datenschutz**: „Cloud“ und „Global Ban Network“ aus. Sonst gehen die IP-Adressen der Besucher an den Dienst des Herstellers, und die Datenschutzerklärung braucht einen Hinweis.
- **Anmeldung**: In Version 5.305 wird der Schalter „Wiederholte Anmeldeversuche blockieren“ nicht abgefragt, und jeder Fehlversuch zählt doppelt. Mit den Grundwerten ist nach 3 Fehlversuchen die **ganze Website** für die IP-Adresse gesperrt – bei gemeinsamer Adresse (Büro, Verein, Familie) für alle, auch für Apps. Deshalb:
  - Firewall › „Verhindern des Zugriffs gesperrter IPs auf die Website“ **aus** (gesperrte Adressen können sich nicht anmelden, sehen aber die Website)
  - Sperrregel **10** Fehlversuche in 5 Minuten (= 5 echte), Sperre **30 Minuten**
  - „Wiederholte Anmeldeversuche blockieren“ aus
- **Firewall** (Angriffsmuster, 404-Schutz, Anfragen ohne Browser-Kennung) mit den Grundwerten lassen. Besucher, Suchmaschinen und Apps mit Browser-Kennung sind nicht betroffen. Prüfwerkzeuge schicken eine Browser-Kennung mit.
- **Zugänge nicht sperren**: Hat eine Live-Seite einen MCP-Zugang oder eigene Apps und Schnittstellen, nach dem Einrichten prüfen, dass Security Ninja sie durchlässt.
- Die Einstellungen setzt der Nutzer im Backend; im Verlauf der Projekt-Doku festhalten. **Nach jedem Update von Security Ninja** die Sperre nach Fehlversuchen von einer fremden IP-Adresse erneut prüfen (nur die Anmeldung gesperrt, Website und Apps antworten).

## Fotos von Personen

Pflicht, wenn die Website Fotos von Personen zeigt, die nicht selbst entschieden haben, dass sie öffentlich sein sollen (Kinder, Mitglieder, Patienten, Mitarbeiter). Snippet `<prefix>-fotos.php` o. ä.:

1. **Nur kleine Fassungen öffentlich**: beim Hochladen das Original in eine geschützte Ablage **außerhalb des Webverzeichnisses** verschieben, öffentlich nur eine Fassung bis z. B. 1024 Pixel; Zwischengrößen neu erzeugen.
2. **Aufnahmedaten entfernen** (GPS, Kamera, Zeit, Name): bei JPEG die Segmente APP1 (EXIF, XMP) und APP13 (IPTC), bei WebP die Blöcke `EXIF`/`XMP ` samt Flags im `VP8X`, bei PNG `eXIf`, `tEXt`, `zTXt`, `iTXt`, `tIME`. Am Anhang vermerken, mit welcher Fassung bereinigt wurde, damit eine verbesserte Fassung vorhandene Fotos nachreinigt.
3. **Zufällige Dateinamen** ohne Namen der Person.
4. **Speichern erschweren**: kein Kontextmenü und kein Ziehen auf den geschützten Bildern (Hinweis im Handbuch: das hält nur Gelegenheitsnutzer ab, ein Bildschirmfoto bleibt möglich).
5. **Nicht in die Bildersuche**: `robots.txt` (`robots_txt`-Filter) sperrt die Fotos über ein Muster im Dateinamen.
6. **Hotlink-Schutz**: eigener `.htaccess`-Block mit Prüfung des Referers auf die eigene Adresse; wird neu geschrieben, wenn sich die Adresse ändert (Umzug).
7. Fotos ohne Einwilligung bzw. ohne Freigabe gar nicht erst öffentlich (nicht in der Mediathek, nur über eine geprüfte Ausgabe für Angemeldete).

Fotos echter Personen nicht in KI-Dienste hochladen oder per KI verändern (siehe [KI-Kennzeichnung](konventionen.md#ki-kennzeichnung)).

## Geschützte Ablage für Dokumente

Einwilligungen, Anträge, Ausweise, Originalfotos: nie in die Mediathek, sondern in einen Ordner **neben** dem Webverzeichnis (Rechte 700, Pfad per Konstante in der `wp-config.php` änderbar). Abruf nur angemeldet über eine eigene Ausgabe mit Prüfung von Recht und Nonce/Token. Der Ordner gehört in die Server-Sicherung – Duplicator-Pakete enthalten ihn nicht, beim Umzug von Hand mitnehmen (verschlüsselt, nie ins Repo).

## Rollen und Zugänge

- Administrator nur für den Betreuer; Kunden bekommen Redakteur bzw. eigene Rollen mit dem nötigsten Recht.
- Persönliche Zugänge, keine geteilten; Datei-Editor im Backend aus (`DISALLOW_FILE_EDIT`).
- Anwendungspasswörter nur, solange sie gebraucht werden.

## Prüfen

Jedes Projekt hat ein Prüfwerkzeug `tools/sicherheit-pruefen.mjs` (Node, ohne Abhängigkeiten), Vorlage aus einem vorhandenen Projekt übernehmen und an Adresse, Prefix und Schnittstellen anpassen.

```
node tools/sicherheit-pruefen.mjs <Adresse> [--innen=<SSH-Alias>] [--bericht]
```

- **Von außen** wie ein fremder Besucher: HTTPS und Weiterleitung, Kopfzeilen, Benutzernamen (Autorenseiten, REST, oEmbed, Anmeldung, „Passwort vergessen“), Sperre nach Fehlversuchen, XML-RPC, REST ohne Anmeldung (Benutzer, Medien, jeder eigene Beitragstyp), Dateien, die nicht öffentlich sein dürfen (`readme.html`, `wp-config.php`-Kopien, Sicherungen, `.git`, Logs, Daten-Ordner, Verzeichnislisten), geschützte Ablage, Fotos (Größe, Aufnahmedaten, `robots.txt`, Hotlink), Apps und eigene Schnittstellen.
- **Von innen** (`--innen`, nur lesend per WP-CLI über SSH): Rollen und Zahl der Administratoren (ohne Namen), Konfiguration (`WP_ENVIRONMENT_TYPE`, `DISALLOW_FILE_EDIT`, Debug aus), Lage der geschützten Ablage, Stand der Fotos, Plugins mit Updates, Fristen.
- Verändert nichts. Ausnahme: wenige Anmeldeversuche mit einem erfundenen Benutzernamen – das sperrt die eigene IP-Adresse für die Anmeldung. Mit `--innen` hebt das Werkzeug diese eigene, frische Sperre am Ende auf; ohne (live) 15 Minuten warten.
- Ergebnis je Prüfung: in Ordnung, Hinweis, Lücke. Ziel: **0 Lücken**. Fehlercode 1 bei Lücken.
- `--bericht` schreibt `docs/sicherheit/pruefung-<Datum>-<Host>.md` ins Projekt. **Berichte enthalten keine Benutzernamen, Passwörter oder Personendaten.**
- `docs/sicherheit/README.md` im Projekt: wie geprüft wird, Tabelle der Berichte, Verlauf (Befund → Maßnahme, mit Datum), offene Hinweise und Entscheidungen (z. B. Staging mit oder ohne HTTP-Passwort).

**Wann prüfen:**

| Anlass | Wo |
| --- | --- |
| bevor echte Daten auf die Website kommen | Staging |
| nach Änderungen an Schutz, Fotos, Apps, Schnittstellen, Rollen | Staging |
| vor dem Umzug auf live | Staging |
| **nach jedem Umzug auf live** (Pflicht, vor der Freigabe) | live, von außen |
| regelmäßig: mindestens einmal im Monat und nach Updates von WordPress oder Plugins | live |

Live prüft Claude nur von außen und nur auf ausdrückliche Anweisung (kein Zugriff auf Live-Server); `--innen` nur dort, wo das Projekt einen SSH-Zugang für Claude vorsieht.

## Dokumentieren

- Handbuch im Backend: eigenes Kapitel **„Datenschutz und Sicherheit“** – was geschützt ist und wie (Einwilligung, Fotos, Dokumente, Zugänge und Rechte, Anmeldung und Schutz der Website, Apps), Fristen als Tabelle, regelmäßige Prüfung, was alle Nutzer tun können, **Verlauf der Maßnahmen mit Datum**. Jede neue Maßnahme dort nachtragen.
- Technik-Doku (`docs/technik.md`): Snippets, Konstanten, `.htaccess`-Blöcke.
- Wo Personendaten verarbeitet werden: Entwürfe für das Verzeichnis der Verarbeitungstätigkeiten (Verantwortlicher ist der Kunde) und die technischen und organisatorischen Maßnahmen (TOM) in `docs/datenschutz/`, offene Punkte markiert, vom Kunden bzw. seiner Beratung prüfen lassen. Keine Rechtsberatung.
- Die Umzugsanleitung des Projekts enthält die Prüfung vor und nach dem Umzug als eigene Schritte (siehe [betrieb.md](betrieb.md#veröffentlichen-mit-duplicator-pro)).
