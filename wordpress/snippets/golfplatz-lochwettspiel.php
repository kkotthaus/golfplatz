<?php
/**
 * Plugin Name: Golfplatz – Lochwettspiel
 * Description: Rechnet den Turnierbaum des jährlichen Lochwettspiels (Beitragstyp „lochwettspiel“, Teams aus zwei Spielern, K.-o.-System) und stellt ihn Etch als Daten bereit: {options.golfplatz.lochwettspiele}. Setzt die Teams ins Tableau (Freilose gestreut), lässt Sieger aus den eingetragenen Ergebnissen weiterrücken und bewertet jede Runde gegen ihren Spielzeitraum (läuft, Frist abgelaufen, abgeschlossen). Keine Shortcodes – das Markup baut die Etch-Komponente „Lochwettspiel“ (wordpress/etch/lochwettspiel.mjs).
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-lochwettspiel.php
 */

defined( 'ABSPATH' ) || exit;

define( 'GOLFPLATZ_LW_SPIELFORMEN', array(
	'vierball' => 'Vierball-Bestball',
	'vierer'   => 'Klassischer Vierer',
	'chapman'  => 'Chapman-Vierer',
	'greensome' => 'Greensome',
) );

/**
 * Höchstens so viele Spiele stehen in der ersten Spalte des Turnierbaums (8 = ab Achtelfinale, 16 Teams).
 * Frühere Runden erscheinen als Rundenlisten über dem Baum – so bleibt die Grafik auch bei 64, 128 oder mehr Teams lesbar.
 */
define( 'GOLFPLATZ_LW_BAUM_MAX_SPIELE', 8 );

/**
 * Datum als JJJJ-MM-TT oder null. Meta Box speichert Datumsfelder in Gruppen im Anzeigeformat („31.05.2026“),
 * obwohl „Y-m-d“ eingestellt ist (wie bei den Steckplänen in golfplatz-platzstatus.php).
 */
function golfplatz_lw_datum( $wert ): ?string {
	$wert = trim( (string) $wert );
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $wert, $m ) ) {
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $wert : null;
	}
	if ( preg_match( '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $wert, $m ) ) {
		return checkdate( (int) $m[2], (int) $m[1], (int) $m[3] ) ? sprintf( '%04d-%02d-%02d', $m[3], $m[2], $m[1] ) : null;
	}
	return null;
}

/** „31.05.“ bzw. mit Jahr „31.05.2026“ */
function golfplatz_lw_datum_text( ?string $iso, bool $jahr = false ): string {
	if ( ! $iso ) {
		return '';
	}
	[ $y, $m, $d ] = explode( '-', $iso );
	return $d . '.' . $m . '.' . ( $jahr ? $y : '' );
}

/**
 * Plätze im Tableau (0-basiert) in Reihenfolge der Setzliste: Platz von Team 1, Team 2, … bei $groesse Plätzen.
 * Standard-Setzung (1 gegen 16, 8 gegen 9 …): Die Freilose fallen auf die ersten Teams und nie zwei aufeinander.
 */
function golfplatz_lw_setzplaetze( int $groesse ): array {
	$folge = array( 1 );
	while ( count( $folge ) < $groesse ) {
		$n     = count( $folge ) * 2;
		$neu   = array();
		foreach ( $folge as $s ) {
			$neu[] = $s;
			$neu[] = $n + 1 - $s;
		}
		$folge = $neu;
	}
	// $folge[Platz] = Setznummer → umkehren zu Setznummer → Platz
	$plaetze = array();
	foreach ( $folge as $platz => $setz ) {
		$plaetze[ $setz - 1 ] = $platz;
	}
	ksort( $plaetze );
	return $plaetze;
}

/** Rundenname vom Ende her: Finale, Halbfinale, Viertelfinale, Achtelfinale, sonst „1. Runde“. */
function golfplatz_lw_rundenname( int $runde, int $anzahl ): string {
	$vom_ende = $anzahl - $runde;
	$namen    = array( 'Finale', 'Halbfinale', 'Viertelfinale', 'Achtelfinale' );
	return $namen[ $vom_ende ] ?? $runde . '. Runde';
}

/**
 * Gemeldete Teams eines Lochwettspiels mit fester ID. Die ID vergibt golfplatz_lw_team_ids() beim Speichern;
 * fehlt sie (ältere Daten), gilt die Position in der Liste („nr-3“).
 */
