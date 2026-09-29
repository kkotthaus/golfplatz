<?php
/**
 * Plugin Name: Golfplatz – Turniere aus PC CADDIE
 * Description: Liest Turnierkalender und Ergebnisliste des Clubs aus PC CADDIE://online (öffentliche Seiten, Club-Kennung aus den Clubdaten) und legt je Turnier einen Eintrag „turnier“ an bzw. aktualisiert ihn: Datum, Name, Kategorien, Spielform, Anmeldeschluss, freie Plätze und Links zu Anmeldung, Ausschreibung und Ergebnissen. Stündlich per WP-Cron und von Hand unter Turniere → PC-CADDIE-Abgleich. Die Daten stehen Etch als {options.golfplatz.turniere} zur Verfügung; Ergebnislisten mit Namen werden nicht übernommen, nur verlinkt.
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-turniere.php
 */

defined( 'ABSPATH' ) || exit;

define( 'GOLFPLATZ_PCC_WEB', 'https://www.pccaddie.net/clubs/' );
define( 'GOLFPLATZ_PCC_LOG', 'golfplatz_pcc_log' );

/** Kategorien von PC CADDIE (data-kat, mehrere möglich). Andere Buchstaben sind intern und werden ignoriert. */
define( 'GOLFPLATZ_PCC_KATEGORIEN', array(
	'D' => 'Damen',
	'H' => 'Herren',
	'S' => 'Senioren',
	'J' => 'Jugend',
	'C' => 'Club',
	'M' => 'Mannschaften',
	'T' => 'Clubmeisterschaft',
) );

function golfplatz_pcc_club(): string {
	$club = (array) get_option( 'clubdaten', array() );
	return preg_replace( '/\D/', '', (string) ( $club['pccaddie_code'] ?? '' ) );
}

/**
 * Eigener Club und GOLFHOCHZEHN-Partnerclubs (Clubdaten → Gäste & Systeme → Partnerclubs).
 * Je Club: code (PC CADDIE, leer = nicht abgleichbar), name, kurz, website, kalender (Link ohne PC CADDIE), eigen.
 */
function golfplatz_pcc_clubs(): array {
	$daten = (array) get_option( 'clubdaten', array() );
	$clubs = array(
		array(
			'code'     => golfplatz_pcc_club(),
			'name'     => (string) ( $daten['club_name'] ?? '' ),
			'kurz'     => (string) ( $daten['club_kurzname'] ?? '' ) ?: 'Heimatclub',
			'website'  => home_url( '/' ),
			'kalender' => '',
			'eigen'    => true,
		),
	);
	foreach ( (array) ( $daten['partnerclubs'] ?? array() ) as $p ) {
		if ( ! is_array( $p ) || '' === trim( (string) ( $p['name'] ?? '' ) ) ) {
			continue;
		}
		$clubs[] = array(
			'code'     => preg_replace( '/\D/', '', (string) ( $p['pccaddie_code'] ?? '' ) ),
			'name'     => trim( (string) $p['name'] ),
			'kurz'     => trim( (string) ( $p['kurzname'] ?? '' ) ) ?: trim( (string) $p['name'] ),
			'website'  => (string) ( $p['website'] ?? '' ),
			'kalender' => (string) ( $p['kalender_link'] ?? '' ),
			'eigen'    => false,
		);
	}
	return $clubs;
}

