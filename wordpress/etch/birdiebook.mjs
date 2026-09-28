// Birdiebook und Bahnen-Übersicht für /platz/ und /platz/birdiebook/ (Konzept: docs/konzept-birdiebook.md).
// Slider: EtchSliderPro (DWC Slider Wrapper 240, DWC Slider 241). Die Slides erzeugt ein Etch-Loop über
// alle Spielbahnen (loops.mjs › gp-bahnen). Grafik, Status, Fahne und Entfernungen rechnet
// mu-plugins/golfplatz-birdiebook.php und stellt sie je Bahn als {item.golfplatz.…} bereit (Filter
// etch/dynamic_data/post), Scorekarte und Rating als {options.golfplatz.platz.…}. Keine Shortcodes.
// Abschlag-Wahl, Direktlink und Nummernleiste steuert das Skript desselben Moduls.

import { el, t, text, loop, wenn, emmp, gruppe } from './lib.mjs';

const ESP = { wrapper: 240, slider: 241 };
const B = (feld) => `{item.metabox.${feld}}`;
const NR = B('bahn_nummer');
const G = (pfad) => `{item.golfplatz.${pfad}}`;
const PLATZ = 'options.golfplatz.platz';

export const ABSCHLAEGE = [
  ['gelb', 'Gelb', 'herren'],
  ['blau', 'Blau', 'herren'],
  ['rot', 'Rot', 'damen'],
  ['orange', 'Orange', 'damen'],
];

const teeBadge = (id, name) =>
  el('span', `tee-badge tee-badge--${id}`, [el('span', 'tee-badge__dot', [], { attrs: { 'aria-hidden': 'true' } }), text(name)]);

// Par: Herren und Damen, das CSS zeigt je nach gewähltem Abschlag eines davon.
const parWerte = () => [
  t('span', 'hole-sheet__par hole-sheet__par--herren', B('bahn_par_herren')),
  t('span', 'hole-sheet__par hole-sheet__par--damen', B('bahn_par_damen')),
];

/**
 * Bahngrafik: hochgeladenes Bild oder aus den Daten gezeichnet (Grün oben, Abschlag unten).
 * mitEntfernung: Entfernung an den Hindernissen anzeigen (Birdiebook). bild: 'bild' (hochkant) oder 'bild_karte'.
 */
const bahngrafik = ({ bild, mitEntfernung }) => [
  wenn(`item.golfplatz.plan.hat_${bild}`, [
    el('img', 'hole-plan__bild', [], { attrs: { src: G(`plan.${bild}`), alt: '', loading: 'lazy' } }),
  ]),
  wenn(`item.golfplatz.plan.hat_${bild}`, [
    el('span', `hole-plan hole-plan--par${G('plan.par')} hole-plan--${G('plan.richtung')}`, [
      el('span', 'hole-plan__fairway', []),
      el('span', 'hole-plan__green', [el('span', 'hole-plan__flag', [])], { attrs: { style: G('plan.pin_stil') } }),
      el('span', 'hole-plan__tee', []),
      loop({ target: 'item.golfplatz.plan.hindernisse', itemId: 'h' }, [
        el('span', 'hole-plan__hazard hole-plan__hazard--{h.art} hole-plan__hazard--{h.seite}', mitEntfernung ? [t('span', 'hole-plan__distance', '{h.bis}')] : [], {
          attrs: { style: '--pos:{h.pos}' },
        }),
      ]),
    ], { attrs: { 'aria-hidden': 'true' } }),
  ], 'isFalsy'),
];

/** Fahne und Entfernungen zur Grünmitte als Text (zur Grafik). */
const entfernungen = () =>
  wenn('item.golfplatz.entfernungen.vorhanden', [
    el('ul', 'hole-distances', [
      wenn('item.golfplatz.fahne.vorhanden', [
        el('li', 'hole-distances__item hole-distances__item--fahne', [
          el('span', 'hole-distances__name', [text('Fahne'), wenn('item.golfplatz.fahne.ausnahme', [text(' '), t('small', '', 'abweichend')])]),
          t('span', 'hole-distances__value', G('fahne.text')),
        ]),
      ]),
      loop({ target: 'item.golfplatz.entfernungen.liste', itemId: 'e' }, [
        el('li', 'hole-distances__item hole-distances__item--{e.art}', [
          el('span', 'hole-distances__name', [text('{e.name} '), t('small', '', '{e.seite}')]),
          t('span', 'hole-distances__value', '{e.wert}'),
        ]),
      ]),
      wenn('item.golfplatz.entfernungen.gruen_tiefe', [
        el('li', 'hole-distances__item hole-distances__item--gruen', [
          t('span', 'hole-distances__name', 'Grüntiefe'),
          t('span', 'hole-distances__value', G('entfernungen.gruen_tiefe')),
        ]),
      ]),
    ], { attrs: { 'aria-label': 'Fahne und Entfernungen bis zur Grünmitte' } }),
  ]);

