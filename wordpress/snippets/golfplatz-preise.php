<?php
/**
 * Plugin Name: Golfplatz – Preise
 * Description: Stellt die Preise (Beitragstyp „preis“, gruppiert nach Preiskategorie) Etch als Daten bereit: {options.golfplatz.preise.tabellen}, je Kategorie auch als Karten (karten[], z. B. Mitgliedschaften). Tabellen mit Wochentagen werden als Matrix aufbereitet (Tarif je Zeile, Spalten Mo–Sa und So/Feiertag, Varianten wie „mit DGV-Ausweis „R““ als Unterzeile), die übrigen als Liste. Keine Shortcodes – das Markup baut die Etch-Komponente „Preistabelle“ (wordpress/etch/preise.mjs).
 * Version: 1.1.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-preise.php
 */

defined( 'ABSPATH' ) || exit;

/** Kategorie (Name des Begriffs) → Schlüssel für die Komponente (Eigenschaft „kategorie“). */
define( 'GOLFPLATZ_PREIS_KATEGORIEN', array(
	'Greenfee'         => 'greenfee',
	'Turnier-Greenfee' => 'turnier',
	'Kooperation'      => 'kooperationen',
	'Leihgeräte'       => 'leihe',
	'Mitgliedschaft'   => 'mitgliedschaft',
) );

define( 'GOLFPLATZ_PREIS_EINHEITEN', array(
	'runde18'  => '18 Loch',
	'runde9'   => '9 Loch',
	'runde'    => 'pro Runde',
	'tag'      => 'pro Tag',
	'monat'    => 'pro Monat',
	'jahr'     => 'pro Jahr',
	'einmalig' => 'einmalig',
) );

/** Spalten der Matrix. Sonntag und Feiertag teilen sich eine Spalte. */
define( 'GOLFPLATZ_PREIS_SPALTEN', array(
	array( 'kurz' => 'Mo', 'lang' => 'Montag' ),
	array( 'kurz' => 'Di', 'lang' => 'Dienstag' ),
	array( 'kurz' => 'Mi', 'lang' => 'Mittwoch' ),
	array( 'kurz' => 'Do', 'lang' => 'Donnerstag' ),
	array( 'kurz' => 'Fr', 'lang' => 'Freitag' ),
	array( 'kurz' => 'Sa', 'lang' => 'Samstag' ),
	array( 'kurz' => 'So/Feiertag', 'lang' => 'Sonntag und Feiertag' ),
) );

/** „40 €“, „37,50 €“ */
function golfplatz_euro( $betrag ): string {
	return golfplatz_preis_zahl( $betrag ) . ' €';
}

/** „40“, „37,50“ (für Tabellen, deren Kopf „€“ nennt) */
function golfplatz_preis_zahl( $betrag ): string {
	$n = (float) $betrag;
	return number_format( $n, floor( $n ) === $n ? 0 : 2, ',', '.' );
}

/**
 * Spalten (0 = Mo … 6 = So/Feiertag) aus „Gültig an“, z. B. „Mo–Fr“, „Sa, So, Feiertag“, „freitags“, „mittwochs, freitags“, „täglich“.
 * Leer, wenn nichts erkannt wird.
 */
function golfplatz_preis_tage( string $text ): array {
	$namen = array( 'mo' => 0, 'di' => 1, 'mi' => 2, 'do' => 3, 'fr' => 4, 'sa' => 5, 'so' => 6 );
	$text  = mb_strtolower( trim( $text ) );
	if ( '' === $text ) {
		return array();
	}
	if ( str_contains( $text, 'täglich' ) ) {
		return range( 0, 6 );
	}
	$tage = array();
	foreach ( preg_split( '/\s*(,|\bund\b|\/)\s*/u', $text ) as $teil ) {
		if ( str_starts_with( $teil, 'feiertag' ) ) {
			$tage[] = 6;
			continue;
		}
		if ( str_starts_with( $teil, 'werktag' ) ) {
			$tage = array_merge( $tage, range( 0, 5 ) );
			continue;
		}
		// Bereich „mo–fr“ bzw. „mo-fr“
		if ( preg_match( '/^([a-zä]{2})[a-zä]*\s*[–-]\s*([a-zä]{2})/u', $teil, $m ) && isset( $namen[ $m[1] ], $namen[ $m[2] ] ) ) {
			$tage = array_merge( $tage, range( $namen[ $m[1] ], $namen[ $m[2] ] ) );
			continue;
		}
		// Einzelner Tag, auch ausgeschrieben („freitags“, „Sonntag“)
		$kurz = mb_substr( $teil, 0, 2 );
		if ( isset( $namen[ $kurz ] ) ) {
			$tage[] = $namen[ $kurz ];
		}
	}
	return array_values( array_unique( $tage ) );
}

