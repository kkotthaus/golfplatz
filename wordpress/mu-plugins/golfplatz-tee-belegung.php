<?php
/**
 * Plugin Name: Golfplatz – Tee-Belegung durch Turniere
 * Description: Erzeugt aus den Turnieren des Heimatclubs (PC CADDIE) automatisch Sperrungen von Abschlag 1 und 10. Regeln je Turnierart (Startform, Start-Tee, Vorlauf, Dauer, Startabstand) unter Sperrungen → Turnier-Regeln; Ausnahmen je Turnier am Turnier selbst. Läuft nach jedem PC-CADDIE-Abgleich, nach dem Speichern der Regeln und eines Turniers. Die Anzeige übernimmt der Platzstatus (golfplatz-platzstatus.php) wie bei jeder Sperrung.
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/mu-plugins/golfplatz-tee-belegung.php
 */

defined( 'ABSPATH' ) || exit;

const GOLFPLATZ_TB_OPTION     = 'tee_belegung';
const GOLFPLATZ_TB_STARTFORMEN = array(
	'kanonenstart' => 'Kanonenstart',
	'tee_times'    => 'Tee-Times (Einzelstart)',
	'keine'        => 'Keine Sperre',
);
const GOLFPLATZ_TB_TEES = array(
	'1'     => 'Tee 1',
	'10'    => 'Tee 10',
	'beide' => 'Tee 1 und 10',
);

/**
 * Standardwerte des Clubs aus den Clubdaten (Reiter „Platz & Abschläge“): Startabstand, Spieler je Flight, Spielzeit je Loch,
 * Turnierpuffer. Regeln und Turniere nutzen sie, solange dort nichts eingetragen ist.
 */
function golfplatz_tb_club(): array {
	$c = (array) get_option( 'clubdaten', array() );
	$z = fn( string $k, int $std ) => max( 1, (int) ( $c[ $k ] ?? 0 ) ?: $std );
	// Puffer darf 0 sein (= kein Puffer); nur ein leeres Feld fällt auf 30 Minuten zurück
	$puffer = isset( $c['turnier_puffer'] ) && '' !== (string) $c['turnier_puffer'] ? max( 0, (int) $c['turnier_puffer'] ) : 30;
	return array( 'intervall' => $z( 'startabstand', 8 ), 'flight' => $z( 'flight_groesse', 3 ), 'loch' => $z( 'spielzeit_loch', 15 ), 'puffer' => $puffer );
}

/** Beispielregeln beim ersten Aufruf – danach nur noch im Backend gepflegt. */
function golfplatz_tb_standard(): array {
	return array(
		'regeln' => array(
			array( 'name' => 'Weihnachtsfeiern (kein Spielbetrieb)', 'muster' => 'Weihnachtsfeier', 'loecher' => '', 'startform' => 'keine', 'tee' => '1', 'vorlauf' => '', 'dauer' => '', 'intervall' => '', 'flight' => '', 'grund' => '' ),
			array( 'name' => '9-Loch Afterwork (B) – Tee 10', 'muster' => 'Afterwork (B)', 'loecher' => '', 'startform' => 'kanonenstart', 'tee' => '10', 'vorlauf' => 120, 'dauer' => '', 'intervall' => '', 'flight' => '', 'grund' => 'Turnier: {turnier}' ),
			array( 'name' => '9-Loch Afterwork (A) und übrige Afterwork – Tee 1', 'muster' => 'Afterwork', 'loecher' => '', 'startform' => 'kanonenstart', 'tee' => '1', 'vorlauf' => 120, 'dauer' => '', 'intervall' => '', 'flight' => '', 'grund' => 'Turnier: {turnier}' ),
			array( 'name' => 'Monats-Cup', 'muster' => 'Monats-Cup', 'loecher' => '', 'startform' => 'tee_times', 'tee' => '1', 'vorlauf' => 60, 'dauer' => '', 'intervall' => '', 'flight' => '', 'grund' => 'Turnier: {turnier}' ),
			array( 'name' => 'Herrengolf, Damengolf, AK 50+ – Tee 1', 'muster' => 'Herrengolf, Damengolf, AK 50+', 'loecher' => '', 'startform' => 'tee_times', 'tee' => '1', 'vorlauf' => 60, 'dauer' => '', 'intervall' => '', 'flight' => '', 'grund' => 'Turnier: {turnier}' ),
		),
	);
}
add_action(
	'admin_init',
	function () {
		if ( false === get_option( GOLFPLATZ_TB_OPTION ) ) {
			add_option( GOLFPLATZ_TB_OPTION, golfplatz_tb_standard(), '', false );
		}
	}
);

