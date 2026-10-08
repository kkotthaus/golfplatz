<?php
/**
 * Plugin Name: Golfplatz – Backend
 * Description: Blendet den Block „Individuelle Felder“ (postcustom) in allen Beitragstypen aus – im Block-Editor samt Schalter in den Voreinstellungen und im klassischen Editor. Er zeigt rohe Metadaten (Meta Box, Platzstatus, Turniere, KI-Kennzeichnung) und ließe sie ungeprüft ändern. Standard aus etch-nodes (docs/konventionen.md, Backend).
 *
 * Gehört auf die Live-Seite. WPCodeBox: PHP, Ausführung „Always“, Einfügepunkt Root.
 * Quelle: Blueprint golfplatz, wordpress/snippets/golfplatz-backend.php
 */

defined( 'ABSPATH' ) || exit;

// Kasten entfernen, nachdem WordPress und Plugins ihre Kästen registriert haben.
// Nicht remove_post_type_support( 'custom-fields' ): das schaltet auch Metadaten in der REST-API ab.
add_action(
	'add_meta_boxes',
	function () {
		foreach ( get_post_types() as $typ ) {
			remove_meta_box( 'postcustom', $typ, 'normal' );
		}
	},
	100
);

// Block-Editor: ohne enableCustomFields gibt es auch den Schalter in den Voreinstellungen nicht
add_filter(
	'block_editor_settings_all',
	function ( $settings ) {
		unset( $settings['enableCustomFields'] );
		return $settings;
	}
);
