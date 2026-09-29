<?php
/**
 * Plugin Name: Golfplatz – Bahnen & Birdiebook
 * Description: Daten der 18 Spielbahnen für Etch: je Bahn {this|item.golfplatz.plan|status|fahne|entfernungen}, dazu Scorekarte und Rating unter {options.golfplatz.platz}. Außerdem Birdiebook-Skript (Abschlag-Wahl, Direktlink #bahn-7, zuletzt gesehene Bahn) und Web-App-Manifest. Keine Shortcodes: das Markup bauen die Etch-Komponenten (wordpress/etch/birdiebook.mjs).
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-birdiebook.php
 * Konzept: docs/konzept-birdiebook.md
 */

defined( 'ABSPATH' ) || exit;

// Build-Dateien (wordpress/etch/build.mjs → dist) liegen in wp-content/golfplatz/. Als WPCodeBox-Snippet läuft der Code per eval(),
// __DIR__ zeigt deshalb nicht auf diesen Ordner.
defined( 'GOLFPLATZ_DATEN' ) || define( 'GOLFPLATZ_DATEN', WP_CONTENT_DIR . '/golfplatz' );

define( 'GOLFPLATZ_ABSCHLAEGE', array(
	'gelb'   => array( 'Gelb', 'herren' ),
	'blau'   => array( 'Blau', 'herren' ),
	'rot'    => array( 'Rot', 'damen' ),
	'orange' => array( 'Orange', 'damen' ),
) );

define( 'GOLFPLATZ_HINDERNIS_ARTEN', array(
	'bunker' => 'Bunker',
	'wasser' => 'Wasser',
	'aus'    => 'Aus',
	'baum'   => 'Bäume',
	'marker' => 'Marker',
) );

