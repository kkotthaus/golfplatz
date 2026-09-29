// Inhalte für Prototyp und WordPress-Import – ZENTRALE KONFIGURATION DES CLUBS.
// Die Struktur entspricht den Meta-Box-Feldern in WordPress (siehe docs/umsetzung.md).
//
// BLUEPRINT: Alle Angaben sind neutrale Platzhalter („Musterclub“). Für einen neuen Club diese Datei
// (und die Farben in wordpress/etch/acss-farben.mjs) anpassen, bauen und importieren – siehe docs/neuer-club.md.
// Adressen unter example.org und Telefonnummern 01234 … sind bewusst ungültig.

export const club = {
  name: 'Golfclub Musterclub e. V.',
  kurzname: 'GC Musterclub',
  logoName: 'Golfclub Musterclub', // Clubdaten › Club › „Name im Logo“
  ort: 'Musterstadt',
  region: 'Musterregion',
  claim: 'Golf in schöner Landschaft – für Mitglieder und Gäste.',
  adresse: ['Musterstraße 1', '12345 Musterstadt'],
  telefon: '01234 5678-0',
  fax: '01234 5678-99',
  email: 'info@example.org',
  // Anmeldung für Gäste (Clubdaten › Gäste & Systeme).
  anmeldung: {
    telefon: '01234 5678-10',
    hinweis: 'Gäste sind herzlich willkommen. Bitte melden Sie sich vor Ihrer Runde im Sekretariat an – am Wochenende und an Feiertagen am besten telefonisch.',
    ruhetag: 'Montags ist Greenkeeper-Tag: Es kann zu Einschränkungen im Spielbetrieb kommen.',
  },
  social: {
    facebook: '',
    instagram: '',
  },
  recht: {
    vertretung: 'Max Mustermann (Präsident), Erika Musterfrau (Vizepräsidentin), Peter Beispiel (Schatzmeister)',
    registergericht: 'Amtsgericht Musterstadt',
    registernummer: 'VR 00000',
    steuernummer: '000/0000/0000',
    verantwortlich: 'Max Mustermann, Musterstraße 1, 12345 Musterstadt',
  },
  // Ort für den Sonnenuntergang (Twilight-Greenfee): Breite, Länge. Platzhalter = geografische Mitte Deutschlands.
  geo: '51.163, 10.448',
  // Clubeigene Texte (Clubdaten › Club › Auftritt & Texte)
  texte: {
    platzBeschreibung: 'Unser 18-Loch-Platz liegt eingebettet in eine abwechslungsreiche Landschaft. Große Grüns und breite Fairways, Teiche und Bunker an den richtigen Stellen – und schöne Ausblicke von fast jeder Bahn.',
    mitgliedschaftLead: 'Unbegrenzt spielen, mit DGV-Mitgliedschaft und vielen Vorteilen bei unseren Partnerclubs. Wir beraten Sie gern persönlich.',
    mitgliedschaftKontakt: 'Ihre Ansprechpartnerin ist Erika Musterfrau (Clubmanagement).',
    aufnahmeantragUrl: '',
    betreiberHinweis: '',
  },
  // PC CADDIE://online: Club-Kennung (leer = kein Turnierabgleich)
  pccaddieCode: '',
  // Partnerclubs für die Platzbelegung (je { name, kurzname, pccaddie_code, website, kalender_link }), leer = nur Heimatclub
  partnerclubs: [],
  // Ligaportal des Landesverbands auf liga.golf (leer = kein Abgleich, Mannschaften werden von Hand gepflegt)
  verband: { name: 'Landesverband', ligaWeb: '', ligaApi: '', suchbegriff: '' },
};

export const restaurant = {
  name: 'Clubrestaurant',
  telefon: '',
  hinweis: 'Unsere Gastronomie startet unter neuer Leitung. Öffnungszeiten und Speisekarte folgen.',
};

// Scorekarte (Platzhalter, fiktiver Par-72-Platz): Jeder Abschlag ist für ein Geschlecht bewertet.
export const abschlaege = [
  { id: 'gelb', name: 'Gelb', geschlecht: 'herren', cr: 71.0, slope: 130, par: 72 },
  { id: 'blau', name: 'Blau', geschlecht: 'herren', cr: 69.2, slope: 125, par: 72 },
  { id: 'rot', name: 'Rot', geschlecht: 'damen', cr: 72.6, slope: 128, par: 72 },
  { id: 'orange', name: 'Orange', geschlecht: 'damen', cr: 70.6, slope: 122, par: 72 },
];