function golfplatz_lw_teams( int $post_id ): array {
	$teams = array();
	foreach ( array_values( (array) get_post_meta( $post_id, 'lw_teams', true ) ) as $i => $t ) {
		if ( ! is_array( $t ) || '' === trim( (string) ( $t['spieler_1'] ?? '' ) . ( $t['spieler_2'] ?? '' ) ) ) {
			continue;
		}
		$t['team_id'] = trim( (string) ( $t['team_id'] ?? '' ) ) ?: 'nr-' . ( $i + 1 );
		$teams[]      = $t;
	}
	return $teams;
}

/** Team für die Ausgabe. */
function golfplatz_lw_team( array $t ): array {
	$s1 = trim( (string) ( $t['spieler_1'] ?? '' ) );
	$s2 = trim( (string) ( $t['spieler_2'] ?? '' ) );
	return array(
		'id'        => (string) ( $t['team_id'] ?? '' ),
		'name'      => trim( (string) ( $t['name'] ?? '' ) ),
		'spieler_1' => $s1,
		'spieler_2' => $s2,
		'kurz'      => trim( $s1 . ( '' !== $s2 ? ' / ' . $s2 : '' ) ),
	);
}

/**
 * Turnierbaum eines Lochwettspiels.
 *
 * Eingaben (Meta Box):
 * - lw_teams[]:  team_id (automatisch), spieler_1, spieler_2, name (optional), position (optional, Platz im Tableau 1…n laut Auslosung)
 * - lw_runden[]: name (optional), von (optional), bis – Spielzeitraum je Runde, Reihenfolge = Runde 1, 2, …
 * - lw_spiele[]: paarung („<runde>:<team_id des Siegers>“, Auswahl mit Paarung und Sieger), ergebnis („3 & 2“, „1 auf“, „kampflos“), datum
 *
 * Ein Ergebnis ist eindeutig über Runde und Siegerteam: Jedes Team spielt je Runde höchstens ein Spiel. Einträge, die zu
 * keinem Spiel passen, landen in „warnungen“ und erscheinen beim Bearbeiten als Hinweis.
 */
