// Etch-Komponente „Zählkarte“: Handicap-Index und Abschlag wählen, Vorgabeschläge je Loch sehen, Schläge eintragen,
// Ergebnis brutto/netto (Schläge und Stableford-Punkte).
// Daten: {options.golfplatz.platz.spielvorgaben} (Abschläge mit CR/Slope/Par) und {options.golfplatz.platz.zaehlkarte}
// (Löcher mit Par Herren/Damen und HCP) aus mu-plugins/golfplatz-birdiebook.php.
// Rechnen, Speichern im Browser und Zurücksetzen: prototype/assets/js/zaehlkarte.js (liest alles aus den data-Attributen).
// Tabelle als CSS-Grid mit Tabellen-Rollen wie Scorekarte und Preistabelle.

import { el, t, loop, club } from './lib.mjs';

const PLATZ = 'options.golfplatz.platz';
const rolle = (role, extra = {}) => ({ attrs: { role, ...extra } });
const wert = (name, inhalt = '–', extra = {}) => t('div', `score-calc__cell score-calc__cell--${name}`, inhalt, { attrs: { role: 'cell', [`data-sc-${name}`]: '', ...extra } });
const KOPF = [['Loch'], ['Par'], ['HCP', 'Schwierigkeit des Lochs (1 = am schwersten)'], ['Vorgabe', 'Vorgabeschläge auf diesem Loch'], ['Schläge'], ['Netto'], ['Pkt. brutto', 'Stableford-Punkte brutto'], ['Pkt. netto', 'Stableford-Punkte netto']];

const summenZeile = (key, label) =>
  el('div', 'score-calc__row score-calc__row--sum', [
    t('div', 'score-calc__cell', label, rolle('rowheader')),
    wert('par', ''),
    t('div', 'score-calc__cell', '', rolle('cell')),
    wert('vorgabe'),
    wert('schlaege'),
    wert('netto'),
    wert('pb'),
    wert('pn'),
  ], { attrs: { role: 'row', 'data-sc-summe': key } });

const ergebnis = (key, label) =>
  el('div', `score-calc__stat score-calc__stat--${key}`, [
    t('dt', 'score-calc__stat-label', label),
    t('dd', 'score-calc__stat-value', '–', { attrs: { 'data-sc-ergebnis': key } }),
  ]);

