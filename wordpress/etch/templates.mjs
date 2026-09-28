// Etch-Templates der Website. Aufbau und Klassen wie im Prototyp (prototype/src/build.mjs).
// Erzeugen: node wordpress/etch/build.mjs  →  wordpress/etch/dist/<slug>.html

import { el, t, text, raw, wenn, markup, komponente, club, telHref, icon, svgEl, postContent, loop, CLUB } from './lib.mjs';
import { header } from './header.mjs';
import { birdiebookKomponente, bahnenRaster } from './birdiebook.mjs';

// Hero-Illustration der Startseite (wie im Prototyp). Platzhalter, bis ein Foto des Platzes vorliegt.
const heroArt = () =>
  el('svg', 'home-hero__art', [
    svgEl('defs', {}, [
      svgEl('linearGradient', { id: 'hero-sky', x1: 0, y1: 0, x2: 0, y2: 1 }, [
        svgEl('stop', { class: 'home-hero__sky-top', offset: 0 }),
        svgEl('stop', { class: 'home-hero__sky-mid', offset: 0.6 }),
        svgEl('stop', { class: 'home-hero__sky-bottom', offset: 1 }),
      ]),
    ]),
    svgEl('rect', { width: 1440, height: 640, fill: 'url(#hero-sky)' }),
    svgEl('circle', { class: 'home-hero__sun', cx: 1120, cy: 200, r: 70 }),
    svgEl('path', { class: 'home-hero__hill home-hero__hill--far', d: 'M0 380 C200 330 380 350 560 320 S920 280 1120 330 1440 300 1440 300 V640 H0Z' }),
    svgEl('g', { class: 'home-hero__trees' }, [
      [160, 352, 34], [205, 340, 44], [250, 356, 30], [1210, 318, 36], [1260, 306, 48], [1310, 322, 32],
    ].map(([cx, cy, r]) => svgEl('circle', { cx, cy, r }))),
    svgEl('path', { class: 'home-hero__hill home-hero__hill--mid', d: 'M0 450 C260 400 520 430 760 400 S1180 380 1440 420 V640 H0Z' }),
    svgEl('path', { class: 'home-hero__hill home-hero__hill--near', d: 'M0 530 C300 480 640 520 900 490 S1300 470 1440 500 V640 H0Z' }),
    svgEl('ellipse', { class: 'home-hero__green', cx: 930, cy: 468, rx: 120, ry: 22 }),
    svgEl('ellipse', { class: 'home-hero__water', cx: 560, cy: 505, rx: 110, ry: 12 }),
    svgEl('line', { class: 'home-hero__pole', x1: 950, y1: 468, x2: 950, y2: 392 }),
    svgEl('path', { class: 'home-hero__flag', d: 'M950 392 l40 12 -40 12z' }),
    svgEl('path', { class: 'home-hero__ground', d: 'M0 600 C400 560 900 600 1440 570 V640 H0Z' }),
  ], { attrs: { viewBox: '0 0 1440 640', preserveAspectRatio: 'xMidYMid slice', 'aria-hidden': 'true' }, name: 'Hero-Illustration' });

const einstieg = (nr, titel, textInhalt, mehr, href) =>
  el('a', 'entry-card', [
    t('span', 'entry-card__number', nr),
    t('h3', 'entry-card__title', titel),
    t('p', 'entry-card__text', textInhalt),
    el('span', 'entry-card__more', [text(mehr + ' '), icon('arrow')]),
  ], { attrs: { href } });

// Jeder Abschlag ist für ein Geschlecht bewertet (Scorekarte).
const abschlaege = [
  ['gelb', 'Gelb · Herren'],
  ['blau', 'Blau · Herren'],
  ['rot', 'Rot · Damen'],
  ['orange', 'Orange · Damen'],
];

const teeBadge = (id, name) =>
  el('span', `tee-badge tee-badge--${id}`, [el('span', 'tee-badge__dot', [], { attrs: { 'aria-hidden': 'true' } }), text(name)]);