// Scorekarte (Platzhalter): nr, Par Herren, Par Damen, Vorgabe (HCP), Längen Gelb, Blau, Rot, Orange.
// Form und Wasser nur für die Platzhalter-Bahngrafik im Prototyp.
const rohBahnen = [
  [1, 4, 4, 9, 360, 340, 310, 290, 'gerade', false],
  [2, 5, 5, 3, 480, 460, 420, 400, 'rechts', false],
  [3, 3, 3, 15, 160, 150, 135, 120, 'gerade', true],
  [4, 4, 4, 1, 400, 380, 345, 325, 'links', false],
  [5, 4, 4, 11, 340, 320, 290, 270, 'rechts', true],
  [6, 3, 3, 17, 145, 135, 120, 105, 'gerade', false],
  [7, 5, 5, 5, 500, 475, 430, 410, 'links', false],
  [8, 4, 4, 13, 330, 315, 285, 265, 'rechts', false],
  [9, 4, 4, 7, 375, 355, 320, 300, 'gerade', false],
  [10, 4, 4, 10, 365, 345, 315, 295, 'links', false],
  [11, 3, 3, 16, 170, 155, 140, 125, 'gerade', false],
  [12, 5, 5, 2, 510, 485, 440, 415, 'rechts', true],
  [13, 4, 4, 8, 380, 360, 325, 305, 'links', false],
  [14, 4, 4, 14, 320, 305, 275, 255, 'rechts', false],
  [15, 3, 3, 18, 135, 125, 110, 100, 'gerade', true],
  [16, 4, 4, 4, 395, 375, 340, 320, 'links', false],
  [17, 5, 5, 6, 470, 450, 410, 390, 'rechts', false],
  [18, 4, 4, 12, 355, 335, 305, 285, 'gerade', false],
];

export const bahnen = rohBahnen.map(([nr, parHerren, parDamen, hcp, gelb, blau, rot, orange, form, wasser]) => ({
  nr,
  parHerren,
  parDamen,
  hcp,
  form,
  wasser,
  laengen: { gelb, blau, rot, orange },
  // Beschreibung und Spieltipp liefert der Club. Leere Felder blenden ihren Abschnitt aus.
  beschreibung: '',
  spieltipp: '',
}));

/** Par als Text: „4“ oder „5/4“ (Herren/Damen), wie auf der Scorekarte. */
export const parText = (h, d) => (h === d ? String(h) : `${h}/${d}`);

// Sperrungen relativ zum heutigen Tag, damit der Prototyp immer etwas zeigt.
// tag: 0 = heute, 1 = morgen. In WordPress: Beitragstyp „Sperrung“.
export const sperrungen = [
  { bereich: 'abschlag_10', tag: 0, von: '08:00', bis: '13:00', grund: 'Ligaspiel AK50/1 Männlich' },
  { bereich: 'abschlag_1', tag: 0, von: '17:00', bis: '21:00', grund: 'Afterwork-Turnier' },
  { bereich: 'abschlag_1', tag: 1, von: '08:30', bis: '10:30', grund: 'Turnier: Herbstpreis des Präsidenten' },
  { bereich: 'abschlag_10', tag: 1, von: '08:30', bis: '10:30', grund: 'Turnier: Herbstpreis des Präsidenten' },
  { bereich: 'platz', tag: 1, von: '06:00', bis: '08:30', grund: 'Platzpflege: Grüns werden besandet' },
  { bereich: 'range', tag: 1, von: '07:00', bis: '09:00', grund: 'Mäharbeiten' },
];

