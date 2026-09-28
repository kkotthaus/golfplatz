// Aktuelles: Etch-Komponente „Newskarten“ und Bausteine für die Seite /news/ und das Template single-post (Aufbau wie im Prototyp).
// Daten: {options.golfplatz.news} und {this.golfplatz.news} aus mu-plugins/golfplatz-news.php. Filter per URL: ?kategorie=<slug>, ?seite=<n>.

import { el, t, text, loop, wenn, icon, postContent } from './lib.mjs';

export const NW = 'options.golfplatz.news';
const B = 'this.golfplatz.news';

/** Beitragskarte wie im Prototyp (prototype/src/components.mjs › newsCard); Beitragsbild, sonst Platzhalter-Verlauf. */
const newskarte = () =>
  el('article', 'news-card', [
    el('div', 'news-card__media', [
      wenn('n.hat_bild', [el('img', 'news-card__image', [], { attrs: { src: '{n.bild}', alt: '', loading: 'lazy' } })]),
      wenn('n.kategorie', [t('span', 'news-card__category', '{n.kategorie}')]),
    ], { attrs: { 'aria-hidden': 'true' } }),
    el('div', 'news-card__body', [
      el('p', 'news-card__meta', [
        t('time', '', '{n.datum}', { attrs: { datetime: '{n.datum_iso}' } }),
        wenn('n.mitglieder', [text(' · '), el('span', 'news-card__members', [icon('lock'), text(' Mitglieder')])]),
      ]),
      el('h3', 'news-card__title', [t('a', 'news-card__link', '{n.titel}', { attrs: { href: '{n.link}' } })]),
      t('p', 'news-card__teaser', '{n.teaser}'),
    ]),
  ], { name: 'Beitragskarte' });

export const newskartenKomponente = {
  key: 'Newskarten',
  name: 'Newskarten',
  description: 'Beiträge als Karten (Bild bzw. Verlauf, Kategorie, Datum, Titel, Teaser, Schloss bei „Nur für Mitglieder“). Eigenschaft liste: neueste (drei neueste, Startseite) oder alle (Übersicht /news/ mit ?kategorie= und ?seite=). Daten: {options.golfplatz.news} aus golfplatz-news.php.',
  properties: [{ key: 'liste', name: 'Liste (neueste oder alle)', type: { primitive: 'string' }, default: 'neueste' }],
  content: el('div', 'grid grid--3 news-grid', [
    wenn('props.liste', [loop({ target: `${NW}.neueste`, itemId: 'n' }, [newskarte()])], '===', '"neueste"'),
    wenn('props.liste', [loop({ target: `${NW}.beitraege`, itemId: 'n' }, [newskarte()])], '===', '"alle"'),
  ], { name: 'Beiträge' }),
};

/** Kategorien als Pillen über der Übersicht (wie Turnierkalender). */
export const kategorienNav = () =>
  wenn(`${NW}.hat_kategorien`, [
    el('nav', 'season-nav', [
      loop({ target: `${NW}.kategorien`, itemId: 'k' }, [
        wenn('k.aktiv', [t('a', 'season-nav__link', '{k.name}', { attrs: { href: '{k.link}' } })], 'isFalsy'),
        wenn('k.aktiv', [t('span', 'season-nav__link season-nav__link--aktiv', '{k.name}', { attrs: { 'aria-current': 'page' } })]),
      ]),
    ], { attrs: { 'aria-label': 'Nachrichten nach Kategorie filtern' } }),
  ]);

/** Seitenzahlen unter der Übersicht. */
export const seitenNav = () =>
  wenn(`${NW}.hat_seiten`, [
    el('nav', 'pagination', [
      loop({ target: `${NW}.seiten`, itemId: 's' }, [
        wenn('s.aktiv', [t('a', 'pagination__link', '{s.nr}', { attrs: { href: '{s.link}', 'aria-label': 'Seite {s.nr}' } })], 'isFalsy'),
        wenn('s.aktiv', [t('span', 'pagination__link pagination__link--aktiv', '{s.nr}', { attrs: { 'aria-current': 'page' } })]),
      ]),
    ], { attrs: { 'aria-label': 'Seiten' } }),
  ]);

export const keineBeitraege = () => wenn(`${NW}.leer`, [t('p', '', 'Zurzeit gibt es keine Nachrichten.')]);

/** Beitragsinhalt: Teaser, dann Volltext oder – bei „Nur für Mitglieder“ ohne Anmeldung – die Sperre. */
export const beitragInhalt = () => [
  wenn(`${B}.teaser`, [t('p', 'lead', `{${B}.teaser}`)]),
  wenn(`${B}.frei`, [postContent()]),
  wenn(`${B}.gesperrt`, [
    el('div', 'members-lock', [
      icon('lock'),
      el('div', '', [
        el('p', '', [t('strong', '', 'Dieser Beitrag ist nur für Mitglieder.')]),
        el('p', '', [t('a', 'btn btn--primary', 'Anmelden', { attrs: { href: '/mitglieder/' } })]),
      ]),
    ], { name: 'Mitglieder-Sperre' }),
  ]),
  el('p', '', [el('a', 'link-arrow', [text('Alle Nachrichten '), icon('arrow')], { attrs: { href: '/news/' } })]),
];
