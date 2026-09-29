<?php
/**
 * Plugin Name: Golfplatz Platzstatus
 * Description: Berechnet Platzstatus, Fahnenpositionen und Öffnungszeiten aus Sperrungen, der Einstellungsseite „Platzstatus“ und den Clubdaten und stellt sie Etch als dynamische Daten bereit ({options.golfplatz.platzstatus.…}, {options.golfplatz.zeiten}). Keine Shortcodes: das Markup bauen die Etch-Komponenten (wordpress/etch/platzstatus.mjs, komponenten.mjs).
 * Version: 1.0.0
 *
 * Quelle: Repository golfplatz, wordpress/snippets/golfplatz-platzstatus.php
 *
 * Zeiten: Meta Box speichert datetime-Felder mit timestamp=true als „Ortszeit als Unix-Zeit“.
 * Deshalb wird überall mit current_time( 'timestamp' ) verglichen und mit gmdate()/date_i18n() formatiert.
 */

defined( 'ABSPATH' ) || exit;

define( 'GOLFPLATZ_BEREICHE', array(
	'abschlag_1'  => 'Abschlag 1',
	'abschlag_10' => 'Abschlag 10',
	'platz'       => 'Ganzer Platz',
	'range'       => 'Driving Range',
	'kurzspiel'   => 'Kurzspielbereich',
	'proshop'     => 'Proshop',
	'trolley'     => 'Trolleys',
	'buggy'       => 'Buggies / E-Carts',
) );
define( 'GOLFPLATZ_PLATZ', array( 'abschlag_1', 'abschlag_10', 'platz' ) );
define( 'GOLFPLATZ_EINRICHTUNGEN', array( 'range', 'kurzspiel', 'proshop' ) );

/**
 * Liest Sperrungen und Schnellsperren und bereitet sie auf.
 *
 * @return array{jetzt:int, liste:array, schnell:array, gruens:string, gruens_hinweis:string}
 */
function golfplatz_platzstatus_daten(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$jetzt      = (int) current_time( 'timestamp' );
	$morgen_end = strtotime( 'today', $jetzt ) + 2 * DAY_IN_SECONDS;

	$posts = get_posts(
		array(
			'post_type'      => 'sperrung',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => 'sperr_ende', 'value' => $jetzt, 'compare' => '>', 'type' => 'NUMERIC' ),
				array( 'key' => 'sperr_beginn', 'value' => $morgen_end, 'compare' => '<', 'type' => 'NUMERIC' ),
			),
		)
	);
	$liste = array();
	foreach ( $posts as $p ) {
		$bereich = (string) get_post_meta( $p->ID, 'sperr_bereich', true );
		if ( ! isset( GOLFPLATZ_BEREICHE[ $bereich ] ) ) {
			continue;
		}
		$liste[] = array(
			'bereich' => $bereich,
			'beginn'  => (int) get_post_meta( $p->ID, 'sperr_beginn', true ),
			'ende'    => (int) get_post_meta( $p->ID, 'sperr_ende', true ),
			'grund'   => (string) get_post_meta( $p->ID, 'sperr_grund', true ),
			// Nur bei Sperrungen aus Turnieren (golfplatz-tee-belegung.php): Turnierstart, getrennt von der Sperrzeit
			'start'   => (string) get_post_meta( $p->ID, 'sperr_start_text', true ),
			'schnell' => false,
		);
	}
	usort( $liste, fn( $a, $b ) => $a['beginn'] <=> $b['beginn'] );

	// Schnellsperren von der Einstellungsseite „Platzstatus“.
	$ps      = (array) get_option( 'platzstatus', array() );
	$schnell = array();
	$felder  = array(
		'platz'     => array( 'platz_gesperrt', 'platz_sperrgrund', 'platz_gesperrt_bis' ),
		'range'     => array( 'range_gesperrt', 'range_sperrgrund', 'range_gesperrt_bis' ),
		'kurzspiel' => array( 'kurzspiel_gesperrt', 'kurzspiel_sperrgrund', 'kurzspiel_gesperrt_bis' ),
		'proshop'   => array( 'proshop_gesperrt', 'proshop_sperrgrund', 'proshop_gesperrt_bis' ),
		'trolley'   => array( 'trolley_gesperrt', 'trolley_grund', 'trolley_bis' ),
		'buggy'     => array( 'buggy_gesperrt', 'buggy_grund', 'buggy_bis' ),
	);
	foreach ( $felder as $bereich => list( $schalter, $grund, $bis ) ) {
		$ende = (int) ( $ps[ $bis ] ?? 0 );
		if ( empty( $ps[ $schalter ] ) || ( $ende && $ende <= $jetzt ) ) {
			continue; // Aus, oder das eingetragene Ende ist vorbei.
		}
		$schnell[ $bereich ] = array(
			'bereich' => $bereich,
			'beginn'  => $jetzt,
			'ende'    => $ende ?: null,
			'grund'   => (string) ( $ps[ $grund ] ?? '' ) ?: 'Gesperrt',
			'schnell' => true,
		);
	}

	$cache = array(
		'jetzt'          => $jetzt,
		'liste'          => $liste,
		'schnell'        => $schnell,
		'gruens'         => ( $ps['gruens'] ?? 'sommer' ) === 'winter' ? 'winter' : 'sommer',
		'gruens_hinweis' => (string) ( $ps['gruens_hinweis'] ?? '' ),
		// Gespielte Abschläge, z. B. „Gelb, Rot“ (Checkbox-Liste auf der Seite „Platzstatus“).
		'abschlaege'     => implode( ', ', array_map( 'ucfirst', array_intersect( array( 'gelb', 'blau', 'rot', 'orange' ), (array) ( $ps['abschlaege_offen'] ?? array() ) ) ) ),
	);
	return $cache;
}

