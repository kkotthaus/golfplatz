<?php
/**
 * Plugin Name: Golfplatz – Dashboard (Termine und Platzstatus)
 * Description: Zwei Dashboard-Widgets. „Platzstatus“ (rechts oben): Ampel, Spielbedingungen, Fahnen, Sperrungen heute/morgen, Übungsanlagen und Proshop wie auf der Startseite, mit Knöpfen zum Bearbeiten. „Termine der nächsten 14 Tage“ (links oben): Turniere des Heimatclubs (mit Absage/Verschiebung und den daraus erzeugten Tee-Sperrungen), von Hand angelegte Sperrungen, Ligaspiele und Fristen des Lochwettspiels, nach Tagen gruppiert und verlinkt; aktive Schnellsperren aus dem Platzstatus oben. Nur für Benutzer, die Inhalte bearbeiten oder den Platzstatus pflegen.
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-dashboard.php
 */

defined( 'ABSPATH' ) || exit;

define( 'GOLFPLATZ_TERMINE_TAGE', 14 );

function golfplatz_termine_erlaubt(): bool {
	return current_user_can( 'edit_posts' ) || current_user_can( 'edit_sperrungen' );
}

/** Link zum Bearbeiten, wenn erlaubt – sonst leer. */
function golfplatz_termine_link( int $id ): string {
	return current_user_can( 'edit_post', $id ) ? (string) get_edit_post_link( $id ) : '';
}

/**
 * Alle Termine im Zeitraum, je Tag (Y-m-d) eine Liste aus
 * [zeit, sort, art, icon, titel, link, info, status, unter[[text, link]]]. Zeiten sind „Ortszeit als Unix-Zeit“ wie überall im Projekt.
 */