const fakt = (label, wert, mod = '') =>
  el('div', 'hole-facts__item' + (mod ? ` hole-facts__item--${mod}` : ''), [
    label.startsWith('<!--') ? el('dt', 'hole-facts__label', [label]) : t('dt', 'hole-facts__label', label),
    t('dd', 'hole-facts__value', wert),
  ]);

const seitenkopf = ({ krumen, eyebrow, titel, lead, aktionen }) =>
  el('section', 'page-hero', [
    el('div', 'page-hero__inner container', [
      el('nav', 'breadcrumb', [
        el('ol', 'breadcrumb__list', [
          el('li', 'breadcrumb__item', [t('a', 'breadcrumb__link', 'Start', { attrs: { href: '/' } })]),
          ...krumen.map(([label, href]) =>
            href
              ? el('li', 'breadcrumb__item', [t('a', 'breadcrumb__link', label, { attrs: { href } })])
              : t('li', 'breadcrumb__item', label, { attrs: { 'aria-current': 'page' } }),
          ),
        ]),
      ], { attrs: { 'aria-label': 'Brotkrumen' } }),
      eyebrow && t('p', 'page-hero__eyebrow', eyebrow),
      t('h1', 'page-hero__title', titel),
      lead && t('p', 'page-hero__lead', lead),
      aktionen && el('div', 'page-hero__actions', aktionen),
    ]),
  ], { name: 'Seitenkopf' });