/** Rohdaten aller Preise einer Kategorie in der Reihenfolge von „Reihenfolge“ (menu_order). */
function golfplatz_preise_roh( int $term_id ): array {
	$zeilen = array();
	$posts  = get_posts(
		array(
			'post_type'      => 'preis',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'no_found_rows'  => true,
			'tax_query'      => array( array( 'taxonomy' => 'preiskategorie', 'field' => 'term_id', 'terms' => $term_id ) ),
		)
	);
	foreach ( $posts as $p ) {
		$m       = fn( string $k ) => get_post_meta( $p->ID, $k, true );
		$titel   = get_the_title( $p );
		$anfrage = (bool) $m( 'preis_auf_anfrage' ) || '' === (string) $m( 'preis_betrag' );
		$einheit = (string) $m( 'preis_einheit' );
		$einheit = GOLFPLATZ_PREIS_EINHEITEN[ $einheit ] ?? $einheit;
		// Keine Dopplung: steht die Einheit schon in der Leistung („18 Loch mit DGV-Ausweis“), entfällt sie
		if ( $anfrage || ( '' !== $einheit && false !== mb_stripos( $titel, $einheit ) ) ) {
			$einheit = '';
		}
		$zeilen[] = array(
			'titel'   => $titel,
			'zusatz'  => (string) $m( 'preis_zusatz' ),
			'tage'    => (string) $m( 'preis_tage' ),
			'spalten' => golfplatz_preis_tage( (string) $m( 'preis_tage' ) ),
			'anfrage' => $anfrage,
			'betrag'  => $m( 'preis_betrag' ),
			'einheit' => $einheit,
			// Für Karten (Mitgliedschaft): Hervorhebung, Aufnahmegebühr, Leistungen (eine je Zeile)
			'hervorheben' => (bool) $m( 'preis_hervorheben' ),
			'aufnahme'    => (string) $m( 'preis_aufnahme' ),
			'leistungen'  => array_values( array_filter( array_map( 'trim', preg_split( '/\R/',(string) $m( 'preis_leistungen' ) ) ) ) ),
		);
	}
	return $zeilen;
}

/**
 * Matrix: je Tarif (Titel + Einheit) eine Zeile mit 7 Zellen. Tarife, deren Titel mit einem anderen Tarif beginnt
 * („18 Loch mit DGV-Ausweis „R““ nach „18 Loch“), werden dessen Unterzeile mit dem Rest als Beschriftung.
 */
function golfplatz_preise_matrix( array $roh ): array {
	$tarife = array();
	foreach ( $roh as $z ) {
		$key = $z['titel'] . '|' . $z['einheit'];
		if ( ! isset( $tarife[ $key ] ) ) {
			$tarife[ $key ] = array( 'titel' => $z['titel'], 'zusatz' => $z['zusatz'], 'einheit' => $z['einheit'], 'zellen' => array_fill( 0, 7, '' ) );
		}
		foreach ( $z['spalten'] ?: range( 0, 6 ) as $i ) {
			$tarife[ $key ]['zellen'][ $i ] = $z['anfrage'] ? 'auf Anfrage' : golfplatz_euro( $z['betrag'] );
		}
	}
	// Unterzeilen zuordnen: längster passender Obertitel gewinnt
	$bloecke = array();
	$index   = array();
	foreach ( $tarife as $key => $t ) {
		$ober = null;
		foreach ( $index as $okey => $pos ) {
			$ot = $tarife[ $okey ]['titel'];
			if ( $ot !== $t['titel'] && str_starts_with( $t['titel'], $ot . ' ' ) && ( ! $ober || mb_strlen( $ot ) > mb_strlen( $tarife[ $ober ]['titel'] ) ) ) {
				$ober = $okey;
			}
		}
		$zellen = array_map( fn( $w ) => array( 'wert' => $w ), $t['zellen'] );
		if ( $ober ) {
			$bloecke[ $index[ $ober ] ]['unter'][] = array(
				'titel'  => trim( mb_substr( $t['titel'], mb_strlen( $tarife[ $ober ]['titel'] ) ) ),
				'zusatz' => $t['zusatz'],
				'zellen' => $zellen,
			);
			continue;
		}
		$index[ $key ] = count( $bloecke );
		$bloecke[]     = array(
			'titel'   => $t['titel'],
			'zusatz'  => $t['zusatz'],
			'einheit' => $t['einheit'],
			'zellen'  => $zellen,
			'unter'   => array(),
		);
	}
	return $bloecke;
}

/**
 * Preise einer Kategorie als Karten (Komponente „Preiskarten“): Betrag groß, Einheit, Zusatz, Aufnahmegebühr, Leistungen.
 * Hervorgehobene Karte mit Modifier „featured“ und Kennzeichnung „Beliebt“.
 */