/** Aktive Sperre eines Bereichs: Schnellsperre vor geplanter Sperrung. */
function golfplatz_aktive_sperre( array $d, string $bereich ): ?array {
	if ( isset( $d['schnell'][ $bereich ] ) ) {
		return $d['schnell'][ $bereich ];
	}
	foreach ( $d['liste'] as $s ) {
		if ( $s['bereich'] === $bereich && $s['beginn'] <= $d['jetzt'] && $s['ende'] > $d['jetzt'] ) {
			return $s;
		}
	}
	return null;
}

/** Sperren der Bereiche, die am Tag (0 = heute, 1 = morgen) noch anstehen. Schnellsperren zuerst. */
function golfplatz_sperren_am_tag( array $d, array $bereiche, int $versatz ): array {
	$tag     = strtotime( 'today', $d['jetzt'] ) + $versatz * DAY_IN_SECONDS;
	$tag_end = $tag + DAY_IN_SECONDS;
	$out     = array();
	foreach ( $bereiche as $b ) {
		$s = $d['schnell'][ $b ] ?? null;
		if ( $s && ( ! $s['ende'] || $s['ende'] > $tag ) && $s['beginn'] < $tag_end ) {
			$out[] = $s;
		}
	}
	foreach ( $d['liste'] as $s ) {
		if ( in_array( $s['bereich'], $bereiche, true ) && $s['beginn'] < $tag_end && $s['ende'] > $tag && $s['ende'] > $d['jetzt'] ) {
			$out[] = $s;
		}
	}
	return $out;
}

function golfplatz_uhr( int $ts ): string {
	return gmdate( 'H:i', $ts );
}

function golfplatz_bis_text( array $s ): string {
	return $s['ende'] ? ' bis ' . golfplatz_uhr( $s['ende'] ) . ' Uhr' : '';
}

function golfplatz_zeitraum( array $s, int $tag ): string {
	$tag_end = $tag + DAY_IN_SECONDS;
	$von     = $s['beginn'] < $tag ? 'ab ' . gmdate( 'd.m.', $s['beginn'] ) . ' ' . golfplatz_uhr( $s['beginn'] ) : golfplatz_uhr( $s['beginn'] );
	if ( ! $s['ende'] ) {
		return 'seit ' . golfplatz_uhr( $s['beginn'] ) . ' Uhr, bis auf Weiteres';
	}
	$bis = $s['ende'] > $tag_end ? gmdate( 'd.m.', $s['ende'] ) . ' ' . golfplatz_uhr( $s['ende'] ) : golfplatz_uhr( $s['ende'] );
	return $von . '–' . $bis . ' Uhr';
}


/**
 * Zustand für Ampel und Kurzfassung: open / restricted / closed, Text und Zusätze.
 *
 * @return array{zustand:string, text:string, extra:string[]}
 */
function golfplatz_ampel_zustand(): array {
	$d       = golfplatz_platzstatus_daten();
	$platz   = golfplatz_aktive_sperre( $d, 'platz' );
	$abschl  = array_filter( array( golfplatz_aktive_sperre( $d, 'abschlag_1' ), golfplatz_aktive_sperre( $d, 'abschlag_10' ) ) );
	if ( $platz ) {
		$zustand = 'closed';
		$text    = 'Platz gesperrt' . ( $platz['schnell'] ? ': ' . $platz['grund'] : golfplatz_bis_text( $platz ) );
	} elseif ( $abschl ) {
		$zustand = 'restricted';
		$text    = implode( ' · ', array_map( fn( $s ) => GOLFPLATZ_BEREICHE[ $s['bereich'] ] . ' gesperrt' . golfplatz_bis_text( $s ), $abschl ) );
	} else {
		$zustand = 'open';
		$text    = 'Platz geöffnet';
	}
	$extra = array();
	if ( 'winter' === $d['gruens'] ) {
		$extra[] = 'Wintergrüns';
	}
	if ( golfplatz_aktive_sperre( $d, 'trolley' ) ) {
		$extra[] = 'Trolley-Verbot';
	}
	if ( golfplatz_aktive_sperre( $d, 'buggy' ) ) {
		$extra[] = 'Buggy-Verbot';
	}
	if ( 'open' === $zustand && $extra ) {
		$zustand = 'restricted';
	}
	return array( 'zustand' => $zustand, 'text' => $text, 'extra' => $extra );
}


/*
 * ---------------------------------------------------------------------------
 * Fahnenpositionen: Jedes Grün hat 6 nummerierte Positionen (1–6).
 * Gesteckt wird eine Position für alle Grüns (Standard), einzelne Grüns können abweichen (Ausnahmen).
 * Daten: Einstellungsseite „Platzstatus“, Steckpläne pin_plaene[gueltig_ab, position, hinweis, ausnahmen[bahn, position]].
 * Es gilt der neueste Plan, dessen Datum erreicht ist; Pläne für spätere Tage können vorab eingetragen werden.
 * Wo die Positionen auf einem Grün liegen, steht an der Spielbahn (pin_positionen[position, tiefe, seite, meter]).
 * „Vorne“ ist die dem Spieler zugewandte Seite des Grüns, „links/rechts“ aus Sicht des Spielers.
 * ---------------------------------------------------------------------------
 */

define( 'GOLFPLATZ_PIN_TIEFE', array( 'vorne' => 'vorne', 'mitte' => 'Mitte', 'hinten' => 'hinten' ) );
define( 'GOLFPLATZ_PIN_SEITE', array( 'links' => 'links', 'mitte' => 'Mitte', 'rechts' => 'rechts' ) );

