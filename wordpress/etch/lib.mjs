// Hilfsfunktionen, die Etch-Block-Markup erzeugen (etch/element, etch/text, etch/loop …).
// Das Format entspricht dem, was der Etch-Editor selbst speichert.

// Block-Attribute wie WordPress' serialize_block_attributes() maskieren.
// Voraussetzung: Das Plugin übergibt alle Inhalte mit wp_slash(), sonst gehen die Backslashes verloren.
export const json = (o) =>
  JSON.stringify(o)
    .replace(/\\"/g, '\\u0022')
    .replace(/--/g, '\\u002d\\u002d')
    .replace(/</g, '\\u003c')
    .replace(/>/g, '\\u003e')
    .replace(/&/g, '\\u0026');

const block = (name, attrs, inner) => {
  const a = Object.keys(attrs).length ? ' ' + json(attrs) : '';
  if (inner === null) return `<!-- wp:${name}${a} /-->`;
  return `<!-- wp:${name}${a} -->\n${inner}\n<!-- /wp:${name} -->`;
};

const join = (kinder) => kinder.flat(Infinity).filter(Boolean).join('\n');

/**
 * HTML-Element. Klasse als String, weitere Attribute als Objekt.
 * el('section', 'page-hero', [...kinder], { name: 'Seitenkopf', attrs: { id: 'x' } })
 */
export const el = (tag, klasse, kinder = [], { name, attrs = {}, styles } = {}) => {
  const attributes = { ...attrs };
  if (klasse) attributes.class = klasse;
  const a = { tag, attributes };
  if (name) a.metadata = { name };
  if (styles) a.styles = styles;
  return block('etch/element', a, join(Array.isArray(kinder) ? kinder : [kinder]));
};

/** Text, darf dynamische Daten wie {this.title} enthalten. */
export const text = (content) => block('etch/text', { content }, null);

/** Element mit einem einzelnen Text als Inhalt. */
export const t = (tag, klasse, content, opts) => el(tag, klasse, [text(content)], opts);

/** Rohes HTML, z. B. für WYSIWYG-Felder. */
export const raw = (content) => block('etch/raw-html', { content }, null);

/** Schleife über eine gespeicherte Etch-Loop (loopId) oder ein Ziel (target). */
export const loop = ({ loopId, target, itemId = 'item' }, kinder) =>
  block('etch/loop', { ...(loopId ? { loopId } : {}), ...(target ? { target } : {}), itemId }, join(kinder));

/**
 * Bedingung. Ohne Operator: Inhalt nur, wenn der Wert gefüllt ist.
 * wenn('this.metabox.bahn_spieltipp', [...]) · wenn('item.meta.x', [...], '==', '"platz"') · sonst: wenn('x', [...], 'isFalsy')
 */
export const wenn = (leftHand, kinder, operator = 'isTruthy', rightHand = null) =>
  block('etch/condition', { condition: { leftHand, operator, rightHand }, conditionString: operator === 'isTruthy' ? leftHand : operator === 'isFalsy' ? `!${leftHand}` : `${leftHand} ${operator} ${rightHand}` }, join(kinder));

/**
 * Wert aus der zentralen Einstellungsseite „Clubdaten“ (Meta Box, ID clubdaten).
 * club('club_telefon') → {options.metabox.clubdaten.club_telefon}
 * Mit Modifier: club('club_telefon', ".replaceAll(' ', '')")
 */
export const CLUB = 'options.metabox.clubdaten';
export const club = (feld, modifier = '') => `{${CLUB}.${feld}${modifier}}`;

/** tel:-Link aus einer formatierten Nummer (Leerzeichen und Bindestriche entfernen). */
export const telHref = (feld) => 'tel:' + club(feld, ".replaceAll(' ', '').replaceAll('-', '')");

/**
 * Etch-Komponente einbinden. Die ID ist beim Bauen noch unbekannt: Der Platzhalter
 * "__REF_<key>__" wird beim Sync (golfplatz/sync-from-files) durch die echte ID ersetzt.
 */
export const komponente = (key, attributes = {}) =>
  `<!-- wp:etch/component ${JSON.stringify({ ref: `__REF_${key}__`, attributes })} -->\n\n<!-- /wp:etch/component -->`;

/**
 * Vorhandene Etch-Komponente per WordPress-ID einbinden, mit Slots.
 * Für EtchMegaMenuPro (DWC Header/Nav/Dropdown/Menu Item/Mobile Toggle), die in Etch hinterlegt sind.
 * emmp(67, { text: 'Start', linkTo: '/' }, { Content: '' })
 */
export const emmp = (ref, attributes = {}, slots = {}, name) => {
  const a = { ref, attributes };
  if (name) a.metadata = { name };
  const inhalt = Object.entries(slots)
    .map(([slot, kinder]) => `<!-- wp:etch/slot-content ${json({ name: slot })} -->\n${join([kinder]) || ''}\n<!-- /wp:etch/slot-content -->`)
    .join('\n\n');
  return `<!-- wp:etch/component ${json(a)} -->\n${inhalt}\n<!-- /wp:etch/component -->`;
};

/**
 * OhMyEtch-Komponente per Key einbinden (Accordion, Breadcrumbs …): Der Sync ersetzt "__REF_<Key>__" durch die ID
 * der vorhandenen Komponente (etch_component_html_key) – keine festen IDs. Gruppen-Eigenschaften als Objekt.
 * ome('OmeAccordion', { settings: { type: 'multiple' } }, { default: [...] })
 */
export const ome = (key, eigenschaften = {}, slots = {}, name) => {
  const attributes = Object.fromEntries(
    Object.entries(eigenschaften).map(([k, v]) => [k, v !== null && typeof v === 'object' && !Array.isArray(v) ? `{${JSON.stringify(v)}}` : v]),
  );
  const a = { ref: `__REF_${key}__`, attributes };
  if (name) a.metadata = { name };
  const inhalt = Object.entries(slots)
    .map(([slot, kinder]) => `<!-- wp:etch/slot-content ${json({ name: slot })} -->\n${join([kinder]) || ''}\n<!-- /wp:etch/slot-content -->`)
    .join('\n\n');
  return `<!-- wp:etch/component ${json(a)} -->\n${inhalt}\n<!-- /wp:etch/component -->`;
};

/** Gruppen-Eigenschaft im EMMP-Format: gruppe({ mode: 'none' }) → '{{"mode":"none"}}' */
export const gruppe = (obj) => `{${JSON.stringify(obj)}}`;

/** SVG-Icon als Etch-Elemente (Pfade aus dem Prototyp). */
const iconPfade = {
  phone: 'M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z',
  mail: 'M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm8 7.2L4.5 7v10.5h15V7z',
  clock: 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zm0 1.8a7.2 7.2 0 1 0 0 14.4 7.2 7.2 0 0 0 0-14.4zm.9 2.7v4.1l3.1 1.8-.9 1.6-4-2.3V7.5z',
  flag: 'M6 2h1.8v1.2L18 7l-10.2 4v11H6z',
  user: 'M12 12a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9zm0 1.8c4.4 0 8 2.3 8 5.2V21H4v-2c0-2.9 3.6-5.2 8-5.2z',
  arrow: 'M13.2 5.3 20 12l-6.8 6.7-1.3-1.3 4.6-4.5H4v-1.8h12.5L11.9 6.6z',
  lock: 'M7 10V7a5 5 0 0 1 10 0v3h1a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-9a1 1 0 0 1 1-1zm1.8 0h6.4V7a3.2 3.2 0 0 0-6.4 0z',
  pin: 'M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7zm0 4.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z',
};

/** SVG-Grundform ohne Klasse, z. B. svgEl('circle', { cx: 10, cy: 10, r: 5, fill: '#000' }) */
export const svgEl = (tag, attrs, kinder = []) =>
  el(tag, '', kinder, { attrs: Object.fromEntries(Object.entries(attrs).map(([k, v]) => [k, String(v)])) });
export const icon = (name) =>
  el('svg', `icon icon--${name}`, [el('path', '', [], { attrs: { d: iconPfade[name] } })], {
    attrs: { viewBox: '0 0 24 24', 'aria-hidden': 'true', focusable: 'false' },
  });

/** Kern-Block „Beitragsinhalt“. */
export const postContent = () => '<!-- wp:post-content {"align":"full","layout":{"type":"default"}} /-->';

export const markup = (...kinder) => join(kinder);
