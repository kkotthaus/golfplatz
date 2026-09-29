// Wiederverwendbare Bausteine. Jeder Baustein entspricht einer späteren Etch-Komponente.
// Klassennamen nach BEM (block__element--modifier).

import { createRequire } from 'node:module';
import { club, restaurant, abschlaege, einheitText, oeffnungszeiten } from './data.mjs';

// Gemeinsame Öffnungszeiten-Logik (auch im Browser genutzt)
const Z = createRequire(import.meta.url)('../assets/js/zeiten.js');

export const esc = (s = '') =>
  String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

export const euro = (n) =>
  n.toLocaleString('de-DE', { minimumFractionDigits: Number.isInteger(n) ? 0 : 2, maximumFractionDigits: 2 }) + ' €';

export const zahl = (n, stellen = 0) =>
  n.toLocaleString('de-DE', { minimumFractionDigits: stellen, maximumFractionDigits: stellen });

const monate = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
const wochentage = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];

export const datumLang = (iso) => {
  const d = new Date(iso + 'T12:00:00');
  return `${d.getDate()}. ${monate[d.getMonth()]} ${d.getFullYear()}`;
};
export const datumKurz = (iso) => {
  const d = new Date(iso + 'T12:00:00');
  return `${wochentage[d.getDay()]}, ${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}.${d.getFullYear()}`;
};

export const telHref = (t) => 'tel:' + t.replace(/[^\d+]/g, '');

// Handicap-Index: negative Werte sind Plus-Handicaps und werden als „+2,3“ geschrieben.
export const hiText = (hi) => (hi < 0 ? '+' + zahl(-hi, 1) : zahl(hi, 1));

// WHS: Spielvorgabe = HI × Slope / 113 + (CR − Par), gerundet
export const spielvorgabe = (hi, { cr, slope, par }) => Math.round(hi * (slope / 113) + (cr - par));

export function spielvorgabenTabelle(werte) {
  const zeilen = [];
  for (let i = -50; i <= 540; i++) {
    const hi = i / 10;
    const sv = spielvorgabe(hi, werte);
    const letzte = zeilen[zeilen.length - 1];
    if (letzte && letzte.sv === sv) letzte.bis = hi;
    else zeilen.push({ von: hi, bis: hi, sv });
  }
  return zeilen;
}

export const svText = (sv) => (sv < 0 ? '+' + -sv : String(sv));

export const teeBadge = (id, text) =>
  `<span class="tee-badge tee-badge--${id}"><span class="tee-badge__dot" aria-hidden="true"></span>${esc(text)}</span>`;

export const icon = (name) => {
  const pfade = {
    phone: '<path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/>',
    mail: '<path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm8 7.2L4.5 7v10.5h15V7z"/>',
    clock: '<path d="M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zm0 1.8a7.2 7.2 0 1 0 0 14.4 7.2 7.2 0 0 0 0-14.4zm.9 2.7v4.1l3.1 1.8-.9 1.6-4-2.3V7.5z"/>',
    flag: '<path d="M6 2h1.8v1.2L18 7l-10.2 4v11H6z"/>',
    lock: '<path d="M7 10V7a5 5 0 0 1 10 0v3h1a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-9a1 1 0 0 1 1-1zm1.8 0h6.4V7a3.2 3.2 0 0 0-6.4 0z"/>',
    pin: '<path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7zm0 4.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/>',
    arrow: '<path d="M13.2 5.3 20 12l-6.8 6.7-1.3-1.3 4.6-4.5H4v-1.8h12.5L11.9 6.6z"/>',
    user: '<path d="M12 12a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9zm0 1.8c4.4 0 8 2.3 8 5.2V21H4v-2c0-2.9 3.6-5.2 8-5.2z"/>',
  };
  return `<svg class="icon icon--${name}" viewBox="0 0 24 24" aria-hidden="true" focusable="false">${pfade[name]}</svg>`;
};

export const logo = (r) => `
<a class="site-logo" href="${r}">
  <svg class="site-logo__mark" viewBox="0 0 48 48" aria-hidden="true">
    <circle cx="24" cy="24" r="22.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
    <circle cx="24" cy="24" r="19" fill="none" stroke="currentColor" stroke-width=".6"/>
    <path d="M21 34V12l11 5-9 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
    <path d="M12 35c5-3 19-3 24 0" fill="none" stroke="currentColor" stroke-width="1.4"/>
  </svg>
  <span class="site-logo__text">
    <span class="site-logo__name">${esc(club.logoName)}</span>
    <span class="site-logo__since">${esc(club.ort)}</span>
  </span>
</a>`;