// Clubdaten › Öffnungszeiten: je Bereich Standardzeiten (Wochentage + Uhrzeit) und Ausnahmen für einen Zeitraum.
// tage: mo di mi do fr sa so. Ausnahme: datum von–bis (JJJJ-MM-TT), geschlossen oder eigene Zeiten.
// Die Website berechnet daraus „heute“, „jetzt geöffnet/geschlossen“ und die nächste Öffnung.
export const WOCHE = ['mo', 'di', 'mi', 'do', 'fr', 'sa', 'so'];
export const oeffnungszeiten = {
  sekretariat: {
    name: 'Sekretariat',
    standard: [{ tage: WOCHE, von: '10:00', bis: '17:00' }],
    ausnahmen: [
      { titel: 'Beispiel: Winterzeit', von: '2026-11-01', bis: '2027-03-31', geschlossen: false, zeiten: [{ tage: WOCHE, von: '10:00', bis: '15:00' }], beispiel: true },
    ],
    hinweis: '',
  },
  range: {
    name: 'Driving Range',
    standard: [{ tage: WOCHE, von: '09:00', bis: '20:00' }],
    ausnahmen: [],
    hinweis: 'Rangefee 5 €.',
  },
  kurzspiel: {
    name: 'Kurzspielbereich & Putting-Grün',
    standard: [],
    ausnahmen: [],
    hinweis: 'Öffnungszeiten folgen.',
  },
  proshop: {
    name: 'Proshop',
    standard: [{ tage: WOCHE, von: '10:00', bis: '17:00' }],
    ausnahmen: [
      { titel: 'Beispiel: Inventur', von: '2026-10-05', bis: '2026-10-05', geschlossen: true, zeiten: [], beispiel: true },
    ],
    hinweis: '',
    link: '',
  },
  restaurant: {
    name: 'Clubrestaurant',
    standard: [],
    ausnahmen: [],
    hinweis: 'Öffnungszeiten folgen.',
  },
};

/** Bereiche, die im Platzstatus als „Übungsanlagen & Proshop“ erscheinen und gesperrt werden können. */
export const EINRICHTUNGEN = ['range', 'kurzspiel', 'proshop'];

// Einstellungsseite „Platzstatus“: Schnellsperren und Umschalter.
// Im Prototyp per URL änderbar: ?gesperrt=platz,range,kurzspiel,proshop,trolley,buggy  ?gruens=winter
export const platzstatus = {
  platz: { gesperrt: false, grund: 'Unwetter – Gewitterwarnung', bisStunden: 3 },
  range: { gesperrt: false, grund: 'Unwetter', bisStunden: 3 },
  kurzspiel: { gesperrt: false, grund: 'Grüns werden besandet', bisStunden: 5 },
  proshop: { gesperrt: false, grund: 'Inventur', bisStunden: 4 },
  trolley: { gesperrt: false, grund: 'Boden zu nass', bisStunden: 0 },
  buggy: { gesperrt: false, grund: 'Fairways zu nass', bisStunden: 0 },
  gruens: 'sommer',
  gruensHinweis: '',
  // Aktuell gespielte Abschläge
  abschlaegeOffen: ['gelb', 'rot'],
};

// Vorstand und Team (Platzhalter, fiktive Personen). gruppen = Taxonomie „personengruppe“.
// Kontakt nur, wenn persönlich (Durchwahl, eigene E-Mail); die allgemeine Sekretariatsnummer steht in den Clubdaten.
export const personengruppen = {
  vorstand: 'Vorstand',
  betreiber: 'Betreibergesellschaft',
  clubmanagement: 'Clubmanagement',
  sekretariat: 'Sekretariat',
  service: 'Service & Proshop',
  golfschule: 'Golfschule',
  greenkeeping: 'Greenkeeping',
  captains: 'Captains',
};

export const personen = [
  { name: 'Max Mustermann', funktion: 'Präsident', gruppen: ['vorstand'] },
  { name: 'Erika Musterfrau', funktion: 'Vizepräsidentin · Clubmanagement', gruppen: ['vorstand', 'clubmanagement'], telefon: '01234 5678-15', email: 'clubmanagement@example.org' },
  { name: 'Peter Beispiel', funktion: 'Schatzmeister', gruppen: ['vorstand'], email: 'finanzen@example.org' },
  { name: 'Sabine Vorlage', funktion: 'Schriftführerin', gruppen: ['vorstand'] },
  { name: 'Thomas Platzhalter', funktion: 'Vorstand Jugend', gruppen: ['vorstand'], email: 'jugend@example.org' },
  { name: 'Jonas Exempel', funktion: 'Geschäftsführer der Betreibergesellschaft', gruppen: ['betreiber'], telefon: '01234 5678-14' },
  { name: 'Laura Muster', funktion: 'Sekretariat', gruppen: ['sekretariat'], telefon: '01234 5678-0', email: 'info@example.org' },
  { name: 'Nina Probe', funktion: 'Service-Team · Captain Damengolf', gruppen: ['service', 'captains'], telefon: '01234 5678-12', email: 'proshop@example.org' },
  { name: 'Tom Schwung', funktion: 'PGA-Professional', gruppen: ['golfschule'], telefon: '01234 5678-30', email: 'golfschule@example.org' },
  { name: 'Lisa Abschlag', funktion: 'Professional', gruppen: ['golfschule'] },
  { name: 'Karl Rasen', funktion: 'Headgreenkeeper', gruppen: ['greenkeeping'] },
  { name: 'Heinz Beispiel', funktion: 'Captain Herrengolf', gruppen: ['captains'] },
];

