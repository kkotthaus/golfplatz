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
| **EtchMegaMenuPro (EMMP)** | – (Etch-Komponenten) | Premium-Navigationssystem für Etch – von einfachen responsiven Menüs bis zu Mega-Menüs mit Animationen, Mobile-Optimierung und flexibler Logo-Positionierung. Kein eigenes Plugin, sondern als Komponenten in Etch hinterlegt (Header, Nav, Dropdown, Menu Item, Mobile Toggle). | [design-with-cracka.gitbook.io/etchmegamenupro](https://design-with-cracka.gitbook.io/etchmegamenupro/) |
| **Meta Box AIO** | `meta-box-aio` | Framework für eigene Felder, Beitragstypen und Einstellungsseiten inkl. API zur Datenverwaltung. Das AIO-Paket enthält alle Erweiterungen, u. a. MB Custom Post Types und MB Relationships. | [docs.metabox.io](https://docs.metabox.io/) |
| **User Role Editor Pro** | `user-role-editor-pro` | Benutzerrollen und Capabilities, z. B. eigene Rollen mit Zugriff nur auf einzelne Beitragstypen oder Einstellungsseiten. | [role-editor.com/documentation](https://www.role-editor.com/documentation/) |
| **Etch Font Manager** | `etch-font-manager` | Schriftverwaltung: lädt Schriften (auch aus Google Fonts) herunter und hostet sie selbst unter `wp-content/fonts/`, erzeugt `@font-face` mit `unicode-range`, Preload, Fallback-Stack und eine CSS-Variable je Familie. Regeln: [konventionen.md](konventionen.md#schriften). | [codexea.gitbook.io/etch-font-manager](https://codexea.gitbook.io/etch-font-manager/) |
| **WPCodeBox 2** | `wpcodebox2` | Verwaltet die eigenen PHP-Erweiterungen der Website als Snippets. Eigene MCP-Funktionen `wpcodebox/…`. Regeln: [betrieb.md](betrieb.md#php-als-wpcodebox-snippets). | [docs.wpcodebox.com](https://docs.wpcodebox.com/) |

### Betrieb & Werkzeuge

| Baustein | Plugin-Slug | Zweck | Doku |
| --- | --- | --- | --- |
| **Duplicator Pro** | `duplicator-pro` | Backup und Migration, z. B. lokale Seite → Staging → Live. | [duplicator.com/knowledge-base](https://duplicator.com/knowledge-base/) |
| **SEOPress Pro** | `wp-seopress` + `wp-seopress-pro` | Titel und Beschreibungen, XML-Sitemap, Indexierung je Beitragstyp, strukturierte Daten, 301-Weiterleitungen und 404-Protokoll. Eigene MCP-Funktionen `seopress/…` (Regeln: [betrieb.md](betrieb.md#seopress-funktionen-mcp)). | [seopress.org/support](https://www.seopress.org/support/) |
| **MCP Adapter** | `mcp-adapter` | Stellt WordPress-Funktionen (Abilities API) über das Model Context Protocol für KI-Werkzeuge bereit. Nur dort aktiv lassen, wo er gebraucht wird, und nur für berechtigte Benutzer freigeben. | – |

## Updates

- Automatische Updates sind für alle Plugins aus. Updates werden von Hand eingespielt.
- Nach einem Update von Etch, EMMP, EtchSliderPro oder OhMyEtch im Frontend prüfen: Header (auch mobil), Slider, Lightbox und die übrigen Fremdkomponenten. Nach einem Etch-Update zusätzlich im Block-Editor: Seitenleiste und Felder der Komponenten (siehe [konventionen.md](konventionen.md#backend)).
