// Etch-Komponenten (wp_block). Alle Club-Angaben kommen aus der Einstellungsseite „Clubdaten“.
// Aufbau und Klassen wie im Prototyp (prototype/src/components.mjs).

import { el, t, text, wenn, club, telHref, icon, CLUB, komponente } from './lib.mjs';
import { birdiebookKomponente, scorekarteKomponente, ratingKomponente, spielvorgabenRechnerKomponente, spielvorgabenTabellenKomponente } from './birdiebook.mjs';
import { platzstatusKomponente, ampelKomponente, platzstatusKurzKomponente } from './platzstatus.mjs';
import { oeffnungszeitenKomponente, oeffnungszeitenAlleKomponente } from './zeiten.mjs';
import { preistabelleKomponente, preiskartenKomponente } from './preise.mjs';
import { zaehlkarteKomponente } from './zaehlkarte.mjs';
import { lochwettspielKomponente } from './lochwettspiel.mjs';
import { turnierkalenderKomponente, platzbelegungKomponente, turnierergebnisseKomponente } from './turniere.mjs';

/** Öffnungszeiten eines Bereichs als Etch-Komponente (wordpress/etch/zeiten.mjs). */
export const zeiten = (bereich, variante = 'compact', liste = variante) => komponente('Oeffnungszeiten', { bereich, variante, liste, titel: '0' });

// Umschalter Hell/Dunkel (Skripte: mu-plugins/golfplatz-farbschema.php, erkennt [data-scheme-toggle])
const farbschemaKomponente = {
  key: 'FarbschemaUmschalter',
  name: 'Farbschema-Umschalter',
  description: 'Knopf Hell/Dunkel für die Top-Bar. Zustand, Beschriftung und Speichern übernimmt das Skript aus mu-plugins/golfplatz-farbschema.php.',
  properties: [],
  content: el('button', 'scheme-toggle', [
    el('span', 'scheme-toggle__icon', [], { attrs: { 'aria-hidden': 'true' } }),
    t('span', 'scheme-toggle__text', 'Dunkel'),
  ], { attrs: { type: 'button', 'data-scheme-toggle': '', 'aria-pressed': 'false' }, name: 'Farbschema-Umschalter' }),
};

const logo = () =>
  el('a', 'site-logo', [
    el('span', 'site-logo__text', [
      t('span', 'site-logo__name', club('club_name')),
      t('span', 'site-logo__since', club('club_ort') + ' · Bergisches Land'),
    ]),
  ], { attrs: { href: '/' }, name: 'Logo' });

const navListe = (titel, links) =>
  el('nav', 'site-footer__col footer-nav', [
    t('h2', 'site-footer__title', titel),
    el('ul', 'footer-nav__list', links.map(([label, href]) => el('li', 'footer-nav__item', [t('a', 'footer-nav__link', label, { attrs: { href } })]))),
  ], { attrs: { 'aria-label': titel } });