/** Personen einer oder mehrerer Gruppen, in der Reihenfolge oben. */
export const personenIn = (...gruppen) => personen.filter((p) => p.gruppen.some((g) => gruppen.includes(g)));

// Greenfee und Mitgliedschaft (Platzhalter-Preise).
// Felder wie Beitragstyp „Preis“: titel, betrag (null = auf Anfrage), tage (Gültig an), einheit, zusatz.
// einheit: runde18 | runde9 | tag | runde | monat | jahr | einmalig
export const preise = {
  greenfee: [
    { titel: '18 Loch', betrag: 70, tage: 'Mo–Fr', einheit: 'runde18' },
    { titel: '18 Loch', betrag: 85, tage: 'Sa, So, Feiertag', einheit: 'runde18' },
    { titel: '9 Loch', betrag: 40, tage: 'Mo–Fr', einheit: 'runde9' },
    { titel: '9 Loch', betrag: 50, tage: 'Sa, So, Feiertag', einheit: 'runde9' },
    { titel: 'Twilight', betrag: 45, tage: 'Mo–Fr', einheit: 'runde' },
    { titel: 'Twilight', betrag: 55, tage: 'Sa, So, Feiertag', einheit: 'runde' },
    { titel: 'Rangefee', betrag: 5, tage: 'täglich', einheit: 'tag' },
  ],
  turnier: [
    { titel: 'Offenes Turnier', betrag: 45, tage: 'Mo–Fr', einheit: 'runde18' },
    { titel: 'Offenes Turnier', betrag: 55, tage: 'Sa, So', einheit: 'runde18' },
    { titel: 'Offenes Turnier', betrag: 30, tage: 'täglich', einheit: 'runde9' },
  ],
  kooperationen: [
    { titel: 'Golfclub Beispielstadt e. V.', betrag: 45, tage: 'Mo–Fr', einheit: 'runde18', zusatz: 'Handicap bis 54' },
    { titel: 'Golfpark Am See', betrag: 45, tage: 'mittwochs, freitags', einheit: 'runde18', zusatz: 'Handicap bis 54' },
  ],
  kooperationenHinweis: 'Ermäßigtes Greenfee für Mitglieder der Partnerclubs. Es ist nur eine Ermäßigung möglich.',
  leihe: [
    { titel: 'E-Buggy', betrag: 35, tage: '', einheit: 'runde18' },
    { titel: 'E-Buggy', betrag: 25, tage: '', einheit: 'runde9' },
    { titel: 'Trolley', betrag: 5, tage: '', einheit: 'runde' },
    { titel: 'Schlägersatz', betrag: 10, tage: '', einheit: 'runde' },
  ],
  hinweise: [
    'Gültiger Mitgliedsausweis eines Golfclubs',
    'Handicap: Mo–Fr bis 54, Sa, So und Feiertag bis 36',
    'Schüler und Studierende unter 27 Jahren: 50 % Ermäßigung mit Ausweis',
  ],
  mitgliedschaft: [
    {
      titel: 'Ordentliche Mitgliedschaft',
      betrag: null,
      einheit: 'jahr',
      zusatz: 'Die aktuellen Beiträge und die Aufnahmegebühr nennen wir Ihnen im persönlichen Gespräch.',
      hervorheben: true,
      leistungen: [
        'DGV-Mitgliedschaft und Handicapverwaltung',
        'Unbegrenztes Spielrecht auf 18 Loch',
        'Nutzung der Übungsanlagen',
        'Ermäßigtes Greenfee bei unseren Partnerclubs',
        'Teilnahme an Turnieren',
      ],
    },
    {
      titel: 'Partnermitgliedschaft',
      betrag: 100,
      einheit: 'monat',
      zusatz: 'Für Partner eines ordentlichen Mitglieds, bei jährlicher Zahlung.',
      leistungen: [],
    },
  ],
};