/* ---------- Einstellungsseite und Felder ---------- */

add_filter(
	'mb_settings_pages',
	function ( $seiten ) {
		$seiten[] = array(
			'id'            => 'tee-belegung',
			'option_name'   => GOLFPLATZ_TB_OPTION,
			'menu_title'    => 'Turnier-Regeln',
			'page_title'    => 'Tee-Belegung durch Turniere',
			'parent'        => 'edit.php?post_type=sperrung',
			'capability'    => 'edit_sperrungen',
			'style'         => 'no-boxes',
			'columns'       => 1,
			'submit_button' => 'Speichern und Sperrungen neu berechnen',
		);
		return $seiten;
	}
);

add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$regel = array(
			array( 'id' => 'name', 'name' => 'Bezeichnung', 'type' => 'text', 'desc' => 'Nur intern, z. B. „9-Loch Afterwork“.' ),
			array( 'id' => 'muster', 'name' => 'Turniername enthält', 'type' => 'text', 'desc' => 'Teil des Turniernamens aus PC CADDIE, mehrere mit Komma trennen (z. B. „Monats-Cup, Monatspreis“). Leer = alle übrigen Turniere.' ),
			array( 'id' => 'loecher', 'name' => 'Nur bei', 'type' => 'select', 'options' => array( '' => 'allen Turnieren', '9' => '9 Loch', '18' => '18 Loch' ) ),
			array( 'id' => 'startform', 'name' => 'Startform', 'type' => 'button_group', 'options' => GOLFPLATZ_TB_STARTFORMEN, 'std' => 'tee_times' ),
			array( 'id' => 'tee', 'name' => 'Start-Tee', 'type' => 'button_group', 'options' => GOLFPLATZ_TB_TEES, 'std' => '1', 'desc' => 'Tee-Times an Tee 1 und 10: Die Flights verteilen sich auf beide Tees.' ),
			array( 'id' => 'vorlauf', 'name' => 'Gesperrt ab … Minuten vor dem Start', 'type' => 'number', 'min' => 0, 'step' => 5, 'std' => 60 ),
			array( 'id' => 'dauer', 'name' => 'Gesperrt bis … Minuten nach dem Start', 'type' => 'number', 'min' => 0, 'step' => 5, 'desc' => 'Leer = automatisch: Kanonenstart Spielzeit je Loch × Löcher, Tee-Times bis der letzte Flight gestartet ist (Standardwerte in Clubdaten → Platz & Abschläge).' ),
			array( 'id' => 'intervall', 'name' => 'Startabstand (Minuten)', 'type' => 'number', 'min' => 1, 'desc' => 'Leer = Standard aus den Clubdaten.', 'visible' => array( 'startform', 'tee_times' ) ),
			array( 'id' => 'flight', 'name' => 'Spieler je Flight', 'type' => 'number', 'min' => 1, 'max' => 4, 'desc' => 'Leer = Standard aus den Clubdaten.', 'visible' => array( 'startform', 'tee_times' ) ),
			array( 'id' => 'grund', 'name' => 'Grund (öffentlich)', 'type' => 'text', 'std' => 'Turnier: {turnier}', 'desc' => '{turnier} wird durch den Turniernamen ersetzt. Der Turnierstart („Kanonenstart 16:30 Uhr“ bzw. „Erster Start 10:00 Uhr“) erscheint automatisch als eigene Zeile unter der Sperrzeit.' ),
		);
		$boxen[] = array(
			'id'             => 'tee-belegung-regeln',
			'title'          => 'Regeln',
			'settings_pages' => 'tee-belegung',
			'fields'         => array(
				array(
					'type' => 'custom_html',
					'std'  => '<p>Aus den Turnieren des Clubs (PC CADDIE) entstehen automatisch Sperrungen von Abschlag 1 und 10. Für jedes Turnier gilt die <strong>erste passende Regel</strong> (Reihenfolge per Ziehen ändern). Turniere ohne passende Regel oder ohne Uhrzeit sperren nichts. Ein einzelnes Turnier lässt sich am Turnier selbst abweichend einstellen (Turniere (PC CADDIE) → Turnier öffnen → „Tee-Belegung“).</p>',
				),
				array(
					'id'          => 'regeln',
					'type'        => 'group',
					'clone'       => true,
					'sort_clone'  => true,
					'collapsible' => true,
					'group_title' => '{name}',
					'add_button'  => '+ Regel hinzufügen',
					'fields'      => $regel,
				),
			),
		);
		$boxen[] = array(
			'id'             => 'clubdaten-startzeiten',
			'title'          => 'Clubdaten · Starts bei Turnieren',
			'settings_pages' => 'clubdaten',
			'tab'            => 'platz',
			'fields'         => array(
				array( 'type' => 'heading', 'name' => 'Starts bei Turnieren', 'desc' => 'Standardwerte für die automatische Tee-Belegung (Sperrungen → Turnier-Regeln). Eine Regel kann eigene Werte haben.' ),
				array( 'id' => 'startabstand', 'name' => 'Startabstand der Flights (Minuten)', 'type' => 'number', 'min' => 1, 'std' => 8 ),
				array( 'id' => 'flight_groesse', 'name' => 'Spieler je Flight', 'type' => 'number', 'min' => 1, 'max' => 4, 'std' => 3, 'desc' => 'So viele Spieler starten in der Regel zusammen (GC Dreibäumen: 3).' ),
				array( 'id' => 'spielzeit_loch', 'name' => 'Spielzeit je Loch beim Kanonenstart (Minuten)', 'type' => 'number', 'min' => 1, 'std' => 15, 'desc' => 'Daraus ergibt sich, wie lange ein Tee nach einem Kanonenstart belegt ist (9 Loch × 15 Min. = 2:15 h).' ),
				array( 'id' => 'turnier_puffer', 'name' => 'Turnierpuffer (Minuten)', 'type' => 'number', 'min' => 0, 'std' => 30, 'desc' => 'Wird an die Sperre des letzten Turniers angehängt. Spielen mehrere Turniere am selben Tee hintereinander, bekommt nur das letzte den Puffer; die Sperre davor reicht bis zum Beginn des nächsten. 0 = kein Puffer.' ),
			),
		);
		$boxen[] = array(
			'id'             => 'tee-belegung-vorschau',
			'title'          => 'Nächste Sperrungen durch Turniere',
			'settings_pages' => 'tee-belegung',
			'fields'         => array( array( 'type' => 'custom_html', 'callback' => 'golfplatz_tb_vorschau_html' ) ),
		);
		$boxen[] = array(
			'id'         => 'tee-belegung-turnier',
			'title'      => 'Tee-Belegung (nur dieses Turnier)',
			'post_types' => array( 'turnier' ),
			'context'    => 'normal',
			'fields'     => array(
				array( 'type' => 'custom_html', 'callback' => 'golfplatz_tb_turnier_html' ),
				array( 'id' => 'tb_startform', 'name' => 'Startform', 'type' => 'select', 'options' => array( '' => 'laut Regel' ) + GOLFPLATZ_TB_STARTFORMEN ),
				array( 'id' => 'tb_tee', 'name' => 'Start-Tee', 'type' => 'select', 'options' => array( '' => 'laut Regel' ) + GOLFPLATZ_TB_TEES ),
				array( 'id' => 'tb_vorlauf', 'name' => 'Gesperrt ab … Minuten vor dem Start', 'type' => 'number', 'min' => 0, 'step' => 5, 'desc' => 'Leer = laut Regel.' ),
				array( 'id' => 'tb_dauer', 'name' => 'Gesperrt bis … Minuten nach dem Start', 'type' => 'number', 'min' => 0, 'step' => 5, 'desc' => 'Leer = laut Regel bzw. automatisch.' ),
			),
		);
		return $boxen;
	}
);