/**
 * Datum als „JJJJ-MM-TT“ oder null. Meta Box speichert Datumsfelder in Gruppen im Anzeigeformat („25.09.2026“),
 * außerhalb im Speicherformat („2026-09-25“); beides wird angenommen, dazu Unix-Zeitstempel.
 */
function golfplatz_datum_iso( $wert ): ?string {
	$wert = trim( (string) $wert );
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $wert, $m ) ) {
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $wert : null;
	}
	if ( preg_match( '/^(\d{1,2})\.(\d{1,2})\.(\d{2}|\d{4})$/', $wert, $m ) ) {
		$jahr = 2 === strlen( $m[3] ) ? 2000 + (int) $m[3] : (int) $m[3];
		return checkdate( (int) $m[2], (int) $m[1], $jahr ) ? sprintf( '%04d-%02d-%02d', $jahr, (int) $m[2], (int) $m[1] ) : null;
	}
	if ( ctype_digit( $wert ) && strlen( $wert ) >= 9 ) {
		return gmdate( 'Y-m-d', (int) $wert );
	}
	return null;
}

/** Positionsnummer 1–6 oder null. */
function golfplatz_pin_nr( $wert ): ?int {
	$n = (int) $wert;
	return $n >= 1 && $n <= 6 ? $n : null;
}

/**
 * Gültiger Steckplan: der neueste Plan, dessen Datum („gültig ab“, 0 Uhr Ortszeit) erreicht ist.
 * Pläne für spätere Tage bleiben unsichtbar, bis ihr Datum beginnt. Null, wenn kein Plan gilt.
 *
 * @return array{datum:string, standard:int, hinweis:string, ausnahmen:array<int,int>}|null
 */
function golfplatz_fahnen(): ?array {
	static $cache = false;
	if ( false !== $cache ) {
		return $cache;
	}
	$ps    = (array) get_option( 'platzstatus', array() );
	$heute = gmdate( 'Y-m-d', (int) current_time( 'timestamp' ) );
	$plan  = null;
	foreach ( (array) ( $ps['pin_plaene'] ?? array() ) as $p ) {
		$datum    = golfplatz_datum_iso( $p['gueltig_ab'] ?? '' );
		$standard = golfplatz_pin_nr( $p['position'] ?? 0 );
		if ( ! $datum || ! $standard || $datum > $heute ) {
			continue;
		}
		// Neuestes Datum gewinnt; bei gleichem Datum der zuletzt eingetragene Plan
		if ( ! $plan || $datum >= $plan['datum'] ) {
			$plan = array( 'datum' => $datum, 'standard' => $standard, 'roh' => $p );
		}
	}
	if ( ! $plan ) {
		return $cache = null;
	}
	$ausnahmen = array();
	foreach ( (array) ( $plan['roh']['ausnahmen'] ?? array() ) as $a ) {
		$bahn = (int) ( $a['bahn'] ?? 0 );
		$pos  = golfplatz_pin_nr( $a['position'] ?? 0 );
		if ( $bahn >= 1 && $bahn <= 18 && $pos && $pos !== $plan['standard'] ) {
			$ausnahmen[ $bahn ] = $pos;
		}
	}
	ksort( $ausnahmen );
	return $cache = array(
		'datum'     => $plan['datum'],
		'standard'  => $plan['standard'],
		'hinweis'   => (string) ( $plan['roh']['hinweis'] ?? '' ),
		'ausnahmen' => $ausnahmen,
	);
}

/**
 * Lage der 6 Positionen je Grün aus den Spielbahnen: [bahn => [position => [tiefe, seite, meter]]].
 */
function golfplatz_pin_lagen(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	foreach ( get_posts( array( 'post_type' => 'spielbahn', 'post_status' => 'publish', 'posts_per_page' => 18, 'fields' => 'ids', 'no_found_rows' => true ) ) as $id ) {
		$bahn = (int) get_post_meta( $id, 'bahn_nummer', true );
		foreach ( (array) get_post_meta( $id, 'pin_positionen', true ) as $p ) {
			$pos = golfplatz_pin_nr( $p['position'] ?? 0 );
			if ( $bahn && $pos ) {
				$cache[ $bahn ][ $pos ] = array(
					'tiefe' => isset( GOLFPLATZ_PIN_TIEFE[ $p['tiefe'] ?? '' ] ) ? $p['tiefe'] : 'mitte',
					'seite' => isset( GOLFPLATZ_PIN_SEITE[ $p['seite'] ?? '' ] ) ? $p['seite'] : 'mitte',
					'meter' => '' === (string) ( $p['meter'] ?? '' ) ? null : max( 0, (int) $p['meter'] ),
				);
			}
		}
	}
	return $cache;
}

/** Heute gesteckte Position einer Bahn: Nummer, Lage (falls an der Bahn gepflegt) und ob sie vom Standard abweicht. */
function golfplatz_fahne_bahn( int $nr ): ?array {
	$f = golfplatz_fahnen();
	if ( ! $f ) {
		return null;
	}
	$pos = $f['ausnahmen'][ $nr ] ?? $f['standard'];
	return array(
		'position' => $pos,
		'lage'     => golfplatz_pin_lagen()[ $nr ][ $pos ] ?? null,
		'ausnahme' => isset( $f['ausnahmen'][ $nr ] ),
	);
}