// Feste Seiten (page). slug/parent bestimmen die URL.
export const pages = [
  {
    slug: 'startseite',
    title: 'Startseite',
    order: 0,
    front_page: true,
    content: markup(
      el('section', 'home-hero', [
        heroArt(),
        el('div', 'home-hero__inner container', [
          el('div', 'home-hero__content', [
            t('p', 'home-hero__eyebrow', 'Willkommen im ' + club('club_name')),
            el('h1', 'home-hero__title', [t('span', 'home-hero__line', 'Golf mit Tradition,'), t('span', 'home-hero__line', 'Natur mit Weitblick.')]),
            t('p', 'home-hero__lead', club('club_claim') + ' Mitglieder, Gäste und Einsteiger sind herzlich willkommen.'),
            el('div', 'home-hero__actions', [
              t('a', 'btn btn--primary btn--large', 'Als Gast spielen', { attrs: { href: '/greenfee/#spielen' } }),
              t('a', 'btn btn--ghost btn--large', 'Mitglied werden', { attrs: { href: '/mitgliedschaft/' } }),
            ]),
          ]),
          // Platzstatus-Kurzfassung: sofort sichtbar, ohne Scrollen. Logik: mu-plugins/golfplatz-platzstatus.php
          komponente('PlatzstatusKurz'),
        ]),
      ], { name: 'Hero' }),
      // Platzstatus direkt unter dem Hero, überlappend. Logik: mu-plugins/golfplatz-platzstatus.php
      el('div', 'container home-status', [komponente('Platzstatus')], { name: 'Platzstatus' }),
      el('section', 'section', [
        el('div', 'container', [
          el('header', 'section-head section-head--center', [
            t('p', 'section-head__eyebrow', 'Für Sie da'),
            t('h2', 'section-head__title', 'Wie möchten Sie uns kennenlernen?'),
          ]),
          el('div', 'grid grid--4 entry-cards', [
            einstieg('01', 'Mitglied werden', 'Vier Modelle, vom Einsteiger bis zur Vollmitgliedschaft – ohne Aufnahmegebühr im Einsteigermodell.', 'Mitgliedschaften', '/mitgliedschaft/'),
            einstieg('02', 'Als Gast spielen', 'Greenfee ab 40 €. Keine festen Startzeiten – am Wochenende bitte kurz anmelden.', 'Greenfee & Preise', '/greenfee/'),
            einstieg('03', 'Golf lernen', 'Schnupperkurs, Platzreife oder Training mit unseren PGA-Pros – für jedes Alter.', 'Golfschule', '/golfschule/'),
            einstieg('04', 'Genießen & Feiern', 'Clubrestaurant mit Terrasse, Feiern und Firmenevents – auch für Nicht-Golfer.', 'Restaurant', '/restaurant/'),
          ]),
        ]),
      ], { name: 'Einstiege' }),
      el('section', 'section section--tint', [el('div', 'container', [komponente('GastInfo', { anker: 'gast' })])], { name: 'Als Gast spielen' }),
    ),
  },
  {
    slug: 'platz',
    title: 'Platz & Bahnen',
    order: 10,
    content: markup(
      seitenkopf({
        krumen: [['Platz & Bahnen']],
        eyebrow: 'Der Platz',
        titel: 'Platz & Bahnen',
        lead: '18 Loch, Par 71 (Herren) / 69 (Damen), 5.880 Meter von Gelb. Vier Abschläge – zwei für Herren, zwei für Damen.',
        aktionen: [
          t('a', 'btn btn--secondary', 'Birdiebook öffnen', { attrs: { href: '/platz/birdiebook/' } }),
          t('a', 'btn btn--ghost', 'Zur Scorekarte', { attrs: { href: '#scorekarte' } }),
        ],
      }),
      el('section', 'section birdiebook-section', [
        el('div', 'container', [
          el('header', 'section-head', [
            t('p', 'section-head__eyebrow', 'Auf der Runde'),
            t('h2', 'section-head__title', 'Birdiebook'),
            t('p', 'section-head__lead', 'Bahn für Bahn wischen, Abschlag wählen – Länge, Par und Entfernungen passen sich an. Für das Handy gibt es das Birdiebook auch als Vollbild.'),
          ]),
          komponente('Birdiebook'),
        ]),
      ], { attrs: { id: 'birdiebook' }, name: 'Birdiebook' }),
      el('section', 'section', [
        el('div', 'container split', [
          el('div', 'split__text prose', [
            t('h2', '', 'Golf im Bergischen Land'),
            t('p', '', 'Unser Platz in Hückeswagen ist harmonisch in das für die Region vergleichsweise flache Gelände eingebettet. Große Grüns und breite Fairways, Teiche und Bunker an den richtigen Stellen – und weite Ausblicke von fast jeder Bahn.'),
            t('p', '', 'Zum Üben gibt es Driving Range, Kurzspielbereich und Putting-Grün.'),
            el('p', '', [el('a', 'link-arrow', [text('Spielvorgaben und Rechner ')], { attrs: { href: '/platz/spielvorgaben/' } })]),
          ], { name: 'Platzbeschreibung' }),
          el('div', 'split__media', [t('h2', 'h4', 'Course & Slope Rating'), komponente('Rating')], { name: 'Rating' }),
        ]),
      ]),
      el('section', 'section section--tint', [
        el('div', 'container', [
          el('header', 'section-head', [t('p', 'section-head__eyebrow', 'Hole by Hole'), t('h2', 'section-head__title', 'Alle 18 Bahnen')]),
          bahnenRaster(),
        ]),
      ], { name: 'Alle Bahnen' }),
      el('section', 'section', [
        el('div', 'container', [
          el('header', 'section-head', [t('p', 'section-head__eyebrow', 'Scorekarte'), t('h2', 'section-head__title', 'Längen in Metern je Abschlag')]),
          komponente('Scorekarte'),
        ]),
      ], { attrs: { id: 'scorekarte' }, name: 'Scorekarte' }),
    ),
  },
  {
    // Vollbild-Birdiebook fürs Handy (eigenes Template page-birdiebook ohne Kopf- und Fußbereich)
    slug: 'birdiebook',
    title: 'Birdiebook',
    parent: 'platz',
    order: 5,
    content: markup(
      el('h1', 'visually-hidden', [text('Birdiebook')]),
      komponente('Birdiebook'),
    ),
  },
  {
    slug: 'greenfee',
    title: 'Greenfee & Preise',
    order: 20,
    content: markup(
      seitenkopf({
        krumen: [['Greenfee & Preise']],
        eyebrow: 'Für Gäste',
        titel: 'Greenfee & Preise',
        lead: 'Gäste sind jeden Tag willkommen – ohne feste Startzeiten. Am Wochenende und an Feiertagen bitte kurz telefonisch anmelden.',
      }),
      el('section', 'section', [
        el('div', 'container split split--wide-left', [
          el('div', '', [
            t('h2', 'h3', 'Greenfee {options.golfplatz.preise.jahr}'),
            komponente('Preistabelle', { kategorie: 'greenfee' }),
            wenn(`${CLUB}.greenfee_fussnote`, [t('p', 'small', club('greenfee_fussnote'))]),
            wenn('options.golfplatz.twilight.regel', [
              el('p', 'price-note', [
                t('strong', 'price-note__title', 'Twilight: '),
                text('{options.golfplatz.twilight.regel}'),
                wenn('options.golfplatz.twilight.hat_zeit', [
                  text(' '),
                  t('span', 'price-note__today', 'Heute ab {options.golfplatz.twilight.ab} Uhr (Sonnenuntergang {options.golfplatz.twilight.sonnenuntergang} Uhr).'),
                ]),
              ], { name: 'Twilight' }),
            ]),
            t('h2', 'h3 spacer-top', 'Greenfee bei Turnieren'),
            komponente('Preistabelle', { kategorie: 'turnier' }),
            t('h2', 'h3 spacer-top', 'Leihgeräte'),
            komponente('Preistabelle', { kategorie: 'leihe' }),
          ], { name: 'Preise' }),
          el('aside', 'side-box', [
            t('h2', 'side-box__title', 'Gut zu wissen'),
            el('ul', 'check-list', [
              loop({ target: `${CLUB}.greenfee_hinweise`, itemId: 'h' }, [t('li', 'check-list__item', '{h}')]),
              wenn(`${CLUB}.ruhetag_hinweis`, [t('li', 'check-list__item', club('ruhetag_hinweis'))]),
            ]),
          ], { name: 'Gut zu wissen' }),
        ]),
      ], { name: 'Greenfee' }),
      el('section', 'section section--tint', [el('div', 'container', [komponente('GastInfo', { anker: 'spielen' })])], { name: 'Als Gast spielen' }),
      el('section', 'section', [
        el('div', 'container', [
          el('header', 'section-head', [
            t('p', 'section-head__eyebrow', 'Anlage'),
            t('h2', 'section-head__title', 'Öffnungszeiten'),
            t('p', 'section-head__lead', 'Sekretariat, Übungsanlagen, Proshop und Restaurant. Abweichende Zeiten und aktuelle Sperren sind markiert.'),
          ]),
          komponente('OeffnungszeitenAlle'),
        ]),
      ], { name: 'Öffnungszeiten' }),
      el('section', 'section', [
        el('div', 'container', [
          el('header', 'section-head', [
            t('p', 'section-head__eyebrow', 'Kooperationen'),
            t('h2', 'section-head__title', 'Greenfee für Mitglieder unserer Partnerclubs'),
            wenn(`${CLUB}.kooperationen_hinweis`, [t('p', 'section-head__lead', club('kooperationen_hinweis'))]),
          ]),
          komponente('Preistabelle', { kategorie: 'kooperationen' }),
        ]),
      ], { name: 'Kooperationen' }),
    ),
  },
];

