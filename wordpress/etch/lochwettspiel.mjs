// Etch-Komponente „Lochwettspiel“: Turnierbaum des jährlichen Lochwettspiels (Teams aus zwei Spielern, K.-o.-System).
// Daten: {options.golfplatz.lochwettspiele} aus snippets/golfplatz-lochwettspiel.php – PHP setzt die Teams ins
// Tableau, lässt Sieger weiterrücken und bewertet die Spielzeiträume; hier steht nur das Markup.
//
// Aufbau: je Runde eine Spalte (Kopf mit Name, Zeitraum, Status), darin Paare aus zwei Spielen, deren Sieger sich in der
// nächsten Runde treffen. Die Linien des Baums zeichnet das CSS an den Paaren (bracket__pair--paar/--final).
// Große Felder (ab 17 Teams): Der Baum beginnt mit dem Achtelfinale ({r.im_baum}); frühere Runden stehen darüber als
// aufklappbare Rundenlisten (<details>, {r.vorrunde}), die laufende Runde ist offen ({r.aufklappen}).
// Keine Listen-Tags: Der Builder packt Loops in <div style="display: contents">, deshalb role="list/listitem".
// Die Karten sind für Screenreader ausgeblendet, stattdessen liest jede Karte einen fertigen Satz vor ({m.aria}).

import { el, t, loop, wenn } from './lib.mjs';

const LW = 'options.golfplatz.lochwettspiele';

const team = () =>
  loop({ target: 'm.seiten', itemId: 'o' }, [
    el('div', 'bracket__team bracket__team--{o.mod}', [
      wenn('o.leer', [t('span', 'bracket__placeholder', '{o.text}')]),
      wenn('o.leer', [
        el('span', 'bracket__players', [
          wenn('o.name', [t('span', 'bracket__team-name', '{o.name}')]),
          t('span', 'bracket__player', '{o.spieler_1}'),
          t('span', 'bracket__player', '{o.spieler_2}'),
        ]),
      ], 'isFalsy'),
      wenn('o.ergebnis', [t('span', 'bracket__score', '{o.ergebnis}')]),
    ]),
  ]);

const karte = () =>
  el('div', 'bracket__match bracket__match--{m.mod}', [
    t('p', 'visually-hidden', '{m.aria}'),
    el('div', 'bracket__card', [
      wenn('m.label', [t('p', 'bracket__label', '{m.label}')]),
      team(),
      wenn('m.status', [t('p', 'bracket__state', '{m.status}')]),
    ], { attrs: { 'aria-hidden': 'true' } }),
  ]);

const spiel = () => el('div', 'bracket__slot', [karte()], { attrs: { role: 'listitem' } });

// Frühere Runden großer Felder: je Runde ein aufklappbarer Block mit allen Spielen im Raster
const vorrunde = (offen) =>
  el('details', 'lw-round lw-round--{r.mod}', [
    el('summary', 'lw-round__summary', [
      t('h3', 'lw-round__name', '{r.name}'),
      wenn('r.zeitraum', [t('span', 'lw-round__dates', '{r.zeitraum}')]),
      t('span', 'bracket__status bracket__status--{r.mod}', '{r.status}'),
      t('span', 'lw-round__count', '{r.zahlen}'),
    ]),
    el('div', 'lw-round__matches', [
      loop({ target: 'r.liste', itemId: 'm' }, [el('div', 'lw-round__item', [karte()], { attrs: { role: 'listitem' } })]),
    ], { attrs: { role: 'list', 'aria-label': 'Spiele {r.name}' } }),
  ], { attrs: offen ? { open: '' } : {}, name: 'Runde (Liste)' });

const vorrunden = () =>
  wenn('t.hat_vorrunden', [
    el('div', 'lw-rounds', [
      t('p', 'lw-rounds__title', 'Frühere Runden'),
      loop({ target: 't.runden', itemId: 'r' }, [
        wenn('r.vorrunde', [wenn('r.aufklappen', [vorrunde(true)]), wenn('r.aufklappen', [vorrunde(false)], 'isFalsy')]),
      ]),
      t('p', 'lw-rounds__title', '{t.baum_titel}'),
    ], { name: 'Frühere Runden' }),
  ]);