function golfplatz_termine_daten(): array {
	$jetzt = (int) current_time( 'timestamp' );
	$heute = strtotime( 'today', $jetzt );
	$ende  = $heute + GOLFPLATZ_TERMINE_TAGE * DAY_IN_SECONDS;
	$tage  = array();
	$neu   = function ( int $ts, array $e ) use ( &$tage ) {
		$tage[ gmdate( 'Y-m-d', $ts ) ][] = $e + array( 'zeit' => '', 'sort' => $ts, 'link' => '', 'info' => '', 'status' => '', 'unter' => array() );
	};
	$bereiche = defined( 'GOLFPLATZ_BEREICHE' ) ? GOLFPLATZ_BEREICHE : array();
	$uhr      = fn( int $ts ) => gmdate( 'H:i', $ts );

	// Sperrungen im Zeitraum (automatische hängen am Turnier, die übrigen stehen für sich)
	$aus_turnier = array();
	$sperrungen  = get_posts(
		array(
			'post_type'      => 'sperrung',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'meta_query'     => array(
				array( 'key' => 'sperr_ende', 'value' => $jetzt, 'compare' => '>', 'type' => 'NUMERIC' ),
				array( 'key' => 'sperr_beginn', 'value' => $ende, 'compare' => '<', 'type' => 'NUMERIC' ),
			),
		)
	);
	foreach ( $sperrungen as $s ) {
		$m       = fn( string $k ) => get_post_meta( $s->ID, $k, true );
		$beginn  = (int) $m( 'sperr_beginn' );
		$ende_s  = (int) $m( 'sperr_ende' );
		$bereich = $bereiche[ (string) $m( 'sperr_bereich' ) ] ?? (string) $m( 'sperr_bereich' );
		$zeit    = gmdate( 'Y-m-d', $beginn ) === gmdate( 'Y-m-d', $ende_s ) ? $uhr( $beginn ) . '–' . $uhr( $ende_s ) . ' Uhr' : wp_date( 'd.m. H:i', $beginn, new DateTimeZone( 'UTC' ) ) . ' – ' . wp_date( 'd.m. H:i', $ende_s, new DateTimeZone( 'UTC' ) ) . ' Uhr';
		if ( preg_match( '/^turnier:(\d+):/', (string) $m( 'sperr_quelle' ), $t ) ) {
			$aus_turnier[ (int) $t[1] ][] = array( $bereich . ' gesperrt ' . $zeit, golfplatz_termine_link( $s->ID ) );
			continue;
		}
		$neu(
			max( $beginn, $heute ),
			array(
				'zeit'  => $beginn >= $heute ? $uhr( $beginn ) : '',
				'art'   => 'Sperrung',
				'icon'  => 'dashicons-lock',
				'titel' => $bereich . ' gesperrt',
				'link'  => golfplatz_termine_link( $s->ID ),
				'info'  => $zeit . ( $m( 'sperr_grund' ) ? ' · ' . $m( 'sperr_grund' ) : '' ),
			)
		);
	}

	// Turniere des Heimatclubs (gültiger Termin, auch abgesagte und verschobene)
	if ( function_exists( 'golfplatz_turnier_status' ) && function_exists( 'golfplatz_pcc_club' ) ) {
		$eigen = golfplatz_pcc_club();
		$ids   = get_posts(
			array(
				'post_type'      => 'turnier',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => 'turnier_beginn', 'value' => $heute - 90 * DAY_IN_SECONDS, 'compare' => '>=', 'type' => 'NUMERIC' ) ),
			)
		);
		foreach ( $ids as $id ) {
			if ( ( (string) get_post_meta( $id, 'turnier_club', true ) ?: $eigen ) !== $eigen ) {
				continue;
			}
			$st = golfplatz_turnier_status( $id );
			if ( $st['beginn'] < $heute || $st['beginn'] >= $ende ) {
				continue;
			}
			$infos = array_filter( array( (string) get_post_meta( $id, 'turnier_spielform', true ), (int) get_post_meta( $id, 'turnier_loecher', true ) ? get_post_meta( $id, 'turnier_loecher', true ) . ' Löcher' : '' ) );
			$unter = $aus_turnier[ $id ] ?? array();
			if ( $st['findet_statt'] && ! $unter && $st['hat_uhrzeit'] ) {
				$unter[] = array( 'keine Tee-Sperre (keine passende Turnier-Regel)', '' );
			}
			$neu(
				$st['beginn'],
				array(
					'zeit'   => $st['hat_uhrzeit'] ? $uhr( $st['beginn'] ) : '',
					'art'    => 'Turnier',
					'icon'   => 'dashicons-flag',
					'titel'  => html_entity_decode( get_the_title( $id ) ),
					'link'   => golfplatz_termine_link( $id ),
					'info'   => implode( ' · ', $infos ),
					'status' => $st['text'],
					'unter'  => $unter,
				)
			);
		}
	}

	// Ligaspiele der Mannschaften
	foreach ( get_posts(
		array(
			'post_type'      => 'ligaspiel',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'meta_query'     => array( array( 'key' => 'ligaspiel_termin', 'value' => array( $heute, $ende - 1 ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ) ),
		)
	) as $l ) {
		$m          = fn( string $k ) => get_post_meta( $l->ID, $k, true );
		$ts         = (int) $m( 'ligaspiel_termin' );
		$mannschaft = (int) $m( 'ligaspiel_mannschaft' );
		$neu(
			$ts,
			array(
				'zeit'  => '00:00' !== $uhr( $ts ) ? $uhr( $ts ) : '',
				'art'   => 'Ligaspiel',
				'icon'  => 'dashicons-groups',
				'titel' => ( $mannschaft ? html_entity_decode( get_the_title( $mannschaft ) ) : 'Mannschaft' ) . ( (int) $m( 'ligaspiel_spieltag' ) ? ', ' . (int) $m( 'ligaspiel_spieltag' ) . '. Spieltag' : '' ),
				'link'  => golfplatz_termine_link( $l->ID ),
				'info'  => trim( ( $m( 'ligaspiel_heimspiel' ) ? 'Heimspiel · ' : '' ) . (string) $m( 'ligaspiel_spielort' ) . ( $m( 'ligaspiel_liga' ) ? ' · ' . $m( 'ligaspiel_liga' ) : '' ), ' ·' ),
			)
		);
	}

	// Lochwettspiel: Ende der Spielzeiträume
	if ( function_exists( 'golfplatz_lw_datum' ) ) {
		foreach ( get_posts( array( 'post_type' => 'lochwettspiel', 'post_status' => 'publish', 'posts_per_page' => 5 ) ) as $lw ) {
			$runden = array_values( (array) get_post_meta( $lw->ID, 'lw_runden', true ) );
			foreach ( $runden as $i => $r ) {
				$bis = golfplatz_lw_datum( $r['bis'] ?? '' );
				$ts  = $bis ? strtotime( $bis . ' 00:00:00 UTC' ) : 0;
				if ( $ts < $heute || $ts >= $ende ) {
					continue;
				}
				$name = trim( (string) ( $r['name'] ?? '' ) ) ?: ( function_exists( 'golfplatz_lw_rundenname' ) ? golfplatz_lw_rundenname( $i + 1, count( $runden ) ) : ( $i + 1 ) . '. Runde' );
				$neu(
					$ts + DAY_IN_SECONDS - 1,
					array(
						'art'   => 'Lochwettspiel',
						'icon'  => 'dashicons-networking',
						'titel' => html_entity_decode( get_the_title( $lw ) ) . ': Frist ' . $name,
						'link'  => golfplatz_termine_link( $lw->ID ),
						'info'  => 'Letzter Tag, um die Spiele dieser Runde auszutragen',
					)
				);
			}
		}
	}

	ksort( $tage );
	foreach ( $tage as &$liste ) {
		usort( $liste, fn( $a, $b ) => $a['sort'] <=> $b['sort'] );
	}
	unset( $liste );

	$schnell = function_exists( 'golfplatz_platzstatus_daten' ) ? array_values( golfplatz_platzstatus_daten()['schnell'] ) : array();
	return array(
		'heute'   => $heute,
		'tage'    => $tage,
		'schnell' => array_map( fn( $s ) => ( $bereiche[ $s['bereich'] ] ?? $s['bereich'] ) . ' gesperrt' . ( $s['ende'] ? ' bis ' . wp_date( 'd.m. H:i', $s['ende'], new DateTimeZone( 'UTC' ) ) . ' Uhr' : ' (bis auf Weiteres)' ) . ' – ' . $s['grund'], $schnell ),
	);
}

