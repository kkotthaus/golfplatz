<?php
/**
 * Plugin Name: Golfplatz MCP-Erweiterung
 * Description: Eng begrenzte MCP-Funktionen zum Aufbau der Website: Seiten, Etch-Templates, Etch-Komponenten und globale Etch-Stylesheets lesen und speichern, Startseite festlegen. Nur für Administratoren und nur für die Entwicklungsumgebung. Zum Entfernen die Datei löschen.
 * Version: 1.1.0
 *
 * Quelle: Repository golfplatz, wordpress/mu-plugins/golfplatz-mcp.php
 * Ziel:   wp-content/mu-plugins/golfplatz-mcp.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Ability' ) ) {
	return;
}

add_action(
	'wp_abilities_api_categories_init',
	function () {
		if ( ! wp_has_ability_category( 'golfplatz' ) ) {
			wp_register_ability_category(
				'golfplatz',
				array(
					'label'       => 'Golfplatz',
					'description' => 'Projektspezifische Funktionen für den Aufbau der Website.',
				)
			);
		}
	}
);

/**
 * Gemeinsame Einstellungen aller Funktionen.
 */
function golfplatz_mcp_ability( string $name, array $args, bool $readonly ): void {
	wp_register_ability(
		'golfplatz/' . $name,
		array_merge(
			array(
				'category'            => 'golfplatz',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations' => array(
						'readonly'    => $readonly,
						'destructive' => false,
						'idempotent'  => $readonly,
					),
					'mcp'         => array( 'public' => true ),
				),
			),
			$args
		)
	);
}

/** Beitragstypen, die diese Funktionen lesen und schreiben dürfen. */
const GOLFPLATZ_MCP_TYPES = array( 'page', 'wp_template', 'wp_block' );

/** Beitragstypen, auf die Etch-Loops aus dem Generator abfragen dürfen. */
const GOLFPLATZ_MCP_LOOP_TYPES = array( 'spielbahn', 'preis', 'person', 'kurs', 'post', 'mannschaft', 'ligaspiel', 'spielbericht', 'sperrung', 'lochwettspiel' );

/** Beitragstypen, deren Inhalte aus daten/<typ>.json importiert werden dürfen. */
const GOLFPLATZ_MCP_IMPORT_TYPES = array( 'spielbahn', 'sperrung', 'person', 'preis', 'kurs', 'lochwettspiel', 'spieler', 'mannschaft', 'ligaspiel', 'spielbericht' );