// Spielvorgaben: Rechner und Tabellen je Abschlag (Werte aus Clubdaten › Platz & Abschläge).
pages.push({
  slug: 'spielvorgaben',
  title: 'Spielvorgaben',
  parent: 'platz',
  order: 10,
  content: markup(
    seitenkopf({
      krumen: [['Platz & Bahnen', '/platz/'], ['Spielvorgaben']],
      eyebrow: 'World Handicap System',
      titel: 'Spielvorgaben',
      lead: 'Welche Spielvorgabe haben Sie auf unserem Platz? Geben Sie Ihren Handicap-Index ein oder lesen Sie ihn in den Tabellen je Abschlag ab.',
    }),
    el('section', 'section', [
      el('div', 'container split', [
        komponente('SpielvorgabenRechner'),
        el('div', 'prose', [
          t('h2', '', 'So wird gerechnet'),
          t('p', '', 'Die Spielvorgabe ergibt sich nach dem World Handicap System aus Ihrem Handicap-Index und der Bewertung des Abschlags:'),
          t('p', 'formula', 'Spielvorgabe = Handicap-Index × Slope ÷ 113 + (Course Rating − Par)'),
          t('p', '', 'Das Ergebnis wird auf eine ganze Zahl gerundet. Die Tabellen unten werden aus denselben Werten berechnet. Ändert der Verband Course Rating oder Slope, passen sich Rechner und Tabellen automatisch an.'),
          komponente('Rating'),
        ], { name: 'So wird gerechnet' }),
      ]),
    ], { name: 'Rechner' }),
    el('section', 'section section--tint', [
      el('div', 'container', [
        el('header', 'section-head', [t('p', 'section-head__eyebrow', 'Tabellen'), t('h2', 'section-head__title', 'Spielvorgaben je Abschlag')]),
        komponente('SpielvorgabenTabellen'),
      ]),
    ], { name: 'Tabellen' }),
    el('section', 'section', [
      el('div', 'container prose', [
        t('h2', 'h3', 'Zählkarte für Ihre Runde'),
        t('p', '', 'Mit der Zählkarte sehen Sie Ihre Vorgabeschläge auf jedem Loch und rechnen Brutto, Netto und Stableford-Punkte mit.'),
        el('p', '', [el('a', 'link-arrow', [text('Zur Zählkarte ')], { attrs: { href: '/platz/zaehlkarte/' } })]),
      ]),
    ], { name: 'Verweis Zählkarte' }),
  ),
});