function golfplatz_termine_widget(): void {
	$d   = golfplatz_termine_daten();
	$tz  = new DateTimeZone( 'UTC' );
	$lnk = fn( string $text, string $url ) => $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>' : esc_html( $text );

	if ( $d['schnell'] ) {
		echo '<div class="notice notice-warning inline golfplatz-termine__jetzt"><p><strong>Jetzt:</strong> ' . implode( '<br>', array_map( 'esc_html', $d['schnell'] ) );
		if ( current_user_can( 'edit_sperrungen' ) ) {
			echo '<br><a href="' . esc_url( admin_url( 'admin.php?page=platzstatus' ) ) . '">Platzstatus öffnen</a>';
		}
		echo '</p></div>';
	}
	if ( ! $d['tage'] ) {
		echo '<p>Keine Termine in den nächsten ' . (int) GOLFPLATZ_TERMINE_TAGE . ' Tagen.</p>';
	}
	foreach ( $d['tage'] as $iso => $liste ) {
		$ts    = strtotime( $iso . ' 00:00:00 UTC' );
		$vorne = $ts === $d['heute'] ? 'Heute, ' : ( $ts === $d['heute'] + DAY_IN_SECONDS ? 'Morgen, ' : '' );
		echo '<h3 class="golfplatz-termine__tag">' . esc_html( $vorne . wp_date( 'l, j. F', $ts, $tz ) ) . '</h3><ul class="golfplatz-termine__liste">';
		foreach ( $liste as $e ) {
			echo '<li class="golfplatz-termine__eintrag">';
			echo '<span class="golfplatz-termine__zeit">' . esc_html( $e['zeit'] ) . '</span>';
			echo '<span class="dashicons ' . esc_attr( $e['icon'] ) . '" title="' . esc_attr( $e['art'] ) . '" aria-hidden="true"></span>';
			echo '<span class="golfplatz-termine__text"><span class="screen-reader-text">' . esc_html( $e['art'] ) . ': </span><strong>' . $lnk( $e['titel'], $e['link'] ) . '</strong>';
			if ( $e['status'] ) {
				echo ' <strong class="golfplatz-termine__status">' . esc_html( $e['status'] ) . '</strong>';
			}
			if ( $e['info'] ) {
				echo '<br><span class="description">' . esc_html( $e['info'] ) . '</span>';
			}
			foreach ( $e['unter'] as list( $text, $url ) ) {
				echo '<br><span class="golfplatz-termine__sperre"><span class="dashicons dashicons-lock" aria-hidden="true"></span> ' . $lnk( $text, $url ) . '</span>';
			}
			echo '</span></li>';
		}
		echo '</ul>';
	}
	$links = array();
	if ( current_user_can( 'edit_posts' ) ) {
		$links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=turnier' ) ) . '">Alle Turniere</a>';
	}
	if ( current_user_can( 'edit_sperrungen' ) ) {
		$links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=sperrung' ) ) . '">Alle Sperrungen</a>';
		$links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=sperrung&page=tee-belegung' ) ) . '">Turnier-Regeln</a>';
	}
	if ( $links ) {
		echo '<p class="golfplatz-termine__links">' . implode( ' · ', $links ) . '</p>';
	}
}