function golfplatz_lw_baum( int $post_id ): array {
	$m      = fn( string $k ) => get_post_meta( $post_id, $k, true );
	$heute  = wp_date( 'Y-m-d' );
	$teams  = golfplatz_lw_teams( $post_id );
	$anzahl = count( $teams );
	$jahr   = (int) $m( 'lw_jahr' ) ?: (int) get_the_date( 'Y', $post_id );
	$form   = (string) $m( 'lw_spielform' );

	$daten = array(
		'jahr'         => (string) $jahr,
		'titel'        => get_the_title( $post_id ),
		'link'         => get_permalink( $post_id ),
		'spielform'    => GOLFPLATZ_LW_SPIELFORMEN[ $form ] ?? $form,
		'teams'        => $anzahl,
		'teams_text'   => 1 === $anzahl ? '1 Team' : $anzahl . ' Teams',
		'hinweis'      => (string) $m( 'lw_hinweis' ),
		'hat_baum'     => $anzahl >= 2,
		'runden'       => array(),
		'sieger'       => array( 'vorhanden' => false, 'mod' => 'offen', 'name' => '', 'spieler_1' => '', 'spieler_2' => '', 'text' => 'noch offen' ),
		'status'       => '',
		'hat_vorrunden' => false,
		'baum_titel'    => 'Turnierbaum',
		'warnungen'     => array(),
		'paarungen'     => array(), // nur für die Bearbeitung: alle Spiele mit beiden Teams
	);
	if ( $anzahl < 2 ) {
		$daten['status'] = 'Die Auslosung folgt.';
		return $daten;
	}

	// Tableau: nächste Zweierpotenz, Plätze aus der Auslosung, übrige Teams nach Setzliste
	$groesse = 2;
	while ( $groesse < $anzahl ) {
		$groesse *= 2;
	}
	$runden_anzahl = (int) log( $groesse, 2 );
	$slots         = array_fill( 0, $groesse, null );
	$ohne          = array();
	foreach ( $teams as $t ) {
		$pos = (int) ( $t['position'] ?? 0 );
		if ( $pos >= 1 && $pos <= $groesse && null === $slots[ $pos - 1 ] ) {
			$slots[ $pos - 1 ] = golfplatz_lw_team( $t );
		} else {
			$ohne[] = golfplatz_lw_team( $t );
		}
	}
	if ( $ohne ) {
		// Freie Plätze in Setzreihenfolge; bei vollständiger Auslosung ohne Positionen ergibt das die Standard-Setzung
		foreach ( golfplatz_lw_setzplaetze( $groesse ) as $platz ) {
			if ( ! $ohne ) {
				break;
			}
			if ( null === $slots[ $platz ] && $platz < $groesse ) {
				$slots[ $platz ] = array_shift( $ohne );
			}
		}
	}

	// Ergebnisse: [runde][team_id des Siegers] => [ergebnis, datum, zeile]
	$ergebnisse = array();
	$namen      = array_column( array_map( 'golfplatz_lw_team', $teams ), 'kurz', 'id' );
	foreach ( array_values( (array) $m( 'lw_spiele' ) ) as $i => $e ) {
		// „4:t5“ = Runde 4, Sieger Team t5; ältere Einträge mit getrennten Feldern runde/sieger
		if ( preg_match( '/^(\d+):(.+)$/', (string) ( $e['paarung'] ?? '' ), $pm ) ) {
			[ , $r, $w ] = $pm;
			$r           = (int) $r;
		} else {
			$r = (int) ( $e['runde'] ?? 0 );
			$w = (string) ( $e['sieger'] ?? '' );
		}
		if ( $r < 1 || '' === $w ) {
			continue;
		}
		if ( ! isset( $namen[ $w ] ) ) {
			$daten['warnungen'][] = sprintf( 'Ergebnis %d: Das Siegerteam ist nicht (mehr) gemeldet.', $i + 1 );
			continue;
		}
		if ( isset( $ergebnisse[ $r ][ $w ] ) ) {
			$daten['warnungen'][] = sprintf( 'Ergebnis %d: %s hat in Runde %d schon ein Ergebnis (Ergebnis %d).', $i + 1, $namen[ $w ], $r, $ergebnisse[ $r ][ $w ]['zeile'] );
			continue;
		}
		$ergebnisse[ $r ][ $w ] = array(
			'ergebnis' => trim( (string) ( $e['ergebnis'] ?? '' ) ),
			'datum'    => golfplatz_lw_datum( $e['datum'] ?? '' ),
			'zeile'    => $i + 1,
			'genutzt'  => false,
		);
	}

	// Spielzeiträume; „von“ fehlt → Tag nach dem Ende der Vorrunde
	$zeiten = array();
	$vorher = null;
	foreach ( array_values( (array) $m( 'lw_runden' ) ) as $i => $z ) {
		$von = golfplatz_lw_datum( $z['von'] ?? '' );
		$bis = golfplatz_lw_datum( $z['bis'] ?? '' );
		if ( ! $von && $vorher ) {
			$von = wp_date( 'Y-m-d', strtotime( $vorher . ' +1 day' ) );
		}
		$zeiten[ $i + 1 ] = array( 'name' => trim( (string) ( $z['name'] ?? '' ) ), 'von' => $von, 'bis' => $bis );
		$vorher           = $bis ?: $vorher;
	}

	// Runde für Runde: Teilnehmer je Platz als [team|null, quelle] – quelle „freilos“ oder „Sieger Spiel 3“
	$teilnehmer = array_map( fn( $t ) => array( $t, $t ? '' : 'Freilos' ), $slots );
	$nr         = 0;
	for ( $r = 1; $r <= $runden_anzahl; $r++ ) {
		$zeit    = $zeiten[ $r ] ?? array( 'name' => '', 'von' => null, 'bis' => null );
		$spiele  = array();
		$weiter  = array();
		$offen   = 0;
		$echte   = 0;
		for ( $s = 1; $s <= count( $teilnehmer ) / 2; $s++ ) {
			[ $a, $qa ] = $teilnehmer[ 2 * $s - 2 ];
			[ $b, $qb ] = $teilnehmer[ 2 * $s - 1 ];
			$sieger     = null;
			$spiel      = array( 'nr' => '', 'label' => '', 'mod' => 'wartet', 'status' => '', 'ergebnis' => '' );
			// Echtes Spiel, wenn keine Seite ein Freilos ist – auch wenn ein Gegner noch nicht feststeht
			$echt = 'Freilos' !== $qa && 'Freilos' !== $qb;
			if ( $echt ) {
				$spiel['nr']    = (string) ++$nr;
				$spiel['label'] = 'Spiel ' . $nr;
				++$echte;
			}
			// Ergebnis: Eintrag dieser Runde, dessen Sieger eines der beiden Teams ist
			$e = null;
			if ( $echt && $a && $b ) {
				$ea = $ergebnisse[ $r ][ $a['id'] ] ?? null;
				$eb = $ergebnisse[ $r ][ $b['id'] ] ?? null;
				if ( $ea && $eb ) {
					$daten['warnungen'][] = sprintf( '%s: Für Spiel %s sind beide Teams als Sieger eingetragen (Ergebnis %d und %d).', golfplatz_lw_rundenname( $r, $runden_anzahl ), $nr, $ea['zeile'], $eb['zeile'] );
					$ergebnisse[ $r ][ $a['id'] ]['genutzt'] = $ergebnisse[ $r ][ $b['id'] ]['genutzt'] = true;
				} elseif ( $ea || $eb ) {
					$e = array_merge( $ea ?: $eb, array( 'sieger' => $ea ? 'oben' : 'unten' ) );
					$ergebnisse[ $r ][ $ea ? $a['id'] : $b['id'] ]['genutzt'] = true;
				}
			}

			if ( $echt && $a && $b ) {
				if ( $e ) {
					$sieger            = $e['sieger'];
					$spiel['mod']      = 'gespielt';
					$spiel['ergebnis'] = $e['ergebnis'];
					$spiel['status']   = $e['datum'] ? 'gespielt am ' . golfplatz_lw_datum_text( $e['datum'] ) : 'gespielt';
				} elseif ( $zeit['von'] && $heute < $zeit['von'] ) {
					$spiel['status'] = 'ab ' . golfplatz_lw_datum_text( $zeit['von'] );
					++$offen;
				} elseif ( $zeit['bis'] && $heute > $zeit['bis'] ) {
					$spiel['mod']    = 'ueberfaellig';
					$spiel['status'] = 'Frist abgelaufen (' . golfplatz_lw_datum_text( $zeit['bis'] ) . ')';
					++$offen;
				} else {
					$spiel['mod']    = 'offen';
					$spiel['status'] = $zeit['bis'] ? 'offen · bis ' . golfplatz_lw_datum_text( $zeit['bis'] ) : 'offen';
					++$offen;
				}
			} elseif ( $echt ) {
				// Mindestens ein Gegner steht noch nicht fest
				$spiel['status'] = $zeit['von'] && $heute < $zeit['von'] ? 'ab ' . golfplatz_lw_datum_text( $zeit['von'] ) : 'Gegner offen';
				++$offen;
			} else {
				// Freilos: das Team kommt kampflos weiter (steht es noch nicht fest, rückt der Platzhalter weiter)
				$spiel['mod']    = 'freilos';
				$spiel['status'] = 'Freilos';
				if ( $a || $b ) {
					$sieger = $a ? 'oben' : 'unten';
				}
			}

			$seiten = array();
			foreach ( array( 'oben' => array( $a, $qa ), 'unten' => array( $b, $qb ) ) as $seite => [ $team, $quelle ] ) {
				$gewinnt  = $sieger === $seite;
				$gespielt = 'gespielt' === $spiel['mod'];
				$seiten[] = array(
					'leer'      => ! $team,
					'text'      => $team ? '' : $quelle,
					'name'      => $team['name'] ?? '',
					'spieler_1' => $team['spieler_1'] ?? '',
					'spieler_2' => $team['spieler_2'] ?? '',
					'sieger'    => $gespielt && $gewinnt,
					'ergebnis'  => $gespielt && $gewinnt ? $spiel['ergebnis'] : '',
					'mod'       => ! $team ? 'leer' : ( $gespielt ? ( $gewinnt ? 'sieger' : 'raus' ) : ( 'freilos' === $spiel['mod'] ? 'weiter' : 'offen' ) ),
				);
			}
			$spiel['seiten'] = $seiten;
			if ( $echt && $a && $b ) {
				$daten['paarungen'][] = array(
					'runde'    => $r,
					'titel'    => $spiel['label'] . ' (' . ( $zeit['name'] ?: golfplatz_lw_rundenname( $r, $runden_anzahl ) ) . ')',
					'a'        => $a,
					'b'        => $b,
					'sieger'   => $sieger ? ( 'oben' === $sieger ? $a : $b ) : null,
					'ergebnis' => $spiel['ergebnis'],
					'status'   => $spiel['status'],
				);
			}
			$spiel['aria']   = golfplatz_lw_spiel_aria( $spiel, $a, $b, $qa, $qb, $sieger );
			$spiele[]        = $spiel;

			if ( $sieger ) {
				$weiter[] = array( 'oben' === $sieger ? $a : $b, '' );
			} elseif ( $echt ) {
				$weiter[] = array( null, 'Sieger Spiel ' . $spiel['nr'] );
			} else {
				// Freilos gegen Freilos bleibt Freilos, sonst rückt der Platzhalter der anderen Seite weiter
				$weiter[] = array( null, 'Freilos' === $qa ? $qb : $qa );
			}
		}

		// Status der Runde (immer mit Text, nie nur Farbe)
		$bis_text = $zeit['bis'] ? golfplatz_lw_datum_text( $zeit['bis'], true ) : '';
		if ( 0 === $offen && $echte > 0 ) {
			$rmod = 'fertig';
			$rtxt = 'abgeschlossen';
		} elseif ( $zeit['von'] && $heute < $zeit['von'] ) {
			$rmod = 'geplant';
			$rtxt = 'beginnt am ' . golfplatz_lw_datum_text( $zeit['von'], true );
		} elseif ( $zeit['bis'] && $heute > $zeit['bis'] ) {
			$rmod = 'abgelaufen';
			$rtxt = 'Frist abgelaufen';
		} elseif ( $zeit['bis'] || $zeit['von'] ) {
			$rmod = 'laeuft';
			$rtxt = 'läuft';
		} else {
			$rmod = 'geplant';
			$rtxt = 'Termin folgt';
		}
		$zeitraum = $zeit['von'] && $zeit['bis']
			? golfplatz_lw_datum_text( $zeit['von'] ) . '–' . $bis_text
			: ( $bis_text ? 'bis ' . $bis_text : '' );

		// Große Felder: Runden mit mehr als GOLFPLATZ_LW_BAUM_MAX_SPIELE Spielen als Liste, der Rest als Baum
		$im_baum  = count( $spiele ) <= GOLFPLATZ_LW_BAUM_MAX_SPIELE;
		$freilose = count( array_filter( $spiele, fn( $sp ) => 'freilos' === $sp['mod'] && ( ! $sp['seiten'][0]['leer'] || ! $sp['seiten'][1]['leer'] ) ) );
		$zahlen   = array( 1 === $echte ? '1 Spiel' : $echte . ' Spiele' );
		if ( $offen && in_array( $rmod, array( 'laeuft', 'abgelaufen' ), true ) ) {
			$zahlen[] = $offen . ' offen';
		}
		if ( $freilose ) {
			$zahlen[] = 1 === $freilose ? '1 Freilos' : $freilose . ' Freilose';
		}

		// Paare: je zwei Spiele, deren Sieger sich in der nächsten Runde treffen (für die Linien im Baum)
		$paare = array();
		foreach ( $im_baum ? array_chunk( $spiele, 2 ) : array() as $paar ) {
			$paare[] = array( 'mod' => count( $paar ) > 1 ? 'paar' : 'final', 'spiele' => $paar );
		}
		$daten['runden'][] = array(
			'nr'         => $r,
			'name'       => $zeit['name'] ?: golfplatz_lw_rundenname( $r, $runden_anzahl ),
			'zeitraum'   => $zeitraum,
			'mod'        => $rmod,
			'status'     => $rtxt,
			'zahlen'     => implode( ' · ', $zahlen ),
			'im_baum'    => $im_baum,
			'vorrunde'   => ! $im_baum,
			'aufklappen' => false,
			'lage'       => 'folge',
			'paare'      => $paare,
			// Rundenliste: Freilos gegen Freilos weglassen
			'liste'      => $im_baum ? array() : array_values( array_filter( $spiele, fn( $sp ) => ! ( 'freilos' === $sp['mod'] && $sp['seiten'][0]['leer'] && $sp['seiten'][1]['leer'] ) ) ),
		);
		$teilnehmer = $weiter;
	}

	// Einträge, die zu keinem Spiel passen (Team ausgeschieden, Freilos, Gegner steht noch nicht fest, falsche Runde)
	foreach ( $ergebnisse as $r => $liste ) {
		foreach ( $liste as $id => $e ) {
			if ( ! $e['genutzt'] ) {
				$daten['warnungen'][] = $r > $runden_anzahl
					? sprintf( 'Ergebnis %d: Runde %d gibt es bei %d Teams nicht.', $e['zeile'], $r, $anzahl )
					: sprintf( 'Ergebnis %d: %s hat in der Runde „%s“ kein Spiel – Freilos, schon ausgeschieden oder der Gegner steht noch nicht fest.', $e['zeile'], $namen[ $id ], golfplatz_lw_rundenname( $r, $runden_anzahl ) );
			}
		}
	}

	// Erste Spalte des Baums ohne Linien nach links; Überschrift nennt, ab welcher Runde der Baum reicht
	foreach ( $daten['runden'] as $i => $runde ) {
		if ( $runde['im_baum'] ) {
			$daten['runden'][ $i ]['lage'] = 'start';
			if ( $i > 0 ) {
				$daten['hat_vorrunden'] = true;
				$daten['baum_titel']    = 'Turnierbaum ab ' . $runde['name'];
			}
			break;
		}
	}

	[ $champ ] = $teilnehmer[0];
	if ( $champ ) {
		$daten['sieger'] = array_merge( $champ, array( 'vorhanden' => true, 'mod' => 'fest', 'text' => '' ) );
		$daten['status'] = 'Turnier beendet';
	} else {
		// Laufende Runde, sonst die erste, die noch nicht abgeschlossen ist; als Rundenliste aufgeklappt
		$aktiv = array_keys( array_filter( $daten['runden'], fn( $r ) => 'laeuft' === $r['mod'] ) )
			?: array_keys( array_filter( $daten['runden'], fn( $r ) => 'fertig' !== $r['mod'] ) );
		if ( $aktiv ) {
			$runde                                      = $daten['runden'][ $aktiv[0] ];
			$daten['status']                            = $runde['name'] . ': ' . $runde['status'] . ( $runde['zeitraum'] ? ' (' . $runde['zeitraum'] . ')' : '' );
			$daten['runden'][ $aktiv[0] ]['aufklappen'] = $runde['vorrunde'];
		}
	}
	return $daten;
}