/** Lage als Text: „hinten rechts“, „Mitte“, „vorne“, „Mitte links“ – optional mit Metern ab Grünanfang. */
function golfplatz_pin_lage_text( ?array $lage ): string {
	if ( ! $lage ) {
		return '';
	}
	if ( 'mitte' === $lage['tiefe'] && 'mitte' === $lage['seite'] ) {
		$text = 'Mitte';
	} elseif ( 'mitte' === $lage['seite'] ) {
		$text = GOLFPLATZ_PIN_TIEFE[ $lage['tiefe'] ];
	} else {
		$text = GOLFPLATZ_PIN_TIEFE[ $lage['tiefe'] ] . ' ' . GOLFPLATZ_PIN_SEITE[ $lage['seite'] ];
	}
	if ( null !== $lage['meter'] ) {
		$text .= ', ' . $lage['meter'] . ' m ab Grünanfang';
	}
	return $text;
}

/** „Position 3 · hinten rechts“ bzw. „Position 3“, wenn die Lage an der Bahn nicht gepflegt ist. */
function golfplatz_fahne_text( array $p ): string {
	$lage = golfplatz_pin_lage_text( $p['lage'] ?? null );
	return 'Position ' . $p['position'] . ( $lage ? ' · ' . $lage : '' );
}


/*
 * ---------------------------------------------------------------------------
 * Öffnungszeiten: Standard je Wochentag plus Ausnahmen für einen Zeitraum.
 * Daten: Clubdaten › Öffnungszeiten (zeiten_<bereich>_standard / _ausnahmen / _hinweis).
 * Logik wie prototype/assets/js/zeiten.js.
 * ---------------------------------------------------------------------------
 */

define( 'GOLFPLATZ_ZEITEN_BEREICHE', array(
	'sekretariat' => 'Sekretariat',
	'range'       => 'Driving Range',
	'kurzspiel'   => 'Kurzspielbereich & Putting-Grün',
	'proshop'     => 'Proshop',
	'restaurant'  => 'Clubrestaurant',
) );
define( 'GOLFPLATZ_WOCHE', array( 'mo', 'di', 'mi', 'do', 'fr', 'sa', 'so' ) );

/** Bereich aus den Clubdaten, normalisiert. */
function golfplatz_zeiten_bereich( string $key ): array {
	$opt     = (array) get_option( 'clubdaten', array() );
	$zeile   = fn( $z ) => array(
		'tage' => array_values( array_intersect( GOLFPLATZ_WOCHE, (array) ( $z['tage'] ?? array() ) ) ),
		'von'  => substr( (string) ( $z['von'] ?? '' ), 0, 5 ),
		'bis'  => substr( (string) ( $z['bis'] ?? '' ), 0, 5 ),
	);
	$gueltig  = fn( $z ) => $z['tage'] && $z['von'] && $z['bis'];
	$standard = array_values( array_filter( array_map( $zeile, (array) ( $opt[ "zeiten_{$key}_standard" ] ?? array() ) ), $gueltig ) );
	$ausnahmen = array();
	foreach ( (array) ( $opt[ "zeiten_{$key}_ausnahmen" ] ?? array() ) as $a ) {
		if ( empty( $a['von'] ) ) {
			continue;
		}
		$ausnahmen[] = array(
			'titel'       => (string) ( $a['titel'] ?? '' ),
			'von'         => (string) $a['von'],
			'bis'         => ! empty( $a['bis'] ) ? (string) $a['bis'] : (string) $a['von'],
			'geschlossen' => ! empty( $a['geschlossen'] ),
			'zeiten'      => array_values( array_filter( array_map( $zeile, (array) ( $a['zeiten'] ?? array() ) ), $gueltig ) ),
		);
	}
	return array(
		'name'      => GOLFPLATZ_ZEITEN_BEREICHE[ $key ] ?? $key,
		'standard'  => $standard,
		'ausnahmen' => $ausnahmen,
		'hinweis'   => (string) ( $opt[ "zeiten_{$key}_hinweis" ] ?? '' ),
	);
}

/** „Mo–Fr“, „Sa, So“, „täglich“ */
function golfplatz_tage_text( array $tage ): string {
	$kurz = array( 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So' );
	$idx  = array_values( array_keys( array_intersect( GOLFPLATZ_WOCHE, $tage ) ) );
	$n    = count( $idx );
	if ( 7 === $n ) {
		return 'täglich';
	}
	$teile = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$start = $idx[ $i ];
		while ( $i + 1 < $n && $idx[ $i + 1 ] === $idx[ $i ] + 1 ) {
			$i++;
		}
		$ende = $idx[ $i ];
		if ( $ende - $start >= 2 ) {
			$teile[] = $kurz[ $start ] . '–' . $kurz[ $ende ];
		} else {
			for ( $k = $start; $k <= $ende; $k++ ) {
				$teile[] = $kurz[ $k ];
			}
		}
	}
	return implode( ', ', $teile );
}

function golfplatz_minuten( string $hhmm ): int {
	return (int) substr( $hhmm, 0, 2 ) * 60 + (int) substr( $hhmm, 3, 2 );
}

/** Zeitspannen an einem Tag (Ortszeit-Timestamp). Eine Ausnahme ersetzt den Standard. */
function golfplatz_zeiten_am( array $bereich, int $ts ): array {
	$tag      = gmdate( 'Y-m-d', $ts );
	$ausnahme = null;
	foreach ( $bereich['ausnahmen'] as $a ) {
		if ( $a['von'] <= $tag && $tag <= $a['bis'] ) {
			$ausnahme = $a; // Die zuletzt eingetragene gewinnt.
		}
	}
	$quelle  = $ausnahme ? ( $ausnahme['geschlossen'] ? array() : $ausnahme['zeiten'] ) : $bereich['standard'];
	$wt      = GOLFPLATZ_WOCHE[ (int) gmdate( 'N', $ts ) - 1 ];
	$spannen = array();
	foreach ( $quelle as $z ) {
		if ( in_array( $wt, $z['tage'], true ) ) {
			$spannen[] = array( 'von' => $z['von'], 'bis' => $z['bis'] );
		}
	}
	usort( $spannen, fn( $x, $y ) => golfplatz_minuten( $x['von'] ) <=> golfplatz_minuten( $y['von'] ) );
	return array( 'ausnahme' => $ausnahme, 'spannen' => $spannen );
}