/** Alle Bahnen, sortiert nach Nummer. */
function golfplatz_bahnen(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	$posts = get_posts(
		array(
			'post_type'      => 'spielbahn',
			'post_status'    => 'publish',
			'posts_per_page' => 18,
			'meta_key'       => 'bahn_nummer',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	foreach ( $posts as $p ) {
		$m  = fn( string $k ) => get_post_meta( $p->ID, $k, true );
		$nr = (int) $m( 'bahn_nummer' );
		if ( $nr < 1 ) {
			continue;
		}
		$laengen = array();
		foreach ( array_keys( GOLFPLATZ_ABSCHLAEGE ) as $tee ) {
			$laengen[ $tee ] = (int) $m( 'laenge_' . $tee );
		}
		$hindernisse = array();
		foreach ( (array) $m( 'bahn_hindernisse' ) as $h ) {
			if ( ! is_array( $h ) || '' === (string) ( $h['bis_gruenmitte'] ?? '' ) ) {
				continue;
			}
			$hindernisse[] = array(
				'bezeichnung' => (string) ( $h['bezeichnung'] ?? '' ),
				'art'         => isset( GOLFPLATZ_HINDERNIS_ARTEN[ $h['art'] ?? '' ] ) ? $h['art'] : 'marker',
				'seite'       => in_array( $h['seite'] ?? '', array( 'links', 'mitte', 'rechts' ), true ) ? $h['seite'] : 'mitte',
				'bis'         => (int) $h['bis_gruenmitte'],
			);
		}
		usort( $hindernisse, fn( $a, $b ) => $b['bis'] <=> $a['bis'] );
		$cache[ $nr ] = array(
			'id'          => $p->ID,
			'nr'          => $nr,
			'par_herren'  => (int) $m( 'bahn_par_herren' ),
			'par_damen'   => (int) $m( 'bahn_par_damen' ),
			'hcp'         => (int) $m( 'bahn_hcp' ),
			'laengen'     => $laengen,
			'richtung'    => in_array( $m( 'bahn_richtung' ), array( 'dogleg_links', 'dogleg_rechts' ), true ) ? $m( 'bahn_richtung' ) : 'gerade',
			'gruen_tiefe' => (int) $m( 'gruen_tiefe' ),
			'hindernisse' => $hindernisse,
			'grafik'      => (int) $m( 'bahn_grafik' ),
			'grafik_hoch' => (int) $m( 'bahn_grafik_hoch' ),
			'pin_grafik'  => (int) $m( 'pin_grafik' ),
			'link'      => get_permalink( $p ),
		);
	}
	ksort( $cache );
	return $cache;
}

function golfplatz_bahn( $nr ): ?array {
	return golfplatz_bahnen()[ (int) $nr ] ?? null;
}

function golfplatz_par_text( int $h, int $d ): string {
	return $h === $d ? (string) $h : $h . '/' . $d;
}

function golfplatz_zahl( $n, int $stellen = 0 ): string {
	return number_format( (float) $n, $stellen, ',', '.' );
}


/**
 * Lage der Fahne im gezeichneten Grün als Wert für das style-Attribut (--pin-x, --pin-y von 0 bis 1, y von oben).
 * „Vorne“ liegt zum Abschlag hin, also unten. Mit Metern ab Grünanfang und bekannter Grüntiefe genauer.
 */
function golfplatz_fahne_stil( array $bahn ): string {
	$p    = function_exists( 'golfplatz_fahne_bahn' ) ? golfplatz_fahne_bahn( $bahn['nr'] ) : null;
	$lage = $p['lage'] ?? null;
	if ( ! $lage ) {
		return ''; // Lage der Position an der Bahn nicht gepflegt: Fahne bleibt in der Mitte des Grüns
	}
	$x = array( 'links' => 0.3, 'mitte' => 0.5, 'rechts' => 0.7 )[ $lage['seite'] ];
	$y = array( 'hinten' => 0.3, 'mitte' => 0.5, 'vorne' => 0.7 )[ $lage['tiefe'] ];
	if ( null !== $lage['meter'] && $bahn['gruen_tiefe'] > 0 ) {
		$y = 1 - min( 0.85, max( 0.15, $lage['meter'] / $bahn['gruen_tiefe'] ) );
	}
	return sprintf( '--pin-x:%s;--pin-y:%s', $x, round( $y, 2 ) );
}

/**
 * Daten einer Bahn für Etch: {this.golfplatz.…} bzw. {item.golfplatz.…} im Loop.
 * plan: Werte für die gezeichnete Bahngrafik; status: Zustand jetzt; fahne: Fahnenposition; entfernungen: Liste zur Grafik.
 */
function golfplatz_bahn_etch( array $bahn ): array {
	// Grafik
	$laenge      = max( 1, $bahn['laengen']['gelb'] ?: max( $bahn['laengen'] ) );
	$hindernisse = array_map(
		fn( $h ) => array(
			'art'   => $h['art'],
			'seite' => $h['seite'],
			'pos'   => round( min( 1, max( 0, $h['bis'] / $laenge ) ), 3 ),
			'bis'   => $h['bis'],
		),
		$bahn['hindernisse']
	);
	$bild_hoch   = $bahn['grafik_hoch'] ?: $bahn['grafik'];

	// Status: Platz- bzw. Abschlagsperre, Wintergrün, frei
	$status = array( 'mod' => 'frei', 'text' => 'Frei' );
	if ( function_exists( 'golfplatz_platzstatus_daten' ) ) {
		$d      = golfplatz_platzstatus_daten();
		$sperre = golfplatz_aktive_sperre( $d, 'platz' );
		if ( ! $sperre && in_array( $bahn['nr'], array( 1, 10 ), true ) ) {
			$sperre = golfplatz_aktive_sperre( $d, 'abschlag_' . $bahn['nr'] );
		}
		if ( $sperre ) {
			$status = array( 'mod' => 'gesperrt', 'text' => ( 'platz' === $sperre['bereich'] ? 'Platz gesperrt' : 'Abschlag gesperrt' ) . golfplatz_bis_text( $sperre ) );
		} elseif ( 'winter' === $d['gruens'] ) {
			$status = array( 'mod' => 'winter', 'text' => 'Wintergrün' );
		}
	}

	// Fahne
	$p     = function_exists( 'golfplatz_fahne_bahn' ) ? golfplatz_fahne_bahn( $bahn['nr'] ) : null;
	$fahne = array( 'vorhanden' => (bool) $p );
	if ( $p ) {
		$fahne += array(
			'text'     => golfplatz_fahne_text( $p ),
			'ausnahme' => $p['ausnahme'],
		);
	}

	// Entfernungen (Text zur Grafik)
	$liste = array_map(
		fn( $h ) => array(
			'art'   => $h['art'],
			'name'  => $h['bezeichnung'] ?: GOLFPLATZ_HINDERNIS_ARTEN[ $h['art'] ],
			'seite' => 'mitte' === $h['seite'] ? '' : $h['seite'],
			'wert'  => $h['bis'] . ' m',
		),
		$bahn['hindernisse']
	);

	return array(
		'plan'         => array(
			'par'           => $bahn['par_herren'],
			'richtung'      => $bahn['richtung'],
			'pin_stil'      => golfplatz_fahne_stil( $bahn ),
			'hindernisse'   => $hindernisse,
			'hat_bild'      => (bool) $bild_hoch,
			'bild'          => $bild_hoch ? (string) wp_get_attachment_image_url( $bild_hoch, 'large' ) : '',
			'hat_bild_karte' => (bool) $bahn['grafik'],
			'bild_karte'    => $bahn['grafik'] ? (string) wp_get_attachment_image_url( $bahn['grafik'], 'medium_large' ) : '',
			'hat_pin_karte' => (bool) $bahn['pin_grafik'],
			'pin_karte'     => $bahn['pin_grafik'] ? (string) wp_get_attachment_image_url( $bahn['pin_grafik'], 'full' ) : '',
		),
		'status'       => $status,
		'fahne'        => $fahne,
		'entfernungen' => array(
			'vorhanden'   => (bool) ( $liste || $bahn['gruen_tiefe'] || $p ),
			'liste'       => $liste,
			'gruen_tiefe' => $bahn['gruen_tiefe'] ? $bahn['gruen_tiefe'] . ' m' : '',
		),
	);
}

/** WHS: Spielvorgabe = HI × Slope ÷ 113 + (CR − Par), gerundet wie Math.round im Rechner (x,5 aufwärts). */
function golfplatz_spielvorgabe( float $hi, float $cr, int $slope, int $par ): int {
	return (int) floor( $hi * $slope / 113 + ( $cr - $par ) + 0.5 );
}

/** Handicap-Index als Text: negative Werte sind Plus-Handicaps („+3,8“). */
function golfplatz_hi_text( float $hi ): string {
	return ( $hi < 0 ? '+' : '' ) . number_format( abs( $hi ), 1, ',', '.' );
}

/**
 * Spielvorgaben je Abschlag für {options.golfplatz.platz.spielvorgaben}: Werte für den Rechner (cr_zahl mit Punkt
 * für data-Attribute) und die Tabelle „Handicap-Index von–bis → Spielvorgabe“ (+5,0 bis 54,0).
 * erster/tabindex/versteckt steuern die Reiter ohne JavaScript-Wissen im Markup.
 */
function golfplatz_spielvorgaben_etch( array $clubdaten, callable $fuer ): array {
	$liste = array();
	foreach ( GOLFPLATZ_ABSCHLAEGE as $tee => list( $name ) ) {
		$a = (array) ( $clubdaten[ 'abschlag_' . $tee ] ?? array() );
		if ( empty( $a['cr'] ) || empty( $a['slope'] ) || empty( $a['par'] ) ) {
			continue;
		}
		$cr    = (float) str_replace( ',', '.', (string) $a['cr'] );
		$slope = (int) $a['slope'];
		$par   = (int) $a['par'];

		$zeilen = array();
		for ( $i = -50; $i <= 540; $i++ ) {
			$hi   = $i / 10;
			$sv   = golfplatz_spielvorgabe( $hi, $cr, $slope, $par );
			$last = count( $zeilen ) - 1;
			if ( $last >= 0 && $zeilen[ $last ]['sv_zahl'] === $sv ) {
				$zeilen[ $last ]['bis'] = $hi;
			} else {
				$zeilen[] = array( 'von' => $hi, 'bis' => $hi, 'sv_zahl' => $sv );
			}
		}
		$zeilen = array_map(
			fn( $z ) => array(
				'hi' => golfplatz_hi_text( $z['von'] ) . ' – ' . golfplatz_hi_text( $z['bis'] ),
				'sv' => $z['sv_zahl'] < 0 ? '+' . abs( $z['sv_zahl'] ) : (string) $z['sv_zahl'],
			),
			$zeilen
		);

		$erster  = ! $liste;
		$liste[] = array(
			'tee'      => $tee,
			'name'     => $name,
			'fuer'     => $fuer( $tee ),
			'geschlecht' => 'Damen' === $fuer( $tee ) ? 'damen' : 'herren',
			'cr'       => golfplatz_zahl( $cr, 1 ),
			'cr_zahl'  => (string) $cr,
			'slope'    => $slope,
			'par'      => $par,
			'erster'   => $erster,
			'selected' => $erster ? 'true' : 'false',
			'tabindex' => $erster ? '0' : '-1',
			'zeilen'   => $zeilen,
		);
	}
	return $liste;
}

/** Eine Lochzeile der Zählkarte. */
function golfplatz_zaehlkarte_loch( array $b ): array {
	return array(
		'nr'         => $b['nr'],
		'hcp'        => $b['hcp'],
		'par'        => golfplatz_par_text( $b['par_herren'], $b['par_damen'] ),
		'par_herren' => $b['par_herren'],
		'par_damen'  => $b['par_damen'],
	);
}

/** Scorekarte und Course/Slope-Rating für Etch: {options.golfplatz.platz.…} */
function golfplatz_platz_etch(): array {
	$bahnen    = golfplatz_bahnen();
	$clubdaten = (array) get_option( 'clubdaten', array() );
	$tees      = array_keys( GOLFPLATZ_ABSCHLAEGE );
	$fuer      = fn( string $tee ) => 'herren' === ( $clubdaten[ 'abschlag_' . $tee ]['geschlecht'] ?? GOLFPLATZ_ABSCHLAEGE[ $tee ][1] ) ? 'Herren' : 'Damen';

	$zeile = fn( array $b ) => array(
		'nr'      => $b['nr'],
		'link'    => wp_make_link_relative( $b['link'] ),
		'par'     => golfplatz_par_text( $b['par_herren'], $b['par_damen'] ),
		'hcp'     => $b['hcp'],
		'laengen' => array_map( fn( $tee ) => array( 'tee' => $tee, 'wert' => $b['laengen'][ $tee ] ), $tees ),
	);
	$summe = fn( string $label, array $liste ) => array(
		'label'   => $label,
		'par'     => golfplatz_par_text( array_sum( array_column( $liste, 'par_herren' ) ), array_sum( array_column( $liste, 'par_damen' ) ) ),
		'laengen' => array_map( fn( $tee ) => array( 'wert' => golfplatz_zahl( array_sum( array_map( fn( $b ) => $b['laengen'][ $tee ], $liste ) ) ) ), $tees ),
	);
	$vorne  = array_values( array_filter( $bahnen, fn( $b ) => $b['nr'] <= 9 ) );
	$hinten = array_values( array_filter( $bahnen, fn( $b ) => $b['nr'] > 9 ) );

	$rating = array();
	foreach ( GOLFPLATZ_ABSCHLAEGE as $tee => list( $name ) ) {
		$a = (array) ( $clubdaten[ 'abschlag_' . $tee ] ?? array() );
		if ( empty( $a['cr'] ) ) {
			continue;
		}
		$rating[] = array(
			'tee'    => $tee,
			'name'   => $name,
			'laenge' => golfplatz_zahl( array_sum( array_map( fn( $b ) => $b['laengen'][ $tee ], $bahnen ) ) ) . ' m',
			'fuer'   => $fuer( $tee ),
			'cr'     => golfplatz_zahl( $a['cr'], 1 ),
			'slope'  => (int) ( $a['slope'] ?? 0 ),
			'par'    => (int) ( $a['par'] ?? 0 ),
		);
	}

	return array(
		'hat_rating'    => (bool) $rating,
		'rating'        => $rating,
		'spielvorgaben' => golfplatz_spielvorgaben_etch( $clubdaten, $fuer ),
		// Zählkarte: Löcher mit Par (Herren/Damen) und HCP; Vorgabeschläge und Punkte rechnet das Skript zaehlkarte.js.
		'zaehlkarte'    => array(
			'bloecke' => array(
				array( 'key' => 'out', 'label' => 'Out', 'zeilen' => array_map( 'golfplatz_zaehlkarte_loch', $vorne ) ),
				array( 'key' => 'in', 'label' => 'In', 'zeilen' => array_map( 'golfplatz_zaehlkarte_loch', $hinten ) ),
			),
		),
		'scorekarte' => array(
			'kopf'   => array_map( fn( $tee ) => array( 'tee' => $tee, 'name' => GOLFPLATZ_ABSCHLAEGE[ $tee ][0], 'fuer' => $fuer( $tee ) ), $tees ),
			'bloecke' => array(
				array( 'zeilen' => array_map( $zeile, $vorne ), 'summe' => $summe( 'Out', $vorne ) ),
				array( 'zeilen' => array_map( $zeile, $hinten ), 'summe' => $summe( 'In', $hinten ) ),
			),
			'gesamt' => $summe( 'Gesamt', $bahnen ),
		),
	);
}

/** Bereitstellung für Etch: Bahndaten je Spielbahn und Platzdaten global. */
add_filter(
	'etch/dynamic_data/post',
	function ( $data, $post_id = 0 ) {
		if ( is_array( $data ) && $post_id && 'spielbahn' === get_post_type( $post_id ) ) {
			$bahn = golfplatz_bahn( (int) get_post_meta( $post_id, 'bahn_nummer', true ) );
			if ( $bahn ) {
				$data['golfplatz'] = golfplatz_bahn_etch( $bahn );
			}
		}
		return $data;
	},
	10,
	2
);
add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			$data['golfplatz']['platz'] = golfplatz_platz_etch();
		}
		return $data;
	}
);

