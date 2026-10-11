<?php
/**
 * Plugin Name: Golfplatz – Farbschema (Hell/Dunkel)
 * Description: Umschalter zwischen hellem und dunklem Farbschema. Ohne Wahl folgt die Seite dem Gerät (ACSS „light dark“). Die Farben rechnet Automatic.css über light-dark(); dieses Modul setzt nur die ACSS-Klasse scheme--light bzw. scheme--dark am <html> und merkt sich die Wahl im Browser („hell“/„dunkel“; entspricht sie dem Gerät, wird sie gelöscht). Die Schaltfläche ist die Etch-Komponente „Farbschema-Umschalter“ ([data-scheme-toggle]); dieses Modul liefert nur die Skripte.
 *
 * Gehört auf die Live-Seite.
 */

defined( 'ABSPATH' ) || exit;

// Der Umschalter Hell/Dunkel lässt sich im Backend ein- und ausschalten: Clubdaten › Club › „Umschalter Hell/Dunkel“
// (Standard aus etch-nodes). Ausgeschaltet verschwindet die Schaltfläche, und eine gespeicherte Wahl wird nicht mehr
// angewendet – die Seite folgt dann immer dem Gerät.
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$feld = array(
			'id'        => 'farbschema_umschalter',
			'name'      => 'Umschalter Hell/Dunkel',
			'type'      => 'switch',
			'style'     => 'rounded',
			'on_label'  => 'Ja',
			'off_label' => 'Nein',
			'std'       => 1,
			'desc'      => 'Zeigt Besuchern die Schaltfläche für das helle und dunkle Design. Ausgeschaltet verschwindet sie, und die Website folgt immer der Einstellung des Geräts.',
		);
		foreach ( $boxen as $i => $b ) {
			if ( 'clubdaten-club' === ( $b['id'] ?? '' ) ) {
				$boxen[ $i ]['fields'][] = $feld;
				break;
			}
		}
		return $boxen;
	},
	20
);

/** Ist der Umschalter eingeschaltet? Ohne gespeicherten Wert: ja. */
function golfplatz_farbschema_an(): bool {
	$daten = (array) get_option( 'clubdaten', array() );
	return ! array_key_exists( 'farbschema_umschalter', $daten ) || ! empty( $daten['farbschema_umschalter'] );
}

// Solange noch nie ein Wert gespeichert wurde, den Schalter im Backend als eingeschaltet zeigen – so, wie er wirkt.
// Sonst stünde er auf „Nein“, und das nächste Speichern der Seite würde den Umschalter unbemerkt abschalten.
add_filter(
	'rwmb_farbschema_umschalter_field_meta',
	function ( $wert ) {
		return array_key_exists( 'farbschema_umschalter', (array) get_option( 'clubdaten', array() ) ) ? $wert : 1;
	}
);

// Nach dem Speichern der Einstellungsseite den Seitencache leeren, damit der Schalter sofort wirkt.
add_action( 'update_option_clubdaten', fn() => do_action( 'litespeed_purge_all' ) );

define( 'GOLFPLATZ_FARBSCHEMA_SPEICHER', 'golfplatz-farbschema' );

/**
 * Gespeicherte Wahl so früh wie möglich anwenden, damit die Seite nicht erst hell aufblitzt.
 * Ohne gespeicherte Wahl folgt die Seite der Einstellung des Geräts.
 */
add_action(
	'wp_head',
	function () {
		if ( ! golfplatz_farbschema_an() ) {
			// Umschalter ausgeschaltet: keine Schema-Klasse, Schaltfläche ausblenden.
			echo "<style>[data-scheme-toggle]{display:none!important}</style>\n";
			return;
		}
		$key = wp_json_encode( GOLFPLATZ_FARBSCHEMA_SPEICHER );
		echo "<script>try{var s=localStorage.getItem({$key});if(s==='dunkel')document.documentElement.classList.add('scheme--dark');else if(s==='hell')document.documentElement.classList.add('scheme--light')}catch(e){}</script>\n";
	},
	0
);

// Die Schaltfläche ist die Etch-Komponente „Farbschema-Umschalter“ (wordpress/etch/komponenten.mjs, Attribut data-scheme-toggle).

add_action(
	'wp_footer',
	function () {
		if ( ! golfplatz_farbschema_an() ) {
			return;
		}
		$key = wp_json_encode( GOLFPLATZ_FARBSCHEMA_SPEICHER );
		?>
<script>
(function () {
	var root = document.documentElement;
	var buttons = document.querySelectorAll('[data-scheme-toggle]');
	if (!buttons.length) return;
	var KEY = <?php echo $key; ?>, mq = window.matchMedia('(prefers-color-scheme: dark)');
	// Ohne gespeicherte Wahl folgt die Seite dem Gerät; Klassen scheme--light/scheme--dark überschreiben es (ACSS).
	function geraet() { return mq.matches ? 'dunkel' : 'hell'; }
	function aktuell() {
		if (root.classList.contains('scheme--dark')) return 'dunkel';
		if (root.classList.contains('scheme--light')) return 'hell';
		return geraet();
	}
	function anzeigen() {
		var dunkel = aktuell() === 'dunkel';
		buttons.forEach(function (b) {
			// Name aus dem sichtbaren Text („Farbschema Dunkel“/„Farbschema Hell“, WCAG 2.5.3): kein aria-label, kein aria-pressed
			b.removeAttribute('aria-label');
			b.removeAttribute('aria-pressed');
			b.setAttribute('data-farbschema', dunkel ? 'dunkel' : 'hell');
			b.title = dunkel ? 'Helles Farbschema einschalten' : 'Dunkles Farbschema einschalten';
			var text = b.querySelector('.scheme-toggle__text');
			if (text) text.textContent = dunkel ? 'Hell' : 'Dunkel';
		});
	}
	buttons.forEach(function (b) {
		b.addEventListener('click', function () {
			var neu = aktuell() === 'dunkel' ? 'hell' : 'dunkel';
			root.classList.remove('scheme--light', 'scheme--dark');
			if (neu !== geraet()) root.classList.add(neu === 'dunkel' ? 'scheme--dark' : 'scheme--light');
			try {
				// Entspricht die Wahl dem Gerät, wird nichts gespeichert: die Seite folgt dann wieder dem Gerät.
				if (neu === geraet()) localStorage.removeItem(KEY);
				else localStorage.setItem(KEY, neu);
			} catch (e) {}
			anzeigen();
		});
	});
	if (mq.addEventListener) mq.addEventListener('change', anzeigen);
	anzeigen();
})();
</script>
		<?php
	},
	50
);
