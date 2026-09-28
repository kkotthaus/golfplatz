/* Frontend-Logik des Prototyps: Navigation, Platzstatus, Spielvorgaben-Rechner, Tabs, Formulare. */
(function () {
  'use strict';

  var data = window.GC_DATA || { sperrungen: [], platzstatus: {}, oeffnungszeiten: {}, einrichtungen: [], abschlaege: [] };
  var Z = window.GCZeiten;
  var pad = function (n) { return String(n).padStart(2, '0'); };
  var uhrzeit = function (d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()); };
  var datum = function (d) { return pad(d.getDate()) + '.' + pad(d.getMonth() + 1) + '.'; };
  var tagesbeginn = function (versatz) { var d = new Date(); d.setHours(0, 0, 0, 0); d.setDate(d.getDate() + versatz); return d; };

  /* ---------- Farbschema (Hell ist Standard; in WordPress: mu-plugins/golfplatz-farbschema.php) ---------- */
  (function () {
    var root = document.documentElement;
    var buttons = document.querySelectorAll('[data-scheme-toggle]');
    function anzeigen() {
      var dunkel = root.classList.contains('scheme--dark');
      buttons.forEach(function (b) {
        b.setAttribute('aria-pressed', dunkel ? 'true' : 'false');
        b.setAttribute('aria-label', dunkel ? 'Helles Farbschema einschalten' : 'Dunkles Farbschema einschalten');
        var text = b.querySelector('.scheme-toggle__text');
        if (text) text.textContent = dunkel ? 'Hell' : 'Dunkel';
      });
    }
    buttons.forEach(function (b) {
      b.addEventListener('click', function () {
        var dunkel = root.classList.toggle('scheme--dark');
        try {
          if (dunkel) localStorage.setItem('golfplatz-farbschema', 'dunkel');
          else localStorage.removeItem('golfplatz-farbschema');
        } catch (e) {}
        anzeigen();
      });
    });
    anzeigen();
  })();

  /* ---------- Hauptnavigation ---------- */
  var nav = document.querySelector('[data-main-nav]');
  var toggle = document.querySelector('[data-nav-toggle]');
  if (nav && toggle) {
    toggle.addEventListener('click', function () {
      var offen = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!offen));
      nav.classList.toggle('main-nav--open', !offen);
    });
  }
  var schliesseSubmenus = function (ausser) {
    document.querySelectorAll('.main-nav__item--has-submenu').forEach(function (item) {
      if (item === ausser) return;
      item.classList.remove('main-nav__item--open');
      item.querySelector('.main-nav__link--toggle').setAttribute('aria-expanded', 'false');
    });
  };
  document.querySelectorAll('.main-nav__link--toggle').forEach(function (btn) {
    var item = btn.closest('.main-nav__item');
    btn.addEventListener('click', function () {
      var offen = item.classList.toggle('main-nav__item--open');
      btn.setAttribute('aria-expanded', String(offen));
      schliesseSubmenus(item);
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.main-nav__item--has-submenu')) schliesseSubmenus();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var offen = document.querySelector('.main-nav__item--open');
    schliesseSubmenus();
    if (offen) offen.querySelector('.main-nav__link--toggle').focus();
  });

  /* ---------- Platzstatus ---------- */
  // Bereiche wie im Feld sperr_bereich (Beitragstyp „Sperrung“) und auf der Einstellungsseite „Platzstatus“.
  var bereichLabel = {
    abschlag_1: 'Abschlag 1', abschlag_10: 'Abschlag 10', platz: 'Ganzer Platz',
    range: 'Driving Range', kurzspiel: 'Kurzspielbereich', proshop: 'Proshop',
    trolley: 'Trolleys', buggy: 'Buggies / E-Carts'
  };
  var PLATZ = ['abschlag_1', 'abschlag_10', 'platz'];
  var EINRICHTUNGEN = ['range', 'kurzspiel', 'proshop'];
  var SCHNELL = ['platz', 'range', 'kurzspiel', 'proshop', 'trolley', 'buggy'];

  function ladeStatus() {
    var ps = data.platzstatus || {};
    var params = new URLSearchParams(location.search);
    var perUrl = (params.get('gesperrt') || (params.get('schnellsperre') === '1' ? 'platz' : '')).split(',');
    var jetzt = Date.now();

    var liste = data.sperrungen.map(function (s) {
      var tag = tagesbeginn(s.tag);
      var beginn = new Date(tag); beginn.setHours(+s.von.slice(0, 2), +s.von.slice(3, 5));
      var ende = new Date(tag); ende.setHours(+s.bis.slice(0, 2), +s.bis.slice(3, 5));
      return { bereich: s.bereich, beginn: beginn, ende: ende, grund: s.grund };
    });

    // Schnellsperren von der Einstellungsseite: gelten ab jetzt, optional mit Ende.
    var schnell = {};
    SCHNELL.forEach(function (b) {
      var w = ps[b] || {};
      if (w.gesperrt || perUrl.indexOf(b) !== -1) {
        schnell[b] = {
          bereich: b, schnell: true, grund: w.grund || 'Gesperrt',
          beginn: new Date(jetzt - 6e5),
          ende: w.bisStunden ? new Date(jetzt + w.bisStunden * 36e5) : null
        };
      }
    });

    var gruens = params.get('gruens') || ps.gruens || 'sommer';
    // Gespielte Abschläge als Text, z. B. „Gelb, Rot“
    var offen = ps.abschlaegeOffen || [];
    var abschlaegeText = (data.abschlaege || []).filter(function (a) { return offen.indexOf(a.id) !== -1; }).map(function (a) { return a.name; }).join(', ');
    return { liste: liste, schnell: schnell, gruens: gruens, gruensHinweis: ps.gruensHinweis || '', abschlaegeText: abschlaegeText };
  }

  // Aktive Sperre eines Bereichs: Schnellsperre vor geplanter Sperrung.
  function aktiveSperre(status, bereich, jetzt) {
    if (status.schnell[bereich]) return status.schnell[bereich];
    return status.liste.filter(function (s) { return s.bereich === bereich && s.beginn <= jetzt && s.ende > jetzt; })[0] || null;
  }

  function bisText(s) {
    return s.ende ? ' bis ' + uhrzeit(s.ende) + ' Uhr' : '';
  }

  function zeitraum(s, tag) {
    var tagEnde = new Date(tag); tagEnde.setDate(tagEnde.getDate() + 1);
    var von = s.beginn < tag ? 'ab ' + datum(s.beginn) + ' ' + uhrzeit(s.beginn) : uhrzeit(s.beginn);
    if (!s.ende) return 'seit ' + uhrzeit(s.beginn) + ' Uhr, bis auf Weiteres';
    var bis = s.ende > tagEnde ? datum(s.ende) + ' ' + uhrzeit(s.ende) : uhrzeit(s.ende);
    return von + '–' + bis + ' Uhr';
  }

  function eintrag(s, tag, jetzt) {
    var laeuft = s.beginn <= jetzt && (!s.ende || s.ende > jetzt);
    return '<li class="status-entry' + (s.bereich === 'platz' ? ' status-entry--platz' : '') + (laeuft ? ' status-entry--active' : '') + '">' +
      '<span class="status-entry__area">' + bereichLabel[s.bereich] + (laeuft ? '<span class="status-entry__live">· jetzt</span>' : '') + '</span>' +
      '<span class="status-entry__time">' + zeitraum(s, tag) + '</span>' +
      '<span class="status-entry__reason">' + escapeHtml(s.grund) + '</span></li>';
  }

  // Sperren eines Bereichs, die heute oder morgen noch anstehen (Schnellsperre zuerst).
  function sperrenAmTag(status, bereiche, versatz, jetzt) {
    var tag = tagesbeginn(versatz);
    var tagEnde = tagesbeginn(versatz + 1);
    var liste = status.liste
      .filter(function (s) { return bereiche.indexOf(s.bereich) !== -1 && s.beginn < tagEnde && s.ende > tag && s.ende > jetzt; })
      .sort(function (a, b) { return a.beginn - b.beginn; });
    bereiche.forEach(function (b) {
      var sn = status.schnell[b];
      if (sn && (!sn.ende || sn.ende > tag) && sn.beginn < tagEnde) liste.unshift(sn);
    });
    return liste;
  }

  function renderStatus() {
    var jetzt = new Date();
    var status = ladeStatus();
    var platzSperre = aktiveSperre(status, 'platz', jetzt);
    var abschlagSperren = ['abschlag_1', 'abschlag_10'].map(function (b) { return aktiveSperre(status, b, jetzt); }).filter(Boolean);
    var trolley = aktiveSperre(status, 'trolley', jetzt);
    var buggy = aktiveSperre(status, 'buggy', jetzt);

    // Ampel in der Top-Bar: Zustand des Platzes, dazu Hinweise zu Grüns und Carts.
    var chip = document.querySelector('[data-course-status]');
    if (chip) {
      var zustand, text;
      if (platzSperre) { zustand = 'closed'; text = 'Platz gesperrt' + (platzSperre.schnell ? ': ' + platzSperre.grund : bisText(platzSperre)); }
      else if (abschlagSperren.length) { zustand = 'restricted'; text = abschlagSperren.map(function (s) { return bereichLabel[s.bereich] + ' gesperrt' + bisText(s); }).join(' · '); }
      else { zustand = 'open'; text = 'Platz geöffnet'; }
      var extra = [];
      if (status.gruens === 'winter') extra.push('Wintergrüns');
      if (trolley) extra.push('Trolley-Verbot');
      if (buggy) extra.push('Buggy-Verbot');
      if (zustand === 'open' && extra.length) zustand = 'restricted';
      chip.classList.remove('course-status--open', 'course-status--restricted', 'course-status--closed');
      chip.classList.add('course-status--' + zustand);
      chip.querySelector('.course-status__text').textContent = [text].concat(extra).join(' · ');
    }

    // Kurzfassung im Hero der Startseite (in WordPress: Shortcode [golfplatz_status_kurz])
    var summary = document.querySelector('[data-status-summary]');
    if (summary) {
      summary.className = 'status-summary status-summary--' + zustand;
      summary.querySelector('[data-summary-text]').textContent = text;
      var heute = sperrenAmTag(status, PLATZ, 0, jetzt);
      var tag0 = tagesbeginn(0);
      var liste = heute.length
        ? '<ul class="status-summary__list">' + heute.slice(0, 3).map(function (s) {
            return '<li class="status-summary__item' + (s.bereich === 'platz' ? ' status-summary__item--platz' : '') + '"><strong>' +
              bereichLabel[s.bereich] + '</strong> ' + escapeHtml(zeitraum(s, tag0) + ' · ' + s.grund) + '</li>';
          }).join('') + (heute.length > 3 ? '<li class="status-summary__item">+ ' + (heute.length - 3) + ' weitere</li>' : '') + '</ul>'
        : '<p class="status-summary__empty">Heute keine Sperren – Platz uneingeschränkt bespielbar</p>';
      var chipHtml = function (mod, txt) { return '<li class="status-summary__chip status-summary__chip--' + mod + '">' + txt + '</li>'; };
      summary.querySelector('[data-summary-body]').innerHTML = liste +
        '<ul class="status-summary__chips" aria-label="Spielbedingungen">' +
        chipHtml(status.gruens === 'winter' ? 'winter' : 'ok', status.gruens === 'winter' ? 'Wintergrüns' : 'Sommergrüns') +
        (status.abschlaegeText ? chipHtml('ok', 'Abschläge ' + status.abschlaegeText) : '') +
        (trolley ? chipHtml('blocked', 'Trolleys gesperrt') : chipHtml('ok', 'Trolleys erlaubt')) +
        (buggy ? chipHtml('blocked', 'Buggies gesperrt') : chipHtml('ok', 'Buggies erlaubt')) + '</ul>';
    }

    renderZeiten(status, jetzt);

    // Großer Block auf der Startseite
    var board = document.querySelector('[data-status-board]');
    if (!board) return;

    var alert = board.querySelector('[data-status-alert]');
    var sn = status.schnell.platz;
    alert.hidden = !sn;
    if (sn) {
      alert.innerHTML = '<div><strong>Der Platz ist gesperrt.</strong>' + escapeHtml(sn.grund) +
        (sn.ende ? ' – voraussichtlich bis ' + uhrzeit(sn.ende) + ' Uhr.' : '') + '</div>';
    }

    // Spielbedingungen: Grüns, Trolleys, Buggies
    var bedingung = function (mod, label, wert, info) {
      return '<li class="condition condition--' + mod + '"><span class="condition__label">' + label + '</span>' +
        '<span class="condition__value">' + wert + '</span>' + (info ? '<span class="condition__info">' + escapeHtml(info) + '</span>' : '') + '</li>';
    };
    var carts = function (label, s) {
      return s ? bedingung('blocked', label, 'gesperrt', s.grund + bisText(s)) : bedingung('ok', label, 'erlaubt');
    };
    board.querySelector('[data-status-conditions]').innerHTML =
      bedingung(status.gruens === 'winter' ? 'winter' : 'ok', 'Grüns', status.gruens === 'winter' ? 'Wintergrüns' : 'Sommergrüns', status.gruensHinweis) +
      (status.abschlaegeText ? bedingung('ok', 'Abschläge', status.abschlaegeText) : '') +
      carts('Trolleys', trolley) +
      carts('Buggies / E-Carts', buggy);

    // Heute / morgen: Platz und Abschläge
    [0, 1].forEach(function (versatz) {
      var tag = tagesbeginn(versatz);
      board.querySelector('[data-status-date="' + versatz + '"]').textContent = tag.toLocaleDateString('de-DE', { weekday: 'long', day: 'numeric', month: 'long' });
      var eintraege = sperrenAmTag(status, PLATZ, versatz, jetzt);
      board.querySelector('[data-status-list="' + versatz + '"]').innerHTML = eintraege.length
        ? eintraege.map(function (s) { return eintrag(s, tag, jetzt); }).join('')
        : '<li class="status-board__empty">Platz uneingeschränkt bespielbar</li>';
    });

    // Übungsanlagen & Proshop: jetzt offen/gesperrt, dazu anstehende Sperren heute und morgen
    board.querySelector('[data-status-facilities]').innerHTML = EINRICHTUNGEN.map(function (b) {
      var aktiv = aktiveSperre(status, b, jetzt);
      var geplant = [0, 1].map(function (v) {
        return sperrenAmTag(status, [b], v, jetzt)
          .filter(function (s) { return s !== aktiv; })
          .map(function (s) { return (v ? 'Morgen ' : 'Heute ') + zeitraum(s, tagesbeginn(v)) + ' gesperrt: ' + s.grund; });
      }).reduce(function (a, b) { return a.concat(b); }, []);
      // Sperre vor Öffnungszeiten; ohne gepflegte Zeiten gilt „geöffnet“.
      var st = zeitenStatus(b, jetzt);
      var mod = aktiv ? 'blocked' : st && !st.offen ? 'closed' : 'ok';
      var zustandText = aktiv ? (b === 'proshop' ? 'geschlossen' : 'gesperrt') + bisText(aktiv) : st ? Z.statusText(st) : 'geöffnet';
      return '<li class="facility-status facility-status--' + mod + '">' +
        '<span class="facility-status__name">' + bereichLabel[b] + '</span>' +
        '<span class="facility-status__state">' + zustandText + '</span>' +
        (aktiv ? '<span class="facility-status__info">' + escapeHtml(aktiv.grund) + '</span>' : '') +
        geplant.map(function (g) { return '<span class="facility-status__info">' + escapeHtml(g) + '</span>'; }).join('') +
        '</li>';
    }).join('');

    board.querySelector('[data-status-updated]').textContent = 'Stand: ' + uhrzeit(jetzt) + ' Uhr';
  }

  // Öffnungszeiten eines Bereichs jetzt (null, wenn keine Zeiten gepflegt sind).
  function zeitenStatus(key, jetzt) {
    var bereich = (data.oeffnungszeiten || {})[key];
    if (!bereich || !Z) return null;
    var st = Z.status(bereich, jetzt);
    return st.hatZeiten ? st : null;
  }

  // Alle Öffnungszeiten-Blöcke: Zustand jetzt (Sperre vor Öffnungszeiten) und kommende Ausnahmen.
  function renderZeiten(status, jetzt) {
    document.querySelectorAll('[data-zeiten]').forEach(function (block) {
      var key = block.getAttribute('data-zeiten');
      var bereich = (data.oeffnungszeiten || {})[key];
      if (!bereich || !Z) return;
      var sperre = aktiveSperre(status, key, jetzt);
      var st = zeitenStatus(key, jetzt);
      var el = block.querySelector('[data-zeiten-status]');
      var mod = sperre ? 'blocked' : st ? (st.offen ? 'open' : 'closed') : '';
      el.className = 'opening-hours__status' + (mod ? ' opening-hours__status--' + mod : '');
      el.textContent = sperre ? (key === 'proshop' ? 'Geschlossen' : 'Gesperrt') + bisText(sperre) + ' – ' + sperre.grund : st ? Z.statusText(st) : '';
      el.hidden = !el.textContent;
      var karte = block.closest('[data-facility]');
      if (karte) {
        karte.classList.toggle('facility-card--blocked', !!sperre);
        karte.classList.toggle('facility-card--closed', !sperre && !!st && !st.offen);
      }
      var liste = block.querySelector('[data-zeiten-ausnahmen]');
      var ausnahmen = Z.kommendeAusnahmen(bereich, jetzt);
      liste.hidden = !ausnahmen.length;
      liste.innerHTML = ausnahmen.map(function (a) {
        return '<li class="opening-hours__exception"><strong>' + escapeHtml(a.titel) + '</strong> ' + Z.zeitraumText(a) + ': ' + escapeHtml(Z.ausnahmeText(a)) + '</li>';
      }).join('');
    });
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }

  renderStatus();
  setInterval(renderStatus, 60000);

  /* ---------- Spielvorgaben-Rechner ---------- */
  var rechner = document.querySelector('[data-calculator]');
  if (rechner) {
    var input = rechner.querySelector('[data-calculator-input]');
    var fehler = rechner.querySelector('[data-calculator-error]');
    var rechne = function () {
      var roh = input.value.trim().replace(',', '.');
      var hi = roh.charAt(0) === '+' ? -parseFloat(roh.slice(1)) : parseFloat(roh);
      var gueltig = roh !== '' && !isNaN(hi) && hi >= -5 && hi <= 54;
      fehler.hidden = gueltig || roh === '';
      data.abschlaege.forEach(function (a) {
        // Jeder Abschlag ist für ein Geschlecht bewertet (Scorekarte).
        var zelle = rechner.querySelector('[data-sv="' + a.id + '"]');
        if (!gueltig) { zelle.textContent = '–'; return; }
        var sv = Math.round(hi * (a.slope / 113) + (a.cr - a.par));
        zelle.textContent = sv < 0 ? '+' + -sv : String(sv);
      });
    };
    input.addEventListener('input', rechne);
  }

  /* ---------- Tabs ---------- */
  document.querySelectorAll('[data-tabs]').forEach(function (tabs) {
    var reiter = Array.prototype.slice.call(tabs.querySelectorAll('.tabs__tab'));
    var aktiviere = function (tab) {
      reiter.forEach(function (t) {
        var an = t === tab;
        t.classList.toggle('tabs__tab--active', an);
        t.setAttribute('aria-selected', String(an));
        t.tabIndex = an ? 0 : -1;
        document.getElementById(t.getAttribute('aria-controls')).hidden = !an;
      });
    };
    reiter.forEach(function (tab, i) {
      tab.addEventListener('click', function () { aktiviere(tab); });
      tab.addEventListener('keydown', function (e) {
        var ziel = e.key === 'ArrowRight' ? reiter[(i + 1) % reiter.length] : e.key === 'ArrowLeft' ? reiter[(i - 1 + reiter.length) % reiter.length] : null;
        if (ziel) { e.preventDefault(); aktiviere(ziel); ziel.focus(); }
      });
    });
  });

  /* ---------- Formulare (Prototyp: kein Versand) ---------- */
  document.querySelectorAll('[data-prototype-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = form.querySelector('[data-form-message]');
      msg.hidden = false;
      msg.textContent = 'Prototyp: Das Formular wird nicht versendet. In WordPress übernimmt das ein Formular-Plugin bzw. der WordPress-Login.';
    });
  });
})();