function golfplatz_preise_karten( array $roh ): array {
	return array_map(
		fn( $z ) => array(
			'titel'          => $z['titel'],
			'betrag'         => $z['anfrage'] ? 'auf Anfrage' : golfplatz_euro( $z['betrag'] ),
			'einheit'        => $z['anfrage'] ? '' : $z['einheit'],
			'zusatz'         => $z['zusatz'],
			'aufnahme'       => '' !== $z['aufnahme'] && (float) $z['aufnahme'] > 0 ? 'Aufnahmegebühr ' . golfplatz_euro( $z['aufnahme'] ) : '',
			'leistungen'     => array_map( fn( $l ) => array( 'text' => $l ), $z['leistungen'] ),
			'hat_leistungen' => (bool) $z['leistungen'],
			'hervorheben'    => $z['hervorheben'],
			'mod'            => $z['hervorheben'] ? 'featured' : 'normal',
			'button'         => $z['anfrage'] ? 'Gespräch vereinbaren' : 'Anfragen',
			'button_mod'     => $z['hervorheben'] ? 'primary' : 'outline',
		),
		$roh
	);
}

/** Alle Preistabellen je Kategorie. */
function golfplatz_preise_etch(): array {
	$tabellen = array();
	foreach ( get_terms( array( 'taxonomy' => 'preiskategorie', 'hide_empty' => true ) ) as $term ) {
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$roh    = golfplatz_preise_roh( $term->term_id );
		$matrix = (bool) array_filter( $roh, fn( $z ) => $z['spalten'] );
		$tabellen[] = array(
			'key'     => GOLFPLATZ_PREIS_KATEGORIEN[ $term->name ] ?? $term->slug,
			'name'    => $term->name,
			'matrix'  => $matrix,
			'bloecke' => $matrix ? golfplatz_preise_matrix( $roh ) : array(),
			'karten'  => golfplatz_preise_karten( $roh ),
			'zeilen'  => $matrix ? array() : array_map(
				// Einheit gehört zur Leistung („E-Buggy 18 Loch“, „Trolley pro Runde“), in der Preisspalte nur der Betrag
				fn( $z ) => array(
					'titel'   => trim( $z['titel'] . ' ' . $z['einheit'] ),
					'zusatz'  => $z['zusatz'],
					'betrag'  => $z['anfrage'] ? 'auf Anfrage' : golfplatz_euro( $z['betrag'] ),
					'einheit' => '',
				),
				$roh
			),
		);
	}
	return array(
		'jahr'     => (int) wp_date( 'Y' ),
		'spalten'  => GOLFPLATZ_PREIS_SPALTEN,
		'tabellen' => $tabellen,
	);
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			$data['golfplatz']['preise'] = golfplatz_preise_etch();
		}
		return $data;
	}
);

/**
 * Twilight: Regel aus den Clubdaten und die heutige Startzeit (Sonnenuntergang am Platz minus Stunden).
 * {options.golfplatz.twilight.regel|hat_zeit|ab|sonnenuntergang}
 */
function golfplatz_twilight_etch(): array {
	$club    = (array) get_option( 'clubdaten', array() );
	$regel   = trim( (string) ( $club['twilight_regel'] ?? '' ) );
	$stunden = (float) str_replace( ',', '.', (string) ( $club['twilight_stunden'] ?? '' ) );
	$daten   = array( 'regel' => $regel, 'hat_zeit' => false, 'ab' => '', 'sonnenuntergang' => '' );
	if ( '' === $regel || $stunden <= 0 || ! preg_match( '/^\s*(-?\d+(?:[.,]\d+)?)\s*[,;]\s*(-?\d+(?:[.,]\d+)?)\s*$/', (string) ( $club['club_geo'] ?? '' ), $g ) ) {
		return $daten;
	}
	$mittag = ( new DateTimeImmutable( 'today 12:00', wp_timezone() ) )->getTimestamp();
	$sonne  = date_sun_info( $mittag, (float) str_replace( ',', '.', $g[1] ), (float) str_replace( ',', '.', $g[2] ) );
	if ( empty( $sonne['sunset'] ) || ! is_int( $sonne['sunset'] ) ) {
		return $daten;
	}
	$ab = $sonne['sunset'] - (int) round( $stunden * HOUR_IN_SECONDS );
	return array(
		'regel'           => $regel,
		'hat_zeit'        => true,
		'ab'              => wp_date( 'H:i', $ab ),
		'sonnenuntergang' => wp_date( 'H:i', $sonne['sunset'] ),
	);
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			$data['golfplatz']['twilight'] = golfplatz_twilight_etch();
		}
		return $data;
	}
);
