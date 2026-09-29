// Etch-Komponenten „Turnierkalender“, „Platzbelegung“ und „Turnierergebnisse“ (Seite /turniere/).
// Daten: {options.golfplatz.turniere} aus mu-plugins/golfplatz-turniere.php – stündlich aus PC CADDIE://online gelesen.
// Anmeldung, Startlisten und Ergebnislisten bleiben bei PC CADDIE (Links); Namen von Spielern übernimmt die Website nicht.
// Turnierkalender und Ergebnisse: nur der Heimatclub. Platzbelegung: Heimatclub (erste Spalte) und GOLFHOCHZEHN-Partnerclubs als Tabelle Tag × Club
// zur Planung eines Besuchs. Filter per URL: ?kategorie=s (Kalender), ?jahr=2025 (Ergebnisse), ?ab=JJJJ-MM-TT (Platzbelegung).
// Tabellen als CSS-Grid aus divs mit Tabellen-Rollen (Konvention, wie Preistabelle und Ligaspiele).

import { el, t, text, loop, wenn } from './lib.mjs';

const TU = 'options.golfplatz.turniere';
const PB = `${TU}.belegung`;

/** Pillen-Navigation (Kategorie bzw. Jahr), aktive Pille als Text mit aria-current. */
const pillen = (target, label, feld) =>
  el('nav', 'season-nav', [
    loop({ target, itemId: 'f' }, [
      wenn('f.aktiv', [t('a', 'season-nav__link', `{f.${feld}}`, { attrs: { href: '{f.link}' } })], 'isFalsy'),
      wenn('f.aktiv', [t('span', 'season-nav__link season-nav__link--aktiv', `{f.${feld}}`, { attrs: { 'aria-current': 'page' } })]),
    ]),
  ], { attrs: { 'aria-label': label } });

const kategorien = (x) => wenn(`${x}.hat_kategorien`, [el('p', 'tournament__tags', [loop({ target: `${x}.kategorien`, itemId: 'k' }, [t('span', 'badge badge--{k.key}', '{k.name}')])])]);

const turnier = () =>
  el('article', 'tournament tournament--{tu.status_mod}', [
    el('div', 'tournament__date', [
      t('span', 'tournament__weekday', '{tu.wochentag}'),
      t('span', 'tournament__day', '{tu.tag}'),
      t('span', 'tournament__month', '{tu.monat}'),
    ], { attrs: { 'aria-hidden': 'true' } }),
    el('div', 'tournament__body', [
      el('h4', 'tournament__title', [t('span', 'visually-hidden', '{tu.datum_lang}: '), text('{tu.titel}')]),
      // Absage/Verschiebung immer als Text (nicht nur über Durchstreichen oder Farbe)
      wenn('tu.status_text', [t('p', 'tournament__status tournament__status--{tu.status_mod}', '{tu.status_text}')]),
      wenn('tu.untertitel', [t('p', 'tournament__subtitle', '{tu.untertitel}')]),
      wenn('tu.infos', [t('p', 'tournament__info', '{tu.infos}')]),
      kategorien('tu'),
      wenn('tu.anmeldeschluss', [t('p', 'tournament__deadline', 'Anmeldeschluss: {tu.anmeldeschluss}')]),
      wenn('tu.plaetze', [t('p', 'tournament__places', '{tu.plaetze}')]),
    ]),
    el('div', 'tournament__actions', [
      wenn('tu.hat_anmeldung', [t('a', 'btn btn--primary tournament__button', 'Anmelden', { attrs: { href: '{tu.link_anmeldung}', rel: 'noopener' } })]),
      wenn('tu.ausgebucht', [t('span', 'tournament__full', 'ausgebucht')]),
      wenn('tu.hat_ausschreibung', [t('a', 'tournament__link', 'Ausschreibung (PDF)', { attrs: { href: '{tu.link_ausschreibung}', rel: 'noopener' } })]),
      wenn('tu.hat_details', [t('a', 'tournament__link', 'Details', { attrs: { href: '{tu.link_details}', rel: 'noopener' } })]),
    ]),
  ], { attrs: { role: 'listitem' }, name: 'Turnier' });

