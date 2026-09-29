// Mannschaften & Ligaspiele: Bausteine für die Templates archive-mannschaft, single-mannschaft und single-spielbericht.
// Daten aus snippets/golfplatz-mannschaften.php: {options.golfplatz.mannschaften} (Übersicht, alle Ligaspiele),
// {this.golfplatz.team} (eine Mannschaft) und {this.golfplatz.bericht} (ein Spielbericht). Aufbau wie im Prototyp.
//
// Ligaspiel-Tabellen als CSS-Grid aus divs mit Tabellen-Rollen (Konvention, wie Preistabelle und Scorekarte).

import { el, t, text, loop, wenn, icon } from './lib.mjs';

export const MS = 'options.golfplatz.mannschaften';
const TEAM = 'this.golfplatz.team';
export const BERICHT = 'this.golfplatz.bericht';

const zelle = (klasse, kinder) => el('div', 'match-table__cell' + (klasse ? ' ' + klasse : ''), kinder, { attrs: { role: 'cell' } });
const kopf = (label) => t('div', 'match-table__cell', label, { attrs: { role: 'columnheader' } });

/** Ligaspiele als Tabelle. target: Liste von Spielen; mitMannschaft: Spalte „Mannschaft“ (Übersicht) oder nicht (Mannschaftsseite). */
export const spielTabelle = (target, label, mitMannschaft = true) =>
  el('div', 'table-wrap', [
    el('div', 'match-table match-table--grid' + (mitMannschaft ? '' : ' match-table--kompakt'), [
      el('div', 'match-table__row match-table__row--head', [
        kopf('Datum'),
        mitMannschaft && kopf('Mannschaft'),
        kopf('Spieltag'),
        kopf('Spielort'),
        kopf('Ergebnis'),
      ], { attrs: { role: 'row' } }),
      loop({ target, itemId: 's' }, [
        el('div', 'match-table__row match-table__row--{s.mod}', [
          zelle('match-table__date', [
            t('time', '', '{s.datum}', { attrs: { datetime: '{s.datum_iso}' } }),
            wenn('s.uhrzeit', [t('span', 'match-table__time', '{s.uhrzeit}')]),
          ]),
          mitMannschaft && zelle('', [t('a', 'match-table__team', '{s.mannschaft}', { attrs: { href: '{s.mannschaft_link}' } })]),
          zelle('', [text('{s.spieltag}')]),
          zelle('', [
            // Spielort mit Link zur Website des Gastclubs (Mannschaften → Gastclubs), sonst nur der Name
            wenn('s.hat_spielort_link', [t('a', 'match-table__venue', '{s.spielort}', { attrs: { href: '{s.spielort_link}', rel: 'noopener' } })]),
            wenn('s.hat_spielort_link', [text('{s.spielort}')], 'isFalsy'),
            wenn('s.heim', [text(' '), t('span', 'badge badge--home', 'Heimspiel')]),
          ]),
          zelle('', [
            wenn('s.hat_ergebnis', [t('strong', '', '{s.ergebnis}')]),
            wenn('s.hat_ergebnis', [t('span', '', '–', { attrs: { 'aria-hidden': 'true' } }), t('span', 'visually-hidden', 'noch kein Ergebnis')], 'isFalsy'),
            wenn('s.hat_bericht', [el('br', '', []), t('a', 'match-table__report', 'Spielbericht', { attrs: { href: '{s.bericht_link}' } })]),
          ]),
        ], { attrs: { role: 'row' } }),
      ]),
    ], { attrs: { role: 'table', 'aria-label': label }, name: 'Ligaspiele' }),
  ]);

/** Übersicht: Mannschaftskarten in fester Reihenfolge. */
export const teamKarten = () =>
  el('div', 'grid grid--3 team-grid', [
    loop({ target: `${MS}.liste`, itemId: 'm' }, [
      el('article', 'team-card', [
        t('p', 'team-card__league', '{m.liga}'),
        el('h2', 'team-card__title', [t('a', 'team-card__link', '{m.titel}', { attrs: { href: '{m.link}' } })]),
        wenn('m.hat_spielfuehrer', [t('p', 'team-card__captain', 'Spielführer: {m.spielfuehrer}')]),
        t('p', 'team-card__next', '{m.naechstes}'),
      ], { name: 'Mannschaftskarte' }),
    ]),
  ], { name: 'Mannschaften' });