/** Vorlesetext eines Spiels, z. B. „Spiel 3: Müller / Schmidt gegen Meier / Schulz, Sieger Müller / Schmidt 3 & 2“. */
function golfplatz_lw_spiel_aria( array $spiel, $a, $b, string $qa, string $qb, ?string $sieger ): string {
	$name = fn( $t, $q ) => $t ? ( $t['name'] ? $t['name'] . ' (' . $t['kurz'] . ')' : $t['kurz'] ) : $q;
	if ( 'freilos' === $spiel['mod'] ) {
		return $a || $b ? 'Freilos für ' . $name( $a ?: $b, '' ) : 'Freilos';
	}
	$text = ( $spiel['label'] ? $spiel['label'] . ': ' : '' ) . $name( $a, $qa ) . ' gegen ' . $name( $b, $qb );
	if ( $sieger ) {
		$text .= ', Sieger ' . $name( 'oben' === $sieger ? $a : $b, '' ) . ( $spiel['ergebnis'] ? ' ' . $spiel['ergebnis'] : '' );
	}
	return $text . ( $spiel['status'] && 'gespielt' !== $spiel['status'] ? ', ' . $spiel['status'] : '' );
}

/**
 * Alle Lochwettspiele, neuestes Jahr zuerst. Das neueste steht zusätzlich unter key „aktuell“,
 * damit die Komponente ohne Jahresangabe immer das laufende Turnier zeigt.
 */
