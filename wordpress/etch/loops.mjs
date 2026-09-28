// Etch-Loops (Loop-Presets), gespeichert in der Etch-Option etch_loops.
// Übertragen mit golfplatz/sync-from-files (Schritt „loops“, nur wp-query auf freigegebene Beitragstypen).
// Einbinden im Markup: loop({ loopId: 'gp-bahnen' }, [...]) – Felder per {item.metabox.<feld>}.

export const loops = [
  {
    id: 'gp-bahnen',
    name: 'Spielbahnen 1–18',
    key: 'spielbahnen',
    config: {
      type: 'wp-query',
      args: {
        post_type: 'spielbahn',
        post_status: 'publish',
        posts_per_page: 18,
        meta_key: 'bahn_nummer',
        orderby: 'meta_value_num',
        order: 'ASC',
      },
    },
  },
];