/** Eine Bahn im Birdiebook (Slide). */
const bahnSlide = () =>
  el('div', 'splide__slide hole-sheet', [
    el('header', 'hole-sheet__head', [
      el('h3', 'hole-sheet__title', [t('span', 'hole-sheet__label', 'Bahn'), text(' '), t('span', 'hole-sheet__number', NR)]),
      el('div', 'hole-sheet__status', [t('span', `hole-status hole-status--${G('status.mod')}`, G('status.text'))]),
      el('p', 'hole-sheet__length', [
        ...ABSCHLAEGE.map(([id]) => t('span', `hole-sheet__len hole-sheet__len--${id}`, B(`laenge_${id}`))),
        el('span', 'hole-sheet__unit', [text(' m')]),
        el('span', 'hole-sheet__from', [text('ab '), ...ABSCHLAEGE.map(([id, name]) => t('span', `hole-sheet__tee hole-sheet__tee--${id}`, name))]),
      ]),
      el('p', 'hole-sheet__meta', [text('Par '), ...parWerte(), text(' · HCP '), t('span', '', B('bahn_hcp'))]),
    ]),
    el('figure', 'hole-sheet__plan', bahngrafik({ bild: 'bild', mitEntfernung: true }), { name: 'Bahngrafik' }),
    el('div', 'hole-sheet__info', [
      entfernungen(),
      wenn('item.metabox.bahn_spieltipp', [
        el('p', 'hole-sheet__tip', [t('strong', '', 'Tipp: '), text(B('bahn_spieltipp'))]),
      ]),
      el('a', 'hole-sheet__more link-arrow', [text(`Mehr zur Bahn ${NR}`)], { attrs: { href: '{item.permalink.relative}' } }),
    ]),
  ], { attrs: { id: `bahn-${NR}`, 'data-bahn': NR }, name: 'Bahn (Slide)' });

/** Hauptslider (EtchSliderPro). Pfeile, Punkte und Autoplay aus – gesteuert über eigene, beschriftete Knöpfe. */
const slider = () =>
  emmp(
    ESP.slider,
    {
      sliderSetup: gruppe({ sliderRole: 'main', transitionType: 'Slide', slldeDirection: 'ltr', customOptions: "keyboard: 'focused'" }),
      layout: gruppe({ slidesPerPage: '1', slidesPerMove: '1', gapBetweenSlides: '1.5rem', paddingBlock: '0px' }),
      dimensions: gruppe({ sliderHeight: 'auto', slideAutoWidth: false }),
      motion: gruppe({ loop: false, rewind: false, enableDrag: true, speed: '300', focus: '0', updateOnMove: true }),
      autoplay: gruppe({ autoPlay: false, interval: '4000', pauseOnHover: false, playPauseButton: false }),
      progress: gruppe({ barProgress: 'False', circularProgress: 'False', counterProgress: false }),
      navigation: gruppe({ navigationArrows: false, paginationDots: false }),
      ariaLabel: 'Birdiebook, 18 Bahnen',
    },
    { Slides: loop({ loopId: 'gp-bahnen', itemId: 'item' }, [bahnSlide()]) },
    'Birdiebook-Slider',
  );