/** Zustand jetzt oder null, wenn für den Bereich keine Zeiten gepflegt sind. */
function golfplatz_zeiten_status( string $key, int $jetzt ): ?array {
	$b = golfplatz_zeiten_bereich( $key );
	if ( ! $b['standard'] && ! $b['ausnahmen'] ) {
		return null;
	}
	$min = (int) gmdate( 'G', $jetzt ) * 60 + (int) gmdate( 'i', $jetzt );
	foreach ( golfplatz_zeiten_am( $b, $jetzt )['spannen'] as $s ) {
		if ( golfplatz_minuten( $s['von'] ) <= $min && $min < golfplatz_minuten( $s['bis'] ) ) {
			return array( 'offen' => true, 'bis' => $s['bis'], 'naechste' => null );
		}
	}
	for ( $i = 0; $i < 14; $i++ ) {
		$ts = $jetzt + $i * DAY_IN_SECONDS;
		foreach ( golfplatz_zeiten_am( $b, $ts )['spannen'] as $s ) {
			if ( $i > 0 || golfplatz_minuten( $s['von'] ) > $min ) {
				return array( 'offen' => false, 'bis' => null, 'naechste' => array( 'tage' => $i, 'ts' => $ts, 'von' => $s['von'] ) );
			}
		}
	}
	return array( 'offen' => false, 'bis' => null, 'naechste' => null );
}

function golfplatz_zeiten_status_text( array $st ): string {
	if ( $st['offen'] ) {
		return 'Jetzt geöffnet bis ' . $st['bis'] . ' Uhr';
	}
	if ( ! $st['naechste'] ) {
		return 'Geschlossen';
	}
	$n    = $st['naechste'];
	$wann = 0 === $n['tage'] ? 'heute' : ( 1 === $n['tage'] ? 'morgen' : 'am ' . date_i18n( 'l', $n['ts'] ) );
	return 'Geschlossen · öffnet ' . $wann . ' um ' . $n['von'] . ' Uhr';
}

function golfplatz_datum_text( string $iso ): string {
	$p = explode( '-', $iso );
	return 3 === count( $p ) ? "{$p[2]}.{$p[1]}.{$p[0]}" : $iso;
}


/** Ampel für Top-Bar und App-Leiste: {options.golfplatz.platzstatus.ampel.zustand|text} */
function golfplatz_ampel_etch(): array {
	$a = golfplatz_ampel_zustand();
	return array( 'zustand' => $a['zustand'], 'text' => implode( ' · ', array_merge( array( $a['text'] ), $a['extra'] ) ) );
}

/** Kurzfassung für den Hero der Startseite: {options.golfplatz.platzstatus.kurz.…} */
function golfplatz_status_kurz_etch(): array {
	$d      = golfplatz_platzstatus_daten();
	$a      = golfplatz_ampel_zustand();
	$tag    = strtotime( 'today', $d['jetzt'] );
	$heute  = golfplatz_sperren_am_tag( $d, GOLFPLATZ_PLATZ, 0 );
	$winter = 'winter' === $d['gruens'];
	$chips  = array(
		array( 'mod' => $winter ? 'winter' : 'ok', 'text' => $winter ? 'Wintergrüns' : 'Sommergrüns' ),
	);
	if ( $d['abschlaege'] ) {
		$chips[] = array( 'mod' => 'ok', 'text' => 'Abschläge ' . $d['abschlaege'] );
	}
	$chips[] = golfplatz_aktive_sperre( $d, 'trolley' ) ? array( 'mod' => 'blocked', 'text' => 'Trolleys gesperrt' ) : array( 'mod' => 'ok', 'text' => 'Trolleys erlaubt' );
	$chips[] = golfplatz_aktive_sperre( $d, 'buggy' ) ? array( 'mod' => 'blocked', 'text' => 'Buggies gesperrt' ) : array( 'mod' => 'ok', 'text' => 'Buggies erlaubt' );
	$f = golfplatz_fahnen();
	if ( $f ) {
		// Ein Chip für den Standard („Fahnen Position 2“), je Ausnahme ein eigener („Bahn 6: Fahne Position 4“)
		$chips[] = array( 'mod' => 'ok', 'text' => 'Fahnen Position ' . $f['standard'] );
		foreach ( $f['ausnahmen'] as $bahn => $pos ) {
			$chips[] = array( 'mod' => 'pin', 'text' => 'Bahn ' . $bahn . ': Fahne Position ' . $pos );
		}
	}
	return array(
		'zustand'   => $a['zustand'],
		'text'      => $a['text'],
		'hat_heute' => (bool) $heute,
		'heute'     => array_map(
			fn( $s ) => array(
				'bereich' => GOLFPLATZ_BEREICHE[ $s['bereich'] ],
				'text'    => golfplatz_zeitraum( $s, $tag ) . ( ! empty( $s['start'] ) ? ' gesperrt · ' . $s['grund'] . ' · ' . $s['start'] : ' · ' . $s['grund'] ),
				'klasse'  => 'platz' === $s['bereich'] ? 'status-summary__item--platz' : '',
			),
			array_slice( $heute, 0, 3 )
		),
		'mehr'      => count( $heute ) > 3 ? '+ ' . ( count( $heute ) - 3 ) . ' weitere' : '',
		'chips'     => $chips,
	);
}