/** Ist die aktuelle Seite das Vollbild-Birdiebook (/platz/birdiebook/)? */
function golfplatz_ist_birdiebook_seite(): bool {
	return is_page( "birdiebook" ) && ( $p = get_post_parent( get_queried_object_id() ) ) && "platz" === $p->post_name;
}

/**
 * Web-App-Manifest für das Vollbild-Birdiebook („Zum Home-Bildschirm“). Themenfarbe aus ACSS (color-primary),
 * Icons aus dem Website-Icon (Design › Website-Informationen), falls gesetzt.
 */
add_action(
	"init",
	function () {
		if ( ! isset( $_GET["golfplatz_manifest"] ) ) {
			return;
		}
		$farbe = "#2E6B4E"; // nur Rückfall ohne ACSS; maßgeblich ist color-primary
		if ( class_exists( '\Automatic_CSS\API' ) ) {
			$acss  = (array) \Automatic_CSS\API::get_settings();
			$farbe = preg_match( "/^#[0-9a-f]{6}$/i", (string) ( $acss["color-primary"] ?? "" ) ) ? $acss["color-primary"] : $farbe;
		}
		$club  = (array) get_option( "clubdaten", array() );
		$icons = array();
		foreach ( array( 192, 512 ) as $groesse ) {
			$url = get_site_icon_url( $groesse );
			if ( $url ) {
				$icons[] = array( "src" => $url, "sizes" => $groesse . "x" . $groesse, "type" => "image/png" );
			}
		}
		header( "Content-Type: application/manifest+json; charset=utf-8" );
		echo wp_json_encode(
			array(
				"name"             => "Birdiebook – " . ( $club["club_name"] ?? get_bloginfo( "name" ) ),
				"short_name"       => "Birdiebook",
				"lang"             => "de",
				"start_url"        => "/platz/birdiebook/",
				"scope"            => "/platz/birdiebook/",
				"display"          => "standalone",
				"orientation"      => "portrait",
				"background_color" => "#ffffff",
				"theme_color"      => $farbe,
				"icons"            => $icons,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
		exit;
	}
);

add_action(
	"wp_head",
	function () {
		if ( ! golfplatz_ist_birdiebook_seite() ) {
			return;
		}
		echo '<link rel="manifest" href="' . esc_url( home_url( '/?golfplatz_manifest=1' ) ) . '">' . "\n";
		echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="Birdiebook">' . "\n";
	},
	5
);

/**
 * Birdiebook-Skript. Läuft nur, wenn die Seite ein .birdiebook enthält.
 * - Abschlag-Wahl (Radiogruppe [data-tee-wahl]) setzt data-tee am .birdiebook; gemerkt in localStorage.
 * - Slider: EtchSliderPro (window.SplideComponent.ready). Fehlt dessen Plugin bzw. Skript, übernimmt ein natives
 *   Wisch-Karussell (CSS Scroll-Snap, Klasse is-native) mit derselben Bedienung.
 * - Direktlink #bahn-7 und die zuletzt angesehene Bahn; Vor/Zurück-Knöpfe und Nummernleiste; Ansage für Screenreader.
 */
add_action(
	'wp_footer',
	function () {
		?>
<script>
(function () {
	var book = document.querySelector('.birdiebook');
	if (!book) return;
	book.classList.add('has-js');
	var SPEICHER_TEE = 'golfplatz-abschlag', SPEICHER_BAHN = 'golfplatz-bahn';
	var lies = function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } };
	var merke = function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} };
	var ruhig = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* Abschlag-Wahl */
	var radios = book.querySelectorAll('[data-tee-wahl] input[type="radio"]');
	function setzeTee(tee) {
		book.setAttribute('data-tee', tee);
		radios.forEach(function (r) { r.checked = r.value === tee; });
	}
	var tee = lies(SPEICHER_TEE);
	if (tee && book.querySelector('[data-tee-wahl] input[value="' + tee + '"]')) setzeTee(tee);
	radios.forEach(function (r) {
		r.addEventListener('change', function () { if (r.checked) { setzeTee(r.value); merke(SPEICHER_TEE, r.value); } });
	});

	/* Slider */
	var main = book.querySelector('.splide[data-slider-role="main"]');
	if (!main) return;
	var liste = main.querySelector('.splide__list');
	var slides = [].slice.call(liste.children).filter(function (el) { return el.classList.contains('splide__slide'); });
	var zahl = slides.length;
	var prev = book.querySelector('[data-bahn-prev]'), next = book.querySelector('[data-bahn-next]');
	var zaehler = book.querySelector('[data-bahn-zaehler]');
	var nummern = book.querySelectorAll('[data-bahn-nr]');
	var ansage = book.querySelector('[data-bahn-ansage]');
	var leiste = book.querySelector('.birdiebook__numbers');

	function start() {
		var h = /^#bahn-(\d{1,2})$/.exec(location.hash);
		var n = h ? +h[1] : +(lies(SPEICHER_BAHN) || 1);
		return Math.min(Math.max(n || 1, 1), zahl);
	}
	function zeige(nr) {
		if (prev) { prev.disabled = nr <= 1; prev.querySelector('[data-text]').textContent = nr > 1 ? 'Bahn ' + (nr - 1) : 'Bahn 1'; }
		if (next) { next.querySelector('[data-text]').textContent = nr < zahl ? 'Bahn ' + (nr + 1) : 'Scorekarte'; }
		if (zaehler) zaehler.textContent = nr + ' / ' + zahl;
		nummern.forEach(function (b) {
			var aktiv = +b.getAttribute('data-bahn-nr') === nr;
			b.setAttribute('aria-current', aktiv ? 'true' : 'false');
			if (aktiv && leiste) leiste.scrollTo({ left: b.offsetLeft - leiste.clientWidth / 2 + b.offsetWidth / 2, behavior: ruhig ? 'auto' : 'smooth' });
		});
		slides.forEach(function (sl, i) { sl.toggleAttribute('inert', i !== nr - 1 && book.classList.contains('is-native')); });
		if (ansage) ansage.textContent = 'Bahn ' + nr + ' von ' + zahl;
		merke(SPEICHER_BAHN, String(nr));
		if (history.replaceState) history.replaceState(null, '', '#bahn-' + nr);
	}

	/* Natives Karussell mit der Splide-Schnittstelle, die dieses Skript braucht (index, go, on('moved')) */
	function nativ() {
		book.classList.add('is-native');
		var hoerer = [], warte = null;
		var api = {
			index: 0,
			go: function (ziel) {
				var i = ziel === '<' ? api.index - 1 : ziel === '>' ? api.index + 1 : ziel;
				i = Math.min(Math.max(i, 0), zahl - 1);
				liste.scrollTo({ left: slides[i].offsetLeft, behavior: ruhig ? 'auto' : 'smooth' });
			},
			on: function (ev, fn) { if (ev === 'moved') hoerer.push(fn); },
		};
		liste.addEventListener('scroll', function () {
			clearTimeout(warte);
			warte = setTimeout(function () {
				var i = Math.round(liste.scrollLeft / liste.clientWidth);
				if (i !== api.index) { api.index = i; hoerer.forEach(function (fn) { fn(i); }); }
			}, 120);
		}, { passive: true });
		return api;
	}

	function bereit(s) {
		if (book.classList.contains('is-ready')) return;
		book.classList.add('is-ready');
		// Deutsche Beschriftung (Splide setzt sonst englische Texte wie „1 of 18“)
		main.setAttribute('aria-roledescription', 'Karussell');
		slides.forEach(function (sl, i) {
			sl.setAttribute('role', 'group');
			sl.setAttribute('aria-roledescription', 'Bahn');
			sl.setAttribute('aria-label', 'Bahn ' + (i + 1) + ' von ' + zahl);
		});
		var nr = start();
		if (book.classList.contains('is-native')) {
			liste.scrollTo({ left: slides[nr - 1].offsetLeft, behavior: 'auto' });
			s.index = nr - 1;
		} else if (nr > 1) {
			s.go(nr - 1);
		}
		zeige(nr);
		s.on('moved', function (i) { zeige(i + 1); });
		if (prev) prev.addEventListener('click', function () { s.go('<'); });
		if (next) next.addEventListener('click', function () {
			if (s.index + 1 < zahl) return s.go('>');
			var sk = document.getElementById('scorekarte');
			if (sk) sk.scrollIntoView({ behavior: ruhig ? 'auto' : 'smooth' });
			else location.href = '/platz/#scorekarte';
		});
		nummern.forEach(function (b) { b.addEventListener('click', function () { s.go(+b.getAttribute('data-bahn-nr') - 1); }); });
		window.addEventListener('hashchange', function () { var n = start(); if (n - 1 !== s.index) s.go(n - 1); });
	}

	// EtchSliderPro, falls vorhanden; sonst nach kurzer Wartezeit das native Karussell.
	var versuche = 0;
	(function warteAufSlider() {
		if (window.SplideComponent && typeof window.SplideComponent.ready === 'function') {
			window.SplideComponent.ready('.birdiebook .splide[data-slider-role="main"]', bereit);
			return;
		}
		if (++versuche < 15) return setTimeout(warteAufSlider, 100);
		bereit(nativ());
	})();
})();
</script>
		<?php
	},
	60
);