/* ---------- Berechnung ---------- */

/** Erste passende Regel für ein Turnier (Name und Löcher). */
function golfplatz_tb_regel( string $titel, int $loecher, array $regeln ): ?array {
	foreach ( $regeln as $r ) {
		if ( ! empty( $r['loecher'] ) && (int) $r['loecher'] !== $loecher ) {
			continue;
		}
		$muster = array_filter( array_map( 'trim', explode( ',', (string) ( $r['muster'] ?? '' ) ) ) );
		if ( ! $muster ) {
			return $r;
		}
		foreach ( $muster as $m ) {
			if ( false !== mb_stripos( $titel, $m ) ) {
				return $r;
			}
		}
	}
	return null;
}

/**
 * Sperren eines Turniers: Liste aus [tee, beginn, ende, grund] (Ortszeit als Unix-Zeit wie alle Sperrungen)
 * und eine Erklärung für die Vorschau. Leere Liste = keine Sperre.
 */
function golfplatz_tb_plan( int $pid, ?array $regeln = null ): array {
	$regeln  = $regeln ?? (array) ( get_option( GOLFPLATZ_TB_OPTION, array() )['regeln'] ?? array() );
	$m       = fn( string $k ) => get_post_meta( $pid, $k, true );
	$titel   = html_entity_decode( get_post_field( 'post_title', $pid ) );
	$loecher = (int) $m( 'turnier_loecher' );
	$status  = golfplatz_turnier_status( $pid );
	$start   = $status['beginn'];
	if ( ! $status['findet_statt'] ) {
		return array( 'sperren' => array(), 'text' => $status['text'] . ' – keine Sperre' );
	}
	if ( ! $status['hat_uhrzeit'] ) {
		return array( 'sperren' => array(), 'text' => 'ohne Uhrzeit in PC CADDIE – keine Sperre' );
	}
	$regel = golfplatz_tb_regel( $titel, $loecher, $regeln );
	$eigen = array_filter( array( 'startform' => $m( 'tb_startform' ), 'tee' => $m( 'tb_tee' ), 'vorlauf' => $m( 'tb_vorlauf' ), 'dauer' => $m( 'tb_dauer' ) ), fn( $v ) => '' !== $v && null !== $v );
	if ( ! $regel && ! isset( $eigen['startform'] ) ) {
		return array( 'sperren' => array(), 'text' => 'keine passende Regel – keine Sperre' );
	}
	$r         = array_merge( array( 'name' => 'Ausnahme am Turnier', 'startform' => 'tee_times', 'tee' => '1', 'vorlauf' => 60, 'dauer' => '', 'intervall' => '', 'flight' => '', 'grund' => 'Turnier: {turnier}' ), (array) $regel, $eigen );
	$club      = golfplatz_tb_club();
	$woher     = ( $regel ? 'Regel „' . ( $r['name'] ?: $r['muster'] ) . '“' : 'Ausnahme am Turnier' ) . ( $eigen ? ' mit Ausnahme am Turnier' : '' );
	if ( 'keine' === $r['startform'] ) {
		return array( 'sperren' => array(), 'text' => $woher . ': keine Sperre' );
	}
	$tees  = 'beide' === $r['tee'] ? array( '1', '10' ) : array( in_array( (string) $r['tee'], array( '1', '10' ), true ) ? (string) $r['tee'] : '1' );
	$dauer = '' !== (string) $r['dauer'] ? (int) $r['dauer'] : 0;
	$info  = '';
	if ( ! $dauer ) {
		if ( 'kanonenstart' === $r['startform'] ) {
			$dauer = ( $loecher ?: 18 ) * $club['loch'];
			$info  = ( $loecher ?: 18 ) . ' Loch × ' . $club['loch'] . ' Min.';
		} else {
			$max   = (int) $m( 'turnier_teilnehmer_max' );
			$frei  = $m( 'turnier_plaetze_frei' );
			$n     = $max && '' !== $frei ? $max - (int) $frei : $max;
			$n     = $n > 0 ? $n : $max;
			$fl    = max( 1, (int) ( $r['flight'] ?: $club['flight'] ) );
			$iv    = max( 1, (int) ( $r['intervall'] ?: $club['intervall'] ) );
			$flights = $n ? (int) ceil( $n / $fl / count( $tees ) ) : 0;
			$dauer = $flights ? $flights * $iv : 120;
			$info  = $flights ? $n . ' Teilnehmer, ' . $flights . ' Flights je Tee à ' . $iv . ' Min.' : 'Teilnehmerzahl unbekannt, 2 Stunden';
		}
	}
	$uhr     = fn( int $ts ) => gmdate( 'H:i', $ts );
	// Turnierstart und Tee-Sperre sind zwei Angaben: Das Tee ist ab „vorlauf“ vor dem Start gesperrt.
	// Kanonenstart: gespielte Bahnen – 9 Loch ab Tee 1 = 1–9, ab Tee 10 = 10–18, sonst 1–18
	$form       = 'kanonenstart' === $r['startform']
		? 'Kanonenstart Tee ' . ( 9 === $loecher && 'beide' !== $r['tee'] ? ( '10' === $tees[0] ? '10–18' : '1–9' ) : '1–18' )
		: 'Erster Start' . ( 'beide' === $r['tee'] ? ' an Tee 1 und 10' : '' );
	$start_text = $form . ' · ' . $uhr( $start ) . ' Uhr';
	$grund      = str_replace( '{turnier}', $titel, (string) ( $r['grund'] ?: 'Turnier: {turnier}' ) );
	$beginn     = $start - (int) $r['vorlauf'] * MINUTE_IN_SECONDS;
	$ende       = $start + $dauer * MINUTE_IN_SECONDS;
	$sperren    = array_map( fn( $tee ) => array( 'tee' => $tee, 'beginn' => $beginn, 'ende' => $ende, 'grund' => $grund, 'start' => $start, 'start_text' => $start_text ), $tees );
	$text       = $woher . ': Turnierstart ' . $uhr( $start ) . ' Uhr (' . ( 'kanonenstart' === $r['startform'] ? $form : GOLFPLATZ_TB_STARTFORMEN[ $r['startform'] ] ) . ') · ' . GOLFPLATZ_TB_TEES[ 'beide' === $r['tee'] ? 'beide' : $tees[0] ] . ' gesperrt ' . $uhr( $beginn ) . '–' . $uhr( $ende ) . ' Uhr (' . (int) $r['vorlauf'] . ' Min. vor dem Start' . ( $info ? ', ' . $info : '' ) . ')';
	return array( 'sperren' => $sperren, 'text' => $text );
}