/**
 * Öffnungszeiten aller Bereiche für Etch: {options.golfplatz.zeiten} (Liste, Reihenfolge wie GOLFPLATZ_ZEITEN_BEREICHE).
 * Die Komponente „Öffnungszeiten“ zeigt daraus den Bereich mit key === props.bereich.
 */
function golfplatz_zeiten_etch(): array {
	$d     = golfplatz_platzstatus_daten();
	$j     = $d['jetzt'];
	$heute = gmdate( 'Y-m-d', $j );
	$liste = array();
	foreach ( array_keys( GOLFPLATZ_ZEITEN_BEREICHE ) as $key ) {
		$b      = golfplatz_zeiten_bereich( $key );
		$sperre = in_array( $key, GOLFPLATZ_EINRICHTUNGEN, true ) ? golfplatz_aktive_sperre( $d, $key ) : null;
		$st     = golfplatz_zeiten_status( $key, $j );
		if ( $sperre ) {
			$status = array( 'blocked', ( 'proshop' === $key ? 'Geschlossen' : 'Gesperrt' ) . golfplatz_bis_text( $sperre ) . ' – ' . $sperre['grund'] );
		} elseif ( $st ) {
			$status = array( $st['offen'] ? 'open' : 'closed', golfplatz_zeiten_status_text( $st ) );
		} else {
			$status = array( '', '' );
		}
		$kommende = array_values( array_filter( $b['ausnahmen'], fn( $a ) => $a['bis'] >= $heute ) );
		usort( $kommende, fn( $x, $y ) => strcmp( $x['von'], $y['von'] ) );
		$liste[] = array(
			'key'           => $key,
			'name'          => $b['name'],
			'hat_status'    => '' !== $status[0],
			'status_mod'    => $status[0],
			'status_text'   => $status[1],
			'karte_mod'     => $sperre ? 'facility-card--blocked' : ( $st && ! $st['offen'] ? 'facility-card--closed' : '' ),
			'hat_standard'  => (bool) $b['standard'],
			'standard'      => array_map( fn( $z ) => array( 'tage' => golfplatz_tage_text( $z['tage'] ), 'zeit' => $z['von'] . '–' . $z['bis'] . ' Uhr' ), $b['standard'] ),
			'hat_ausnahmen' => (bool) $kommende,
			'ausnahmen'     => array_map(
				function ( $a ) {
					$zeitraum = $a['von'] === $a['bis'] ? golfplatz_datum_text( $a['von'] ) : golfplatz_datum_text( $a['von'] ) . '–' . golfplatz_datum_text( $a['bis'] );
					$was      = $a['geschlossen'] ? 'geschlossen' : implode( ', ', array_map( fn( $z ) => golfplatz_tage_text( $z['tage'] ) . ' ' . $z['von'] . '–' . $z['bis'] . ' Uhr', $a['zeiten'] ) );
					return array( 'titel' => $a['titel'], 'text' => $zeitraum . ': ' . $was );
				},
				$kommende
			),
			'hinweis'       => $b['hinweis'],
		);
	}
	return $liste;
}

/*
 * ---------------------------------------------------------------------------
 * Platzstatus als Etch-Daten: {options.golfplatz.platzstatus.<feld>}
 * Die Etch-Komponente „Platzstatus“ (wordpress/etch/platzstatus.mjs) baut daraus das Markup.
 * Hier wird nur gerechnet: fertige Texte, Kennzeichen (true/false) und Modifier für BEM-Klassen.
 * ---------------------------------------------------------------------------
 */