export const turnierkalenderKomponente = {
  key: 'Turnierkalender',
  name: 'Turnierkalender',
  description: 'Kommende Turniere des Heimatclubs aus PC CADDIE, nach Monat, Filter nach Kategorie (?kategorie=d|h|s|j|c), „Heute auf dem Platz“, Anmeldeschluss, freie Plätze und Links zu Anmeldung, Ausschreibung und Details bei PC CADDIE. Daten: {options.golfplatz.turniere} (golfplatz-turniere.php, stündlicher Abgleich).',
  properties: [],
  content: el('div', 'tournaments', [
    wenn(`${TU}.hat_heute`, [
      el('div', 'tournaments__today', [
        t('p', 'tournaments__today-label', 'Heute auf dem Platz'),
        loop({ target: `${TU}.heute`, itemId: 'h' }, [el('p', 'tournaments__today-item', [t('strong', '', '{h.titel}'), wenn('h.infos', [text(' · {h.infos}')]), wenn('h.status_text', [text(' · '), t('strong', '', '{h.status_text}')])])]),
      ], { attrs: { role: 'status' } }),
    ]),
    pillen(`${TU}.filter`, 'Turniere nach Kategorie filtern', 'name'),
    wenn(`${TU}.hat_kommende`, [
      loop({ target: `${TU}.monate`, itemId: 'mo' }, [
        t('h3', 'tournaments__month', '{mo.name}'),
        el('div', 'tournaments__list', [loop({ target: 'mo.turniere', itemId: 'tu' }, [turnier()])], { attrs: { role: 'list', 'aria-label': 'Turniere {mo.name}' } }),
      ]),
    ]),
    wenn(`${TU}.hat_kommende`, [t('p', 'tournaments__empty', 'Zurzeit sind keine Turniere ausgeschrieben.')], 'isFalsy'),
    el('p', 'tournaments__source small', [
      text('Die Turniere kommen aus PC CADDIE. Anmeldung, Start- und Ergebnislisten finden Sie dort, auch im '),
      t('a', '', 'Turnierkalender bei PC CADDIE', { attrs: { href: `{${TU}.pcc_kalender}`, rel: 'noopener' } }),
      text('.'),
    ]),
  ], { name: 'Turnierkalender' }),
};

/** Eine Zelle der Platzbelegung: Turniere des Tages oder „frei“. Für Screenreader ein ganzer Satz ({z.vorlesen}). */
const belegungZelle = () =>
  el('div', 'occupancy__cell occupancy__cell--{z.mod} occupancy__cell--{z.club_mod}', [
    t('span', 'visually-hidden', '{z.vorlesen}'),
    el('div', 'occupancy__content', [
      wenn('z.hat_turniere', [
        loop({ target: 'z.turniere', itemId: 'e' }, [
          el('p', 'occupancy__event occupancy__event--{e.mod}', [
            wenn('e.zeit', [t('span', 'occupancy__time', '{e.zeit}')]),
            t('span', 'occupancy__name', '{e.titel}'),
            wenn('e.abgesagt', [t('span', 'occupancy__cancel', 'abgesagt')]),
            wenn('e.abgesagt', [wenn('e.loecher', [t('span', 'occupancy__holes', '{e.loecher}')])], 'isFalsy'),
          ]),
        ]),
      ]),
      wenn('z.belegt', [t('span', 'occupancy__free', 'frei')], 'isFalsy'),
    ], { attrs: { 'aria-hidden': 'true' } }),
  ], { attrs: { role: 'cell' } });

