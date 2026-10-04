// Schreibt das Block-Markup aller Etch-Templates nach wordpress/etch/dist/<slug>.html.
// Übertragen nach WordPress: MCP-Funktion golfplatz/save-template (siehe wordpress/README.md).

import { copyFileSync, mkdirSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { templates, pages } from './templates.mjs';
import { components } from './komponenten.mjs';
import { acssEinstellungen } from './acss-farben.mjs';
import { acssButtons } from './acss-buttons.mjs';
import { acssSchrift } from './acss-schrift.mjs';
import { loops } from './loops.mjs';
import { handbuchHtml } from './handbuch.mjs';
import { bahnen, sperrungen, club, restaurant, abschlaege, oeffnungszeiten, platzstatus, personen, personengruppen, news, preise, kurse, kurseAnmeldung, lochwettspiele, mannschaften, ligaspiele } from '../../prototype/src/data.mjs';

const hier = dirname(fileURLToPath(import.meta.url));
const dist = join(hier, 'dist');
mkdirSync(dist, { recursive: true });

for (const tpl of templates) {
  // Präfix template-, damit ein Template wie page-birdiebook nicht mit der Seiten-Datei page-<slug>.html kollidiert
  writeFileSync(join(dist, `template-${tpl.slug}.html`), tpl.content + '\n');
  console.log(`template-${tpl.slug}.html  (${tpl.content.length} Zeichen)`);
}
for (const c of components) {
  writeFileSync(join(dist, `component-${c.key}.html`), c.content + '\n');
  console.log(`component-${c.key}.html  (${c.content.length} Zeichen)`);
}
for (const p of pages) {
  writeFileSync(join(dist, `page-${p.slug}.html`), p.content + '\n');
  console.log(`page-${p.slug}.html  (${p.content.length} Zeichen)`);
}
writeFileSync(
  join(dist, 'manifest.json'),
  JSON.stringify(
    {
      loops,
      components: components.map(({ content, ...meta }) => meta),
      templates: templates.map(({ slug, title }) => ({ slug, title })),
      pages: pages.map(({ content, ...meta }) => meta),
    },
    null,
    2,
  ) + '\n',
);

// Handbuch für die Backend-Seite „Handbuch“ (snippets/golfplatz-handbuch.php), Quelle docs/handbuch.md.
// Als .php mit Schutzzeile: Direkt über die URL aufgerufen gibt der Webserver nichts aus (das Handbuch ist nur im Backend sichtbar).
writeFileSync(join(dist, 'handbuch.php'), "<?php defined( 'ABSPATH' ) || exit; ?>\n" + handbuchHtml(readFileSync(join(hier, '../../docs/handbuch.md'), 'utf8')));
console.log('handbuch.php');

// Inhalte für golfplatz/import-content, Quelle sind die Prototyp-Daten.
mkdirSync(join(dist, 'daten'), { recursive: true });
writeFileSync(
  join(dist, 'daten/spielbahn.json'),
  JSON.stringify(
    {
      key: 'bahn_nummer',
      items: bahnen.map((b) => ({
        title: `Bahn ${b.nr}`,
        slug: String(b.nr),
        order: b.nr,
        meta: {
          bahn_nummer: b.nr,
          bahn_par_herren: String(b.parHerren),
          bahn_par_damen: String(b.parDamen),
          bahn_hcp: b.hcp,
          laenge_gelb: b.laengen.gelb,
          laenge_blau: b.laengen.blau,
          laenge_rot: b.laengen.rot,
          laenge_orange: b.laengen.orange,
          bahn_beschreibung: b.beschreibung ? `<p>${b.beschreibung}</p>` : '',
          bahn_spieltipp: b.spieltipp,
        },
      })),
    },
    null,
    2,
  ) + '\n',
);
console.log(`daten/spielbahn.json  (${bahnen.length} Bahnen)`);

// Beispiel-Sperrungen relativ zum Build-Tag. Meta Box speichert datetime mit timestamp=true
// als „Ortszeit als Unix-Zeit“, deshalb Date.UTC mit den lokalen Datumsteilen.
const ortszeit = (tagVersatz, hhmm) => {
  const d = new Date();
  d.setDate(d.getDate() + tagVersatz);
  return Date.UTC(d.getFullYear(), d.getMonth(), d.getDate(), +hhmm.slice(0, 2), +hhmm.slice(3, 5)) / 1000;
};
writeFileSync(
  join(dist, 'daten/sperrung.json'),
  JSON.stringify(
    {
      key: 'slug',
      items: sperrungen.map((s, i) => ({
        title: `${s.grund} (Beispiel)`,
        slug: `beispiel-${i + 1}`,
        meta: { sperr_bereich: s.bereich, sperr_beginn: ortszeit(s.tag, s.von), sperr_ende: ortszeit(s.tag, s.bis), sperr_grund: s.grund },
      })),
    },
    null,
    2,
  ) + '\n',
);
console.log(`daten/sperrung.json  (${sperrungen.length} Beispiel-Sperrungen)`);

// Team & Vorstand (Beitragstyp „person“) mit Personengruppen.
const slug = (s) => s.toLowerCase().replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
writeFileSync(
  join(dist, 'daten/person.json'),
  JSON.stringify(
    {
      key: 'slug',
      items: personen.map((p, i) => ({
        title: p.name,
        slug: slug(p.name),
        order: (i + 1) * 10,
        meta: { person_funktion: p.funktion, person_email: p.email || '', person_telefon: p.telefon || '', person_text: p.text || '' },
        terms: { personengruppe: p.gruppen.map((g) => personengruppen[g]) },
      })),
    },
    null,
    2,
  ) + '\n',
);
console.log(`daten/person.json  (${personen.length} Personen)`);

// Beispiel-News (Beiträge) aus dem Prototyp – zum Ersetzen durch echte Nachrichten.
writeFileSync(
  join(dist, 'daten/post.json'),
  JSON.stringify(
    {
      key: 'slug',
      items: news.map((n) => ({
        title: n.titel,
        slug: n.slug,
        date: `${n.datum} 10:00:00`,
        excerpt: n.teaser,
        content: '<!-- wp:paragraph --><p>Hier steht der vollständige Beitragstext. Er wird im WordPress-Editor gepflegt und kann Bilder, Zwischenüberschriften und Links enthalten.</p><!-- /wp:paragraph -->',
        meta: { nur_mitglieder: n.mitglieder ? 1 : 0 },
        terms: { category: [n.kategorie] },
      })),
    },
    null,
    2,
  ) + '\n',
);
console.log(`daten/post.json  (${news.length} Beispiel-Beiträge)`);

// Preise (Beitragstyp „preis“) mit Preiskategorie. Reihenfolge über menu_order wie in data.mjs.
const preisKategorien = [
  ['greenfee', 'Greenfee'],
  ['turnier', 'Turnier-Greenfee'],
  ['kooperationen', 'Kooperation'],
  ['leihe', 'Leihgeräte'],
  ['mitgliedschaft', 'Mitgliedschaft'],
];
const preisItems = preisKategorien.flatMap(([schluessel, kategorie], k) =>
  preise[schluessel].map((p, i) => ({
    title: p.titel,
    slug: slug(`${kategorie} ${p.titel} ${p.tage || ''} ${p.einheit || ''}`),
    order: (k + 1) * 100 + i,
    meta: {
      preis_betrag: p.betrag ?? '',
      preis_auf_anfrage: p.betrag === null ? 1 : 0,
      preis_einheit: p.einheit || '',
      preis_tage: p.tage || '',
      preis_zusatz: p.zusatz || '',
      preis_hervorheben: p.hervorheben ? 1 : 0,
      preis_leistungen: (p.leistungen || []).join('\n'),
    },
    terms: { preiskategorie: [kategorie] },
  })),
);
writeFileSync(join(dist, 'daten/preis.json'), JSON.stringify({ key: 'slug', items: preisItems }, null, 2) + '\n');
console.log(`daten/preis.json  (${preisItems.length} Preise)`);

// Kurse (Beitragstyp „kurs“). Preis leer = auf Anfrage.
const kursTyp = { Schnupperkurs: 'schnupperkurs', Platzreife: 'platzreife', Training: 'training', 'Kinder & Jugend': 'jugend' };
const kursItems = kurse.map((k, i) => ({
  title: k.titel,
  slug: slug(k.titel),
  order: (i + 1) * 10,
  content: `<!-- wp:paragraph --><p>${k.text}</p><!-- /wp:paragraph -->`,
  meta: {
    kurs_typ: kursTyp[k.typ] || 'training',
    kurs_preis: k.preis ?? '',
    kurs_dauer: k.dauer || '',
    kurs_max_teilnehmer: k.max ?? '',
    kurs_anmeldung: `${k.termine.length ? k.termine.join(', ') + '. ' : ''}Anmeldung unter ${kurseAnmeldung.telefon} oder ${kurseAnmeldung.email}.`,
  },
}));
writeFileSync(join(dist, 'daten/kurs.json'), JSON.stringify({ key: 'slug', items: kursItems }, null, 2) + '\n');
console.log(`daten/kurs.json  (${kursItems.length} Kurse)`);

// Lochwettspiele (Beitragstyp „lochwettspiel“), je Jahr ein Eintrag, Slug = Jahr (/turniere/lochwettspiel/2026/).
const lwItems = lochwettspiele.map((lw) => ({
  title: `Lochwettspiel ${lw.jahr}`,
  slug: String(lw.jahr),
  content: '<!-- wp:paragraph --><p>Beispiel-Turnier. Hier steht die Ausschreibung: Teilnahme, Spielform, Vorgabe und Meldung.</p><!-- /wp:paragraph -->',
  meta: {
    lw_jahr: lw.jahr,
    lw_spielform: lw.spielform,
    lw_hinweis: lw.hinweis,
    lw_runden: lw.runden.map((r) => ({ name: r.name || '', von: r.von || '', bis: r.bis })),
    lw_teams: lw.teams.map(([spieler_1, spieler_2], i) => ({ team_id: `t${i + 1}`, spieler_1, spieler_2, name: '', position: '' })),
    lw_spiele: lw.spiele.map(([runde, team, ergebnis, datum]) => ({ paarung: `${runde}:t${team}`, ergebnis, datum })),
  },
}));
writeFileSync(join(dist, 'daten/lochwettspiel.json'), JSON.stringify({ key: 'lw_jahr', items: lwItems }, null, 2) + '\n');
console.log(`daten/lochwettspiel.json  (${lwItems.length} Turniere)`);

// Mannschaften und Ligaspiele: Mit eingerichtetem Ligaportal (Clubdaten › Gäste & Systeme) kommen sie vom Landesverband
// (snippets/golfplatz-liga-sync.php). Ohne Ligaportal – wie im Blueprint – importiert golfplatz/import-content die
// Platzhalter aus dem Prototyp (erst mannschaft, dann ligaspiel). Spieler und Spielberichte pflegt der Club.
const geschlechtVon = { weiblich: 'weiblich', maennlich: 'maennlich', gemischt: 'gemischt' };
writeFileSync(
  join(dist, 'daten/mannschaft.json'),
  JSON.stringify(
    {
      key: 'slug',
      items: mannschaften.map((m, i) => ({
        title: m.titel,
        slug: m.slug,
        order: (i + 1) * 10,
        meta: {
          mannschaft_altersklasse: m.ak === 'Clubmannschaft' ? 'club' : m.ak.toLowerCase(),
          mannschaft_nummer: m.nr,
          mannschaft_geschlecht: geschlechtVon[m.geschlecht],
          mannschaft_liga: m.liga,
        },
      })),
    },
    null,
    2,
  ) + '\n',
);
console.log(`daten/mannschaft.json  (${mannschaften.length} Platzhalter-Mannschaften)`);
const ligaTitel = Object.fromEntries(mannschaften.map((m) => [m.slug, m]));
writeFileSync(
  join(dist, 'daten/ligaspiel.json'),
  JSON.stringify(
    {
      key: 'slug',
      items: ligaspiele.map((s) => ({
        title: `${ligaTitel[s.mannschaft].titel} · ${s.spieltag}. Spieltag`,
        slug: s.id,
        meta: {
          ligaspiel_mannschaft: { '@post': `mannschaft:${s.mannschaft}` },
          ligaspiel_spieltag: s.spieltag,
          // Meta Box datetime mit timestamp=true: Ortszeit als Unix-Zeit
          ligaspiel_termin: Date.UTC(+s.datum.slice(0, 4), +s.datum.slice(5, 7) - 1, +s.datum.slice(8, 10), +s.uhrzeit.slice(0, 2), +s.uhrzeit.slice(3, 5)) / 1000,
          ligaspiel_spielort: s.spielort,
          ligaspiel_heimspiel: s.heim ? 1 : 0,
          ligaspiel_platzierung: s.platzierung ?? '',
          ligaspiel_saison: +s.datum.slice(0, 4),
          ligaspiel_liga: ligaTitel[s.mannschaft].liga,
        },
      })),
    },
    null,
    2,
  ) + '\n',
);
console.log(`daten/ligaspiel.json  (${ligaspiele.length} Platzhalter-Ligaspiele)`);

// Einstellungsseiten für golfplatz/import-settings. Nur die aufgeführten Felder werden überschrieben.
// Öffnungszeiten je Bereich; Beispiel-Ausnahmen aus dem Prototyp werden nicht übernommen.
const zeitenFelder = Object.fromEntries(
  Object.entries(oeffnungszeiten).flatMap(([key, b]) => [
    [`zeiten_${key}_standard`, b.standard],
    [`zeiten_${key}_ausnahmen`, b.ausnahmen.filter((a) => !a.beispiel).map(({ titel, von, bis, geschlossen, zeiten }) => ({ titel, von, bis, geschlossen: geschlossen ? 1 : 0, zeiten }))],
    [`zeiten_${key}_hinweis`, b.hinweis],
    [`zeiten_${key}_link`, b.link || ''],
  ]),
);
const einstellungen = {
  clubdaten: {
    club_name: club.name,
    club_kurzname: club.kurzname,
    club_claim: club.claim,
    club_gegruendet: '',
    club_strasse: club.adresse[0],
    club_plz: club.adresse[1].split(' ')[0],
    club_ort: club.adresse[1].split(' ').slice(1).join(' '),
    club_telefon: club.telefon,
    club_fax: club.fax,
    club_email: club.email,
    club_anfahrt_auto: '',
    club_anfahrt_oepnv: '',
    anmeldung_telefon: club.anmeldung.telefon,
    anmeldung_hinweis: club.anmeldung.hinweis,
    ruhetag_hinweis: club.anmeldung.ruhetag,
    greenfee_hinweise: preise.hinweise,
    greenfee_fussnote: '',
    kooperationen_hinweis: preise.kooperationenHinweis,
    // Anbindungen (alle aus data.mjs; im Blueprint leer = kein Abruf)
    pccaddie_code: club.pccaddieCode,
    partnerclubs: club.partnerclubs,
    verband_name: club.verband.name,
    verband_liga_web: club.verband.ligaWeb,
    verband_liga_api: club.verband.ligaApi,
    verband_suchbegriff: club.verband.suchbegriff,
    // Twilight: Regel und Ort für den Sonnenuntergang
    twilight_regel: 'Täglich bei Start ab drei Stunden vor Sonnenuntergang.',
    twilight_stunden: 3,
    startabstand: 8,
    flight_groesse: 3,
    spielzeit_loch: 15,
    turnier_puffer: 30,
    club_geo: club.geo,
    // Auftritt und clubeigene Texte (golfplatz-club.php)
    club_logoname: club.logoName,
    club_region: club.region,
    club_logo: null,
    club_logo_hell: null,
    club_karte: null,
    club_routenlink: '',
    platz_beschreibung: club.texte.platzBeschreibung,
    mitgliedschaft_lead: club.texte.mitgliedschaftLead,
    mitgliedschaft_kontakt: club.texte.mitgliedschaftKontakt,
    aufnahmeantrag_url: club.texte.aufnahmeantragUrl,
    club_betreiber_hinweis: club.texte.betreiberHinweis,
    recht_datenschutz_kontakt: `${club.name}, ${club.adresse.join(', ')}, ${club.email}`,
    social_youtube: '',
    restaurant_name: restaurant.name,
    restaurant_telefon: restaurant.telefon,
    restaurant_hinweis: restaurant.hinweis,
    recht_vertretung: club.recht.vertretung,
    recht_registergericht: club.recht.registergericht,
    recht_registernummer: club.recht.registernummer,
    recht_steuernummer: club.recht.steuernummer,
    recht_verantwortlich: club.recht.verantwortlich,
    social_facebook: club.social.facebook,
    social_instagram: club.social.instagram,
    ...Object.fromEntries(abschlaege.map((a) => [`abschlag_${a.id}`, { geschlecht: a.geschlecht, cr: a.cr, slope: a.slope, par: a.par }])),
    // null = Feld entfernen (veraltet bzw. Platzhalter ohne echte Quelle)
    ...zeitenFelder,
    // Alte Öffnungszeiten-Felder (Freitext) entfernen
    club_oeffnungszeiten: null,
    club_oeffnungszeiten_hinweis: null,
    range_oeffnungszeiten: null,
    range_hinweis: null,
    kurzspiel_oeffnungszeiten: null,
    kurzspiel_hinweis: null,
    proshop_oeffnungszeiten: null,
    proshop_hinweis: null,
    restaurant_oeffnungszeiten: null,
    startzeiten_url: null,
    startzeiten_hinweis: null,
    club_adresse: null,
    club_mitglieder: null,
    club_jugend: null,
    recht_ust_id: null,
  },
  platzstatus: {
    gruens: platzstatus.gruens,
    gruens_hinweis: platzstatus.gruensHinweis,
    abschlaege_offen: platzstatus.abschlaegeOffen,
    buggy_gesperrt: platzstatus.buggy.gesperrt ? 1 : 0,
    trolley_gesperrt: platzstatus.trolley.gesperrt ? 1 : 0,
  },
};
for (const [id, werte] of Object.entries(einstellungen)) {
  writeFileSync(join(dist, `daten/einstellungen-${id}.json`), JSON.stringify(werte, null, 2) + '\n');
  console.log(`daten/einstellungen-${id}.json  (${Object.keys(werte).length} Felder)`);
}

// Globales Etch-Stylesheet „Golfplatz“: eigene Tokens + Komponenten-CSS aus dem Prototyp.
const kompakt = (css) =>
  css
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/\s*\n\s*/g, '\n')
    .replace(/\n{2,}/g, '\n')
    .trim();
