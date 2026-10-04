<?php
/**
 * Plugin Name: Golfplatz – KI-Kennzeichnung
 * Description: In der Mediathek je Bild oder Video die Art der KI-Nutzung (AI · KI wurde verwendet, AI GENERATED · vollständig von KI erzeugt, AI MODIFIED · durch KI verändert), optional mit Werkzeug. Auf der Website erscheint ein „AI“-Symbol am Bild (Übergangs-Symbol nach dem Verhaltenskodex der EU-Kommission zu Art. 50 KI-Verordnung), das beim Darüberfahren aufklappt und den Text zeigt; der Hinweis steht zusätzlich im Alternativtext. Position, Stil und Größe unter Medien › KI-Kennzeichnung. Daten für Etch: golfplatz_ki_daten() → { hat, kurz, label, text, mod }.
 * Version: 2.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/snippets/golfplatz-ki.php
 */

defined( 'ABSPATH' ) || exit;

define( 'GOLFPLATZ_KI_OPTION', 'golfplatz_ki' );

/** Arten der KI-Nutzung: Schlüssel → Kennung (aufgeklappt, groß) und Erklärung. */
define(
	'GOLFPLATZ_KI_ARTEN',
	array(
		'ai'        => array( 'label' => 'AI', 'text' => 'KI wurde verwendet' ),
		'generated' => array( 'label' => 'AI GENERATED', 'text' => 'Inhalt vollständig von KI erzeugt' ),
		'modified'  => array( 'label' => 'AI MODIFIED', 'text' => 'Inhalt durch KI verändert' ),
	)
);

/** Darstellung: Position, Stil, Größe – je Schlüssel → Bezeichnung im Backend. */
define(
	'GOLFPLATZ_KI_DARSTELLUNG',
	array(
		'position' => array( 'oben-rechts' => 'oben rechts', 'oben-links' => 'oben links', 'unten-rechts' => 'unten rechts', 'unten-links' => 'unten links', 'unter' => 'unter dem Bild' ),
		'stil'     => array( 'dunkel' => 'dunkel', 'hell' => 'hell', 'club' => 'Clubgrün', 'rahmen' => 'nur Rahmen' ),
		'groesse'  => array( 'normal' => 'normal', 'klein' => 'klein' ),
	)
);

// Feld in der Mediathek (Anhang bearbeiten und Medien-Dialog)
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$boxen[] = array(
			'id'          => 'ki-kennzeichnung',
			'title'       => 'KI-Kennzeichnung',
			'post_types'  => array( 'attachment' ),
			'media_modal' => true,
			'fields'      => array(
				array(
					'id'          => 'ki_art',
					'name'        => 'KI-Nutzung',
					'type'        => 'select',
					'placeholder' => 'keine KI',
					'options'     => array_map( fn( $a ) => $a['label'] . ' – ' . $a['text'], GOLFPLATZ_KI_ARTEN ),
					'desc'        => 'Auf der Website erscheint am Bild das Symbol „AI“, beim Darüberfahren mit diesem Text.',
				),
				array(
					'id'          => 'ki_werkzeug',
					'name'        => 'Werkzeug',
					'type'        => 'text',
					'placeholder' => 'z. B. Midjourney, Firefly',
					'desc'        => 'Optional, erscheint im Hinweis (wenn unter Medien › KI-Kennzeichnung eingeschaltet).',
				),
				array(
					'id'          => 'ki_position',
					'name'        => 'Position des Symbols',
					'type'        => 'select',
					'placeholder' => 'wie unter Medien › KI-Kennzeichnung eingestellt',
					'options'     => GOLFPLATZ_KI_DARSTELLUNG['position'],
					'desc'        => 'Nur nötig, wenn das Symbol an der üblichen Stelle etwas Wichtiges im Bild verdeckt.',
				),
			),
		);
		return $boxen;
	}
);

