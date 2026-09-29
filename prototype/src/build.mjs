// Erzeugt den statischen Prototyp: node prototype/src/build.mjs
// Ausgabe: prototype/**/index.html und prototype/assets/js/data.js

import { mkdirSync, writeFileSync, rmSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import {
  club, restaurant, abschlaege, bahnen, parText, personenIn, kurseAnmeldung, einheitText, sperrungen, platzstatus, oeffnungszeiten, EINRICHTUNGEN, personen, preise, kurse,
  mannschaften, ligaspiele, spielberichte, news,
} from './data.mjs';
import {
  esc, euro, zahl, datumLang, datumKurz, telHref, hiText, spielvorgabenTabelle, svText, teeBadge, icon, logo,
  pageHero, sectionHead, hoursList, guestInfo, statusBoard, facilityHours, openingHours, personCard, newsCard, priceTable, priceMatrix, preisText, prototypeForm, holeSvg,
} from './components.mjs';
import { farbenCss } from '../../wordpress/etch/acss-farben.mjs';

const OUT = join(dirname(fileURLToPath(import.meta.url)), '..');
const HEUTE = new Date().toISOString().slice(0, 10);

// Generierte Seitenordner vor dem Build leeren
for (const dir of ['platz', 'greenfee', 'mitgliedschaft', 'turniere', 'golfschule', 'mannschaften', 'spielberichte', 'restaurant', 'news', 'club', 'mitglieder', 'impressum', 'datenschutz']) {
  const p = join(OUT, dir);
  if (existsSync(p)) rmSync(p, { recursive: true });
}

const nav = [
  {
    key: 'golf',
    label: 'Golf spielen',
    children: [
      ['platz', 'Platz & Bahnen', 'platz/'],
      ['spielvorgaben', 'Spielvorgaben', 'platz/spielvorgaben/'],
      ['zaehlkarte', 'Zählkarte', 'platz/zaehlkarte/'],
      ['greenfee', 'Greenfee & Preise', 'greenfee/'],
      ['turniere', 'Turniere & Kalender', 'turniere/'],
      ['golfschule', 'Golfschule', 'golfschule/'],
    ],
  },
  { key: 'mitgliedschaft', label: 'Mitgliedschaft', href: 'mitgliedschaft/' },
  { key: 'mannschaften', label: 'Mannschaften', href: 'mannschaften/' },
  { key: 'restaurant', label: 'Restaurant', href: 'restaurant/' },
  { key: 'news', label: 'Aktuelles', href: 'news/' },
  { key: 'club', label: 'Club & Kontakt', href: 'club/' },
];

const mainNav = (r, aktiv) => `
<nav class="main-nav" id="main-nav" aria-label="Hauptnavigation" data-main-nav>
  <ul class="main-nav__list">
    ${nav
      .map((item) => {
        if (item.children) {
          const istAktiv = item.children.some(([k]) => k === aktiv);
          return `<li class="main-nav__item main-nav__item--has-submenu${istAktiv ? ' main-nav__item--active' : ''}">
        <button class="main-nav__link main-nav__link--toggle" type="button" aria-expanded="false" aria-controls="submenu-${item.key}">${item.label}<svg class="main-nav__chevron" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></button>
        <ul class="main-nav__submenu" id="submenu-${item.key}">
          ${item.children
            .map(
              ([k, label, href]) =>
                `<li class="main-nav__submenu-item${k === aktiv ? ' main-nav__submenu-item--active' : ''}"><a class="main-nav__submenu-link" href="${r}${href}"${k === aktiv ? ' aria-current="page"' : ''}>${label}</a></li>`,
            )
            .join('')}
        </ul>
      </li>`;
        }
        const a = item.key === aktiv;
        return `<li class="main-nav__item${a ? ' main-nav__item--active' : ''}"><a class="main-nav__link" href="${r}${item.href}"${a ? ' aria-current="page"' : ''}>${item.label}</a></li>`;
      })
      .join('')}
  </ul>
  <div class="main-nav__actions">
    <a class="btn btn--secondary main-nav__cta" href="${r}greenfee/#spielen">Als Gast spielen</a>
  </div>
</nav>`;

const header = (r, aktiv) => `
<a class="skip-link" href="#inhalt">Zum Inhalt springen</a>
<header class="site-header">
  <div class="top-bar">
    <div class="top-bar__inner container">
      <a class="top-bar__status course-status" href="${r}#platzstatus" data-course-status>
        <span class="course-status__dot" aria-hidden="true"></span>
        <span class="course-status__text">Platzstatus</span>
      </a>
      <div class="top-bar__links">
        <button type="button" class="scheme-toggle" data-scheme-toggle aria-pressed="false"><span class="scheme-toggle__icon" aria-hidden="true"></span><span class="scheme-toggle__text">Dunkel</span></button>
        <a class="top-bar__link" href="${telHref(club.telefon)}">${icon('phone')}<span class="top-bar__link-text">${esc(club.telefon)}</span></a>
        <a class="top-bar__link top-bar__link--login" href="${r}mitglieder/">${icon('user')}<span class="top-bar__link-text">Mitglieder-Login</span></a>
      </div>
    </div>
  </div>
  <div class="site-header__inner container">
    ${logo(r)}
    <button class="main-nav__toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-nav-toggle>
      <span class="main-nav__toggle-bars" aria-hidden="true"></span>
      <span class="main-nav__toggle-label">Menü</span>
    </button>
    ${mainNav(r, aktiv)}
  </div>
</header>`;

const footer = (r) => `
<footer class="site-footer">
  <div class="site-footer__inner container">
    <div class="site-footer__col site-footer__col--brand">
      ${logo(r)}
      <address class="site-footer__address">${club.adresse.map(esc).join('<br>')}</address>
      <p class="site-footer__contact">
        <a class="site-footer__link" href="${telHref(club.telefon)}">${icon('phone')} ${esc(club.telefon)}</a><br>
        <a class="site-footer__link" href="mailto:${club.email}">${icon('mail')} ${esc(club.email)}</a>
      </p>
    </div>
    <div class="site-footer__col">
      <h2 class="site-footer__title">Sekretariat</h2>
      ${openingHours('sekretariat', { mod: 'footer' })}
    </div>
    <nav class="site-footer__col footer-nav" aria-label="Fußnavigation">
      <h2 class="site-footer__title">Golf</h2>
      <ul class="footer-nav__list">
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}platz/">Platz & Bahnen</a></li>
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}greenfee/">Greenfee & Preise</a></li>
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}turniere/">Turniere</a></li>
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}golfschule/">Golfschule</a></li>
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}mannschaften/">Mannschaften</a></li>
      </ul>
    </nav>
    <nav class="site-footer__col footer-nav" aria-label="Club">
      <h2 class="site-footer__title">Club</h2>
      <ul class="footer-nav__list">
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}mitgliedschaft/">Mitglied werden</a></li>
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}restaurant/">Restaurant & Events</a></li>
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}news/">Aktuelles</a></li>
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}club/">Club & Kontakt</a></li>
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}mitglieder/">Mitgliederbereich</a></li>
      </ul>
    </nav>
  </div>
  <div class="site-footer__bottom">
    <div class="site-footer__bottom-inner container">
      <p class="site-footer__copy">© ${new Date().getFullYear()} ${esc(club.name)}</p>
      <ul class="footer-nav__list footer-nav__list--inline">
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}impressum/">Impressum</a></li>
        <li class="footer-nav__item"><a class="footer-nav__link" href="${r}datenschutz/">Datenschutz</a></li>
      </ul>
    </div>
  </div>
</footer>`;

function page({ path, title, description = club.claim, aktiv = '', body, bodyClass = '' }) {
  const tiefe = path.split('/').length - 1;
  const r = '../'.repeat(tiefe);
  const html = `<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${esc(title ? `${title} – ${club.kurzname}` : club.name)}</title>
<meta name="description" content="${esc(description)}">
<script>try{if(localStorage.getItem('golfplatz-farbschema')==='dunkel'){document.documentElement.classList.add('scheme--dark')}}catch(e){}</script>
<link rel="stylesheet" href="${r}assets/css/farben.css">
<link rel="stylesheet" href="${r}assets/css/tokens.css">
<link rel="stylesheet" href="${r}assets/css/main.css">
<script src="${r}assets/js/data.js" defer></script>
<script src="${r}assets/js/zeiten.js" defer></script>
<script src="${r}assets/js/main.js" defer></script>
<script src="${r}assets/js/zaehlkarte.js" defer></script>
</head>
<body class="${bodyClass}">
${header(r, aktiv)}
<main id="inhalt" class="site-main">
${typeof body === 'function' ? body(r) : body}
</main>
${footer(r)}
</body>
</html>
`;
  const ziel = join(OUT, path);
  mkdirSync(dirname(ziel), { recursive: true });
  writeFileSync(ziel, html);
  seiten.push(path);
}
const seiten = [];

// ---------- Hilfsdaten ----------
const summe = (liste, fn) => liste.reduce((s, x) => s + fn(x), 0);
const vorne = bahnen.slice(0, 9);
const hinten = bahnen.slice(9);
const gesamtLaenge = (id) => summe(bahnen, (b) => b.laengen[id]);
const bahnTitel = (b) => `Bahn ${b.nr}`;
const par = (b) => parText(b.parHerren, b.parDamen);
const parLang = (b) => (b.parHerren === b.parDamen ? `Par ${b.parHerren}` : `Par ${b.parHerren} Herren · ${b.parDamen} Damen`);
const parGesamt = (liste) => parText(summe(liste, (b) => b.parHerren), summe(liste, (b) => b.parDamen));
const geschlechtText = (a) => (a.geschlecht === 'herren' ? 'Herren' : 'Damen');
const minGreenfee = Math.min(...preise.greenfee.filter((p) => p.titel !== 'Rangefee').map((p) => p.betrag));
const mannschaftBySlug = Object.fromEntries(mannschaften.map((m) => [m.slug, m]));
const ligaspielById = Object.fromEntries(ligaspiele.map((l) => [l.id, l]));
const kommende = ligaspiele.filter((l) => l.datum >= HEUTE).sort((a, b) => a.datum.localeCompare(b.datum));
const vergangene = ligaspiele.filter((l) => l.datum < HEUTE).sort((a, b) => b.datum.localeCompare(a.datum));

const ligaspielTabelle = (liste, r, { mitMannschaft = true } = {}) => `
<div class="table-wrap">
<table class="match-table">
  <thead><tr>
    <th scope="col">Datum</th>
    ${mitMannschaft ? '<th scope="col">Mannschaft</th>' : ''}
    <th scope="col">Spieltag</th>
    <th scope="col">Spielort</th>
    <th scope="col">Ergebnis</th>
  </tr></thead>
  <tbody>
    ${liste
      .map((l) => {
        const m = mannschaftBySlug[l.mannschaft];
        const bericht = spielberichte.find((s) => s.ligaspiel === l.id);
        return `<tr class="match-table__row${l.heim ? ' match-table__row--home' : ''}">
      <td class="match-table__date"><time datetime="${l.datum}">${datumKurz(l.datum)}</time><span class="match-table__time">${l.uhrzeit} Uhr</span></td>
      ${mitMannschaft ? `<td><a class="match-table__team" href="${r}mannschaften/${m.slug}/">${esc(m.titel)}</a></td>` : ''}
      <td>${l.spieltag}. Spieltag</td>
      <td>${esc(l.spielort)}${l.heim ? ' <span class="badge badge--home">Heimspiel</span>' : ''}</td>
      <td>${l.platzierung ? `<strong>${l.platzierung}. Platz</strong>` : '–'}${bericht ? `<br><a class="match-table__report" href="${r}spielberichte/${bericht.slug}/">Spielbericht</a>` : ''}</td>
    </tr>`;
      })
      .join('')}
  </tbody>
</table>
</div>`;

const heroIllustration = `
<svg class="home-hero__art" viewBox="0 0 1440 640" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
  <defs>
    <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop class="home-hero__sky-top" offset="0"/><stop class="home-hero__sky-mid" offset=".6"/><stop class="home-hero__sky-bottom" offset="1"/></linearGradient>
  </defs>
  <rect width="1440" height="640" fill="url(#sky)"/>
  <circle class="home-hero__sun" cx="1120" cy="200" r="70"/>
  <path class="home-hero__hill home-hero__hill--far" d="M0 380 C200 330 380 350 560 320 S920 280 1120 330 1440 300 1440 300 V640 H0Z"/>
  <g class="home-hero__trees"><circle cx="160" cy="352" r="34"/><circle cx="205" cy="340" r="44"/><circle cx="250" cy="356" r="30"/><circle cx="1210" cy="318" r="36"/><circle cx="1260" cy="306" r="48"/><circle cx="1310" cy="322" r="32"/></g>
  <path class="home-hero__hill home-hero__hill--mid" d="M0 450 C260 400 520 430 760 400 S1180 380 1440 420 V640 H0Z"/>
  <path class="home-hero__hill home-hero__hill--near" d="M0 530 C300 480 640 520 900 490 S1300 470 1440 500 V640 H0Z"/>
  <ellipse class="home-hero__green" cx="930" cy="468" rx="120" ry="22"/>
  <ellipse class="home-hero__water" cx="560" cy="505" rx="110" ry="12"/>
  <line class="home-hero__pole" x1="950" y1="468" x2="950" y2="392"/>
  <path class="home-hero__flag" d="M950 392 l40 12 -40 12z"/>
  <path class="home-hero__ground" d="M0 600 C400 560 900 600 1440 570 V640 H0Z"/>
</svg>`;

// ---------- Startseite ----------
page({
  path: 'index.html',
  title: '',
  bodyClass: 'is-home',
  body: (r) => `
<section class="home-hero">
  ${heroIllustration}
  <div class="home-hero__inner container">
    <div class="home-hero__content">
      <p class="home-hero__eyebrow">Willkommen im ${esc(club.name)}</p>
      <h1 class="home-hero__title"><span class="home-hero__line">Golf mit Tradition,</span><span class="home-hero__line">Natur mit Weitblick.</span></h1>
      <p class="home-hero__lead">${esc(club.claim)} Mitglieder, Gäste und Einsteiger sind herzlich willkommen.</p>
      <div class="home-hero__actions">
        <a class="btn btn--primary btn--large" href="${r}greenfee/#spielen">Als Gast spielen</a>
        <a class="btn btn--ghost btn--large" href="${r}mitgliedschaft/">Mitglied werden</a>
      </div>
    </div>
    <aside class="status-summary" aria-labelledby="status-summary-title" data-status-summary>
      <p class="status-summary__eyebrow">Platzstatus heute</p>
      <p class="status-summary__state" id="status-summary-title"><span class="status-summary__dot" aria-hidden="true"></span><span data-summary-text>Wird geladen …</span></p>
      <div data-summary-body></div>
      <a class="status-summary__link link-arrow" href="#platzstatus">Morgen, Übungsanlagen &amp; Details</a>
    </aside>
  </div>
</section>

<div class="container home-status">
  ${statusBoard(r)}
</div>

<section class="section">
  <div class="container">
    ${sectionHead({ eyebrow: 'Für Sie da', title: 'Wie möchten Sie uns kennenlernen?', align: 'center' })}
    <div class="grid grid--4 entry-cards">
      <a class="entry-card" href="${r}mitgliedschaft/">
        <span class="entry-card__number">01</span>
        <h3 class="entry-card__title">Mitglied werden</h3>
        <p class="entry-card__text">Unbegrenzt spielen und Teil des Clubs werden – wir beraten Sie gern persönlich.</p>
        <span class="entry-card__more">Mitgliedschaften ${icon('arrow')}</span>
      </a>
      <a class="entry-card" href="${r}greenfee/">
        <span class="entry-card__number">02</span>
        <h3 class="entry-card__title">Als Gast spielen</h3>
        <p class="entry-card__text">Greenfee ab ${euro(minGreenfee)}. Bitte vor der Runde kurz anmelden.</p>
        <span class="entry-card__more">Greenfee & Preise ${icon('arrow')}</span>
      </a>
      <a class="entry-card" href="${r}golfschule/">
        <span class="entry-card__number">03</span>
        <h3 class="entry-card__title">Golf lernen</h3>
        <p class="entry-card__text">Schnupperkurs, Unterricht und DGV-Platzreife mit unseren PGA-Professionals.</p>
        <span class="entry-card__more">Golfschule ${icon('arrow')}</span>
      </a>
      <a class="entry-card" href="${r}restaurant/">
        <span class="entry-card__number">04</span>
        <h3 class="entry-card__title">Genießen & Feiern</h3>
        <p class="entry-card__text">Clubrestaurant mit Terrasse, Feiern und Firmenevents – auch für Nicht-Golfer.</p>
        <span class="entry-card__more">Restaurant ${icon('arrow')}</span>
      </a>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="container split">
    <div class="split__text">
      ${sectionHead({ eyebrow: 'Der Platz', title: `18 Bahnen in ${esc(club.ort)}`, lead: esc(club.texte.platzBeschreibung) })}
      <dl class="stats">
        <div class="stats__item"><dt class="stats__label">Bahnen</dt><dd class="stats__value">18</dd></div>
        <div class="stats__item"><dt class="stats__label">Par Herren/Damen</dt><dd class="stats__value">${parGesamt(bahnen)}</dd></div>
        <div class="stats__item"><dt class="stats__label">Länge Gelb</dt><dd class="stats__value">${zahl(gesamtLaenge('gelb'))} m</dd></div>
        <div class="stats__item"><dt class="stats__label">CR / Slope Gelb</dt><dd class="stats__value">${zahl(abschlaege[0].cr, 1)} / ${abschlaege[0].slope}</dd></div>
      </dl>
      <div class="button-row">
        <a class="btn btn--primary" href="${r}platz/">Platz & Bahnen</a>
        <a class="btn btn--outline" href="${r}platz/spielvorgaben/">Spielvorgaben-Rechner</a>
      </div>
    </div>
    <div class="split__media hole-strip">
      ${[1, 9, 18].map((nr) => { const b = bahnen[nr - 1]; return `<a class="hole-strip__item" href="${r}platz/bahn-${nr}/">${holeSvg(b)}<span class="hole-strip__label">${bahnTitel(b)}<small>${parLang(b)}</small></span></a>`; }).join('')}
    </div>
  </div>
</section>

<section class="section">
  <div class="container split split--wide-left">
    <div>
      ${sectionHead({ eyebrow: 'Aktuelles', title: 'Neues aus dem Club' })}
      <div class="grid grid--3">${news.slice(0, 3).map((n) => newsCard(n, r)).join('')}</div>
      <p class="more-link"><a class="link-arrow" href="${r}news/">Alle Nachrichten ${icon('arrow')}</a></p>
    </div>
    <aside class="side-box">
      <h2 class="side-box__title">Nächste Ligaspiele</h2>
      <ul class="event-list">
        ${kommende.slice(0, 4).map((l) => { const m = mannschaftBySlug[l.mannschaft]; return `<li class="event-list__item">
          <time class="event-list__date" datetime="${l.datum}"><span class="event-list__day">${l.datum.slice(8, 10)}</span><span class="event-list__month">${datumLang(l.datum).split(' ')[1].slice(0, 3)}</span></time>
          <span class="event-list__body"><a class="event-list__title" href="${r}mannschaften/${m.slug}/">${esc(m.titel)}</a><span class="event-list__meta">${esc(l.spielort)}${l.heim ? ' · Heimspiel' : ''}</span></span>
        </li>`; }).join('')}
      </ul>
      <p class="side-box__footer"><a class="link-arrow" href="${r}turniere/">Turnierkalender ${icon('arrow')}</a></p>
    </aside>
  </div>
</section>

<section class="section section--dark feature-band">
  <div class="container split">
    <div class="split__text">
      ${sectionHead({ eyebrow: 'Restaurant & Veranstaltungen', title: 'Das 19. Loch – für Golfer und Genießer', lead: restaurant.hinweis })}
      ${openingHours('restaurant', { mod: 'light' })}
      <div class="button-row">
        <a class="btn btn--secondary" href="${r}restaurant/">Speisekarte & Öffnungszeiten</a>
        <a class="btn btn--ghost" href="${r}restaurant/#feiern">Feiern & Firmenevents</a>
      </div>
    </div>
    <div class="split__media feature-band__quote">
      <blockquote class="quote">
        <p class="quote__text">„Nach der Runde auf die Terrasse, ein kühles Getränk und der Blick über das Grün – schöner kann ein Tag nicht enden.“</p>
        <footer class="quote__author">Stimme eines Gastes</footer>
      </blockquote>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">${guestInfo()}</div>
</section>`,
});

// ---------- Platz & Bahnen ----------
const scorekarte = () => {
  const zeile = (b) => `<div role="row" class="scorecard__row">
    <div role="rowheader" class="scorecard__cell scorecard__hole"><a href="bahn-${b.nr}/" aria-label="Bahn ${b.nr}">${b.nr}</a></div>
    <div role="cell" class="scorecard__cell">${par(b)}</div><div role="cell" class="scorecard__cell">${b.hcp}</div>
    ${abschlaege.map((a) => `<div role="cell" class="scorecard__cell scorecard__len scorecard__len--${a.id}">${b.laengen[a.id]}</div>`).join('')}
  </div>`;
  const summenzeile = (label, liste) => `<div role="row" class="scorecard__row scorecard__row--sum">
    <div role="rowheader" class="scorecard__cell">${label}</div><div role="cell" class="scorecard__cell">${parGesamt(liste)}</div><div role="cell" class="scorecard__cell"></div>
    ${abschlaege.map((a) => `<div role="cell" class="scorecard__cell">${zahl(summe(liste, (b) => b.laengen[a.id]))}</div>`).join('')}
  </div>`;
  return `
<div class="table-wrap">
<div role="table" class="scorecard" aria-label="Scorekarte mit Par, Schwierigkeitsrang und Längen in Metern je Abschlag">
  <div role="row" class="scorecard__row scorecard__row--head">
    <div role="columnheader" class="scorecard__cell">Loch</div><div role="columnheader" class="scorecard__cell">Par <small>H/D</small></div><div role="columnheader" class="scorecard__cell">HCP</div>
    ${abschlaege.map((a) => `<div role="columnheader" class="scorecard__cell">${teeBadge(a.id, a.name)}<small class="scorecard__gender">${geschlechtText(a)}</small></div>`).join('')}
  </div>
  ${vorne.map(zeile).join('')}
  ${summenzeile('Out', vorne)}
  ${hinten.map(zeile).join('')}
  ${summenzeile('In', hinten)}
  ${summenzeile('Gesamt', bahnen)}
</div>
</div>`;
};

const ratingTabelle = () => `
<div class="table-wrap">
<div role="table" class="rating-table" aria-label="Course und Slope Rating je Abschlag">
  <div role="row" class="rating-table__row rating-table__row--head">${['Abschlag', 'Für', 'CR', 'Slope', 'Par'].map((k) => `<div role="columnheader" class="rating-table__cell">${k}</div>`).join('')}</div>
  ${abschlaege.map((a) => `<div role="row" class="rating-table__row"><div role="rowheader" class="rating-table__cell">${teeBadge(a.id, a.name)}<span class="rating-table__len">${zahl(gesamtLaenge(a.id))} m</span></div>
    <div role="cell" class="rating-table__cell">${geschlechtText(a)}</div><div role="cell" class="rating-table__cell">${zahl(a.cr, 1)}</div><div role="cell" class="rating-table__cell">${a.slope}</div><div role="cell" class="rating-table__cell">${a.par}</div></div>`).join('')}
</div>
</div>`;

page({
  path: 'platz/index.html',
  title: 'Platz & Bahnen',
  aktiv: 'platz',
  body: (r) => `
${pageHero({ r, crumbs: [['Platz & Bahnen']], eyebrow: 'Der Platz', title: 'Platz & Bahnen', lead: `18 Loch, Par ${parGesamt(bahnen)} (Herren/Damen), ${zahl(gesamtLaenge('gelb'))} Meter von Gelb. Vier Abschläge – zwei für Herren, zwei für Damen.` })}
<section class="section">
  <div class="container split">
    <div class="split__text prose">
      <h2>Unser Platz</h2>
      <p>${esc(club.texte.platzBeschreibung)}</p>
      <p>Zum Üben gibt es Driving Range, Kurzspielbereich und Putting-Grün.</p>
      <p><a class="link-arrow" href="spielvorgaben/">Spielvorgaben und Rechner ${icon('arrow')}</a></p>
    </div>
    <div class="split__media">
      <h2 class="h4">Course & Slope Rating</h2>
      ${ratingTabelle()}
    </div>
  </div>
</section>
<section class="section section--tint">
  <div class="container">
    ${sectionHead({ eyebrow: 'Hole by Hole', title: 'Alle 18 Bahnen' })}
    <ol class="hole-grid">
      ${bahnen.map((b) => `<li class="hole-card">
        <a class="hole-card__link" href="bahn-${b.nr}/">
          <span class="hole-card__map">${holeSvg(b)}</span>
          <span class="hole-card__body">
            <span class="hole-card__number">${b.nr}</span>
            <span class="hole-card__name">Bahn ${b.nr}</span>
            <span class="hole-card__facts">Par ${par(b)} · HCP ${b.hcp} · ${b.laengen.gelb} m</span>
          </span>
        </a>
      </li>`).join('')}
    </ol>
  </div>
</section>
<section class="section" id="scorekarte">
  <div class="container">
    ${sectionHead({ eyebrow: 'Scorekarte', title: 'Längen in Metern je Abschlag' })}
    ${scorekarte()}
  </div>
</section>`,
});

for (const b of bahnen) {
  const prev = bahnen[b.nr - 2];
  const next = bahnen[b.nr];
  page({
    path: `platz/bahn-${b.nr}/index.html`,
    title: bahnTitel(b),
    description: b.beschreibung,
    aktiv: 'platz',
    body: (r) => `
${pageHero({ r, crumbs: [['Platz & Bahnen', 'platz/'], [`Bahn ${b.nr}`]], eyebrow: `Hole by Hole · ${b.nr} von 18`, title: `Bahn ${b.nr}`, lead: `${parLang(b)} · HCP ${b.hcp}` })}
<section class="section">
  <div class="container hole-detail">
    <figure class="hole-detail__map">${holeSvg(b)}<figcaption class="hole-detail__caption">Bahngrafik (Platzhalter für das Luftbild)</figcaption></figure>
    <div class="hole-detail__content">
      <dl class="hole-facts">
        <div class="hole-facts__item"><dt class="hole-facts__label">Par Herren</dt><dd class="hole-facts__value">${b.parHerren}</dd></div>
        <div class="hole-facts__item"><dt class="hole-facts__label">Par Damen</dt><dd class="hole-facts__value">${b.parDamen}</dd></div>
        <div class="hole-facts__item"><dt class="hole-facts__label">HCP</dt><dd class="hole-facts__value">${b.hcp}</dd></div>
        ${abschlaege.map((a) => `<div class="hole-facts__item hole-facts__item--tee"><dt class="hole-facts__label">${teeBadge(a.id, a.name)}</dt><dd class="hole-facts__value">${b.laengen[a.id]} m</dd></div>`).join('')}
      </dl>
      ${b.beschreibung ? `<div class="prose"><h2>Die Bahn</h2><p>${esc(b.beschreibung)}</p></div>` : ''}
      ${b.spieltipp ? `<aside class="tip"><p class="tip__label">Spieltipp vom Pro</p><p class="tip__text">${esc(b.spieltipp)}</p></aside>` : ''}
      <figure class="hole-video">
        <div class="hole-video__player hole-video__player--placeholder" role="img" aria-label="Platzhalter für das Bahnvideo">
          <span class="hole-video__play" aria-hidden="true"></span>
          <span class="hole-video__note">Video zur Bahn ${b.nr}<br><small>Ausgabe später als &lt;video controls preload="none" poster="…"&gt; aus der Mediathek</small></span>
        </div>
        <figcaption class="hole-video__caption">Überflug Bahn ${b.nr}</figcaption>
      </figure>
    </div>
  </div>
</section>
<nav class="section section--tint hole-pager" aria-label="Bahnen">
  <div class="container">
    <div class="hole-pager__prevnext">
      ${prev ? `<a class="hole-pager__link hole-pager__link--prev" href="../bahn-${prev.nr}/"><small>Vorherige Bahn</small>${bahnTitel(prev)}</a>` : '<span></span>'}
      ${next ? `<a class="hole-pager__link hole-pager__link--next" href="../bahn-${next.nr}/"><small>Nächste Bahn</small>${bahnTitel(next)}</a>` : `<a class="hole-pager__link hole-pager__link--next" href="../"><small>Geschafft</small>Zur Platzübersicht</a>`}
    </div>
    <ol class="hole-pager__list">
      ${bahnen.map((x) => `<li><a class="hole-pager__number${x.nr === b.nr ? ' hole-pager__number--active' : ''}" href="../bahn-${x.nr}/"${x.nr === b.nr ? ' aria-current="page"' : ''}>${x.nr}</a></li>`).join('')}
    </ol>
  </div>
</nav>`,
  });
}

// ---------- Zählkarte (wie Etch-Komponente Zaehlkarte) ----------
const zaehlkarte = () => {
  const wert = (name, inhalt = '–') => `<div role="cell" class="score-calc__cell score-calc__cell--${name}" data-sc-${name}>${inhalt}</div>`;
  const kopf = [['Loch'], ['Par'], ['HCP', 'Schwierigkeit des Lochs (1 = am schwersten)'], ['Vorgabe', 'Vorgabeschläge auf diesem Loch'], ['Schläge'], ['Netto'], ['Pkt. brutto', 'Stableford-Punkte brutto'], ['Pkt. netto', 'Stableford-Punkte netto']];
  const loch = (b) => `<div role="row" class="score-calc__row" data-sc-loch="${b.nr}" data-hcp="${b.hcp}" data-par-herren="${b.parHerren}" data-par-damen="${b.parDamen}">
    <div role="rowheader" class="score-calc__cell score-calc__hole">${b.nr}</div>${wert('par', par(b))}<div role="cell" class="score-calc__cell">${b.hcp}</div>${wert('vorgabe')}
    <div role="cell" class="score-calc__cell score-calc__cell--eingabe"><input class="score-calc__strokes" type="number" inputmode="numeric" min="1" max="20" step="1" aria-label="Schläge Loch ${b.nr}" data-sc-schlaege></div>${wert('netto')}${wert('pb')}${wert('pn')}</div>`;
  const summe = (key, label) => `<div role="row" class="score-calc__row score-calc__row--sum" data-sc-summe="${key}"><div role="rowheader" class="score-calc__cell">${label}</div>${wert('par', '')}<div role="cell" class="score-calc__cell"></div>${wert('vorgabe')}${wert('schlaege')}${wert('netto')}${wert('pb')}${wert('pn')}</div>`;
  const stat = (key, label) => `<div class="score-calc__stat score-calc__stat--${key}"><dt class="score-calc__stat-label">${label}</dt><dd class="score-calc__stat-value" data-sc-ergebnis="${key}">–</dd></div>`;
  return `
<div class="score-calc" data-score-calc data-sc-club="${esc(club.name)}">
  <div class="score-calc__settings">
    <div class="score-calc__field"><label class="score-calc__label" for="sc-hi">Handicap-Index</label><input class="score-calc__input" id="sc-hi" type="text" inputmode="decimal" placeholder="z. B. 18,4" autocomplete="off" data-sc-hi aria-describedby="sc-hi-hinweis"><span class="score-calc__hint" id="sc-hi-hinweis">von +5,0 bis 54,0</span></div>
    <div class="score-calc__field"><label class="score-calc__label" for="sc-tee">Abschlag</label><select class="score-calc__select" id="sc-tee" data-sc-tee>${abschlaege.map((a) => `<option value="${a.id}" data-cr="${a.cr}" data-slope="${a.slope}" data-par="${a.par}" data-geschlecht="${a.geschlecht}">${a.name} · ${geschlechtText(a)}</option>`).join('')}</select></div>
    <div class="score-calc__field score-calc__field--sv"><span class="score-calc__label" id="sc-sv-label">Ihre Spielvorgabe</span><output class="score-calc__sv" data-sc-sv aria-labelledby="sc-sv-label" aria-live="polite">–</output></div>
  </div>
  <div class="score-calc__shared" data-sc-geteilt hidden><p class="score-calc__shared-text">Sie sehen eine geteilte Runde. Sobald Sie etwas ändern, wird sie als Ihre Runde gespeichert.</p><button class="btn btn--outline score-calc__own" type="button" data-sc-eigene>Meine eigene Runde anzeigen</button></div>
  <p class="calculator__error" data-sc-error hidden role="alert">Bitte einen Handicap-Index zwischen +5,0 und 54,0 eingeben.</p>
  <div class="table-wrap">
  <div role="table" class="score-calc__table" aria-label="Zählkarte: Vorgabe, Schläge und Punkte je Loch">
    <div role="row" class="score-calc__row score-calc__row--head">${kopf.map(([k, titel]) => `<div role="columnheader" class="score-calc__cell">${titel ? `<abbr title="${titel}">${k}</abbr>` : k}</div>`).join('')}</div>
    ${vorne.map(loch).join('')}${summe('out', 'Out')}${hinten.map(loch).join('')}${summe('in', 'In')}${summe('gesamt', 'Gesamt')}
  </div>
  </div>
  <div class="score-calc__footer">
    <dl class="score-calc__summary" aria-live="polite">${stat('gespielt', 'Gespielte Löcher')}${stat('brutto', 'Brutto')}${stat('netto', 'Netto')}${stat('pb', 'Stableford brutto')}${stat('pn', 'Stableford netto')}</dl>
    <div class="score-calc__actions"><button class="btn btn--primary score-calc__share" type="button" data-sc-teilen>Ergebnis teilen</button><button class="btn btn--outline score-calc__download" type="button" data-sc-html>Als HTML speichern</button><button class="btn btn--outline score-calc__reset" type="button" data-sc-reset>Schläge löschen</button></div>
  </div>
  <p class="score-calc__status" data-sc-status role="status" hidden></p>
  <p class="score-calc__note">Vorgabeschläge nach Loch-HCP verteilt. Netto = Schläge − Vorgabe. Stableford: Par = 2 Punkte, je Schlag besser +1, ab zwei über Par (netto: über Par plus Vorgabe) 0 Punkte. Ihre Eingaben bleiben nur in diesem Browser gespeichert.</p>
</div>`;
};

// ---------- Spielvorgaben ----------
page({
  path: 'platz/spielvorgaben/index.html',
  title: 'Spielvorgaben',
  aktiv: 'spielvorgaben',
  body: (r) => `
${pageHero({ r, crumbs: [['Platz & Bahnen', 'platz/'], ['Spielvorgaben']], eyebrow: 'World Handicap System', title: 'Spielvorgaben', lead: 'Welche Spielvorgabe haben Sie auf unserem Platz? Geben Sie Ihren Handicap-Index ein oder lesen Sie ihn in den Tabellen je Abschlag ab.' })}
<section class="section">
  <div class="container split">
    <div class="calculator" data-calculator>
      <h2 class="calculator__title">Spielvorgaben-Rechner</h2>
      <label class="calculator__label" for="hi-input">Ihr Handicap-Index</label>
      <div class="calculator__input-row">
        <input class="calculator__input" id="hi-input" type="text" inputmode="decimal" placeholder="z. B. 18,4 oder +1,2" autocomplete="off" data-calculator-input>
        <span class="calculator__hint">von +5,0 bis 54,0</span>
      </div>
      <p class="calculator__error" data-calculator-error hidden>Bitte einen Wert zwischen +5,0 und 54,0 eingeben.</p>
      <div role="table" class="calculator__result" aria-label="Ihre Spielvorgabe je Abschlag">
        <div role="row" class="calculator__row calculator__row--head"><div role="columnheader" class="calculator__cell">Abschlag</div><div role="columnheader" class="calculator__cell">Für</div><div role="columnheader" class="calculator__cell">Spiel&shy;vorgabe</div></div>
        ${abschlaege.map((a) => `<div role="row" class="calculator__row" data-cr="${a.cr}" data-slope="${a.slope}" data-par="${a.par}"><div role="rowheader" class="calculator__cell">${teeBadge(a.id, a.name)}</div><div role="cell" class="calculator__cell">${geschlechtText(a)}</div><div role="cell" class="calculator__cell calculator__sv" data-sv="${a.id}" aria-live="polite">–</div></div>`).join('')}
      </div>
    </div>
    <div class="prose">
      <h2>So wird gerechnet</h2>
      <p>Die Spielvorgabe ergibt sich nach dem World Handicap System aus Ihrem Handicap-Index und der Bewertung des Abschlags:</p>
      <p class="formula">Spielvorgabe = Handicap-Index × Slope ÷ 113 + (Course Rating − Par)</p>
      <p>Das Ergebnis wird auf eine ganze Zahl gerundet. Die Tabellen unten werden aus denselben Werten berechnet. Ändert der Verband Course Rating oder Slope, passen sich alle Tabellen automatisch an.</p>
      ${ratingTabelle()}
    </div>
  </div>
</section>
<section class="section section--tint">
  <div class="container">
    ${sectionHead({ eyebrow: 'Tabellen', title: 'Spielvorgaben je Abschlag' })}
    <div class="tabs" data-tabs>
      <div class="tabs__list" role="tablist" aria-label="Abschlag wählen">
        ${abschlaege.map((a, i) => `<button class="tabs__tab${i === 0 ? ' tabs__tab--active' : ''}" role="tab" id="tab-${a.id}" aria-controls="panel-${a.id}" aria-selected="${i === 0}" tabindex="${i === 0 ? 0 : -1}" type="button">${teeBadge(a.id, a.name)}</button>`).join('')}
      </div>
      ${abschlaege.map((a, i) => `<div class="tabs__panel" role="tabpanel" id="panel-${a.id}" aria-labelledby="tab-${a.id}"${i === 0 ? '' : ' hidden'}>
        <div class="grid grid--2">
          ${[a].map((w) => `<div class="hcp-table">
            <h3 class="hcp-table__title">${geschlechtText(w)} · CR ${zahl(w.cr, 1)} · Slope ${w.slope} · Par ${w.par}</h3>
            <div role="table" class="hcp-table__table" aria-label="Spielvorgaben Abschlag ${w.name}">
              <div role="row" class="hcp-table__row hcp-table__row--head"><div role="columnheader" class="hcp-table__cell">Handicap-Index</div><div role="columnheader" class="hcp-table__cell">Spielvorgabe</div></div>
              ${spielvorgabenTabelle(w).map((z) => `<div role="row" class="hcp-table__row"><div role="cell" class="hcp-table__cell">${hiText(z.von)} – ${hiText(z.bis)}</div><div role="cell" class="hcp-table__cell">${svText(z.sv)}</div></div>`).join('')}
            </div>
          </div>`).join('')}
        </div>
      </div>`).join('')}
    </div>
  </div>
</section>
<section class="section">
  <div class="container prose">
    <h2 class="h3">Zählkarte für Ihre Runde</h2>
    <p>Mit der Zählkarte sehen Sie Ihre Vorgabeschläge auf jedem Loch und rechnen Brutto, Netto und Stableford-Punkte mit.</p>
    <p><a class="link-arrow" href="../zaehlkarte/">Zur Zählkarte ${icon('arrow')}</a></p>
  </div>
</section>`,
});

// ---------- Zählkarte ----------
page({
  path: 'platz/zaehlkarte/index.html',
  title: 'Zählkarte',
  aktiv: 'zaehlkarte',
  body: (r) => `
${pageHero({ r, crumbs: [['Platz & Bahnen', 'platz/'], ['Zählkarte']], eyebrow: 'Meine Runde', title: 'Zählkarte', lead: 'Handicap-Index und Abschlag wählen: Sie sehen auf jedem Loch Ihre Vorgabeschläge. Tragen Sie Ihre Schläge ein – Brutto, Netto und Stableford-Punkte rechnen sich mit.' })}
<section class="section" id="zaehlkarte">
  <div class="container">
    ${zaehlkarte()}
  </div>
</section>
<section class="section section--tint">
  <div class="container split">
    <div class="prose">
      <h2 class="h3">So wird gezählt</h2>
      <p>Die Spielvorgabe ergibt sich nach dem World Handicap System aus Handicap-Index, Course Rating, Slope und Par des gewählten Abschlags. Die Vorgabeschläge werden nach der Schwierigkeit der Löcher (HCP 1 = am schwersten) verteilt; bei einem Plus-Handicap geben Sie Schläge ab dem leichtesten Loch zurück.</p>
      <p>Stableford: Par ergibt 2 Punkte, jeder Schlag besser einen Punkt mehr. Ab zwei Schlägen über Par gibt es keinen Punkt – netto zählt Par plus Ihre Vorgabeschläge.</p>
      <p><a class="link-arrow" href="../spielvorgaben/">Spielvorgaben-Tabellen je Abschlag ${icon('arrow')}</a></p>
    </div>
    <div class="split__media"><h2 class="h4">Course &amp; Slope Rating</h2>${ratingTabelle()}</div>
  </div>
</section>`,
});

// ---------- Greenfee ----------
page({
  path: 'greenfee/index.html',
  title: 'Greenfee & Preise',
  aktiv: 'greenfee',
  body: (r) => `
${pageHero({ r, crumbs: [['Greenfee & Preise']], eyebrow: 'Für Gäste', title: 'Greenfee & Preise', lead: 'Gäste sind jeden Tag herzlich willkommen. Bitte melden Sie sich vor Ihrer Runde im Sekretariat an.' })}
<section class="section">
  <div class="container split split--wide-left">
    <div>
      <h2 class="h3">Greenfee ${new Date().getFullYear()}</h2>
      ${priceMatrix(preise.greenfee)}
      <p class="small">„R“ = DGV-Ausweis mit R-Kennzeichnung.</p>
      <h2 class="h3 spacer-top">Greenfee bei Turnieren</h2>
      ${priceMatrix(preise.turnier)}
      <h2 class="h3 spacer-top">Leihgeräte</h2>
      ${priceTable(preise.leihe, { mitTagen: false })}
    </div>
    <aside class="side-box">
      <h2 class="side-box__title">Gut zu wissen</h2>
      <ul class="check-list">
        ${preise.hinweise.map((h) => `<li class="check-list__item">${esc(h)}</li>`).join('')}
        ${club.anmeldung.ruhetag ? `<li class="check-list__item">${esc(club.anmeldung.ruhetag)}</li>` : ''}
      </ul>
    </aside>
  </div>
</section>
<section class="section section--tint" id="spielen">
  <div class="container">${guestInfo({ id: 'gast' })}</div>
</section>
<section class="section">
  <div class="container">
    ${sectionHead({ eyebrow: 'Anlage', title: 'Öffnungszeiten', lead: 'Sekretariat, Übungsanlagen, Proshop und Restaurant. Abweichende Zeiten und aktuelle Sperren sind markiert.' })}
    ${facilityHours()}
  </div>
</section>
<section class="section">
  <div class="container">
    ${sectionHead({ eyebrow: 'Kooperationen', title: 'Greenfee für Mitglieder unserer Partnerclubs', lead: preise.kooperationenHinweis })}
    ${priceMatrix(preise.kooperationen)}
  </div>
</section>`,
});

// ---------- Mitgliedschaft ----------
page({
  path: 'mitgliedschaft/index.html',
  title: 'Mitgliedschaft',
  aktiv: 'mitgliedschaft',
  body: (r) => `
${pageHero({ r, crumbs: [['Mitgliedschaft']], eyebrow: 'Mitglied werden', title: 'Ihr Heimatclub', lead: esc(club.texte.mitgliedschaftLead) })}
<section class="section">
  <div class="container">
    <div class="grid grid--2 price-cards">
      ${preise.mitgliedschaft.map((m) => `<article class="price-card${m.hervorheben ? ' price-card--featured' : ''}">
        
        <h2 class="price-card__title">${esc(m.titel)}</h2>
        <p class="price-card__price"><span class="price-card__amount">${preisText(m.betrag)}</span>${m.betrag !== null ? ` <span class="price-card__unit">${esc(einheitText[m.einheit])}</span>` : ''}</p>
        ${m.zusatz ? `<p class="price-card__note">${esc(m.zusatz)}</p>` : ''}
        <ul class="price-card__list">${m.leistungen.map((l) => `<li class="price-card__item">${esc(l)}</li>`).join('')}</ul>
        <a class="btn ${m.hervorheben ? 'btn--primary' : 'btn--outline'} price-card__button" href="#antrag">${m.betrag === null ? 'Gespräch vereinbaren' : 'Anfragen'}</a>
      </article>`).join('')}
    </div>
  </div>
</section>
<section class="section section--tint">
  <div class="container">
    ${sectionHead({ eyebrow: 'In drei Schritten', title: 'So werden Sie Mitglied', align: 'center' })}
    <ol class="steps">
      <li class="steps__item"><h3 class="steps__title">Kennenlernen</h3><p class="steps__text">Spielen Sie eine Runde als Gast oder besuchen Sie einen <a href="${r}golfschule/">Schnupperkurs</a>.</p></li>
      <li class="steps__item"><h3 class="steps__title">Persönliches Gespräch</h3><p class="steps__text">Welches Modell passt zu Ihnen? Wir beraten Sie und nennen Ihnen die aktuellen Konditionen.</p></li>
      <li class="steps__item"><h3 class="steps__title">Aufnahmeantrag</h3><p class="steps__text">Antrag unten ausfüllen oder als PDF herunterladen und im Sekretariat abgeben.</p></li>
    </ol>
  </div>
</section>
<section class="section" id="antrag">
  <div class="container split">
    <div class="prose">
      <h2>Aufnahmeantrag</h2>
      <p>Schreiben Sie uns, wir melden uns für ein persönliches Gespräch. ${esc(club.texte.mitgliedschaftKontakt)}${club.texte.aufnahmeantragUrl ? ` Lieber auf Papier? <a href="${esc(club.texte.aufnahmeantragUrl)}">Aufnahmeantrag als PDF</a>.` : ''}</p>
      <h3>Häufige Fragen</h3>
      <div class="accordion">
        <details class="accordion__item"><summary class="accordion__summary">Was brauche ich für die Aufnahme?</summary><div class="accordion__content"><p>Für das Spiel auf dem Platz die DGV-Platzreife oder ein Handicap. Beides können Sie auch bei uns in der Golfschule erwerben.</p></div></details>
        <details class="accordion__item"><summary class="accordion__summary">Kann ich vorher auf dem Platz spielen?</summary><div class="accordion__content"><p>Ja. Spielen Sie eine Runde als Gast – im persönlichen Gespräch rechnen wir Ihr Greenfee auf Wunsch an.</p></div></details>
        <details class="accordion__item"><summary class="accordion__summary">Gibt es Partnerclubs?</summary><div class="accordion__content"><p>Ja. Auf den Plätzen unserer Partnerclubs spielen Mitglieder zu besonderen Konditionen – Details auf der Seite Greenfee &amp; Preise.</p></div></details>
      </div>
    </div>
    ${prototypeForm({
      felder: [
        ['vorname', 'Vorname'], ['nachname', 'Nachname'], ['email', 'E-Mail', 'email'], ['telefon', 'Telefon', 'tel'],
        ['modell', 'Mitgliedschaft', 'select:' + preise.mitgliedschaft.map((m) => m.titel).join('|'), true],
        ['nachricht', 'Nachricht', 'textarea', true],
      ],
      button: 'Antrag senden',
      hinweis: 'Mit dem Absenden stimmen Sie der Verarbeitung Ihrer Daten gemäß <a href="' + r + 'datenschutz/">Datenschutzerklärung</a> zu.',
    })}
  </div>
</section>`,
});

// ---------- Turniere (PC CADDIE) ----------
page({
  path: 'turniere/index.html',
  title: 'Turniere & Kalender',
  aktiv: 'turniere',
  body: (r) => `
${pageHero({ r, crumbs: [['Turniere & Kalender']], eyebrow: 'Spielbetrieb', title: 'Turniere & Kalender', lead: 'Alle Clubturniere mit Ausschreibung, Meldung und Ergebnissen. Die Daten kommen direkt aus PC CADDIE.' })}
<section class="section">
  <div class="container">
    <div class="tabs" data-tabs>
      <div class="tabs__list" role="tablist" aria-label="Turnierbereich">
        ${[['kalender', 'Turnierkalender'], ['meldung', 'Meldung'], ['ergebnisse', 'Ergebnisse']].map(([id, label], i) => `<button class="tabs__tab${i === 0 ? ' tabs__tab--active' : ''}" role="tab" id="tab-${id}" aria-controls="panel-${id}" aria-selected="${i === 0}" tabindex="${i === 0 ? 0 : -1}" type="button">${label}</button>`).join('')}
      </div>
      ${[['kalender', 'Turnierkalender'], ['meldung', 'Online-Meldung'], ['ergebnisse', 'Ergebnislisten']].map(([id, label], i) => `<div class="tabs__panel" role="tabpanel" id="panel-${id}" aria-labelledby="tab-${id}"${i === 0 ? '' : ' hidden'}>
        <div class="embed-placeholder">
          <p class="embed-placeholder__title">PC CADDIE · ${label}</p>
          <p class="embed-placeholder__text">Hier wird das Modul „${label}“ von PC CADDIE eingebettet. Einbettungscode, Design-Anpassung und Cookie-Consent sind noch zu klären.</p>
        </div>
      </div>`).join('')}
    </div>
  </div>
</section>
<section class="section section--tint">
  <div class="container split">
    <div class="prose">
      <h2>Ligaspiele der Mannschaften</h2>
      <p>Die Ligaspiele unserer zehn Mannschaften pflegen wir direkt auf der Website. Termine, Ergebnisse und Spielberichte finden Sie bei den Mannschaften.</p>
      <p><a class="btn btn--outline" href="${r}mannschaften/ligaspiele/">Alle Ligaspiele</a></p>
    </div>
    <div class="prose">
      <h2>Abschlagsperren</h2>
      <p>Während Turnieren und Ligaspielen sind Abschlag 1 oder 10 zeitweise gesperrt. Den aktuellen Stand für heute und morgen sehen Sie auf der <a href="${r}#platzstatus">Startseite</a>.</p>
    </div>
  </div>
</section>`,
});

// ---------- Golfschule ----------
page({
  path: 'golfschule/index.html',
  title: 'Golfschule',
  aktiv: 'golfschule',
  body: (r) => `
${pageHero({ r, crumbs: [['Golfschule']], eyebrow: 'Golf lernen', title: 'Golfschule', lead: 'Vom ersten Schwung bis zur DGV-Platzreife: Unsere Professionals begleiten Sie einzeln oder in kleinen Gruppen.' })}
<section class="section">
  <div class="container">
    ${sectionHead({ eyebrow: 'Angebot', title: 'Kurse & Training' })}
    <div class="grid grid--2">
      ${kurse.map((k) => `<article class="course-card">
        <p class="course-card__type">${esc(k.typ)}</p>
        <h3 class="course-card__title">${esc(k.titel)}</h3>
        <p class="course-card__text">${esc(k.text)}</p>
        <dl class="course-card__facts">
          <div><dt>Preis</dt><dd>${preisText(k.preis)}</dd></div>
          ${k.dauer ? `<div><dt>Dauer</dt><dd>${esc(k.dauer)}</dd></div>` : ''}
          ${k.max ? `<div><dt>Teilnehmer</dt><dd>max. ${k.max}</dd></div>` : ''}
        </dl>
        ${k.termine.length ? `<p class="course-card__dates"><strong>Termine:</strong> ${k.termine.map(esc).join(' · ')}</p>` : ''}
      </article>`).join('')}
    </div>
    <p class="note">${icon('phone')} Anmeldung unter <a href="${telHref(kurseAnmeldung.telefon)}">${esc(kurseAnmeldung.telefon)}</a> oder <a href="mailto:${kurseAnmeldung.email}?subject=Golf%20lernen">${esc(kurseAnmeldung.email)}</a>.</p>
  </div>
</section>
<section class="section section--tint">
  <div class="container">
    ${sectionHead({ eyebrow: 'Unsere Pros', title: 'Das Team der Golfschule' })}
    <div class="grid grid--2">${personenIn('golfschule').map(personCard).join('')}</div>
  </div>
</section>
<section class="section">
  <div class="container prose prose--narrow">
    <h2>Was ist die Platzreife?</h2>
    <p>Mit der DGV-Platzreife weisen Sie nach, dass Sie die Grundschläge, die Golfregeln und die Etikette beherrschen. Sie ist die Erlaubnis, eigenständig auf dem Platz zu spielen. Unsere Professionals bereiten Sie in Einzelstunden oder kleinen Gruppen darauf vor.</p>
  </div>
</section>`,
});

// ---------- Mannschaften ----------
page({
  path: 'mannschaften/index.html',
  title: 'Mannschaften',
  aktiv: 'mannschaften',
  body: (r) => `
${pageHero({ r, crumbs: [['Mannschaften']], eyebrow: 'Ligabetrieb', title: 'Unsere Mannschaften', lead: `${mannschaften.length} Mannschaften vertreten den Club in den Ligen des Landesverbands – von der Clubmannschaft bis zur AK65.` })}
<section class="section">
  <div class="container">
    <div class="grid grid--3 team-grid">
      ${mannschaften.map((m) => {
        const naechstes = kommende.find((l) => l.mannschaft === m.slug);
        return `<article class="team-card">
        <p class="team-card__league">${esc(m.liga)}</p>
        <h2 class="team-card__title"><a class="team-card__link" href="${m.slug}/">${esc(m.titel)}</a></h2>
        <p class="team-card__captain">Spielführer: ${esc(m.spielfuehrer)}</p>
        <p class="team-card__next">${naechstes ? `Nächstes Spiel: <time datetime="${naechstes.datum}">${datumKurz(naechstes.datum)}</time>, ${esc(naechstes.spielort)}` : 'Saison beendet'}</p>
      </article>`;
      }).join('')}
    </div>
    <p class="more-link"><a class="btn btn--outline" href="ligaspiele/">Alle Ligaspiele im Überblick</a></p>
  </div>
</section>`,
});

page({
  path: 'mannschaften/ligaspiele/index.html',
  title: 'Ligaspiele',
  aktiv: 'mannschaften',
  body: (r) => `
${pageHero({ r, crumbs: [['Mannschaften', 'mannschaften/'], ['Ligaspiele']], eyebrow: 'Saison ' + new Date().getFullYear(), title: 'Alle Ligaspiele', lead: 'Termine und Ergebnisse aller Mannschaften. Heimspiele sind hervorgehoben.' })}
<section class="section">
  <div class="container">
    <h2 class="h3">Kommende Spiele</h2>
    ${kommende.length ? ligaspielTabelle(kommende, r) : '<p>Keine weiteren Spiele in dieser Saison.</p>'}
    <h2 class="h3 spacer-top">Vergangene Spiele</h2>
    ${ligaspielTabelle(vergangene, r)}
  </div>
</section>`,
});

for (const m of mannschaften) {
  const spiele = ligaspiele.filter((l) => l.mannschaft === m.slug);
  const berichte = spielberichte.filter((s) => ligaspielById[s.ligaspiel].mannschaft === m.slug);
  page({
    path: `mannschaften/${m.slug}/index.html`,
    title: m.titel,
    aktiv: 'mannschaften',
    body: (r) => `
${pageHero({ r, crumbs: [['Mannschaften', 'mannschaften/'], [m.titel]], eyebrow: m.liga, title: esc(m.titel) })}
<section class="section">
  <div class="container split split--wide-left">
    <div>
      <div class="team-photo" role="img" aria-label="Platzhalter Mannschaftsfoto"><span>Mannschaftsfoto</span></div>
      <h2 class="h3 spacer-top">Ligaspiele</h2>
      ${ligaspielTabelle(spiele.slice().sort((a, b) => a.datum.localeCompare(b.datum)), r, { mitMannschaft: false })}
      ${berichte.length ? `<h2 class="h3 spacer-top">Spielberichte</h2><ul class="report-list">${berichte.map((s) => `<li class="report-list__item"><a class="report-list__link" href="${r}spielberichte/${s.slug}/">${esc(s.titel)}</a><span class="report-list__meta">${datumLang(ligaspielById[s.ligaspiel].datum)}</span></li>`).join('')}</ul>` : ''}
    </div>
    <aside class="side-box">
      <h2 class="side-box__title">Spielführer</h2>
      <p class="side-box__captain">${icon('user')} ${esc(m.spielfuehrer)}</p>
      <h2 class="side-box__title">Kader</h2>
      <ul class="roster">${m.kader.map((n) => `<li class="roster__item">${esc(n)}${n === m.spielfuehrer ? ' <span class="badge">Spielführer</span>' : ''}</li>`).join('')}</ul>
      <p class="small">Es erscheinen nur Spieler, die der Veröffentlichung zugestimmt haben.</p>
    </aside>
  </div>
</section>`,
  });
}

for (const s of spielberichte) {
  const l = ligaspielById[s.ligaspiel];
  const m = mannschaftBySlug[l.mannschaft];
  page({
    path: `spielberichte/${s.slug}/index.html`,
    title: s.titel,
    aktiv: 'mannschaften',
    body: (r) => `
${pageHero({ r, crumbs: [['Mannschaften', 'mannschaften/'], [m.titel, `mannschaften/${m.slug}/`], ['Spielbericht']], eyebrow: `${m.titel} · ${l.spieltag}. Spieltag · ${datumLang(l.datum)}`, title: esc(s.titel) })}
<section class="section">
  <div class="container prose prose--narrow">
    <p class="article-meta">Von ${esc(s.autor)} · ${esc(l.spielort)} · ${l.platzierung ? l.platzierung + '. Platz' : ''}</p>
    ${s.text.map((p) => `<p>${esc(p)}</p>`).join('')}
    <p><a class="link-arrow" href="${r}mannschaften/${m.slug}/">Zur Mannschaft ${esc(m.titel)} ${icon('arrow')}</a></p>
  </div>
</section>`,
  });
}

// ---------- Restaurant ----------
page({
  path: 'restaurant/index.html',
  title: 'Restaurant & Veranstaltungen',
  aktiv: 'restaurant',
  body: (r) => `
${pageHero({ r, crumbs: [['Restaurant']], eyebrow: 'Gastronomie', title: esc(restaurant.name), lead: 'Für Golfer und alle anderen Gäste.' })}
<section class="section">
  <div class="container split">
    <div>
      ${restaurant.hinweis ? `<p class="notice">${esc(restaurant.hinweis)}</p>` : ''}
      <h2 class="h3">Öffnungszeiten</h2>${openingHours('restaurant')}
      ${restaurant.telefon ? `<p class="spacer-top"><a class="btn btn--primary" href="${telHref(restaurant.telefon)}">${icon('phone')} Tisch reservieren</a></p>` : ''}
    </div>
    <div class="menu-card">
      <h2 class="menu-card__title">Aus der Speisekarte</h2>
      ${[
        ['Vorspeisen', [['Kürbissuppe mit Kernöl', 7.5], ['Feldsalat mit Speck und Croûtons', 9.5]]],
        ['Hauptgerichte', [['Wiener Schnitzel mit Bratkartoffeln', 22], ['Zander auf Rahmsauerkraut', 24.5], ['Pilzrisotto mit Parmesan', 17.5]]],
        ['Für die Runde', [['Clubsandwich', 12.5], ['Currywurst mit Pommes frites', 10.5]]],
      ].map(([titel, gerichte]) => `<h3 class="menu-card__section">${titel}</h3><ul class="menu-card__list">${gerichte.map(([g, p]) => `<li class="menu-card__item"><span class="menu-card__dish">${g}</span><span class="menu-card__price">${euro(p)}</span></li>`).join('')}</ul>`).join('')}
      <p class="menu-card__footer"><a class="link-arrow" href="#">Komplette Speisekarte (PDF) ${icon('arrow')}</a></p>
    </div>
  </div>
</section>
<section class="section section--tint" id="feiern">
  <div class="container">
    ${sectionHead({ eyebrow: 'Veranstaltungen', title: 'Feiern & Firmenevents', lead: 'Vom Geburtstag bis zur Firmenfeier mit Golf-Schnupperkurs: Wir planen Ihre Veranstaltung mit Ihnen.' })}
    <div class="grid grid--3">
      <article class="card"><h3 class="card__title">Familienfeiern</h3><p class="card__text">Geburtstage, Taufen, Jubiläen – im Clubraum für bis zu 60 Gäste oder auf der Terrasse.</p></article>
      <article class="card"><h3 class="card__title">Hochzeiten</h3><p class="card__text">Freie Trauung am Weiher und Feier im Clubhaus mit bis zu 120 Gästen.</p></article>
      <article class="card"><h3 class="card__title">Firmen-Golf-Tag</h3><p class="card__text">Schnupperkurs mit unseren Pros, kleines Turnier und Abendessen. Ideal als Teamevent, auch für Nicht-Golfer.</p></article>
    </div>
  </div>
</section>
<section class="section">
  <div class="container split">
    <div class="prose"><h2>Veranstaltung anfragen</h2><p>Erzählen Sie uns von Ihren Plänen. Wir melden uns mit einem Vorschlag bei Ihnen.</p><p>Telefon: <a href="${telHref(restaurant.telefon)}">${esc(restaurant.telefon)}</a></p></div>
    ${prototypeForm({
      felder: [['name', 'Name'], ['email', 'E-Mail', 'email'], ['anlass', 'Anlass', 'select:Familienfeier|Hochzeit|Firmenevent|Sonstiges'], ['datum', 'Wunschtermin', 'date'], ['personen', 'Anzahl Gäste', 'number'], ['telefon', 'Telefon', 'tel'], ['nachricht', 'Ihre Nachricht', 'textarea', true]],
      button: 'Anfrage senden',
    })}
  </div>
</section>`,
});

// ---------- News ----------
page({
  path: 'news/index.html',
  title: 'Aktuelles',
  aktiv: 'news',
  body: (r) => `
${pageHero({ r, crumbs: [['Aktuelles']], eyebrow: 'News', title: 'Aktuelles aus dem Club' })}
<section class="section">
  <div class="container">
    <div class="grid grid--3">${news.map((n) => newsCard(n, r)).join('')}</div>
  </div>
</section>`,
});

for (const n of news) {
  page({
    path: `news/${n.slug}/index.html`,
    title: n.titel,
    description: n.teaser,
    aktiv: 'news',
    body: (r) => `
${pageHero({ r, crumbs: [['Aktuelles', 'news/'], [n.titel]], eyebrow: `${n.kategorie} · ${datumLang(n.datum)}`, title: esc(n.titel) })}
<section class="section">
  <div class="container prose prose--narrow">
    <p class="lead">${esc(n.teaser)}</p>
    ${n.mitglieder
      ? `<div class="members-lock">${icon('lock')}<div><p><strong>Dieser Beitrag ist nur für Mitglieder.</strong></p><p><a class="btn btn--primary" href="${r}mitglieder/">Anmelden</a></p></div></div>`
      : `<p>Hier steht der vollständige Beitragstext. Er wird im WordPress-Editor gepflegt und kann Bilder, Zwischenüberschriften und Links enthalten.</p><p>Das Sekretariat legt News als normale Beiträge an. Über den Schalter „Nur für Mitglieder“ lässt sich ein Beitrag auf angemeldete Mitglieder beschränken.</p>`}
    <p><a class="link-arrow" href="${r}news/">Alle Nachrichten ${icon('arrow')}</a></p>
  </div>
</section>`,
  });
}

// ---------- Club & Kontakt ----------
page({
  path: 'club/index.html',
  title: 'Club & Kontakt',
  aktiv: 'club',
  body: (r) => `
${pageHero({ r, crumbs: [['Club & Kontakt']], eyebrow: 'Über uns', title: 'Club & Kontakt', lead: `Golf in ${esc(club.ort)}. Ansprechpartner, Anfahrt und Kontakt.` })}
<nav class="section section--compact subnav" aria-label="Auf dieser Seite">
  <div class="container">
    <ul class="subnav__list">
      ${[['vorstand', 'Vorstand'], ['team', 'Team'], ['abteilungen', 'Abteilungen'], ['jugend', 'Jugend'], ['anfahrt', 'Anfahrt'], ['kontakt', 'Kontakt']].map(([id, l]) => `<li class="subnav__item"><a class="subnav__link" href="#${id}">${l}</a></li>`).join('')}
    </ul>
  </div>
</nav>
<section class="section" id="vorstand">
  <div class="container">
    ${sectionHead({ eyebrow: 'Ehrenamt', title: 'Vorstand' })}
    <div class="grid grid--3">${personenIn('vorstand').map(personCard).join('')}</div>
  </div>
</section>
<section class="section section--tint" id="team">
  <div class="container">
    ${sectionHead({ eyebrow: 'Für Sie da', title: 'Clubmanagement, Sekretariat & Team' })}
    <div class="grid grid--3">${personenIn('betreiber', 'clubmanagement', 'sekretariat', 'service', 'greenkeeping').map(personCard).join('')}</div>
    ${club.texte.betreiberHinweis ? `<p class="small spacer-top">${esc(club.texte.betreiberHinweis)}</p>` : ''}
  </div>
</section>
<section class="section" id="abteilungen">
  <div class="container">
    ${sectionHead({ eyebrow: 'Abteilungen', title: 'Damen-, Herren- und Seniorengolf', lead: 'Ansprechpartner für die Spielgruppen des Clubs.' })}
    <div class="grid grid--3">${personenIn('captains').map(personCard).join('')}</div>
  </div>
</section>
<section class="section section--tint" id="jugend">
  <div class="container split">
    <div class="prose">
      <h2>Jugend</h2>
      <p>Ansprechpartnerin für die Jugend ist unser Vorstand Jugend. Angebote für Kinder und Jugendliche folgen.</p>
      <p><a class="btn btn--outline" href="${r}golfschule/">Golfschule</a></p>
    </div>
    ${personCard(personen.find((p) => p.funktion.includes('Jugend')))}
  </div>
</section>
<section class="section" id="anfahrt">
  <div class="container split">
    <div class="prose">
      <h2>Anfahrt</h2>
      <address>${club.adresse.map(esc).join('<br>')}</address>
      <p>Die Wegbeschreibung mit Auto sowie Bus und Bahn folgt.</p>
    </div>
    <div class="map-placeholder" role="img" aria-label="Platzhalter für die Karte">
      ${icon('pin')}
      <p>Karte als statisches Bild oder erst nach Einwilligung laden – kein Kartendienst ohne Consent.</p>
    </div>
  </div>
</section>
<section class="section section--tint" id="kontakt">
  <div class="container split">
    <div>
      <h2 class="h3">Kontakt</h2>
      <p><a class="contact-line" href="${telHref(club.telefon)}">${icon('phone')} ${esc(club.telefon)}</a><br><a class="contact-line" href="mailto:${club.email}">${icon('mail')} ${esc(club.email)}</a></p>
      <h3 class="h4">Sekretariat</h3>
      ${openingHours('sekretariat')}
    </div>
    ${prototypeForm({ felder: [['name', 'Name'], ['email', 'E-Mail', 'email'], ['betreff', 'Betreff', 'text', true], ['nachricht', 'Nachricht', 'textarea', true]], button: 'Nachricht senden' })}
  </div>
</section>`,
});

// ---------- Mitgliederbereich ----------
page({
  path: 'mitglieder/index.html',
  title: 'Mitgliederbereich',
  body: (r) => `
${pageHero({ r, crumbs: [['Mitgliederbereich']], eyebrow: 'Intern', title: 'Mitgliederbereich', lead: 'Melden Sie sich mit Ihren Zugangsdaten an, um die internen Inhalte zu sehen.' })}
<section class="section">
  <div class="container split">
    <div class="login-box">
      <h2 class="login-box__title">${icon('lock')} Anmelden</h2>
      ${prototypeForm({ felder: [['user', 'Benutzername oder E-Mail', 'text', true], ['pass', 'Passwort', 'password', true]], button: 'Anmelden', hinweis: '<a href="#">Passwort vergessen?</a> · Zugang erhalten Sie im Sekretariat.' })}
    </div>
    <div class="prose">
      <h2>Das finden Sie nach der Anmeldung</h2>
      <ul>
        <li>Protokolle und Einladungen zur Mitgliederversammlung</li>
        <li>Beitragsordnung, Satzung und Platzregeln</li>
        <li>Interne Nachrichten des Vorstands</li>
        <li>Downloads für Mannschaften und Spielführer</li>
      </ul>
      <p class="small">Inhalte des Mitgliederbereichs sind noch mit dem Club abzustimmen.</p>
    </div>
  </div>
</section>`,
});

// ---------- Rechtliches ----------
for (const [slug, titel] of [['impressum', 'Impressum'], ['datenschutz', 'Datenschutz']]) {
  page({
    path: `${slug}/index.html`,
    title: titel,
    body: (r) => `
${pageHero({ r, crumbs: [[titel]], title: titel })}
<section class="section"><div class="container prose prose--narrow"><p>Platzhalter. Der Text wird vom Club bzw. dessen Datenschutzbeauftragten geliefert.</p></div></section>`,
  });
}

// ---------- Farben (wie ACSS, hell/dunkel) ----------
mkdirSync(join(OUT, 'assets/css'), { recursive: true });
writeFileSync(join(OUT, 'assets/css/farben.css'), farbenCss());

// ---------- Daten für das Frontend-JS ----------
mkdirSync(join(OUT, 'assets/js'), { recursive: true });
writeFileSync(
  join(OUT, 'assets/js/data.js'),
  `// Generiert von src/build.mjs – nicht von Hand bearbeiten.
// In WordPress kommen diese Daten aus den Beitragstypen „Sperrung“ und der Einstellungsseite „Platzstatus“ bzw. „Abschläge“.
window.GC_DATA = ${JSON.stringify({ sperrungen, platzstatus, oeffnungszeiten, einrichtungen: EINRICHTUNGEN, abschlaege }, null, 2)};
`,
);

console.log(`${seiten.length} Seiten erzeugt.`);
