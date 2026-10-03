// Club & Kontakt: Etch-Komponente „Personenkarten“ und Bausteine der Seite /club/ (Aufbau wie im Prototyp).
// Daten: {options.golfplatz.personen.listen} und {options.golfplatz.anfahrt} aus snippets/golfplatz-club.php.

import { el, t, text, raw, loop, wenn, icon } from './lib.mjs';

const PE = 'options.golfplatz.personen';
export const AN = 'options.golfplatz.anfahrt';

/** Personenkarte wie im Prototyp (prototype/src/components.mjs › personCard); Foto, falls als Beitragsbild gesetzt. */
const personenkarte = () =>
  el('article', 'person-card', [
    wenn('p.hat_bild', [el('img', 'person-card__avatar person-card__avatar--bild', [], { attrs: { src: '{p.bild}', alt: '', loading: 'lazy' } })]),
    wenn('p.hat_bild', [t('div', 'person-card__avatar', '{p.initialen}', { attrs: { 'aria-hidden': 'true' } })], 'isFalsy'),
    el('div', 'person-card__body', [
      t('h3', 'person-card__name', '{p.name}'),
      wenn('p.funktion', [t('p', 'person-card__role', '{p.funktion}')]),
      wenn('p.text', [t('p', 'person-card__text', '{p.text}')]),
      wenn('p.telefon', [t('a', 'person-card__link', '{p.telefon}', { attrs: { href: '{p.tel_href}' } })]),
      wenn('p.email', [t('a', 'person-card__link', '{p.email}', { attrs: { href: 'mailto:{p.email}' } })]),
    ]),
  ], { name: 'Personenkarte' });

export const personenkartenKomponente = {
  key: 'Personenkarten',
  name: 'Personenkarten',
  description: 'Personen einer Liste als Karten (Name, Funktion, Text, Telefon, E-Mail, Foto oder Initialen). Eigenschaft liste: vorstand, team (Betreibergesellschaft, Clubmanagement, Sekretariat, Service & Proshop, Greenkeeping), captains, golfschule, jugend; spalten: Modifier des Rasters (3 = grid--3, 1 = eine Karte). Daten: {options.golfplatz.personen} aus golfplatz-club.php; Reihenfolge über „Reihenfolge“ an der Person.',
  properties: [
    { key: 'liste', name: 'Liste', type: { primitive: 'string' }, default: 'vorstand' },
    { key: 'spalten', name: 'Spalten (3 oder 1)', type: { primitive: 'string' }, default: '3' },
  ],
  content: loop({ target: `${PE}.listen`, itemId: 'l' }, [
    wenn('l.key', [
      el('div', 'grid grid--{props.spalten} person-grid', [loop({ target: 'l.personen', itemId: 'p' }, [personenkarte()])], { name: 'Personen' }),
    ], '===', 'props.liste'),
  ]),
};

/** Anfahrt: Adresse aus den Clubdaten, Beschreibungen (falls gepflegt), Routenlink statt eingebetteter Karte. */
export const anfahrt = (club) => [
  el('div', 'prose', [
    t('h2', '', 'Anfahrt'),
    el('address', '', [text(club('club_name')), el('br', '', []), text(club('club_strasse')), el('br', '', []), text(club('club_plz') + ' ' + club('club_ort'))]),
    wenn(`${AN}.hat_auto`, [t('h3', 'h4', 'Mit dem Auto'), raw(`{${AN}.auto}`)]),
    wenn(`${AN}.hat_oepnv`, [t('h3', 'h4', 'Mit Bus und Bahn'), raw(`{${AN}.oepnv}`)]),
    el('p', '', [el('a', 'btn--primary btn--s', [icon('arrow'), text(' Route planen')], { attrs: { href: `{${AN}.route}`, rel: 'noopener' } })]),
  ], { name: 'Anfahrt' }),
  wenn(`${AN}.hat_karte`, [el('img', 'club-map', [], { attrs: { src: `{${AN}.karte}`, alt: 'Lageplan des Golfplatzes', loading: 'lazy' } })]),
  wenn(`${AN}.hat_karte`, [
    el('div', 'map-placeholder', [
      icon('pin'),
      t('p', '', 'Keine eingebettete Karte: Kartendienste laden wir erst nach Ihrer Einwilligung. Mit „Route planen“ öffnet sich die Route in Google Maps.'),
    ], { attrs: { role: 'note' } }),
  ], 'isFalsy'),
];