const runde = () =>
  loop({ target: 't.runden', itemId: 'r' }, [
    wenn('r.im_baum', [el('div', 'bracket__round bracket__round--{r.lage}', [
      el('div', 'bracket__head', [
        t('h3', 'bracket__title', '{r.name}'),
        wenn('r.zeitraum', [t('p', 'bracket__dates', '{r.zeitraum}')]),
        t('p', 'bracket__status bracket__status--{r.mod}', '{r.status}'),
      ]),
      el('div', 'bracket__matches', [
        loop({ target: 'r.paare', itemId: 'p' }, [
          el('div', 'bracket__pair bracket__pair--{p.mod}', [loop({ target: 'p.spiele', itemId: 'm' }, [spiel()])]),
        ]),
      ], { attrs: { role: 'list', 'aria-label': 'Spiele {r.name}' } }),
    ], { attrs: { role: 'group', 'aria-label': '{r.name}' }, name: 'Runde' })]),
  ]);

const siegerSpalte = () =>
  el('div', 'bracket__round bracket__round--folge bracket__round--sieger', [
    el('div', 'bracket__head', [t('h3', 'bracket__title', 'Sieger {t.jahr}')]),
    el('div', 'bracket__matches', [
      el('div', 'bracket__pair', [
        el('div', 'bracket__slot', [
          el('div', 'bracket__champion bracket__champion--{t.sieger.mod}', [
            wenn('t.sieger.vorhanden', [
              wenn('t.sieger.name', [t('span', 'bracket__team-name', '{t.sieger.name}')]),
              t('span', 'bracket__player', '{t.sieger.spieler_1}'),
              t('span', 'bracket__player', '{t.sieger.spieler_2}'),
            ]),
            wenn('t.sieger.vorhanden', [t('span', 'bracket__placeholder', '{t.sieger.text}')], 'isFalsy'),
          ]),
        ]),
      ]),
    ]),
  ], { attrs: { role: 'group', 'aria-label': 'Sieger {t.jahr}' }, name: 'Sieger' });

const kopf = () =>
  el('div', 'lochwettspiel__head', [
    t('p', 'lochwettspiel__meta', '{t.spielform} · {t.teams_text} · K.-o.-System'),
    wenn('t.status', [t('p', 'lochwettspiel__status', '{t.status}')]),
    wenn('t.hinweis', [t('p', 'lochwettspiel__note', '{t.hinweis}')]),
    wenn('t.hat_jahre', [
      el('nav', 'lochwettspiel__years', [
        t('span', 'lochwettspiel__years-label', 'Jahrgänge:'),
        loop({ target: 't.jahre', itemId: 'j' }, [
          wenn('j.aktiv', [t('a', 'lochwettspiel__year', '{j.jahr}', { attrs: { href: '{j.link}' } })], 'isFalsy'),
          wenn('j.aktiv', [t('span', 'lochwettspiel__year lochwettspiel__year--aktiv', '{j.jahr}')]),
        ]),
      ], { attrs: { 'aria-label': 'Lochwettspiel anderer Jahre' } }),
    ]),
  ], { name: 'Kopf' });

export const lochwettspielKomponente = {
  key: 'Lochwettspiel',
  name: 'Lochwettspiel',
  description: 'Turnierbaum des jährlichen Lochwettspiels (Teams aus zwei Spielern, K.-o.-System, Spielzeitraum je Runde; beliebig viele Teams – ab 17 Teams frühere Runden als aufklappbare Listen, Baum ab Achtelfinale). Eigenschaft: jahr („aktuell“ = neuestes Turnier, sonst die Jahreszahl). Daten: {options.golfplatz.lochwettspiele} aus golfplatz-lochwettspiel.php; gepflegt im Beitragstyp „Lochwettspiele“ (Teams, Spielzeiträume, Ergebnisse).',
  properties: [{ key: 'jahr', name: 'Jahr (aktuell oder Jahreszahl)', type: { primitive: 'string' }, default: 'aktuell' }],
  content: loop({ target: LW, itemId: 't' }, [
    wenn('t.key', [
      el('div', 'lochwettspiel', [
        kopf(),
        wenn('t.hat_baum', [
          vorrunden(),
          t('p', 'bracket__hint', 'Der Turnierbaum lässt sich seitlich verschieben.'),
          el('div', 'bracket', [el('div', 'bracket__grid', [runde(), siegerSpalte()])], {
            attrs: { role: 'region', tabindex: '0', 'aria-label': 'Turnierbaum {t.titel}' },
            name: 'Turnierbaum',
          }),
        ]),
      ], { name: 'Lochwettspiel' }),
    ], '===', 'props.jahr'),
  ]),
};
