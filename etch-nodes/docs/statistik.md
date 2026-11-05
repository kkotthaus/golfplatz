# Besucherstatistik (Burst Statistics)

**Standard für jede Website** (seit 2026-11-05): Besucherzahlen mit **Burst Statistics** (`burst-statistics`, freie Version von wordpress.org) – ohne Cookies, ohne Banner, die Daten bleiben in der Datenbank der Website. Kein Google Analytics und kein zweites Statistik-Werkzeug. Matomo nur, wo ein Projekt ausdrücklich tiefere Auswertungen braucht (dann in der Projekt-Doku begründet).

## Was es zeigt

- Besucher und Seitenaufrufe je Tag, Woche, Monat, mit Vergleich zum Zeitraum davor
- meistbesuchte Seiten, Absprungrate, Verweildauer, Scrolltiefe
- Herkunft (Suchmaschine, direkt, andere Websites, soziale Netzwerke – soweit der Browser die Herkunft mitschickt)
- Geräte, Browser, Betriebssystem
- Ziele (Klicks), siehe unten

Nur in der Pro-Version: Kampagnen-Links (UTM), Länder und Städte, Klicks auf externe Links, eigene Ereignisse, automatisches Löschen alter Daten, mehr als drei Ziele. Braucht ein Projekt davon etwas, vorher klären, ob Pro gekauft wird.

## Grundeinstellungen

Setzt das Server-Werkzeug beim Einrichten der Website (Blueprint, Skript `statistik-einrichten.php`, einmal je Website). Danach gehören die Einstellungen der Website.

| Einstellung | Wert | Warum |
| --- | --- | --- |
| Wie Besucher erkannt werden | **Cookieless** (`private_mode`) | Kein Cookie, kein Speicher im Browser, kein Fingerabdruck. Der Server bildet aus IP-Adresse, Browserkennung und einem täglich neuen Zufallswert einen Hash; die IP-Adresse wird nicht gespeichert. Wiederkehrende Besucher über mehrere Tage sind dadurch nicht erkennbar. **Nicht** auf „Cookie“ oder „Cookieless fingerprint“ umstellen – beides bräuchte eine Einwilligung. |
| „Do Not Track“ beachten | an | |
| Ausgeschlossene Rollen | Administrator, Redakteur, Autor, Mitarbeiter | Eigene Aufrufe zählen nicht mit. Eigene Rollen eines Projekts, die nur intern arbeiten, ergänzen. |
| Anonyme Nutzungsdaten an den Hersteller | aus | Keine Daten an Dritte. |
| KI-Schnittstelle (Abilities API) | aus | |
| Nur wichtige Hinweise im Backend | an | Keine Werbung und Tipps für Kunden. |
| Warnungen per Mail (Messausfall, Spitzen) | aus | Zahlen stehen im Backend. |
| Search Console verbinden | nicht | Läuft über einen Dienst des Herstellers; Search Console direkt bei Google nutzen. |

**Gezählt wird nur live.** Lokal und auf Staging hängt das Umgebungs-mu-plugin des Server-Werkzeugs das Skript ab. Zum Testen außerhalb von live `define( 'WPS_STATISTIK_TEST', true );` in die `wp-config.php` und danach wieder entfernen. Testaufrufe im Browser ohne Anmeldung (angemeldete Mitarbeiter zählen nicht).

## Ziele

Die freie Version erlaubt **drei aktive Ziele**. Standard (Typ „Klick“, ganze Website):

| Ziel | Selektor |
| --- | --- |
| Anruf (Telefonnummer angeklickt) | `a[href^="tel:"]` |
| E-Mail (Adresse angeklickt) | `a[href^="mailto:"]` |
| PDF geöffnet | `a[href*=".pdf"]` |

Ein Projekt darf ein Ziel tauschen, wenn ihm etwas anderes wichtiger ist (z. B. Klick auf „Bewerben“); in der Projekt-Doku festhalten. **Formulare** zählt WS Form selbst (gespeicherte Einsendungen) – dafür kein Ziel. Ziele vom Typ „Hook“ (serverseitig) funktionieren ohne Cookie nicht zuverlässig.

## Datenschutzerklärung

Jede Website mit Burst nennt die Statistik in der Datenschutzerklärung. Vorlage (an das Projekt anpassen, vom Kunden bzw. seiner Beratung prüfen lassen – keine Rechtsberatung):

> **Besucherstatistik**
>
> Wir werten aus, wie unsere Website genutzt wird, um zu sehen, welche Inhalte gefragt sind, und das Angebot zu verbessern. Dafür nutzen wir das Plugin Burst Statistics. Die Auswertung läuft vollständig auf unserem eigenen Server; es werden keine Daten an den Hersteller oder andere Dritte übermittelt.
>
> Es werden keine Cookies gesetzt und keine Daten im Speicher Ihres Browsers abgelegt. Erfasst werden: die aufgerufene Seite, Zeitpunkt und Verweildauer, die Seite, von der Sie zu uns kommen, Browser, Betriebssystem und Gerätetyp sowie Klicks auf Telefonnummern, E-Mail-Adressen und PDF-Dateien. Um Aufrufe desselben Tages zusammenzufassen, wird aus Ihrer IP-Adresse, der Kennung Ihres Browsers und einem täglich wechselnden Zufallswert ein verschlüsselter Wert (Hash) gebildet. Die IP-Adresse selbst wird nicht gespeichert; ein Wiedererkennen an anderen Tagen ist nicht möglich. Hat Ihr Browser „Do Not Track“ eingeschaltet, wird Ihr Besuch nicht erfasst.
>
> Rechtsgrundlage ist unser berechtigtes Interesse an der Auswertung der Nutzung unserer Website (Art. 6 Abs. 1 lit. f DSGVO). Die Daten werden [Speicherdauer] gespeichert.

**Speicherdauer:** Die freie Version löscht alte Daten nicht selbst. Je Projekt eine Frist festlegen und eintragen; bis zu einer Lösung im Server-Werkzeug von Hand löschen (Einstellungen › Daten) bzw. „bis zur Löschung, da keine Personen erkennbar sind“ nur nach Prüfung durch die Beratung des Kunden.

## Einrichten und prüfen

- Neue Website: kommt mit dem Blueprint (Plugin in der Plugin-Liste, Schritt „Statistik“).
- Vorhandene Website: Plugin installieren, dann das Einrichtungs-Skript des Server-Werkzeugs per WP-CLI mit einem Administrator ausführen (`wp eval-file statistik-einrichten.php 0 --user=<admin>`; mit `1` als Probelauf); erneut setzen mit `--neu`. Absatz in der Datenschutzerklärung ergänzen, Technik-Doku und Handbuch der Website nachziehen.
- Live prüfen: Seite abgemeldet aufrufen, im Quelltext `burst.min.js`, keine Cookies (Entwicklerwerkzeuge › Anwendung); am nächsten Tag Zahlen im Backend (Statistik). Nach Updates von Burst einmal wiederholen.
- Im Handbuch der Website für den Kunden erklären: wo die Zahlen stehen, was die Ziele zählen, dass eigene Aufrufe nicht mitzählen und dass wiederkehrende Besucher nicht erkannt werden.