/** Alle Werte, die die Etch-Komponente „Platzstatus“ braucht. */
function golfplatz_platzstatus_etch(): array {
	$d = golfplatz_platzstatus_daten();
	$j = $d['jetzt'];

	// Schnellsperre des ganzen Platzes
	$platz = $d['schnell']['platz'] ?? null;

	// Spielbedingungen: Grüns, gespielte Abschläge, Trolleys, Buggies
	$bedingungen = array();
	$winter      = 'winter' === $d['gruens'];
	$bedingungen[] = array( 'mod' => $winter ? 'winter' : 'ok', 'label' => 'Grüns', 'wert' => $winter ? 'Wintergrüns' : 'Sommergrüns', 'info' => $d['gruens_hinweis'] );
	if ( $d['abschlaege'] ) {
		$bedingungen[] = array( 'mod' => 'ok', 'label' => 'Abschläge', 'wert' => $d['abschlaege'], 'info' => '' );
	}
	foreach ( array( 'trolley' => 'Trolleys', 'buggy' => 'Buggies / E-Carts' ) as $b => $label ) {
		$s             = golfplatz_aktive_sperre( $d, $b );
		$bedingungen[] = $s
			? array( 'mod' => 'blocked', 'label' => $label, 'wert' => 'gesperrt', 'info' => $s['grund'] . golfplatz_bis_text( $s ) )
			: array( 'mod' => 'ok', 'label' => $label, 'wert' => 'erlaubt', 'info' => '' );
	}

	// Heute und morgen: Sperren von Platz und Abschlägen
	$tage = array();
	foreach ( array( 0 => 'Heute', 1 => 'Morgen' ) as $versatz => $label ) {
		$tag       = strtotime( 'today', $j ) + $versatz * DAY_IN_SECONDS;
		$eintraege = array();
		foreach ( golfplatz_sperren_am_tag( $d, GOLFPLATZ_PLATZ, $versatz ) as $s ) {
			$laeuft      = $s['beginn'] <= $j && ( ! $s['ende'] || $s['ende'] > $j );
			$eintraege[] = array(
				'bereich' => GOLFPLATZ_BEREICHE[ $s['bereich'] ],
				'zeit'    => golfplatz_zeitraum( $s, $tag ),
				'grund'   => $s['grund'],
				'start'   => $s['start'] ?? '',
				'laeuft'  => $laeuft,
				'klasse'  => trim( ( 'platz' === $s['bereich'] ? 'status-entry--platz' : '' ) . ( $laeuft ? ' status-entry--active' : '' ) ),
			);
		}
		$frei = ! $eintraege;
		// Abgesagte oder verschobene Turniere des Tages (golfplatz-turniere.php): Hinweis, dass die Turniersperre entfällt
		foreach ( function_exists( 'golfplatz_turniere_ausfall_am' ) ? golfplatz_turniere_ausfall_am( $tag ) : array() as $a ) {
			$eintraege[] = array(
				'bereich' => 'Turnier ' . $a['art'],
				'zeit'    => $a['zeit'],
				'grund'   => $a['text'],
				'start'   => '',
				'laeuft'  => false,
				'klasse'  => 'status-entry--' . $a['art'],
			);
		}
		$tage[] = array(
			'label'     => $label,
			'datum'     => date_i18n( 'l, j. F', $tag ),
			'frei'      => $frei,
			'eintraege' => $eintraege,
		);
	}

	// Übungsanlagen und Proshop: Sperre vor Öffnungszeiten; ohne gepflegte Zeiten gilt „geöffnet“
	$einrichtungen = array();
	foreach ( GOLFPLATZ_EINRICHTUNGEN as $b ) {
		$aktiv = golfplatz_aktive_sperre( $d, $b );
		$st    = golfplatz_zeiten_status( $b, $j );
		$infos = array();
		if ( $aktiv ) {
			$infos[] = array( 'text' => $aktiv['grund'] );
		}
		foreach ( array( 0 => 'Heute', 1 => 'Morgen' ) as $versatz => $label ) {
			$tag = strtotime( 'today', $j ) + $versatz * DAY_IN_SECONDS;
			foreach ( golfplatz_sperren_am_tag( $d, array( $b ), $versatz ) as $s ) {
				if ( $aktiv && $s['beginn'] === $aktiv['beginn'] && $s['schnell'] === $aktiv['schnell'] ) {
					continue;
				}
				$infos[] = array( 'text' => $label . ' ' . golfplatz_zeitraum( $s, $tag ) . ' gesperrt: ' . $s['grund'] );
			}
		}
		$einrichtungen[] = array(
			'name'    => GOLFPLATZ_BEREICHE[ $b ],
			'mod'     => $aktiv ? 'blocked' : ( $st && ! $st['offen'] ? 'closed' : 'ok' ),
			'zustand' => $aktiv ? ( 'proshop' === $b ? 'geschlossen' : 'gesperrt' ) . golfplatz_bis_text( $aktiv ) : ( $st ? golfplatz_zeiten_status_text( $st ) : 'geöffnet' ),
			'infos'   => $infos,
		);
	}

	// Fahnenpositionen
	$f      = golfplatz_fahnen();
	$fahnen = array( 'vorhanden' => (bool) $f );
	if ( $f ) {
		$lagen   = golfplatz_pin_lagen();
		$fahnen += array(
			'label'         => $f['ausnahmen'] ? 'Alle anderen Grüns' : 'Alle Grüns',
			'info'          => $f['hinweis'],
			'standard'      => 'Position ' . $f['standard'],
			'standard_nr'   => $f['standard'],
			'hat_ausnahmen' => (bool) $f['ausnahmen'],
			'ausnahmen'     => array_map(
				fn( $bahn, $pos ) => array(
					'bahn' => $bahn,
					'nr'   => $pos,
					'text' => 'Position ' . $pos,
					'lage' => golfplatz_pin_lage_text( $lagen[ $bahn ][ $pos ] ?? null ),
				),
				array_keys( $f['ausnahmen'] ),
				array_values( $f['ausnahmen'] )
			),
			'hinweis'       => $f['hinweis'],
		);
	}

	return array(
		'stand'                => golfplatz_uhr( $j ),
		'platz_gesperrt'       => (bool) $platz,
		'platz_gesperrt_grund' => $platz ? $platz['grund'] : '',
		'platz_gesperrt_bis'   => $platz && $platz['ende'] ? 'voraussichtlich bis ' . golfplatz_uhr( $platz['ende'] ) . ' Uhr' : '',
		'bedingungen'          => $bedingungen,
		'fahnen'               => $fahnen,
		'tage'                 => $tage,
		'einrichtungen'        => $einrichtungen,
		'ampel'                => golfplatz_ampel_etch(),
		'kurz'                 => golfplatz_status_kurz_etch(),
	);
}

/** Bereitstellung für Etch: {options.golfplatz.platzstatus.…}, im Builder und auf der Website. */
add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			$data['golfplatz']['platzstatus'] = golfplatz_platzstatus_etch();
			$data['golfplatz']['zeiten']      = golfplatz_zeiten_etch();
		}
		return $data;
	}
);

/*
 * ---------------------------------------------------------------------------
 * Rechte: Sperrungen und die Einstellungsseite „Platzstatus“ nutzen eigene Rechte (edit_sperrungen …),
 * damit eine eigene Rolle nur den Platzstatus pflegen kann. Administratoren bekommen sie automatisch –
 * sonst blendet WordPress Menü „Sperrungen“ und Seite „Platzstatus“ aus. Geschrieben wird nur, wenn ein Recht fehlt.
 * ---------------------------------------------------------------------------
 */
