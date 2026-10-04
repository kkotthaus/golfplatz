<?php
/**
 * Plugin Name: Golfplatz – Aktuelles
 * Description: News als normale Beiträge unter /news/<slug>/. Stellt Etch die Beitragsliste bereit ({options.golfplatz.news}: neueste, beitraege, kategorien, seiten – Filter ?kategorie=<slug>, Blättern ?seite=<n>) und je Beitrag Kategorie, Datum, Teaser und Mitglieder-Sperre ({this.golfplatz.news}). Beiträge mit „Nur für Mitglieder“ liefern ohne Anmeldung keinen Volltext. Keine Shortcodes – das Markup bauen die Etch-Komponente „Newskarten“, die Seite /news/ und das Template single-post (wordpress/etch/news.mjs).
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-news.php
 */

defined( 'ABSPATH' ) || exit;

define( 'GOLFPLATZ_NEWS_PRO_SEITE', 9 );
define( 'GOLFPLATZ_NEWS_REWRITE', '1' );

// Beitrags-URL /news/<slug>/ – nur für Beiträge, andere Beitragstypen behalten ihre URLs.
add_action(
	'init',
	function () {
		add_rewrite_rule( '^news/([^/]+)/?$', 'index.php?name=$matches[1]', 'top' );
		if ( get_option( 'golfplatz_news_rewrite' ) !== GOLFPLATZ_NEWS_REWRITE ) {
			flush_rewrite_rules( false );
			update_option( 'golfplatz_news_rewrite', GOLFPLATZ_NEWS_REWRITE );
		}
	}
);
add_filter(
	'post_link',
	function ( $link, $post ) {
		return 'post' === $post->post_type && 'publish' === $post->post_status ? home_url( '/news/' . $post->post_name . '/' ) : $link;
	},
	10,
	2
);
// Kategorie-Links führen auf die gefilterte Übersicht statt auf ein eigenes Archiv.
add_filter(
	'term_link',
	function ( $link, $term, $taxonomy ) {
		return 'category' === $taxonomy ? home_url( '/news/?kategorie=' . $term->slug ) : $link;
	},
	10,
	3
);

/** Nur für Mitglieder und niemand angemeldet? */
function golfplatz_news_gesperrt( int $id ): bool {
	return (bool) get_post_meta( $id, 'nur_mitglieder', true ) && ! is_user_logged_in();
}

// Volltext gesperrter Beiträge gelangt nie ins HTML (auch nicht über Feeds oder die REST-API).
add_filter(
	'the_content',
	function ( $content ) {
		$id = get_the_ID();
		return $id && 'post' === get_post_type( $id ) && golfplatz_news_gesperrt( $id ) ? '' : $content;
	},
	1
);
add_filter(
	'rest_prepare_post',
	function ( $response, $post ) {
		if ( golfplatz_news_gesperrt( $post->ID ) ) {
			$daten                        = $response->get_data();
			$daten['content']['rendered'] = '';
			$response->set_data( $daten );
		}
		return $response;
	},
	10,
	2
);

/** Ein Beitrag für Karten und Kopf. */
function golfplatz_news_etch( WP_Post $p ): array {
	$kategorie = get_the_category( $p->ID );
	$kategorie = $kategorie ? $kategorie[0] : null;
	$bild      = get_the_post_thumbnail_url( $p, 'medium_large' );
	$teaser    = has_excerpt( $p ) ? $p->post_excerpt : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $p->post_content ) ), 30 );
	$mitglieder = (bool) get_post_meta( $p->ID, 'nur_mitglieder', true );
	return array(
		'titel'          => html_entity_decode( get_the_title( $p ) ),
		'link'           => get_permalink( $p ),
		'datum'          => wp_date( 'j. F Y', get_post_timestamp( $p ) ),
		'datum_iso'      => wp_date( 'Y-m-d', get_post_timestamp( $p ) ),
		'kategorie'      => $kategorie ? $kategorie->name : '',
		'kategorie_link' => $kategorie ? get_term_link( $kategorie ) : '',
		'teaser'         => html_entity_decode( $teaser ),
		'mitglieder'     => $mitglieder,
		'gesperrt'       => $mitglieder && ! is_user_logged_in(),
		'frei'           => ! ( $mitglieder && ! is_user_logged_in() ),
		'bild'           => $bild ?: '',
		'hat_bild'       => (bool) $bild,
		// KI-Kennzeichnung des Beitragsbilds (golfplatz-ki.php)
		'bild_ki'        => $bild ? ( function_exists( 'golfplatz_ki_daten' ) ? golfplatz_ki_daten( (int) get_post_thumbnail_id( $p ) ) : array( 'hat' => false ) ) : array( 'hat' => false ),
	);
}

/** Übersicht: drei neueste (Startseite) und die gefilterte, geblätterte Liste (/news/). */
function golfplatz_news_liste_etch(): array {
	$kat   = isset( $_GET['kategorie'] ) ? sanitize_title( wp_unslash( $_GET['kategorie'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$seite = max( 1, (int) ( $_GET['seite'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$basis = home_url( '/news/' );

	$abfrage = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => GOLFPLATZ_NEWS_PRO_SEITE,
		'paged'               => $seite,
		'ignore_sticky_posts' => true,
	);
	if ( $kat ) {
		$abfrage['category_name'] = $kat;
	}
	$q = new WP_Query( $abfrage );

	$neueste = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 3, 'ignore_sticky_posts' => true ) );

	$kategorien = array( array( 'name' => 'Alle', 'link' => $basis, 'aktiv' => '' === $kat ) );
	foreach ( get_categories( array( 'hide_empty' => true ) ) as $c ) {
		$kategorien[] = array( 'name' => $c->name, 'link' => add_query_arg( 'kategorie', $c->slug, $basis ), 'aktiv' => $kat === $c->slug );
	}

	$seiten = array();
	for ( $i = 1; $i <= $q->max_num_pages && $q->max_num_pages > 1; $i++ ) {
		$args     = array_filter( array( 'kategorie' => $kat, 'seite' => $i > 1 ? $i : '' ) );
		$seiten[] = array( 'nr' => $i, 'link' => add_query_arg( $args, $basis ), 'aktiv' => $i === $seite );
	}

	return array(
		'neueste'        => array_map( 'golfplatz_news_etch', $neueste ),
		'beitraege'      => array_map( 'golfplatz_news_etch', $q->posts ),
		'anzahl'         => (int) $q->found_posts,
		'leer'           => ! $q->posts,
		'kategorien'     => $kategorien,
		'hat_kategorien' => count( $kategorien ) > 2,
		'seiten'         => $seiten,
		'hat_seiten'     => (bool) $seiten,
	);
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			$data['golfplatz']['news'] = golfplatz_news_liste_etch();
		}
		return $data;
	}
);

add_filter(
	'etch/dynamic_data/post',
	function ( $data, $post_id = 0 ) {
		if ( is_array( $data ) && $post_id && 'post' === get_post_type( $post_id ) ) {
			$data['golfplatz']['news'] = golfplatz_news_etch( get_post( $post_id ) );
		}
		return $data;
	},
	10,
	2
);