// Zählkarte: eigene Seite unter Platz & Bahnen (Menü „Golf spielen“)
pages.push({
  slug: 'zaehlkarte',
  title: 'Zählkarte',
  parent: 'platz',
  order: 11,
  content: markup(
    seitenkopf({
      krumen: [['Platz & Bahnen', '/platz/'], ['Zählkarte']],
      eyebrow: 'Meine Runde',
      titel: 'Zählkarte',
      lead: 'Handicap-Index und Abschlag wählen: Sie sehen auf jedem Loch Ihre Vorgabeschläge. Tragen Sie Ihre Schläge ein – Brutto, Netto und Stableford-Punkte rechnen sich mit.',
    }),
    el('section', 'section', [
      el('div', 'container', [komponente('Zaehlkarte')]),
    ], { attrs: { id: 'zaehlkarte' }, name: 'Zählkarte' }),
    el('section', 'section section--tint', [
      el('div', 'container split', [
        el('div', 'prose', [
          t('h2', 'h3', 'So wird gezählt'),
          t('p', '', 'Die Spielvorgabe ergibt sich nach dem World Handicap System aus Handicap-Index, Course Rating, Slope und Par des gewählten Abschlags. Die Vorgabeschläge werden nach der Schwierigkeit der Löcher (HCP 1 = am schwersten) verteilt; bei einem Plus-Handicap geben Sie Schläge ab dem leichtesten Loch zurück.'),
          t('p', '', 'Stableford: Par ergibt 2 Punkte, jeder Schlag besser einen Punkt mehr. Ab zwei Schlägen über Par gibt es keinen Punkt – netto zählt Par plus Ihre Vorgabeschläge.'),
          el('p', '', [el('a', 'link-arrow', [text('Spielvorgaben-Tabellen je Abschlag ')], { attrs: { href: '/platz/spielvorgaben/' } })]),
        ], { name: 'So wird gezählt' }),
        el('div', 'split__media', [t('h2', 'h4', 'Course & Slope Rating'), komponente('Rating')], { name: 'Rating' }),
      ]),
    ], { name: 'Erklärung' }),
  ),
});

