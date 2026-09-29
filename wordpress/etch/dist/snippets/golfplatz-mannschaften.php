<?php
/**
 * Plugin Name: Golfplatz – Mannschaften & Ligaspiele
 * Description: Stellt Mannschaften, Kader, Ligaspiele und Spielberichte Etch als Daten bereit: {options.golfplatz.mannschaften} (Übersicht, nächstes Spiel, alle Ligaspiele einer Saison, Auswahl per ?saison=), je Mannschaft {this.golfplatz.team} (Spiele, Berichte, Spielführer, Kader) und je Spielbericht {this.golfplatz.bericht} (Mannschaft über das Ligaspiel). Spieler erscheinen nur mit Einwilligung. Keine Shortcodes – das Markup steht in den Etch-Templates (wordpress/etch/mannschaften.mjs).
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-mannschaften.php
 */

defined( 'ABSPATH' ) || exit;

// Feld „Reihenfolge“ (menu_order) im Editor der Mannschaft – bestimmt die Reihenfolge der Übersicht.
add_action( 'init', fn() => add_post_type_support( 'mannschaft', 'page-attributes' ), 20 );

/** Name eines Spielers – nur mit Einwilligung (spieler_einwilligung), sonst leer. */
function golfplatz_spieler_name( $id ): string {
	$id = (int) $id;
	if ( ! $id || 'spieler' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) || ! get_post_meta( $id, 'spieler_einwilligung', true ) ) {
		return '';
	}
	$name = trim( get_post_meta( $id, 'spieler_vorname', true ) . ' ' . get_post_meta( $id, 'spieler_nachname', true ) );
	return '' !== $name ? $name : get_the_title( $id );
}

/** Termin eines Ligaspiels. Meta Box speichert „Ortszeit als Unix-Zeit“, deshalb in UTC formatieren. */
function golfplatz_ligaspiel_zeit( int $ts, string $format ): string {
	return $ts ? wp_date( $format, $ts, new DateTimeZone( 'UTC' ) ) : '';
}

/** Alle Mannschaften in fester Reihenfolge (Reihenfolge-Feld, dann Titel). */
function golfplatz_mannschaften(): array {
	static $cache = null;
	if ( null === $cache ) {
		$cache = get_posts(
			array(
				'post_type'      => 'mannschaft',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'no_found_rows'  => true,
			)
		);
	}
	return $cache;
}

/** Spielberichte je Ligaspiel-ID. */
function golfplatz_spielberichte_je_spiel(): array {
	static $cache = null;
	if ( null === $cache ) {
		$cache = array();
		foreach ( get_posts( array( 'post_type' => 'spielbericht', 'post_status' => 'publish', 'posts_per_page' => 200, 'no_found_rows' => true ) ) as $b ) {
			$spiel = (int) get_post_meta( $b->ID, 'bericht_ligaspiel', true );
			if ( $spiel && ! isset( $cache[ $spiel ] ) ) {
				$cache[ $spiel ] = $b;
			}
		}
	}
	return $cache;
}

/** Websites der Gastclubs (Mannschaften → Gastclubs), Schlüssel = Clubname wie beim Verband. */
function golfplatz_gastclub_websites(): array {
	static $cache = null;
	if ( null === $cache ) {
		$cache = array();
		if ( post_type_exists( 'gastclub' ) ) {
			foreach ( get_posts( array( 'post_type' => 'gastclub', 'post_status' => 'publish', 'posts_per_page' => -1, 'no_found_rows' => true ) ) as $c ) {
				$url = trim( (string) get_post_meta( $c->ID, 'gastclub_website', true ) );
				if ( '' !== $url ) {
					$cache[ mb_strtolower( trim( html_entity_decode( $c->post_title, ENT_QUOTES, 'UTF-8' ) ) ) ] = $url; // „&amp;“ im Titel
				}
			}
		}
	}
	return $cache;
}