/**
 * Platzstatus wie auf der Startseite (Daten aus golfplatz_platzstatus_etch()): Ampel, Spielbedingungen, Fahnen,
 * Sperrungen heute/morgen, Übungsanlagen und Proshop. Zustand immer als Text, Symbol nur zusätzlich.
 */
function golfplatz_platzstatus_widget(): void {
	if ( ! function_exists( 'golfplatz_platzstatus_etch' ) ) {
		echo '<p>Das Snippet „Golfplatz Platzstatus“ ist nicht aktiv.</p>';
		return;
	}
	$d     = golfplatz_platzstatus_etch();
	$icon  = fn( string $mod ) => array( 'ok' => 'dashicons-yes-alt', 'blocked' => 'dashicons-dismiss', 'closed' => 'dashicons-clock', 'info' => 'dashicons-info', 'pin' => 'dashicons-flag' )[ $mod ] ?? 'dashicons-warning';
	$zeile = function ( string $mod, string $label, string $wert, string $info = '' ) use ( $icon ) {
		echo '<li class="golfplatz-status__zeile golfplatz-status__zeile--' . esc_attr( $mod ) . '"><span class="dashicons ' . esc_attr( $icon( $mod ) ) . '" aria-hidden="true"></span><span>' . esc_html( $label ) . '</span><strong>' . esc_html( $wert ) . '</strong>' . ( $info ? '<span class="description golfplatz-status__info">' . esc_html( $info ) . '</span>' : '' ) . '</li>';
	};

	$gesperrt = ! empty( $d['platz_gesperrt'] );
	echo '<p class="golfplatz-status__ampel golfplatz-status__ampel--' . esc_attr( $d['ampel']['zustand'] ?? '' ) . '"><span class="dashicons ' . ( $gesperrt ? 'dashicons-dismiss' : ( 'open' === ( $d['ampel']['zustand'] ?? '' ) ? 'dashicons-yes-alt' : 'dashicons-warning' ) ) . '" aria-hidden="true"></span> <strong>' . esc_html( $d['ampel']['text'] ?? '' ) . '</strong> <span class="description">Stand ' . esc_html( $d['stand'] ?? '' ) . ' Uhr</span>';
	if ( $gesperrt ) {
		echo '<br>' . esc_html( trim( $d['platz_gesperrt_grund'] . ( $d['platz_gesperrt_bis'] ? ' · bis ' . $d['platz_gesperrt_bis'] : '' ), ' ·' ) );
	}
	echo '</p>';

	echo '<h3 class="golfplatz-status__titel">Spielbedingungen</h3><ul class="golfplatz-status__liste">';
	foreach ( (array) $d['bedingungen'] as $b ) {
		$zeile( (string) $b['mod'], (string) $b['label'], (string) $b['wert'], (string) $b['info'] );
	}
	$f = (array) ( $d['fahnen'] ?? array() );
	if ( ! empty( $f['vorhanden'] ) ) {
		$zeile( 'pin', 'Fahnenposition', (string) $f['standard'], (string) ( $f['hinweis'] ?? '' ) );
		foreach ( (array) ( $f['ausnahmen'] ?? array() ) as $a ) {
			$zeile( 'pin', 'Bahn ' . $a['bahn'], (string) $a['text'], (string) ( $a['lage'] ?? '' ) );
		}
	} else {
		$zeile( 'info', 'Fahnenposition', 'kein gültiger Steckplan' );
	}
	echo '</ul>';

	foreach ( (array) $d['tage'] as $tag ) {
		echo '<h3 class="golfplatz-status__titel">' . esc_html( $tag['label'] ) . ' <span class="description">' . esc_html( $tag['datum'] ) . '</span></h3><ul class="golfplatz-status__liste">';
		if ( $tag['frei'] ) {
			$zeile( 'ok', 'Platz', 'uneingeschränkt bespielbar' );
		}
		foreach ( (array) $tag['eintraege'] as $e ) {
			$mod = str_contains( (string) $e['klasse'], 'status-entry--abgesagt' ) || str_contains( (string) $e['klasse'], 'status-entry--verschoben' ) || str_contains( (string) $e['klasse'], 'status-entry--info' ) ? 'info' : 'blocked';
			$zeile( $mod, (string) $e['bereich'] . ( $e['laeuft'] ? ' · jetzt' : '' ), (string) $e['zeit'], trim( $e['grund'] . ( ! empty( $e['start'] ) ? ' · ' . $e['start'] : '' ), ' ·' ) );
		}
		echo '</ul>';
	}

	echo '<h3 class="golfplatz-status__titel">Übungsanlagen &amp; Proshop</h3><ul class="golfplatz-status__liste">';
	foreach ( (array) $d['einrichtungen'] as $x ) {
		$zeile( (string) $x['mod'], (string) $x['name'], (string) $x['zustand'], implode( ' · ', array_column( (array) $x['infos'], 'text' ) ) );
	}
	echo '</ul><p class="golfplatz-status__aktionen">';
	if ( current_user_can( 'edit_sperrungen' ) ) {
		echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=platzstatus' ) ) . '">Platzstatus bearbeiten</a> ';
		echo '<a class="button" href="' . esc_url( admin_url( 'post-new.php?post_type=sperrung' ) ) . '">Neue Sperrung</a> ';
	}
	echo '<a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">So sieht es auf der Website aus</a></p>';
}