// Platzhalter-Seiten für Menüpunkte, deren Inhalt noch folgt (Aufbau laut docs/seitenstruktur.md).
const platzhalter = (slug, title, eyebrow, lead, parent) => ({
  slug,
  title,
  parent,
  order: 50,
  content: markup(
    seitenkopf({ krumen: parent ? [['Platz & Bahnen', '/platz/'], [title]] : [[title]], eyebrow, titel: title, lead }),
    el('section', 'section', [el('div', 'container prose', [t('p', 'small', 'Diese Seite wird gerade aufgebaut.')])], { name: 'Inhalt folgt' }),
  ),
});
pages.push(
  platzhalter('golfschule', 'Golfschule', 'Golf lernen', 'Vom ersten Schwung bis zur DGV-Platzreife mit unseren Professionals.'),
  platzhalter('restaurant', 'Restaurant', 'Gastronomie', 'Für Golfer und alle anderen Gäste.'),
  platzhalter('news', 'Aktuelles', 'News', 'Neues aus dem Club.'),
  platzhalter('club', 'Club & Kontakt', 'Über uns', 'Golf im Bergischen Land – in Hückeswagen. Ansprechpartner, Anfahrt und Kontakt.'),
  platzhalter('mitglieder', 'Mitgliederbereich', 'Intern', 'Melden Sie sich an, um die internen Inhalte zu sehen.'),
  platzhalter('impressum', 'Impressum', '', ''),
  platzhalter('datenschutz', 'Datenschutz', '', ''),
);

// Mitgliedschaft: Modelle (Preise der Kategorie „Mitgliedschaft“), drei Schritte, Aufnahmeantrag mit FAQ.
// Ein Antragsformular folgt, sobald ein Formular-Plugin feststeht – bis dahin Telefon, E-Mail und PDF.
const faq = (frage, antwort) =>
  el('details', 'accordion__item', [t('summary', 'accordion__summary', frage), el('div', 'accordion__content', [t('p', '', antwort)])]);
