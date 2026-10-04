// Schrift-Einstellungen für Automatic.css (Automatic.css › Typografie), Standard aus etch-nodes/docs/konventionen.md#schriften.
// Beide Schriften (Google Fonts, SIL Open Font License, Lizenzen in wordpress/lizenzen/) hostet der Etch Font Manager selbst
// (wp-content/fonts/, Subset latin, variabel, jeweils normal und kursiv): Überschriften Fraunces (vorgeladen), Fließtext Lora.
// Der Font Manager setzt keine Rolle – welche Schrift wofür gilt, steht nur hier.
// Build schreibt dist/daten/acss-schrift.json; übernehmen per MCP golfplatz/acss-colors mit { aus_datei: true }.

const FALLBACK = 'Georgia, "Times New Roman", serif';

export const acssSchrift = {
  'heading-font-family': `"Fraunces", ${FALLBACK}`,
  'text-font-family': `"Lora", ${FALLBACK}`,
  // Das Design nutzt die Serifenschrift der Überschriften in normaler Stärke (statt fett)
  'heading-weight': '500',
};