export const platzbelegungKomponente = {
  key: 'Platzbelegung',
  name: 'Platzbelegung',
  description: 'Tabelle Tag × Club für 4 Wochen – erste Spalte der Heimatclub, dann die GOLFHOCHZEHN-Partnerclubs (Clubdaten → Partnerclubs): an welchem Tag ist bei welchem Club ein Turnier (Uhrzeit, Name, Löcher) und wo ist der Platz frei – zur Planung eines Besuchs. Blättern per ?ab=JJJJ-MM-TT. Daten: {options.golfplatz.turniere.belegung}.',
  properties: [],
  content: wenn(`${PB}.hat_clubs`, [
    el('div', 'occupancy', [
      el('div', 'occupancy__bar', [
        t('p', 'occupancy__range', `{${PB}.zeitraum}`),
        el('nav', 'occupancy__nav', [
          wenn(`${PB}.hat_zurueck`, [t('a', 'occupancy__step', '← vorige 4 Wochen', { attrs: { href: `{${PB}.zurueck}` } })]),
          t('a', 'occupancy__step', 'nächste 4 Wochen →', { attrs: { href: `{${PB}.weiter}` } }),
        ], { attrs: { 'aria-label': 'Zeitraum wählen' } }),
      ]),
      el('div', 'table-wrap occupancy__wrap', [
        el('div', 'occupancy__table', [
          el('div', 'occupancy__row occupancy__row--head', [
            t('div', 'occupancy__date', 'Tag', { attrs: { role: 'columnheader' } }),
            loop({ target: `${PB}.clubs`, itemId: 'c' }, [
              el('div', 'occupancy__club occupancy__club--{c.mod}', [
                wenn('c.hat_website', [t('a', '', '{c.kurz}', { attrs: { href: '{c.website}', title: '{c.name}', rel: 'noopener' } })]),
                wenn('c.hat_website', [t('span', '', '{c.kurz}', { attrs: { title: '{c.name}' } })], 'isFalsy'),
              ], { attrs: { role: 'columnheader' } }),
            ]),
          ], { attrs: { role: 'row' } }),
          loop({ target: `${PB}.tage`, itemId: 'd' }, [
            el('div', 'occupancy__row occupancy__row--{d.mod} occupancy__row--{d.heute_mod}', [
              el('div', 'occupancy__date', [t('span', 'visually-hidden', '{d.datum_lang}'), t('span', '', '{d.datum}', { attrs: { 'aria-hidden': 'true' } })], { attrs: { role: 'rowheader' } }),
              loop({ target: 'd.zellen', itemId: 'z' }, [belegungZelle()]),
            ], { attrs: { role: 'row' } }),
          ]),
        ], { attrs: { role: 'table', 'aria-label': `Platzbelegung, {${PB}.zeitraum}`, style: `--clubs: {${PB}.anzahl}` } }),
      ]),
      el('p', 'occupancy__note small', [
        text('„Belegt“ heißt: Laut PC CADDIE findet an diesem Tag ein Turnier statt – der Platz ist dann ganz oder zu bestimmten Zeiten gesperrt. Startzeit bitte vorher beim Club erfragen.'),
      ]),
      wenn(`${PB}.hat_ohne_pcc`, [
        el('p', 'occupancy__note small', [
          text('Nicht in der Tabelle: '),
          loop({ target: `${PB}.ohne_pcc`, itemId: 'o' }, [t('a', 'occupancy__extern', '{o.name} (eigener Turnierkalender)', { attrs: { href: '{o.link}', rel: 'noopener' } })]),
        ]),
      ]),
    ], { name: 'Platzbelegung' }),
  ]),
};

/** Startseite: die nächsten Turniere des Heimatclubs (auch abgesagte bzw. verschobene, mit Hinweis). */
export const naechsteTurniereKomponente = {
  key: 'NaechsteTurniere',
  name: 'Nächste Turniere',
  description: 'Die vier nächsten Turniere des Heimatclubs aus PC CADDIE für die Startseite – wie im Turnierkalender, abgesagte durchgestrichen mit „Abgesagt“, verschobene mit „Verschoben vom …“. Daten: {options.golfplatz.turniere.naechste}.',
  properties: [],
  content: el('div', 'tournaments tournaments--kompakt', [
    wenn(`${TU}.hat_naechste`, [
      el('div', 'tournaments__list', [loop({ target: `${TU}.naechste`, itemId: 'tu' }, [turnier()])], { attrs: { role: 'list', 'aria-label': 'Nächste Turniere' } }),
    ]),
    wenn(`${TU}.hat_naechste`, [t('p', 'tournaments__empty', 'Zurzeit sind keine Turniere ausgeschrieben.')], 'isFalsy'),
  ], { name: 'Nächste Turniere' }),
};

export const turnierergebnisseKomponente = {
  key: 'Turnierergebnisse',
  name: 'Turnierergebnisse',
  description: 'Gespielte Turniere des Heimatclubs eines Jahres (?jahr=2025) mit Link zur Ergebnisliste bei PC CADDIE. Daten: {options.golfplatz.turniere}.',
  properties: [],
  content: wenn(`${TU}.hat_gespielt`, [
    el('div', 'results', [
      pillen(`${TU}.jahre`, 'Jahr wählen', 'jahr'),
      el('div', 'results__list', [
        loop({ target: `${TU}.gespielt`, itemId: 'g' }, [
          el('div', 'results__item', [
            t('time', 'results__date', '{g.tag}. {g.monat}', { attrs: { datetime: '{g.datum_iso}' } }),
            el('div', 'results__body', [
              el('p', 'results__title', [text('{g.titel}'), wenn('g.untertitel', [t('span', 'results__subtitle', ' {g.untertitel}')])]),
              kategorien('g'),
            ]),
            wenn('g.hat_ergebnisse', [t('a', 'results__link', 'Ergebnisliste', { attrs: { href: '{g.link_ergebnisse}', rel: 'noopener' } })]),
          ], { attrs: { role: 'listitem' } }),
        ]),
      ], { attrs: { role: 'list', 'aria-label': `Turnierergebnisse {${TU}.jahr}` } }),
    ], { name: 'Turnierergebnisse' }),
  ]),
};
