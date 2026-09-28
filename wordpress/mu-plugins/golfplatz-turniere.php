<?php
/**
 * Plugin Name: Golfplatz – Turniere aus PC CADDIE
 * Description: Liest Turnierkalender und Ergebnisliste des Clubs aus PC CADDIE://online (öffentliche Seiten, Club-Kennung aus den Clubdaten) und legt je Turnier einen Eintrag „turnier“ an bzw. aktualisiert ihn: Datum, Name, Kategorien, Spielform, Anmeldeschluss, freie Plätze und Links zu Anmeldung, Ausschreibung und Ergebnissen. Stündlich per WP-Cron und von Hand unter Turniere → PC-CADDIE-Abgleich. Die Daten stehen Etch als {options.golfplatz.turniere} zur Verfügung; Ergebnislisten mit Namen werden nicht übernommen, nur verlinkt.
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/mu-plugins/golfplatz-turniere.php
 */

defined( 'ABSPATH' ) || exit;

const GOLFPLATZ_PCC_WEB = 'https://www.pccaddie.net/clubs/';
const GOLFPLATZ_PCC_LOG = 'golfplatz_pcc_log';

/** Kategorien von PC CADDIE (data-kat, mehrere möglich). Andere Buchstaben sind intern und werden ignoriert. */
const GOLFPLATZ_PCC_KATEGORIEN = array(
	'D' => 'Damen',
	'H' => 'Herren',
	'S' => 'Senioren',
	'J' => 'Jugend',
	'C' => 'Club',
	'M' => 'Mannschaften',
	'T' => 'Clubmeisterschaft',
);

function golfplatz_pcc_club(): string {
	$club = (array) get_option( 'clubdaten', array() );
	return preg_replace( '/\D/', '', (string) ( $club['pccaddie_code'] ?? '' ) );
}