/**
 * Alle Sperren der kommenden Turniere mit Turnierpuffer: je Tee nach Beginn sortiert. Beginnt das nächste Turnier am selben Tee,
 * bevor Sperre + Puffer abgelaufen sind, spielen sie hintereinander – dann reicht die Sperre bis zum Beginn des nächsten und
 * nur das letzte Turnier der Kette bekommt den Puffer.
 *
 * @return array{sperren: array<string, array>, texte: array<int, string>} Sperren je Quelle „turnier:<ID>:<tee>“, Erklärung je Turnier.
 */
function golfplatz_tb_soll( ?array $regeln = null ): array {
	$puffer = golfplatz_tb_club()['puffer'] * MINUTE_IN_SECONDS;
	$texte  = array();
	$je_tee = array();
	foreach ( golfplatz_tb_turniere() as $tid ) {
		$plan          = golfplatz_tb_plan( $tid, $regeln );
		$texte[ $tid ] = $plan['text'];
		foreach ( $plan['sperren'] as $s ) {
			$je_tee[ $s['tee'] ][] = $s + array( 'tid' => $tid, 'titel' => html_entity_decode( get_post_field( 'post_title', $tid ) ) );
		}
	}
	$sperren = array();
	$zusatz  = array();
	foreach ( $je_tee as $tee => $liste ) {
		usort( $liste, fn( $a, $b ) => $a['beginn'] <=> $b['beginn'] );
		foreach ( $liste as $i => $s ) {
			$naechste = $liste[ $i + 1 ] ?? null;
			if ( $naechste && $naechste['beginn'] <= $s['ende'] + $puffer ) {
				$s['ende']                   = max( $s['ende'], $naechste['beginn'] );
				$zusatz[ $s['tid'] ][ $tee ] = 'Tee ' . $tee . ': kein Puffer, danach folgt „' . $naechste['titel'] . '“ (Sperre bis ' . gmdate( 'H:i', $s['ende'] ) . ' Uhr)';
			} elseif ( $puffer ) {
				$s['ende']                  += $puffer;
				$zusatz[ $s['tid'] ][ $tee ] = 'Tee ' . $tee . ': + ' . ( $puffer / MINUTE_IN_SECONDS ) . ' Min. Turnierpuffer bis ' . gmdate( 'H:i', $s['ende'] ) . ' Uhr';
			}
			$sperren[ 'turnier:' . $s['tid'] . ':' . $tee ] = $s;
		}
	}
	foreach ( $zusatz as $tid => $z ) {
		$texte[ $tid ] .= ' · ' . implode( ' · ', $z );
	}
	return array( 'sperren' => $sperren, 'texte' => $texte );
}

