// Etch-Komponente „Platzstatus“ (Startseite, Anker #platzstatus).
// Die Werte rechnet mu-plugins/golfplatz-platzstatus.php und stellt sie über den Etch-Filter
// etch/dynamic_data/option bereit: {options.golfplatz.platzstatus.<feld>}. Hier steht nur das Markup –
// Überschriften, Texte, Reihenfolge und Klassen lassen sich im Etch-Builder ändern.
// Markup und Klassen wie der frühere Shortcode [golfplatz_platzstatus] (CSS in prototype/assets/css/main.css).

import { el, t, text, loop, wenn } from './lib.mjs';

const P = 'options.golfplatz.platzstatus';
const v = (pfad) => `{${P}.${pfad}}`;

const kopf = () =>
  el('div', 'status-board__head', [
    t('h2', 'status-board__title', 'Platzstatus', { attrs: { id: 'status-board-title' } }),
    t('p', 'status-board__updated', `Stand: ${v('stand')} Uhr`),
  ]);

// Schnellsperre des ganzen Platzes, deutlich ganz oben
const sperre = () =>
  wenn(`${P}.platz_gesperrt`, [
    el('div', 'status-board__alert', [
      el('div', '', [
        t('strong', '', 'Der Platz ist gesperrt.'),
        text(` ${v('platz_gesperrt_grund')}`),
        wenn(`${P}.platz_gesperrt_bis`, [text(` – ${v('platz_gesperrt_bis')}.`)]),
      ]),
    ], { attrs: { role: 'alert' } }),
  ]);

const bedingungen = () =>
  el('ul', 'status-board__conditions', [
    loop({ target: `${P}.bedingungen`, itemId: 'b' }, [
      el('li', 'condition condition--{b.mod}', [
        t('span', 'condition__label', '{b.label}'),
        t('span', 'condition__value', '{b.wert}'),
        wenn('b.info', [t('span', 'condition__info', '{b.info}')]),
      ]),
    ]),
    // Standard-Fahnenposition als Kachel wie die Spielbedingungen
    wenn(`${P}.fahnen.vorhanden`, [
      el('li', 'condition condition--ok', [
        t('span', 'condition__label', 'Fahnenposition'),
        t('span', 'condition__value', v('fahnen.standard')),
        wenn(`${P}.fahnen.info`, [t('span', 'condition__info', v('fahnen.info'))]),
      ]),
      // Jede Ausnahme als eigene Kachel: „Bahn 6 · Position 4“ (darunter die Lage, falls gepflegt)
      loop({ target: `${P}.fahnen.ausnahmen`, itemId: 'a' }, [
        el('li', 'condition condition--pin condition--ausnahme', [
          t('span', 'condition__label', 'Bahn {a.bahn}'),
          t('span', 'condition__value', '{a.text}'),
          wenn('a.lage', [t('span', 'condition__info', '{a.lage}')]),
        ]),
      ]),
    ]),
  ], { attrs: { 'aria-label': 'Spielbedingungen' }, name: 'Spielbedingungen' });

// Nummer der Fahnenposition (1–6) als Fähnchen-Badge; die Position steht zusätzlich als Text daneben
const tage = () =>
  el('div', 'status-board__days', [
    loop({ target: `${P}.tage`, itemId: 'tag' }, [
      el('div', 'status-board__day', [
        el('h3', 'status-board__day-title', [text('{tag.label} '), t('span', 'status-board__date', '{tag.datum}')]),
        el('ul', 'status-board__list', [
          wenn('tag.frei', [t('li', 'status-board__empty', 'Platz uneingeschränkt bespielbar')]),
          loop({ target: 'tag.eintraege', itemId: 'e' }, [
            el('li', 'status-entry {e.klasse}', [
              el('span', 'status-entry__area', [text('{e.bereich}'), wenn('e.laeuft', [t('span', 'status-entry__live', '· jetzt')])]),
              t('span', 'status-entry__time', '{e.zeit}'),
              t('span', 'status-entry__reason', '{e.grund}'),
              wenn('e.start', [t('span', 'status-entry__start', '{e.start}')]),
            ]),
          ]),
        ]),
      ]),
    ]),
  ], { name: 'Heute und morgen' });