add_action(
	'wp_abilities_api_init',
	function () {
		golfplatz_mcp_ability(
			'list-content',
			array(
				'label'            => 'Seiten, Templates oder Komponenten auflisten',
				'description'      => 'Listet Seiten (page), Etch-Templates (wp_template) oder Etch-Komponenten (wp_block) mit ID, Titel, Slug und Status.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'post_type' ),
					'properties' => array(
						'post_type' => array( 'type' => 'string', 'enum' => GOLFPLATZ_MCP_TYPES ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_list_content',
			),
			true
		);

		golfplatz_mcp_ability(
			'get-content',
			array(
				'label'            => 'Inhalt lesen',
				'description'      => 'Liefert Block-Markup und Metadaten einer Seite, eines Etch-Templates oder einer Etch-Komponente.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'id' ),
					'properties' => array( 'id' => array( 'type' => 'integer' ) ),
				),
				'execute_callback' => 'golfplatz_mcp_get_content',
			),
			true
		);

		golfplatz_mcp_ability(
			'save-page',
			array(
				'label'            => 'Seite speichern',
				'description'      => 'Legt eine Seite an oder aktualisiert sie (mit id). Inhalt als Block-Markup (etch/element, etch/text, etch/loop, etch/component …).',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'title' ),
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'slug'    => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
						'status'  => array( 'type' => 'string', 'enum' => array( 'draft', 'publish', 'private' ), 'default' => 'draft' ),
						'parent'  => array( 'type' => 'integer', 'description' => 'ID der Elternseite' ),
						'order'   => array( 'type' => 'integer', 'description' => 'Reihenfolge (menu_order)' ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_save_page',
			),
			false
		);

		golfplatz_mcp_ability(
			'save-template',
			array(
				'label'            => 'Etch-Template speichern',
				'description'      => 'Legt ein Etch-Template (wp_template des aktiven Themes) über die Etch-Logik an oder aktualisiert es. Der Slug folgt der WordPress-Template-Hierarchie, z. B. single-spielbahn, archive-mannschaft, page, front-page.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'title', 'content' ),
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'slug'    => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_save_template',
			),
			false
		);

		golfplatz_mcp_ability(
			'save-component',
			array(
				'label'            => 'Etch-Komponente speichern',
				'description'      => 'Legt eine Etch-Komponente (wp_block) an oder aktualisiert sie. properties entspricht dem Etch-Format, z. B. [{"key":"title","type":{"primitive":"string"},"default":""}].',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'name', 'content' ),
					'properties' => array(
						'id'          => array( 'type' => 'integer' ),
						'name'        => array( 'type' => 'string' ),
						'key'         => array( 'type' => 'string', 'description' => 'HTML-Key der Komponente, z. B. StatusBoard' ),
						'description' => array( 'type' => 'string' ),
						'content'     => array( 'type' => 'string' ),
						'properties'  => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_save_component',
			),
			false
		);

		golfplatz_mcp_ability(
			'save-stylesheet',
			array(
				'label'            => 'Globales Etch-Stylesheet speichern',
				'description'      => 'Legt ein globales Etch-Stylesheet an oder aktualisiert es (mit id). Ohne css werden nur die vorhandenen Stylesheets aufgelistet.',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'id'   => array( 'type' => 'string' ),
						'name' => array( 'type' => 'string' ),
						'css'  => array( 'type' => 'string' ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_save_stylesheet',
			),
			false
		);

		golfplatz_mcp_ability(
			'sync-from-files',
			array(
				'label'            => 'Templates und Stylesheet aus dem Build übernehmen',
				'description'      => 'Liest die gebauten Dateien aus dem festen Ordner wp-content/mu-plugins/golfplatz/ (manifest.json, template-<slug>.html, page-<slug>.html, golfplatz.css) und speichert sie: Etch-Loops (nur wp-query auf freigegebene Beitragstypen) per ID, Etch-Templates per Slug, Seiten per Pfad, globales Etch-Stylesheet „Golfplatz“ – jeweils anlegen oder aktualisieren.',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'what' => array( 'type' => 'string', 'enum' => array( 'all', 'loops', 'components', 'templates', 'pages', 'stylesheet' ), 'default' => 'all' ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_sync_from_files',
			),
			false
		);

		golfplatz_mcp_ability(
			'import-content',
			array(
				'label'            => 'Inhalte aus dem Build importieren',
				'description'      => 'Liest wp-content/mu-plugins/golfplatz/daten/<post_type>.json und legt Einträge an oder aktualisiert sie. Erkannt wird ein Eintrag am Schlüsselfeld der Datei (z. B. bahn_nummer). Erlaubte Beitragstypen: ' . implode( ', ', GOLFPLATZ_MCP_IMPORT_TYPES ) . '. Verweise auf andere Beiträge als {"@post": "<typ>:<slug>"} oder Liste davon; sie werden zur ID aufgelöst (Reihenfolge: spieler, mannschaft, ligaspiel, spielbericht).',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'post_type' ),
					'properties' => array(
						'post_type' => array( 'type' => 'string', 'enum' => GOLFPLATZ_MCP_IMPORT_TYPES ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_import_content',
			),
			false
		);

		golfplatz_mcp_ability(
			'acss-colors',
			array(
				'label'            => 'Automatic.css: Farben lesen oder setzen',
				'description'      => 'Ohne „werte“: liefert alle Farb-Einstellungen von Automatic.css (Schlüssel color-*, option-*-clr, OKLCH je Stufe und Farbschema: auto-color-scheme, website-color-scheme, color-scheme-force-*). Mit „werte“: setzt diese über die offizielle ACSS-API (API::update_settings) und erzeugt das CSS neu. Andere Einstellungen sind nicht erlaubt.',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'werte' => array( 'type' => 'object', 'additionalProperties' => true, 'description' => 'z. B. {"color-primary":"#1e3a2b","option-secondary-clr":"on"}' ),
						'aus_datei' => array( 'type' => 'boolean', 'description' => 'true: Werte aus mu-plugins/golfplatz/daten/acss-farben.json (erzeugt von etch/build.mjs) übernehmen' ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_acss_colors',
			),
			false
		);

		golfplatz_mcp_ability(
			'import-settings',
			array(
				'label'            => 'Einstellungen aus dem Build importieren',
				'description'      => 'Liest wp-content/mu-plugins/golfplatz/daten/einstellungen-<seite>.json und schreibt die enthaltenen Felder in die Meta-Box-Einstellungsseite. Nicht aufgeführte Felder bleiben unverändert, null entfernt ein Feld. Mit „felder“ nur die genannten Felder – sonst überschreibt der Import alles, was im Admin gepflegt wurde. Erlaubt: clubdaten, platzstatus.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'seite' ),
					'properties' => array(
						'seite'  => array( 'type' => 'string', 'enum' => array( 'clubdaten', 'platzstatus' ) ),
						'felder' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Nur diese Felder übernehmen (empfohlen, sobald im Admin gepflegt wird). Ohne Angabe werden alle Felder der Datei geschrieben.' ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_import_settings',
			),
			false
		);

		golfplatz_mcp_ability(
			'save-settings-page',
			array(
				'label'            => 'Meta-Box-Einstellungsseite speichern',
				'description'      => 'Legt eine Einstellungsseite im Meta-Box-Builder an oder aktualisiert sie (per id). Sie bleibt unter Meta Box → Einstellungsseiten bearbeitbar. Feldgruppen hängen über settings.settings_pages = [id] und settings.tab = <tab-key> daran.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'id', 'title' ),
					'properties' => array(
						'id'          => array( 'type' => 'string', 'description' => 'ID/Slug, z. B. clubdaten' ),
						'title'       => array( 'type' => 'string' ),
						'option_name' => array( 'type' => 'string', 'description' => 'Standard: wie id' ),
						'parent'      => array( 'type' => 'string', 'description' => 'Leer = eigener Hauptmenüpunkt, sonst Slug des Elternmenüs' ),
						'icon'        => array( 'type' => 'string', 'description' => 'Dashicon ohne Präfix, z. B. flag' ),
						'position'    => array( 'type' => 'integer' ),
						'capability'  => array( 'type' => 'string', 'default' => 'manage_options' ),
						'tabs'        => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'key'   => array( 'type' => 'string' ),
									'label' => array( 'type' => 'string' ),
								),
							),
						),
						'columns'     => array( 'type' => 'integer', 'default' => 1 ),
						'tab_style'   => array( 'type' => 'string', 'enum' => array( 'default', 'box', 'left' ), 'default' => 'left' ),
					),
				),
				'execute_callback' => 'golfplatz_mcp_save_settings_page',
			),
			false
		);

		golfplatz_mcp_ability(
			'flush-permalinks',
			array(
				'label'            => 'Permalinks neu erzeugen',
				'description'      => 'Entspricht „Einstellungen → Permalinks → Speichern“. Nötig nach neuen Beitragstypen oder URL-Slugs.',
				'input_schema'     => array( 'type' => 'object' ),
				'execute_callback' => function () {
					flush_rewrite_rules( false );
					return array( 'flushed' => true );
				},
			),
			false
		);

		golfplatz_mcp_ability(
			'set-front-page',
			array(
				'label'            => 'Startseite festlegen',
				'description'      => 'Setzt eine Seite als statische Startseite.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'page_id' ),
					'properties' => array( 'page_id' => array( 'type' => 'integer' ) ),
				),
				'execute_callback' => 'golfplatz_mcp_set_front_page',
			),
			false
		);

		golfplatz_mcp_ability(
			'turniere-sync',
			array(
				'label'            => 'Turniere aus PC CADDIE abgleichen',
				'description'      => 'Liest Turnierkalender und Ergebnisliste des Clubs aus PC CADDIE://online und aktualisiert die Einträge „turnier“ (golfplatz-turniere.php).',
				'input_schema'     => array( 'type' => 'object', 'properties' => new stdClass() ),
				'execute_callback' => function () {
					if ( ! function_exists( 'golfplatz_pcc_abgleich' ) ) {
						return new WP_Error( 'golfplatz_pcc', 'golfplatz-turniere.php ist nicht aktiv.' );
					}
					$log = golfplatz_pcc_abgleich();
					return array( 'text' => golfplatz_pcc_log_text( $log ), 'log' => $log );
				},
			),
			false
		);

		golfplatz_mcp_ability(
			'liga-sync',
			array(
				'label'            => 'Ligaspiele vom Golfverband NRW abgleichen',
				'description'      => 'Gleicht Mannschaften, Ligaspiele (Spieltag, Datum, Ort, Ergebnis) und Gastclubs einer Saison mit gvnrw.liga.golf ab (golfplatz-liga-sync.php). suche: alle Ligen neu durchsuchen (1–2 Minuten).',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'jahr' ),
					'properties' => array(
						'jahr'  => array( 'type' => 'integer', 'minimum' => 2023 ),
						'suche' => array( 'type' => 'boolean', 'default' => false ),
					),
				),
				'execute_callback' => function ( $input ) {
					if ( ! function_exists( 'golfplatz_liga_abgleich' ) ) {
						return new WP_Error( 'golfplatz_liga', 'golfplatz-liga-sync.php ist nicht aktiv.' );
					}
					$log = golfplatz_liga_abgleich( (int) $input['jahr'], ! empty( $input['suche'] ) );
					return array( 'text' => golfplatz_liga_log_text( $log ), 'log' => $log );
				},
			),
			false
		);
	}
);

