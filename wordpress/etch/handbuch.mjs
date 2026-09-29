// Handbuch: docs/handbuch.md → dist/handbuch.php (Inhaltsverzeichnis + Kapitel) für die Backend-Seite „Handbuch“
// (snippets/golfplatz-handbuch.php). Kleiner Markdown-Umwandler für genau die Formen, die das Handbuch nutzt:
// Überschriften, Absätze, Listen (auch verschachtelt), Tabellen, Hinweise (> …), **fett**, `Code` und [Links](…).

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

export const anker = (s) =>
  s.toLowerCase().replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

const inline = (s) =>
  esc(s)
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (_, text, href) => `<a href="${href}">${text}</a>`);

const einzug = (z) => z.match(/^ */)[0].length;
const listenPunkt = /^( *)(-|\d+\.) (.*)$/;

/** Liste ab Zeile i mit Einzug e; liefert [html, nächste Zeile]. */
function liste(zeilen, i, e) {
  const tag = /^\d+\./.test(zeilen[i].trim()) ? 'ol' : 'ul';
  const punkte = [];
  while (i < zeilen.length) {
    const m = listenPunkt.exec(zeilen[i]);
    if (!m || m[1].length !== e) break;
    let html = inline(m[3]);
    i++;
    if (i < zeilen.length) {
      const n = listenPunkt.exec(zeilen[i]);
      if (n && n[1].length > e) {
        const [unter, weiter] = liste(zeilen, i, n[1].length);
        html += unter;
        i = weiter;
      }
    }
    punkte.push(`<li>${html}</li>`);
  }
  return [`<${tag}>${punkte.join('')}</${tag}>`, i];
}

const zellen = (z) => z.trim().replace(/^\||\|$/g, '').split('|').map((c) => c.trim());

export function handbuchHtml(md) {
  const zeilen = md.replace(/\r\n/g, '\n').split('\n');
  const teile = [];
  const kapitel = [];
  let titel = '';
  let i = 0;
  while (i < zeilen.length) {
    const z = zeilen[i];
    if (!z.trim()) { i++; continue; }
    const h = /^(#{1,3}) (.*)$/.exec(z);
    if (h) {
      const stufe = h[1].length;
      if (stufe === 1) titel = h[2];
      else {
        const id = anker(h[2]);
        if (stufe === 2) kapitel.push({ id, text: h[2] });
        teile.push(`<h${stufe} id="${id}">${inline(h[2])}</h${stufe}>`);
      }
      i++;
      continue;
    }
    if (listenPunkt.test(z)) {
      const [html, weiter] = liste(zeilen, i, einzug(z));
      teile.push(html);
      i = weiter;
      continue;
    }
    if (z.startsWith('|')) {
      const kopf = zellen(z);
      i += 2; // Kopf und Trennzeile
      const rows = [];
      while (i < zeilen.length && zeilen[i].startsWith('|')) rows.push(zellen(zeilen[i++]));
      teile.push(
        `<table class="widefat striped"><thead><tr>${kopf.map((c) => `<th>${inline(c)}</th>`).join('')}</tr></thead>` +
          `<tbody>${rows.map((r) => `<tr>${r.map((c) => `<td>${inline(c)}</td>`).join('')}</tr>`).join('')}</tbody></table>`,
      );
      continue;
    }
    if (z.startsWith('> ')) {
      const text = [];
      while (i < zeilen.length && zeilen[i].startsWith('> ')) text.push(inline(zeilen[i++].slice(2)));
      teile.push(`<div class="notice notice-info inline golfplatz-handbuch__hinweis"><p>${text.join('<br>')}</p></div>`);
      continue;
    }
    const text = [];
    while (i < zeilen.length && zeilen[i].trim() && !/^(#|\||> )/.test(zeilen[i]) && !listenPunkt.test(zeilen[i])) text.push(inline(zeilen[i++]));
    teile.push(`<p>${text.join('<br>')}</p>`);
  }
  const toc = `<nav class="golfplatz-handbuch__toc" aria-label="Kapitel"><h2>Kapitel</h2><ol>${kapitel.map((k) => `<li><a href="#${k.id}">${inline(k.text)}</a></li>`).join('')}</ol></nav>`;
  return `<!-- Erzeugt aus docs/handbuch.md von wordpress/etch/build.mjs – nicht von Hand ändern. -->\n<h1>${inline(titel)}</h1>\n<div class="golfplatz-handbuch__layout">${toc}<div class="golfplatz-handbuch__inhalt">${teile.join('\n')}</div></div>\n`;
}
