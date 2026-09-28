/*
  Öffnungszeiten: Standard je Wochentag plus Ausnahmen für einen Zeitraum.
  Wird im Browser (window.GCZeiten) und im Build (require) genutzt.
  In WordPress rechnet dasselbe mu-plugins/golfplatz-platzstatus.php.
*/
(function (root, factory) {
  if (typeof module === 'object' && module.exports) module.exports = factory();
  else root.GCZeiten = factory();
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var WOCHE = ['mo', 'di', 'mi', 'do', 'fr', 'sa', 'so'];
  var KURZ = { mo: 'Mo', di: 'Di', mi: 'Mi', do: 'Do', fr: 'Fr', sa: 'Sa', so: 'So' };
  var LANG = { mo: 'Montag', di: 'Dienstag', mi: 'Mittwoch', do: 'Donnerstag', fr: 'Freitag', sa: 'Samstag', so: 'Sonntag' };
  var pad = function (n) { return String(n).padStart(2, '0'); };

  /** „Mo–Fr“, „Sa, So“, „täglich“ */
  function tageText(tage) {
    var idx = WOCHE.filter(function (t) { return tage.indexOf(t) !== -1; }).map(function (t) { return WOCHE.indexOf(t); });
    if (idx.length === 7) return 'täglich';
    var teile = [];
    for (var i = 0; i < idx.length; i++) {
      var start = idx[i];
      while (i + 1 < idx.length && idx[i + 1] === idx[i] + 1) i++;
      var ende = idx[i];
      if (ende - start >= 2) teile.push(KURZ[WOCHE[start]] + '–' + KURZ[WOCHE[ende]]);
      else for (var k = start; k <= ende; k++) teile.push(KURZ[WOCHE[k]]);
    }
    return teile.join(', ');
  }

  /** Zeilen für die Anzeige: [{ tage: 'Mo–Fr', zeit: '10:00–17:00 Uhr' }] */
  function zeilen(zeiten) {
    return (zeiten || []).map(function (z) { return { tage: tageText(z.tage), zeit: z.von + '–' + z.bis + ' Uhr' }; });
  }

  function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function wochentag(d) { return WOCHE[(d.getDay() + 6) % 7]; }
  function datumText(isoDatum) { var p = isoDatum.split('-'); return p[2] + '.' + p[1] + '.' + p[0]; }
  function zeitraumText(a) { return a.von === a.bis ? datumText(a.von) : datumText(a.von) + '–' + datumText(a.bis); }
  function minuten(hhmm) { return +hhmm.slice(0, 2) * 60 + +hhmm.slice(3, 5); }

  /** Ausnahme, die an diesem Tag gilt (die zuletzt eingetragene gewinnt). */
  function ausnahmeAm(bereich, d) {
    var tag = iso(d);
    var treffer = (bereich.ausnahmen || []).filter(function (a) { return a.von <= tag && tag <= a.bis; });
    return treffer.length ? treffer[treffer.length - 1] : null;
  }

  /** Zeitspannen an einem Tag: { ausnahme, spannen: [{von, bis}] } */
  function zeitenAm(bereich, d) {
    var a = ausnahmeAm(bereich, d);
    var quelle = a ? (a.geschlossen ? [] : a.zeiten) : bereich.standard;
    var wt = wochentag(d);
    var spannen = (quelle || []).filter(function (z) { return z.tage.indexOf(wt) !== -1; })
      .map(function (z) { return { von: z.von, bis: z.bis }; })
      .sort(function (x, y) { return minuten(x.von) - minuten(y.von); });
    return { ausnahme: a, spannen: spannen };
  }

  /**
   * Zustand jetzt: { hatZeiten, offen, bis, naechste: { d, von } | null, ausnahme }
   * hatZeiten = false, wenn weder Standard noch Ausnahmen gepflegt sind.
   */
  function status(bereich, jetzt) {
    var hatZeiten = (bereich.standard || []).length > 0 || (bereich.ausnahmen || []).length > 0;
    var heute = zeitenAm(bereich, jetzt);
    var min = jetzt.getHours() * 60 + jetzt.getMinutes();
    var offen = heute.spannen.filter(function (s) { return minuten(s.von) <= min && min < minuten(s.bis); })[0];
    if (offen) return { hatZeiten: hatZeiten, offen: true, bis: offen.bis, naechste: null, ausnahme: heute.ausnahme };
    for (var i = 0; i < 14; i++) {
      var d = new Date(jetzt); d.setDate(d.getDate() + i);
      var spannen = zeitenAm(bereich, d).spannen.filter(function (s) { return i > 0 || minuten(s.von) > min; });
      if (spannen.length) return { hatZeiten: hatZeiten, offen: false, bis: null, naechste: { d: d, von: spannen[0].von, tage: i }, ausnahme: heute.ausnahme };
    }
    return { hatZeiten: hatZeiten, offen: false, bis: null, naechste: null, ausnahme: heute.ausnahme };
  }

  /** „Jetzt geöffnet bis 17:00 Uhr“ bzw. „Geschlossen · öffnet morgen um 10:00 Uhr“ */
  function statusText(st) {
    if (!st.hatZeiten) return '';
    if (st.offen) return 'Jetzt geöffnet bis ' + st.bis + ' Uhr';
    if (!st.naechste) return 'Geschlossen';
    var wann = st.naechste.tage === 0 ? 'heute' : st.naechste.tage === 1 ? 'morgen' : 'am ' + LANG[wochentag(st.naechste.d)];
    return 'Geschlossen · öffnet ' + wann + ' um ' + st.naechste.von + ' Uhr';
  }

  /** Ausnahmen, die heute gelten oder noch kommen, nach Beginn sortiert. */
  function kommendeAusnahmen(bereich, jetzt) {
    var tag = iso(jetzt);
    return (bereich.ausnahmen || []).filter(function (a) { return a.bis >= tag; })
      .slice().sort(function (x, y) { return x.von < y.von ? -1 : 1; });
  }

  function ausnahmeText(a) {
    return a.geschlossen ? 'geschlossen' : zeilen(a.zeiten).map(function (z) { return z.tage + ' ' + z.zeit; }).join(', ');
  }

  return {
    WOCHE: WOCHE, tageText: tageText, zeilen: zeilen, iso: iso, zeitenAm: zeitenAm, status: status,
    statusText: statusText, kommendeAusnahmen: kommendeAusnahmen, ausnahmeText: ausnahmeText, zeitraumText: zeitraumText,
  };
});
