// Club-Palette für Automatic.css v4.
// ACSS speichert jede Farbe und jede Abstufung als OKLCH (…-l-oklch, …-c-oklch, …-h-oklch).
// Wo das Design einen festen Wert hat, wird er exakt übernommen; die übrigen Abstufungen
// werden mit Farbton und Sättigung der Hauptfarbe auf die ACSS-Helligkeitsstufen gerechnet.
//
// Anwenden: node wordpress/etch/acss-farben.mjs > dist/daten/acss-farben.json,
// dann MCP-Funktion golfplatz/acss-colors mit { werte: <Inhalt> }.

const ABSTUFUNGEN = ['ultra-light', 'light', 'semi-light', 'semi-dark', 'dark', 'ultra-dark', 'hover'];
const STANDARD_L = { 'ultra-light': 0.95, light: 0.85, 'semi-light': 0.6, 'semi-dark': 0.4, dark: 0.25, 'ultra-dark': 0.12 };

// Farben des Designs (siehe prototype/assets/css/tokens.css)
// Hell (Standard): main und die Abstufungen. Im dunklen Farbschema tauscht ACSS die Abstufungen
// (ultra-light ↔ ultra-dark, light ↔ dark, semi-light ↔ semi-dark); main und hover nehmen die Werte aus „dunkel“.
// ultra-dark und base-dark werden im hellen Design nicht verwendet und sind deshalb auf das dunkle Schema abgestimmt
// (Seitenhintergrund, Flächen, Rahmen).
export const palette = {
  // CLUBFARBEN – hier für einen neuen Club anpassen. Blueprint: neutrales Golf-Grün (Primary) und dezentes Blau (Secondary).
  // Barrierefreiheit (WCAG 2.1 AA) nach jeder Änderung prüfen: node wordpress/etch/kontrast.mjs.
  // Primary main trägt weiße Schrift und wird für Flächen genutzt; grüne Schrift läuft über primary-dark.
  // Secondary ist auf Weiß gut lesbar, auf Grün aber nicht – dort nie als Schrift.
  primary: { main: '#2E6B4E', hover: '#245A41', dark: '#1D4A35', light: '#D6E8DE', 'ultra-light': '#EEF5F1', 'ultra-dark': '#10271C', dunkel: { main: '#6CC39A', hover: '#8BD1AF' } },
  secondary: { main: '#2C5A85', dark: '#1C3F60', light: '#DCE6F0', 'ultra-light': '#F8F9FA', 'ultra-dark': '#171A1C', dunkel: { main: '#8DB4DA', hover: '#A8C6E4' } },
  base: { main: '#232826', 'semi-dark': '#4a524f', 'semi-light': '#636d69', light: '#d3dbd8', 'ultra-light': '#eef2f0', dark: '#3a403e', 'ultra-dark': '#212524', dunkel: { main: '#e8eceb', hover: '#cfd6d3' } },
  success: { main: '#2f6b3f', light: '#9fd3aa', 'ultra-light': '#e3efe4', 'ultra-dark': '#1a2b1f', dunkel: { main: '#7fc08f', hover: '#98cfa5' } },
  warning: { main: '#8a5a0e', light: '#f0cf7e', 'ultra-light': '#fbefd8', 'ultra-dark': '#2e2310', dunkel: { main: '#e0b35a', hover: '#e8c47c' } },
  // Danger etwas oranger als das Club-Rot, damit Sperren nicht wie Markenfarbe wirken (Status steht zusätzlich immer als Text da).
  danger: { main: '#b42318', light: '#f1b0a8', 'ultra-light': '#fbe9e7', 'ultra-dark': '#2f1a18', dunkel: { main: '#f08c80', hover: '#f4a79e' } },
  info: { main: '#2e5873', 'ultra-light': '#e6eef4', 'ultra-dark': '#16232d', dunkel: { main: '#8fb6d3', hover: '#a9c7de' } },
};

// Farbschema: folgt dem Gerät (ACSS „light dark“); der Umschalter setzt scheme--light bzw. scheme--dark am <html> (etch-nodes: Hell/Dunkel).
// Diese Bereiche bleiben auch im dunklen Schema hell gerechnet (dunkelgrüne Flächen mit heller Schrift).
// Dazu Flächen mit fester Illustration (Hero-Landschaft, Bahngrafik, Platzhalterbilder).
export const immerHell = ['.top-bar', '.app-bar', '.site-footer', '.page-hero', '.section--dark', '.home-hero', '.hole-map', '.hole-video__player', '.news-card__media', '.team-photo', '.map-placeholder'];

// sRGB-Hex → OKLCH (Björn Ottosson)
export function hexToOklch(hex) {
  const n = parseInt(hex.slice(1), 16);
  const lin = (c) => ((c /= 255) <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
  const [r, g, b] = [lin((n >> 16) & 255), lin((n >> 8) & 255), lin(n & 255)];
  const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
  const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
  const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);
  const L = 0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s;
  const A = 1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s;
  const B = 0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s;
  const C = Math.sqrt(A * A + B * B);
  let H = (Math.atan2(B, A) * 180) / Math.PI;
  if (H < 0) H += 360;
  return { l: +L.toFixed(3), c: +C.toFixed(3), h: +H.toFixed(2) };
}