// Reihenfolge: Komponenten, die in anderen eingebunden werden, zuerst (Sync ersetzt __REF_<key>__ der Reihe nach).
export const components = [
  oeffnungszeitenKomponente,
  ampelKomponente,
  farbschemaKomponente,
  oeffnungszeitenAlleKomponente,
  preistabelleKomponente,
  preiskartenKomponente,
  platzstatusKurzKomponente,
  platzstatusKomponente,
  scorekarteKomponente,
  ratingKomponente,
  spielvorgabenRechnerKomponente,
  spielvorgabenTabellenKomponente,
  zaehlkarteKomponente,
  lochwettspielKomponente,
  turnierkalenderKomponente,
  platzbelegungKomponente,
  turnierergebnisseKomponente,
  birdiebookKomponente,
  {
    key: 'GastInfo',
    // Übernimmt die frühere Komponente „Startzeit reservieren“ (gleiche WordPress-ID).
    ersetzt: 'BookingCta',
    name: 'Als Gast spielen',
    description: 'Hinweis für Gäste: keine festen Startzeiten, Anmeldung am Wochenende empfohlen. Telefon, E-Mail und Öffnungszeiten aus den Clubdaten.',
    properties: [{ key: 'anker', name: 'Anker (id)', type: { primitive: 'string' }, default: 'spielen' }],
    content: el('aside', 'guest-info', [
      el('div', 'guest-info__intro', [
        t('p', 'guest-info__eyebrow', 'Als Gast spielen'),
        t('h2', 'guest-info__title', 'Einfach spielen – ohne feste Startzeiten'),
        t('p', 'guest-info__text', club('anmeldung_hinweis')),
        wenn(`${CLUB}.ruhetag_hinweis`, [t('p', 'guest-info__note', club('ruhetag_hinweis'))]),
      ]),
      el('div', 'guest-info__contact', [
        el('a', 'guest-info__channel', [
          icon('phone'),
          el('span', '', [t('span', 'guest-info__label', 'Anmeldung'), t('span', 'guest-info__value', club('anmeldung_telefon'))]),
        ], { attrs: { href: telHref('anmeldung_telefon') } }),
        el('a', 'guest-info__channel', [
          icon('mail'),
          el('span', '', [t('span', 'guest-info__label', 'E-Mail'), t('span', 'guest-info__value', club('club_email'))]),
        ], { attrs: { href: 'mailto:' + club('club_email') } }),
      ]),
      el('div', 'guest-info__hours', [
        el('p', 'guest-info__label', [icon('clock'), text(' Sekretariat')]),
        zeiten('sekretariat', 'compact'),
      ]),
    ], { attrs: { id: '{props.anker}', 'aria-label': 'Als Gast spielen' }, name: 'Als Gast spielen' }),
  },
  {
    key: 'SiteFooter',
    name: 'Footer',
    description: 'Seitenfuß mit Adresse, Kontakt und Öffnungszeiten aus den Clubdaten.',
    properties: [],
    content: el('footer', 'site-footer', [
      el('div', 'site-footer__inner container', [
        el('div', 'site-footer__col site-footer__col--brand', [
          logo(),
          el('address', 'site-footer__address', [
            t('span', 'site-footer__line', club('club_strasse')),
            t('span', 'site-footer__line', club('club_plz') + ' ' + club('club_ort')),
          ]),
          el('p', 'site-footer__contact', [
            el('a', 'site-footer__link site-footer__line', [icon('phone'), text(' ' + club('club_telefon'))], { attrs: { href: telHref('club_telefon') } }),
            el('a', 'site-footer__link site-footer__line', [icon('mail'), text(' ' + club('club_email'))], { attrs: { href: 'mailto:' + club('club_email') } }),
          ]),
        ]),
        el('div', 'site-footer__col', [t('h2', 'site-footer__title', 'Sekretariat'), zeiten('sekretariat', 'footer')]),
        navListe('Golf', [
          ['Platz & Bahnen', '/platz/'],
          ['Greenfee & Preise', '/greenfee/'],
          ['Turniere', '/turniere/'],
          ['Golfschule', '/golfschule/'],
          ['Mannschaften', '/mannschaften/'],
        ]),
        navListe('Club', [
          ['Mitglied werden', '/mitgliedschaft/'],
          ['Restaurant & Events', '/restaurant/'],
          ['Aktuelles', '/news/'],
          ['Club & Kontakt', '/club/'],
          ['Mitgliederbereich', '/mitglieder/'],
        ]),
      ]),
      el('div', 'site-footer__bottom', [
        el('div', 'site-footer__bottom-inner container', [
          t('p', 'site-footer__copy', '© ' + club('club_name')),
          el('ul', 'footer-nav__list footer-nav__list--inline', [
            el('li', 'footer-nav__item', [t('a', 'footer-nav__link', 'Impressum', { attrs: { href: '/impressum/' } })]),
            el('li', 'footer-nav__item', [t('a', 'footer-nav__link', 'Datenschutz', { attrs: { href: '/datenschutz/' } })]),
          ]),
        ]),
      ]),
    ], { name: 'Footer' }),
  },
];
