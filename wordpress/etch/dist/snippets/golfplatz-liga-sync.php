<?php
/**
 * Plugin Name: Golfplatz – Ligaspiele vom Landesverband
 * Description: Holt Spieltage, Spielorte und Ergebnisse der Club-Mannschaften aus dem Ligaportal des Landesverbands auf liga.golf (GraphQL-Schnittstelle der Seite; Adressen und Suchbegriff in den Clubdaten › Gäste & Systeme, leer = kein Abgleich) und legt daraus Mannschaften und Ligaspiele an bzw. aktualisiert sie. Täglich per WP-Cron für die laufende Saison, von Hand unter Mannschaften → Verband-Abgleich. Spielberichte und von Hand gepflegte Spiele bleiben unberührt.
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-liga-sync.php
 *
 * Hinweis: Die Schnittstelle ist nicht offiziell dokumentiert und kann sich ändern. Fällt ein Abruf aus, bleiben
 * die zuletzt übernommenen Daten stehen; der Fehler steht im Protokoll unter Mannschaften → Verband-Abgleich.
 */

defined( 'ABSPATH' ) || exit;

define( 'GOLFPLATZ_LIGA_AB', 2023 ); // frühestes Jahr des Abgleichs
define( 'GOLFPLATZ_LIGA_TEAMS', 'golfplatz_liga_teams' ); // Option: gefundene Teams je Jahr
define( 'GOLFPLATZ_LIGA_LOG', 'golfplatz_liga_log' );   // Option: letzte Abgleiche

/** Wert aus den Clubdaten › Gäste & Systeme › Ligaportal. */
function golfplatz_liga_einstellung( string $feld ): string {
	$club = (array) get_option( 'clubdaten', array() );
	return trim( (string) ( $club[ $feld ] ?? '' ) );
}

/** Schnittstelle des Ligaportals, z. B. https://gvnrw-backend.liga.golf */
function golfplatz_liga_api(): string {
	return untrailingslashit( golfplatz_liga_einstellung( 'verband_liga_api' ) );
}

/** Öffentliche Seite des Ligaportals, z. B. https://gvnrw.liga.golf (Links zu den Tabellen). */
function golfplatz_liga_web(): string {
	return untrailingslashit( golfplatz_liga_einstellung( 'verband_liga_web' ) );
}

/** Name des Verbands für Texte, z. B. „Golfverband NRW“. */
function golfplatz_liga_verband(): string {
	return golfplatz_liga_einstellung( 'verband_name' ) ?: 'Landesverband';
}

/** Kennung vor den externen IDs der Ligaspiele: Subdomain des Portals (gvnrw.liga.golf → „gvnrw“). */
function golfplatz_liga_praefix(): string {
	$host = (string) wp_parse_url( golfplatz_liga_web() ?: golfplatz_liga_api(), PHP_URL_HOST );
	return strtok( str_replace( '-backend', '', $host ), '.' ) ?: 'liga';
}

/** Abgleich nur, wenn Schnittstelle und Suchbegriff in den Clubdaten stehen (im Blueprint leer = aus). */
function golfplatz_liga_eingerichtet(): bool {
	return '' !== golfplatz_liga_api() && '' !== golfplatz_liga_suchbegriff();
}