/** Eine öffentliche Seite von PC CADDIE als DOMXPath, oder WP_Error. */
function golfplatz_pcc_seite( string $cat ) {
	$club = golfplatz_pcc_club();
	if ( '' === $club ) {
		return new WP_Error( 'golfplatz_pcc', 'Keine PC-CADDIE-Kennung in den Clubdaten (pccaddie_code).' );
	}
	$res = wp_remote_get( GOLFPLATZ_PCC_WEB . $club . '/app.php?cat=' . rawurlencode( $cat ), array( 'timeout' => 30, 'user-agent' => 'Golfplatz-Website/1.0 (' . home_url() . ')' ) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	if ( 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return new WP_Error( 'golfplatz_pcc', 'PC CADDIE antwortet mit HTTP ' . wp_remote_retrieve_response_code( $res ) . '.' );
	}
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="UTF-8">' . wp_remote_retrieve_body( $res ) );
	libxml_clear_errors();
	return new DOMXPath( $dom );
}

/** Text eines Knotens, Leerraum zusammengefasst. */
function golfplatz_pcc_text( ?DOMNode $n ): string {
	return $n ? trim( preg_replace( '/\s+/u', ' ', str_replace( "\u{a0}", ' ', $n->textContent ) ) ) : '';
}

/** Link absolut und ohne Sitzungskennung von PC CADDIE. */
function golfplatz_pcc_link( string $href ): string {
	if ( '' === $href || '#' === $href ) {
		return '';
	}
	$href = preg_replace( '/[&?]__Host-PHPSESSID=[^&]*/', '', html_entity_decode( $href ) );
	return str_starts_with( $href, 'http' ) ? $href : 'https://www.pccaddie.net' . $href;
}

/** Alle Turnierzeilen einer Seite (Kalender oder Ergebnisliste). */
function golfplatz_pcc_turniere_lesen( DOMXPath $x ): array {
	$liste = array();
	foreach ( $x->query( "//tr[contains(concat(' ', normalize-space(@class), ' '), ' pcco-xcal-list-item ')][@data-id]" ) as $tr ) {
		$id   = $tr->getAttribute( 'data-id' );
		$time = $x->query( './/time', $tr )->item( 0 );
		// „Do., 01.10.2026, 13:30 Uhr“ bzw. ohne Uhrzeit
		if ( ! $time || ! preg_match( '/(\d{2})\.(\d{2})\.(\d{4})(?:,\s*(\d{1,2}):(\d{2}))?/', $time->getAttribute( 'datetime' ), $d ) ) {
			continue;
		}
		$namen = array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) $x->query( ".//span[contains(@class,'tk-turnier')]", $tr )->item( 0 )?->textContent ) ) ) );
		$art   = golfplatz_pcc_text( $x->query( ".//td[contains(@class,'tk-body-name')]", $tr )->item( 0 ) );
		$link  = function ( string $bed ) use ( $x, $tr ) {
			$a = $x->query( './/a[' . $bed . ']', $tr )->item( 0 );
			return $a ? golfplatz_pcc_link( $a->getAttribute( 'href' ) ) : '';
		};
		$kat = array_values( array_intersect( preg_split( '/\s+/', trim( $tr->getAttribute( 'data-kat' ) ) ), array_keys( GOLFPLATZ_PCC_KATEGORIEN ) ) );
		preg_match( '/Anmeldeschluss:\s*(.+?Uhr)/u', $art, $schluss );
		preg_match( '/Teilnehmer maximal:\s*(\d+)/u', $art, $max );
		preg_match( '/Freie Plätze online:\s*(\d+)/u', $art, $frei );
		preg_match( '/Löcher:\s*(\d+)/u', $art, $loecher );
		preg_match( '/\|\s*((?:Einzel|Vierer|Vierball|Scramble|Texas|Mannschaft|Zweier|Chapman|Greensome|Team)[^|]*?)\s*(?:\||Handicap|Löcher|$)/u', $art, $form );
		$liste[ $id ] = array(
			'id'            => $id,
			'titel'         => $namen[0] ?? '',
			'untertitel'    => trim( implode( ' ', array_slice( $namen, 1 ) ) ),
			// Beginn als „Ortszeit als Unix-Zeit“ (wie alle Meta-Box-Datumsfelder im Projekt)
			'beginn'        => gmmktime( (int) ( $d[4] ?? 0 ), (int) ( $d[5] ?? 0 ), 0, (int) $d[2], (int) $d[1], (int) $d[3] ),
			'hat_uhrzeit'   => isset( $d[4] ) && '' !== $d[4],
			'kategorien'    => implode( ' ', $kat ),
			'spielform'     => trim( $form[1] ?? '' ),
			'loecher'       => (int) ( $loecher[1] ?? 0 ),
			'vorgabe'       => str_contains( $art, 'Handicap-relevant' ),
			'gaeste'        => (bool) $x->query( ".//i[contains(@class,'tk-icon-guest') and contains(@class,'fa-check')]", $tr )->length,
			'schluss'       => html_entity_decode( trim( $schluss[1] ?? '' ) ),
			'max'           => (int) ( $max[1] ?? 0 ),
			'frei'          => isset( $frei[1] ) ? (int) $frei[1] : null,
			'anmeldung'     => $link( "contains(@class,'href-register')" ),
			'details'       => $link( "contains(@class,'href-detail')" ),
			'ausschreibung' => $link( "normalize-space(.)='Ausschreibung' and @href" ),
			'ergebnisse'    => $link( "contains(@href,'sub=resultlist')" ),
			'startliste'    => $link( "contains(@href,'sub=startlist')" ),
		);
	}
	return $liste;
}