function golfplatz_lochwettspiele_etch(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	if ( ! post_type_exists( 'lochwettspiel' ) ) {
		return $cache;
	}
	$posts = get_posts(
		array(
			'post_type'      => 'lochwettspiel',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'meta_key'       => 'lw_jahr',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	$jahre = array_map( fn( $p ) => array( 'jahr' => (string) get_post_meta( $p->ID, 'lw_jahr', true ), 'link' => get_permalink( $p ) ), $posts );
	foreach ( $posts as $i => $p ) {
		$baum          = golfplatz_lw_baum( $p->ID );
		unset( $baum['warnungen'], $baum['paarungen'] ); // nur für die Bearbeitung
		$baum['jahre'] = array_map( fn( $j ) => array_merge( $j, array( 'aktiv' => $j['jahr'] === $baum['jahr'] ? 'aktiv' : '' ) ), $jahre );
		$baum['hat_jahre'] = count( $jahre ) > 1;
		if ( 0 === $i ) {
			$cache[] = array_merge( $baum, array( 'key' => 'aktuell' ) );
		}
		$cache[] = array_merge( $baum, array( 'key' => $baum['jahr'] ) );
	}
	return $cache;
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			$data['golfplatz']['lochwettspiele'] = golfplatz_lochwettspiele_etch();
		}
		return $data;
	}
);

