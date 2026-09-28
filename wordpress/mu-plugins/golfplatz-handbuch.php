<?php
/**
 * Plugin Name: Golfplatz – Handbuch
 * Description: Handbuch zur Bedienung der Website als eigene Seite im WordPress-Backend (Menü „Handbuch“, dazu ein Hinweis im Dashboard). Nur für angemeldete Benutzer, die Inhalte bearbeiten (edit_posts) oder den Platzstatus pflegen (edit_sperrungen) – nicht für reine Mitglieder-Konten. Inhalt: golfplatz/handbuch.html, erzeugt von wordpress/etch/build.mjs aus docs/handbuch.md.
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. Quelle: Repository golfplatz, wordpress/mu-plugins/golfplatz-handbuch.php
 */

defined( 'ABSPATH' ) || exit;

function golfplatz_handbuch_erlaubt(): bool {
	return current_user_can( 'edit_posts' ) || current_user_can( 'edit_sperrungen' );
}

add_action(
	'admin_menu',
	function () {
		if ( golfplatz_handbuch_erlaubt() ) {
			add_menu_page( 'Handbuch zur Website', 'Handbuch', 'read', 'golfplatz-handbuch', 'golfplatz_handbuch_seite', 'dashicons-book-alt', 2 );
		}
	}
);

function golfplatz_handbuch_seite(): void {
	if ( ! golfplatz_handbuch_erlaubt() ) {
		wp_die( 'Kein Zugriff.' );
	}
	$html = (string) @file_get_contents( __DIR__ . '/golfplatz/handbuch.html' );
	echo '<div class="wrap golfplatz-handbuch">';
	echo $html ? wp_kses_post( $html ) : '<h1>Handbuch</h1><p>Die Datei golfplatz/handbuch.html fehlt. Bitte den Build ausführen und den Ordner dist kopieren.</p>';
	echo '</div>';
}

// Lesbare Zeilenlänge, Kapitel-Verzeichnis links mitlaufend; Farben kommen aus dem WordPress-Backend.
add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_golfplatz-handbuch' !== $screen->id ) {
			return;
		}
		echo '<style>
			.golfplatz-handbuch__layout { display: grid; grid-template-columns: minmax(12rem, 16rem) minmax(0, 48rem); gap: 2rem; align-items: start; }
			.golfplatz-handbuch__toc { position: sticky; top: 3rem; }
			.golfplatz-handbuch__toc ol { margin-left: 1.25rem; }
			.golfplatz-handbuch__toc li { margin-bottom: .35rem; }
			.golfplatz-handbuch__inhalt { font-size: 14px; line-height: 1.6; }
			.golfplatz-handbuch__inhalt h2 { font-size: 1.5em; margin-top: 3rem; scroll-margin-top: 3rem; }
			.golfplatz-handbuch__inhalt h3 { font-size: 1.15em; margin-top: 1.75rem; scroll-margin-top: 3rem; }
			.golfplatz-handbuch__inhalt ul, .golfplatz-handbuch__inhalt ol { margin-left: 1.5rem; }
			.golfplatz-handbuch__inhalt ul { list-style: disc; }
			.golfplatz-handbuch__inhalt ul ul { list-style: circle; }
			.golfplatz-handbuch__inhalt table { margin: 1rem 0; }
			.golfplatz-handbuch__hinweis { margin: 1rem 0; }
			@media (max-width: 960px) { .golfplatz-handbuch__layout { grid-template-columns: 1fr; } .golfplatz-handbuch__toc { position: static; } }
		</style>';
	}
);

add_action(
	'wp_dashboard_setup',
	function () {
		if ( ! golfplatz_handbuch_erlaubt() ) {
			return;
		}
		wp_add_dashboard_widget(
			'golfplatz_handbuch',
			'Handbuch zur Website',
			function () {
				echo '<p>Schritt für Schritt erklärt: Platzstatus, Öffnungszeiten, Nachrichten, Preise, Turniere, Lochwettspiel, Mannschaften und mehr.</p>';
				echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=golfplatz-handbuch' ) ) . '">Handbuch öffnen</a></p>';
			}
		);
	}
);
