// Etch-Komponenten „Turnierkalender“ und „Turnierergebnisse“ (Seite /turniere/).
// Daten: {options.golfplatz.turniere} aus mu-plugins/golfplatz-turniere.php – stündlich aus PC CADDIE://online gelesen.
// Anmeldung, Startlisten und Ergebnislisten bleiben bei PC CADDIE (Links); Namen von Spielern übernimmt die Website nicht.
// Filter per URL: ?kategorie=s (Kalender), ?jahr=2025 (Ergebnisse); Auswahl als Pillen wie bei den Ligaspielen.

import { el, t, text, loop, wenn } from './lib.mjs';

const TU = 'options.golfplatz.turniere';

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
  el('article', 'tournament', [
    el('div', 'tournament__date', [
      t('span', 'tournament__weekday', '{tu.wochentag}'),
      t('span', 'tournament__day', '{tu.tag}'),
      t('span', 'tournament__month', '{tu.monat}'),
    ], { attrs: { 'aria-hidden': 'true' } }),
    el('div', 'tournament__body', [
      el('h4', 'tournament__title', [t('span', 'visually-hidden', '{tu.datum_lang}: '), text('{tu.titel}')]),
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
  description: 'Kommende Turniere aus PC CADDIE, nach Monat, mit Kategorie-Filter (?kategorie=d|h|s|j|c), Anmeldeschluss, freien Plätzen und Links zu Anmeldung, Ausschreibung und Details bei PC CADDIE. Daten: {options.golfplatz.turniere} (golfplatz-turniere.php, stündlicher Abgleich).',
  properties: [],
  content: el('div', 'tournaments', [
    wenn(`${TU}.hat_heute`, [
      el('div', 'tournaments__today', [
        t('p', 'tournaments__today-label', 'Heute auf dem Platz'),
        loop({ target: `${TU}.heute`, itemId: 'h' }, [el('p', 'tournaments__today-item', [t('strong', '', '{h.titel}'), wenn('h.infos', [text(' · {h.infos}')])])]),
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

export const turnierergebnisseKomponente = {
  key: 'Turnierergebnisse',
  name: 'Turnierergebnisse',
  description: 'Gespielte Turniere eines Jahres (?jahr=2025) mit Link zur Ergebnisliste bei PC CADDIE. Daten: {options.golfplatz.turniere}.',
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