add_action(
	'wp_dashboard_setup',
	function () {
		if ( ! golfplatz_termine_erlaubt() ) {
			return;
		}
		// Platzstatus oben in der rechten Spalte
		wp_add_dashboard_widget( 'golfplatz_platzstatus', 'Platzstatus', 'golfplatz_platzstatus_widget', null, null, 'side', 'high' );
		wp_add_dashboard_widget( 'golfplatz_termine', 'Termine der nächsten ' . GOLFPLATZ_TERMINE_TAGE . ' Tage', 'golfplatz_termine_widget' );
		// Ganz nach oben in die linke Spalte
		global $wp_meta_boxes;
		$normal = $wp_meta_boxes['dashboard']['normal']['core'] ?? array();
		if ( isset( $normal['golfplatz_termine'] ) ) {
			$wp_meta_boxes['dashboard']['normal']['core'] = array( 'golfplatz_termine' => $normal['golfplatz_termine'] ) + $normal;
		}
	}
);

// Aufbau der Liste; Farben kommen aus dem WordPress-Backend
add_action(
	'admin_head-index.php',
	function () {
		echo '<style>
			#golfplatz_termine .golfplatz-termine__tag { margin: 1em 0 .4em; font-size: 13px; font-weight: 600; }
			#golfplatz_termine .golfplatz-termine__liste { margin: 0; }
			#golfplatz_termine .golfplatz-termine__eintrag { display: grid; grid-template-columns: 3.2em 22px 1fr; gap: 0 .4em; align-items: start; margin: 0 0 .6em; }
			#golfplatz_termine .golfplatz-termine__zeit { font-variant-numeric: tabular-nums; }
			#golfplatz_termine .golfplatz-termine__sperre .dashicons { font-size: 14px; width: 14px; height: 14px; vertical-align: -2px; }
			#golfplatz_termine .golfplatz-termine__links { margin-top: 1em; }
			#golfplatz_platzstatus .golfplatz-status__ampel { font-size: 14px; margin: .5em 0 1em; }
			#golfplatz_platzstatus .golfplatz-status__titel { margin: 1em 0 .3em; font-size: 13px; font-weight: 600; }
			#golfplatz_platzstatus .golfplatz-status__liste { margin: 0; }
			#golfplatz_platzstatus .golfplatz-status__zeile { display: grid; grid-template-columns: 22px minmax(7em, auto) 1fr; gap: 0 .4em; margin: 0 0 .35em; }
			#golfplatz_platzstatus .golfplatz-status__info { grid-column: 3; }
			#golfplatz_platzstatus .golfplatz-status__aktionen { margin-top: 1.2em; display: flex; flex-wrap: wrap; gap: .5em; align-items: center; }
		</style>';
	}
);