/** GraphQL-Abfrage. Liefert data oder WP_Error. */
function golfplatz_liga_gql( string $query, array $variables ) {
	$res = wp_remote_post(
		golfplatz_liga_api(),
		array(
			'timeout'    => 20,
			'headers'    => array( 'Content-Type' => 'application/json' ),
			'user-agent' => 'Golfplatz-Website/1.0 (' . home_url() . ')',
			'body'       => wp_json_encode( array( 'query' => $query, 'variables' => $variables ) ),
		)
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$json = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( 200 !== wp_remote_retrieve_response_code( $res ) || ! is_array( $json ) || ! empty( $json['errors'] ) ) {
		return new WP_Error( 'golfplatz_liga_api', 'Antwort von ' . wp_parse_url( golfplatz_liga_api(), PHP_URL_HOST ) . ' nicht verwendbar (HTTP ' . wp_remote_retrieve_response_code( $res ) . ').' );
	}
	return $json['data'] ?? array();
}

function golfplatz_liga_wettbewerbe( int $jahr ) {
	$d = golfplatz_liga_gql(
		'query($year:String!){findLeagues(input:{year:$year,useVisibilty:true}){leagues{leagueId name subLeagues{sort leagueId name}}}}',
		array( 'year' => (string) $jahr )
	);
	return is_wp_error( $d ) ? $d : ( $d['findLeagues']['leagues'] ?? array() );
}

function golfplatz_liga_tabelle( string $liga_id, array $sub_leagues ) {
	$d = golfplatz_liga_gql(
		'query($leagueId:String!,$subLeagues:[SubLeagueInput]!,$useVisibilty:Boolean){findLeagueResult(input:{leagueId:$leagueId,subLeagues:$subLeagues,useVisibilty:$useVisibilty}){result{name spieltage{name date} teams{teamId data{value homeTeam}}}}}',
		array( 'leagueId' => $liga_id, 'subLeagues' => $sub_leagues, 'useVisibilty' => true )
	);
	return is_wp_error( $d ) ? $d : ( $d['findLeagueResult']['result'][0] ?? null );
}

function golfplatz_liga_team_orte( int $team_id ) {
	$d = golfplatz_liga_gql( 'query($teamId:Int!){findTeamResult(input:{teamId:$teamId}){spieltage{name date clubname}}}', array( 'teamId' => $team_id ) );
	if ( is_wp_error( $d ) ) {
		return $d;
	}
	$orte = array();
	foreach ( $d['findTeamResult']['spieltage'] ?? array() as $s ) {
		$orte[ $s['name'] ] = trim( preg_replace( '/^\s*im\s+/u', '', (string) $s['clubname'] ) );
	}
	return $orte;
}

/** Suchbegriff aus den Clubdaten (Name des Clubs in den Ligatabellen). Leer = kein Abgleich. */
function golfplatz_liga_suchbegriff(): string {
	return golfplatz_liga_einstellung( 'verband_suchbegriff' );
}

/**
 * Wettbewerb ohne Jahr; die Jugendliga läuft je nach Stufe unter eigenem Namen, gehört aber zu einer Mannschaft.
 * „NRW-MM Herren AK 50 - 2026“ → [„NRW-MM Herren AK 50“, ''], „Jugendliga Bezirksliga 2026“ → [„Jugendliga“, „Bezirksliga“]
 */
function golfplatz_liga_wettbewerb( string $name ): array {
	$name = trim( preg_replace( '/\s*-?\s*\d{4}\s*$/', '', $name ) );
	if ( preg_match( '/^Jugendliga\s+(.+)$/u', $name, $m ) ) {
		return array( 'Jugendliga', $m[1] );
	}
	return array( $name, '' );
}

/** Teamname ohne Zusatz in Klammern: „Musterclub  (abgemeldet am 14.04.23)“ → „Musterclub“. */
function golfplatz_liga_team_name( string $team ): string {
	return trim( preg_replace( '/\s*\(.*\)\s*$/u', '', $team ) );
}

/** Teamname zum Vergleich: „Musterclub 1“ gilt wie „Musterclub“. */
function golfplatz_liga_team_schluessel( string $team ): string {
	return mb_strtolower( preg_replace( '/\s+1$/', '', golfplatz_liga_team_name( $team ) ) );
}

/**
 * Alle Ligen eines Jahres nach Teams des Clubs durchsuchen (rund 250 Abrufe, deshalb nur bei Bedarf).
 * Ergebnis wird je Jahr in der Option GOLFPLATZ_LIGA_TEAMS gespeichert.
 */
function golfplatz_liga_suche( int $jahr ) {
	$wettbewerbe = golfplatz_liga_wettbewerbe( $jahr );
	if ( is_wp_error( $wettbewerbe ) ) {
		return $wettbewerbe;
	}
	$such  = golfplatz_liga_suchbegriff();
	$teams = array();
	foreach ( $wettbewerbe as $w ) {
		[ $wname, $stufe ] = golfplatz_liga_wettbewerb( $w['name'] );
		foreach ( $w['subLeagues'] as $s ) {
			$t = golfplatz_liga_tabelle( (string) $s['leagueId'], $w['subLeagues'] );
			usleep( 100000 ); // Server schonen
			if ( is_wp_error( $t ) || ! $t ) {
				continue;
			}
			$club = array_search( 'Club', array_column( $t['spieltage'], 'name' ), true );
			foreach ( $t['teams'] as $team ) {
				$name = trim( (string) ( $team['data'][ $club ]['value'] ?? '' ) );
				if ( false !== $club && '' !== $name && false !== mb_stripos( $name, $such ) ) {
					$teams[] = array(
						'wettbewerb_id' => (string) $w['leagueId'],
						'wettbewerb'    => $wname,
						'stufe'         => $stufe,
						'liga_id'       => (string) $s['leagueId'],
						'liga'          => $stufe ? $stufe . ' · ' . $s['name'] : (string) $s['name'],
						'team'          => $name,
						'team_id'       => (int) $team['teamId'],
					);
				}
			}
		}
	}
	$alle          = (array) get_option( GOLFPLATZ_LIGA_TEAMS, array() );
	$alle[ $jahr ] = array( 'gesucht' => time(), 'teams' => $teams );
	update_option( GOLFPLATZ_LIGA_TEAMS, $alle, false );
	return $teams;
}

/** Titel und Felder einer neuen Mannschaft aus Wettbewerb und Teamname. */
function golfplatz_liga_neue_mannschaft( array $e ): array {
	$nr    = preg_match( '/\s(\d+)$/', $e['team'], $m ) ? (int) $m[1] : 0;
	$w     = $e['wettbewerb'];
	$g     = str_contains( $w, 'Damen' ) ? 'weiblich' : ( str_contains( $w, 'Herren' ) ? 'maennlich' : 'gemischt' );
	$gtext = array( 'weiblich' => 'Damen', 'maennlich' => 'Herren', 'gemischt' => '' )[ $g ];
	$ak    = preg_match( '/AK\s*(\d+)/', $w, $a ) ? (int) $a[1] : 0;
	if ( $ak ) {
		$titel = 'AK' . $ak . ( $nr ? '/' . $nr : '' ) . ' ' . $gtext;
		$order = array( 30 => 20, 50 => 40, 65 => 60 )[ $ak ] ?? 80;
		$order += ( 'maennlich' === $g ? 10 : 0 ) + $nr;
	} elseif ( 'Jugendliga' === $w ) {
		$titel = 'Jugend' . ( $nr ? ' ' . $nr : '' );
		$order = 90 + $nr;
	} elseif ( str_starts_with( $w, 'DGL' ) ) {
		$titel = trim( 'DGL ' . $gtext ) . ( $nr ? ' ' . $nr : '' );
		$order = 10 + ( 'maennlich' === $g ? 2 : 0 ) + $nr;
	} else {
		$titel = preg_replace( '/-?Mannschaftspreis$/u', '-Preis', $w ) . ( $nr ? ' ' . $nr : '' );
		$order = 15 + $nr;
	}
	return array(
		'titel' => trim( $titel ),
		'order' => $order,
		'meta'  => array(
			'mannschaft_altersklasse' => $ak ? 'ak' . $ak : ( 'Jugendliga' === $w ? '' : 'club' ),
			'mannschaft_nummer'       => $nr ? (string) $nr : '',
			'mannschaft_geschlecht'   => $g,
		),
	);
}

/** Mannschaft zu einem Verbands-Team finden (Wettbewerb + Teamname) oder anlegen. */
function golfplatz_liga_mannschaft( array $e, array &$log ): int {
	$kandidaten = get_posts(
		array(
			'post_type'      => 'mannschaft',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'meta_key'       => 'mannschaft_verband_wettbewerb',
			'meta_value'     => $e['wettbewerb'],
			'fields'         => 'ids',
		)
	);
	foreach ( $kandidaten as $id ) {
		if ( golfplatz_liga_team_schluessel( (string) get_post_meta( $id, 'mannschaft_verband_team', true ) ) === golfplatz_liga_team_schluessel( $e['team'] ) ) {
			return (int) $id;
		}
	}
	$neu = golfplatz_liga_neue_mannschaft( $e );
	$id  = wp_insert_post(
		array(
			'post_type'   => 'mannschaft',
			'post_status' => 'publish',
			'post_title'  => $neu['titel'],
			'post_name'   => sanitize_title( $neu['titel'] ),
			'menu_order'  => $neu['order'],
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		$log['fehler'][] = 'Mannschaft „' . $neu['titel'] . '“ nicht angelegt: ' . $id->get_error_message();
		return 0;
	}
	foreach ( $neu['meta'] + array( 'mannschaft_verband_wettbewerb' => $e['wettbewerb'], 'mannschaft_verband_team' => $e['team'] ) as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	$log['mannschaften_neu'][] = $neu['titel'];
	return (int) $id;
}

/**
 * Gastclub (Austragungsort) in der Liste „Gastclubs“ anlegen, falls er fehlt. Die Website pflegt der Club dort einmal;
 * sie erscheint als Link beim Spielort (golfplatz-mannschaften.php).
 */
function golfplatz_liga_gastclub( string $name, array &$log ): void {
	if ( '' === $name || ! post_type_exists( 'gastclub' ) ) {
		return;
	}
	// Vorhandene Namen einmal laden; WordPress speichert „&“ im Titel als „&amp;“, deshalb dekodiert vergleichen
	static $namen = null;
	if ( null === $namen ) {
		$namen = array();
		foreach ( get_posts( array( 'post_type' => 'gastclub', 'post_status' => 'any', 'posts_per_page' => -1 ) ) as $p ) {
			$namen[ golfplatz_gastclub_schluessel( $p->post_title ) ] = true;
		}
	}
	$k = golfplatz_gastclub_schluessel( $name );
	if ( isset( $namen[ $k ] ) ) {
		return;
	}
	wp_insert_post( array( 'post_type' => 'gastclub', 'post_status' => 'publish', 'post_title' => $name ) );
	$namen[ $k ]            = true;
	$log['gastclubs_neu'][] = $name;
}

/** Vergleichsschlüssel eines Clubnamens (Entities dekodiert, Kleinschreibung, Leerraum vereinheitlicht). */
function golfplatz_gastclub_schluessel( string $name ): string {
	return mb_strtolower( trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ) ) ) );
}

