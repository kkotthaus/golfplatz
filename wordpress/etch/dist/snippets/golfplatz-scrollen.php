<?php
/**
 * Plugin Name: Golfplatz – Sanftes Scrollen
 * Description: Sprunglinks innerhalb der Seite (#ziel bzw. dieselbe Seite mit #ziel) gleiten in 600 ms zum Ziel – wie die ACSS-Einstellung „Smooth Scrolling“ (Dauer 0,6 s, Kurve ähnlich --ease-snappy). Das reine CSS-scroll-behavior überlässt die Dauer dem Browser (oft nur ~0,3 s). Abstand zum Header wie beim Browser: scroll-margin-top des Ziels (EMMP setzt ihn bei mitlaufendem Header) plus scroll-padding-top. Bei „Bewegung reduzieren“, Klick mit Strg/Umschalt/Mittelklick, Links auf andere Seiten und fehlendem Ziel bleibt das normale Verhalten. Danach Adresse mit #ziel (Zurück funktioniert) und Fokus auf das Ziel (Tastatur, Screenreader). Keine Shortcodes, kein Markup.
 *
 * Gehört auf die Live-Seite.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_footer',
	function () {
		?>
<script>
(function () {
	var DAUER = 600; // ms, wie ACSS „scroll-animation-duration“
	var ruhig = window.matchMedia('(prefers-reduced-motion: reduce)');
	// Kurve ähnlich --ease-snappy (cubic-bezier(0.16, 1, 0.3, 1)): schneller Start, sanftes Auslaufen
	function kurve(t) { return 1 - Math.pow(1 - t, 4); }
	function px(wert) { return parseFloat(wert) || 0; }

	function zielZu(link) {
		var href = link.getAttribute('href') || '';
		if (href.indexOf('#') === -1 || link.target === '_blank' || link.hasAttribute('download')) return null;
		var url = new URL(link.href, location.href);
		if (url.origin !== location.origin || url.pathname !== location.pathname || url.search !== location.search || url.hash.length < 2) return null;
		try { return document.getElementById(decodeURIComponent(url.hash.slice(1))); } catch (e) { return null; }
	}

	function gleite(ziel, hash) {
		var html = document.documentElement;
		var abstand = px(getComputedStyle(ziel).scrollMarginTop) + px(getComputedStyle(html).scrollPaddingTop);
		var start = window.scrollY;
		var ende = Math.max(0, Math.min(ziel.getBoundingClientRect().top + start - abstand, html.scrollHeight - window.innerHeight));
		var weg = ende - start;
		var vorher = html.style.scrollBehavior;
		html.style.scrollBehavior = 'auto'; // eigenes Gleiten, nicht zusätzlich das CSS-Gleiten
		var t0 = null;
		function schritt(jetzt) {
			if (t0 === null) t0 = jetzt;
			var t = Math.min(1, (jetzt - t0) / DAUER);
			window.scrollTo(0, start + weg * kurve(t));
			if (t < 1) { requestAnimationFrame(schritt); return; }
			html.style.scrollBehavior = vorher;
			if (location.hash !== hash) history.pushState(null, '', hash);
			// Fokus für Tastatur und Screenreader, ohne erneut zu springen
			if (!ziel.matches('a[href], button, input, select, textarea, [tabindex]')) ziel.setAttribute('tabindex', '-1');
			ziel.focus({ preventScroll: true });
		}
		requestAnimationFrame(schritt);
	}

	document.addEventListener('click', function (e) {
		if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || ruhig.matches) return;
		var link = e.target.closest && e.target.closest('a[href]');
		if (!link) return;
		var ziel = zielZu(link);
		if (!ziel) return;
		e.preventDefault();
		gleite(ziel, new URL(link.href, location.href).hash);
	});
})();
</script>
		<?php
	}
);