add_action(
	'init',
	function () {
		$rolle = get_role( 'administrator' );
		$typ   = get_post_type_object( 'sperrung' );
		if ( ! $rolle || ! $typ ) {
			return;
		}
		foreach ( (array) $typ->cap as $schluessel => $recht ) {
			// edit_post, read_post, delete_post sind Meta-Rechte und werden über map_meta_cap abgeleitet
			if ( in_array( $schluessel, array( 'edit_post', 'read_post', 'delete_post' ), true ) || $rolle->has_cap( $recht ) ) {
				continue;
			}
			$rolle->add_cap( $recht );
		}
	},
	99
);

/*
 * Clubdaten › Öffnungszeiten: Feldgruppe im Code (ersetzt die frühere Builder-Gruppe „clubdaten-zeiten“, gleiche Feld-IDs).
 * Je Bereich: Überschrift, normale Öffnungszeiten, Ausnahmen, Hinweis. Die alten Freitext-Felder (club_oeffnungszeiten usw.) entfallen.
 */
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$woche = array( 'mo' => 'Mo', 'di' => 'Di', 'mi' => 'Mi', 'do' => 'Do', 'fr' => 'Fr', 'sa' => 'Sa', 'so' => 'So' );
		$zeit  = fn( string $id, string $name ) => array( 'id' => $id, 'name' => $name, 'type' => 'time', 'columns' => 3, 'js_options' => array( 'timeFormat' => 'HH:mm', 'stepMinute' => 15 ) );
		$datum = fn( string $id, string $name ) => array( 'id' => $id, 'name' => $name, 'type' => 'date', 'columns' => 3, 'save_format' => 'Y-m-d', 'js_options' => array( 'dateFormat' => 'dd.mm.yy' ) );
		$zeile = array(
			array( 'id' => 'tage', 'name' => 'An diesen Tagen', 'type' => 'checkbox_list', 'inline' => true, 'options' => $woche, 'columns' => 6 ),
			$zeit( 'von', 'geöffnet von' ),
			$zeit( 'bis', 'bis' ),
		);
		$felder = array(
			array(
				'type' => 'heading',
				'name' => 'So funktionieren die Öffnungszeiten',
				'desc' => 'Für jeden Bereich gibt es drei Angaben: <strong>Normale Öffnungszeiten</strong> (die übliche Woche), <strong>Ausnahmen</strong> für einen bestimmten Zeitraum (Feiertage, Winterzeit, Betriebsferien) und einen <strong>Hinweis</strong>, der unter den Zeiten erscheint. Tage ohne Zeile gelten als geschlossen. Die Website zeigt daraus selbst „Jetzt geöffnet“ bzw. „Geschlossen · öffnet …“.',
			),
		);
		foreach ( GOLFPLATZ_ZEITEN_BEREICHE as $key => $name ) {
			$felder[] = array( 'id' => 'zeiten_' . $key . '_heading', 'type' => 'heading', 'name' => $name );
			$felder[] = array(
				'id'         => 'zeiten_' . $key . '_standard',
				'name'       => 'Normale Öffnungszeiten',
				'desc'       => 'Tage anhaken und Uhrzeit eintragen. Andere Zeiten an anderen Tagen (z. B. Wochenende): weitere Zeile.',
				'type'       => 'group',
				'clone'      => true,
				'sort_clone' => true,
				'add_button' => '+ weitere Tage mit anderen Zeiten',
				'fields'     => $zeile,
			);
			$felder[] = array(
				'id'           => 'zeiten_' . $key . '_ausnahmen',
				'name'         => 'Ausnahmen',
				'desc'         => 'Nur für einen Zeitraum, z. B. Weihnachten oder Winterzeit. An diesen Tagen gelten statt der normalen Zeiten die Zeiten der Ausnahme – oder „geschlossen“.',
				'type'         => 'group',
				'clone'        => true,
				'sort_clone'   => true,
				'collapsible'  => true,
				'default_state' => 'collapsed',
				'group_title'  => '{titel}',
				'add_button'   => '+ Ausnahme',
				'fields'       => array(
					array( 'id' => 'titel', 'name' => 'Bezeichnung', 'type' => 'text', 'placeholder' => 'z. B. Weihnachten, Winterzeit', 'columns' => 4 ),
					$datum( 'von', 'vom' ),
					$datum( 'bis', 'bis einschließlich' ),
					array( 'id' => 'geschlossen', 'name' => 'Ganz geschlossen', 'type' => 'checkbox', 'columns' => 2 ),
					array(
						'id'         => 'zeiten',
						'name'       => 'Abweichende Öffnungszeiten in diesem Zeitraum',
						'type'       => 'group',
						'clone'      => true,
						'add_button' => '+ weitere Tage',
						'fields'     => $zeile,
						'hidden'     => array( 'geschlossen', '=', 1 ),
					),
				),
			);
			$felder[] = array( 'id' => 'zeiten_' . $key . '_hinweis', 'name' => 'Hinweis (öffentlich)', 'type' => 'text', 'placeholder' => 'z. B. Letzter Einlass 30 Minuten vor Schluss' );
		}
		$boxen[] = array(
			'id'             => 'clubdaten-zeiten',
			'title'          => 'Clubdaten · Öffnungszeiten',
			'settings_pages' => 'clubdaten',
			'tab'            => 'zeiten',
			'fields'         => $felder,
		);
		return $boxen;
	}
);