/* ---------- Bearbeitung im Admin ---------- */

/**
 * Jedes Team bekommt beim Speichern eine feste ID (Feld team_id, im Formular ausgeblendet), damit Ergebnisse beim
 * Umsortieren oder Korrigieren der Namen gültig bleiben. Fehlt die ID, übernimmt das Team die ID des bisherigen
 * Teams mit denselben Spielern; sonst gibt es eine neue.
 */
add_filter(
	'rwmb_lw_teams_value',
	function ( $teams, $field = array(), $old = array() ) {
		if ( ! is_array( $teams ) ) {
			return $teams;
		}
		$schluessel = fn( $t ) => mb_strtolower( trim( (string) ( $t['spieler_1'] ?? '' ) ) . '|' . trim( (string) ( $t['spieler_2'] ?? '' ) ) );
		$vergeben   = array_filter( array_map( fn( $t ) => is_array( $t ) ? trim( (string) ( $t['team_id'] ?? '' ) ) : '', $teams ) );
		$bisher     = array();
		foreach ( (array) $old as $t ) {
			if ( is_array( $t ) && '' !== trim( (string) ( $t['team_id'] ?? '' ) ) && ! in_array( $t['team_id'], $vergeben, true ) ) {
				$bisher[ $schluessel( $t ) ] = $t['team_id'];
			}
		}
		foreach ( $teams as $i => $t ) {
			if ( ! is_array( $t ) || '' !== trim( (string) ( $t['team_id'] ?? '' ) ) ) {
				continue;
			}
			$k                      = $schluessel( $t );
			$teams[ $i ]['team_id'] = $bisher[ $k ] ?? 't' . substr( md5( uniqid( (string) $i, true ) ), 0, 8 );
			unset( $bisher[ $k ] );
		}
		return $teams;
	},
	10,
	3
);

