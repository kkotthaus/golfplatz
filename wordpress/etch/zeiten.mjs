// Etch-Komponenten „Öffnungszeiten“ (ein Bereich) und „Öffnungszeiten alle“ (Karten aller Bereiche).
// Daten: {options.golfplatz.zeiten} aus mu-plugins/golfplatz-platzstatus.php – Liste der Bereiche
// (sekretariat, range, kurzspiel, proshop, restaurant) mit Zustand jetzt, Standardzeiten, kommenden Ausnahmen, Hinweis.
// Markup und Klassen wie der frühere Shortcode [golfplatz_oeffnungszeiten].

import { el, t, text, loop, wenn } from './lib.mjs';

const Z = 'options.golfplatz.zeiten';

/**
 * Inhalt eines Bereichs (z = Loop-Variable).
 * klasse: Modifier am Block, liste: Modifier der Zeitenliste, titel: 'immer' | 'prop' (props.titel === "1") | null
 */
const bereich = ({ klasse, liste, titel }) =>
  el('div', `opening-hours${klasse ? ' ' + klasse : ''}`, [
    titel === 'immer' && t('h3', 'opening-hours__title', '{z.name}'),
    titel === 'prop' && wenn('props.titel', [t('h3', 'opening-hours__title', '{z.name}')], '===', '"1"'),
    wenn('z.hat_status', [t('p', 'opening-hours__status opening-hours__status--{z.status_mod}', '{z.status_text}')]),
    wenn('z.hat_standard', [
      el('dl', `hours hours--${liste}`, [
        loop({ target: 'z.standard', itemId: 's' }, [
          el('div', 'hours__row', [t('dt', 'hours__days', '{s.tage}'), t('dd', 'hours__time', '{s.zeit}')]),
        ]),
      ]),
    ]),
    wenn('z.hat_ausnahmen', [
      el('ul', 'opening-hours__exceptions', [
        loop({ target: 'z.ausnahmen', itemId: 'a' }, [
          el('li', 'opening-hours__exception', [t('strong', '', '{a.titel}'), text(' {a.text}')]),
        ]),
      ]),
    ]),
    wenn('z.hinweis', [t('p', 'opening-hours__note', '{z.hinweis}')]),
  ]);

export const oeffnungszeitenKomponente = {
  key: 'Oeffnungszeiten',
  name: 'Öffnungszeiten',
  description: 'Öffnungszeiten eines Bereichs: Zustand jetzt (Sperren vor Öffnungszeiten), Standardzeiten, kommende Ausnahmen, Hinweis. Daten: {options.golfplatz.zeiten}. Eigenschaften: bereich (sekretariat, range, kurzspiel, proshop, restaurant), variante (Modifier opening-hours--…), liste (compact, footer, light), titel ("1" = Bereichsname als Überschrift).',
  properties: [
    { key: 'bereich', name: 'Bereich', type: { primitive: 'string' }, default: 'sekretariat' },
    { key: 'variante', name: 'Variante', type: { primitive: 'string' }, default: 'compact' },
    { key: 'liste', name: 'Zeitenliste', type: { primitive: 'string' }, default: 'compact' },
    { key: 'titel', name: 'Überschrift zeigen (1)', type: { primitive: 'string' }, default: '0' },
  ],
  content: loop({ target: Z, itemId: 'z' }, [
    wenn('z.key', [bereich({ klasse: 'opening-hours--{props.variante}', liste: '{props.liste}', titel: 'prop' })], '===', 'props.bereich'),
  ]),
};

export const oeffnungszeitenAlleKomponente = {
  key: 'OeffnungszeitenAlle',
  name: 'Öffnungszeiten alle',
  description: 'Alle Bereiche als Karten (Anker #oeffnungszeiten), gesperrte bzw. geschlossene Bereiche markiert. Daten: {options.golfplatz.zeiten}.',
  properties: [],
  content: el('div', 'facility-hours', [
    loop({ target: Z, itemId: 'z' }, [
      el('article', 'facility-card {z.karte_mod}', [bereich({ klasse: '', liste: 'compact', titel: 'immer' })]),
    ]),
  ], { attrs: { id: 'oeffnungszeiten' }, name: 'Öffnungszeiten alle' }),
};
