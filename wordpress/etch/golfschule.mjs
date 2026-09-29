// Golfschule: Etch-Komponente „Kurskarten“ und Bausteine für die Seite /golfschule/ und das Template single-kurs (Aufbau wie im Prototyp).
// Daten: {options.golfplatz.kurse} und {this.golfplatz.kurs} aus snippets/golfplatz-golfschule.php.

import { el, t, text, loop, wenn, icon, club, telHref, postContent } from './lib.mjs';

const KU = 'options.golfplatz.kurse';
const K = 'this.golfplatz.kurs';

/** Eckdaten eines Kurses (Preis, Dauer, Teilnehmer) wie im Prototyp; x = Datenpfad (k bzw. this.golfplatz.kurs). */
const fakten = (x) =>
  el('dl', 'course-card__facts', [
    el('div', '', [t('dt', '', 'Preis'), t('dd', '', `{${x}.preis}`)]),
    wenn(`${x}.hat_dauer`, [el('div', '', [t('dt', '', 'Dauer'), t('dd', '', `{${x}.dauer}`)])]),
    wenn(`${x}.hat_max`, [el('div', '', [t('dt', '', 'Teilnehmer'), t('dd', '', `{${x}.max}`)])]),
  ]);

const termine = (x) => wenn(`${x}.hat_termine`, [el('p', 'course-card__dates', [t('strong', '', 'Termine: '), text(`{${x}.termine}`)])]);

const kurskarte = () =>
  el('article', 'course-card', [
    wenn('k.typ', [t('p', 'course-card__type', '{k.typ}')]),
    el('h3', 'course-card__title', [t('a', 'course-card__link', '{k.titel}', { attrs: { href: '{k.link}' } })]),
    t('p', 'course-card__text', '{k.text}'),
    fakten('k'),
    termine('k'),
    wenn('k.hat_trainer', [el('p', 'course-card__trainer', [icon('user'), text(' mit {k.trainer}')])]),
  ], { name: 'Kurskarte' });

export const kurskartenKomponente = {
  key: 'Kurskarten',
  name: 'Kurskarten',
  description: 'Kurse der Golfschule als Karten (Kursart, Titel mit Link zur Kursseite, Text, Preis, Dauer, Teilnehmer, künftige Termine, Golflehrer). Kurse, deren Termine alle vorbei sind, erscheinen nicht. Daten: {options.golfplatz.kurse} aus golfplatz-golfschule.php; Reihenfolge über „Reihenfolge“ am Kurs.',
  properties: [],
  content: el('div', 'course-list', [
    wenn(`${KU}.hat_kurse`, [el('div', 'grid grid--2', [loop({ target: `${KU}.liste`, itemId: 'k' }, [kurskarte()])], { name: 'Kurse' })]),
    wenn(`${KU}.hat_kurse`, [t('p', '', 'Zurzeit sind keine Kurse ausgeschrieben. Sprechen Sie uns gern an.')], 'isFalsy'),
  ], { name: 'Kurskarten' }),
};

/** Hinweis zur Anmeldung mit Telefon und E-Mail aus den Clubdaten (Betreff „Kursanmeldung“). */
export const anmeldung = () =>
  el('p', 'note', [
    icon('phone'),
    text(' Anmeldung unter '),
    t('a', '', club('club_telefon'), { attrs: { href: telHref('club_telefon') } }),
    text(' oder '),
    t('a', '', club('club_email'), { attrs: { href: 'mailto:' + club('club_email') + '?subject=Kursanmeldung' } }),
    text('.'),
  ], { name: 'Anmeldung' });

/** Inhalt der Kursseite (Template single-kurs). */
export const kursInhalt = () => [
  el('div', 'course-card course-card--detail', [
    fakten(K),
    termine(K),
    wenn(`${K}.hat_trainer`, [el('p', 'course-card__trainer', [icon('user'), text(` mit {${K}.trainer}`)])]),
  ], { name: 'Eckdaten' }),
  el('div', 'prose', [postContent()], { name: 'Beschreibung' }),
  wenn(`${K}.hat_anmeldung`, [el('p', 'note', [icon('phone'), text(` {${K}.anmeldung}`)])]),
  wenn(`${K}.hat_anmeldung`, [anmeldung()], 'isFalsy'),
  el('p', '', [el('a', 'link-arrow', [text('Alle Kurse der Golfschule '), icon('arrow')], { attrs: { href: '/golfschule/' } })]),
];