/** Lochwettspiel, das gerade bearbeitet oder gespeichert wird (0 außerhalb des Editors). */
function golfplatz_lw_admin_post(): int {
	$id = (int) ( $_GET['post'] ?? $_POST['post_ID'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	return $id && 'lochwettspiel' === get_post_type( $id ) ? $id : 0;
}

/** „Name (Spieler / Spieler)“ bzw. „Spieler / Spieler“ */
function golfplatz_lw_team_text( array $team ): string {
	return $team['name'] ? $team['name'] . ' (' . $team['kurz'] . ')' : $team['kurz'];
}

/**
 * Ergebnisse: Auswahl „Spiel 6 (Viertelfinale): A gegen B → Sieger: A“ – je Spiel zwei Einträge, einer je möglichem Sieger.
 * Wert „<runde>:<team_id>“. Die Liste kommt aus dem gespeicherten Stand: ein Spiel der nächsten Runde erscheint,
 * sobald die Ergebnisse davor gespeichert sind. Gespeicherte Werte, die nicht mehr passen, bleiben mit Warnung wählbar.
 */
add_filter(
	'rwmb_normalize_lw_spiele_field',
	function ( $field ) {
		$post_id  = golfplatz_lw_admin_post();
		$optionen = array();
		if ( $post_id ) {
			// Offene Spiele zuerst – bei großen Feldern sind das die, die gerade eingetragen werden
			$paarungen = golfplatz_lw_baum( $post_id )['paarungen'];
			usort( $paarungen, fn( $x, $y ) => (int) (bool) $x['sieger'] <=> (int) (bool) $y['sieger'] );
			foreach ( $paarungen as $p ) {
				$paarung = $p['titel'] . ': ' . golfplatz_lw_team_text( $p['a'] ) . ' gegen ' . golfplatz_lw_team_text( $p['b'] );
				foreach ( array( $p['a'], $p['b'] ) as $team ) {
					$optionen[ $p['runde'] . ':' . $team['id'] ] = $paarung . ' → Sieger: ' . golfplatz_lw_team_text( $team );
				}
			}
			foreach ( (array) get_post_meta( $post_id, 'lw_spiele', true ) as $e ) {
				$wert = (string) ( $e['paarung'] ?? '' );
				if ( '' !== $wert && ! isset( $optionen[ $wert ] ) ) {
					$optionen[ $wert ] = '⚠ passt zu keinem Spiel mehr (' . $wert . ') – bitte neu wählen';
				}
			}
		}
		foreach ( $field['fields'] as $i => $sub ) {
			if ( 'paarung' === $sub['id'] ) {
				$field['fields'][ $i ]['options'] = $optionen;
				// Nicht gegen die Liste prüfen: beim Speichern kann sie sich gerade ändern
				$field['fields'][ $i ]['sanitize_callback'] = 'none';
			}
		}
		return $field;
	}
);

/**
 * Kasten „Stand“ über den Ergebnissen (Feld lw_pruefung, custom_html): offene Spiele mit Teams und Frist,
 * dazu Ergebnisse, die zu keinem Spiel passen. Der Blockeditor zeigt keine admin_notices, deshalb als Feld.
 * Stand beim Laden der Seite – nach dem Speichern neu laden.
 */
add_filter(
	'rwmb_normalize_lw_pruefung_field',
	function ( $field ) {
		$post_id = golfplatz_lw_admin_post();
		if ( ! $post_id ) {
			return $field;
		}
		$baum = golfplatz_lw_baum( $post_id );
		// Tabelle der Paarungen; offene Spiele sichtbar, gespielte eingeklappt (bei 64 Teams sonst 63 Zeilen)
		$tabelle = function ( array $liste ): string {
			$html = '<table class="widefat striped" style="max-width:60rem"><thead><tr><th>Spiel</th><th>Paarung</th><th>Stand</th></tr></thead><tbody>';
			foreach ( $liste as $p ) {
				$team  = fn( $t ) => $p['sieger'] && $p['sieger']['id'] === $t['id'] ? '<strong>' . esc_html( golfplatz_lw_team_text( $t ) ) . ' ✔</strong>' : esc_html( golfplatz_lw_team_text( $t ) );
				$stand = $p['sieger'] ? 'Sieger: ' . esc_html( golfplatz_lw_team_text( $p['sieger'] ) ) . ( $p['ergebnis'] ? ' (' . esc_html( $p['ergebnis'] ) . ')' : '' ) : esc_html( $p['status'] );
				$html .= '<tr><td>' . esc_html( $p['titel'] ) . '</td><td>' . $team( $p['a'] ) . ' gegen ' . $team( $p['b'] ) . '</td><td>' . $stand . '</td></tr>';
			}
			return $html . '</tbody></table>';
		};
		$offen    = array_filter( $baum['paarungen'], fn( $p ) => ! $p['sieger'] );
		$gespielt = array_filter( $baum['paarungen'], fn( $p ) => (bool) $p['sieger'] );
		if ( ! $baum['paarungen'] ) {
			$html = '<p>Noch keine Paarung – zuerst Teams eintragen und speichern.</p>';
		} else {
			$html  = '<p><strong>Offene Spiele (' . count( $offen ) . ')</strong> – Ergebnis unten unter „Ergebnisse“ eintragen:</p>';
			$html .= $offen ? $tabelle( $offen ) : '<p>keine</p>';
			if ( $gespielt ) {
				$html .= '<details style="margin-top:1em"><summary><strong>Gespielte Spiele (' . count( $gespielt ) . ')</strong></summary>' . $tabelle( $gespielt ) . '</details>';
			}
		}
		if ( $baum['warnungen'] ) {
			$html .= '<div class="notice notice-warning inline"><p><strong>Diese Ergebnisse erscheinen nicht im Turnierbaum:</strong></p><ul style="list-style:disc;padding-left:1.5em">'
				. implode( '', array_map( fn( $w ) => '<li>' . esc_html( $w ) . '</li>', $baum['warnungen'] ) ) . '</ul></div>';
		}
		$field['std'] = $html . '<p class="description">Stand beim Öffnen der Seite; nach dem Speichern neu laden. Spiele der nächsten Runde erscheinen, sobald die Ergebnisse davor gespeichert sind.</p>';
		return $field;
	}
);