export function acssEinstellungen() {
  const werte = {
    'option-palette-unify-brand-lightness': 'off',
    'option-palette-unify-status-lightness': 'off',
    'auto-color-scheme': 'on',
    'website-color-scheme': 'light dark',
    'option-ref-color-tokens': 'on',
    'color-scheme-force-light-selectors': immerHell.join(', '),
    // Buttons: ACSS setzt die Schrift standardmäßig auf -ultra-light; auf einem mittleren Primary reicht das oft nicht. Weiß ist sicher.
    'btn-primary-text': 'var(--white)',
    'btn-primary-hover-text': 'var(--white)',
    'btn-secondary-text': 'var(--white)',
    'btn-secondary-hover-text': 'var(--white)',
    // Links: ACSS-Standard var(--primary) hat auf dem Seitenhintergrund nur 4,33:1
    'link-color': 'var(--primary-dark)',
    'link-color-hover': 'var(--primary-hover)',
  };
  for (const [name, farben] of Object.entries(palette)) {
    const main = hexToOklch(farben.main);
    werte[`color-${name}`] = farben.main;
    werte[`color-${name}-alt`] = farben.main;
    werte[`option-${name}-clr`] = 'on';
    const setze = (prefix, { l, c, h }) => {
      werte[`${prefix}-l-oklch`] = l;
      werte[`${prefix}-c-oklch`] = c;
      werte[`${prefix}-h-oklch`] = h;
    };
    setze(name, main);
    if (farben.dunkel) {
      for (const [teil, hex] of Object.entries(farben.dunkel)) {
        const { l, c } = hexToOklch(hex);
        const prefix = teil === 'main' ? name : `${name}-hover`;
        werte[`${prefix}-l-alt-oklch`] = l;
        werte[`${prefix}-c-alt-oklch`] = c;
      }
    }
    for (const stufe of ABSTUFUNGEN) {
      let wert;
      if (farben[stufe] && typeof farben[stufe] === 'string') wert = hexToOklch(farben[stufe]);
      else if (stufe === 'hover') wert = { l: +(main.l < 0.5 ? main.l + 0.08 : main.l - 0.08).toFixed(3), c: main.c, h: main.h };
      else {
        const l = STANDARD_L[stufe];
        // Sehr helle Stufen mit wenig Sättigung, damit Flächen ruhig bleiben
        wert = { l, c: l >= 0.85 ? Math.min(main.c, 0.03) : main.c, h: main.h };
      }
      setze(`${name}-${stufe}`, wert);
    }
  }
  return werte;
}

// Farbvariablen für den Prototyp (der kein ACSS hat), gerechnet wie ACSS mit auto-color-scheme:
// light-dark() je Variable, Abstufungen im dunklen Schema getauscht, -ref-Tokens ohne light-dark().
export function farbenCss() {
  const w = acssEinstellungen();
  const ok = (p) => `oklch(${w[`${p}-l-oklch`]} ${w[`${p}-c-oklch`]} ${w[`${p}-h-oklch`]})`;
  const alt = (p, h) => (w[`${p}-l-alt-oklch`] ? `oklch(${w[`${p}-l-alt-oklch`]} ${w[`${p}-c-alt-oklch`]} ${h})` : ok(p));
  const paare = [['ultra-light', 'ultra-dark'], ['light', 'dark'], ['semi-light', 'semi-dark']];
  const zeilen = ['  --white: light-dark(#fff, #000);', '  --black: light-dark(#000, #fff);'];
  for (const name of Object.keys(palette)) {
    const h = w[`${name}-h-oklch`];
    zeilen.push(`  --${name}: light-dark(${ok(name)}, ${alt(name, h)});`);
    zeilen.push(`  --${name}-hover: light-dark(${ok(`${name}-hover`)}, ${alt(`${name}-hover`, h)});`);
    for (const [hell, dunkel] of paare) {
      zeilen.push(`  --${name}-${hell}: light-dark(${ok(`${name}-${hell}`)}, ${ok(`${name}-${dunkel}`)});`);
      zeilen.push(`  --${name}-${dunkel}: light-dark(${ok(`${name}-${dunkel}`)}, ${ok(`${name}-${hell}`)});`);
    }
    for (const stufe of ['', ...ABSTUFUNGEN.filter((s) => s !== 'hover').map((s) => `-${s}`)]) {
      zeilen.push(`  --${name}${stufe}-ref: ${ok(name + stufe)};`);
    }
  }
  return `/* Generiert aus wordpress/etch/acss-farben.mjs – nicht von Hand bearbeiten.
   Im Prototyp ersetzt diese Datei das Farbsystem von Automatic.css (gleiche Variablen, gleiche Werte). */
:root {
  color-scheme: ${w['website-color-scheme']};
${zeilen.join('\n')}
}
${w['color-scheme-force-light-selectors']} {
  color-scheme: light;
}
.scheme--light {
  color-scheme: light;
}
.scheme--dark {
  color-scheme: dark;
}
`;
}

// Direkt aufgerufen: Einstellungen als JSON ausgeben
if (process.argv[1] && import.meta.url.endsWith(process.argv[1].replace(/\\/g, '/').split('/').pop())) {
  process.stdout.write(JSON.stringify(acssEinstellungen()));
}