/** Zahl aus einem Tabellenwert („52.5 **“ → 52.5), null wenn leer. */
function golfplatz_liga_zahl( $wert ): ?float {
	return preg_match( '/-?\d+(?:[.,]\d+)?/', (string) $wert, $m ) ? (float) str_replace( ',', '.', $m[0] ) : null;
}

/** Ligaspiele eines Verbands-Teams anlegen bzw. aktualisieren. */
function golfplatz_liga_team_abgleichen( int $jahr, array $e, array $wettbewerb, array &$log ): void {
	// Abgemeldete bzw. zurückgezogene Teams spielen nicht: keine Spiele, früher angelegte in den Papierkorb
	if ( preg_match( '/abgemeldet|zurückgezogen/iu', $e['team'] ) ) {
		$alt = get_posts(
			array(
				'post_type'      => 'ligaspiel',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => 'ligaspiel_extern_id', 'value' => golfplatz_liga_praefix() . ':' . $e['liga_id'] . ':' . $e['team_id'] . ':', 'compare' => 'LIKE' ) ),
			)
		);
		array_map( 'wp_trash_post', $alt );
		$log['fehler'][] = $e['wettbewerb'] . ' ' . $jahr . ': ' . $e['team'] . ' – keine Spiele übernommen' . ( $alt ? ', ' . count( $alt ) . ' in den Papierkorb' : '' ) . '.';
		return;
	}
	$e['team'] = golfplatz_liga_team_name( $e['team'] );
	$tabelle = golfplatz_liga_tabelle( $e['liga_id'], $wettbewerb['subLeagues'] );
	$orte    = golfplatz_liga_team_orte( $e['team_id'] );
	if ( is_wp_error( $tabelle ) || ! $tabelle || is_wp_error( $orte ) ) {
		$log['fehler'][] = $e['team'] . ' (' . $e['wettbewerb'] . ', ' . $e['liga'] . '): Abruf fehlgeschlagen.';
		return;
	}
	$mannschaft = golfplatz_liga_mannschaft( $e, $log );
	if ( ! $mannschaft ) {
		return;
	}
	$spalten = $tabelle['spieltage'];
	$club    = array_search( 'Club', array_column( $spalten, 'name' ), true );
	$zeile   = null;
	foreach ( $tabelle['teams'] as $t ) {
		if ( (int) $t['teamId'] === $e['team_id'] ) {
			$zeile = $t['data'];
		}
	}
	if ( null === $zeile ) {
		$log['fehler'][] = $e['team'] . ' fehlt in der Tabelle ' . $e['liga'] . '.';
		return;
	}
	$brutto = in_array( 'Brutto', array_column( $spalten, 'name' ), true );
	$such   = golfplatz_liga_suchbegriff();
	$link   = golfplatz_liga_web() . '/' . $e['wettbewerb_id'] . '/' . $e['liga_id'] . '/?year=' . $jahr;

	foreach ( $spalten as $i => $sp ) {
		if ( empty( $sp['date'] ) || ! preg_match( '/^(\d+)\.\s*Spieltag/u', $sp['name'], $m ) ) {
			continue;
		}
		$nr   = (int) $m[1];
		$wert = trim( (string) ( $zeile[ $i ]['value'] ?? '' ) );
		$ort  = $orte[ $sp['name'] ] ?? '';
		if ( '' === $ort ) {
			// Kein Ort beim Team: Gastgeber aus der Tabelle; fehlt beides und gibt es kein Ergebnis, spielt das Team nicht
			foreach ( $tabelle['teams'] as $t ) {
				if ( true === ( $t['data'][ $i ]['homeTeam'] ?? null ) ) {
					$ort = trim( (string) $t['data'][ $club ]['value'] );
				}
			}
			if ( '' === $ort && '' === $wert ) {
				continue;
			}
		}
		if ( '' !== $ort && false === mb_stripos( $ort, $such ) ) {
			golfplatz_liga_gastclub( $ort, $log );
		}
		// Tagesplatzierung aus der Spalte „Punkte“ rechts daneben (NRW-MM, DGL): 1 + Teams mit mehr Punkten
		$platz = 0;
		if ( 'Punkte' === ( $spalten[ $i + 1 ]['name'] ?? '' ) && null !== golfplatz_liga_zahl( $zeile[ $i + 1 ]['value'] ?? '' ) ) {
			$eigene = golfplatz_liga_zahl( $zeile[ $i + 1 ]['value'] );
			$platz  = 1;
			foreach ( $tabelle['teams'] as $t ) {
				$p = golfplatz_liga_zahl( $t['data'][ $i + 1 ]['value'] ?? '' );
				if ( null !== $p && $p > $eigene ) {
					++$platz;
				}
			}
		}
		$extern = golfplatz_liga_praefix() . ':' . $e['liga_id'] . ':' . $e['team_id'] . ':' . $nr;
		$titel  = get_the_title( $mannschaft ) . ' · ' . $e['liga'] . ' · ' . $nr . '. Spieltag ' . $jahr;
		$meta   = array(
			'ligaspiel_mannschaft'   => $mannschaft,
			'ligaspiel_spieltag'     => $nr,
			'ligaspiel_termin'       => strtotime( substr( $sp['date'], 0, 10 ) . ' 00:00:00 UTC' ), // „Ortszeit als Unix-Zeit“
			'ligaspiel_spielort'     => $ort,
			'ligaspiel_heimspiel'    => '' !== $ort && false !== mb_stripos( $ort, $such ) ? 1 : 0,
			'ligaspiel_ergebnis'     => '' === $wert ? '' : ( $brutto ? $wert . ' brutto' : $wert . ' über CR' ),
			'ligaspiel_platzierung'  => $platz ?: '',
			'ligaspiel_saison'       => $jahr,
			'ligaspiel_liga'         => $e['liga'],
			'ligaspiel_verband_link' => $link,
			'ligaspiel_extern_id'    => $extern,
		);
		$vorhanden = get_posts( array( 'post_type' => 'ligaspiel', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => 'ligaspiel_extern_id', 'meta_value' => $extern ) );
		$post      = array( 'post_type' => 'ligaspiel', 'post_status' => 'publish', 'post_title' => $titel, 'post_name' => sanitize_title( $extern ) );
		if ( $vorhanden ) {
			$post['ID'] = $vorhanden[0];
			// Nur schreiben, was sich geändert hat
			$alt = array_map( fn( $k ) => (string) get_post_meta( $vorhanden[0], $k, true ), array_keys( $meta ) );
			if ( array_combine( array_keys( $meta ), $alt ) == array_map( 'strval', $meta ) && get_the_title( $vorhanden[0] ) === $titel ) {
				++$log['unveraendert'];
				continue;
			}
			wp_update_post( $post );
			$id = $vorhanden[0];
			++$log['aktualisiert'];
		} else {
			$id = wp_insert_post( $post );
			++$log['neu'];
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, $k, $v );
		}
	}
}