export const einheitText = {
  runde18: '18 Loch',
  runde9: '9 Loch',
  runde: 'pro Runde',
  tag: 'pro Tag',
  monat: 'pro Monat',
  jahr: 'pro Jahr',
  einmalig: 'einmalig',
};

// Golfschule (Platzhalter). preis null = auf Anfrage.
export const kurse = [
  { titel: 'Schnupperkurs', typ: 'Schnupperkurs', preis: 35, dauer: '', max: null, termine: [], text: 'Einzeln oder in kleinen Gruppen: ein Gefühl für Schläger und Ball bekommen und die Basics lernen.' },
  { titel: 'Golfunterricht', typ: 'Training', preis: null, dauer: '', max: null, termine: ['nach Absprache'], text: 'Einzelstunden oder Unterricht in kleinen Gruppen – auch zur Vorbereitung auf die DGV-Platzreife.' },
  { titel: 'DGV-Platzreife', typ: 'Platzreife', preis: null, dauer: '', max: null, termine: ['nach Absprache'], text: 'Mit der Platzreife dürfen Sie eigenständig auf dem Platz spielen.' },
];
export const kurseAnmeldung = { telefon: '01234 5678-30', email: 'golfschule@example.org' };

const vornamen = {
  weiblich: ['Anna', 'Birgit', 'Claudia', 'Doris', 'Eva', 'Friederike', 'Gabi', 'Heike', 'Ines', 'Julia', 'Karin', 'Lea', 'Monika', 'Nina', 'Petra', 'Renate', 'Susanne', 'Ute'],
  maennlich: ['Andreas', 'Bernd', 'Christian', 'Dieter', 'Erik', 'Frank', 'Georg', 'Holger', 'Jan', 'Klaus', 'Lars', 'Michael', 'Norbert', 'Oliver', 'Peter', 'Ralf', 'Stefan', 'Uwe'],
};
const nachnamen = ['Adler', 'Becker', 'Conrad', 'Dietz', 'Engel', 'Fuchs', 'Graf', 'Hahn', 'Imhof', 'Jäger', 'Keller', 'Lorenz', 'Maier', 'Neumann', 'Otto', 'Pohl', 'Roth', 'Schulz', 'Thiel', 'Vogel', 'Wolf', 'Ziegler'];

export const mannschaften = [
  { slug: 'clubmannschaft', ak: 'Clubmannschaft', nr: '', geschlecht: 'gemischt', liga: 'Landesliga Nord' },
  { slug: 'ak30-damen', ak: 'AK30', nr: '', geschlecht: 'weiblich', liga: 'Regionalliga' },
  { slug: 'ak30-herren', ak: 'AK30', nr: '', geschlecht: 'maennlich', liga: 'Oberliga' },
  { slug: 'ak50-1-damen', ak: 'AK50', nr: '1', geschlecht: 'weiblich', liga: 'Landesliga' },
  { slug: 'ak50-1-herren', ak: 'AK50', nr: '1', geschlecht: 'maennlich', liga: 'Oberliga' },
  { slug: 'ak50-2-herren', ak: 'AK50', nr: '2', geschlecht: 'maennlich', liga: 'Bezirksliga' },
  { slug: 'ak65-1-damen', ak: 'AK65', nr: '1', geschlecht: 'weiblich', liga: 'Landesliga' },
  { slug: 'ak65-1-herren', ak: 'AK65', nr: '1', geschlecht: 'maennlich', liga: 'Landesliga' },
  { slug: 'ak65-2-damen', ak: 'AK65', nr: '2', geschlecht: 'weiblich', liga: 'Bezirksliga' },
  { slug: 'ak65-2-herren', ak: 'AK65', nr: '2', geschlecht: 'maennlich', liga: 'Bezirksliga' },
].map((m, i) => {
  const g = { weiblich: 'Damen', maennlich: 'Herren', gemischt: '' }[m.geschlecht];
  const titel = [m.ak + (m.nr ? '/' + m.nr : ''), g].filter(Boolean).join(' ');
  const kader = Array.from({ length: 7 }, (_, k) => {
    const gs = m.geschlecht === 'gemischt' ? (k % 2 ? 'weiblich' : 'maennlich') : m.geschlecht;
    const vn = vornamen[gs][(i * 5 + k * 3) % vornamen[gs].length];
    const nn = nachnamen[(i * 7 + k * 5) % nachnamen.length];
    return `${vn} ${nn}`;
  });
  return { ...m, titel, kader, spielfuehrer: kader[0] };
});

