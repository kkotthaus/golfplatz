/*
 * Zählkarte (Etch-Komponente „Zaehlkarte“, wordpress/etch/zaehlkarte.mjs; Prototyp: /platz/spielvorgaben/).
 * Handicap-Index und Abschlag wählen → Spielvorgabe und Vorgabeschläge je Loch; Schläge eintragen →
 * Netto je Loch, Stableford-Punkte brutto/netto, Summen Out/In/Gesamt.
 * Alle Werte stehen im Markup: Abschlag-Optionen (data-cr/-slope/-par/-geschlecht), Lochzeilen (data-hcp, data-par-herren/-damen).
 * Eingaben bleiben nur in diesem Browser (localStorage). Ohne JavaScript bleibt die Karte eine lesbare Tabelle.
 */
(function () {
  var karte = document.querySelector('[data-score-calc]');
  if (!karte) return;
  var SPEICHER = 'golfplatz-zaehlkarte', SPEICHER_TEE = 'golfplatz-abschlag';
  var lies = function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } };
  var merke = function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} };
  var vergiss = function (k) { try { localStorage.removeItem(k); } catch (e) {} };

  var hiFeld = karte.querySelector('[data-sc-hi]');
  var teeFeld = karte.querySelector('[data-sc-tee]');
  var fehler = karte.querySelector('[data-sc-error]');
  var svAnzeige = karte.querySelector('[data-sc-sv]');
  var zeilen = Array.prototype.slice.call(karte.querySelectorAll('[data-sc-loch]'));
  var feld = function (el, name) { return el.querySelector('[data-sc-' + name + ']'); };

  // Plus-Werte mit „+“ (Handicap-Index +1,2 bzw. Vorgabe +1 = Schlag zurückgeben)
  var plus = function (n) { return n < 0 ? '+' + -n : String(n); };
  var hiLesen = function () {
    var roh = hiFeld.value.trim().replace(',', '.');
    if (roh === '') return null;
    var hi = roh.charAt(0) === '+' ? -parseFloat(roh.slice(1)) : parseFloat(roh);
    return !isNaN(hi) && hi >= -5 && hi <= 54 ? hi : NaN;
  };
  // Vorgabeschläge je Loch nach Schwierigkeit (HCP 1 = schwerstes Loch). Plus-Vorgabe: zurück ab HCP 18.
  var vorgabeLoch = function (ph, si) {
    if (ph >= 0) return Math.floor(ph / 18) + (si <= ph % 18 ? 1 : 0);
    var p = -ph;
    return -(Math.floor(p / 18) + (si > 18 - (p % 18) ? 1 : 0));
  };
  var punkte = function (par, schlaege) { return Math.max(0, par - schlaege + 2); };

  function speichern() {
    merke(SPEICHER, JSON.stringify({
      hi: hiFeld.value,
      tee: teeFeld.value,
      schlaege: zeilen.map(function (z) { return feld(z, 'schlaege').value; }),
    }));
  }

  function rechne() {
    var opt = teeFeld.options[teeFeld.selectedIndex];
    var geschlecht = opt ? opt.getAttribute('data-geschlecht') : 'herren';
    var hi = hiLesen();
    fehler.hidden = !(typeof hi === 'number' && isNaN(hi));
    var ph = null;
    if (typeof hi === 'number' && !isNaN(hi) && opt) {
      ph = Math.round(hi * (+opt.getAttribute('data-slope') / 113) + (+opt.getAttribute('data-cr') - +opt.getAttribute('data-par')));
    }
    svAnzeige.textContent = ph === null ? '–' : plus(ph);

    var summen = {};
    var neu = function () { return { par: 0, vorgabe: 0, brutto: 0, netto: 0, pb: 0, pn: 0, gespielt: 0 }; };
    ['out', 'in', 'gesamt'].forEach(function (k) { summen[k] = neu(); });

    zeilen.forEach(function (z) {
      var nr = +z.getAttribute('data-sc-loch');
      var par = +z.getAttribute(geschlecht === 'damen' ? 'data-par-damen' : 'data-par-herren');
      var si = +z.getAttribute('data-hcp');
      var vg = ph === null ? null : vorgabeLoch(ph, si);
      var eingabe = feld(z, 'schlaege');
      var s = parseInt(eingabe.value, 10);
      var gueltig = !isNaN(s) && s >= 1 && s <= 20;
      eingabe.setAttribute('aria-invalid', eingabe.value !== '' && !gueltig ? 'true' : 'false');

      feld(z, 'par').textContent = par;
      feld(z, 'vorgabe').textContent = vg === null ? '–' : plus(vg);
      z.classList.toggle('score-calc__row--vorgabe', vg !== null && vg > 0);
      feld(z, 'netto').textContent = gueltig && vg !== null ? s - vg : '–';
      feld(z, 'pb').textContent = gueltig ? punkte(par, s) : '–';
      feld(z, 'pn').textContent = gueltig && vg !== null ? punkte(par + vg, s) : '–';

      [nr <= 9 ? 'out' : 'in', 'gesamt'].forEach(function (k) {
        var t = summen[k];
        t.par += par;
        if (vg !== null) t.vorgabe += vg;
        if (gueltig) {
          t.gespielt++;
          t.brutto += s;
          t.pb += punkte(par, s);
          if (vg !== null) { t.netto += s - vg; t.pn += punkte(par + vg, s); }
        }
      });
    });

    karte.querySelectorAll('[data-sc-summe]').forEach(function (r) {
      var t = summen[r.getAttribute('data-sc-summe')];
      var mit = t.gespielt > 0;
      feld(r, 'par').textContent = t.par;
      feld(r, 'vorgabe').textContent = ph === null ? '–' : plus(t.vorgabe);
      feld(r, 'schlaege').textContent = mit ? t.brutto : '–';
      feld(r, 'netto').textContent = mit && ph !== null ? t.netto : '–';
      feld(r, 'pb').textContent = mit ? t.pb : '–';
      feld(r, 'pn').textContent = mit && ph !== null ? t.pn : '–';
    });

    var g = summen.gesamt;
    var setze = function (name, wert) { var el = karte.querySelector('[data-sc-ergebnis="' + name + '"]'); if (el) el.textContent = wert; };
    setze('gespielt', g.gespielt + ' von 18');
    setze('brutto', g.gespielt ? g.brutto + ' Schläge' : '–');
    setze('netto', g.gespielt && ph !== null ? g.netto + ' Schläge' : '–');
    setze('pb', g.gespielt ? g.pb + (g.pb === 1 ? ' Punkt' : ' Punkte') : '–');
    setze('pn', g.gespielt && ph !== null ? g.pn + (g.pn === 1 ? ' Punkt' : ' Punkte') : '–');
  }

  function setzeStand(st) {
    if (st.tee && teeFeld.querySelector('option[value="' + st.tee + '"]')) teeFeld.value = st.tee;
    hiFeld.value = st.hi || '';
    zeilen.forEach(function (z, i) { feld(z, 'schlaege').value = (st.schlaege && st.schlaege[i]) || ''; });
  }
  function eigenerStand() {
    var st = null;
    try { st = JSON.parse(lies(SPEICHER) || 'null'); } catch (e) {}
    return st || { tee: lies(SPEICHER_TEE), hi: '', schlaege: [] };
  }

  /* Teilen: Link mit der Runde im Anker (#runde=gelb;16,4;5.4.6.-.…), bleibt im Browser, geht nicht an den Server */
  var ANKER = '#runde=';
  var status = karte.querySelector('[data-sc-status]');
  var hinweisGeteilt = karte.querySelector('[data-sc-geteilt]');
  var teilenKnopf = karte.querySelector('[data-sc-teilen]');
  var geteilt = false;
  function geteilteRunde() {
    if (location.hash.indexOf(ANKER) !== 0) return null;
    var teile = decodeURIComponent(location.hash.slice(ANKER.length)).split(';');
    if (teile.length < 3) return null;
    return { tee: teile[0], hi: teile[1], schlaege: teile[2].split('.').map(function (v) { return /^\d{1,2}$/.test(v) ? v : ''; }) };
  }
  // Zählkarte als Text-Tabelle mit festen Spaltenbreiten (24 Zeichen, passt aufs Handy). In ``` gesetzt, damit
  // WhatsApp, Signal und Telegram sie in Festbreitenschrift zeigen und die Spalten untereinander stehen.
  function tabelle() {
    var links = function (s, n) { s = String(s); while (s.length < n) s += ' '; return s; };
    var rechts = function (s, n) { s = String(s); while (s.length < n) s = ' ' + s; return s; };
    var wert = function (z, name) { var el = feld(z, name); var t = el ? (el.value !== undefined && el.tagName === 'INPUT' ? el.value : el.textContent).trim() : ''; return t === '' || t === '–' ? '-' : t; };
    var zeile = function (label, z, schlaege) {
      return links(label, 5) + rechts(wert(z, 'par'), 3) + rechts(wert(z, 'vorgabe'), 3) + rechts(schlaege, 5) + rechts(wert(z, 'netto'), 4) + rechts(wert(z, 'pn'), 4);
    };
    var summe = function (key) { return karte.querySelector('[data-sc-summe="' + key + '"]'); };
    var trenner = '------------------------';
    var out = ['Loch Par Vg Schl Net Pkt', trenner];
    zeilen.forEach(function (z, i) {
      out.push(zeile(rechts(z.getAttribute('data-sc-loch'), 4), z, wert(z, 'schlaege')));
      if (i === 8) out.push(zeile('Out', summe('out'), wert(summe('out'), 'schlaege')), trenner);
    });
    out.push(zeile('In', summe('in'), wert(summe('in'), 'schlaege')), trenner);
    out.push(zeile('Ges.', summe('gesamt'), wert(summe('gesamt'), 'schlaege')));
    return '```\n' + out.join('\n') + '\n```\nVg = Vorgabeschläge · Net = netto · Pkt = Stableford netto';
  }
  function meldung(text) { if (status) { status.textContent = text; status.hidden = !text; } }
  /* HTML-Ausgabe: eigenständiges Dokument mit Inline-Styles (für E-Mail-Programme und als Datei).
     Farben aus den festen ACSS-Tokens (--<farbe>-ref), über ein Canvas in RGB umgerechnet – E-Mail-Programme kennen
     weder CSS-Variablen noch oklch(). */
  var farbe = (function () {
    var cache = {}, ctx = null;
    try { ctx = document.createElement('canvas').getContext('2d', { willReadFrequently: true }); } catch (e) {}
    return function (token) {
      if (cache[token]) return cache[token];
      var cs = getComputedStyle(document.documentElement);
      var roh = cs.getPropertyValue('--' + token + '-ref').trim() || cs.getPropertyValue('--' + token).trim();
      // Ohne -ref-Token (z. B. --white): light-dark(hell, dunkel) → hellen Wert nehmen, die Ausgabe ist immer hell
      if (roh.indexOf('light-dark(') === 0) {
        var tiefe = 0, i = 11;
        for (; i < roh.length; i++) {
          var c = roh.charAt(i);
          if (c === '(') tiefe++;
          else if (c === ')') tiefe--;
          else if (c === ',' && tiefe === 0) break;
        }
        roh = roh.slice(11, i).trim();
      }
      var rgb = '';
      if (ctx && roh) {
        ctx.clearRect(0, 0, 1, 1);
        ctx.fillStyle = roh;
        ctx.fillRect(0, 0, 1, 1);
        var p = ctx.getImageData(0, 0, 1, 1).data;
        if (p[3]) rgb = 'rgb(' + p[0] + ',' + p[1] + ',' + p[2] + ')';
      }
      return (cache[token] = rgb || 'currentColor');
    };
  })();
  var esc = function (t) { return String(t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var zelle = function (el) {
    if (!el) return '';
    var t = (el.tagName === 'INPUT' ? el.value : el.textContent).trim();
    return t === '–' ? '' : t;
  };

  function runde() {
    var opt = teeFeld.options[teeFeld.selectedIndex];
    var wertVon = function (k) { return karte.querySelector('[data-sc-ergebnis="' + k + '"]').textContent; };
    var anker = ANKER + encodeURIComponent([teeFeld.value, hiFeld.value.trim(), zeilen.map(function (z) { return feld(z, 'schlaege').value || '-'; }).join('.')].join(';'));
    return {
      club: karte.getAttribute('data-sc-club') || '',
      abschlag: opt ? opt.textContent.trim() : '',
      hi: hiFeld.value.trim(),
      sv: svAnzeige.textContent,
      gespielt: wertVon('gespielt'), brutto: wertVon('brutto'), netto: wertVon('netto'), pb: wertVon('pb'), pn: wertVon('pn'),
      datum: new Date().toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' }),
      iso: new Date().toISOString().slice(0, 10),
      url: location.origin + location.pathname + anker,
    };
  }

  // Tabelle und Kopf als HTML-Fragment (für die Zwischenablage) – im Dokument steht es im <body>.
  function htmlFragment(r) {
    var gruen = farbe('primary'), gruenHell = farbe('primary-light'), gruenDunkel = farbe('primary-dark');
    var grau = farbe('base-ultra-light'), rahmen = farbe('base-light'), text = farbe('base'), neben = farbe('base-semi-dark'), rot = farbe('secondary'), weiss = farbe('white');
    var th = 'padding:6px 8px;background:' + gruen + ';color:' + weiss + ';font-size:12px;text-transform:uppercase;letter-spacing:.04em;text-align:center;';
    var td = 'padding:5px 8px;border-bottom:1px solid ' + rahmen + ';text-align:center;';
    var zeileHtml = function (label, z, summe) {
      var stil = td + (summe ? 'background:' + grau + ';font-weight:700;' : '');
      var vg = zelle(feld(z, 'vorgabe'));
      var vgStil = stil + (!summe && vg && vg !== '0' && vg.charAt(0) !== '+' ? 'background:' + gruenHell + ';color:' + gruenDunkel + ';font-weight:700;' : '');
      return '<tr>' +
        '<th scope="row" style="' + stil + 'font-weight:700;color:' + (summe ? text : gruenDunkel) + ';">' + esc(label) + '</th>' +
        '<td style="' + stil + '">' + esc(zelle(feld(z, 'par'))) + '</td>' +
        '<td style="' + stil + '">' + esc(summe ? '' : z.getAttribute('data-hcp')) + '</td>' +
        '<td style="' + vgStil + '">' + esc(vg) + '</td>' +
        '<td style="' + stil + 'font-weight:700;">' + esc(zelle(feld(z, 'schlaege'))) + '</td>' +
        '<td style="' + stil + '">' + esc(zelle(feld(z, 'netto'))) + '</td>' +
        '<td style="' + stil + '">' + esc(zelle(feld(z, 'pb'))) + '</td>' +
        '<td style="' + stil + 'font-weight:700;color:' + gruenDunkel + ';">' + esc(zelle(feld(z, 'pn'))) + '</td>' +
        '</tr>';
    };
    var summe = function (k) { return karte.querySelector('[data-sc-summe="' + k + '"]'); };
    var koerper = '';
    zeilen.forEach(function (z, i) {
      koerper += zeileHtml(z.getAttribute('data-sc-loch'), z, false);
      if (i === 8) koerper += zeileHtml('Out', summe('out'), true);
    });
    koerper += zeileHtml('In', summe('in'), true) + zeileHtml('Gesamt', summe('gesamt'), true);
    var kachel = function (label, wert, hell) {
      return '<td style="padding:8px 12px;background:' + (hell ? gruenHell : grau) + ';border:4px solid ' + weiss + ';">' +
        '<div style="font-size:12px;color:' + neben + ';">' + esc(label) + '</div>' +
        '<div style="font-size:20px;font-weight:700;color:' + gruenDunkel + ';">' + esc(wert) + '</div></td>';
    };
    return '<div style="font-family:Arial,Helvetica,sans-serif;color:' + text + ';max-width:640px;">' +
      '<div style="border-top:4px solid ' + rot + ';padding-top:8px;">' +
      '<h1 style="margin:0 0 4px;font-size:22px;color:' + gruenDunkel + ';">Meine Runde' + (r.club ? ' im ' + esc(r.club) : '') + '</h1>' +
      '<p style="margin:0 0 12px;font-size:14px;color:' + neben + ';">' + esc(r.datum) + ' · Abschlag ' + esc(r.abschlag) +
      (r.hi ? ' · Handicap-Index ' + esc(r.hi) : '') + (r.sv !== '–' ? ' · Spielvorgabe ' + esc(r.sv) : '') + '</p></div>' +
      '<table role="presentation" style="border-collapse:collapse;margin:0 0 12px -4px;"><tr>' +
      kachel('Gespielte Löcher', r.gespielt) + kachel('Brutto', r.brutto) + kachel('Netto', r.netto, true) +
      '</tr><tr>' + kachel('Stableford brutto', r.pb) + kachel('Stableford netto', r.pn, true) + '<td></td></tr></table>' +
      '<table style="border-collapse:collapse;width:100%;font-size:14px;">' +
      '<caption style="text-align:left;font-weight:700;padding:0 0 6px;">Zählkarte</caption>' +
      '<thead><tr><th scope="col" style="' + th + '">Loch</th><th scope="col" style="' + th + '">Par</th><th scope="col" style="' + th + '">HCP</th><th scope="col" style="' + th + '">Vorgabe</th><th scope="col" style="' + th + '">Schläge</th><th scope="col" style="' + th + '">Netto</th><th scope="col" style="' + th + '">Pkt. brutto</th><th scope="col" style="' + th + '">Pkt. netto</th></tr></thead>' +
      '<tbody>' + koerper + '</tbody></table>' +
      '<p style="margin:10px 0 0;font-size:12px;color:' + neben + ';">Vorgabe = Vorgabeschläge auf dem Loch. Netto = Schläge − Vorgabe. Stableford: Par = 2 Punkte, je Schlag besser +1.</p>' +
      '<p style="margin:10px 0 0;font-size:14px;"><a href="' + esc(r.url) + '" style="color:' + gruenDunkel + ';">Runde in der Zählkarte öffnen</a></p>' +
      '</div>';
  }

  function htmlDokument(r) {
    return '<!DOCTYPE html>\n<html lang="de">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n' +
      '<title>Meine Runde ' + esc(r.datum) + (r.club ? ' – ' + esc(r.club) : '') + '</title>\n</head>\n' +
      '<body style="margin:0;padding:16px;background:' + farbe('white') + ';">\n' + htmlFragment(r) + '\n</body>\n</html>\n';
  }

  function kurztext(r) {
    return 'Meine Runde' + (r.club ? ' im ' + r.club : '') + ': ' + r.abschlag + (r.sv !== '–' ? ', Spielvorgabe ' + r.sv : '') + '\n' +
      r.gespielt + ' Löchern · Brutto ' + r.brutto + ' · Netto ' + r.netto + '\n' +
      'Stableford ' + r.pb + ' brutto · ' + r.pn + ' netto';
  }

  function hatSchlaege() {
    if (zeilen.some(function (z) { return feld(z, 'schlaege').value !== ''; })) return true;
    meldung('Tragen Sie zuerst Ihre Schläge ein.');
    return false;
  }

  // Teilen: Handy → HTML-Datei über das Teilen-Menü; sonst formatierte Tabelle (HTML) + Text in die Zwischenablage.
  function teilen() {
    if (!hatSchlaege()) return;
    var r = runde();
    var text = kurztext(r);
    var datei = null;
    try { datei = new File([htmlDokument(r)], 'runde-' + r.iso + '.html', { type: 'text/html' }); } catch (e) {}
    if (datei && navigator.canShare && navigator.canShare({ files: [datei] })) {
      navigator.share({ files: [datei], title: 'Meine Runde', text: text + '\n' + r.url }).then(function () { meldung(''); }).catch(function () {});
      return;
    }
    var plain = text + '\n\n' + tabelle() + '\n' + r.url;
    var fertig = function () { meldung('Ergebnis als formatierte Tabelle kopiert – in E-Mail oder Dokument einfügen. Messenger übernehmen die Textfassung.'); };
    if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
      navigator.clipboard.write([new ClipboardItem({
        'text/html': new Blob([htmlFragment(r)], { type: 'text/html' }),
        'text/plain': new Blob([plain], { type: 'text/plain' }),
      })]).then(fertig, function () { textKopieren(plain); });
      return;
    }
    if (navigator.share) {
      navigator.share({ title: 'Meine Runde', text: text + '\n\n' + tabelle(), url: r.url }).then(function () { meldung(''); }).catch(function () {});
      return;
    }
    textKopieren(plain);
  }
  function textKopieren(plain) {
    var fertig = function () { meldung('Ergebnis mit Link in die Zwischenablage kopiert.'); };
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(plain).then(fertig, function () { window.prompt('Ergebnis zum Kopieren:', plain); });
    else window.prompt('Ergebnis zum Kopieren:', plain);
  }

  // Als HTML-Datei speichern (Download)
  function speichernAlsHtml() {
    if (!hatSchlaege()) return;
    var r = runde();
    var url = URL.createObjectURL(new Blob([htmlDokument(r)], { type: 'text/html' }));
    var a = document.createElement('a');
    a.href = url;
    a.download = 'runde-' + r.iso + '.html';
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    meldung('Zählkarte als HTML-Datei gespeichert (runde-' + r.iso + '.html).');
  }
  var htmlKnopf = karte.querySelector('[data-sc-html]');
  if (htmlKnopf) htmlKnopf.addEventListener('click', speichernAlsHtml);
  if (teilenKnopf) teilenKnopf.addEventListener('click', teilen);

  // Geteilte Runde aus dem Link anzeigen, sonst die eigene (bzw. den zuletzt gewählten Abschlag, auch aus dem Birdiebook).
  // Eine geteilte Runde wird erst gespeichert, wenn man etwas ändert – die eigene bleibt bis dahin erhalten.
  var fremd = geteilteRunde();
  if (fremd) {
    geteilt = true;
    setzeStand(fremd);
    if (hinweisGeteilt) hinweisGeteilt.hidden = false;
  } else {
    setzeStand(eigenerStand());
  }
  var eigeneKnopf = karte.querySelector('[data-sc-eigene]');
  if (eigeneKnopf) eigeneKnopf.addEventListener('click', function () {
    geteilt = false;
    history.replaceState(null, '', location.pathname + location.search);
    setzeStand(eigenerStand());
    if (hinweisGeteilt) hinweisGeteilt.hidden = true;
    rechne();
  });

  var geaendert = function () {
    if (geteilt) { geteilt = false; if (hinweisGeteilt) hinweisGeteilt.hidden = true; }
    meldung('');
    rechne(); speichern();
  };
  karte.addEventListener('input', geaendert);
  karte.addEventListener('change', function (e) {
    if (e.target === teeFeld) merke(SPEICHER_TEE, teeFeld.value);
    geaendert();
  });
  var reset = karte.querySelector('[data-sc-reset]');
  if (reset) reset.addEventListener('click', function () {
    zeilen.forEach(function (z) { feld(z, 'schlaege').value = ''; });
    geteilt = false;
    if (hinweisGeteilt) hinweisGeteilt.hidden = true;
    meldung('');
    vergiss(SPEICHER);
    speichern();
    rechne();
    var erstes = zeilen[0] && feld(zeilen[0], 'schlaege');
    if (erstes) erstes.focus();
  });
  karte.classList.add('has-js');
  rechne();
})();
