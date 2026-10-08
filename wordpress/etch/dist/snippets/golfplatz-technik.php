<?php
/**
 * Plugin Name: Golfplatz – Technik-Doku
 * Description: Doku zum Aufbau der Website für Administratoren (manage_options): Schichten, Plugins, Snippets, externe Daten und Zeitpläne, CSS, Skripte, was man nicht tun sollte, Prüfungen nach Updates. Als Unterpunkt „Technik“ im Menü „Handbuch“ (sonst eigener Menüpunkt), dazu ein Hinweis im Dashboard. Inhalt: golfplatz/technik.php, erzeugt von wordpress/etch/build.mjs aus docs/technik.md.
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. WPCodeBox: PHP, Ausführung „Always“, Einfügepunkt Root.
 * Quelle: Blueprint golfplatz, wordpress/snippets/golfplatz-technik.php
 */

defined( 'ABSPATH' ) || exit;

// Build-Dateien liegen in wp-content/golfplatz/. Als WPCodeBox-Snippet läuft der Code per eval(), __DIR__ zeigt nicht dorthin.
defined( 'GOLFPLATZ_DATEN' ) || define( 'GOLFPLATZ_DATEN', WP_CONTENT_DIR . '/golfplatz' );

// Nach dem Menü des Handbuchs (Priorität 10), damit der Unterpunkt dort hinten steht.
add_action(
	'admin_menu',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( function_exists( 'golfplatz_handbuch_seite' ) ) {
			add_submenu_page( 'golfplatz-handbuch', 'Technik der Website', 'Technik', 'manage_options', 'golfplatz-technik', 'golfplatz_technik_seite' );
		} else {
			add_menu_page( 'Technik der Website', 'Technik', 'manage_options', 'golfplatz-technik', 'golfplatz_technik_seite', 'dashicons-admin-tools', 81 );
		}
	},
	20
);

function golfplatz_technik_seite(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Kein Zugriff.' );
	}
	// Die Datei beginnt mit einer PHP-Schutzzeile (ABSPATH-Prüfung): direkt aufgerufen liefert der Webserver nichts aus.
	$html = preg_replace( '/^<\?php.*?\?>\n?/s', '', (string) @file_get_contents( GOLFPLATZ_DATEN . '/technik.php' ) );
	echo '<div class="wrap golfplatz-technik">';
	echo $html ? wp_kses_post( $html ) : '<h1>Technik der Website</h1><p>Die Datei golfplatz/technik.php fehlt. Bitte den Build ausführen und den Ordner dist kopieren.</p>';
	echo '</div>';
}

// Lesbare Zeilenlänge, Kapitel-Verzeichnis links mitlaufend; Farben kommen aus dem WordPress-Backend.
// Der Umwandler (wordpress/etch/handbuch.mjs) setzt die Klassen golfplatz-handbuch__…; hier unter .golfplatz-technik gestaltet.
add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || ! preg_match( '/_page_golfplatz-technik$/', $screen->id ) ) {
			return;
		}
		echo '<style>
			.golfplatz-technik .golfplatz-handbuch__layout { display: grid; grid-template-columns: minmax(12rem, 16rem) minmax(0, 72rem); gap: 2rem; align-items: start; }
			.golfplatz-technik .golfplatz-handbuch__toc { position: sticky; top: 3rem; }
			.golfplatz-technik .golfplatz-handbuch__toc ol { margin-left: 1.25rem; }
			.golfplatz-technik .golfplatz-handbuch__toc li { margin-bottom: .35rem; }
			.golfplatz-technik .golfplatz-handbuch__inhalt { font-size: 14px; line-height: 1.6; }
			.golfplatz-technik .golfplatz-handbuch__inhalt h2 { font-size: 1.5em; margin-top: 3rem; scroll-margin-top: 3rem; }
			.golfplatz-technik .golfplatz-handbuch__inhalt h3 { font-size: 1.15em; margin-top: 1.75rem; scroll-margin-top: 3rem; }
			.golfplatz-technik .golfplatz-handbuch__inhalt ul, .golfplatz-technik .golfplatz-handbuch__inhalt ol { margin-left: 1.5rem; }
			.golfplatz-technik .golfplatz-handbuch__inhalt ul { list-style: disc; }
			.golfplatz-technik .golfplatz-handbuch__inhalt table { margin: 1rem 0; }
			.golfplatz-technik .golfplatz-handbuch__inhalt td, .golfplatz-technik .golfplatz-handbuch__inhalt th { vertical-align: top; }
			.golfplatz-technik .golfplatz-handbuch__hinweis { margin: 1rem 0; }
			@media (max-width: 960px) { .golfplatz-technik .golfplatz-handbuch__layout { grid-template-columns: 1fr; } .golfplatz-technik .golfplatz-handbuch__toc { position: static; } }
		</style>';
	}
);

add_action(
	'wp_dashboard_setup',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'golfplatz_technik',
			'Technik der Website (Administratoren)',
			function () {
				echo '<p>Wie die Website aufgebaut ist: Plugins, Snippets, externe Daten, CSS und Skripte und wie sie zusammenarbeiten – mit Hinweisen, was man nicht ändern sollte.</p>';
				echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=golfplatz-technik' ) ) . '">Technik-Doku öffnen</a></p>';
			}
		);
	}
);