/** Übersicht: alle Ligaspiele (früher als eigene Seite /mannschaften/ligaspiele/ geplant, die URL kollidiert mit den Mannschaften). */
export const alleLigaspiele = () => [
  t('h2', '', `Alle Ligaspiele · Saison {${MS}.saison}`),
  t('p', '', `Termine und Ergebnisse aller Mannschaften. Heimspiele sind hervorgehoben. Die Daten kommen vom {${MS}.verband}.`),
  wenn(`${MS}.hat_saisons`, [
    el('nav', 'season-nav', [
      t('span', 'season-nav__label', 'Saison:'),
      loop({ target: `${MS}.saisons`, itemId: 'j' }, [
        wenn('j.aktiv', [t('a', 'season-nav__link', '{j.jahr}', { attrs: { href: '{j.link}' } })], 'isFalsy'),
        wenn('j.aktiv', [t('span', 'season-nav__link season-nav__link--aktiv', '{j.jahr}', { attrs: { 'aria-current': 'page' } })]),
      ]),
    ], { attrs: { 'aria-label': 'Saison wählen' } }),
  ]),
  t('h3', 'spacer-top', 'Kommende Spiele'),
  wenn(`${MS}.hat_kommende`, [spielTabelle(`${MS}.kommende`, 'Kommende Ligaspiele')]),
  wenn(`${MS}.hat_kommende`, [t('p', '', 'Keine weiteren Spiele in dieser Saison.')], 'isFalsy'),
  wenn(`${MS}.hat_vergangene`, [t('h3', 'spacer-top', 'Vergangene Spiele'), spielTabelle(`${MS}.vergangene`, 'Vergangene Ligaspiele')]),
];

/** Eine Saison auf der Mannschaftsseite: aufklappbar, Tabelle der Spiele, Link zur Ligatabelle beim Verband. */
const saison = (offen) =>
  el('details', 'season', [
    el('summary', 'season__summary', [t('h3', 'season__title', '{z.titel}')]),
    el('div', 'season__body', [
      spielTabelle('z.spiele', 'Ligaspiele {this.title} {z.jahr}', false),
      wenn('z.hat_verband_link', [el('p', 'season__source', [t('a', 'link-arrow', `Tabelle beim {${MS}.verband}`, { attrs: { href: '{z.verband_link}', rel: 'noopener' } })])]),
    ]),
  ], { attrs: offen ? { open: '' } : {}, name: 'Saison' });

/** Mannschaftsseite, linke Spalte: Foto, Ligaspiele, Spielberichte. */
export const teamInhalt = () =>
  el('div', '', [
    wenn(`${TEAM}.hat_foto`, [el('img', 'team-photo team-photo--bild', [], { attrs: { src: `{${TEAM}.foto}`, alt: `{${TEAM}.foto_alt}` } })]),
    // Ligaspiele je Saison, die neueste aufgeklappt
    wenn(`${TEAM}.hat_spiele`, [
      t('h2', 'h3', 'Ligaspiele'),
      loop({ target: `${TEAM}.saisons`, itemId: 'z' }, [wenn('z.offen', [saison(true)]), wenn('z.offen', [saison(false)], 'isFalsy')]),
    ]),
    wenn(`${TEAM}.hat_berichte`, [
      t('h2', 'h3 spacer-top', 'Spielberichte'),
      el('ul', 'report-list', [
        loop({ target: `${TEAM}.berichte`, itemId: 'b' }, [
          el('li', 'report-list__item', [t('a', 'report-list__link', '{b.titel}', { attrs: { href: '{b.link}' } }), t('span', 'report-list__meta', '{b.datum}')]),
        ]),
      ]),
    ]),
  ], { name: 'Spiele & Berichte' });

/** Mannschaftsseite, Seitenkasten: Spielführer und Kader (nur Spieler mit Einwilligung). */
export const teamKader = () =>
  wenn(`${TEAM}.hat_personen`, [el('aside', 'side-box', [
    wenn(`${TEAM}.hat_spielfuehrer`, [t('h2', 'side-box__title', 'Spielführer'), el('p', 'side-box__captain', [icon('user'), text(` {${TEAM}.spielfuehrer}`)])]),
    wenn(`${TEAM}.hat_kader`, [
      t('h2', 'side-box__title', 'Kader'),
      el('ul', 'roster', [
        loop({ target: `${TEAM}.kader`, itemId: 'k' }, [
          el('li', 'roster__item', [text('{k.name}'), wenn('k.ist_spielfuehrer', [text(' '), t('span', 'badge', 'Spielführer')])]),
        ]),
      ]),
    ]),
    t('p', 'small', 'Es erscheinen nur Spieler, die der Veröffentlichung zugestimmt haben.'),
  ], { attrs: { 'aria-label': 'Spielführer und Kader' }, name: 'Kader' })]);

/** Spielbericht: Meta-Zeile, Galerie und Rücklink zur Mannschaft (der Text kommt aus dem Beitragsinhalt). */
export const berichtMeta = () => wenn(`${BERICHT}.meta`, [t('p', 'article-meta', `{${BERICHT}.meta}`)]);
export const berichtBilder = () =>
  wenn(`${BERICHT}.hat_bilder`, [
    el('div', 'report-gallery', [
      loop({ target: `${BERICHT}.bilder`, itemId: 'i' }, [el('img', 'report-gallery__image', [], { attrs: { src: '{i.url}', alt: '{i.alt}', loading: 'lazy' } })]),
    ], { name: 'Bilder' }),
  ]);
export const berichtRuecklink = () =>
  wenn(`${BERICHT}.hat_mannschaft`, [
    el('p', '', [el('a', 'link-arrow', [text(`Zur Mannschaft {${BERICHT}.mannschaft} `), icon('arrow')], { attrs: { href: `{${BERICHT}.mannschaft_link}` } })]),
  ]);