export const zaehlkarteKomponente = {
  key: 'Zaehlkarte',
  name: 'Zählkarte',
  description: 'Zählkarte zum Mitschreiben: Handicap-Index und Abschlag wählen → Spielvorgabe und Vorgabeschläge je Loch; Schläge eintragen → Netto je Loch, Stableford-Punkte brutto/netto, Summen Out/In/Gesamt. Eingaben bleiben im Browser. Daten: {options.golfplatz.platz.spielvorgaben}, {options.golfplatz.platz.zaehlkarte}.',
  properties: [],
  content: el('div', 'score-calc', [
    el('div', 'score-calc__settings', [
      el('div', 'score-calc__field', [
        t('label', 'score-calc__label', 'Handicap-Index', { attrs: { for: 'sc-hi' } }),
        el('input', 'score-calc__input', [], { attrs: { id: 'sc-hi', type: 'text', inputmode: 'decimal', placeholder: 'z. B. 18,4', autocomplete: 'off', 'data-sc-hi': '', 'aria-describedby': 'sc-hi-hinweis' } }),
        t('span', 'score-calc__hint', 'von +5,0 bis 54,0', { attrs: { id: 'sc-hi-hinweis' } }),
      ]),
      el('div', 'score-calc__field', [
        t('label', 'score-calc__label', 'Abschlag', { attrs: { for: 'sc-tee' } }),
        el('select', 'score-calc__select', [
          loop({ target: `${PLATZ}.spielvorgaben`, itemId: 's' }, [
            t('option', '', '{s.name} · {s.fuer}', { attrs: { value: '{s.tee}', 'data-cr': '{s.cr_zahl}', 'data-slope': '{s.slope}', 'data-par': '{s.par}', 'data-geschlecht': '{s.geschlecht}' } }),
          ]),
        ], { attrs: { id: 'sc-tee', 'data-sc-tee': '' } }),
      ]),
      el('div', 'score-calc__field score-calc__field--sv', [
        t('span', 'score-calc__label', 'Ihre Spielvorgabe', { attrs: { id: 'sc-sv-label' } }),
        t('output', 'score-calc__sv', '–', { attrs: { 'data-sc-sv': '', 'aria-labelledby': 'sc-sv-label', 'aria-live': 'polite' } }),
      ]),
    ]),
    el('div', 'score-calc__shared', [
      t('p', 'score-calc__shared-text', 'Sie sehen eine geteilte Runde. Sobald Sie etwas ändern, wird sie als Ihre Runde gespeichert.'),
      t('button', 'btn btn--outline score-calc__own', 'Meine eigene Runde anzeigen', { attrs: { type: 'button', 'data-sc-eigene': '' } }),
    ], { attrs: { 'data-sc-geteilt': '', hidden: '' } }),
    t('p', 'calculator__error', 'Bitte einen Handicap-Index zwischen +5,0 und 54,0 eingeben.', { attrs: { 'data-sc-error': '', hidden: '', role: 'alert' } }),
    el('div', 'table-wrap', [
      el('div', 'score-calc__table', [
        el('div', 'score-calc__row score-calc__row--head', KOPF.map(([k, titel]) =>
          titel ? el('div', 'score-calc__cell', [t('abbr', '', k, { attrs: { title: titel } })], rolle('columnheader')) : t('div', 'score-calc__cell', k, rolle('columnheader')),
        ), rolle('row')),
        loop({ target: `${PLATZ}.zaehlkarte.bloecke`, itemId: 'b' }, [
          loop({ target: 'b.zeilen', itemId: 'z' }, [
            el('div', 'score-calc__row', [
              t('div', 'score-calc__cell score-calc__hole', '{z.nr}', rolle('rowheader')),
              wert('par', '{z.par}'),
              t('div', 'score-calc__cell', '{z.hcp}', rolle('cell')),
              wert('vorgabe'),
              el('div', 'score-calc__cell score-calc__cell--eingabe', [
                el('input', 'score-calc__strokes', [], { attrs: { type: 'number', inputmode: 'numeric', min: '1', max: '20', step: '1', 'aria-label': 'Schläge Loch {z.nr}', 'data-sc-schlaege': '' } }),
              ], rolle('cell')),
              wert('netto'),
              wert('pb'),
              wert('pn'),
            ], { attrs: { role: 'row', 'data-sc-loch': '{z.nr}', 'data-hcp': '{z.hcp}', 'data-par-herren': '{z.par_herren}', 'data-par-damen': '{z.par_damen}' } }),
          ]),
          // Out nach Loch 9, In nach Loch 18
          el('div', 'score-calc__row score-calc__row--sum', [
            t('div', 'score-calc__cell', '{b.label}', rolle('rowheader')),
            wert('par', ''),
            t('div', 'score-calc__cell', '', rolle('cell')),
            wert('vorgabe'),
            wert('schlaege'),
            wert('netto'),
            wert('pb'),
            wert('pn'),
          ], { attrs: { role: 'row', 'data-sc-summe': '{b.key}' } }),
        ]),
        summenZeile('gesamt', 'Gesamt'),
      ], rolle('table', { 'aria-label': 'Zählkarte: Vorgabe, Schläge und Punkte je Loch' })),
    ]),
    el('div', 'score-calc__footer', [
      el('dl', 'score-calc__summary', [
        ergebnis('gespielt', 'Gespielte Löcher'),
        ergebnis('brutto', 'Brutto'),
        ergebnis('netto', 'Netto'),
        ergebnis('pb', 'Stableford brutto'),
        ergebnis('pn', 'Stableford netto'),
      ], { attrs: { 'aria-live': 'polite' } }),
      el('div', 'score-calc__actions', [
        t('button', 'btn btn--primary score-calc__share', 'Ergebnis teilen', { attrs: { type: 'button', 'data-sc-teilen': '' } }),
        t('button', 'btn btn--outline score-calc__download', 'Als HTML speichern', { attrs: { type: 'button', 'data-sc-html': '' } }),
        t('button', 'btn btn--outline score-calc__reset', 'Schläge löschen', { attrs: { type: 'button', 'data-sc-reset': '' } }),
      ]),
    ]),
    t('p', 'score-calc__status', '', { attrs: { 'data-sc-status': '', role: 'status', hidden: '' } }),
    t('p', 'score-calc__note', 'Vorgabeschläge nach Loch-HCP verteilt. Netto = Schläge − Vorgabe. Stableford: Par = 2 Punkte, je Schlag besser +1, ab zwei über Par (netto: über Par plus Vorgabe) 0 Punkte. Ihre Eingaben bleiben nur in diesem Browser gespeichert.'),
  ], { name: 'Zählkarte', attrs: { 'data-score-calc': '', 'data-sc-club': club('club_name') } }),
};