const schritt = (titel, inhalt) => el('li', 'steps__item', [t('h3', 'steps__title', titel), el('p', 'steps__text', inhalt)]);
pages.push({
  slug: 'mitgliedschaft',
  title: 'Mitgliedschaft',
  order: 50,
  content: markup(
    seitenkopf({
      krumen: [['Mitgliedschaft']],
      eyebrow: 'Mitglied werden',
      titel: 'Ihr Heimatclub',
      lead: 'Unbegrenzt spielen – ohne Startzeiten, mit DGV-Mitgliedschaft, GOLFHOCHZEHN und Urlaubspartnern. Mitglied werden Sie mit einer Aktie der Dohrmann Golfplatz AG oder mit einer Spielberechtigung.',
    }),
    el('section', 'section', [el('div', 'container', [komponente('Preiskarten', { kategorie: 'mitgliedschaft', ziel: '#antrag' })])], { name: 'Modelle' }),
    el('section', 'section section--tint', [
      el('div', 'container', [
        el('header', 'section-head section-head--center', [t('p', 'section-head__eyebrow', 'In drei Schritten'), t('h2', 'section-head__title', 'So werden Sie Mitglied')]),
        el('ol', 'steps', [
          schritt('Kennenlernen', [text('Spielen Sie eine Runde als Gast oder besuchen Sie einen '), t('a', '', 'Schnupperkurs', { attrs: { href: '/golfschule/' } }), text('.')]),
          schritt('Persönliches Gespräch', [text('Aktie oder Spielberechtigung? Wir beraten Sie und nennen Ihnen die aktuellen Konditionen.')]),
          schritt('Aufnahmeantrag', [text('Antrag als PDF herunterladen, ausfüllen und im Sekretariat abgeben – oder uns anrufen oder schreiben.')]),
        ]),
      ]),
    ], { name: 'In drei Schritten' }),
    el('section', 'section', [
      el('div', 'container split', [
        el('div', 'prose', [
          t('h2', '', 'Aufnahmeantrag'),
          t('p', '', 'Rufen Sie uns an oder schreiben Sie uns, wir melden uns für ein persönliches Gespräch. Ansprechpartner ist Erich Buchholz (Clubmanagement).'),
          // Kontaktzeilen wie auf „Club & Kontakt“ im Prototyp: Link mit Symbol
          el('p', '', [
            el('a', 'contact-line', [icon('phone'), text(' ' + club('club_telefon'))], { attrs: { href: telHref('club_telefon') } }),
            el('br', '', []),
            el('a', 'contact-line', [icon('mail'), text(' ' + club('club_email'))], { attrs: { href: 'mailto:' + club('club_email') } }),
          ]),
          el('p', '', [t('a', 'btn btn--primary', 'Aufnahmeantrag als PDF', { attrs: { href: 'https://dreibaeumen.de/wp-content/uploads/2020/07/Aufnahmeantrag_GC3B.pdf' } })]),
        ], { name: 'Kontakt' }),
        el('div', '', [
          t('h3', '', 'Häufige Fragen'),
          el('div', 'accordion', [
            faq('Warum eine Aktie?', 'Voraussetzung für die ordentliche Mitgliedschaft ist eine der 800 Aktien der Dohrmann Golfplatz AG. Sie können eine Aktie kaufen und später verkaufen, verschenken oder vererben – oder eine Spielberechtigung erwerben (Aktienmiete).'),
            faq('Muss ich Startzeiten buchen?', 'Nein. Auf unserem Platz gibt es keine festen Startzeiten.'),
            faq('Was ist GOLFHOCHZEHN?', 'Ein Verbund von Golfclubs, auf deren Plätzen Mitglieder greenfeefrei spielen.'),
          ]),
        ], { name: 'Häufige Fragen' }),
      ]),
    ], { attrs: { id: 'antrag' }, name: 'Aufnahmeantrag' }),
  ),
});

// Turniere: Lochwettspiel (auf der Website gepflegt) und Hinweis auf PC CADDIE (Einbettung folgt)
pages.push({
  slug: 'turniere',
  title: 'Turniere & Kalender',
  order: 50,
  content: markup(
    seitenkopf({ krumen: [['Turniere & Kalender']], eyebrow: 'Spielbetrieb', titel: 'Turniere & Kalender', lead: 'Clubturniere mit Ausschreibung, Meldung und Ergebnissen aus PC CADDIE – und unser Lochwettspiel im K.-o.-System.' }),
    el('section', 'section', [
      el('div', 'container', [
        t('h2', '', 'Lochwettspiel'),
        t('p', 'lead', 'Einmal im Jahr spielen Zweier-Teams im Lochwettspiel um den Titel. Jede Runde hat einen festen Zeitraum, in dem die Teams ihr Spiel selbst verabreden.'),
        komponente('Lochwettspiel', { jahr: 'aktuell' }),
      ]),
    ], { attrs: { id: 'lochwettspiel' }, name: 'Lochwettspiel' }),
    el('section', 'section section--tint', [el('div', 'container prose', [t('h2', 'h3', 'Turnierkalender'), t('p', 'small', 'Turnierkalender, Meldung und Ergebnisse aus PC CADDIE werden gerade eingebunden.')])], { name: 'PC CADDIE' }),
  ),
});

// Seitenrahmen: EMMP-Header, Inhalt, Footer. id=main ist das Sprungziel des EMMP-Skip-Links.
const rahmen = (...inhalt) => markup(header(), el('main', 'site-main', inhalt, { attrs: { id: 'main' }, name: 'Main' }), komponente('SiteFooter'));