const einrichtungen = () =>
  el('div', 'status-board__facilities', [
    t('h3', 'status-board__day-title', 'Übungsanlagen & Proshop'),
    el('ul', 'status-board__facility-list', [
      loop({ target: `${P}.einrichtungen`, itemId: 'x' }, [
        el('li', 'facility-status facility-status--{x.mod}', [
          t('span', 'facility-status__name', '{x.name}'),
          t('span', 'facility-status__state', '{x.zustand}'),
          loop({ target: 'x.infos', itemId: 'i' }, [t('span', 'facility-status__info', '{i.text}')]),
        ]),
      ]),
    ]),
    el('p', 'status-board__more', [t('a', 'link-arrow', 'Alle Öffnungszeiten', { attrs: { href: '{props.zeitenLink}' } })]),
  ], { name: 'Übungsanlagen & Proshop' });

export const platzstatusKomponente = {
  key: 'Platzstatus',
  name: 'Platzstatus',
  description: 'Platzstatus der Startseite: Schnellsperre, Spielbedingungen, Fahnenpositionen, Sperren heute/morgen, Übungsanlagen. Daten: {options.golfplatz.platzstatus.*} aus mu-plugins/golfplatz-platzstatus.php.',
  properties: [{ key: 'zeitenLink', name: 'Link „Alle Öffnungszeiten“', type: { primitive: 'string' }, default: '/greenfee/#oeffnungszeiten' }],
  content: el('section', 'status-board', [kopf(), sperre(), bedingungen(), tage(), einrichtungen()], {
    attrs: { id: 'platzstatus', 'aria-labelledby': 'status-board-title' },
    name: 'Platzstatus',
  }),
};

// Ampel für Top-Bar und App-Leiste: {options.golfplatz.platzstatus.ampel}
export const ampelKomponente = {
  key: 'Ampel',
  name: 'Platzstatus-Ampel',
  description: 'Ampel mit Kurztext (Platz geöffnet / eingeschränkt / gesperrt), verlinkt auf den Platzstatus der Startseite. Daten: {options.golfplatz.platzstatus.ampel}.',
  properties: [],
  content: el('a', `top-bar__status course-status course-status--${v('ampel.zustand')}`, [
    el('span', 'course-status__dot', [], { attrs: { 'aria-hidden': 'true' } }),
    t('span', 'course-status__text', v('ampel.text')),
  ], { attrs: { href: '/#platzstatus' }, name: 'Ampel' }),
};

// Kurzfassung für den Hero der Startseite: {options.golfplatz.platzstatus.kurz}
export const platzstatusKurzKomponente = {
  key: 'PlatzstatusKurz',
  name: 'Platzstatus kurz',
  description: 'Kurzfassung im Hero der Startseite: Zustand jetzt, heutige Sperren von Platz und Abschlägen, Spielbedingungen als Chips (inkl. Fahnen), Link auf den ausführlichen Platzstatus. Daten: {options.golfplatz.platzstatus.kurz}.',
  properties: [],
  content: el('aside', `status-summary status-summary--${v('kurz.zustand')}`, [
    t('p', 'status-summary__eyebrow', 'Platzstatus heute'),
    el('p', 'status-summary__state', [el('span', 'status-summary__dot', [], { attrs: { 'aria-hidden': 'true' } }), text(v('kurz.text'))], { attrs: { id: 'status-summary-title' } }),
    wenn(`${P}.kurz.hat_heute`, [
      el('ul', 'status-summary__list', [
        loop({ target: `${P}.kurz.heute`, itemId: 'h' }, [
          el('li', 'status-summary__item {h.klasse}', [t('strong', '', '{h.bereich}'), text(' {h.text}')]),
        ]),
        wenn(`${P}.kurz.mehr`, [t('li', 'status-summary__item', v('kurz.mehr'))]),
      ]),
    ]),
    wenn(`${P}.kurz.hat_heute`, [t('p', 'status-summary__empty', 'Heute keine Sperren – Platz uneingeschränkt bespielbar')], 'isFalsy'),
    el('ul', 'status-summary__chips', [
      loop({ target: `${P}.kurz.chips`, itemId: 'c' }, [t('li', 'status-summary__chip status-summary__chip--{c.mod}', '{c.text}')]),
    ], { attrs: { 'aria-label': 'Spielbedingungen' } }),
    t('a', 'status-summary__link link-arrow', 'Morgen, Übungsanlagen & Details', { attrs: { href: '#platzstatus' } }),
  ], { attrs: { 'aria-labelledby': 'status-summary-title' }, name: 'Platzstatus kurz' }),
};