/**
 * Prüft, ob ein Beitrag zu den erlaubten Typen gehört.
 */
function golfplatz_mcp_allowed_post( int $id, ?string $type = null ) {
	$post = get_post( $id );
	if ( ! $post || ! in_array( $post->post_type, GOLFPLATZ_MCP_TYPES, true ) || ( $type && $post->post_type !== $type ) ) {
		return new WP_Error( 'golfplatz_not_found', 'Inhalt nicht gefunden oder nicht erlaubt.' );
	}
	return $post;
}

/**
 * Führt eine feste Etch-REST-Route intern aus.
 */
function golfplatz_mcp_etch_request( string $method, string $route, array $body = array() ): array {
	$request = new WP_REST_Request( $method, '/etch-api' . $route );
	if ( $body ) {
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
	}
	$response = rest_do_request( $request );
	return array(
		'status' => $response->get_status(),
		'data'   => rest_get_server()->response_to_data( $response, false ),
	);
}

function golfplatz_mcp_list_content( $input ) {
	$type = $input['post_type'] ?? 'page';
	if ( ! in_array( $type, GOLFPLATZ_MCP_TYPES, true ) ) {
		return new WP_Error( 'golfplatz_type', 'Beitragstyp nicht erlaubt.' );
	}
	if ( 'wp_template' === $type ) {
		// Über Etch, damit Theme-Templates in der Datenbank landen und nur das aktive Theme erscheint.
		return golfplatz_mcp_etch_request( 'GET', '/templates' );
	}
	$posts = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);
	return array_map(
		fn( $p ) => array(
			'id'     => $p->ID,
			'title'  => $p->post_title,
			'slug'   => $p->post_name,
			'status' => $p->post_status,
			'parent' => $p->post_parent,
			'link'   => get_permalink( $p ),
		),
		$posts
	);
}