/** Abgleich: Kalender (kommende) und Ergebnisliste (gespielte) lesen, Einträge anlegen/aktualisieren. */
function golfplatz_pcc_abgleich(): array {
	$log = array( 'zeit' => time(), 'neu' => 0, 'aktualisiert' => 0, 'unveraendert' => 0, 'abgesagt' => 0, 'fehler' => array() );
	$kal = golfplatz_pcc_seite( 'ts_calendar' );
	$erg = golfplatz_pcc_seite( 'ts_resultlist' );
	if ( is_wp_error( $kal ) || is_wp_error( $erg ) ) {
		$log['fehler'][] = ( is_wp_error( $kal ) ? $kal : $erg )->get_error_message();
		return golfplatz_pcc_log( $log );
	}
	$kommend  = golfplatz_pcc_turniere_lesen( $kal );
	$gespielt = golfplatz_pcc_turniere_lesen( $erg );
	if ( ! $kommend && ! $gespielt ) {
		$log['fehler'][] = 'Keine Turniere gelesen – hat PC CADDIE den Aufbau der Seiten geändert?';
		return golfplatz_pcc_log( $log );
	}
	// Ein Turnier kann in beiden Listen stehen (heute); die Kalenderzeile hat die aktuelleren Plätze, die Ergebnisliste den Ergebnislink
	$alle = $gespielt;
	foreach ( $kommend as $id => $t ) {
		$alle[ $id ] = isset( $alle[ $id ] ) ? array_merge( $alle[ $id ], array_filter( $t, fn( $v ) => '' !== $v && null !== $v ) ) : $t;
	}

	$vorhanden = array();
	foreach ( get_posts( array( 'post_type' => 'turnier', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
		$vorhanden[ (string) get_post_meta( $pid, 'turnier_extern_id', true ) ] = $pid;
	}
	foreach ( $alle as $id => $t ) {
		$meta = array(
			'turnier_extern_id'          => $id,
			'turnier_beginn'             => $t['beginn'],
			'turnier_hat_uhrzeit'        => $t['hat_uhrzeit'] ? 1 : 0,
			'turnier_kategorien'         => $t['kategorien'],
			'turnier_untertitel'         => $t['untertitel'],
			'turnier_spielform'          => $t['spielform'],
			'turnier_loecher'            => $t['loecher'] ?: '',
			'turnier_vorgabewirksam'     => $t['vorgabe'] ? 1 : 0,
			'turnier_gaeste'             => $t['gaeste'] ? 1 : 0,
			'turnier_anmeldeschluss'     => $t['schluss'],
			'turnier_teilnehmer_max'     => $t['max'] ?: '',
			'turnier_plaetze_frei'       => $t['frei'] ?? '',
			'turnier_link_anmeldung'     => $t['anmeldung'],
			'turnier_link_details'       => $t['details'],
			'turnier_link_ausschreibung' => $t['ausschreibung'],
			'turnier_link_ergebnisse'    => $t['ergebnisse'],
			'turnier_link_startliste'    => $t['startliste'],
		);
		$pid = $vorhanden[ (string) $id ] ?? 0;
		if ( $pid ) {
			$alt = array_map( fn( $k ) => (string) get_post_meta( $pid, $k, true ), array_keys( $meta ) );
			if ( array_combine( array_keys( $meta ), $alt ) == array_map( 'strval', $meta ) && html_entity_decode( get_post_field( 'post_title', $pid ) ) === $t['titel'] /* Rohtitel: get_the_title() setzt typografische Striche */ && 'publish' === get_post_status( $pid ) ) {
				++$log['unveraendert'];
				continue;
			}
			wp_update_post( array( 'ID' => $pid, 'post_title' => $t['titel'], 'post_status' => 'publish' ) );
			++$log['aktualisiert'];
		} else {
			$pid = wp_insert_post( array( 'post_type' => 'turnier', 'post_status' => 'publish', 'post_title' => $t['titel'], 'post_name' => 'pcc-' . $id ) );
			++$log['neu'];
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $pid, $k, $v );
		}
	}
	// Kommende Turniere, die nicht mehr im Kalender stehen, sind abgesagt (nur wenn der Kalender gelesen werden konnte)
	$heute = gmmktime( 0, 0, 0, (int) wp_date( 'n' ), (int) wp_date( 'j' ), (int) wp_date( 'Y' ) );
	if ( $kommend ) {
		foreach ( $vorhanden as $id => $pid ) {
			if ( ! isset( $alle[ $id ] ) && (int) get_post_meta( $pid, 'turnier_beginn', true ) >= $heute && 'publish' === get_post_status( $pid ) ) {
				wp_trash_post( $pid );
				++$log['abgesagt'];
			}
		}
	}
	return golfplatz_pcc_log( $log );
}

function golfplatz_pcc_log( array $log ): array {
	$alle = (array) get_option( GOLFPLATZ_PCC_LOG, array() );
	array_unshift( $alle, $log );
	update_option( GOLFPLATZ_PCC_LOG, array_slice( $alle, 0, 30 ), false );
	return $log;
}

function golfplatz_pcc_log_text( array $l ): string {
	return wp_date( 'd.m.Y H:i', $l['zeit'] ) . ': ' . $l['neu'] . ' neu, ' . $l['aktualisiert'] . ' aktualisiert, ' . $l['unveraendert'] . ' unverändert' . ( $l['abgesagt'] ? ', ' . $l['abgesagt'] . ' abgesagt (Papierkorb)' : '' ) . ( $l['fehler'] ? '; Probleme: ' . implode( ' ', $l['fehler'] ) : '' );
}

/* ---------- Daten für Etch: {options.golfplatz.turniere} ---------- */

/** Ein Turnier für die Ausgabe. */
function golfplatz_turnier_zeile( WP_Post $p, int $heute ): array {
	$m      = fn( string $k ) => get_post_meta( $p->ID, $k, true );
	$ts     = (int) $m( 'turnier_beginn' );
	$tz     = new DateTimeZone( 'UTC' ); // Ortszeit als Unix-Zeit
	$kat    = array_filter( explode( ' ', (string) $m( 'turnier_kategorien' ) ) );
	$max    = (int) $m( 'turnier_teilnehmer_max' );
	$frei   = $m( 'turnier_plaetze_frei' );
	$plaetze = '';
	if ( '' !== (string) $frei && $max ) {
		$plaetze = 0 === (int) $frei ? 'ausgebucht' : (int) $frei . ' von ' . $max . ' Plätzen frei';
	}
	$infos = array_filter(
		array(
			$m( 'turnier_hat_uhrzeit' ) ? wp_date( 'H:i', $ts, $tz ) . ' Uhr' : '',
			(string) $m( 'turnier_spielform' ),
			(int) $m( 'turnier_loecher' ) ? (int) $m( 'turnier_loecher' ) . ' Löcher' : '',
			$m( 'turnier_vorgabewirksam' ) ? 'handicaprelevant' : '',
			$m( 'turnier_gaeste' ) ? 'offen für Gäste' : '',
		)
	);
	return array(
		'titel'             => html_entity_decode( get_the_title( $p ) ),
		'untertitel'        => (string) $m( 'turnier_untertitel' ),
		'datum_iso'         => wp_date( 'Y-m-d', $ts, $tz ),
		'tag'               => wp_date( 'j', $ts, $tz ),
		'monat'             => wp_date( 'M', $ts, $tz ),
		'wochentag'         => wp_date( 'D', $ts, $tz ),
		'datum_lang'        => wp_date( 'l, j. F Y', $ts, $tz ),
		'jahr'              => (int) wp_date( 'Y', $ts, $tz ),
		'monat_key'         => wp_date( 'Y-m', $ts, $tz ),
		'monat_name'        => wp_date( 'F Y', $ts, $tz ),
		'heute'             => $ts >= $heute && $ts < $heute + DAY_IN_SECONDS,
		'kommend'           => $ts >= $heute,
		'infos'             => implode( ' · ', $infos ),
		'kategorien'        => array_map( fn( $k ) => array( 'name' => GOLFPLATZ_PCC_KATEGORIEN[ $k ] ?? $k, 'key' => strtolower( $k ) ), $kat ),
		'kategorie_keys'    => $kat,
		'hat_kategorien'    => (bool) $kat,
		'anmeldeschluss'    => (string) $m( 'turnier_anmeldeschluss' ),
		'plaetze'           => $plaetze,
		'ausgebucht'        => 'ausgebucht' === $plaetze,
		'link_anmeldung'    => (string) $m( 'turnier_link_anmeldung' ),
		'hat_anmeldung'     => '' !== (string) $m( 'turnier_link_anmeldung' ) && 'ausgebucht' !== $plaetze,
		'link_ausschreibung' => (string) $m( 'turnier_link_ausschreibung' ),
		'hat_ausschreibung' => '' !== (string) $m( 'turnier_link_ausschreibung' ),
		'link_details'      => (string) $m( 'turnier_link_details' ),
		'hat_details'       => '' !== (string) $m( 'turnier_link_details' ),
		'link_ergebnisse'   => (string) $m( 'turnier_link_ergebnisse' ),
		'hat_ergebnisse'    => '' !== (string) $m( 'turnier_link_ergebnisse' ),
	);
}

/**
 * Turnierkalender (kommende, nach Monat gruppiert) und gespielte Turniere eines Jahres.
 * Filter per URL: ?kategorie=S (Kalender), ?jahr=2025 (Ergebnisse) – auf /turniere/.
 */
function golfplatz_turniere_etch(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$heute = gmmktime( 0, 0, 0, (int) wp_date( 'n' ), (int) wp_date( 'j' ), (int) wp_date( 'Y' ) );
	$alle  = array();
	foreach ( get_posts( array( 'post_type' => 'turnier', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => 'turnier_beginn', 'orderby' => 'meta_value_num', 'order' => 'ASC', 'no_found_rows' => true ) ) as $p ) {
		$alle[] = golfplatz_turnier_zeile( $p, $heute );
	}
	$seite = get_permalink( get_page_by_path( 'turniere' ) ) ?: home_url( '/turniere/' );
	// phpcs:disable WordPress.Security.NonceVerification
	$kat  = isset( $_GET['kategorie'] ) ? strtoupper( sanitize_key( $_GET['kategorie'] ) ) : '';
	$kat  = isset( GOLFPLATZ_PCC_KATEGORIEN[ $kat ] ) ? $kat : '';
	$jahr = isset( $_GET['jahr'] ) ? (int) $_GET['jahr'] : 0;
	// phpcs:enable

	$kommend = array_values( array_filter( $alle, fn( $t ) => $t['kommend'] && ( ! $kat || in_array( $kat, $t['kategorie_keys'], true ) ) ) );
	$monate  = array();
	foreach ( $kommend as $t ) {
		$monate[ $t['monat_key'] ]['name']       = $t['monat_name'];
		$monate[ $t['monat_key'] ]['turniere'][] = $t;
	}
	$gespielt = array_reverse( array_values( array_filter( $alle, fn( $t ) => ! $t['kommend'] ) ) );
	$jahre    = array_values( array_unique( array_column( $gespielt, 'jahr' ) ) );
	$jahr     = in_array( $jahr, $jahre, true ) ? $jahr : ( $jahre[0] ?? (int) wp_date( 'Y' ) );

	$filter = array( array( 'name' => 'Alle', 'link' => $seite . '#turnierkalender', 'aktiv' => $kat ? '' : 'aktiv' ) );
	foreach ( array( 'D', 'H', 'S', 'J', 'C' ) as $k ) {
		$filter[] = array( 'name' => GOLFPLATZ_PCC_KATEGORIEN[ $k ], 'link' => add_query_arg( 'kategorie', strtolower( $k ), $seite ) . '#turnierkalender', 'aktiv' => $k === $kat ? 'aktiv' : '' );
	}
	$heutige = array_values( array_filter( $alle, fn( $t ) => $t['heute'] ) );
	$cache   = array(
		'monate'        => array_values( $monate ),
		'hat_kommende'  => (bool) $kommend,
		'filter'        => $filter,
		'filter_name'   => $kat ? GOLFPLATZ_PCC_KATEGORIEN[ $kat ] : '',
		'gespielt'      => array_values( array_filter( $gespielt, fn( $t ) => $t['jahr'] === $jahr ) ),
		'hat_gespielt'  => (bool) $gespielt,
		'jahr'          => (string) $jahr,
		'jahre'         => array_map( fn( $j ) => array( 'jahr' => (string) $j, 'link' => add_query_arg( 'jahr', $j, $seite ) . '#turnierergebnisse', 'aktiv' => $j === $jahr ? 'aktiv' : '' ), $jahre ),
		'heute'         => $heutige,
		'hat_heute'     => (bool) $heutige,
		'naechste'      => array_slice( array_values( array_filter( $alle, fn( $t ) => $t['kommend'] ) ), 0, 4 ),
		'pcc_kalender'  => GOLFPLATZ_PCC_WEB . golfplatz_pcc_club() . '/app.php?cat=ts_calendar',
	);
	return $cache;
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) && post_type_exists( 'turnier' ) ) {
			$data['golfplatz']['turniere'] = golfplatz_turniere_etch();
		}
		return $data;
	}
);