const css = [
  kompakt(readFileSync(join(hier, 'css/tokens.css'), 'utf8')),
  kompakt(readFileSync(join(hier, '../../prototype/assets/css/main.css'), 'utf8')),
  // EtchMegaMenuPro-Farben an das Club-Design anpassen
  kompakt(readFileSync(join(hier, 'css/emmp.css'), 'utf8')),
].join('\n');
writeFileSync(join(dist, 'golfplatz.css'), css + '\n');
console.log(`golfplatz.css  (${css.length} Zeichen)`);

// ACSS-Farben und Farbschema (MCP golfplatz/acss-colors mit aus_datei: true)
const acss = acssEinstellungen();
writeFileSync(join(dist, 'daten/acss-farben.json'), JSON.stringify(acss, null, 1) + '\n');
console.log(`daten/acss-farben.json  (${Object.keys(acss).length} Einstellungen)`);
writeFileSync(join(dist, 'daten/acss-buttons.json'), JSON.stringify(acssButtons, null, 1) + '\n');
console.log(`daten/acss-buttons.json  (${Object.keys(acssButtons).length} Einstellungen)`);
writeFileSync(join(dist, 'daten/acss-schrift.json'), JSON.stringify(acssSchrift, null, 1) + '\n');
console.log(`daten/acss-schrift.json  (${Object.keys(acssSchrift).length} Einstellungen)`);

// Skript der Zählkarte (gemeinsam mit dem Prototyp), eingebunden von snippets/golfplatz-birdiebook.php
copyFileSync(new URL('../../prototype/assets/js/zaehlkarte.js', import.meta.url), join(dist, 'zaehlkarte.js'));

// PHP-Snippets für WPCodeBox (MCP golfplatz/snippets-sync liest sie aus wp-content/golfplatz/snippets/). Jede Datei beginnt mit der ABSPATH-Prüfung.
mkdirSync(join(dist, 'snippets'), { recursive: true });
for (const f of readdirSync(join(hier, '../snippets')).filter((n) => n.endsWith('.php'))) copyFileSync(join(hier, '../snippets', f), join(dist, 'snippets', f));
console.log('snippets/*.php');
console.log('zaehlkarte.js');