export const templates = [
  {
    // Vollbild-Birdiebook: schmale Leiste (Logo, Platzstatus, Schließen), kein Menü, kein Footer
    slug: 'page-birdiebook',
    title: 'Birdiebook (Vollbild)',
    content: markup(
      el('header', 'app-bar', [
        el('a', 'app-bar__home', [text('{options.metabox.clubdaten.club_kurzname}')], { attrs: { href: '/' } }),
        el('div', 'app-bar__status', [komponente('Ampel')]),
        el('a', 'app-bar__close', [t('span', 'visually-hidden', 'Birdiebook schließen, zu '), text('Platz & Bahnen')], { attrs: { href: '/platz/' } }),
      ], { name: 'App-Leiste' }),
      el('main', 'site-main site-main--app', [postContent()], { attrs: { id: 'main' }, name: 'Main' }),
    ),
  },
  {
    // Standard-Template für alle Seiten ohne eigenes Template (ersetzt den EMMP-Beispiel-Header)
    slug: 'index',
    title: 'Index',
    content: rahmen(postContent()),
  },
  {
    // Ein Jahrgang des Lochwettspiels: /turniere/lochwettspiel/<jahr>/
    slug: 'single-lochwettspiel',
    title: 'Lochwettspiel',
    content: rahmen(
      seitenkopf({
        krumen: [['Turniere & Kalender', '/turniere/'], ['Lochwettspiel {this.metabox.lw_jahr}']],
        eyebrow: 'Lochwettspiel · Zweier-Teams',
        titel: '{this.title}',
      }),
      el('section', 'section', [
        el('div', 'container', [t('h2', 'visually-hidden', 'Turnierbaum'), komponente('Lochwettspiel', { jahr: '{this.metabox.lw_jahr}' })]),
      ], { name: 'Turnierbaum' }),
      el('section', 'section section--tint', [el('div', 'container prose', [postContent()])], { name: 'Ausschreibung' }),
    ),
  },
  {
    slug: 'single-spielbahn',
    title: 'Spielbahn',
    content: rahmen(
        seitenkopf({
          krumen: [['Platz & Bahnen', '/platz/'], ['Bahn {this.metabox.bahn_nummer}']],
          eyebrow: 'Hole by Hole · {this.metabox.bahn_nummer} von 18',
          titel: '{this.title}',
          lead: 'Par {this.metabox.bahn_par_herren} (Herren) · {this.metabox.bahn_par_damen} (Damen) · HCP {this.metabox.bahn_hcp}',
        }),
        el('section', 'section', [
          el('div', 'container hole-detail', [
            el('figure', 'hole-detail__map', [
              t('figcaption', 'hole-detail__caption', 'Bahngrafik / Luftbild'),
            ], { name: 'Bahngrafik' }),
            el('div', 'hole-detail__content', [
              el('dl', 'hole-facts', [
                fakt('Par Herren', '{this.metabox.bahn_par_herren}'),
                fakt('Par Damen', '{this.metabox.bahn_par_damen}'),
                fakt('HCP', '{this.metabox.bahn_hcp}'),
                ...abschlaege.map(([id, name]) => fakt(teeBadge(id, name), `{this.metabox.laenge_${id}} m`, 'tee')),
              ], { name: 'Eckdaten' }),
              wenn('this.metabox.bahn_beschreibung', [
                el('div', 'prose', [t('h2', '', 'Die Bahn'), raw('{this.metabox.bahn_beschreibung}')], { name: 'Beschreibung' }),
              ]),
              wenn('this.metabox.bahn_spieltipp', [
                el('aside', 'tip', [t('p', 'tip__label', 'Spieltipp vom Pro'), t('p', 'tip__text', '{this.metabox.bahn_spieltipp}')], { name: 'Spieltipp' }),
              ]),
            ]),
          ]),
        ], { name: 'Bahn' }),
    ),
  },
];
