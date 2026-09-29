<?php
/**
 * Plugin Name: Golfplatz – Golfschule
 * Description: Stellt die Kurse der Golfschule (Beitragstyp „kurs“) Etch als Daten bereit: {options.golfplatz.kurse.liste[]} für die Seite /golfschule/ und je Kurs {this.golfplatz.kurs} für das Template single-kurs. Preis als Text („35 €“, „auf Anfrage“, „kostenlos“), nur künftige Termine; Kurse, deren Termine alle vorbei sind, fallen aus der Liste. Keine Shortcodes – das Markup baut die Etch-Komponente „Kurskarten“ (wordpress/etch/golfschule.mjs).
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-golfschule.php
 */

defined( 'ABSPATH' ) || exit;

/** Datum aus Meta Box: „2026-10-10“ oder „10.10.2026“ (Anzeigeformat in Gruppen) → „2026-10-10“, sonst null. */
function golfplatz_kurs_datum( $wert ): ?string {
	$wert = trim( (string) $wert );
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $wert ) ) {
		return $wert;
	}
	if ( preg_match( '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $wert, $m ) ) {
		return sprintf( '%04d-%02d-%02d', $m[3], $m[2], $m[1] );
	}
	return null;
}

/** Ein Kurs für die Ausgabe; termine nur ab heute. abgelaufen = hatte Termine, aber keiner liegt mehr in der Zukunft. */
function golfplatz_kurs_etch( WP_Post $p ): array {
	$m      = fn( string $k ) => get_post_meta( $p->ID, $k, true );
	$heute  = wp_date( 'Y-m-d' );
	$tz     = new DateTimeZone( 'UTC' );
	$typen  = array( 'schnupperkurs' => 'Schnupperkurs', 'platzreife' => 'Platzreife', 'training' => 'Training', 'jugend' => 'Kinder & Jugend', 'firmen' => 'Firmen & Gruppen' );
	$preis  = $m( 'kurs_preis' );
	$termine = array();
	$hatte   = false;
	foreach ( (array) $m( 'kurs_termine' ) as $t ) {
		$von = golfplatz_kurs_datum( $t['von'] ?? '' );
		if ( ! $von ) {
			continue;
		}
		$hatte = true;
		$bis   = golfplatz_kurs_datum( $t['bis'] ?? '' ) ?: $von;
		if ( $bis < $heute ) {
			continue;
		}
		$tag       = fn( string $iso ) => wp_date( 'D, j.n.', strtotime( $iso . ' 00:00:00 UTC' ), $tz );
		$termine[] = array(
			'iso'  => $von,
			'text' => ( $bis !== $von ? $tag( $von ) . ' – ' . $tag( $bis ) : $tag( $von ) ) . ( trim( (string) ( $t['uhrzeit'] ?? '' ) ) ? ', ' . trim( $t['uhrzeit'] ) : '' ),
		);
	}
	usort( $termine, fn( $a, $b ) => strcmp( $a['iso'], $b['iso'] ) );
	$trainer = (int) $m( 'kurs_trainer' );
	$max     = (int) $m( 'kurs_max_teilnehmer' );
	$dauer   = trim( (string) $m( 'kurs_dauer' ) );
	return array(
		'titel'        => html_entity_decode( get_the_title( $p ) ),
		'link'         => get_permalink( $p ),
		'typ'          => $typen[ (string) $m( 'kurs_typ' ) ] ?? '',
		'text'         => trim( wp_strip_all_tags( strip_shortcodes( $p->post_content ) ) ),
		'preis'        => '' === (string) $preis ? 'auf Anfrage' : ( 0.0 === (float) $preis ? 'kostenlos' : number_format_i18n( (float) $preis, floor( (float) $preis ) == (float) $preis ? 0 : 2 ) . ' €' ),
		'dauer'        => $dauer,
		'hat_dauer'    => '' !== $dauer,
		'max'          => $max ? 'max. ' . $max : '',
		'hat_max'      => (bool) $max,
		'termine'      => implode( ' · ', array_column( $termine, 'text' ) ),
		'hat_termine'  => (bool) $termine,
		'abgelaufen'   => $hatte && ! $termine,
		'trainer'      => $trainer ? html_entity_decode( get_the_title( $trainer ) ) : '',
		'hat_trainer'  => (bool) $trainer,
		'anmeldung'    => trim( (string) $m( 'kurs_anmeldung' ) ),
		'hat_anmeldung' => '' !== trim( (string) $m( 'kurs_anmeldung' ) ),
	);
}

function golfplatz_kurse_etch(): array {
	$liste = array();
	foreach ( get_posts( array( 'post_type' => 'kurs', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $p ) {
		$k = golfplatz_kurs_etch( $p );
		if ( ! $k['abgelaufen'] ) {
			$liste[] = $k;
		}
	}
	return array( 'liste' => $liste, 'hat_kurse' => (bool) $liste );
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) && post_type_exists( 'kurs' ) ) {
			$data['golfplatz']['kurse'] = golfplatz_kurse_etch();
		}
		return $data;
	}
);

add_filter(
	'etch/dynamic_data/post',
	function ( $data, $post_id = 0 ) {
		if ( is_array( $data ) && $post_id && 'kurs' === get_post_type( $post_id ) ) {
			$data['golfplatz']['kurs'] = golfplatz_kurs_etch( get_post( $post_id ) );
		}
		return $data;
	},
	10,
	2
);