export const pageHero = ({ eyebrow = '', title, lead = '', crumbs = [], r }) => `
<section class="page-hero">
  <div class="page-hero__inner container">
    ${crumbs.length ? breadcrumb(crumbs, r) : ''}
    ${eyebrow ? `<p class="page-hero__eyebrow">${esc(eyebrow)}</p>` : ''}
    <h1 class="page-hero__title">${title}</h1>
    ${lead ? `<p class="page-hero__lead">${lead}</p>` : ''}
  </div>
</section>`;

export const breadcrumb = (crumbs, r) => `
<nav class="breadcrumb" aria-label="Brotkrumen">
  <ol class="breadcrumb__list">
    <li class="breadcrumb__item"><a class="breadcrumb__link" href="${r}">Start</a></li>
    ${crumbs
      .map(([label, href], i) =>
        i === crumbs.length - 1
          ? `<li class="breadcrumb__item" aria-current="page">${esc(label)}</li>`
          : `<li class="breadcrumb__item"><a class="breadcrumb__link" href="${r}${href}">${esc(label)}</a></li>`,
      )
      .join('')}
  </ol>
</nav>`;

export const sectionHead = ({ eyebrow = '', title, lead = '', align = '' }) => `
<header class="section-head${align ? ' section-head--' + align : ''}">
  ${eyebrow ? `<p class="section-head__eyebrow">${esc(eyebrow)}</p>` : ''}
  <h2 class="section-head__title">${title}</h2>
  ${lead ? `<p class="section-head__lead">${lead}</p>` : ''}
</header>`;

export const hoursList = (zeilen, mod = '') => `
<dl class="hours${mod ? ' hours--' + mod : ''}">
  ${zeilen.map((z) => `<div class="hours__row"><dt class="hours__days">${esc(z.tage)}</dt><dd class="hours__time">${esc(z.zeit)}</dd></div>`).join('')}
</dl>`;

// Gäste: Es gibt keine festen Startzeiten. Anmeldung am Wochenende empfohlen (Clubdaten › Gäste & Systeme).
export const guestInfo = ({ id = '', mod = '' } = {}) => `
<aside class="guest-info${mod ? ' guest-info--' + mod : ''}"${id ? ` id="${id}"` : ''} aria-labelledby="guest-info-title${id}">
  <div class="guest-info__intro">
    <p class="guest-info__eyebrow">Als Gast spielen</p>
    <h2 class="guest-info__title" id="guest-info-title${id}">Einfach spielen – ohne feste Startzeiten</h2>
    <p class="guest-info__text">${esc(club.anmeldung.hinweis)}</p>
    ${club.anmeldung.ruhetag ? `<p class="guest-info__note">${esc(club.anmeldung.ruhetag)}</p>` : ''}
  </div>
  <div class="guest-info__contact">
    <a class="guest-info__channel" href="${telHref(club.anmeldung.telefon)}">${icon('phone')}<span><span class="guest-info__label">Anmeldung</span><span class="guest-info__value">${esc(club.anmeldung.telefon)}</span></span></a>
    <a class="guest-info__channel" href="mailto:${club.email}">${icon('mail')}<span><span class="guest-info__label">E-Mail</span><span class="guest-info__value">${esc(club.email)}</span></span></a>
  </div>
  <div class="guest-info__hours">
    <p class="guest-info__label">${icon('clock')} Sekretariat</p>
    ${openingHours('sekretariat', { mod: 'compact' })}
  </div>
</aside>`;

// Platzstatus-Block: wird per JS aus den Sperrungen befüllt (assets/js/main.js).
export const statusBoard = (r = '') => `
<section class="status-board" id="platzstatus" aria-labelledby="status-board-title" data-status-board>
  <div class="status-board__head">
    <h2 class="status-board__title" id="status-board-title">Platzstatus</h2>
    <p class="status-board__updated" data-status-updated></p>
  </div>
  <div class="status-board__alert" data-status-alert hidden></div>
  <ul class="status-board__conditions" data-status-conditions aria-label="Spielbedingungen"></ul>
  <div class="status-board__days">
    <div class="status-board__day">
      <h3 class="status-board__day-title">Heute <span class="status-board__date" data-status-date="0"></span></h3>
      <ul class="status-board__list" data-status-list="0"><li class="status-board__empty">Wird geladen …</li></ul>
    </div>
    <div class="status-board__day">
      <h3 class="status-board__day-title">Morgen <span class="status-board__date" data-status-date="1"></span></h3>
      <ul class="status-board__list" data-status-list="1"><li class="status-board__empty">Wird geladen …</li></ul>
    </div>
  </div>
  <div class="status-board__facilities">
    <h3 class="status-board__day-title">Übungsanlagen & Proshop</h3>
    <ul class="status-board__facility-list" data-status-facilities></ul>
    <p class="status-board__more"><a class="link-arrow" href="${r}greenfee/#oeffnungszeiten">Alle Öffnungszeiten ${icon('arrow')}</a></p>
  </div>
</section>`;