/** Kommende Turniere des Heimatclubs mit Uhrzeit ab heute. */
function golfplatz_tb_turniere(): array {
	$heute = strtotime( 'today', (int) current_time( 'timestamp' ) );
	$eigen = function_exists( 'golfplatz_pcc_club' ) ? golfplatz_pcc_club() : '';
	// Auch Turniere mit altem Datum in der Vergangenheit, die auf einen kommenden Termin verschoben sind
	$ids = get_posts(
		array(
			'post_type'      => 'turnier',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( array( 'key' => 'turnier_beginn', 'value' => $heute - 90 * DAY_IN_SECONDS, 'compare' => '>=', 'type' => 'NUMERIC' ) ),
		)
	);
	$ts  = array();
	foreach ( $ids as $id ) {
		if ( ( (string) get_post_meta( $id, 'turnier_club', true ) ?: $eigen ) === $eigen ) {
			$b = golfplatz_turnier_status( $id )['beginn'];
			if ( $b >= $heute ) {
				$ts[ $id ] = $b;
			}
		}
	}
	asort( $ts );
	return array_keys( $ts );
}

/**
 * Sperrungen abgleichen: erzeugen, anpassen, nicht mehr benötigte (kommende) in den Papierkorb.
 * Erkennung über sperr_quelle = „turnier:<ID>:<tee>“. Abgelaufene automatische Sperrungen nach 14 Tagen in den Papierkorb.
 */