/**
 * Abgleich eines Jahres. $suche = alle Ligen neu durchsuchen (sonst nur die bekannten Teams, falls vorhanden).
 * Gibt das Protokoll zurück und hängt es an GOLFPLATZ_LIGA_LOG an.
 */
function golfplatz_liga_abgleich( int $jahr, bool $suche = false ): array {
	if ( ! golfplatz_liga_eingerichtet() ) {
		return array( 'jahr' => $jahr, 'zeit' => time(), 'neu' => 0, 'aktualisiert' => 0, 'unveraendert' => 0, 'mannschaften_neu' => array(), 'gastclubs_neu' => array(), 'teams' => 0, 'fehler' => array( 'Ligaportal nicht eingerichtet (Clubdaten › Gäste & Systeme).' ) );
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	$log   = array( 'jahr' => $jahr, 'zeit' => time(), 'neu' => 0, 'aktualisiert' => 0, 'unveraendert' => 0, 'mannschaften_neu' => array(), 'gastclubs_neu' => array(), 'fehler' => array(), 'teams' => 0 );
	$alle  = (array) get_option( GOLFPLATZ_LIGA_TEAMS, array() );
	$teams = $alle[ $jahr ]['teams'] ?? null;
	if ( $suche || null === $teams ) {
		$teams = golfplatz_liga_suche( $jahr );
		if ( is_wp_error( $teams ) ) {
			$log['fehler'][] = $teams->get_error_message();
			return golfplatz_liga_log( $log );
		}
	}
	$wettbewerbe = golfplatz_liga_wettbewerbe( $jahr );
	if ( is_wp_error( $wettbewerbe ) ) {
		$log['fehler'][] = $wettbewerbe->get_error_message();
		return golfplatz_liga_log( $log );
	}
	$nach_id = array_column( $wettbewerbe, null, 'leagueId' );
	foreach ( $teams as $e ) {
		if ( isset( $nach_id[ $e['wettbewerb_id'] ] ) ) {
			golfplatz_liga_team_abgleichen( $jahr, $e, $nach_id[ $e['wettbewerb_id'] ], $log );
			++$log['teams'];
			usleep( 100000 );
		}
	}
	golfplatz_liga_ligen_aktualisieren();
	return golfplatz_liga_log( $log );
}

/** Liga je Mannschaft = Liga der neuesten Saison mit den meisten Spielen (Endrunden und Aufstiegsrunden zählen nicht). */
function golfplatz_liga_ligen_aktualisieren(): void {
	$je = array();
	foreach ( get_posts( array( 'post_type' => 'ligaspiel', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => 'ligaspiel_extern_id', 'meta_compare' => 'EXISTS' ) ) as $id ) {
		$m     = (int) get_post_meta( $id, 'ligaspiel_mannschaft', true );
		$s     = (int) get_post_meta( $id, 'ligaspiel_saison', true );
		$liga  = (string) get_post_meta( $id, 'ligaspiel_liga', true );
		$bonus = preg_match( '/Endrunde|Aufstiegsrunde/u', $liga ) ? 0 : 100;
		$je[ $m ][ $liga ] = max( $je[ $m ][ $liga ] ?? 0, $s * 1000 + $bonus ) + 1;
	}
	foreach ( $je as $m => $ligen ) {
		arsort( $ligen );
		update_post_meta( $m, 'mannschaft_liga', (string) array_key_first( $ligen ) );
	}
}

function golfplatz_liga_log( array $log ): array {
	$alle = (array) get_option( GOLFPLATZ_LIGA_LOG, array() );
	array_unshift( $alle, $log );
	update_option( GOLFPLATZ_LIGA_LOG, array_slice( $alle, 0, 20 ), false );
	return $log;
}

/* ---------- Täglicher Abgleich der laufenden Saison ---------- */

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'golfplatz_liga_sync' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 05:30', current_time( 'timestamp' ) ) - (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ), 'daily', 'golfplatz_liga_sync' );
		}
	}
);
add_action( 'golfplatz_liga_sync', fn() => golfplatz_liga_eingerichtet() && golfplatz_liga_abgleich( (int) wp_date( 'Y' ) ) );

