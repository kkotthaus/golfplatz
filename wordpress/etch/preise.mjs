// Etch-Komponente „Preistabelle“: Preise einer Kategorie (Greenfee, Turnier-Greenfee, Kooperationen, Leihgeräte).
// Daten: {options.golfplatz.preise} aus mu-plugins/golfplatz-preise.php.
// Mit Wochentagen: kompakte Matrix wie auf dreibaeumen.de – je Tarif eine Zeile, Spalten Mo–Sa und So/Feiertag,
// Varianten („mit DGV-Ausweis „R““) als Unterzeile. Ohne Wochentage (Leihgeräte): einfache Liste.
//
// Bewusst keine <table>: Der Etch-Builder packt Loops und Bedingungen in <div style="display: contents">. In einer
// Tabelle sind solche divs ungültig und zerlegen sie im Builder. Deshalb CSS-Grid aus divs mit Tabellen-Rollen
// (role="table/row/columnheader/rowheader/cell") – für Screenreader weiterhin eine Tabelle.

import { el, t, text, loop, wenn } from './lib.mjs';

const PR = 'options.golfplatz.preise';
const rolle = (role, extra = {}) => ({ attrs: { role, ...extra } });

// Beschriftung einer Zeile: Titel, darunter Einheit bzw. Zusatz, falls vorhanden
const beschriftung = (x, mitEinheit) =>
  el('div', 'price-matrix__label', [
    text(`{${x}.titel}`),
    mitEinheit && wenn(`${x}.einheit`, [t('span', 'price-matrix__note', `{${x}.einheit}`)]),
    wenn(`${x}.zusatz`, [t('span', 'price-matrix__note', `{${x}.zusatz}`)]),
  ], rolle('rowheader'));

// Zellen Mo–So/Feiertag; leere Zelle = an diesem Tag nicht angeboten
const zellen = (x) =>
  loop({ target: `${x}.zellen`, itemId: 'c' }, [
    el('div', 'price-matrix__cell', [
      wenn('c.wert', [text('{c.wert}')]),
      wenn('c.wert', [t('span', '', '–', { attrs: { 'aria-hidden': 'true' } }), t('span', 'visually-hidden', 'nicht angeboten')], 'isFalsy'),
    ], rolle('cell')),
  ]);

const matrix = () =>
  el('div', 'table-wrap', [
    el('div', 'price-matrix', [
      el('div', 'price-matrix__head', [
        t('div', 'price-matrix__corner', 'Tarif', rolle('columnheader')),
        loop({ target: `${PR}.spalten`, itemId: 's' }, [
          el('div', 'price-matrix__day', [t('abbr', '', '{s.kurz}', { attrs: { title: '{s.lang}' } })], rolle('columnheader')),
        ]),
      ], rolle('row')),
      loop({ target: 'k.bloecke', itemId: 'b' }, [
        el('div', 'price-matrix__row', [beschriftung('b', true), zellen('b')], rolle('row')),
        loop({ target: 'b.unter', itemId: 'u' }, [
          el('div', 'price-matrix__row price-matrix__row--sub', [beschriftung('u', false), zellen('u')], rolle('row')),
        ]),
      ]),
    ], rolle('table', { 'aria-label': 'Preise {k.name}' })),
  ], { name: 'Preis-Matrix' });

const liste = () =>
  el('div', 'price-list', [
    el('div', 'price-list__head', [
      t('div', 'price-list__title', 'Leistung', rolle('columnheader')),
      t('div', 'price-list__amount', 'Preis', rolle('columnheader')),
    ], rolle('row')),
    loop({ target: 'k.zeilen', itemId: 'p' }, [
      el('div', 'price-list__row', [
        el('div', 'price-list__title', [text('{p.titel}'), wenn('p.zusatz', [t('span', 'price-list__note', '{p.zusatz}')])], rolle('rowheader')),
        t('div', 'price-list__amount', '{p.betrag}', rolle('cell')),
      ], rolle('row')),
    ]),
  ], { name: 'Preisliste', ...rolle('table', { 'aria-label': 'Preise {k.name}' }) });

export const preistabelleKomponente = {
  key: 'Preistabelle',
  name: 'Preistabelle',
  description: 'Preise einer Kategorie. Mit Wochentagen als kompakte Matrix (Mo–Sa, So/Feiertag; Varianten als Unterzeile), sonst als Liste. Eigenschaft: kategorie (greenfee, turnier, kooperationen, leihe). Daten: {options.golfplatz.preise}; Reihenfolge über „Reihenfolge“ am Preis, Tage aus „Gültig an“. Aufbau als CSS-Grid mit Tabellen-Rollen (Builder-tauglich).',
  properties: [{ key: 'kategorie', name: 'Kategorie', type: { primitive: 'string' }, default: 'greenfee' }],
  content: loop({ target: `${PR}.tabellen`, itemId: 'k' }, [
    wenn('k.key', [wenn('k.matrix', [matrix()]), wenn('k.matrix', [liste()], 'isFalsy')], '===', 'props.kategorie'),
  ]),
};

// Etch-Komponente „Preiskarten“: Preise einer Kategorie als Karten (Mitgliedschaft auf /mitgliedschaft/).
// Daten: {options.golfplatz.preise.tabellen[].karten} – Betrag, Einheit, Zusatz, Aufnahmegebühr, Leistungen, Hervorhebung.
export const preiskartenKomponente = {
  key: 'Preiskarten',
  name: 'Preiskarten',
  description: 'Preise einer Kategorie als Karten, z. B. Mitgliedschaften: Titel, Betrag („auf Anfrage“), Einheit, Zusatz, Aufnahmegebühr, Leistungen (eine je Zeile am Preis), hervorgehobene Karte mit „Beliebt“. Eigenschaften: kategorie (z. B. mitgliedschaft), ziel (Link der Buttons, z. B. #antrag). Daten: {options.golfplatz.preise}.',
  properties: [
    { key: 'kategorie', name: 'Kategorie', type: { primitive: 'string' }, default: 'mitgliedschaft' },
    { key: 'ziel', name: 'Link der Buttons', type: { primitive: 'string' }, default: '#antrag' },
  ],
  content: loop({ target: `${PR}.tabellen`, itemId: 'k' }, [
    wenn('k.key', [
      el('div', 'grid grid--2 price-cards', [
        loop({ target: 'k.karten', itemId: 'c' }, [
          el('article', 'price-card price-card--{c.mod}', [
            wenn('c.hervorheben', [t('p', 'price-card__badge', 'Beliebt')]),
            t('h2', 'price-card__title', '{c.titel}'),
            el('p', 'price-card__price', [
              t('span', 'price-card__amount', '{c.betrag}'),
              wenn('c.einheit', [text(' '), t('span', 'price-card__unit', '{c.einheit}')]),
            ]),
            wenn('c.zusatz', [t('p', 'price-card__note', '{c.zusatz}')]),
            wenn('c.aufnahme', [t('p', 'price-card__note', '{c.aufnahme}')]),
            wenn('c.hat_leistungen', [
              el('ul', 'price-card__list', [loop({ target: 'c.leistungen', itemId: 'l' }, [t('li', 'price-card__item', '{l.text}')])]),
            ]),
            t('a', 'btn btn--{c.button_mod} price-card__button', '{c.button}', { attrs: { href: '{props.ziel}' } }),
          ], { name: 'Preiskarte' }),
        ]),
      ], { name: 'Preiskarten' }),
    ], '===', 'props.kategorie'),
  ]),
};