function golfplatz_tb_abgleich(): array {
	$jetzt  = (int) current_time( 'timestamp' );
	$log    = array( 'neu' => 0, 'geaendert' => 0, 'entfernt' => 0 );
	$regeln = (array) ( get_option( GOLFPLATZ_TB_OPTION, array() )['regeln'] ?? array() );

	$vorhanden = array();
	foreach ( get_posts( array( 'post_type' => 'sperrung', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => 'sperr_quelle', 'meta_compare' => 'LIKE', 'meta_value' => 'turnier:' ) ) as $sid ) {
		if ( 'trash' !== get_post_status( $sid ) ) {
			$vorhanden[ (string) get_post_meta( $sid, 'sperr_quelle', true ) ] = $sid;
		}
	}

	$soll = golfplatz_tb_soll( $regeln )['sperren'];

	foreach ( $soll as $quelle => $s ) {
		$meta = array( 'sperr_bereich' => 'abschlag_' . $s['tee'], 'sperr_beginn' => $s['beginn'], 'sperr_ende' => $s['ende'], 'sperr_grund' => $s['grund'], 'sperr_turnierstart' => $s['start'], 'sperr_start_text' => $s['start_text'], 'sperr_quelle' => $quelle );
		$sid  = $vorhanden[ $quelle ] ?? 0;
		unset( $vorhanden[ $quelle ] );
		$titel = $s['titel'] . ' – Tee ' . $s['tee'] . ' (automatisch)';
		if ( $sid ) {
			$alt = array_map( fn( $k ) => (string) get_post_meta( $sid, $k, true ), array_keys( $meta ) );
			if ( array_combine( array_keys( $meta ), $alt ) == array_map( 'strval', $meta ) && get_post_field( 'post_title', $sid ) === $titel && 'publish' === get_post_status( $sid ) ) {
				continue;
			}
			wp_update_post( array( 'ID' => $sid, 'post_title' => $titel, 'post_status' => 'publish' ) );
			++$log['geaendert'];
		} else {
			$sid = wp_insert_post( array( 'post_type' => 'sperrung', 'post_status' => 'publish', 'post_title' => $titel ) );
			if ( ! $sid || is_wp_error( $sid ) ) {
				continue;
			}
			++$log['neu'];
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $sid, $k, $v );
		}
	}

	// Übrig: Turnier abgesagt, Regel geändert oder abgelaufen
	foreach ( $vorhanden as $sid ) {
		$ende = (int) get_post_meta( $sid, 'sperr_ende', true );
		if ( $ende > $jetzt || $ende < $jetzt - 14 * DAY_IN_SECONDS ) {
			wp_trash_post( $sid );
			++$log['entfernt'];
		}
	}
	update_option( 'golfplatz_tb_log', $log + array( 'zeit' => time() ), false );
	return $log;
}