function golfplatz_mcp_get_content( $input ) {
	$post = golfplatz_mcp_allowed_post( (int) ( $input['id'] ?? 0 ) );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	return array(
		'id'         => $post->ID,
		'post_type'  => $post->post_type,
		'title'      => $post->post_title,
		'slug'       => $post->post_name,
		'status'     => $post->post_status,
		'content'    => $post->post_content,
		'properties' => 'wp_block' === $post->post_type ? get_post_meta( $post->ID, 'etch_component_properties', true ) : null,
		'key'        => 'wp_block' === $post->post_type ? get_post_meta( $post->ID, 'etch_component_html_key', true ) : null,
	);
}

function golfplatz_mcp_save_page( $input ) {
	$data = array(
		'post_type'   => 'page',
		'post_title'  => sanitize_text_field( $input['title'] ),
		'post_status' => $input['status'] ?? 'draft',
	);
	if ( isset( $input['content'] ) ) {
		$data['post_content'] = wp_slash( $input['content'] );
	}
	if ( ! empty( $input['slug'] ) ) {
		$data['post_name'] = sanitize_title( $input['slug'] );
	}
	if ( isset( $input['parent'] ) ) {
		$data['post_parent'] = (int) $input['parent'];
	}
	if ( isset( $input['order'] ) ) {
		$data['menu_order'] = (int) $input['order'];
	}
	if ( ! empty( $input['id'] ) ) {
		$post = golfplatz_mcp_allowed_post( (int) $input['id'], 'page' );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$data['ID'] = $post->ID;
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	return array( 'id' => $id, 'link' => get_permalink( $id ) );
}

function golfplatz_mcp_save_template( $input ) {
	$body = array(
		'post_title'   => $input['title'],
		// Etch speichert Templates ohne wp_slash(); vorab maskieren, sonst gehen JSON-Escapes (Backslash-u0022 usw.) verloren.
		'post_content' => wp_slash( $input['content'] ),
	);
	if ( ! empty( $input['slug'] ) ) {
		$body['post_name'] = $input['slug'];
	}
	if ( ! empty( $input['id'] ) ) {
		$post = golfplatz_mcp_allowed_post( (int) $input['id'], 'wp_template' );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		return golfplatz_mcp_etch_request( 'PUT', '/templates/' . $post->ID, $body );
	}
	return golfplatz_mcp_etch_request( 'POST', '/templates', $body );
}

function golfplatz_mcp_save_component( $input ) {
	$data = array(
		'post_type'    => 'wp_block',
		'post_title'   => sanitize_text_field( $input['name'] ),
		'post_content' => wp_slash( $input['content'] ),
		'post_excerpt' => sanitize_text_field( $input['description'] ?? '' ),
		'post_status'  => 'publish',
	);
	if ( ! empty( $input['id'] ) ) {
		$post = golfplatz_mcp_allowed_post( (int) $input['id'], 'wp_block' );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$data['ID'] = $post->ID;
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	if ( isset( $input['properties'] ) ) {
		update_post_meta( $id, 'etch_component_properties', $input['properties'] );
	}
	if ( ! empty( $input['key'] ) ) {
		update_post_meta( $id, 'etch_component_html_key', sanitize_text_field( $input['key'] ) );
	}
	return array( 'id' => $id );
}

function golfplatz_mcp_save_stylesheet( $input ) {
	if ( ! isset( $input['css'] ) ) {
		return golfplatz_mcp_etch_request( 'GET', '/stylesheets' );
	}
	$body = array(
		'name' => (string) ( $input['name'] ?? 'Stylesheet' ),
		'css'  => (string) $input['css'],
	);
	if ( ! empty( $input['id'] ) ) {
		return golfplatz_mcp_etch_request( 'PUT', '/stylesheets/' . rawurlencode( $input['id'] ), $body );
	}
	return golfplatz_mcp_etch_request( 'POST', '/stylesheets', $body );
}

function golfplatz_mcp_sync_from_files( $input ) {
	$dir      = __DIR__ . '/golfplatz';
	$what     = $input['what'] ?? 'all';
	$log      = array();
	$manifest = json_decode( (string) @file_get_contents( $dir . '/manifest.json' ), true );
	if ( ! is_array( $manifest ) && 'stylesheet' !== $what ) {
		return new WP_Error( 'golfplatz_manifest', 'manifest.json fehlt oder ist ungültig.' );
	}

	// Loops vor allem anderen: Seiten, Templates und Komponenten verweisen per loopId darauf.
	if ( in_array( $what, array( 'all', 'loops' ), true ) && ! empty( $manifest['loops'] ) ) {
		$loops = (array) golfplatz_mcp_etch_request( 'GET', '/loops' )['data'];
		foreach ( (array) $manifest['loops'] as $loop ) {
			$id   = preg_replace( '/[^a-z0-9-]/', '', (string) ( $loop['id'] ?? '' ) );
			$args = (array) ( $loop['config']['args'] ?? array() );
			if ( '' === $id || 'wp-query' !== ( $loop['config']['type'] ?? '' ) || ! in_array( $args['post_type'] ?? '', GOLFPLATZ_MCP_LOOP_TYPES, true ) ) {
				$log[] = array( 'loop' => $id, 'status' => 'nicht erlaubt' );
				continue;
			}
			$loops[ $id ] = array(
				'name'   => sanitize_text_field( (string) $loop['name'] ),
				'key'    => sanitize_key( (string) $loop['key'] ),
				'global' => true,
				'config' => array( 'type' => 'wp-query', 'args' => $args ),
			);
			$log[] = array( 'loop' => $id );
		}
		$res   = golfplatz_mcp_etch_request( 'PUT', '/loops', $loops );
		$log[] = array( 'loops' => $res['status'] );
	}

	// Komponenten zuerst: Ihre IDs ersetzen die Platzhalter "__REF_<key>__" in Seiten, Templates und
	// Komponenten, die andere Komponenten einbinden (Reihenfolge im Manifest: eingebundene zuerst).
	$refs     = array();
	$mit_refs = function ( string $markup ) use ( &$refs ) {
		foreach ( $refs as $key => $id ) {
			$markup = str_replace( '"__REF_' . $key . '__"', (string) $id, $markup );
		}
		return $markup;
	};
	foreach ( (array) ( $manifest['components'] ?? array() ) as $komp ) {
		$key   = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $komp['key'] );
		$finde = fn( string $k ) => get_posts(
			array(
				'post_type'      => 'wp_block',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => 'etch_component_html_key',
				'meta_value'     => $k,
				'fields'         => 'ids',
			)
		);
		$vorhanden = $finde( $key );
		// Umbenannte Komponente: die alte (Key aus „ersetzt“) weiterverwenden statt eine Kopie anzulegen.
		if ( ! $vorhanden && ! empty( $komp['ersetzt'] ) ) {
			$vorhanden = $finde( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $komp['ersetzt'] ) );
		}
		if ( in_array( $what, array( 'all', 'components' ), true ) ) {
			$file = $dir . '/component-' . $key . '.html';
			if ( ! is_readable( $file ) ) {
				$log[] = array( 'component' => $key, 'status' => 'Datei fehlt' );
				continue;
			}
			$res = golfplatz_mcp_save_component(
				array(
					'id'          => $vorhanden ? $vorhanden[0] : 0,
					'name'        => (string) $komp['name'],
					'key'         => $key,
					'description' => (string) ( $komp['description'] ?? '' ),
					'content'     => $mit_refs( (string) file_get_contents( $file ) ),
					'properties'  => (array) ( $komp['properties'] ?? array() ),
				)
			);
			$log[] = array( 'component' => $key, 'result' => is_wp_error( $res ) ? $res->get_error_message() : $res );
			if ( ! is_wp_error( $res ) ) {
				$refs[ $key ] = (int) $res['id'];
			}
		} elseif ( $vorhanden ) {
			$refs[ $key ] = (int) $vorhanden[0];
		}
	}
	if ( in_array( $what, array( 'all', 'templates' ), true ) ) {
		$vorhanden = array();
		foreach ( (array) golfplatz_mcp_etch_request( 'GET', '/templates' )['data'] as $tpl ) {
			$vorhanden[ $tpl['slug'] ] = (int) $tpl['id'];
		}
		foreach ( $manifest['templates'] ?? array() as $tpl ) {
			$slug = sanitize_key( $tpl['slug'] );
			$file = $dir . '/template-' . $slug . '.html';
			if ( ! is_readable( $file ) ) {
				$file = $dir . '/' . $slug . '.html'; // ältere Builds ohne Präfix
			}
			if ( ! is_readable( $file ) ) {
				$log[] = array( 'template' => $slug, 'status' => 'Datei fehlt' );
				continue;
			}
			$body = array(
				'post_title'   => (string) $tpl['title'],
				'post_name'    => $slug,
				'post_content' => wp_slash( $mit_refs( (string) file_get_contents( $file ) ) ),
			);
			$res   = isset( $vorhanden[ $slug ] )
				? golfplatz_mcp_etch_request( 'PUT', '/templates/' . $vorhanden[ $slug ], $body )
				: golfplatz_mcp_etch_request( 'POST', '/templates', $body );
			$log[] = array( 'template' => $slug, 'status' => $res['status'] );
		}
	}

	if ( in_array( $what, array( 'all', 'pages' ), true ) ) {
		foreach ( (array) ( $manifest['pages'] ?? array() ) as $seite ) {
			$slug = sanitize_title( $seite['slug'] );
			$file = $dir . '/page-' . $slug . '.html';
			if ( ! is_readable( $file ) ) {
				$log[] = array( 'page' => $slug, 'status' => 'Datei fehlt' );
				continue;
			}
			$parent = 0;
			if ( ! empty( $seite['parent'] ) ) {
				$eltern = get_page_by_path( sanitize_title( $seite['parent'] ) );
				$parent = $eltern ? $eltern->ID : 0;
			}
			$pfad      = ( $parent ? get_page_uri( $parent ) . '/' : '' ) . $slug;
			$vorhanden = get_page_by_path( $pfad );
			$res       = golfplatz_mcp_save_page(
				array(
					'id'      => $vorhanden ? $vorhanden->ID : 0,
					'title'   => (string) $seite['title'],
					'slug'    => $slug,
					'content' => $mit_refs( (string) file_get_contents( $file ) ),
					'status'  => $seite['status'] ?? 'publish',
					'parent'  => $parent,
					'order'   => (int) ( $seite['order'] ?? 0 ),
				)
			);
			$log[] = array( 'page' => $pfad, 'result' => is_wp_error( $res ) ? $res->get_error_message() : $res );
			if ( ! is_wp_error( $res ) && ! empty( $seite['front_page'] ) ) {
				golfplatz_mcp_set_front_page( array( 'page_id' => $res['id'] ) );
			}
		}
	}

	if ( in_array( $what, array( 'all', 'stylesheet' ), true ) ) {
		$css = (string) @file_get_contents( $dir . '/golfplatz.css' );
		if ( '' === $css ) {
			return new WP_Error( 'golfplatz_css', 'golfplatz.css fehlt.' );
		}
		$id = null;
		foreach ( (array) golfplatz_mcp_etch_request( 'GET', '/stylesheets' )['data'] as $key => $sheet ) {
			if ( is_array( $sheet ) && ( $sheet['name'] ?? '' ) === 'Golfplatz' ) {
				$id = (string) ( $sheet['id'] ?? $key );
			}
		}
		$body  = array( 'name' => 'Golfplatz', 'css' => $css );
		$res   = $id
			? golfplatz_mcp_etch_request( 'PUT', '/stylesheets/' . rawurlencode( $id ), $body )
			: golfplatz_mcp_etch_request( 'POST', '/stylesheets', $body );
		$log[] = array( 'stylesheet' => 'Golfplatz', 'status' => $res['status'], 'id' => $id ?? ( $res['data']['id'] ?? null ) );
	}

	return $log;
}