/** Bedienleiste: Abschlag, Nummern 1–18, Vor/Zurück. Erscheint erst, wenn der Slider läuft (.is-ready). */
const bedienung = () =>
  el('div', 'birdiebook__controls', [
    el('fieldset', 'tee-switch', [
      t('legend', 'tee-switch__legend', 'Abschlag'),
      ...ABSCHLAEGE.map(([id, name], i) =>
        el('label', `tee-switch__option tee-switch__option--${id}`, [
          el('input', 'tee-switch__input', [], { attrs: { type: 'radio', name: 'birdiebook-abschlag', value: id, ...(i === 0 ? { checked: '' } : {}) } }),
          teeBadge(id, name),
        ]),
      ),
    ], { attrs: { 'data-tee-wahl': '' }, name: 'Abschlag-Wahl' }),
    el('nav', 'birdiebook__numbers', [
      el('ol', 'birdiebook__number-list', [
        loop({ loopId: 'gp-bahnen', itemId: 'item' }, [
          el('li', 'birdiebook__number-item', [
            t('button', 'birdiebook__number', NR, { attrs: { type: 'button', 'data-bahn-nr': NR, 'aria-label': `Bahn ${NR}` } }),
          ]),
        ]),
      ]),
    ], { attrs: { 'aria-label': 'Bahn wählen' }, name: 'Nummernleiste' }),
    el('div', 'birdiebook__pager', [
      el('button', 'birdiebook__step birdiebook__step--prev', [t('span', '', '‹ ', { attrs: { 'aria-hidden': 'true' } }), t('span', '', 'Vorherige Bahn', { attrs: { 'data-text': '' } })], { attrs: { type: 'button', 'data-bahn-prev': '' } }),
      t('span', 'birdiebook__counter', '1 / 18', { attrs: { 'data-bahn-zaehler': '', 'aria-hidden': 'true' } }),
      el('button', 'birdiebook__step birdiebook__step--next', [t('span', '', 'Nächste Bahn', { attrs: { 'data-text': '' } }), t('span', '', ' ›', { attrs: { 'aria-hidden': 'true' } })], { attrs: { type: 'button', 'data-bahn-next': '' } }),
    ]),
    el('p', 'visually-hidden', [], { attrs: { 'aria-live': 'polite', 'data-bahn-ansage': '' } }),
  ], { name: 'Bedienung' });

export const birdiebookKomponente = {
  key: 'Birdiebook',
  name: 'Birdiebook',
  description: 'Alle 18 Bahnen zum Durchwischen (EtchSliderPro) mit Abschlag-Wahl, Nummernleiste und Vor/Zurück. Daten: Spielbahnen (Loop gp-bahnen), Skript: mu-plugins/golfplatz-birdiebook.php.',
  properties: [],
  content: el('div', 'birdiebook', [emmp(ESP.wrapper, { backgroundColor: 'transparent' }, { Sliders_and_Controls: [slider()] }, 'Slider-Wrapper'), bedienung()], {
    attrs: { 'data-tee': 'gelb' },
    name: 'Birdiebook',
  }),
};

/** Kartenraster aller Bahnen (Übersicht auf /platz/). */
export const bahnenRaster = () =>
  el('ol', 'hole-grid', [
    loop({ loopId: 'gp-bahnen', itemId: 'item' }, [
      el('li', 'hole-card', [
        el('a', 'hole-card__link', [
          el('span', 'hole-card__map', bahngrafik({ bild: 'bild_karte', mitEntfernung: false })),
          el('span', 'hole-card__body', [
            t('span', 'hole-card__number', NR, { attrs: { 'aria-hidden': 'true' } }),
            t('span', 'hole-card__name', `Bahn ${NR}`),
            el('span', 'hole-card__facts', [
              text(`Par ${B('bahn_par_herren')}`),
              wenn('item.metabox.bahn_par_herren', [text(`/${B('bahn_par_damen')}`)], '!==', 'item.metabox.bahn_par_damen'),
              text(` · HCP ${B('bahn_hcp')} · ${B('laenge_gelb')} m`),
            ]),
          ]),
        ], { attrs: { href: '{item.permalink.relative}' } }),
      ]),
    ]),
  ], { name: 'Alle 18 Bahnen' });

/** Tee-Badge mit dynamischem Abschlag (Loop-Variable). */
const teeBadgeDyn = (tee, name) =>
  el('span', `tee-badge tee-badge--${tee}`, [el('span', 'tee-badge__dot', [], { attrs: { 'aria-hidden': 'true' } }), text(name)]);

// Scorekarte und Rating als CSS-Grid mit Tabellen-Rollen statt <table>: Der Etch-Builder legt um Loops
// <div style="display: contents">, die in einer echten Tabelle ungültig wären und sie im Builder zerlegen.
const rolle = (role, extra = {}) => ({ attrs: { role, ...extra } });
const zelle = (klasse, inhalt) => t('div', klasse, inhalt, rolle('cell'));

const summenZeile = (pfad) =>
  el('div', 'scorecard__row scorecard__row--sum', [
    t('div', 'scorecard__cell', `{${pfad}.label}`, rolle('rowheader')),
    zelle('scorecard__cell', `{${pfad}.par}`),
    el('div', 'scorecard__cell', [], rolle('cell')),
    loop({ target: `${pfad}.laengen`, itemId: 'l' }, [zelle('scorecard__cell', '{l.wert}')]),
  ], rolle('row'));

