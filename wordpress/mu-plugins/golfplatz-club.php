<?php
/**
 * Plugin Name: Golfplatz – Club & Kontakt
 * Description: Stellt Personen (Beitragstyp „person“, gruppiert nach Personengruppe) und die Anfahrt Etch als Daten bereit: {options.golfplatz.personen.listen[key, personen[]]} und {options.golfplatz.anfahrt}. Keine Shortcodes – das Markup bauen die Etch-Komponente „Personenkarten“ und die Seite /club/ (wordpress/etch/club.mjs).
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/mu-plugins/golfplatz-club.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Listen für die Seite: Schlüssel → Personengruppen (Slugs). „jugend“ findet die Person über die Funktion,
 * solange es keine eigene Gruppe „Jugend“ gibt (siehe docs/seitenstruktur.md › Offene Punkte).
 */
const GOLFPLATZ_PERSONEN_LISTEN = array(
	'vorstand'   => array( 'vorstand' ),
	'team'       => array( 'betreibergesellschaft', 'clubmanagement', 'sekretariat', 'service-proshop', 'greenkeeping' ),
	'captains'   => array( 'captains' ),
	'golfschule' => array( 'golfschule' ),
	'jugend'     => array( 'jugend' ),
);

/** Eine Person für die Ausgabe. */
function golfplatz_person_etch( WP_Post $p ): array {
	$m        = fn( string $k ) => trim( (string) get_post_meta( $p->ID, $k, true ) );
	$name     = html_entity_decode( get_the_title( $p ) );
	$teile    = array_values( array_filter( explode( ' ', preg_replace( '/^(Dr\.|Prof\.)\s+/u', '', $name ) ) ) );
	$telefon  = $m( 'person_telefon' );
	$bild     = get_the_post_thumbnail_url( $p, 'thumbnail' );
	return array(
		'name'        => $name,
		'funktion'    => $m( 'person_funktion' ),
		'text'        => $m( 'person_text' ),
		'initialen'   => mb_strtoupper( mb_substr( $teile[0] ?? '', 0, 1 ) . mb_substr( $teile[ count( $teile ) - 1 ] ?? '', 0, 1 ) ),
		'telefon'     => $telefon,
		'tel_href'    => 'tel:' . preg_replace( '/[^\d+]/', '', $telefon ),
		'email'       => $m( 'person_email' ),
		'bild'        => $bild ?: '',
		'hat_bild'    => (bool) $bild,
	);
}

function golfplatz_personen_etch(): array {
	$alle = get_posts(
		array(
			'post_type'      => 'person',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'no_found_rows'  => true,
		)
	);
	$listen = array();
	foreach ( GOLFPLATZ_PERSONEN_LISTEN as $key => $gruppen ) {
		$personen = array_filter( $alle, fn( $p ) => has_term( $gruppen, 'personengruppe', $p ) );
		if ( 'jugend' === $key && ! $personen ) {
			$personen = array_filter( $alle, fn( $p ) => false !== mb_stripos( (string) get_post_meta( $p->ID, 'person_funktion', true ), 'jugend' ) );
		}
		$listen[] = array(
			'key'      => $key,
			'personen' => array_values( array_map( 'golfplatz_person_etch', $personen ) ),
			'anzahl'   => count( $personen ),
		);
	}
	return array( 'listen' => $listen );
}

/** Anfahrt aus den Clubdaten; Routenlink ohne eingebettete Karte (kein externer Dienst ohne Einwilligung). */
function golfplatz_anfahrt_etch(): array {
	$c       = (array) get_option( 'clubdaten', array() );
	$adresse = trim( ( $c['club_strasse'] ?? '' ) . ', ' . ( $c['club_plz'] ?? '' ) . ' ' . ( $c['club_ort'] ?? '' ), ' ,' );
	$route   = trim( (string) ( $c['club_routenlink'] ?? '' ) ) ?: 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( ( $c['club_name'] ?? '' ) . ', ' . $adresse );
	$karte   = ! empty( $c['club_karte'] ) ? wp_get_attachment_image_url( (int) ( is_array( $c['club_karte'] ) ? reset( $c['club_karte'] ) : $c['club_karte'] ), 'large' ) : '';
	$auto    = trim( (string) ( $c['club_anfahrt_auto'] ?? '' ) );
	$oepnv   = trim( (string) ( $c['club_anfahrt_oepnv'] ?? '' ) );
	return array(
		'route'     => $route,
		'auto'      => wpautop( $auto ),
		'hat_auto'  => '' !== $auto,
		'oepnv'     => wpautop( $oepnv ),
		'hat_oepnv' => '' !== $oepnv,
		'karte'     => $karte ?: '',
		'hat_karte' => (bool) $karte,
	);
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) && post_type_exists( 'person' ) ) {
			$data['golfplatz']['personen'] = golfplatz_personen_etch();
			$data['golfplatz']['anfahrt']  = golfplatz_anfahrt_etch();
		}
		return $data;
	}
);