// Einstellungsseite Medien › KI-Kennzeichnung
add_filter(
	'mb_settings_pages',
	function ( $seiten ) {
		$seiten[] = array(
			'id'          => 'ki-kennzeichnung',
			'option_name' => GOLFPLATZ_KI_OPTION,
			'menu_title'  => 'KI-Kennzeichnung',
			'page_title'  => 'KI-Kennzeichnung von Bildern und Videos',
			'parent'      => 'upload.php',
			'capability'  => 'edit_pages',
			'style'         => 'no-boxes',
			'columns'       => 1,
			'submit_button' => 'Speichern',
			'message'       => 'Einstellungen gespeichert.',
		);
		return $seiten;
	}
);
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$d       = GOLFPLATZ_KI_DARSTELLUNG;
		$boxen[] = array(
			'id'             => 'ki-kennzeichnung-einstellungen',
			'title'          => 'Darstellung',
			'settings_pages' => 'ki-kennzeichnung',
			'fields'         => array(
				array(
					'type' => 'custom_html',
					'std'  => '<p>So erscheint das „AI“-Symbol an Bildern, die in der Mediathek als KI-Inhalt markiert sind. Es ist immer sichtbar; beim Darüberfahren klappt es auf und zeigt die Art der KI-Nutzung.</p>',
				),
				array( 'id' => 'position', 'name' => 'Position', 'type' => 'button_group', 'options' => $d['position'], 'std' => 'oben-rechts' ),
				array( 'id' => 'stil', 'name' => 'Stil', 'type' => 'button_group', 'options' => $d['stil'], 'std' => 'dunkel' ),
				array( 'id' => 'groesse', 'name' => 'Größe', 'type' => 'button_group', 'options' => $d['groesse'], 'std' => 'normal' ),
				array( 'id' => 'werkzeug', 'name' => 'Werkzeug nennen', 'type' => 'switch', 'style' => 'rounded', 'on_label' => 'Ja', 'off_label' => 'Nein', 'std' => 1, 'desc' => 'Zum Beispiel „Inhalt vollständig von KI erzeugt (Midjourney)“.' ),
			),
		);
		return $boxen;
	}
);

// KI-Symbol als Bild in Clubdaten › Club (neben den Club-Logos), z. B. das offizielle EU-Symbol; ohne Bild erscheint „AI“
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		foreach ( $boxen as $i => $b ) {
			if ( 'clubdaten-club' === ( $b['id'] ?? '' ) ) {
				$boxen[ $i ]['fields'][] = array(
					'id'      => 'ki_logo',
					'name'    => 'KI-Symbol',
					'type'    => 'single_image',
					'columns' => 4,
					'desc'    => 'Erscheint an KI-Bildern statt des Schriftzugs „AI“ (z. B. das offizielle EU-Symbol). Quadratisch oder breit, mit gut lesbarem Kontrast. Leer = „AI“.',
				);
			}
		}
		return $boxen;
	},
	20
);

/** Adresse des KI-Symbols aus den Clubdaten, sonst leer. */
function golfplatz_ki_logo(): string {
	static $url = null;
	if ( null === $url ) {
		$id  = (int) ( ( (array) get_option( 'clubdaten', array() ) )['ki_logo'] ?? 0 );
		$url = $id ? (string) wp_get_attachment_image_url( $id, 'medium' ) : '';
	}
	return $url;
}

/** Gewählte Darstellung, ungültige Werte auf die Vorgabe. */
function golfplatz_ki_darstellung(): array {
	$opt = (array) get_option( GOLFPLATZ_KI_OPTION, array() );
	$wert = function ( string $k, string $vorgabe ) use ( $opt ) {
		$v = (string) ( $opt[ $k ] ?? '' );
		return isset( GOLFPLATZ_KI_DARSTELLUNG[ $k ][ $v ] ) ? $v : $vorgabe;
	};
	return array(
		'position' => $wert( 'position', 'oben-rechts' ),
		'stil'     => $wert( 'stil', 'dunkel' ),
		'groesse'  => $wert( 'groesse', 'normal' ),
		'werkzeug' => ! isset( $opt['werkzeug'] ) || ! empty( $opt['werkzeug'] ),
	);
}

/** Art der KI-Nutzung eines Anhangs; maßgeblich ist nur die Auswahl „KI-Nutzung“ (leer = keine KI). */
function golfplatz_ki_art( int $id ): string {
	if ( ! $id ) {
		return '';
	}
	$art = (string) get_post_meta( $id, 'ki_art', true );
	return isset( GOLFPLATZ_KI_ARTEN[ $art ] ) ? $art : '';
}

/**
 * Daten für das Symbol: hat, kurz („AI“), logo/hat_logo (KI-Symbol aus den Clubdaten statt „AI“), label („AI GENERATED“), zusatz (Kennung ohne „AI“: „GENERATED“, aufgeklappt neben dem Symbol), text („Inhalt vollständig von KI erzeugt (Midjourney)“),
 * mod (Klassen-Modifier für Position, Stil, Größe), alt (Hinweis für den Alternativtext).
 */
