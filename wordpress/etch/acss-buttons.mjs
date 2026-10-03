// Button-Einstellungen für Automatic.css (Automatic.css › Buttons & Links).
// Gelten für alle ACSS-Buttons (btn--primary, btn--secondary, btn--outline, btn--s …).
// build.mjs schreibt dist/daten/acss-buttons.json; übernehmen per MCP golfplatz/acss-colors mit { aus_datei: true }.
//
// Verwendete Klassen:
//   btn--primary                      Hauptaktion
//   btn--secondary                    „Als Gast spielen“
//   btn--primary btn--outline         Nebenaktion auf hellem Grund
//   btn--primary-light btn--outline   Nebenaktion auf grünem Seitenkopf und dunklem Band
//   btn--primary-light                Nebenaktion im Startseiten-Hero (heller Grund über der Illustration)
//   btn--s                            kleine Buttons (ohne: Standardgröße)

export const acssButtons = {
  'btn-font-weight': '600',
  'btn-letter-spacing': '.03em',
  'btn-line-height': '1.2',
  'btn-padding-block': '.75em',
  'btn-padding-inline': '1.4em',
};

// Der Prototyp hat kein ACSS. Diese Regeln bilden die verwendeten ACSS-Buttons nach
// (gleiche Variablen und Werte wie automatic.css), damit Prototyp und WordPress gleich aussehen.
export function buttonsCss() {
  const vars = Object.entries(acssButtons).map(([k, v]) => `  --${k}: ${v};`).join('\n');
  const farbe = (klasse, bg, hover, text, textHover) => `${klasse} {
  --btn-background: ${bg};
  --btn-background-hover: ${hover};
  --btn-text-color: ${text};
  --btn-text-color-hover: ${textHover};
  --btn-border-color: var(--btn-background);
  --btn-border-color-hover: var(--btn-background-hover);
}`;
  const outline = (klasse, farbeName, hover, textHover) => `${klasse} {
  --btn-background: transparent;
  --btn-background-hover: ${hover};
  --btn-text-color: var(--${farbeName});
  --btn-text-color-hover: ${textHover};
  --btn-border-width: 1.5px;
  --btn-border-color: var(--${farbeName});
  --btn-border-color-hover: var(--btn-background-hover);
}`;
  return `/* Generiert aus wordpress/etch/acss-buttons.mjs – nicht von Hand bearbeiten.
   Im Prototyp ersetzt diese Datei die Buttons von Automatic.css (gleiche Klassen, Variablen und Werte). */
:root {
  --btn-padding-block: .5em;
  --btn-padding-inline: 1.25em;
  --btn-min-width: 8.75rem;
  --btn-width: max-content;
  --btn-font-weight: 400;
  --btn-line-height: 1;
  --btn-letter-spacing: 0;
  --btn-border-width: 1.5px;
  --btn-border-style: solid;
  --btn-border-radius: var(--radius);
${vars}
}
[class*="btn--"] {
  display: inline-flex; justify-content: center; align-items: center; text-align: center;
  background: var(--btn-background); color: var(--btn-text-color);
  padding-block: var(--btn-padding-block); padding-inline: var(--btn-padding-inline);
  inline-size: var(--btn-width, auto); min-inline-size: var(--btn-min-width);
  line-height: var(--btn-line-height); font-family: inherit; font-size: var(--btn-font-size, var(--text-m));
  font-weight: var(--btn-font-weight); letter-spacing: var(--btn-letter-spacing); text-decoration: none;
  border-width: var(--btn-border-width); border-style: var(--btn-border-style); border-radius: var(--btn-border-radius); border-color: var(--btn-border-color);
  transition: var(--transition); cursor: pointer;
}
[class*="btn--"]:hover { background: var(--btn-background-hover); color: var(--btn-text-color-hover); border-color: var(--btn-border-color-hover); }
.btn--s { --btn-font-size: var(--text-s); }
${farbe('.btn--primary', 'var(--primary)', 'var(--primary-hover)', 'var(--white)', 'var(--white)')}
${farbe('.btn--secondary', 'var(--secondary)', 'var(--secondary-hover)', 'var(--white)', 'var(--white)')}
${farbe('.btn--primary-light', 'var(--primary-ultra-light)', 'var(--primary-light)', 'var(--primary)', 'var(--primary-ultra-dark)')}
${outline('.btn--primary.btn--outline', 'primary', 'var(--primary-hover)', 'var(--primary-ultra-light)')}
${outline('.btn--primary-light.btn--outline', 'primary-light', 'var(--primary-light)', 'var(--primary-dark)')}
`;
}