/**
 * Öffnungszeiten eines Bereichs: Standardzeiten, Zustand jetzt, kommende Ausnahmen, Hinweis.
 * Statisch gerendert; main.js ergänzt „jetzt geöffnet/geschlossen“, Sperren und aktualisiert die Ausnahmen.
 * In WordPress: Shortcode [golfplatz_oeffnungszeiten bereich="…"].
 */
export const openingHours = (key, { mod = '', titel = false } = {}) => {
  const b = oeffnungszeiten[key];
  const heute = new Date();
  const ausnahmen = Z.kommendeAusnahmen(b, heute);
  return `<div class="opening-hours${mod ? ' opening-hours--' + mod : ''}" data-zeiten="${key}">
  ${titel ? `<h3 class="opening-hours__title">${esc(b.name)}</h3>` : ''}
  <p class="opening-hours__status" data-zeiten-status hidden></p>
  ${b.standard.length ? hoursList(Z.zeilen(b.standard), mod === 'footer' ? 'footer' : mod === 'light' ? 'light' : 'compact') : ''}
  <ul class="opening-hours__exceptions" data-zeiten-ausnahmen${ausnahmen.length ? '' : ' hidden'}>${ausnahmen
    .map((a) => `<li class="opening-hours__exception"><strong>${esc(a.titel)}</strong> ${Z.zeitraumText(a)}: ${esc(Z.ausnahmeText(a))}</li>`)
    .join('')}</ul>
  ${b.hinweis ? `<p class="opening-hours__note">${esc(b.hinweis)}</p>` : ''}
</div>`;
};

// Öffnungszeiten der Anlage: alle Bereiche als Karten. Gesperrte und geschlossene Bereiche markiert main.js.
export const facilityHours = () => `
<div class="facility-hours" id="oeffnungszeiten">
  ${Object.keys(oeffnungszeiten)
    .map((key) => `<article class="facility-card" data-facility="${key}">${openingHours(key, { titel: true })}</article>`)
    .join('')}
</div>`;

export const personCard = (p) => {
  const initialen = p.name.replace(/^Dr\. /, '').split(' ').map((t) => t[0]).slice(0, 2).join('');
  return `
<article class="person-card">
  <div class="person-card__avatar" aria-hidden="true">${esc(initialen)}</div>
  <div class="person-card__body">
    <h3 class="person-card__name">${esc(p.name)}</h3>
    <p class="person-card__role">${esc(p.funktion)}</p>
    ${p.text ? `<p class="person-card__text">${esc(p.text)}</p>` : ''}
    ${p.telefon ? `<a class="person-card__link" href="${telHref(p.telefon)}">${esc(p.telefon)}</a>` : ''}
    ${p.email ? `<a class="person-card__link" href="mailto:${p.email}">${esc(p.email)}</a>` : ''}
  </div>
</article>`;
};

export const newsCard = (n, r, mod = '') => `
<article class="news-card${mod ? ' news-card--' + mod : ''}">
  <div class="news-card__media" aria-hidden="true"><span class="news-card__category">${esc(n.kategorie)}</span></div>
  <div class="news-card__body">
    <p class="news-card__meta"><time datetime="${n.datum}">${datumLang(n.datum)}</time>${n.mitglieder ? ` · <span class="news-card__members">${icon('lock')} Mitglieder</span>` : ''}</p>
    <h3 class="news-card__title"><a class="news-card__link" href="${r}news/${n.slug}/">${esc(n.titel)}</a></h3>
    <p class="news-card__teaser">${esc(n.teaser)}</p>
  </div>
</article>`;

/** Preis als Text; null = „auf Anfrage“. */
export const preisText = (betrag) => (betrag === null || betrag === undefined ? 'auf Anfrage' : euro(betrag));

// Einheit hinter dem Preis – entfällt, wenn sie schon in der Leistung steht („18 Loch mit DGV-Ausweis“). Wie golfplatz-preise.php.
const einheitAnzeige = (p) => {
  const e = p.betrag === null || p.betrag === undefined ? '' : einheitText[p.einheit] || p.einheit || '';
  return e && p.titel.toLowerCase().includes(e.toLowerCase()) ? '' : e;
};