/**
 * Verweis im Import auflösen: {"@post": "spieler:anna-adler"} → Beitrags-ID, Liste von Verweisen → Liste von IDs.
 * Nur Beitragstypen aus GOLFPLATZ_MCP_IMPORT_TYPES. Nicht gefundene Verweise werden 0 bzw. fallen aus der Liste.
 */
function golfplatz_mcp_import_ref( $v ) {
	$eins = function ( $ref ) {
		[ $typ, $slug ] = array_pad( explode( ':', (string) $ref, 2 ), 2, '' );
		if ( ! in_array( $typ, GOLFPLATZ_MCP_IMPORT_TYPES, true ) ) {
			return 0;
		}
		$ids = get_posts( array( 'post_type' => $typ, 'name' => sanitize_title( $slug ), 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
		return $ids ? (int) $ids[0] : 0;
	};
	if ( is_array( $v ) && isset( $v['@post'] ) ) {
		return $eins( $v['@post'] );
	}
	if ( is_array( $v ) && $v && array_is_list( $v ) && is_array( $v[0] ) && isset( $v[0]['@post'] ) ) {
		return array_values( array_filter( array_map( fn( $x ) => $eins( $x['@post'] ?? '' ), $v ) ) );
	}
	return $v;
}

/**
 * Importiert Einträge aus daten/<post_type>.json.
 * Format: { "key": "<meta-feld>", "items": [ { "title", "slug", "status", "order", "meta": { feld: wert } } ] }
 */
function golfplatz_mcp_import_content( $input ) {
	$type = (string) ( $input['post_type'] ?? '' );
	if ( ! in_array( $type, GOLFPLATZ_MCP_IMPORT_TYPES, true ) || ! post_type_exists( $type ) ) {
		return new WP_Error( 'golfplatz_type', 'Beitragstyp nicht erlaubt oder nicht registriert.' );
	}
	$daten = json_decode( (string) @file_get_contents( __DIR__ . '/golfplatz/daten/' . $type . '.json' ), true );
	if ( ! is_array( $daten ) || empty( $daten['key'] ) || ! isset( $daten['items'] ) ) {
		return new WP_Error( 'golfplatz_daten', 'Datei fehlt oder hat nicht das erwartete Format.' );
	}
	$key = sanitize_key( $daten['key'] );
	$log = array();

	foreach ( (array) $daten['items'] as $item ) {
		// Schlüssel „slug“ = Eintrag am Slug erkennen, sonst an einem Meta-Feld.
		$wert  = 'slug' === $key ? sanitize_title( $item['slug'] ?? '' ) : ( $item['meta'][ $key ] ?? null );
		$suche = array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);
		if ( 'slug' === $key ) {
			$suche['name'] = $wert;
		} else {
			$suche['meta_key']   = $key;
			$suche['meta_value'] = $wert;
		}
		$vorhanden = get_posts( $suche );
		$post = array(
			'post_type'   => $type,
			'post_title'  => sanitize_text_field( $item['title'] ),
			'post_name'   => sanitize_title( $item['slug'] ?? $item['title'] ),
			'post_status' => $item['status'] ?? 'publish',
			'menu_order'  => (int) ( $item['order'] ?? 0 ),
		);
		if ( isset( $item['content'] ) ) {
			$post['post_content'] = wp_kses_post( (string) $item['content'] );
		}
		if ( $vorhanden ) {
			$post['ID'] = $vorhanden[0];
			$id         = wp_update_post( $post, true );
		} else {
			$id = wp_insert_post( $post, true );
		}
		if ( is_wp_error( $id ) ) {
			$log[] = array( $key => $wert, 'fehler' => $id->get_error_message() );
			continue;
		}
		// Taxonomien: { "personengruppe": ["Vorstand", "Clubmanagement"] } – fehlende Begriffe werden angelegt.
		foreach ( (array) ( $item['terms'] ?? array() ) as $taxonomie => $namen ) {
			if ( ! taxonomy_exists( $taxonomie ) || ! is_object_in_taxonomy( $type, $taxonomie ) ) {
				continue;
			}
			$ids = array();
			foreach ( (array) $namen as $name ) {
				$term = term_exists( $name, $taxonomie ) ?: wp_insert_term( $name, $taxonomie );
				if ( ! is_wp_error( $term ) ) {
					$ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
				}
			}
			wp_set_object_terms( $id, $ids, $taxonomie );
		}
		foreach ( (array) ( $item['meta'] ?? array() ) as $feld => $v ) {
			$feld = sanitize_key( $feld );
			$v    = golfplatz_mcp_import_ref( $v );
			if ( function_exists( 'rwmb_set_meta' ) ) {
				rwmb_set_meta( $id, $feld, $v );
			} else {
				update_post_meta( $id, $feld, $v );
			}
		}
		$log[] = array( $key => $wert, 'id' => $id, 'aktion' => $vorhanden ? 'aktualisiert' : 'angelegt', 'link' => get_permalink( $id ) );
	}
	return $log;
}

/** Nur Farb-Einstellungen von Automatic.css (Palette und Schalter je Farbe). */
function golfplatz_mcp_acss_farbschluessel( string $key ): bool {
	return (bool) preg_match( '/^(color-[a-z0-9-]+|option-[a-z]+-clr|option-palette-unify-[a-z-]+|unified-lightness-value|auto-color-scheme|website-color-scheme|option-ref-color-tokens|btn-(primary|secondary)-(hover-)?text|link-color(-hover)?|(primary|secondary|tertiary|accent|base|neutral|success|warning|danger|info)(-(ultra-light|light|semi-light|semi-dark|dark|ultra-dark|hover))?-[lch](-alt)?-oklch)$/', $key );
}

function golfplatz_mcp_acss_colors( $input ) {
	if ( ! class_exists( '\Automatic_CSS\API' ) ) {
		return new WP_Error( 'golfplatz_acss', 'Automatic.css ist nicht aktiv.' );
	}
	$werte = isset( $input['werte'] ) && is_array( $input['werte'] ) ? $input['werte'] : array();
	if ( ! empty( $input['aus_datei'] ) ) {
		$datei = json_decode( (string) @file_get_contents( __DIR__ . '/golfplatz/daten/acss-farben.json' ), true );
		if ( ! is_array( $datei ) ) {
			return new WP_Error( 'golfplatz_daten', 'daten/acss-farben.json fehlt oder ist ungültig.' );
		}
		$werte = array_merge( $datei, $werte );
	}
	if ( $werte ) {
		$abgelehnt = array_values( array_filter( array_keys( $werte ), fn( $k ) => ! golfplatz_mcp_acss_farbschluessel( (string) $k ) ) );
		if ( $abgelehnt ) {
			return new WP_Error( 'golfplatz_acss_key', 'Nicht erlaubte Schlüssel: ' . implode( ', ', $abgelehnt ) );
		}
		try {
			$werte = array_map( 'strval', $werte );
			// API::update_settings() hält jeden Schlüssel mit „color-“ für eine Hex-Farbe (auch website-color-scheme).
			// Hex-Farben laufen über die API, alles andere direkt über die ACSS-Einstellungen.
			$hex    = array_filter( $werte, fn( $k ) => str_starts_with( (string) $k, 'color-' ) && preg_match( '/^#[0-9a-f]{6}$/i', $werte[ $k ] ), ARRAY_FILTER_USE_KEY );
			$andere = array_diff_key( $werte, $hex );
			if ( $hex ) {
				\Automatic_CSS\API::update_settings( $hex, array( 'regenerate_css' => ! $andere ) );
			}
			if ( $andere ) {
				$db = \Automatic_CSS\Model\Database_Settings::get_instance();
				$db->save_settings( array_merge( $db->get_vars(), $andere ), true );
			}
		} catch ( \Throwable $e ) {
			return new WP_Error( 'golfplatz_acss_save', $e->getMessage() );
		}
	}
	$alle = (array) \Automatic_CSS\API::get_settings();
	if ( $werte ) {
		// Nach dem Speichern nur die geänderten Schlüssel zurückgeben
		return array_intersect_key( $alle, $werte );
	}
	return array_filter( $alle, fn( $k ) => golfplatz_mcp_acss_farbschluessel( (string) $k ), ARRAY_FILTER_USE_KEY );
}

function golfplatz_mcp_import_settings( $input ) {
	$seite = (string) ( $input['seite'] ?? '' );
	if ( ! in_array( $seite, array( 'clubdaten', 'platzstatus' ), true ) ) {
		return new WP_Error( 'golfplatz_seite', 'Einstellungsseite nicht erlaubt.' );
	}
	$werte = json_decode( (string) @file_get_contents( __DIR__ . '/golfplatz/daten/einstellungen-' . $seite . '.json' ), true );
	if ( ! is_array( $werte ) ) {
		return new WP_Error( 'golfplatz_daten', 'Datei fehlt oder ist ungültig.' );
	}
	$option = (array) get_option( $seite, array() );
	$log    = array( 'gesetzt' => array(), 'entfernt' => array() );
	if ( ! empty( $input['felder'] ) && is_array( $input['felder'] ) ) {
		$werte = array_intersect_key( $werte, array_flip( array_map( 'sanitize_key', $input['felder'] ) ) );
	}
	foreach ( $werte as $feld => $wert ) {
		$feld = sanitize_key( $feld );
		if ( null === $wert ) {
			unset( $option[ $feld ] );
			$log['entfernt'][] = $feld;
		} else {
			$option[ $feld ]  = $wert;
			$log['gesetzt'][] = $feld;
		}
	}
	update_option( $seite, $option );
	return $log;
}

/**
 * Legt eine Einstellungsseite so an, wie es der Meta-Box-Builder tut
 * (vgl. mb-acf-migration/src/Processors/SettingsPages.php).
 */
function golfplatz_mcp_save_settings_page( $input ) {
	if ( ! class_exists( 'MBB\Extensions\SettingsPage\Parser' ) ) {
		return new WP_Error( 'golfplatz_mbb', 'Meta Box Builder (Settings Page) ist nicht aktiv.' );
	}
	$id       = sanitize_key( $input['id'] );
	$tabs     = array();
	foreach ( (array) ( $input['tabs'] ?? array() ) as $i => $tab ) {
		$tabs[] = array(
			'id'    => 'tab_' . $i,
			'key'   => sanitize_key( $tab['key'] ),
			'value' => sanitize_text_field( $tab['label'] ),
		);
	}
	$settings = array(
		'id'            => $id,
		'option_name'   => sanitize_key( $input['option_name'] ?? $id ),
		'menu_title'    => sanitize_text_field( $input['title'] ),
		'page_title'    => sanitize_text_field( $input['title'] ),
		'capability'    => sanitize_key( $input['capability'] ?? 'manage_options' ),
		'menu_type'     => empty( $input['parent'] ) ? 'top' : 'submenu',
		'parent'        => (string) ( $input['parent'] ?? '' ),
		'icon_type'     => 'dashicons',
		'icon_dashicons' => sanitize_key( $input['icon'] ?? 'admin-generic' ),
		'position'      => (int) ( $input['position'] ?? 25 ),
		'style'         => 'no-boxes',
		'columns'       => (int) ( $input['columns'] ?? 1 ),
		'tabs'          => $tabs,
		'tab_style'     => $input['tab_style'] ?? 'left',
		'submit_button' => 'Speichern',
		'message'       => 'Gespeichert.',
	);

	$vorhanden = get_posts(
		array(
			'post_type'      => 'mb-settings-page',
			'name'           => $id,
			'post_status'    => 'any',
			'posts_per_page' => 1,
		)
	);
	$data = array(
		'post_title'  => $settings['menu_title'],
		'post_type'   => 'mb-settings-page',
		'post_status' => 'publish',
		'post_name'   => $id,
	);
	if ( $vorhanden ) {
		$data['ID'] = $vorhanden[0]->ID;
		$post_id    = wp_update_post( $data, true );
	} else {
		$post_id = wp_insert_post( $data, true );
	}
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$parser = new \MBB\Extensions\SettingsPage\Parser( $settings );
	$parser->parse_boolean_values()->parse_numeric_values();
	update_post_meta( $post_id, 'settings', $parser->get_settings() );
	$parser->parse();
	update_post_meta( $post_id, 'settings_page', $parser->get_settings() );

	return array(
		'post_id'       => $post_id,
		'settings_page' => get_post_meta( $post_id, 'settings_page', true ),
		'admin_url'     => admin_url( 'admin.php?page=' . $id ),
	);
}

function golfplatz_mcp_set_front_page( $input ) {
	$post = golfplatz_mcp_allowed_post( (int) ( $input['page_id'] ?? 0 ), 'page' );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $post->ID );
	return array( 'page_on_front' => $post->ID );
}
