<?php
/**
 * Plugin Name: Golfplatz – Farbschema (Hell/Dunkel)
 * Description: Umschalter zwischen hellem (Standard) und dunklem Farbschema. Die Farben rechnet Automatic.css über light-dark(); dieses Modul setzt nur die ACSS-Klasse scheme--dark am <html> und merkt sich die Wahl im Browser. Die Schaltfläche ist die Etch-Komponente „Farbschema-Umschalter“ ([data-scheme-toggle]); dieses Modul liefert nur die Skripte.
 *
 * Gehört auf die Live-Seite.
 */

defined( 'ABSPATH' ) || exit;

const GOLFPLATZ_FARBSCHEMA_SPEICHER = 'golfplatz-farbschema';

/**
 * Gespeicherte Wahl so früh wie möglich anwenden, damit die Seite nicht erst hell aufblitzt.
 * Ohne gespeicherte Wahl bleibt die Seite hell (Standard), unabhängig von der Systemeinstellung.
 */
add_action(
	'wp_head',
	function () {
		$key = wp_json_encode( GOLFPLATZ_FARBSCHEMA_SPEICHER );
		echo "<script>try{if(localStorage.getItem({$key})==='dunkel'){document.documentElement.classList.add('scheme--dark')}}catch(e){}</script>\n";
	},
	0
);

// Die Schaltfläche ist die Etch-Komponente „Farbschema-Umschalter“ (wordpress/etch/komponenten.mjs, Attribut data-scheme-toggle).

add_action(
	'wp_footer',
	function () {
		$key = wp_json_encode( GOLFPLATZ_FARBSCHEMA_SPEICHER );
		?>
<script>
(function () {
	var root = document.documentElement;
	var buttons = document.querySelectorAll('[data-scheme-toggle]');
	if (!buttons.length) return;
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
				if (dunkel) localStorage.setItem(<?php echo $key; ?>, 'dunkel');
				else localStorage.removeItem(<?php echo $key; ?>);
			} catch (e) {}
			anzeigen();
		});
	});
	anzeigen();
})();
</script>
		<?php
	},
	50
);