// Preis-Matrix wie in WordPress (snippets/golfplatz-preise.php): Tage aus „Gültig an“, Tarif je Zeile,
// Varianten („… mit DGV-Ausweis „R““) als Unterzeile. Spalten Mo–Sa und So/Feiertag.
const SPALTEN = [['Mo', 'Montag'], ['Di', 'Dienstag'], ['Mi', 'Mittwoch'], ['Do', 'Donnerstag'], ['Fr', 'Freitag'], ['Sa', 'Samstag'], ['So/Feiertag', 'Sonntag und Feiertag']];
const TAGNR = { mo: 0, di: 1, mi: 2, do: 3, fr: 4, sa: 5, so: 6 };
export const preisTage = (text = '') => {
  const t = text.toLowerCase().trim();
  if (!t) return [];
  if (t.includes('täglich')) return [0, 1, 2, 3, 4, 5, 6];
  const tage = new Set();
  for (const teil of t.split(/\s*(?:,|\bund\b|\/)\s*/)) {
    if (teil.startsWith('feiertag')) { tage.add(6); continue; }
    if (teil.startsWith('werktag')) { [0, 1, 2, 3, 4, 5].forEach((d) => tage.add(d)); continue; }
    const m = /^([a-zä]{2})[a-zä]*\s*[–-]\s*([a-zä]{2})/.exec(teil);
    if (m && m[1] in TAGNR && m[2] in TAGNR) { for (let d = TAGNR[m[1]]; d <= TAGNR[m[2]]; d++) tage.add(d); continue; }
    if (teil.slice(0, 2) in TAGNR) tage.add(TAGNR[teil.slice(0, 2)]);
  }
  return [...tage];
};
const zahlText = (b) => (b === null || b === undefined ? 'auf Anfrage' : euro(b));
const matrixZellen = (zellen) =>
  zellen.map((w) => `<div role="cell" class="price-matrix__cell">${w ? esc(w) : '<span aria-hidden="true">–</span><span class="visually-hidden">nicht angeboten</span>'}</div>`).join('');
export const priceMatrix = (zeilen) => {
  const tarife = new Map();
  for (const p of zeilen) {
    const einheit = einheitAnzeige(p);
    const key = p.titel + '|' + einheit;
    if (!tarife.has(key)) tarife.set(key, { titel: p.titel, zusatz: p.zusatz || '', einheit, zellen: Array(7).fill('') });
    const tage = preisTage(p.tage);
    for (const d of tage.length ? tage : [0, 1, 2, 3, 4, 5, 6]) tarife.get(key).zellen[d] = zahlText(p.betrag);
  }
  const bloecke = [];
  const ober = new Map();
  for (const [key, t] of tarife) {
    let passend = null;
    for (const [okey] of ober) {
      const ot = tarife.get(okey).titel;
      if (ot !== t.titel && t.titel.startsWith(ot + ' ') && (!passend || ot.length > tarife.get(passend).titel.length)) passend = okey;
    }
    if (passend) { bloecke[ober.get(passend)].unter.push({ ...t, titel: t.titel.slice(tarife.get(passend).titel.length).trim() }); continue; }
    ober.set(key, bloecke.length);
    bloecke.push({ ...t, unter: [] });
  }
  const label = (t, mitEinheit) => `<div role="rowheader" class="price-matrix__label">${esc(t.titel)}${mitEinheit && t.einheit ? `<span class="price-matrix__note">${esc(t.einheit)}</span>` : ''}${t.zusatz ? `<span class="price-matrix__note">${esc(t.zusatz)}</span>` : ''}</div>`;
  return `
<div class="table-wrap">
<div role="table" class="price-matrix">
  <div role="row" class="price-matrix__head"><div role="columnheader" class="price-matrix__corner">Tarif</div>${SPALTEN.map(([k, l]) => `<div role="columnheader" class="price-matrix__day"><abbr title="${l}">${k}</abbr></div>`).join('')}</div>
  ${bloecke.map((b) => `<div role="row" class="price-matrix__row">${label(b, true)}${matrixZellen(b.zellen)}</div>${b.unter.map((u) => `<div role="row" class="price-matrix__row price-matrix__row--sub">${label(u, false)}${matrixZellen(u.zellen)}</div>`).join('')}`).join('')}
</div>
</div>`;
};