/* ---------- Abgleich: stündlich und von Hand ---------- */

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'golfplatz_pcc_sync' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'golfplatz_pcc_sync' );
		}
	}
);
add_action( 'golfplatz_pcc_sync', 'golfplatz_pcc_abgleich' );

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=turnier', 'PC-CADDIE-Abgleich', 'PC-CADDIE-Abgleich', 'edit_others_posts', 'golfplatz-pcc', 'golfplatz_pcc_seite_admin' );
	}
);

function golfplatz_pcc_seite_admin(): void {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$ergebnis = null;
	if ( isset( $_POST['golfplatz_pcc'] ) && check_admin_referer( 'golfplatz_pcc' ) ) {
		$ergebnis = golfplatz_pcc_abgleich();
	}
	echo '<div class="wrap"><h1>PC-CADDIE-Abgleich</h1><p>Turnierkalender und Ergebnisliste kommen aus PC CADDIE (Club-Kennung ' . esc_html( golfplatz_pcc_club() ?: '– fehlt in den Clubdaten –' ) . '). Der Abgleich läuft stündlich. Turniere bitte nur in PC CADDIE ändern; Anmeldung und Ergebnislisten bleiben dort.</p>';
	if ( $ergebnis ) {
		echo '<div class="notice ' . ( $ergebnis['fehler'] ? 'notice-warning' : 'notice-success' ) . '"><p>' . esc_html( golfplatz_pcc_log_text( $ergebnis ) ) . '</p></div>';
	}
	echo '<form method="post">';
	wp_nonce_field( 'golfplatz_pcc' );
	echo '<input type="hidden" name="golfplatz_pcc" value="1">';
	submit_button( 'Jetzt abgleichen' );
	echo '</form><h2>Letzte Abgleiche</h2><ul>';
	foreach ( array_slice( (array) get_option( GOLFPLATZ_PCC_LOG, array() ), 0, 15 ) as $l ) {
		echo '<li>' . esc_html( golfplatz_pcc_log_text( $l ) ) . '</li>';
	}
	echo '</ul></div>';
}
