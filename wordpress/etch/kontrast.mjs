// Kontrastprüfung (WCAG 2.1) der ACSS-Farben in beiden Farbschemata.
// Rechnet die Werte so, wie ACSS sie ausgibt (acss-farben.mjs), inkl. Tausch der Abstufungen im dunklen Schema.
// Aufruf: node wordpress/etch/kontrast.mjs   → Exit-Code 1, wenn eine Kombination durchfällt.

import { acssEinstellungen } from './acss-farben.mjs';

const w = acssEinstellungen();
const TAUSCH = { 'ultra-light': 'ultra-dark', 'ultra-dark': 'ultra-light', light: 'dark', dark: 'light', 'semi-light': 'semi-dark', 'semi-dark': 'semi-light' };

const oklch = (p, alt = false) => {
  const s = alt && w[`${p}-l-alt-oklch`] !== undefined ? '-alt' : '';
  const h = w[`${p}-h-oklch`] ?? w[`${p.split('-')[0]}-h-oklch`];
  return { l: +w[`${p}-l${s}-oklch`], c: +w[`${p}-c${s}-oklch`], h: +h };
};

// Variable → OKLCH im jeweiligen Schema
function farbe(name, dunkel) {
  if (name === 'white') return dunkel ? { l: 0, c: 0, h: 0 } : { l: 1, c: 0, h: 0 };
  if (name === 'black') return dunkel ? { l: 1, c: 0, h: 0 } : { l: 0, c: 0, h: 0 };
  // Nebentext im dunklen Schema: color-mix aus main.css
  if (dunkel && (name === 'base-semi-dark' || name === 'base-semi-light')) {
    const anteil = name === 'base-semi-dark' ? 0.85 : 0.72;
    const a = farbe('base', true), b = farbe('secondary-ultra-light', true);
    return { l: a.l * anteil + b.l * (1 - anteil), c: a.c * anteil + b.c * (1 - anteil), h: a.h };
  }
  const [familie, ...rest] = name.split('-');
  const stufe = rest.join('-');
  if (!stufe) return oklch(familie, dunkel);
  if (stufe === 'hover') return oklch(`${familie}-hover`, dunkel);
  return oklch(`${familie}-${dunkel ? TAUSCH[stufe] : stufe}`);
}

function luminanz({ l, c, h }) {
  const a = c * Math.cos((h * Math.PI) / 180), b = c * Math.sin((h * Math.PI) / 180);
  const l_ = (l + 0.3963377774 * a + 0.2158037573 * b) ** 3;
  const m_ = (l - 0.1055613458 * a - 0.0638541728 * b) ** 3;
  const s_ = (l - 0.0894841775 * a - 1.291485548 * b) ** 3;
  const k = (x) => Math.min(1, Math.max(0, x));
  const r = k(4.0767416621 * l_ - 3.3077115913 * m_ + 0.2309699292 * s_);
  const g = k(-1.2684380046 * l_ + 2.6097574011 * m_ - 0.3413193965 * s_);
  const bl = k(-0.0041960863 * l_ - 0.7034186147 * m_ + 1.707614701 * s_);
  return 0.2126 * r + 0.7152 * g + 0.0722 * bl;
}
const kontrast = (x, y) => { const [a, b] = [luminanz(x), luminanz(y)].sort((p, q) => q - p); return (a + 0.05) / (b + 0.05); };