// Preisliste ohne Wochentage (Leihgeräte): Leistung mit Einheit, Preis. Grid mit Tabellen-Rollen wie in Etch.
export const priceTable = (zeilen) => `
<div role="table" class="price-list">
  <div role="row" class="price-list__head"><div role="columnheader" class="price-list__title">Leistung</div><div role="columnheader" class="price-list__amount">Preis</div></div>
  ${zeilen
    .map(
      (p) => `<div role="row" class="price-list__row"><div role="rowheader" class="price-list__title">${esc([p.titel, einheitAnzeige(p)].filter(Boolean).join(' '))}${p.zusatz ? `<span class="price-list__note">${esc(p.zusatz)}</span>` : ''}</div><div role="cell" class="price-list__amount">${preisText(p.betrag)}</div></div>`,
    )
    .join('')}
</div>`;

export const prototypeForm = ({ felder, button, hinweis = '' }) => `
<form class="form" data-prototype-form novalidate>
  <div class="form__grid">
    ${felder
      .map(([name, label, type = 'text', breit = false]) => {
        const id = 'f-' + name + '-' + Math.random().toString(36).slice(2, 7);
        const feld =
          type === 'textarea'
            ? `<textarea class="form__input form__input--textarea" id="${id}" name="${name}" rows="5"></textarea>`
            : type.startsWith('select:')
              ? `<select class="form__input form__input--select" id="${id}" name="${name}">${type
                  .slice(7)
                  .split('|')
                  .map((o) => `<option>${esc(o)}</option>`)
                  .join('')}</select>`
              : `<input class="form__input" id="${id}" name="${name}" type="${type}">`;
        return `<div class="form__field${breit ? ' form__field--wide' : ''}"><label class="form__label" for="${id}">${esc(label)}</label>${feld}</div>`;
      })
      .join('')}
  </div>
  ${hinweis ? `<p class="form__hint">${hinweis}</p>` : ''}
  <button class="btn btn--primary form__submit" type="submit">${esc(button)}</button>
  <p class="form__message" data-form-message hidden role="status"></p>
</form>`;

// Bahngrafik als SVG aus den Bahndaten. Später ersetzt durch das hochgeladene Luftbild.
export function holeSvg(b) {
  const par = b.parHerren;
  const dx = { links: -38, rechts: 38, gerade: 0 }[b.form];
  const gx = 80 + dx;
  const gy = par === 3 ? 90 : 48;
  const teeY = 272;
  const fairway =
    par === 3
      ? ''
      : `<path class="hole-map__fairway" d="M80 ${teeY - 30} C80 ${150 + (par === 5 ? -10 : 20)}, 80 ${120}, ${gx} ${gy + 24}" />`;
  const bunker = [
    `<ellipse class="hole-map__bunker" cx="${gx - 22}" cy="${gy + 14}" rx="9" ry="6" />`,
    `<ellipse class="hole-map__bunker" cx="${gx + 20}" cy="${gy - 12}" rx="7" ry="5" />`,
  ];
  if (par > 3) bunker.push(`<ellipse class="hole-map__bunker" cx="${80 - dx * 0.5 + (dx ? 0 : 22)}" cy="140" rx="10" ry="7" />`);
  let wasser = '';
  if (b.wasser) {
    wasser =
      par === 3
        ? `<ellipse class="hole-map__water" cx="${gx}" cy="${gy + 44}" rx="36" ry="14" />`
        : `<ellipse class="hole-map__water" cx="${dx >= 0 ? 138 : 22}" cy="${par === 5 ? 90 : 170}" rx="16" ry="38" />`;
  }
  const baeume = [
    [18, 230], [142, 250], [14, 60], [146, 40], [24, 180], [136, 120],
  ]
    .filter((_, i) => (b.nr + i) % 3 !== 0)
    .map(([x, y]) => `<circle class="hole-map__tree" cx="${x}" cy="${y}" r="9" />`)
    .join('');
  const tees = abschlaege
    .map((a, i) => `<rect class="hole-map__tee hole-map__tee--${a.id}" x="${66 + i * 8}" y="${teeY - 2 + i * 0}" width="5" height="5" rx="1" />`)
    .join('');
  return `
<svg class="hole-map" viewBox="0 0 160 300" role="img" aria-label="Bahngrafik Bahn ${b.nr}, Par ${par}">
  <rect class="hole-map__rough" x="0" y="0" width="160" height="300" rx="10" />
  ${baeume}
  ${wasser}
  ${fairway}
  <rect class="hole-map__teebox" x="62" y="${teeY - 8}" width="36" height="18" rx="4" />
  ${tees}
  <circle class="hole-map__green" cx="${gx}" cy="${gy}" r="17" />
  ${bunker.join('')}
  <line class="hole-map__pole" x1="${gx}" y1="${gy}" x2="${gx}" y2="${gy - 22}" />
  <path class="hole-map__flag" d="M${gx} ${gy - 22} l11 4 -11 4z" />
</svg>`;
}