/* ---------- Mannschaften → Verband-Abgleich ---------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=mannschaft', 'Verband-Abgleich', 'Verband-Abgleich', 'edit_others_posts', 'golfplatz-liga', 'golfplatz_liga_seite' );
	}
);

function golfplatz_liga_seite(): void {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$heute = (int) wp_date( 'Y' );
	$ergebnis = null;
	if ( isset( $_POST['golfplatz_liga_jahr'] ) && check_admin_referer( 'golfplatz_liga' ) ) {
		$jahr = max( GOLFPLATZ_LIGA_AB, min( $heute, (int) $_POST['golfplatz_liga_jahr'] ) );
		$ergebnis = golfplatz_liga_abgleich( $jahr, ! empty( $_POST['golfplatz_liga_suche'] ) );
	}
	$teams = (array) get_option( GOLFPLATZ_LIGA_TEAMS, array() );
	$log   = (array) get_option( GOLFPLATZ_LIGA_LOG, array() );
	echo '<div class="wrap"><h1>Verband-Abgleich</h1>';
	if ( ! golfplatz_liga_eingerichtet() ) {
		echo '<div class="notice notice-info inline"><p>Noch nicht eingerichtet: Unter Clubdaten → Gäste &amp; Systeme die Adressen des Ligaportals und den Namen des Clubs in den Ligatabellen eintragen. Bis dahin werden Mannschaften und Ligaspiele von Hand gepflegt.</p></div></div>';
		return;
	}
	echo '<p>Spieltage, Spielorte und Ergebnisse der Mannschaften kommen vom ' . esc_html( golfplatz_liga_verband() ) . '. Gesucht wird nach „' . esc_html( golfplatz_liga_suchbegriff() ) . '“ (Clubdaten → Gäste &amp; Systeme). Die laufende Saison wird jeden Morgen automatisch abgeglichen; Spielberichte bleiben erhalten.</p>';
	if ( $ergebnis ) {
		$klasse = $ergebnis['fehler'] ? 'notice-warning' : 'notice-success';
		echo '<div class="notice ' . esc_attr( $klasse ) . '"><p>' . esc_html( golfplatz_liga_log_text( $ergebnis ) ) . '</p></div>';
	}
	echo '<form method="post">';
	wp_nonce_field( 'golfplatz_liga' );
	echo '<p><label>Saison <select name="golfplatz_liga_jahr">';
	for ( $j = $heute; $j >= GOLFPLATZ_LIGA_AB; $j-- ) {
		echo '<option value="' . (int) $j . '">' . (int) $j . '</option>';
	}
	echo '</select></label> <label><input type="checkbox" name="golfplatz_liga_suche" value="1"> alle Ligen neu durchsuchen (dauert 1–2 Minuten, z. B. bei neuen Mannschaften)</label></p>';
	submit_button( 'Jetzt abgleichen', 'primary', 'submit', false );
	echo '</form>';

	echo '<h2>Gefundene Mannschaften</h2><table class="widefat striped" style="max-width:60rem"><thead><tr><th>Saison</th><th>Wettbewerb</th><th>Liga</th><th>Team</th></tr></thead><tbody>';
	krsort( $teams );
	foreach ( $teams as $jahr => $eintrag ) {
		foreach ( $eintrag['teams'] as $e ) {
			$url = golfplatz_liga_web() . '/' . $e['wettbewerb_id'] . '/' . $e['liga_id'] . '/?year=' . $jahr;
			echo '<tr><td>' . (int) $jahr . '</td><td>' . esc_html( $e['wettbewerb'] ) . '</td><td><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $e['liga'] ) . '</a></td><td>' . esc_html( $e['team'] ) . '</td></tr>';
		}
	}
	echo '</tbody></table><h2>Letzte Abgleiche</h2><ul>';
	foreach ( array_slice( $log, 0, 10 ) as $l ) {
		echo '<li>' . esc_html( golfplatz_liga_log_text( $l ) ) . '</li>';
	}
	echo '</ul></div>';
}

function golfplatz_liga_log_text( array $l ): string {
	$text = wp_date( 'd.m.Y H:i', $l['zeit'] ) . ' · Saison ' . $l['jahr'] . ': ' . $l['teams'] . ' Teams, ' . $l['neu'] . ' Spiele neu, ' . $l['aktualisiert'] . ' aktualisiert, ' . $l['unveraendert'] . ' unverändert';
	if ( $l['mannschaften_neu'] ) {
		$text .= '; neue Mannschaften: ' . implode( ', ', $l['mannschaften_neu'] );
	}
	if ( ! empty( $l['gastclubs_neu'] ) ) {
		$text .= '; neue Gastclubs (Website unter Mannschaften → Gastclubs eintragen): ' . count( $l['gastclubs_neu'] );
	}
	if ( $l['fehler'] ) {
		$text .= '; Probleme: ' . implode( ' ', $l['fehler'] );
	}
	return $text;
}

/*
 * Clubdaten › Gäste & Systeme: Ligaportal des Landesverbands (Feldgruppe im Code). Der Suchbegriff
 * („Name in den Ligatabellen“, verband_suchbegriff) steht in der Builder-Gruppe darüber.
 */
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$boxen[] = array(
			'id'             => 'clubdaten-ligaportal',
			'title'          => 'Clubdaten · Ligaportal',
			'settings_pages' => array( 'clubdaten' ),
			'tab'            => 'systeme',
			'fields'         => array(
				array( 'type' => 'heading', 'name' => 'Ligaportal des Landesverbands', 'desc' => 'Für den automatischen Abgleich der Ligaspiele (Mannschaften → Verband-Abgleich). Leer = kein Abgleich, Mannschaften und Ligaspiele werden von Hand gepflegt.' ),
				array( 'id' => 'verband_name', 'name' => 'Name des Verbands', 'type' => 'text', 'columns' => 4, 'placeholder' => 'z. B. Golfverband NRW', 'desc' => 'Erscheint in Texten, z. B. „Tabelle beim …“.' ),
				array( 'id' => 'verband_liga_web', 'name' => 'Adresse Ligaportal', 'type' => 'url', 'columns' => 4, 'placeholder' => 'https://gvnrw.liga.golf' ),
				array( 'id' => 'verband_liga_api', 'name' => 'Adresse Schnittstelle', 'type' => 'url', 'columns' => 4, 'placeholder' => 'https://gvnrw-backend.liga.golf' ),
			),
		);
		return $boxen;
	}
);