export const scorekarteKomponente = {
  key: 'Scorekarte',
  name: 'Scorekarte',
  description: 'Scorekarte: Loch, Par (Herren/Damen), HCP und Länge je Abschlag, Summen Out/In/Gesamt. Daten: {options.golfplatz.platz.scorekarte} aus mu-plugins/golfplatz-birdiebook.php.',
  properties: [],
  content: el('div', 'table-wrap', [
    el('div', 'scorecard', [
      el('div', 'scorecard__row scorecard__row--head', [
        t('div', 'scorecard__cell', 'Loch', rolle('columnheader')),
        el('div', 'scorecard__cell', [text('Par '), t('small', '', 'H/D')], rolle('columnheader')),
        t('div', 'scorecard__cell', 'HCP', rolle('columnheader')),
        loop({ target: `${PLATZ}.scorekarte.kopf`, itemId: 'k' }, [
          el('div', 'scorecard__cell', [teeBadgeDyn('{k.tee}', '{k.name}'), t('small', 'scorecard__gender', '{k.fuer}')], rolle('columnheader')),
        ]),
      ], rolle('row')),
      loop({ target: `${PLATZ}.scorekarte.bloecke`, itemId: 'b' }, [
        loop({ target: 'b.zeilen', itemId: 'z' }, [
          el('div', 'scorecard__row', [
            el('div', 'scorecard__cell scorecard__hole', [t('a', '', '{z.nr}', { attrs: { href: '{z.link}', 'aria-label': 'Bahn {z.nr}' } })], rolle('rowheader')),
            zelle('scorecard__cell', '{z.par}'),
            zelle('scorecard__cell', '{z.hcp}'),
            loop({ target: 'z.laengen', itemId: 'l' }, [zelle('scorecard__cell scorecard__len scorecard__len--{l.tee}', '{l.wert}')]),
          ], rolle('row')),
        ]),
        summenZeile('b.summe'),
      ]),
      summenZeile(`${PLATZ}.scorekarte.gesamt`),
    ], rolle('table', { 'aria-label': 'Scorekarte mit Par, Schwierigkeitsrang und Längen in Metern je Abschlag' })),
  ], { name: 'Scorekarte' }),
};

export const ratingKomponente = {
  key: 'Rating',
  name: 'Course & Slope Rating',
  description: 'Course & Slope Rating je Abschlag (Clubdaten › Platz & Abschläge), Gesamtlänge aus den Bahnen. Daten: {options.golfplatz.platz.rating}.',
  properties: [],
  content: wenn(`${PLATZ}.hat_rating`, [
    el('div', 'table-wrap', [
      el('div', 'rating-table', [
        el('div', 'rating-table__row rating-table__row--head', ['Abschlag', 'Für', 'CR', 'Slope', 'Par'].map((k) => t('div', 'rating-table__cell', k, rolle('columnheader'))), rolle('row')),
        loop({ target: `${PLATZ}.rating`, itemId: 'r' }, [
          el('div', 'rating-table__row', [
            el('div', 'rating-table__cell', [teeBadgeDyn('{r.tee}', '{r.name}'), t('span', 'rating-table__len', '{r.laenge}')], rolle('rowheader')),
            zelle('rating-table__cell', '{r.fuer}'),
            zelle('rating-table__cell', '{r.cr}'),
            zelle('rating-table__cell', '{r.slope}'),
            zelle('rating-table__cell', '{r.par}'),
          ], rolle('row')),
        ]),
      ], rolle('table', { 'aria-label': 'Course und Slope Rating je Abschlag' })),
    ], { name: 'Rating' }),
  ]),
};

// ---------- Spielvorgaben (/platz/spielvorgaben/) ----------
// Daten: {options.golfplatz.platz.spielvorgaben} (je Abschlag CR/Slope/Par und Tabelle HI → Spielvorgabe).
// Verhalten (Rechnen, Reiter) liefert das Skript in mu-plugins/golfplatz-birdiebook.php.
const SV = `${PLATZ}.spielvorgaben`;

