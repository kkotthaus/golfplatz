<?php
/**
 * Plugin Name: Golfplatz – Mannschaften & Ligaspiele
 * Description: Stellt Mannschaften, Kader, Ligaspiele und Spielberichte Etch als Daten bereit: {options.golfplatz.mannschaften} (Übersicht, nächstes Spiel, alle Ligaspiele), je Mannschaft {this.golfplatz.team} (Spiele, Berichte, Spielführer, Kader) und je Spielbericht {this.golfplatz.bericht} (Mannschaft über das Ligaspiel). Spieler erscheinen nur mit Einwilligung. Keine Shortcodes – das Markup steht in den Etch-Templates (wordpress/etch/mannschaften.mjs).
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/mu-plugins/golfplatz-mannschaften.php
 */

defined( 'ABSPATH' ) || exit;

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

/** Alle Ligaspiele als Zeilen, chronologisch. */
function golfplatz_ligaspiele(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache    = array();
	$berichte = golfplatz_spielberichte_je_spiel();
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
			'spielort'        => (string) $m( 'ligaspiel_spielort' ),
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
	$spiele    = golfplatz_ligaspiele();
	$kommende  = array_values( array_filter( $spiele, fn( $s ) => $s['kommend'] ) );
	$vergangen = array_reverse( array_values( array_filter( $spiele, fn( $s ) => ! $s['kommend'] ) ) );
	$liste     = array();
	foreach ( golfplatz_mannschaften() as $t ) {
		$naechstes = current( array_filter( $kommende, fn( $s ) => $s['mannschaft_id'] === $t->ID ) );
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
	);
}

/** Eine Mannschaft: Spiele, Berichte, Spielführer, Kader (nur mit Einwilligung), Foto. */
function golfplatz_team_etch( int $id ): array {
	$spiele   = array_values( array_filter( golfplatz_ligaspiele(), fn( $s ) => $s['mannschaft_id'] === $id ) );
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
		'spiele'           => $spiele,
		'hat_spiele'       => (bool) $spiele,
		'berichte'         => array_reverse( $berichte ),
		'hat_berichte'     => (bool) $berichte,
		'spielfuehrer'     => $fuehrer,
		'hat_spielfuehrer' => '' !== $fuehrer,
		'kader'            => array_values( $kader ),
		'hat_kader'        => (bool) $kader,
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