// Auslöser: nach dem PC-CADDIE-Abgleich (stündlich und von Hand), nach dem Speichern der Regeln oder eines Turniers.
add_action( 'golfplatz_pcc_nach_abgleich', 'golfplatz_tb_abgleich' );
add_action( 'add_option_' . GOLFPLATZ_TB_OPTION, 'golfplatz_tb_abgleich' );
add_action(
	'rwmb_after_save_post',
	function ( $id ) {
		// Einstellungsseiten übergeben den Optionsnamen (Regeln, Clubdaten mit den Standardwerten), Beiträge ihre ID
		if ( in_array( $id, array( GOLFPLATZ_TB_OPTION, 'clubdaten' ), true ) || ( is_numeric( $id ) && 'turnier' === get_post_type( (int) $id ) ) ) {
			golfplatz_tb_abgleich();
		}
	}
);

/* ---------- Anzeige im Backend ---------- */

function golfplatz_tb_vorschau_html(): string {
	$bis   = strtotime( 'today', (int) current_time( 'timestamp' ) ) + 29 * DAY_IN_SECONDS;
	$zeilen = '';
	foreach ( golfplatz_tb_soll()['texte'] as $tid => $text ) {
		$st = golfplatz_turnier_status( $tid );
		$ts = $st['beginn'];
		if ( $ts > $bis ) {
			break;
		}
		$zeilen .= '<tr><td>' . esc_html( wp_date( 'D, d.m.', $ts, new DateTimeZone( 'UTC' ) ) . ( $st['hat_uhrzeit'] ? ' ' . gmdate( 'H:i', $ts ) : '' ) ) . '</td><td><a href="' . esc_url( get_edit_post_link( $tid ) ) . '">' . esc_html( html_entity_decode( get_the_title( $tid ) ) ) . '</a></td><td>' . esc_html( $text ) . '</td></tr>';
	}
	$log  = (array) get_option( 'golfplatz_tb_log', array() );
	$info = $log ? '<p>Zuletzt berechnet: ' . esc_html( wp_date( 'd.m.Y H:i', (int) $log['zeit'] ) ) . ' – ' . (int) $log['neu'] . ' neu, ' . (int) $log['geaendert'] . ' geändert, ' . (int) $log['entfernt'] . ' entfernt. Die Sperrungen stehen unter „Alle Sperrungen“ mit dem Zusatz „(automatisch)“.</p>' : '';
	return '<p>Turniere der nächsten 4 Wochen und was sie sperren (Stand der gespeicherten Regeln):</p>'
		. ( $zeilen ? '<table class="widefat striped"><thead><tr><th>Termin</th><th>Turnier</th><th>Tee-Belegung</th></tr></thead><tbody>' . $zeilen . '</tbody></table>' : '<p>Keine Turniere in den nächsten 4 Wochen.</p>' ) . $info;
}