const clubs = ['GC Nachbarort', 'Golfpark Am See', 'GC Waldhausen', 'Golf & Country Club Hügelland', 'GC Burg Beispiel'];

// Ligaspiele der Saison 2026. In WordPress: Beitragstyp „Ligaspiel“ mit Bezug zur Mannschaft.
export const ligaspiele = mannschaften.flatMap((m, i) => {
  const basis = [
    ['2026-05-09', 1],
    ['2026-06-13', 2],
    ['2026-07-18', 3],
    ['2026-08-22', 4],
    ['2026-09-26', 5],
  ];
  return basis.map(([datum, spieltag], k) => {
    const tagVersatz = (i % 3) - 1;
    const d = new Date(datum + 'T09:00:00');
    d.setDate(d.getDate() + tagVersatz);
    const heim = (i + k) % 5 === 2;
    const vergangen = d < new Date('2026-09-24T12:00:00');
    const platz = ((i + k * 2) % 5) + 1;
    return {
      id: `${m.slug}-${spieltag}`,
      mannschaft: m.slug,
      spieltag,
      datum: d.toISOString().slice(0, 10),
      uhrzeit: heim ? '09:00' : '08:30',
      spielort: heim ? club.kurzname : clubs[(i + k) % clubs.length],
      heim,
      platzierung: vergangen ? platz : null,
    };
  });
});

export const spielberichte = [
  {
    slug: 'ak50-1-herren-spieltag-4',
    titel: 'Starker vierter Spieltag: AK50/1 Herren auf Platz 1',
    ligaspiel: 'ak50-1-herren-4',
    autor: 'Jens Vorlage',
    text: [
      'Bei bestem Spätsommerwetter zeigte unsere AK50/1 Herren im Golfpark Am See, was in ihr steckt. Mit einem geschlossenen Mannschaftsergebnis setzte sie sich an die Spitze des Tagesklassements.',
      'Besonders hervorzuheben ist die 74er-Runde von Andreas Adler, die das beste Einzelergebnis des Tages war. Mit diesem Sieg geht die Mannschaft als Tabellenführer in den letzten Spieltag auf der eigenen Anlage.',
      'Wir freuen uns über jede Unterstützung beim Heimspiel am 26. September!',
    ],
  },
  {
    slug: 'clubmannschaft-spieltag-3',
    titel: 'Clubmannschaft behauptet sich in Waldhausen',
    ligaspiel: 'clubmannschaft-3',
    autor: 'Sekretariat',
    text: [
      'Auf dem anspruchsvollen Waldplatz in Waldhausen erkämpfte sich die Clubmannschaft einen soliden dritten Platz. Nach einem holprigen Start am Vormittag drehte das Team in den Nachmittagsvierern auf.',
      'Damit bleibt der Klassenerhalt in der Landesliga Nord in Reichweite.',
    ],
  },
];

export const news = [
  { slug: 'herbstpreis-des-praesidenten', datum: '2026-09-22', kategorie: 'Turniere', titel: 'Herbstpreis des Präsidenten: Meldeliste offen', teaser: 'Am Freitag startet das traditionelle Herbstturnier mit Kanonenstart. Bitte meldet euch bis Donnerstag, 12 Uhr, über PC CADDIE an.' },
  { slug: 'gruens-werden-besandet', datum: '2026-09-18', kategorie: 'Platz', titel: 'Grünpflege im Herbst: Was jetzt passiert', teaser: 'Unser Greenkeeping-Team aerifiziert und besandet die Grüns. Warum das nötig ist und wann der Platz wieder im Topzustand ist.' },
  { slug: 'jugend-landesmeisterschaft', datum: '2026-09-10', kategorie: 'Jugend', titel: 'Zwei Podestplätze bei der Jugend-Landesmeisterschaft', teaser: 'Unsere Jugend hat bei den Landesmeisterschaften stark gespielt. Glückwunsch an Lea und Jan zu Platz 2 und 3!' },
  { slug: 'neue-terrasse', datum: '2026-08-29', kategorie: 'Club', titel: 'Die neue Terrasse ist eröffnet', teaser: 'Mehr Plätze, mehr Schatten und der beste Blick auf das 18. Grün: Unser Clubrestaurant hat die neue Terrasse eingeweiht.' },
  { slug: 'mitgliederversammlung-2026', datum: '2026-08-15', kategorie: 'Club', titel: 'Einladung zur Mitgliederversammlung', teaser: 'Die ordentliche Mitgliederversammlung findet am 14. November im Clubhaus statt. Die Unterlagen stehen im Mitgliederbereich bereit.', mitglieder: true },
  { slug: 'firmen-golf-tag', datum: '2026-07-30', kategorie: 'Events', titel: 'Firmen-Golf-Tag: Team-Event mit Schnupperkurs', teaser: 'Golf als Teamevent: Wir haben ein Paket aus Schnupperkurs, Turnier und Abendessen geschnürt – auch für Nicht-Golfer.' },
];

