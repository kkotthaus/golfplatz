<?php
/**
 * Plugin Name: Golfplatz – Club & Kontakt
 * Description: Stellt Personen (Beitragstyp „person“, gruppiert nach Personengruppe) und die Anfahrt Etch als Daten bereit: {options.golfplatz.personen.listen[key, personen[]]}, {options.golfplatz.anfahrt} und den Auftritt des Clubs (Logo, Region, clubeigene Texte) als {options.golfplatz.club}. Keine Shortcodes – das Markup bauen die Etch-Komponente „Personenkarten“ und die Seite /club/ (wordpress/etch/club.mjs).
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-club.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Listen für die Seite: Schlüssel → Personengruppen (Slugs). „jugend“ findet die Person über die Funktion,
 * solange es keine eigene Gruppe „Jugend“ gibt (siehe docs/seitenstruktur.md › Offene Punkte).
 */
define( 'GOLFPLATZ_PERSONEN_LISTEN', array(
	'vorstand'   => array( 'vorstand' ),
	'team'       => array( 'betreibergesellschaft', 'clubmanagement', 'sekretariat', 'service-proshop', 'greenkeeping' ),
	'captains'   => array( 'captains' ),
	'golfschule' => array( 'golfschule' ),
	'jugend'     => array( 'jugend' ),
) );

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

/** Bild-ID aus einem Meta-Box-Feld (single_image speichert die ID, ältere Stände ein Array). */
function golfplatz_club_bild( $wert, string $groesse = 'medium' ): string {
	$id = (int) ( is_array( $wert ) ? reset( $wert ) : $wert );
	return $id ? (string) wp_get_attachment_image_url( $id, $groesse ) : '';
}

/**
 * Auftritt des Clubs für Header, Footer und Seitentexte: {options.golfplatz.club.…}.
 * Alles, was von Club zu Club verschieden ist, steht in den Clubdaten – nicht im Markup.
 */
function golfplatz_club_etch(): array {
	$c      = (array) get_option( 'clubdaten', array() );
	$s      = fn( string $k ) => trim( (string) ( $c[ $k ] ?? '' ) );
	$logo   = golfplatz_club_bild( $c['club_logo'] ?? 0 );
	$hell   = golfplatz_club_bild( $c['club_logo_hell'] ?? 0 ) ?: $logo;
	$daten  = array(
		'logo'      => $logo,
		'hat_logo'  => '' !== $logo,
		'logo_hell' => $hell,
		'logoname'  => $s( 'club_logoname' ) ?: $s( 'club_name' ),
		'region'    => $s( 'club_region' ),
		'unterzeile' => implode( ' · ', array_filter( array( $s( 'club_ort' ), $s( 'club_region' ) ) ) ),
	);
	// Texte: HTML mit Absätzen (raw) und Schalter „hat_…“ für Bedingungen
	foreach ( array( 'platz_beschreibung', 'mitgliedschaft_kontakt', 'club_betreiber_hinweis' ) as $k ) {
		$daten[ $k ]          = wpautop( $s( $k ) );
		$daten[ 'hat_' . $k ] = '' !== $s( $k );
	}
	$daten['mitgliedschaft_lead'] = $s( 'mitgliedschaft_lead' ); // Seitenkopf: einzeiliger Text
	$daten['aufnahmeantrag_url']     = esc_url( $s( 'aufnahmeantrag_url' ) );
	$daten['hat_aufnahmeantrag_url'] = '' !== $daten['aufnahmeantrag_url'];
	return $daten;
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) && post_type_exists( 'person' ) ) {
			$data['golfplatz']['personen'] = golfplatz_personen_etch();
			$data['golfplatz']['anfahrt']  = golfplatz_anfahrt_etch();
		}
		if ( is_array( $data ) ) {
			$data['golfplatz']['club'] = golfplatz_club_etch();
		}
		return $data;
	}
);

/*
 * Clubdaten › Club: Auftritt und clubeigene Texte (Feldgruppe im Code, ergänzt die Builder-Gruppe „clubdaten-club“).
 * Damit steht im Markup der Seiten kein Clubname, Ort oder clubeigener Text mehr – ein neuer Club pflegt nur die Clubdaten.
 */
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$boxen[] = array(
			'id'             => 'clubdaten-auftritt',
			'title'          => 'Clubdaten · Auftritt & Texte',
			'settings_pages' => array( 'clubdaten' ),
			'tab'            => 'club',
			'fields'         => array(
				array( 'type' => 'heading', 'name' => 'Auftritt', 'desc' => 'Name im Logo und Region erscheinen im Kopf und Fuß jeder Seite. Ist ein Logo hinterlegt (oben), ersetzt es die Wortmarke.' ),
				array( 'id' => 'club_logoname', 'name' => 'Name im Logo', 'type' => 'text', 'columns' => 6, 'placeholder' => 'z. B. Golfclub Musterclub', 'desc' => 'Leer = Vereinsname.' ),
				array( 'id' => 'club_region', 'name' => 'Region', 'type' => 'text', 'columns' => 6, 'placeholder' => 'z. B. Musterregion', 'desc' => 'Steht hinter dem Ort unter dem Logo.' ),
				array( 'type' => 'heading', 'name' => 'Texte' ),
				array( 'id' => 'platz_beschreibung', 'name' => 'Platzbeschreibung', 'type' => 'textarea', 'rows' => 4, 'desc' => 'Seite „Platz & Bahnen“. Leerzeile = neuer Absatz.' ),
				array( 'id' => 'mitgliedschaft_lead', 'name' => 'Einleitung Mitgliedschaft', 'type' => 'textarea', 'rows' => 3, 'desc' => 'Seitenkopf der Seite „Mitgliedschaft“.' ),
				array( 'id' => 'mitgliedschaft_kontakt', 'name' => 'Ansprechpartner Aufnahme', 'type' => 'textarea', 'rows' => 2, 'placeholder' => 'z. B. Ansprechpartnerin ist Erika Musterfrau (Clubmanagement).' ),
				array( 'id' => 'aufnahmeantrag_url', 'name' => 'Aufnahmeantrag (PDF-Link)', 'type' => 'url', 'desc' => 'Leer = kein Knopf „Aufnahmeantrag als PDF“.' ),
				array( 'id' => 'club_betreiber_hinweis', 'name' => 'Hinweis Betreiber / Partner', 'type' => 'textarea', 'rows' => 2, 'desc' => 'Kleingedrucktes unter dem Team auf „Club & Kontakt“, z. B. Betreibergesellschaft des Platzes. Leer = ausgeblendet.' ),
			),
		);
		return $boxen;
	}
);