/**
 * Spielvorgaben-Seite: Rechner ([data-calculator], Werte je Abschlag aus data-cr/-slope/-par der Ergebniszeilen)
 * und Reiter ([data-tabs], Pfeiltasten links/rechts). Läuft nur, wenn die Seite diese Bausteine enthält.
 */
add_action(
	'wp_footer',
	function () {
		?>
<script>
(function () {
	var rechner = document.querySelector('[data-calculator]');
	if (rechner) {
		var input = rechner.querySelector('[data-calculator-input]');
		var fehler = rechner.querySelector('[data-calculator-error]');
		var zeilen = rechner.querySelectorAll('[data-cr]');
		var rechne = function () {
			var roh = input.value.trim().replace(',', '.');
			var hi = roh.charAt(0) === '+' ? -parseFloat(roh.slice(1)) : parseFloat(roh);
			var gueltig = roh !== '' && !isNaN(hi) && hi >= -5 && hi <= 54;
			fehler.hidden = gueltig || roh === '';
			zeilen.forEach(function (z) {
				var zelle = z.querySelector('[data-sv]');
				if (!gueltig) { zelle.textContent = '–'; return; }
				var sv = Math.round(hi * (+z.getAttribute('data-slope') / 113) + (+z.getAttribute('data-cr') - +z.getAttribute('data-par')));
				zelle.textContent = sv < 0 ? '+' + -sv : String(sv);
			});
		};
		input.addEventListener('input', rechne);
	}

	document.querySelectorAll('[data-tabs]').forEach(function (tabs) {
		var reiter = Array.prototype.slice.call(tabs.querySelectorAll('[role="tab"]'));
		var aktiviere = function (tab) {
			reiter.forEach(function (t) {
				var an = t === tab;
				t.classList.toggle('tabs__tab--active', an);
				t.setAttribute('aria-selected', String(an));
				t.tabIndex = an ? 0 : -1;
				var panel = document.getElementById(t.getAttribute('aria-controls'));
				if (panel) panel.hidden = !an;
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
})();
</script>
		<?php
	},
	60
);

/** Zählkarte (Etch-Komponente „Zaehlkarte“): Skript aus golfplatz/zaehlkarte.js, prüft selbst, ob die Karte auf der Seite ist. */
add_action(
	'wp_enqueue_scripts',
	function () {
		$datei = GOLFPLATZ_DATEN . '/zaehlkarte.js';
		if ( is_readable( $datei ) ) {
			wp_enqueue_script( 'golfplatz-zaehlkarte', content_url( '/golfplatz/zaehlkarte.js' ), array(), (string) filemtime( $datei ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
	}
);