/** Alle Ligaspiele als Zeilen, chronologisch. Spiele von Mannschaften, die nicht veröffentlicht sind, fallen weg. */
function golfplatz_ligaspiele(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache    = array();
	$berichte = golfplatz_spielberichte_je_spiel();
	$websites = golfplatz_gastclub_websites();
	$sichtbar = array_flip( wp_list_pluck( golfplatz_mannschaften(), 'ID' ) );
	$heute    = wp_date( 'Y-m-d' );
	$posts    = get_posts(
		array(
			'post_type'      => 'ligaspiel',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'meta_key'       => 'ligaspiel_termin',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	foreach ( $posts as $p ) {
		$m           = fn( string $k ) => get_post_meta( $p->ID, $k, true );
		$ts          = (int) $m( 'ligaspiel_termin' );
		$mannschaft  = (int) $m( 'ligaspiel_mannschaft' );
		if ( ! isset( $sichtbar[ $mannschaft ] ) ) {
			continue;
		}
		$ort         = (string) $m( 'ligaspiel_spielort' );
		$ort_link    = $websites[ mb_strtolower( trim( $ort ) ) ] ?? '';
		$platz       = (int) $m( 'ligaspiel_platzierung' );
		$ergebnis    = $platz ? $platz . '. Platz' : trim( (string) $m( 'ligaspiel_ergebnis' ) );
		$heim        = (bool) $m( 'ligaspiel_heimspiel' );
		$bericht     = $berichte[ $p->ID ] ?? null;
		$uhrzeit     = golfplatz_ligaspiel_zeit( $ts, 'H:i' );
		$cache[]     = array(
			'id'              => $p->ID,
			'mannschaft_id'   => $mannschaft,
			'datum_iso'       => golfplatz_ligaspiel_zeit( $ts, 'Y-m-d' ),
			'datum'           => golfplatz_ligaspiel_zeit( $ts, 'D, d.m.' ),
			'datum_lang'      => golfplatz_ligaspiel_zeit( $ts, 'l, j. F Y' ),
			'uhrzeit'         => $uhrzeit && '00:00' !== $uhrzeit ? $uhrzeit . ' Uhr' : '',
			'kommend'         => golfplatz_ligaspiel_zeit( $ts, 'Y-m-d' ) >= $heute,
			'mannschaft'      => $mannschaft ? get_the_title( $mannschaft ) : '',
			'mannschaft_link' => $mannschaft ? get_permalink( $mannschaft ) : '',
			'spieltag'        => (int) $m( 'ligaspiel_spieltag' ) ? (int) $m( 'ligaspiel_spieltag' ) . '. Spieltag' : '',
			'spielort'        => $ort,
			'spielort_link'   => $heim ? '' : $ort_link,
			'hat_spielort_link' => ! $heim && '' !== $ort_link,
			'saison'          => (int) $m( 'ligaspiel_saison' ) ?: (int) golfplatz_ligaspiel_zeit( $ts, 'Y' ),
			'liga'            => (string) $m( 'ligaspiel_liga' ),
			'verband_link'    => (string) $m( 'ligaspiel_verband_link' ),
			'heim'            => $heim,
			'mod'             => $heim ? 'home' : 'away',
			'ergebnis'        => $ergebnis,
			'hat_ergebnis'    => '' !== $ergebnis,
			'platzierung'     => $platz,
			'hat_bericht'     => (bool) $bericht,
			'bericht_link'    => $bericht ? get_permalink( $bericht ) : '',
			'bericht_titel'   => $bericht ? get_the_title( $bericht ) : '',
		);
	}
	return $cache;
}

/** Übersicht: Mannschaftskarten mit nächstem Spiel, dazu alle kommenden und vergangenen Ligaspiele. */
function golfplatz_mannschaften_etch(): array {
	$alle    = golfplatz_ligaspiele();
	$saisons = array_values( array_unique( array_column( $alle, 'saison' ) ) );
	rsort( $saisons );
	// Aktuelle Saison: laufendes Jahr, falls es Spiele hat, sonst die neueste; per ?saison=2025 wählbar
	$aktuell = in_array( (int) wp_date( 'Y' ), $saisons, true ) ? (int) wp_date( 'Y' ) : (int) ( $saisons[0] ?? wp_date( 'Y' ) );
	$wahl    = isset( $_GET['saison'] ) ? (int) $_GET['saison'] : $aktuell; // phpcs:ignore WordPress.Security.NonceVerification
	$wahl    = in_array( $wahl, $saisons, true ) ? $wahl : $aktuell;
	$spiele    = array_values( array_filter( $alle, fn( $s ) => $s['saison'] === $wahl ) );
	$kommende  = array_values( array_filter( $spiele, fn( $s ) => $s['kommend'] ) );
	$vergangen = array_reverse( array_values( array_filter( $spiele, fn( $s ) => ! $s['kommend'] ) ) );
	$naechste  = array_values( array_filter( $alle, fn( $s ) => $s['kommend'] ) );
	$archiv    = get_post_type_archive_link( 'mannschaft' ) ?: home_url( '/mannschaften/' );
	$liste     = array();
	foreach ( golfplatz_mannschaften() as $t ) {
		$naechstes = current( array_filter( $naechste, fn( $s ) => $s['mannschaft_id'] === $t->ID ) );
		$fuehrer   = golfplatz_spieler_name( get_post_meta( $t->ID, 'mannschaft_spielfuehrer', true ) );
		$liste[]   = array(
			'titel'            => get_the_title( $t ),
			'link'             => get_permalink( $t ),
			'liga'             => (string) get_post_meta( $t->ID, 'mannschaft_liga', true ),
			'spielfuehrer'     => $fuehrer,
			'hat_spielfuehrer' => '' !== $fuehrer,
			'naechstes'        => $naechstes ? 'Nächstes Spiel: ' . $naechstes['datum'] . ', ' . $naechstes['spielort'] : 'Saison beendet',
		);
	}
	$anzahl = count( $liste );
	return array(
		'anzahl'         => $anzahl,
		'anzahl_text'    => 1 === $anzahl ? '1 Mannschaft' : $anzahl . ' Mannschaften',
		'liste'          => $liste,
		'kommende'       => $kommende,
		'vergangene'     => $vergangen,
		'hat_kommende'   => (bool) $kommende,
		'hat_vergangene' => (bool) $vergangen,
		'saison'         => (string) $wahl,
		'saisons'        => array_map( fn( $j ) => array( 'jahr' => (string) $j, 'link' => add_query_arg( 'saison', $j, $archiv ) . '#ligaspiele', 'aktiv' => $j === $wahl ? 'aktiv' : '' ), $saisons ),
		'hat_saisons'    => count( $saisons ) > 1,
	);
}

/** Eine Mannschaft: Spiele, Berichte, Spielführer, Kader (nur mit Einwilligung), Foto. */
function golfplatz_team_etch( int $id ): array {
	$spiele   = array_values( array_filter( golfplatz_ligaspiele(), fn( $s ) => $s['mannschaft_id'] === $id ) );
	// Je Saison ein Block, neueste zuerst und aufgeklappt; Liga und Link zur Tabelle beim Verband
	$saisons = array();
	foreach ( $spiele as $s ) {
		$saisons[ $s['saison'] ][] = $s;
	}
	krsort( $saisons );
	$bloecke = array();
	foreach ( $saisons as $jahr => $liste ) {
		$ligen     = array_values( array_unique( array_filter( array_column( $liste, 'liga' ) ) ) );
		$links     = array_values( array_unique( array_filter( array_column( $liste, 'verband_link' ) ) ) );
		$bloecke[] = array(
			'jahr'             => (string) $jahr,
			'titel'            => 'Saison ' . $jahr . ( $ligen ? ' · ' . implode( ', ', $ligen ) : '' ),
			'spiele'           => $liste,
			'offen'            => ! $bloecke,
			'verband_link'     => $links[0] ?? '',
			'hat_verband_link' => (bool) $links,
		);
	}
	$berichte = array();
	foreach ( $spiele as $s ) {
		if ( $s['hat_bericht'] ) {
			$berichte[] = array( 'titel' => $s['bericht_titel'], 'link' => $s['bericht_link'], 'datum' => $s['datum_lang'] );
		}
	}
	$fuehrer_id = (int) get_post_meta( $id, 'mannschaft_spielfuehrer', true );
	$fuehrer    = golfplatz_spieler_name( $fuehrer_id );
	$kader      = array();
	foreach ( (array) get_post_meta( $id, 'mannschaft_kader', false ) as $wert ) {
		foreach ( (array) $wert as $sid ) {
			$name = golfplatz_spieler_name( $sid );
			if ( '' !== $name ) {
				$kader[ (int) $sid ] = array( 'name' => $name, 'ist_spielfuehrer' => (int) $sid === $fuehrer_id );
			}
		}
	}
	$foto = get_the_post_thumbnail_url( $id, 'large' );
	return array(
		'liga'             => (string) get_post_meta( $id, 'mannschaft_liga', true ),
		'foto'             => $foto ?: '',
		'hat_foto'         => (bool) $foto,
		'foto_alt'         => 'Mannschaftsfoto ' . get_the_title( $id ),
		'saisons'          => $bloecke,
		'hat_spiele'       => (bool) $spiele,
		'berichte'         => array_reverse( $berichte ),
		'hat_berichte'     => (bool) $berichte,
		'spielfuehrer'     => $fuehrer,
		'hat_spielfuehrer' => '' !== $fuehrer,
		'kader'            => array_values( $kader ),
		'hat_kader'        => (bool) $kader,
		'hat_personen'     => '' !== $fuehrer || $kader, // Kasten Spielführer/Kader nur, wenn jemand zugestimmt hat
	);
}

/** Ein Spielbericht: Mannschaft, Spieltag und Datum über das Ligaspiel, Autor, Bilder. */
function golfplatz_bericht_etch( int $id ): array {
	$spiel_id = (int) get_post_meta( $id, 'bericht_ligaspiel', true );
	$spiel    = current( array_filter( golfplatz_ligaspiele(), fn( $s ) => $s['id'] === $spiel_id ) ) ?: null;
	$autor    = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) );
	$meta     = array_filter( array( $autor ? 'Von ' . $autor : '', $spiel['spielort'] ?? '', $spiel && $spiel['platzierung'] ? $spiel['ergebnis'] : '' ) );
	$bilder   = array();
	foreach ( (array) get_post_meta( $id, 'bericht_bilder', false ) as $bild ) {
		$url = wp_get_attachment_image_url( (int) $bild, 'large' );
		if ( $url ) {
			$bilder[] = array( 'url' => $url, 'alt' => (string) get_post_meta( (int) $bild, '_wp_attachment_image_alt', true ) );
		}
	}
	return array(
		'hat_mannschaft'  => $spiel && $spiel['mannschaft'],
		'mannschaft'      => $spiel['mannschaft'] ?? '',
		'mannschaft_link' => $spiel['mannschaft_link'] ?? '',
		'eyebrow'         => $spiel ? implode( ' · ', array_filter( array( $spiel['mannschaft'], $spiel['spieltag'], $spiel['datum_lang'] ) ) ) : 'Spielbericht',
		'meta'            => implode( ' · ', $meta ),
		'bilder'          => $bilder,
		'hat_bilder'      => (bool) $bilder,
	);
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) && post_type_exists( 'mannschaft' ) ) {
			$data['golfplatz']['mannschaften'] = golfplatz_mannschaften_etch();
		}
		return $data;
	}
);

add_filter(
	'etch/dynamic_data/post',
	function ( $data, $post_id = 0 ) {
		if ( ! is_array( $data ) || ! $post_id ) {
			return $data;
		}
		$typ = get_post_type( $post_id );
		if ( 'mannschaft' === $typ ) {
			$data['golfplatz']['team'] = golfplatz_team_etch( (int) $post_id );
		} elseif ( 'spielbericht' === $typ ) {
			$data['golfplatz']['bericht'] = golfplatz_bericht_etch( (int) $post_id );
		}
		return $data;
	},
	10,
	2
);
