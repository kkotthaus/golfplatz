# Etch-Nodes – Entwicklungsumgebung

Referenz für den technischen Stack, auf dem die Nodes in diesem Repository aufbauen. Wird als Kontext für die Weiterentwicklung mit Claude genutzt. Regeln für Komponenten, Markup und CSS: [konventionen.md](konventionen.md). Das Design (Farben, Schriften, Look) steht im jeweiligen Projekt. PHP, MCP und Veröffentlichen: [betrieb.md](betrieb.md).

Versionen stehen hier bewusst nicht. Welche Version ein Projekt einsetzt, hält das Projekt selbst fest (z. B. aus `wp plugin list --status=active`).

## Stack

| Baustein | Plugin-Slug | Zweck | Doku |
| --- | --- | --- | --- |
| WordPress | – | CMS-Basis | – |
| **Etch** | `etch` | Visueller "Unified Visual Development Environment" für WordPress. Erzeugt echtes, semantisches HTML/CSS/PHP/JS statt proprietärem Builder-Markup und erstellt automatisch passende Gutenberg-Blöcke. | [docs.etchwp.com](https://docs.etchwp.com/) |
| **Automatic.css v4 (ACSS)** | `automatic-css` | CSS-Framework/Design-System mit Utility-Klassen, Design-Tokens, automatischen Farbrelationen und fluid-responsivem Spacing. Bindet sich direkt in Etch ein ("True Builder Integration"), keine Zusatz-Plugins nötig. | [docs.automaticcss.com](https://docs.automaticcss.com/) (Version 4) |
| **OhMyEtch** | `oh-my-etch` | Komponentenbibliothek mit atomaren, "headless-style" Bausteinen (Accordion, Dialog, Tabs, Lightbox u. a.) – zugängliches Verhalten/Interaktionslogik, aber bewusst wenig visuelle Vorgaben. Dazu Facet-Komponenten für Filter/Suche und WooCommerce-Atome (Warenkorb, Checkout). | [docs.ohmyetch.com](https://docs.ohmyetch.com/) |
| **Slider Pro for Etch** (EtchSliderPro) | `dwc-slider-pro-etch` + Etch-Komponenten | **Standard für alle Slider und Karussells.** Komponentenbasiertes Slider-/Carousel-System für Etch, auf Basis von Splide. Slider werden aus wiederverwendbaren Teilen (Wrapper, Track, Navigation, Fortschrittsanzeige) zusammengesetzt. **Besteht aus Etch-Komponenten und dem Plugin**; das Plugin lädt Splide, das CSS und `window.SplideComponent`. Ohne Plugin bleiben die Slider stehen. | [design-with-cracka.gitbook.io/etchsliderpro](https://design-with-cracka.gitbook.io/etchsliderpro/) |
| **Mega Menu Pro + Header Builder (EMMP)** | – (Etch-Komponenten + Stylesheet) | Premium-Navigationssystem für Etch – von einfachen responsiven Menüs bis zu Mega-Menüs mit Animationen, Mobile-Optimierung, Tabs im Mega-Menü und flexibler Logo-Positionierung. Kein eigenes Plugin, sondern als Komponenten in Etch hinterlegt (DWC Header, Nav, Dropdown, Menu Item, Mobile Toggle, Tabbed Nav, Tab), dazu das Etch-Stylesheet `DWC Mega Menu` (nie bearbeiten, wird bei Updates ersetzt). Regeln: [konventionen.md](konventionen.md#komponenten). | [design-with-cracka.gitbook.io/megamenupro](https://design-with-cracka.gitbook.io/megamenupro/) ([Changelog](https://design-with-cracka.gitbook.io/megamenupro/changelog)) |
| **Meta Box AIO** | `meta-box-aio` | Framework für eigene Felder, Beitragstypen und Einstellungsseiten inkl. API zur Datenverwaltung. Das AIO-Paket enthält alle Erweiterungen, u. a. MB Custom Post Types und MB Relationships. | [docs.metabox.io](https://docs.metabox.io/) |
| **User Role Editor Pro** | `user-role-editor-pro` | Benutzerrollen und Capabilities, z. B. eigene Rollen mit Zugriff nur auf einzelne Beitragstypen oder Einstellungsseiten. | [role-editor.com/documentation](https://www.role-editor.com/documentation/) |
| **Etch Font Manager** | `etch-font-manager` | Schriftverwaltung: lädt Schriften (auch aus Google Fonts) herunter und hostet sie selbst unter `wp-content/fonts/`, erzeugt `@font-face` mit `unicode-range`, Preload, Fallback-Stack und eine CSS-Variable je Familie. Regeln: [konventionen.md](konventionen.md#schriften). | [codexea.gitbook.io/etch-font-manager](https://codexea.gitbook.io/etch-font-manager/) |
| **Etch Text Editor** | `etch-text-editor` | Popup im Etch-Editor zum Bearbeiten von Text: Etch-Textelemente und Gutenberg-Textblöcke, die Etch sonst nur durchreicht, mit Formatierung im Text (fett, kursiv, Link mit interner Suche, Farbe, weiches Trennzeichen, geschütztes Leerzeichen). Für Seiten, die im Editor gepflegt werden. Freies Plugin von GitHub (ein Skript, keine eigenen Daten), baut auf der experimentellen Schnittstelle `window.etch` auf. Regeln: [konventionen.md](konventionen.md#etch-editor-canvas). | [github.com/electricarts/etch-text-editor](https://github.com/electricarts/etch-text-editor) |
| **WS Form** | `ws-form` | **Standard für alle Formulare.** Formulare mit Prüfung der Eingaben, Speicherung der Anfragen und E-Mail-Versand. Vorerst in der freien Version (WS Form LITE von wordpress.org). Regeln: [konventionen.md](konventionen.md#formulare). | [wsform.com/knowledgebase](https://wsform.com/knowledgebase/) |
| **Bausteine Backend** | `bausteine-backend` | Eigenes Plugin: räumt das Backend auf – blendet „Individuelle Felder“ aus, öffnet bei Etch-Komponenten die Seitenleiste, vereinfacht deren Felder im Block-Editor und fängt eine abgelaufene Anmeldung im Etch-Editor ab. Aktualisiert sich selbst vom eigenen Update-Server. Regeln: [konventionen.md](konventionen.md#backend). | Doku im Repo der eigenen Plugins |
| **WPCodeBox 2** | `wpcodebox2` | Verwaltet die eigenen PHP-Erweiterungen der Website als Snippets. Eigene MCP-Funktionen `wpcodebox/…`. Regeln: [betrieb.md](betrieb.md#php-als-wpcodebox-snippets). | [docs.wpcodebox.com](https://docs.wpcodebox.com/) |

### Betrieb & Werkzeuge

| Baustein | Plugin-Slug | Zweck | Doku |
| --- | --- | --- | --- |
| **Duplicator Pro** | `duplicator-pro` | Backup und Migration, z. B. lokale Seite → Staging → Live. | [duplicator.com/knowledge-base](https://duplicator.com/knowledge-base/) |
| **FluentSMTP** | `fluent-smtp` | **Standard für den Mailversand.** Schickt alle Mails der Website (`wp_mail`, auch die von WS Form) über den SMTP-Server des Postfachs statt über PHP. Frei und quelloffen, mit Mail-Protokoll und Ersatzverbindung. Regeln: [betrieb.md](betrieb.md#mailversand-smtp). | [fluentsmtp.com/docs](https://fluentsmtp.com/docs/) |
| **SEOPress Pro** | `wp-seopress` + `wp-seopress-pro` | Titel und Beschreibungen, XML-Sitemap, Indexierung je Beitragstyp, strukturierte Daten, 301-Weiterleitungen und 404-Protokoll. Eigene MCP-Funktionen `seopress/…` (Regeln: [betrieb.md](betrieb.md#seopress-funktionen-mcp)). | [seopress.org/support](https://www.seopress.org/support/) |
| **MCP Adapter** | `mcp-adapter` | Stellt WordPress-Funktionen (Abilities API) über das Model Context Protocol für KI-Werkzeuge bereit. Nur dort aktiv lassen, wo er gebraucht wird, und nur für berechtigte Benutzer freigeben. | – |
| **Etch AI Connector** | – (Etch-Einstellung + npm-Paket `@digital-gravy/etch-connector`) | Lokaler Dienst, über den ein KI-Agent den geöffneten Etch-Editor liest und ändert (Schnittstelle `etch.*`). Experimentell, nur in der Entwicklung und nur bei Bedarf eingeschaltet. Regeln: [betrieb.md](betrieb.md#etch-ai-connector). | [docs.etchwp.com/public-api](https://docs.etchwp.com/public-api) |

## Plugin-Liste

Jedes Plugin, das neu in die Arbeit aufgenommen wird, steht an drei Stellen – sonst gilt es nicht als aufgenommen:

1. hier in der Tabelle unter [Stack](#stack) bzw. [Betrieb & Werkzeuge](#betrieb--werkzeuge), mit Zweck und Doku; Regeln für den Einsatz in [konventionen.md](konventionen.md) bzw. [betrieb.md](betrieb.md)
2. in der Plugin-Liste des Server-Werkzeugs (`blueprint/plugins.conf`) mit der getesteten Version, das ZIP dazu in der Plugin-Ablage – auch bei freien Plugins
3. in der zentralen Übersicht der Projekte (Abschnitt „WordPress-Plugins“) mit einem Satz zum Zweck

Ein Plugin nur für eine einzelne Website steht nicht hier, sondern bei dieser Website (Projekt-Doku und Einstellungen der Seite im Server-Werkzeug).

## Updates

- Automatische Updates sind aus: für WordPress, Themes, Übersetzungen und alle fremden Plugins. Diese Updates werden von Hand eingespielt.
- **Ausnahme: die eigenen Plugins** (z. B. `bausteine-backend`). Sie tragen im Kopf eine `Update URI` auf den eigenen Update-Server und aktualisieren sich selbst. Lokale und Staging-Seiten (`WP_ENVIRONMENT_TYPE` = `local`, `development`, `staging`) bekommen jede gebaute Version, Live-Seiten (`production`) nur ausdrücklich freigegebene – eine neue Version läuft also zuerst lokal und auf Staging. Das Umgebungs-mu-plugin des Server-Werkzeugs lässt genau diese Updates zu und sperrt alle anderen. Je Website abschalten: `define( 'BAUSTEINE_AUTO', false );` in der `wp-config.php`. Nach einem Update eines eigenen Plugins gilt dieselbe Prüfung wie unten für das, was es berührt.
- **EMMP aktualisieren** (kein Plugin, deshalb von Hand im Etch-Editor, zuerst lokal):
  1. Im [Changelog](https://design-with-cracka.gitbook.io/megamenupro/changelog) nachsehen, was die Version ändert und ob sie nur das CSS oder CSS und Komponenten betrifft.
  2. Vorher sichern (Datenbank bzw. Duplicator-Paket) und die im Projekt dokumentierten Komponenten-IDs bereithalten.
  3. In den Etch-Einstellungen (General) **„Overwrite on Paste“ einschalten**. Ohne den Schalter findet Etch die vorhandenen DWC-Komponenten über ihren Key und lässt sie unverändert; nur neue Komponenten und Stile kommen dazu – das Update wirkt dann nur halb. Mit dem Schalter werden Komponenten und Stile mit gleichem Key bzw. Selektor ersetzt, **die IDs bleiben** (lokal geprüft), vorhandene Header behalten ihre Einstellungen und Inhalte.
  4. Das JSON der neuen Version in Etch einfügen (kein Element ausgewählt, am besten auf einer leeren Entwurfsseite). Etch schreibt die Komponenten dabei sofort, nicht erst beim Speichern. Den dabei eingefügten Beispiel-Header wieder entfernen – die DWC-Komponenten selbst nie löschen und neu anlegen. Danach „Overwrite on Paste“ **wieder ausschalten**.
     - Wer Werte direkt in den Stil-Einträgen von EMMP geändert hat (`.dwc-*-vars`), verliert sie beim Überschreiben. Dafür gibt es den [Updater](https://etch.designwithcracka.com/mmpro-updater/) des Herstellers, der altes und neues JSON zusammenführt („Carry Over“). Nach Standard stehen Anpassungen im Stylesheet des Projekts (siehe [konventionen.md](konventionen.md#css)); dann genügt das JSON des Herstellers.
  5. Im Etch-Stylesheet `DWC Mega Menu` das CSS durch das der neuen Version ersetzen. JSON und CSS gehören zusammen, immer beide auf denselben Stand bringen.
  6. Prüfen: Komponenten-IDs unverändert (siehe [konventionen.md](konventionen.md#komponenten)), dann Frontend wie unten, zusätzlich Skip-Link und Tastaturbedienung. Erst danach auf Staging und live wiederholen.
- **Etch Text Editor: regelmäßig nach Updates suchen.** WordPress meldet für dieses Plugin keine Updates. Deshalb bei jeder Update-Runde, mindestens einmal im Monat, und vor jedem Etch-Update die neueste Version auf GitHub mit der installierten vergleichen:
  ```bash
  gh api repos/electricarts/etch-text-editor/releases/latest --jq .tag_name
  wp plugin get etch-text-editor --field=version
  ```
  Die zentrale Übersicht der Projekte sucht für Plugins von GitHub und von wordpress.org selbst nach Updates (grünes Kettenglied in der Plugin-Liste) und zeigt eine neuere Version unter der vereinbarten an; der Vergleich von Hand ist dann nur die Gegenprobe. Zusätzlich auf GitHub im Repo „Watch › Custom › Releases“ einschalten, dann kommt bei jeder neuen Version eine Nachricht. Claude vergleicht die Versionen, wenn in einem Projekt mit diesem Plugin Updates anstehen, und meldet eine neue Version.
- **Etch Text Editor aktualisieren** (kein Update über WordPress): im [Changelog](https://github.com/electricarts/etch-text-editor/blob/main/CHANGELOG.md) nachsehen, das ZIP des Release von GitHub laden und als Plugin hochladen (ersetzt die vorhandene Version), zuerst lokal. Danach im Etch-Editor prüfen wie unten.
- **Quellen für Komponenten** (fremde Sammlungen, siehe [quellen.md](quellen.md)) bei derselben Update-Runde auf neue und geänderte Einträge prüfen.
- Nach einem Update von Etch, EMMP, EtchSliderPro oder OhMyEtch im Frontend prüfen: Header (auch mobil), Slider, Lightbox und die übrigen Fremdkomponenten. Nach einem Etch-Update zusätzlich im Block-Editor: Seitenleiste und Felder der Komponenten (siehe [konventionen.md](konventionen.md#backend)).
- Nach einem Update von Etch oder vom Etch Text Editor im Etch-Editor prüfen: Textblock auswählen, „Edit“ öffnen, Text mit Link und Formatierung ändern, schließen, im Frontend ansehen. Das Plugin hängt an der experimentellen Schnittstelle von Etch; funktioniert es nach einem Etch-Update nicht mehr, deaktivieren, bis eine passende Version da ist – die Inhalte bleiben unverändert.