/** Eine öffentliche Seite von PC CADDIE als DOMXPath, oder WP_Error. $club leer = eigener Club. */
function golfplatz_pcc_seite( string $cat, string $club = '' ) {
	$club = $club ?: golfplatz_pcc_club();
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
		// Kategorien vergibt jeder Club anders (Dreibäumen: D, H, S …; Schloss Haag: DAM, HER, SEN; Varmert: keine).
		// Deshalb Kürzel vereinheitlichen und zusätzlich am Namen erkennen.
		$codes = preg_split( '/\s+/', strtoupper( trim( $tr->getAttribute( 'data-kat' ) ) ) );
		$codes = array_map( fn( $k ) => array( 'DAM' => 'D', 'HER' => 'H', 'SEN' => 'S', 'JUG' => 'J' )[ $k ] ?? $k, $codes );
		$name  = implode( ' ', $namen );
		foreach ( array( 'D' => '/damen|ladies/iu', 'H' => '/herren|\bmen\b/iu', 'S' => '/senior|\bAK\s?(50|55|60|65|70)\b/iu', 'J' => '/jugend|junior|kinder|\bU\s?1[0-8]\b/iu' ) as $k => $muster ) {
			if ( preg_match( $muster, $name ) ) {
				$codes[] = $k;
			}
		}
		$kat = array_values( array_unique( array_intersect( $codes, array_keys( GOLFPLATZ_PCC_KATEGORIEN ) ) ) );
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

/**
 * Kommende Turniere eines Clubs. Manche Clubs haben den Standardkalender abgeschaltet und zeigen nur die Ansicht
 * „ts_calendar_turn_only“ (z. B. Velbert, Marienfeld) – dann diese lesen. Gibt array oder WP_Error zurück.
 */
function golfplatz_pcc_kalender( string $club ) {
	$letzter = null;
	foreach ( array( 'ts_calendar', 'ts_calendar_turn_only' ) as $cat ) {
		$x = golfplatz_pcc_seite( $cat, $club );
		if ( ! is_wp_error( $x ) ) {
			return golfplatz_pcc_turniere_lesen( $x );
		}
		$letzter = $x;
	}
	return $letzter;
}

/**
 * Abgleich: eigener Club (Kalender und Ergebnisliste) und Partnerclubs (nur Kalender).
 * Je Turnier ein Eintrag „turnier“, Schlüssel = Club + PC-CADDIE-Kennung des Turniers.
 */
function golfplatz_pcc_abgleich(): array {
	$log   = array( 'zeit' => time(), 'neu' => 0, 'aktualisiert' => 0, 'unveraendert' => 0, 'abgesagt' => 0, 'clubs' => 0, 'fehler' => array() );
	$heute = gmmktime( 0, 0, 0, (int) wp_date( 'n' ), (int) wp_date( 'j' ), (int) wp_date( 'Y' ) );
	$eigen = golfplatz_pcc_club();

	// Vorhandene Einträge je Club; ältere Einträge ohne Club gehören zum eigenen Club
	$vorhanden = array();
	foreach ( get_posts( array( 'post_type' => 'turnier', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
		$club = (string) get_post_meta( $pid, 'turnier_club', true ) ?: $eigen;
		$vorhanden[ $club ][ (string) get_post_meta( $pid, 'turnier_extern_id', true ) ] = $pid;
	}

	foreach ( golfplatz_pcc_clubs() as $c ) {
		if ( '' === $c['code'] ) {
			continue; // ohne PC CADDIE (z. B. PDF-Kalender)
		}
		$kommend = golfplatz_pcc_kalender( $c['code'] );
		if ( is_wp_error( $kommend ) ) {
			$log['fehler'][] = $c['kurz'] . ': ' . $kommend->get_error_message();
			continue;
		}
		$alle = array();
		if ( $c['eigen'] ) {
			$erg = golfplatz_pcc_seite( 'ts_resultlist', $c['code'] );
			if ( is_wp_error( $erg ) ) {
				$log['fehler'][] = $c['kurz'] . ' (Ergebnisse): ' . $erg->get_error_message();
			} else {
				$alle = golfplatz_pcc_turniere_lesen( $erg );
			}
		}
		// Ein Turnier kann in beiden Listen stehen (heute); die Kalenderzeile hat die aktuelleren Plätze, die Ergebnisliste den Ergebnislink
		foreach ( $kommend as $id => $t ) {
			$alle[ $id ] = isset( $alle[ $id ] ) ? array_merge( $alle[ $id ], array_filter( $t, fn( $v ) => '' !== $v && null !== $v ) ) : $t;
		}
		if ( ! $alle ) {
			continue;
		}
		++$log['clubs'];

		foreach ( $alle as $id => $t ) {
			$meta = array(
				'turnier_extern_id'          => $id,
				'turnier_club'               => $c['code'],
				'turnier_club_name'          => $c['name'],
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
			$pid = $vorhanden[ $c['code'] ][ (string) $id ] ?? 0;
			if ( $pid ) {
				// Wieder im Kalender: automatische Absage aufheben
				if ( get_post_meta( $pid, 'turnier_pcc_fehlt', true ) ) {
					delete_post_meta( $pid, 'turnier_pcc_fehlt' );
				}
				// Neues Datum für ein kommendes Turnier: als „verschoben vom …“ merken (zurück auf das alte Datum hebt es auf)
				$alt_ts = (int) get_post_meta( $pid, 'turnier_beginn', true );
				if ( $alt_ts && $alt_ts >= $heute && gmdate( 'Y-m-d', $alt_ts ) !== gmdate( 'Y-m-d', $t['beginn'] ) && ! get_post_meta( $pid, 'turnier_verschoben_von', true ) ) {
					update_post_meta( $pid, 'turnier_verschoben_von', $alt_ts );
				}
				$von = (int) get_post_meta( $pid, 'turnier_verschoben_von', true );
				if ( $von && gmdate( 'Y-m-d', $von ) === gmdate( 'Y-m-d', $t['beginn'] ) ) {
					delete_post_meta( $pid, 'turnier_verschoben_von' );
				}
				$alt = array_map( fn( $k ) => (string) get_post_meta( $pid, $k, true ), array_keys( $meta ) );
				if ( array_combine( array_keys( $meta ), $alt ) == array_map( 'strval', $meta ) && html_entity_decode( get_post_field( 'post_title', $pid ) ) === $t['titel'] /* Rohtitel: get_the_title() setzt typografische Striche */ && 'publish' === get_post_status( $pid ) ) {
					++$log['unveraendert'];
					continue;
				}
				wp_update_post( array( 'ID' => $pid, 'post_title' => $t['titel'], 'post_status' => 'publish' ) );
				++$log['aktualisiert'];
			} else {
				$pid = wp_insert_post( array( 'post_type' => 'turnier', 'post_status' => 'publish', 'post_title' => $t['titel'], 'post_name' => 'pcc-' . $c['code'] . '-' . $id ) );
				++$log['neu'];
			}
			foreach ( $meta as $k => $v ) {
				update_post_meta( $pid, $k, $v );
			}
		}
		// Kommende Turniere dieses Clubs, die nicht mehr im Kalender stehen, gelten als abgesagt (nur wenn der Kalender Turniere hatte).
		// Sie bleiben sichtbar und werden als „abgesagt“ angezeigt; die Tee-Sperre entfällt.
		if ( $kommend ) {
			foreach ( $vorhanden[ $c['code'] ] ?? array() as $id => $pid ) {
				if ( ! isset( $alle[ $id ] ) && (int) get_post_meta( $pid, 'turnier_beginn', true ) >= $heute && 'publish' === get_post_status( $pid ) && ! get_post_meta( $pid, 'turnier_pcc_fehlt', true ) ) {
					update_post_meta( $pid, 'turnier_pcc_fehlt', 1 );
					++$log['abgesagt'];
				}
			}
		}
		usleep( 200000 ); // Server von PC CADDIE schonen
	}
	if ( ! $log['clubs'] ) {
		$log['fehler'][] = 'Keine Turniere gelesen – hat PC CADDIE den Aufbau der Seiten geändert?';
	}
	do_action( 'golfplatz_pcc_nach_abgleich', $log ); // z. B. Tee-Belegung (golfplatz-tee-belegung.php)
	return golfplatz_pcc_log( $log );
}

function golfplatz_pcc_log( array $log ): array {
	$alle = (array) get_option( GOLFPLATZ_PCC_LOG, array() );
	array_unshift( $alle, $log );
	update_option( GOLFPLATZ_PCC_LOG, array_slice( $alle, 0, 30 ), false );
	return $log;
}

function golfplatz_pcc_log_text( array $l ): string {
	return wp_date( 'd.m.Y H:i', $l['zeit'] ) . ': ' . ( isset( $l['clubs'] ) ? $l['clubs'] . ' Clubs, ' : '' ) . $l['neu'] . ' neu, ' . $l['aktualisiert'] . ' aktualisiert, ' . $l['unveraendert'] . ' unverändert' . ( $l['abgesagt'] ? ', ' . $l['abgesagt'] . ' nicht mehr im Kalender (als abgesagt angezeigt)' : '' ) . ( $l['fehler'] ? '; Probleme: ' . implode( ' ', $l['fehler'] ) : '' );
}

/* ---------- Absage und Verschiebung ---------- */

/**
 * Tatsächlicher Stand eines Turniers. Von Hand am Turnier (Kasten „Absage / Verschiebung“: turnier_status, turnier_status_hinweis,
 * turnier_neuer_termin) oder automatisch aus PC CADDIE (turnier_pcc_fehlt = nicht mehr im Kalender, turnier_verschoben_von = altes Datum).
 * Die Angabe von Hand geht vor. beginn = gültiger Termin (Ortszeit als Unix-Zeit), findet_statt = sperrt Tees und erlaubt Anmeldung.
 */
function golfplatz_turnier_status( int $id ): array {
	$m       = fn( string $k ) => get_post_meta( $id, $k, true );
	$tz      = new DateTimeZone( 'UTC' );
	$beginn  = (int) $m( 'turnier_beginn' );
	$uhrzeit = (bool) $m( 'turnier_hat_uhrzeit' );
	$hand    = (string) $m( 'turnier_status' );
	$hinweis = trim( (string) $m( 'turnier_status_hinweis' ) );
	$status  = $hand;
	$von     = 0;
	$offen   = false;
	if ( 'verschoben' === $hand ) {
		$neu = (int) $m( 'turnier_neuer_termin' );
		if ( $neu ) {
			$von     = $beginn;
			$beginn  = $neu;
			$uhrzeit = true;
		} else {
			$offen = true; // neuer Termin steht noch nicht fest
		}
	} elseif ( '' === $hand ) {
		if ( $m( 'turnier_pcc_fehlt' ) ) {
			$status = 'abgesagt';
		} elseif ( (int) $m( 'turnier_verschoben_von' ) ) {
			$status = 'verschoben';
			$von    = (int) $m( 'turnier_verschoben_von' );
		}
	}
	$tag  = fn( int $ts ) => wp_date( 'D, j.n.', $ts, $tz );
	$text = '';
	if ( 'abgesagt' === $status ) {
		$text = 'Abgesagt';
	} elseif ( 'verschoben' === $status ) {
		$text = $offen ? 'Verschoben – neuer Termin folgt' : 'Verschoben vom ' . $tag( $von );
	}
	return array(
		'status'       => $status,
		'beginn'       => $beginn,
		'hat_uhrzeit'  => $uhrzeit,
		'von'          => $von,
		'termin_offen' => $offen,
		'hinweis'      => $hinweis,
		'text'         => $text ? $text . ( $hinweis ? ' – ' . $hinweis : '' ) : '',
		'findet_statt' => 'abgesagt' !== $status && ! $offen,
	);
}

/**
 * Hinweise für den Platzstatus: Turniere des Heimatclubs, die an diesem Tag geplant waren und abgesagt oder verschoben sind.
 * $tag = 0 Uhr des Tages (Ortszeit als Unix-Zeit).
 */
function golfplatz_turniere_ausfall_am( int $tag ): array {
	$eigen  = golfplatz_pcc_club();
	$liste  = array();
	$ids    = get_posts(
		array(
			'post_type'      => 'turnier',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			// altes Datum: turnier_beginn (von Hand verschoben/abgesagt) oder turnier_verschoben_von (Datum in PC CADDIE geändert)
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => 'turnier_beginn', 'value' => array( $tag, $tag + DAY_IN_SECONDS - 1 ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ),
				array( 'key' => 'turnier_verschoben_von', 'value' => array( $tag, $tag + DAY_IN_SECONDS - 1 ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ),
			),
		)
	);
	foreach ( $ids as $id ) {
		if ( ( (string) get_post_meta( $id, 'turnier_club', true ) ?: $eigen ) !== $eigen ) {
			continue;
		}
		$s = golfplatz_turnier_status( $id );
		if ( $s['findet_statt'] && ( ! $s['von'] || gmdate( 'Y-m-d', $s['beginn'] ) === gmdate( 'Y-m-d', $tag ) ) ) {
			continue; // findet an diesem Tag statt
		}
		$alt     = $s['von'] && gmdate( 'Y-m-d', $s['von'] ) === gmdate( 'Y-m-d', $tag ) ? $s['von'] : (int) get_post_meta( $id, 'turnier_beginn', true );
		$was     = 'abgesagt' === $s['status'] ? 'abgesagt' : ( $s['termin_offen'] ? 'verschoben, neuer Termin folgt' : 'verschoben auf ' . wp_date( 'l, j. F', $s['beginn'], new DateTimeZone( 'UTC' ) ) );
		$liste[] = array(
			'titel'   => html_entity_decode( get_the_title( $id ) ),
			'zeit'    => get_post_meta( $id, 'turnier_hat_uhrzeit', true ) ? gmdate( 'H:i', $alt ) . ' Uhr' : '',
			'text'    => html_entity_decode( get_the_title( $id ) ) . ' ' . $was . ( $s['hinweis'] ? ' (' . $s['hinweis'] . ')' : '' ),
			'art'     => 'abgesagt' === $s['status'] ? 'abgesagt' : 'verschoben',
		);
	}
	return $liste;
}

// Kasten am Turnier: Absage oder Verschiebung von Hand (bleibt beim Abgleich mit PC CADDIE erhalten)
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$boxen[] = array(
			'id'         => 'turnier-absage',
			'title'      => 'Absage / Verschiebung',
			'post_types' => array( 'turnier' ),
			'context'    => 'side',
			'priority'   => 'high',
			'fields'     => array(
				array( 'type' => 'custom_html', 'callback' => 'golfplatz_turnier_status_html' ),
				array( 'id' => 'turnier_status', 'name' => 'Status', 'type' => 'radio', 'inline' => false, 'options' => array( '' => 'Findet statt (laut PC CADDIE)', 'abgesagt' => 'Abgesagt', 'verschoben' => 'Verschoben' ), 'std' => '' ),
				array( 'id' => 'turnier_neuer_termin', 'name' => 'Neuer Termin', 'type' => 'datetime', 'timestamp' => true, 'js_options' => array( 'stepMinute' => 5 ), 'desc' => 'Leer = „neuer Termin folgt“.', 'visible' => array( 'turnier_status', 'verschoben' ) ),
				array( 'id' => 'turnier_status_hinweis', 'name' => 'Hinweis (öffentlich)', 'type' => 'text', 'placeholder' => 'z. B. wegen Unwetter', 'hidden' => array( 'turnier_status', '' ) ),
			),
		);
		return $boxen;
	}
);

function golfplatz_turnier_status_html(): string {
	$id = (int) ( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $id ) {
		return '';
	}
	$s    = golfplatz_turnier_status( $id );
	$auto = '' === (string) get_post_meta( $id, 'turnier_status', true ) && $s['status'] ? ' (automatisch aus PC CADDIE)' : '';
	return '<p><strong>Aktuell:</strong> ' . esc_html( $s['text'] ? $s['text'] . $auto : 'findet statt' ) . '</p><p class="description">Abgesagte Turniere bleiben im Kalender sichtbar (durchgestrichen); die Sperre von Tee 1/10 entfällt. Verschobene erscheinen am neuen Termin, die Sperre wandert mit.</p>';
}

/* ---------- Daten für Etch: {options.golfplatz.turniere} ---------- */

/** Ein Turnier für die Ausgabe. */
function golfplatz_turnier_zeile( WP_Post $p, int $heute, array $clubs = array() ): array {
	$m      = fn( string $k ) => get_post_meta( $p->ID, $k, true );
	$status = golfplatz_turnier_status( $p->ID );
	$ts     = $status['beginn'];
	$tz     = new DateTimeZone( 'UTC' ); // Ortszeit als Unix-Zeit
	$kat    = array_filter( explode( ' ', (string) $m( 'turnier_kategorien' ) ) );
	$max    = (int) $m( 'turnier_teilnehmer_max' );
	$frei   = $m( 'turnier_plaetze_frei' );
	$plaetze = '';
	if ( '' !== (string) $frei && $max && $status['findet_statt'] ) {
		$plaetze = 0 === (int) $frei ? 'ausgebucht' : (int) $frei . ' von ' . $max . ' Plätzen frei';
	}
	$infos = array_filter(
		array(
			$status['hat_uhrzeit'] ? wp_date( 'H:i', $ts, $tz ) . ' Uhr' : '',
			(string) $m( 'turnier_spielform' ),
			(int) $m( 'turnier_loecher' ) ? (int) $m( 'turnier_loecher' ) . ' Löcher' : '',
			$m( 'turnier_vorgabewirksam' ) ? 'handicaprelevant' : '',
			$m( 'turnier_gaeste' ) ? 'offen für Gäste' : '',
		)
	);
	// Club des Turniers (eigener Club oder GOLFHOCHZEHN-Partner); ältere Einträge ohne Club gehören zum eigenen
	$code = (string) $m( 'turnier_club' ) ?: golfplatz_pcc_club();
	$club = $clubs[ $code ] ?? array( 'kurz' => (string) $m( 'turnier_club_name' ), 'name' => (string) $m( 'turnier_club_name' ), 'eigen' => false, 'website' => '' );
	return array(
		'club'              => $code,
		'club_kurz'         => $club['kurz'],
		'club_name'         => $club['name'],
		'club_website'      => $club['website'],
		'eigen'             => $club['eigen'],
		'club_mod'          => $club['eigen'] ? 'heim' : 'partner',
		'titel'             => html_entity_decode( get_the_title( $p ) ),
		'uhrzeit'           => $status['hat_uhrzeit'] ? wp_date( 'H:i', $ts, $tz ) : '',
		'ts'                => $ts,
		'abgesagt'          => ! $status['findet_statt'],
		'verschoben'        => 'verschoben' === $status['status'],
		'status_mod'        => $status['findet_statt'] ? ( $status['status'] ?: 'geplant' ) : 'abgesagt',
		'status_text'       => $status['text'],
		'loecher'           => (int) $m( 'turnier_loecher' ),
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
		'anmeldeschluss'    => $status['findet_statt'] ? (string) $m( 'turnier_anmeldeschluss' ) : '',
		'plaetze'           => $plaetze,
		'ausgebucht'        => 'ausgebucht' === $plaetze,
		'link_anmeldung'    => (string) $m( 'turnier_link_anmeldung' ),
		'hat_anmeldung'     => '' !== (string) $m( 'turnier_link_anmeldung' ) && 'ausgebucht' !== $plaetze && $status['findet_statt'],
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
 * Filter per URL: ?kategorie=S (Kalender), ?jahr=2025 (Ergebnisse), ?ab=JJJJ-MM-TT (Platzbelegung) – auf /turniere/.
 */
function golfplatz_turniere_etch(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$heute = gmmktime( 0, 0, 0, (int) wp_date( 'n' ), (int) wp_date( 'j' ), (int) wp_date( 'Y' ) );
	$clubs = array();
	foreach ( golfplatz_pcc_clubs() as $c ) {
		$clubs[ $c['code'] ?: 'ohne-' . sanitize_title( $c['kurz'] ) ] = $c;
	}
	$alle = array();
	foreach ( get_posts( array( 'post_type' => 'turnier', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => 'turnier_beginn', 'orderby' => 'meta_value_num', 'order' => 'ASC', 'no_found_rows' => true ) ) as $p ) {
		$alle[] = golfplatz_turnier_zeile( $p, $heute, $clubs );
	}
	usort( $alle, fn( $a, $b ) => $a['ts'] <=> $b['ts'] ); // verschobene an ihrem neuen Termin
	$eigene  = array_values( array_filter( $alle, fn( $t ) => $t['eigen'] ) );
	$seite   = get_permalink( get_page_by_path( 'turniere' ) ) ?: home_url( '/turniere/' );
	// phpcs:disable WordPress.Security.NonceVerification
	$kat  = isset( $_GET['kategorie'] ) ? strtoupper( sanitize_key( $_GET['kategorie'] ) ) : '';
	$kat  = isset( GOLFPLATZ_PCC_KATEGORIEN[ $kat ] ) ? $kat : '';
	$jahr = isset( $_GET['jahr'] ) ? (int) $_GET['jahr'] : 0;
	$ab   = isset( $_GET['ab'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['ab'] ) ? strtotime( $_GET['ab'] . ' 00:00:00 UTC' ) : 0;
	// phpcs:enable

	// Turnierkalender: nur der Heimatclub, Filter nach Kategorie
	$kommend = array_values( array_filter( $eigene, fn( $t ) => $t['kommend'] && ( ! $kat || in_array( $kat, $t['kategorie_keys'], true ) ) ) );
	$monate  = array();
	foreach ( $kommend as $t ) {
		$monate[ $t['monat_key'] ]['name']       = $t['monat_name'];
		$monate[ $t['monat_key'] ]['turniere'][] = $t;
	}
	$filter = array( array( 'name' => 'Alle', 'link' => $seite . '#turnierkalender', 'aktiv' => $kat ? '' : 'aktiv' ) );
	foreach ( array( 'D', 'H', 'S', 'J', 'C' ) as $k ) {
		$filter[] = array( 'name' => GOLFPLATZ_PCC_KATEGORIEN[ $k ], 'link' => add_query_arg( 'kategorie', strtolower( $k ), $seite ) . '#turnierkalender', 'aktiv' => $k === $kat ? 'aktiv' : '' );
	}

	// Ergebnisse des Heimatclubs
	$gespielt = array_reverse( array_values( array_filter( $eigene, fn( $t ) => ! $t['kommend'] && ! $t['abgesagt'] ) ) ); // abgesagte haben keine Ergebnisse
	$jahre    = array_values( array_unique( array_column( $gespielt, 'jahr' ) ) );
	$jahr     = in_array( $jahr, $jahre, true ) ? $jahr : ( $jahre[0] ?? (int) wp_date( 'Y' ) );
	$heutige  = array_values( array_filter( $eigene, fn( $t ) => $t['heute'] ) );

	$cache = array(
		'monate'       => array_values( $monate ),
		'hat_kommende' => (bool) $kommend,
		'filter'       => $filter,
		'filter_name'  => $kat ? GOLFPLATZ_PCC_KATEGORIEN[ $kat ] : '',
		'gespielt'     => array_values( array_filter( $gespielt, fn( $t ) => $t['jahr'] === $jahr ) ),
		'hat_gespielt' => (bool) $gespielt,
		'jahr'         => (string) $jahr,
		'jahre'        => array_map( fn( $j ) => array( 'jahr' => (string) $j, 'link' => add_query_arg( 'jahr', $j, $seite ) . '#turnierergebnisse', 'aktiv' => $j === $jahr ? 'aktiv' : '' ), $jahre ),
		'heute'        => $heutige,
		'hat_heute'    => (bool) $heutige,
		'naechste'     => array_slice( array_values( array_filter( $eigene, fn( $t ) => $t['kommend'] ) ), 0, 4 ),
		'hat_naechste' => (bool) array_filter( $eigene, fn( $t ) => $t['kommend'] ),
		'pcc_kalender' => GOLFPLATZ_PCC_WEB . golfplatz_pcc_club() . '/app.php?cat=ts_calendar',
		'belegung'     => golfplatz_platzbelegung( $alle, $clubs, $heute, $ab, $seite ),
	);
	return $cache;
}

/**
 * Platzbelegung als Tabelle: Zeilen = Tage (4 Wochen ab Montag), Spalten = Heimatclub (erste Spalte) und Partnerclubs mit PC CADDIE.
 * Zelle: Turniere des Tages mit Uhrzeit und Löchern oder „frei“. Blättern per ?ab=JJJJ-MM-TT (nie vor die laufende Woche).
 */
function golfplatz_platzbelegung( array $turniere, array $clubs, int $heute, int $ab, string $seite ): array {
	$tage_anzahl = 28;
	$montag      = $heute - ( (int) gmdate( 'N', $heute ) - 1 ) * DAY_IN_SECONDS; // Montag der laufenden Woche
	$start       = max( $montag, $ab ? $ab - ( (int) gmdate( 'N', $ab ) - 1 ) * DAY_IN_SECONDS : $montag );
	$ende        = $start + $tage_anzahl * DAY_IN_SECONDS;
	$tz          = new DateTimeZone( 'UTC' ); // Ortszeit als Unix-Zeit

	// Heimatclub zuerst (golfplatz_pcc_clubs() liefert ihn als ersten), dann die Partnerclubs mit PC CADDIE
	$spalten = array_values( array_filter( $clubs, fn( $c ) => '' !== $c['code'] ) );
	usort( $spalten, fn( $a, $b ) => (int) $b['eigen'] <=> (int) $a['eigen'] );
	$ohne    = array_values( array_filter( $clubs, fn( $c ) => ! $c['eigen'] && '' === $c['code'] ) );

	// Turniere je Tag und Club
	$je = array();
	foreach ( $turniere as $t ) {
		$ts = strtotime( $t['datum_iso'] . ' 00:00:00 UTC' );
		if ( $ts >= $start && $ts < $ende ) {
			$je[ $t['datum_iso'] ][ $t['club'] ][] = array(
				'zeit'    => $t['uhrzeit'],
				'titel'   => $t['titel'],
				'loecher' => $t['loecher'] ? $t['loecher'] . ' Loch' : '',
				'link'    => $t['link_details'] ?: $t['link_ausschreibung'],
					'abgesagt' => $t['abgesagt'],
					'mod'     => $t['abgesagt'] ? 'abgesagt' : 'aktiv',
				);
		}
	}
	$tage = array();
	for ( $ts = $start; $ts < $ende; $ts += DAY_IN_SECONDS ) {
		$iso    = gmdate( 'Y-m-d', $ts );
		$zellen = array();
		foreach ( $spalten as $c ) {
			$liste    = $je[ $iso ][ $c['code'] ] ?? array();
			$belegt   = (bool) array_filter( $liste, fn( $e ) => ! $e['abgesagt'] );
			$zellen[] = array(
				'belegt'    => $belegt,
				'hat_turniere' => (bool) $liste,
				'mod'       => $belegt ? 'belegt' : 'frei',
				'turniere'  => $liste,
				'club'      => $c['kurz'],
				'club_mod'  => $c['eigen'] ? 'heim' : 'partner',
				'vorlesen'  => $c['kurz'] . ': ' . ( $liste ? implode( '; ', array_map( fn( $e ) => trim( $e['zeit'] . ' ' . $e['titel'] ) . ( $e['abgesagt'] ? ' (abgesagt)' : '' ), $liste ) ) . ( $belegt ? '' : ', Platz frei' ) : 'kein Turnier' ),
			);
		}
		$wt     = (int) gmdate( 'N', $ts );
		$tage[] = array(
			'iso'       => $iso,
			'datum'     => wp_date( 'D, d.m.', $ts, $tz ),
			'datum_lang' => wp_date( 'l, j. F', $ts, $tz ),
			'mod'       => $ts < $heute ? 'vergangen' : ( $wt >= 6 ? 'wochenende' : 'werktag' ),
			'heute'     => $ts === $heute,
			'heute_mod' => $ts === $heute ? 'heute' : 'tag',
			'zellen'    => $zellen,
		);
	}
	$link = fn( int $ts ) => add_query_arg( 'ab', gmdate( 'Y-m-d', $ts ), $seite ) . '#platzbelegung';
	return array(
		'clubs'        => array_map( fn( $c ) => array( 'mod' => $c['eigen'] ? 'heim' : 'partner', 'kurz' => $c['kurz'], 'name' => $c['name'], 'website' => $c['website'], 'hat_website' => '' !== $c['website'] ), $spalten ),
		'anzahl'       => count( $spalten ),
		'hat_clubs'    => (bool) $spalten,
		'tage'         => $tage,
		'zeitraum'     => wp_date( 'j. F', $start, $tz ) . ' – ' . wp_date( 'j. F Y', $ende - DAY_IN_SECONDS, $tz ),
		'zurueck'      => $link( $start - $tage_anzahl * DAY_IN_SECONDS ),
		'hat_zurueck'  => $start > $montag,
		'weiter'       => $link( $ende ),
		'ohne_pcc'     => array_map( fn( $c ) => array( 'name' => $c['name'], 'link' => $c['kalender'] ?: $c['website'] ), $ohne ),
		'hat_ohne_pcc' => (bool) $ohne,
	);
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

/* ---------- Backend-Liste „Alle Turniere“: standardmäßig nur kommende Turniere des Heimatclubs ---------- */

/** Filter der Liste aus der URL: Club (heim, alle oder PC-CADDIE-Kennung) und Zeitraum (kommend, vergangen, alle). */
function golfplatz_turnier_listenfilter(): array {
	// phpcs:disable WordPress.Security.NonceVerification
	$club     = isset( $_GET['turnier_club'] ) ? sanitize_key( wp_unslash( $_GET['turnier_club'] ) ) : 'heim';
	$zeitraum = isset( $_GET['turnier_zeitraum'] ) ? sanitize_key( wp_unslash( $_GET['turnier_zeitraum'] ) ) : 'kommend';
	// phpcs:enable
	return array(
		'club'     => $club ?: 'heim',
		'zeitraum' => in_array( $zeitraum, array( 'kommend', 'vergangen', 'alle' ), true ) ? $zeitraum : 'kommend',
	);
}

add_action(
	'pre_get_posts',
	function ( WP_Query $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'turnier' !== $q->get( 'post_type' ) || 'edit.php' !== ( $GLOBALS['pagenow'] ?? '' ) ) {
			return;
		}
		$f     = golfplatz_turnier_listenfilter();
		$heute = gmmktime( 0, 0, 0, (int) wp_date( 'n' ), (int) wp_date( 'j' ), (int) wp_date( 'Y' ) );
		$meta  = array( 'relation' => 'AND' );
		if ( 'heim' === $f['club'] ) {
			// Ältere Einträge ohne Club gehören zum Heimatclub
			$meta[] = array(
				'relation' => 'OR',
				array( 'key' => 'turnier_club', 'value' => golfplatz_pcc_club() ),
				array( 'key' => 'turnier_club', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'turnier_club', 'value' => '' ),
			);
		} elseif ( 'alle' !== $f['club'] ) {
			$meta[] = array( 'key' => 'turnier_club', 'value' => $f['club'] );
		}
		if ( 'kommend' === $f['zeitraum'] ) {
			// auch Turniere, die auf einen kommenden Termin verschoben sind
			$meta[] = array(
				'relation' => 'OR',
				array( 'key' => 'turnier_beginn', 'value' => $heute, 'compare' => '>=', 'type' => 'NUMERIC' ),
				array( 'key' => 'turnier_neuer_termin', 'value' => $heute, 'compare' => '>=', 'type' => 'NUMERIC' ),
			);
		} elseif ( 'vergangen' === $f['zeitraum'] ) {
			$meta[] = array( 'key' => 'turnier_beginn', 'value' => $heute, 'compare' => '<', 'type' => 'NUMERIC' );
		}
		$q->set( 'meta_query', $meta );
		// Sortierung nach Termin: kommende aufsteigend, sonst neueste zuerst (Spaltenkopf „Termin“ dreht sie um)
		if ( ! $q->get( 'orderby' ) || 'termin' === $q->get( 'orderby' ) ) {
			$q->set( 'meta_key', 'turnier_beginn' );
			$q->set( 'orderby', 'meta_value_num' );
			if ( ! isset( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$q->set( 'order', 'kommend' === $f['zeitraum'] ? 'ASC' : 'DESC' );
			}
		}
	}
);

// Auswahlfelder über der Liste
add_action(
	'restrict_manage_posts',
	function ( $typ ) {
		if ( 'turnier' !== $typ ) {
			return;
		}
		$f     = golfplatz_turnier_listenfilter();
		$clubs = array( 'heim' => 'Heimatclub' );
		foreach ( golfplatz_pcc_clubs() as $c ) {
			if ( ! $c['eigen'] && '' !== $c['code'] ) {
				$clubs[ $c['code'] ] = $c['kurz'];
			}
		}
		$clubs['alle'] = 'Alle Clubs';
		echo '<label class="screen-reader-text" for="turnier_club">Club</label><select name="turnier_club" id="turnier_club">';
		foreach ( $clubs as $wert => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $wert ), selected( $f['club'], (string) $wert, false ), esc_html( $label ) );
		}
		echo '</select><label class="screen-reader-text" for="turnier_zeitraum">Zeitraum</label><select name="turnier_zeitraum" id="turnier_zeitraum">';
		foreach ( array( 'kommend' => 'Kommende Turniere', 'vergangen' => 'Vergangene Turniere', 'alle' => 'Alle Termine' ) as $wert => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $wert ), selected( $f['zeitraum'], $wert, false ), esc_html( $label ) );
		}
		echo '</select>';
	}
);

// Spalten Termin, Club, Status statt Veröffentlichungsdatum
add_filter(
	'manage_turnier_posts_columns',
	function ( $spalten ) {
		unset( $spalten['date'] );
		return array_slice( $spalten, 0, 2, true ) + array( 'termin' => 'Termin' ) + array_slice( $spalten, 2, null, true ) + array( 'club' => 'Club', 'status' => 'Status' );
	}
);
add_filter( 'manage_edit-turnier_sortable_columns', fn( $s ) => $s + array( 'termin' => 'termin' ) );
add_action(
	'manage_turnier_posts_custom_column',
	function ( $spalte, $id ) {
		$s = golfplatz_turnier_status( (int) $id );
		if ( 'termin' === $spalte ) {
			echo esc_html( wp_date( 'D, d.m.Y', $s['beginn'], new DateTimeZone( 'UTC' ) ) . ( $s['hat_uhrzeit'] ? ', ' . gmdate( 'H:i', $s['beginn'] ) . ' Uhr' : '' ) );
		} elseif ( 'club' === $spalte ) {
			echo esc_html( (string) get_post_meta( $id, 'turnier_club_name', true ) ?: 'Heimatclub' );
		} elseif ( 'status' === $spalte ) {
			echo $s['text'] ? '<strong>' . esc_html( $s['text'] ) . '</strong>' : 'findet statt';
		}
	},
	10,
	2
);