function golfplatz_tb_turnier_html(): string {
	$id = (int) ( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $id ) {
		return '';
	}
	$eigen = function_exists( 'golfplatz_pcc_club' ) ? golfplatz_pcc_club() : '';
	if ( ( (string) get_post_meta( $id, 'turnier_club', true ) ?: $eigen ) !== $eigen ) {
		return '<p>Turnier eines Partnerclubs – sperrt keine Abschläge.</p>';
	}
	return '<p><strong>Aktuell:</strong> ' . esc_html( golfplatz_tb_soll()['texte'][ $id ] ?? golfplatz_tb_plan( $id )['text'] ) . '</p><p class="description">Nur ausfüllen, wenn dieses Turnier von der Regel abweicht (z. B. Start an Tee 10). Regeln: <a href="' . esc_url( admin_url( 'edit.php?post_type=sperrung&page=tee-belegung' ) ) . '">Sperrungen → Turnier-Regeln</a>.</p>';
}

// Hinweis an automatisch erzeugten Sperrungen: Änderungen gehören an Regel oder Turnier.
add_action(
	'admin_notices',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'sperrung' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}
		$quelle = (string) get_post_meta( (int) ( $_GET['post'] ?? 0 ), 'sperr_quelle', true ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( preg_match( '/^turnier:(\d+):/', $quelle, $t ) ) {
			$start = (string) get_post_meta( (int) ( $_GET['post'] ?? 0 ), 'sperr_start_text', true ); // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-info"><p>Diese Sperrung entsteht automatisch aus dem Turnier <a href="' . esc_url( get_edit_post_link( (int) $t[1] ) ) . '">' . esc_html( html_entity_decode( get_the_title( (int) $t[1] ) ) ) . '</a>' . ( $start ? ' (' . esc_html( $start ) . '; Beginn und Ende unten sind die Sperrzeit des Tees)' : '' ) . '. Änderungen hier werden bei der nächsten Berechnung überschrieben – bitte die <a href="' . esc_url( admin_url( 'edit.php?post_type=sperrung&page=tee-belegung' ) ) . '">Turnier-Regeln</a> oder die Tee-Belegung am Turnier ändern.</p></div>';
		}
	}
);