// [Vordergrund, Hintergrund, Mindestwert, Verwendung]
const SEITE = 'secondary-ultra-light', KARTE = 'white';
const paare = [
  ['base', SEITE, 4.5, 'Fließtext'], ['base', KARTE, 4.5, 'Text auf Karten'],
  ['base-semi-dark', SEITE, 4.5, 'Nebentext'], ['base-semi-dark', KARTE, 4.5, 'Nebentext auf Karten'], ['base-semi-dark', 'base-ultra-light', 4.5, 'Zählkarte Ergebnis-Label'], ['base-semi-dark', 'primary-light', 4.5, 'Zählkarte Netto-Label'], ['primary-dark', 'base-ultra-light', 4.5, 'Zählkarte Ergebnis'],
  ['base-semi-light', SEITE, 4.5, 'gedämpfter Text'], ['base-semi-light', KARTE, 4.5, 'gedämpfter Text auf Karten'], ['base-semi-light', 'base-ultra-light', 4.5, 'gedämpfter Text auf Flächen'],
  ['base-semi-light', KARTE, 3, 'Rahmen Eingabefeld'],
  ['primary-dark', SEITE, 4.5, 'grüne Schrift, Links'], ['primary-hover', SEITE, 4.5, 'Link-Hover'], ['primary-hover', KARTE, 4.5, 'Link-Hover auf Karten'], ['primary-dark', KARTE, 4.5, 'grüne Schrift auf Karten'], ['primary-dark', 'primary-light', 4.5, 'Badge'], ['primary-dark', 'primary-ultra-light', 4.5, 'Menü-Hover'],
  ['white', 'primary', 4.5, 'btn--primary, Tabellenkopf, Seiten-Hero'], ['white', 'primary-hover', 4.5, 'btn--primary Hover'],
  ['primary', SEITE, 4.5, 'btn--primary btn--outline Text'], ['primary', KARTE, 4.5, 'btn--outline Text auf Karten'], ['primary-ultra-light', 'primary-hover', 4.5, 'btn--outline Hover'],
  ['primary-light', 'primary', 4.5, 'btn--primary-light btn--outline (grüner Seitenkopf, Band)'], ['primary-dark', 'primary-light', 4.5, 'btn--primary-light btn--outline Hover'], ['primary', 'primary-ultra-light', 4.5, 'btn--primary-light (Startseiten-Hero)'], ['primary-ultra-dark', 'primary-light', 4.5, 'btn--primary-light Hover'],
  ['white', 'secondary', 4.5, 'btn--secondary „Als Gast spielen“'], ['white', 'secondary-hover', 4.5, 'btn--secondary Hover'],
  ['secondary', SEITE, 4.5, 'rote Schrift'], ['secondary-dark', SEITE, 4.5, 'Eyebrows'], ['secondary-dark', 'secondary-light', 4.5, 'Hinweis-Kästen'],
  ['primary', SEITE, 3, 'Button-Fläche / Rahmen (Grafik)'], ['secondary', KARTE, 3, 'Fokusrahmen'],
  ['success', KARTE, 4.5, 'Status geöffnet'], ['success', 'success-ultra-light', 4.5, 'Status-Chip'],
  ['warning', KARTE, 4.5, 'Status eingeschränkt'], ['warning', 'warning-ultra-light', 4.5, 'Status-Chip'],
  ['danger', KARTE, 4.5, 'Status gesperrt'], ['danger', 'danger-ultra-light', 4.5, 'Status-Chip'],
  ['white', 'danger', 4.5, 'Schild „Abgesagt“, Datum abgesagter Turniere'], ['base', 'danger-ultra-light', 4.5, 'Text auf abgesagter Turnierkarte'], ['base-semi-dark', 'danger-ultra-light', 4.5, 'Nebentext auf abgesagter Turnierkarte'], ['primary-dark', 'danger-ultra-light', 4.5, 'Links auf abgesagter Turnierkarte'], ['base', 'warning-ultra-light', 4.5, 'Text im Hinweis „Turnier verschoben“'],
  ['info', 'info-ultra-light', 4.5, 'Wintergrüns'],
];
// Bereiche, die immer hell gerechnet werden (Top-Bar, Footer)
const immerHell = [
  ['primary-light', 'primary-ultra-dark', 4.5, 'Top-Bar/Footer-Text'], ['secondary-light', 'primary-ultra-dark', 4.5, 'Footer-Überschriften'],
];

let fehler = 0;
for (const [schema, dunkel] of [['Hell', false], ['Dunkel', true]]) {
  console.log(`\n${schema}`);
  const liste = [...paare.map((p) => [...p, dunkel]), ...immerHell.map((p) => [...p, false])];
  for (const [fg, bg, min, wozu, d] of liste) {
    const k = kontrast(farbe(fg, d), farbe(bg, d));
    const ok = k >= min;
    if (!ok) fehler++;
    console.log(`${ok ? '  ok ' : 'FEHLT'} ${k.toFixed(2).padStart(5)} (min ${min})  ${fg} auf ${bg} – ${wozu}`);
  }
}
console.log(fehler ? `\n${fehler} Kombination(en) unter dem Mindestwert.` : '\nAlle Kombinationen erfüllen WCAG 2.1 AA.');
process.exitCode = fehler ? 1 : 0;