// Lochwettspiel (Beispiele): je Jahr ein Turnier, Teams aus zwei Spielern in Reihenfolge der Setzliste.
// Runden: Spielzeitraum je Runde (von leer = Tag nach der Vorrunde). Ergebnisse: Runde, Siegerteam (Nummer in der
// Teamliste, wird zur team_id „t<n>“; beides zusammen ergibt lw_spiele.paarung „<runde>:t<n>“), Ergebnis, Datum.
export const lochwettspiele = [
  {
    jahr: 2026,
    spielform: 'vierball',
    hinweis: 'Spiel mit voller Vorgabe nach Spielvorgabe; Termine bitte selbst verabreden und das Ergebnis im Sekretariat melden.',
    runden: [
      { von: '2026-04-15', bis: '2026-05-31' },
      { bis: '2026-06-30' },
      { bis: '2026-08-31' },
      { bis: '2026-10-11' },
    ],
    teams: [
      ['Petra Schneider', 'Thomas Schneider'], ['Jan Becker', 'Lukas Wolf'], ['Anna Hoffmann', 'Mia Krüger'], ['Stefan Braun', 'Frank Zimmermann'],
      ['Sabine Koch', 'Uwe Richter'], ['Heike Schulz', 'Jürgen Lange'], ['Tim Schäfer', 'Nils Werner'], ['Claudia Meyer', 'Karin Fischer'],
      ['Ralf Klein', 'Dirk Neumann'], ['Laura Weber', 'Paul Weber'], ['Andrea Vogel', 'Markus Hahn'], ['Ben Keller', 'Felix Roth'],
    ],
    spiele: [
      [1, 9, '2 & 1', '2026-05-09'], [1, 5, '4 & 3', '2026-05-16'], [1, 7, '1 auf', '2026-05-23'], [1, 11, '3 & 2', '2026-05-30'],
      [2, 1, '5 & 4', '2026-06-06'], [2, 5, '2 auf', '2026-06-13'], [2, 2, '19. Loch', '2026-06-20'], [2, 11, '3 & 1', '2026-06-27'],
      [3, 5, '2 & 1', '2026-07-25'], [3, 2, 'kampflos', '2026-08-31'],
    ],
  },
  {
    jahr: 2025,
    spielform: 'vierball',
    hinweis: '',
    runden: [{ von: '2025-05-01', bis: '2025-06-30' }, { bis: '2025-08-31' }, { bis: '2025-09-30' }],
    teams: [
      ['Anna Hoffmann', 'Mia Krüger'], ['Petra Schneider', 'Thomas Schneider'], ['Ralf Klein', 'Dirk Neumann'], ['Sabine Koch', 'Uwe Richter'],
      ['Jan Becker', 'Lukas Wolf'], ['Tim Schäfer', 'Nils Werner'], ['Heike Schulz', 'Jürgen Lange'], ['Claudia Meyer', 'Karin Fischer'],
    ],
    spiele: [
      [1, 1, '3 & 2', '2025-05-24'], [1, 5, '1 auf', '2025-06-07'], [1, 2, '4 & 2', '2025-06-14'], [1, 6, '20. Loch', '2025-06-28'],
      [2, 1, '2 & 1', '2025-07-19'], [2, 6, '1 auf', '2025-08-16'],
      [3, 6, '3 & 2', '2025-09-20'],
    ],
  },
];