export const spielvorgabenRechnerKomponente = {
  key: 'SpielvorgabenRechner',
  name: 'Spielvorgaben-Rechner',
  description: 'Rechner: Handicap-Index eingeben, Spielvorgabe je Abschlag erscheint sofort (WHS). Werte aus Clubdaten › Platz & Abschläge. Daten: {options.golfplatz.platz.spielvorgaben}.',
  properties: [],
  content: el('div', 'calculator', [
    t('h2', 'calculator__title', 'Spielvorgaben-Rechner'),
    t('label', 'calculator__label', 'Ihr Handicap-Index', { attrs: { for: 'hi-input' } }),
    el('div', 'calculator__input-row', [
      el('input', 'calculator__input', [], { attrs: { id: 'hi-input', type: 'text', inputmode: 'decimal', placeholder: 'z. B. 18,4 oder +1,2', autocomplete: 'off', 'data-calculator-input': '', 'aria-describedby': 'hi-hinweis' } }),
      t('span', 'calculator__hint', 'von +5,0 bis 54,0', { attrs: { id: 'hi-hinweis' } }),
    ]),
    t('p', 'calculator__error', 'Bitte einen Wert zwischen +5,0 und 54,0 eingeben.', { attrs: { 'data-calculator-error': '', hidden: '', role: 'alert' } }),
    el('div', 'calculator__result', [
      el('div', 'calculator__row calculator__row--head', ['Abschlag', 'Für', 'Spiel­vorgabe'].map((k) => t('div', 'calculator__cell', k, rolle('columnheader'))), rolle('row')),
      loop({ target: SV, itemId: 's' }, [
        el('div', 'calculator__row', [
          el('div', 'calculator__cell', [teeBadgeDyn('{s.tee}', '{s.name}')], rolle('rowheader')),
          zelle('calculator__cell', '{s.fuer}'),
          t('div', 'calculator__cell calculator__sv', '–', { attrs: { role: 'cell', 'data-sv': '{s.tee}', 'aria-live': 'polite' } }),
        ], { attrs: { role: 'row', 'data-cr': '{s.cr_zahl}', 'data-slope': '{s.slope}', 'data-par': '{s.par}' } }),
      ]),
    ], rolle('table', { 'aria-label': 'Ihre Spielvorgabe je Abschlag' })),
  ], { name: 'Spielvorgaben-Rechner', attrs: { 'data-calculator': '' } }),
};

const svPanel = (versteckt) =>
  el('div', 'tabs__panel', [
    el('div', 'hcp-table', [
      t('h3', 'hcp-table__title', '{s.fuer} · CR {s.cr} · Slope {s.slope} · Par {s.par}'),
      el('div', 'hcp-table__table', [
        el('div', 'hcp-table__row hcp-table__row--head', [t('div', 'hcp-table__cell', 'Handicap-Index', rolle('columnheader')), t('div', 'hcp-table__cell', 'Spielvorgabe', rolle('columnheader'))], rolle('row')),
        loop({ target: 's.zeilen', itemId: 'z' }, [
          el('div', 'hcp-table__row', [zelle('hcp-table__cell', '{z.hi}'), zelle('hcp-table__cell', '{z.sv}')], rolle('row')),
        ]),
      ], rolle('table', { 'aria-label': 'Spielvorgaben Abschlag {s.name}' })),
    ]),
  ], { attrs: { role: 'tabpanel', id: 'panel-{s.tee}', 'aria-labelledby': 'tab-{s.tee}', ...(versteckt ? { hidden: '' } : {}) } });

export const spielvorgabenTabellenKomponente = {
  key: 'SpielvorgabenTabellen',
  name: 'Spielvorgaben-Tabellen',
  description: 'Je Abschlag ein Reiter mit der Tabelle Handicap-Index → Spielvorgabe (WHS, +5,0 bis 54,0). Werte aus Clubdaten › Platz & Abschläge. Daten: {options.golfplatz.platz.spielvorgaben}.',
  properties: [],
  content: el('div', 'tabs', [
    el('div', 'tabs__list', [
      loop({ target: SV, itemId: 's' }, [
        el('button', 'tabs__tab', [teeBadgeDyn('{s.tee}', '{s.name} · {s.fuer}')], { attrs: { type: 'button', role: 'tab', id: 'tab-{s.tee}', 'aria-controls': 'panel-{s.tee}', 'aria-selected': '{s.selected}', tabindex: '{s.tabindex}' } }),
      ]),
    ], { attrs: { role: 'tablist', 'aria-label': 'Abschlag wählen' } }),
    loop({ target: SV, itemId: 's' }, [
      wenn('s.erster', [svPanel(false)]),
      wenn('s.erster', [svPanel(true)], 'isFalsy'),
    ]),
  ], { name: 'Spielvorgaben-Tabellen', attrs: { 'data-tabs': '' } }),
};