function golfplatz_ki_daten( int $id ): array {
	$art = golfplatz_ki_art( $id );
	if ( '' === $art ) {
		return array( 'hat' => false, 'kurz' => '', 'logo' => '', 'hat_logo' => false, 'label' => '', 'zusatz' => '', 'text' => '', 'mod' => '', 'alt' => '' );
	}
	$d        = golfplatz_ki_darstellung();
	// Position am Bild (Feld ki_position) geht vor der Einstellung unter Medien › KI-Kennzeichnung
	$eigene   = (string) get_post_meta( $id, 'ki_position', true );
	$d['position'] = isset( GOLFPLATZ_KI_DARSTELLUNG['position'][ $eigene ] ) ? $eigene : $d['position'];
	$werkzeug = $d['werkzeug'] ? trim( (string) get_post_meta( $id, 'ki_werkzeug', true ) ) : '';
	$text     = GOLFPLATZ_KI_ARTEN[ $art ]['text'] . ( '' !== $werkzeug ? ' (' . $werkzeug . ')' : '' );
	return array(
		'hat'   => true,
		'kurz'  => 'AI',
		'logo'  => golfplatz_ki_logo(),
		'hat_logo' => '' !== golfplatz_ki_logo(),
		'label' => GOLFPLATZ_KI_ARTEN[ $art ]['label'],
		// ohne eigenes KI-Symbol steht „AI“ im Symbol davor, sonst die volle Kennung
		'zusatz' => '' !== golfplatz_ki_logo() ? GOLFPLATZ_KI_ARTEN[ $art ]['label'] : trim( substr( GOLFPLATZ_KI_ARTEN[ $art ]['label'], 2 ) ),
		'text'  => $text,
		'mod'   => 'ki-plakette--' . $d['position'] . ' ki-plakette--' . $d['stil'] . ' ki-plakette--' . $d['groesse'] . ( '' !== golfplatz_ki_logo() ? ' ki-plakette--logo' : '' ),
		'alt'   => GOLFPLATZ_KI_ARTEN[ $art ]['label'] . ': ' . $text,
	);
}

/** Früherer Name: Hinweistext eines Anhangs, sonst leer. */
function golfplatz_ki_text( int $id ): string {
	return golfplatz_ki_daten( $id )['alt'];
}

/** Alternativtext mit Hinweis ergänzen (Screenreader). */
function golfplatz_ki_alt( string $alt, string $hinweis ): string {
	return trim( $alt . ( '' !== $alt ? ' – ' : '' ) . $hinweis );
}

/**
 * Markup des Symbols für Bilder aus dem Editor. Gleicher Aufbau wie in den Etch-Komponenten (lib.mjs: kiPlakette):
 * sichtbar „AI“, beim Darüberfahren Kennung und Text. Für Screenreader steht der Hinweis im Alternativtext.
 */
function golfplatz_ki_plakette( array $k ): string {
	return '<span class="ki-plakette ' . esc_attr( $k['mod'] ) . '" aria-hidden="true" title="' . esc_attr( $k['alt'] ) . '">'
		. '<span class="ki-plakette__icon">' . ( $k['hat_logo'] ? '<img class="ki-plakette__logo" src="' . esc_url( $k['logo'] ) . '" alt="">' : esc_html( $k['kurz'] ) ) . '</span>'
		. '<span class="ki-plakette__text">' . ( '' !== $k['zusatz'] ? '<strong>' . esc_html( $k['zusatz'] ) . '</strong> ' : '' ) . esc_html( $k['text'] ) . '</span></span>';
}

/**
 * Bilder aus dem Editor (Bild, Galerie, Beitragsbild, Cover): Klasse „ki-bild“ am äußeren Element, Bild und Symbol in
 * span.ki-bild__rahmen (Bezug für die Position, ohne Bildunterschrift), Hinweis im Alternativtext.
 */
add_filter(
	'render_block',
	function ( $html, $block ) {
		$name = $block['blockName'] ?? '';
		if ( ! in_array( $name, array( 'core/image', 'core/post-featured-image', 'core/cover' ), true ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $html;
		}
		$id = 'core/post-featured-image' === $name ? (int) get_post_thumbnail_id() : (int) ( $block['attrs']['id'] ?? 0 );
		$k  = golfplatz_ki_daten( $id );
		if ( ! $k['hat'] ) {
			return $html;
		}
		$p = new WP_HTML_Tag_Processor( $html );
		if ( $p->next_tag() ) {
			$p->add_class( 'ki-bild' );
		}
		if ( $p->next_tag( 'img' ) ) {
			$p->set_attribute( 'alt', golfplatz_ki_alt( (string) $p->get_attribute( 'alt' ), $k['alt'] ) );
		}
		$html = $p->get_updated_html();
		// Symbol direkt nach dem Bild (bzw. nach dem Link um das Bild) einfügen
		$nach = preg_match( '#<a\b[^>]*>\s*<img\b[^>]*>\s*</a>#i', $html, $m ) ? $m[0] : ( preg_match( '#<img\b[^>]*>#i', $html, $m ) ? $m[0] : '' );
		return '' !== $nach ? str_replace( $nach, '<span class="ki-bild__rahmen">' . $nach . golfplatz_ki_plakette( $k ) . '</span>', $html ) : $html;
	},
	10,
	2
);
