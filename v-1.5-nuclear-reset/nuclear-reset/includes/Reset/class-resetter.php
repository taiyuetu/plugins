<?php
/**
 * The engine that performs the nuclear reset.
 *
 * @package NuclearReset\Reset
 */

namespace NuclearReset\Reset;

defined( 'ABSPATH' ) || exit;

/**
 * Class Resetter
 *
 * Executes each step of the nuclear reset in order and returns a detailed log.
 */
class Resetter {

    /** @var array<string, bool> Which operations to perform. */
    private array $opts;

    /** @var string[] Log lines accumulated during the reset. */
    private array $log = [];

    /** @var array<string, mixed> Critical options saved before any wipe. */
    private array $option_snapshot = [];

    /** @var array<string, mixed>|null Current admin user data saved before any wipe. */
    private ?array $admin_snapshot = null;

    /**
     * Core WordPress table names (without prefix) that must be re-seeded.
     * Everything else encountered in the DB will be treated as a plugin table and dropped.
     *
     * @var string[]
     */
    private const CORE_TABLES = [
        'commentmeta',
        'comments',
        'links',
        'options',
        'postmeta',
        'posts',
        'term_relationships',
        'term_taxonomy',
        'termmeta',
        'terms',
        'usermeta',
        'users',
    ];

    /**
     * Official WordPress bundled theme slugs (folder names).
     * These are always preserved during filesystem cleanup.
     *
     * @var string[]
     */
    private const DEFAULT_THEME_SLUGS = [
        'twentytwentyfive',
        'twentytwentyfour',
        'twentytwentythree',
        'twentytwentytwo',
        'twentytwentyone',
        'twentytwenty',
        'twentynineteen',
        'twentyeighteen',
        'twentyseventeen',
        'twentysixteen',
        'twentyfifteen',
        'twentyfourteen',
        'twentythirteen',
        'twentytwelve',
        'twentyeleven',
        'twentyten',
    ];

    /**
     * Multisite-only core tables (without prefix).
     *
     * @var string[]
     */
    private const CORE_MS_TABLES = [
        'blogs',
        'blog_versions',
        'registration_log',
        'signups',
        'site',
        'sitemeta',
        'sitecategories',
    ];

    /**
     * Constructor.
     *
     * @param array<string, bool> $opts Options controlling which steps run.
     */
    public function __construct( array $opts ) {
        $this->opts = $opts;
    }

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Run all selected reset operations and return the log.
     *
     * @return string[] Log lines.
     * @throws \RuntimeException On a fatal error.
     */
    public function run(): array {
        $this->log( '=== Nuclear Reset Started ===' );
        $this->log( 'Time: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC' );
        $this->log( 'Options: ' . wp_json_encode( $this->opts ) );
        $this->log( '' );

        @set_time_limit( 300 );
        @ini_set( 'memory_limit', '256M' );

        // Snapshot critical site identity before anything is touched.
        // These must survive a full options-table wipe.
        $this->snapshot_critical_options();

        // Order matters: deactivate plugins first so their shutdown
        // hooks do not interfere with subsequent DB operations.
        if ( $this->opts['plugins'] ) {
            $this->deactivate_plugins();
        }

        if ( $this->opts['theme'] ) {
            $this->activate_default_theme();
        }

        if ( $this->opts['content'] ) {
            $this->delete_all_content();
        }

        if ( $this->opts['options'] ) {
            $this->reset_options();
        }

        if ( $this->opts['db'] ) {
            $this->reset_database();
        }

        // Filesystem cleanup — runs after DB steps so deactivation/theme-switch
        // have already been committed to the DB before we touch the files.
        if ( $this->opts['del_plugins'] ) {
            $this->delete_custom_plugins();
        }

        if ( $this->opts['del_themes'] ) {
            $this->delete_custom_themes();
        }

        // Broken theme folders (missing style.css) are reported by WordPress
        // under Appearance → Themes → "Broken Themes". They can never be
        // activated, so they are swept even when delete_custom_themes() aborted
        // above (e.g. because no bundled default theme was available).
        if ( ! empty( $this->opts['theme'] ) || ! empty( $this->opts['del_themes'] ) ) {
            $this->remove_broken_themes();
        }

        // After a potential DB wipe, ensure the current admin user still exists.
        $this->ensure_admin_user();

        // Re-activate Nuclear Reset itself so the admin can still use it.
        $this->reactivate_self();

        $this->log( '' );
        $this->log( '=== Nuclear Reset Complete ===' );

        return $this->log;
    }

    // ── Pre-flight snapshot ────────────────────────────────────────────────────

    /**
     * Save site-critical options and the current admin user's credentials
     * so they can be restored if the options table is later wiped.
     */
    private function snapshot_critical_options(): void {
        // NOTE: `blogname` (site title) and `blogdescription` (tagline) are
        // deliberately NOT snapshotted. They are the site's editorial identity,
        // not infrastructure, so a nuclear reset must return them to WordPress'
        // out-of-the-box defaults instead of resurrecting whatever the site was
        // called before the reset. See reset_site_identity().
        $keys = [
            'siteurl', 'home', 'admin_email',
            'blog_charset', 'blog_public', 'wp_user_roles', 'db_version',
            'initial_db_version', 'auth_key', 'secure_auth_key', 'logged_in_key',
            'nonce_key', 'auth_salt', 'secure_auth_salt', 'logged_in_salt', 'nonce_salt',
            // Infrastructure option. WordPress builds the page header from
            // get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ).
            // If it is missing the site sends "Content-Type: ; charset=UTF-8", and
            // because core (and most hardening snippets) send
            // "X-Content-Type-Options: nosniff", browsers refuse to sniff and render
            // the raw HTML source as plain text instead of the page.
            'html_type',
        ];

        // The active-theme pointers must only be restored when we are NOT
        // deliberately switching to a default theme and NOT deleting theme
        // folders — otherwise we would either undo the switch or point the site
        // at a directory that is about to be removed.
        if ( empty( $this->opts['theme'] ) && empty( $this->opts['del_themes'] ) ) {
            $keys[] = 'template';
            $keys[] = 'stylesheet';
            $keys[] = 'current_theme';
        }

        foreach ( $keys as $key ) {
            $this->option_snapshot[ $key ] = get_option( $key );
        }

        $user = wp_get_current_user();
        if ( $user->exists() ) {
            $this->admin_snapshot = [
                'ID'              => $user->ID,
                'user_login'      => $user->user_login,
                'user_pass'       => $user->user_pass, // already hashed
                'user_email'      => $user->user_email,
                'user_registered' => $user->user_registered,
                'display_name'    => $user->display_name,
            ];
        }

        $this->log( '--- Pre-flight: Critical options + admin user snapshotted ---' );
    }

    // ── Step: Deactivate plugins ───────────────────────────────────────────────

    private function deactivate_plugins(): void {
        $this->log( '--- Step: Deactivate Plugins ---' );

        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $active = get_option( 'active_plugins', [] );
        $self   = NUCLEAR_RESET_BASENAME;
        $others = array_filter( $active, fn( $p ) => $p !== $self );

        if ( empty( $others ) ) {
            $this->log( '  No other active plugins found.' );
            return;
        }

        foreach ( $others as $plugin ) {
            deactivate_plugins( $plugin, true ); // true = silent
            $this->log( '  Deactivated: ' . $plugin );
        }

        $this->log( '  Total deactivated: ' . count( $others ) );
    }

    // ── Step: Activate default theme ──────────────────────────────────────────

    /**
     * Is a bundled default theme currently active *and* actually present on disk?
     *
     * get_stylesheet() alone is not enough: populate_options() writes the
     * WP_DEFAULT_THEME slug into `stylesheet` even when that theme is not
     * installed, so a slug check can claim a theme exists when it does not.
     */
    private function has_usable_default_theme(): bool {
        $active = get_stylesheet();

        if ( ! in_array( $active, self::DEFAULT_THEME_SLUGS, true ) ) {
            return false;
        }

        $theme = wp_get_theme( $active );
        $dir   = $theme->get_stylesheet_directory();

        // exists() only proves WordPress knows about the theme; the directory
        // check proves the files are physically there to be kept.
        return $theme->exists() && is_string( $dir ) && is_dir( $dir );
    }

    private function activate_default_theme(): void {
        $this->log( '--- Step: Activate Default Theme ---' );

        if ( ! function_exists( 'wp_get_themes' ) ) {
            require_once ABSPATH . 'wp-admin/includes/theme.php';
        }

        // WordPress default themes in preference order (newest first).
        $defaults = [
            'twentytwentyfive',
            'twentytwentyfour',
            'twentytwentythree',
            'twentytwentytwo',
            'twentytwentyone',
            'twentytwenty',
            'twentynineteen',
            'twentyseventeen',
            'twentysixteen',
        ];

        $installed = wp_get_themes();
        $chosen    = null;

        foreach ( $defaults as $slug ) {
            if ( isset( $installed[ $slug ] ) ) {
                $chosen = $slug;
                break;
            }
        }

        if ( null === $chosen ) {
            $this->log( '  WARNING: No bundled default theme found. Theme not changed.' );
            return;
        }

        switch_theme( $chosen );
        $this->log( '  Activated theme: ' . $chosen );
    }

    // ── Step: Delete all content ───────────────────────────────────────────────

    private function delete_all_content(): void {
        $this->log( '--- Step: Delete All Content ---' );

        global $wpdb;

        $current_user_id = get_current_user_id();

        // ── Comments ──────────────────────────────────────────────────────────
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->commentmeta}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->comments}" );
        $this->log( "  Deleted {$count} comment(s) + commentmeta." );

        // ── Posts & postmeta ──────────────────────────────────────────────────
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->postmeta}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->posts}" );
        $this->log( "  Deleted {$count} post(s) + postmeta." );

        // ── Terms, taxonomies, relationships ──────────────────────────────────
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->term_relationships}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->term_taxonomy}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->termmeta}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->terms}" );
        $this->log( "  Deleted {$count} term(s) + taxonomies, relationships, and termmeta." );

        // ── Non-admin users + usermeta ────────────────────────────────────────
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $users = $wpdb->get_col(
            $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID != %d", $current_user_id )
        );
        foreach ( $users as $uid ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->delete( $wpdb->usermeta, [ 'user_id' => $uid ] );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->delete( $wpdb->users, [ 'ID' => $uid ] );
        }
        $this->log( '  Deleted ' . count( $users ) . ' non-admin user(s).' );

        // ── Links (blogroll) ──────────────────────────────────────────────────
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->links}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->links}" );
        $this->log( "  Deleted {$count} link(s)." );

        // ── Transients ────────────────────────────────────────────────────────
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted_transients = $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'"
        );
        $this->log( "  Deleted {$deleted_transients} transient(s)." );

        // ── Object cache ──────────────────────────────────────────────────────
        wp_cache_flush();
        $this->log( '  Object cache flushed.' );
    }

    // ── Step: Reset options ────────────────────────────────────────────────────

    private function reset_options(): void {
        $this->log( '--- Step: Reset Options & Widgets ---' );

        // Options to explicitly reset to WP defaults.
        // (`blogname` / `blogdescription` live here on purpose: the site title
        // and tagline must go back to the factory defaults WordPress ships —
        // the same values populate_options() seeds on a fresh install.)
        $defaults = [
            'blogname'            => 'My Site',
            'blogdescription'     => '',
            'posts_per_page'      => 10,
            'date_format'         => 'F j, Y',
            'time_format'         => 'g:i a',
            'start_of_week'       => 1,
            'timezone_string'     => '',
            'permalink_structure' => '',
            'default_category'    => 1,
            'default_post_format' => 0,
            'default_comment_status' => 'open',
            'default_ping_status'    => 'open',
            'comment_moderation'     => 0,
            'show_on_front'          => 'posts',
            'page_on_front'          => 0,
            'page_for_posts'         => 0,
            'upload_path'            => '',
            'html_type'              => 'text/html',
        ];

        foreach ( $defaults as $key => $value ) {
            update_option( $key, $value );
            $this->log( "  Reset option: {$key}" );
        }

        // Clear widget data.
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'widget_%'" );
        $this->log( '  Cleared all widget settings.' );

        // Clear nav menu assignments.
        delete_option( 'nav_menu_options' );
        $this->log( '  Cleared nav menu assignments.' );

        // Flush rewrite rules.
        delete_option( 'rewrite_rules' );
        flush_rewrite_rules( false );
        $this->log( '  Flushed rewrite rules.' );
    }

    // ── Step: Reset database ──────────────────────────────────────────────────

    private function reset_database(): void {
        $this->log( '--- Step: Reset Database ---' );

        global $wpdb;

        $prefix      = $wpdb->prefix;
        $core_names  = self::CORE_TABLES;

        if ( is_multisite() ) {
            $core_names = array_merge( $core_names, self::CORE_MS_TABLES );
        }

        $core_full = array_map( fn( $t ) => $prefix . $t, $core_names );

        // Disable FK checks to allow table drops in any order.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );

        // Get every table in the current DB.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $all_tables = $wpdb->get_col( 'SHOW TABLES' );

        $dropped = 0;
        foreach ( $all_tables as $table ) {
            // Only touch tables that belong to this WordPress install (matching prefix).
            if ( strpos( $table, $prefix ) !== 0 ) {
                continue;
            }

            if ( in_array( $table, $core_full, true ) ) {
                // Core table — truncate (content already deleted in previous step).
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->query( "TRUNCATE TABLE `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $this->log( "  Truncated core table: {$table}" );
            } else {
                // Plugin/custom table — drop entirely.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $this->log( "  Dropped plugin table: {$table}" );
                ++$dropped;
            }
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );

        $this->log( "  Dropped {$dropped} plugin table(s)." );

        // Re-run WP install to recreate core tables and seed defaults.
        $this->reseed_core_tables();
    }

    /**
     * Re-seed core WordPress tables using the built-in install routine,
     * then restore critical options and the admin user that were snapshotted
     * before the wipe.
     */
    private function reseed_core_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        if ( ! function_exists( 'wp_get_db_schema' ) && file_exists( ABSPATH . 'wp-admin/includes/schema.php' ) ) {
            ob_start();
            require ABSPATH . 'wp-admin/includes/schema.php';
            ob_end_clean();
        }

        if ( function_exists( 'wp_get_db_schema' ) ) {
            dbDelta( wp_get_db_schema() );
            $this->log( '  dbDelta schema applied — core tables recreated.' );
        }

        // ── Flush the in-memory options cache ─────────────────────────────────
        // wp_options was just TRUNCATEd. WordPress's $alloptions cache still
        // holds the old values from the start of this request. update_option()
        // short-circuits (returns false without writing) when the new value
        // equals the cached old value — so we must invalidate the cache first,
        // then write directly via $wpdb->replace() to guarantee the rows land
        // in the now-empty table regardless of cache state.
        wp_cache_delete( 'alloptions', 'options' );
        wp_cache_delete( 'notoptions', 'options' );
        foreach ( array_keys( $this->option_snapshot ) as $key ) {
            wp_cache_delete( $key, 'options' );
        }

        // ── Restore site-critical options BEFORE wp_install_defaults() ────────
        // Use $wpdb->replace() (MySQL INSERT OR REPLACE) to bypass update_option()'s
        // value-equality short-circuit, which would silently skip writing when the
        // cached value matches the new value even though the DB row is gone.
        foreach ( $this->option_snapshot as $key => $value ) {
            if ( false !== $value ) {
                $wpdb->replace(
                    $wpdb->options,
                    [
                        'option_name'  => $key,
                        'option_value' => maybe_serialize( $value ),
                        'autoload'     => 'yes',
                    ],
                    [ '%s', '%s', '%s' ]
                );
            }
        }
        // Rebuild the alloptions cache from the live DB now that rows exist.
        wp_load_alloptions( true );
        $this->log( '  Site-critical options restored from snapshot (direct DB write).' );

        // ── Backfill every other missing core option ──────────────────────────
        // TRUNCATE removes *all* rows, but the snapshot only covers the keys we
        // know about. Anything else the current WordPress version expects (or
        // that a future version adds) would stay missing and break the site in
        // non-obvious ways — the empty `html_type` bug fixed in 1.0.1 is exactly
        // that failure mode. populate_options() re-inserts the core defaults for
        // missing rows only, so the values restored above always win.
        if ( ! function_exists( 'populate_options' ) && file_exists( ABSPATH . 'wp-admin/includes/schema.php' ) ) {
            require ABSPATH . 'wp-admin/includes/schema.php';
        }

        if ( function_exists( 'populate_options' ) && function_exists( '__get_option' ) ) {
            populate_options();

            // populate_options() writes with raw SQL, so the in-request option
            // caches still claim these options do not exist. Drop both caches
            // and reload before anything else reads them.
            wp_cache_delete( 'alloptions', 'options' );
            wp_cache_delete( 'notoptions', 'options' );
            wp_load_alloptions();

            $this->log( '  Missing core options backfilled with WordPress defaults.' );
        } else {
            $this->log( '  WARNING: populate_options() unavailable — some core options may still be missing.' );
        }

        // ── Reset site title & tagline to out-of-the-box defaults ─────────────
        // They are intentionally absent from the snapshot (see
        // snapshot_critical_options()), so the wipe already removed the old rows.
        // populate_options() seeds them on modern WordPress, but write them
        // explicitly so the outcome does not depend on the WP version and a site
        // with a custom title/tagline really ends up back at factory defaults.
        $this->reset_site_identity();

        // ── Restore admin user ────────────────────────────────────────────────
        $admin_user_id = 1;
        if ( null !== $this->admin_snapshot ) {
            $existing = get_user_by( 'login', $this->admin_snapshot['user_login'] );

            if ( $existing ) {
                $admin_user_id = $existing->ID;
            } else {
                // Re-insert the admin user row directly to preserve login + hashed password.
                $wpdb->insert(
                    $wpdb->users,
                    [
                        'ID'              => $this->admin_snapshot['ID'],
                        'user_login'      => $this->admin_snapshot['user_login'],
                        'user_pass'       => $this->admin_snapshot['user_pass'],
                        'user_email'      => $this->admin_snapshot['user_email'],
                        'user_registered' => $this->admin_snapshot['user_registered'],
                        'display_name'    => $this->admin_snapshot['display_name'],
                        'user_status'     => 0,
                    ]
                );
                $admin_user_id = (int) $wpdb->insert_id ?: $this->admin_snapshot['ID'];

                // Restore administrator capability meta.
                $wpdb->insert(
                    $wpdb->usermeta,
                    [
                        'user_id'    => $admin_user_id,
                        'meta_key'   => $wpdb->prefix . 'capabilities',
                        'meta_value' => serialize( [ 'administrator' => true ] ),
                    ]
                );
                $wpdb->insert(
                    $wpdb->usermeta,
                    [
                        'user_id'    => $admin_user_id,
                        'meta_key'   => $wpdb->prefix . 'user_level',
                        'meta_value' => '10',
                    ]
                );
            }

            $this->log( '  Admin user restored: ' . $this->admin_snapshot['user_login'] . ' (ID: ' . $admin_user_id . ')' );
        }

        // Seed default content (Hello World, Sample Page, default category, etc.).
        wp_install_defaults( $admin_user_id );
        $this->log( '  WordPress defaults seeded (Hello World post, Sample Page, default category).' );

        // Bump db_version so WP does not re-run upgrade on next load.
        $wpdb->replace(
            $wpdb->options,
            [
                'option_name'  => 'db_version',
                'option_value' => (string) $GLOBALS['wp_db_version'],
                'autoload'     => 'yes',
            ],
            [ '%s', '%s', '%s' ]
        );
        wp_cache_delete( 'db_version', 'options' );
        $this->log( '  db_version updated.' );
    }

    /**
     * Put the site title and tagline back to the WordPress out-of-the-box
     * defaults ('My Site' / empty) and make sure the change is visible to the
     * rest of this request.
     */
    private function reset_site_identity(): void {
        wp_cache_delete( 'blogname', 'options' );
        wp_cache_delete( 'blogdescription', 'options' );

        update_option( 'blogname', 'My Site' );
        update_option( 'blogdescription', '' );

        $this->log( '  Site title & tagline reset to WordPress defaults.' );
    }

    // ── Step: Ensure admin user still exists ─────────────────────────────────

    private function ensure_admin_user(): void {
        $this->log( '--- Step: Verify Admin User ---' );

        // Clear WP's internal user cache so we query the live DB, not a stale
        // PHP object that survived the table truncation.
        wp_cache_delete( get_current_user_id(), 'users' );
        wp_cache_delete( get_current_user_id(), 'userlogins' );
        $GLOBALS['current_user'] = null;

        $current_user = wp_get_current_user();

        if ( ! $current_user->exists() ) {
            // Try to locate the user by login from our snapshot.
            if ( null !== $this->admin_snapshot ) {
                $found = get_user_by( 'login', $this->admin_snapshot['user_login'] );
                if ( $found ) {
                    $found->set_role( 'administrator' );
                    $this->log( '  Admin user verified via snapshot: ' . $found->user_login . ' (ID: ' . $found->ID . ')' );
                    return;
                }
            }

            $this->log( '  WARNING: Current user not found after reset. Creating emergency admin.' );
            $this->create_emergency_admin();
            return;
        }

        // Confirm the row actually exists in the live DB (not just the PHP cache).
        $exists_in_db = get_user_by( 'id', $current_user->ID );
        if ( ! $exists_in_db ) {
            $this->log( '  WARNING: Cached user ID ' . $current_user->ID . ' missing from DB. Creating emergency admin.' );
            $this->create_emergency_admin();
            return;
        }

        $current_user->set_role( 'administrator' );
        wp_update_user( [
            'ID'   => $current_user->ID,
            'role' => 'administrator',
        ] );

        $this->log( '  Admin user preserved: ' . $current_user->user_login . ' (ID: ' . $current_user->ID . ')' );
    }

    /**
     * Create an emergency admin account if the current user was somehow lost.
     */
    private function create_emergency_admin(): void {
        $login    = 'nuclear_reset_admin_' . wp_generate_password( 6, false );
        $password = wp_generate_password( 24 );
        $email    = get_option( 'admin_email' );

        $user_id = wp_create_user( $login, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            $this->log( '  ERROR: Could not create emergency admin — ' . $user_id->get_error_message() );
            return;
        }

        $user = new \WP_User( $user_id );
        $user->set_role( 'administrator' );

        $this->log( "  Emergency admin created — login: {$login} / password: {$password}" );
        $this->log( '  *** SAVE THESE CREDENTIALS BEFORE CLOSING THIS PAGE ***' );
    }

    // ── Step: Re-activate Nuclear Reset itself ────────────────────────────────

    private function reactivate_self(): void {
        $this->log( '--- Step: Re-activate Nuclear Reset Plugin ---' );

        global $wpdb;

        // Re-read from the live DB (not the potentially stale cache) to get
        // the current active_plugins list after all previous steps.
        wp_cache_delete( 'active_plugins', 'options' );
        $active = get_option( 'active_plugins', [] );
        if ( ! is_array( $active ) ) {
            $active = [];
        }

        if ( ! in_array( NUCLEAR_RESET_BASENAME, $active, true ) ) {
            $active[] = NUCLEAR_RESET_BASENAME;
            $wpdb->replace(
                $wpdb->options,
                [
                    'option_name'  => 'active_plugins',
                    'option_value' => serialize( $active ),
                    'autoload'     => 'yes',
                ],
                [ '%s', '%s', '%s' ]
            );
            wp_cache_delete( 'active_plugins', 'options' );
            $this->log( '  Nuclear Reset re-added to active plugins.' );
        } else {
            $this->log( '  Nuclear Reset was already active.' );
        }
    }

    // ── Step: Delete custom plugin files ──────────────────────────────────────

    private function delete_custom_plugins(): void {
        $this->log( '--- Step: Delete Custom Plugin Files ---' );

        // If the "Deactivate Plugins" step was not selected, active_plugins still
        // lists the folders we are about to remove. Deactivate them first so the
        // option never keeps dangling entries pointing at deleted files.
        if ( empty( $this->opts['plugins'] ) ) {
            $this->deactivate_plugins();
        }

        $this->init_filesystem();
        global $wp_filesystem;

        $plugins_dir = WP_PLUGIN_DIR;
        $self_slug   = explode( '/', NUCLEAR_RESET_BASENAME )[0]; // e.g. "nuclear-reset"

        if ( ! is_dir( $plugins_dir ) ) {
            $this->log( '  Plugins directory not found — skipped.' );
            return;
        }

        $entries = scandir( $plugins_dir );
        $deleted = 0;

        foreach ( $entries as $entry ) {
            if ( '.' === $entry || '..' === $entry ) {
                continue;
            }

            // Skip Nuclear Reset itself.
            if ( $entry === $self_slug ) {
                $this->log( "  Kept: {$entry} (Nuclear Reset)" );
                continue;
            }

            $path = trailingslashit( $plugins_dir ) . $entry;

            if ( is_dir( $path ) ) {
                if ( $wp_filesystem->delete( $path, true ) ) {
                    $this->log( "  Deleted plugin folder: {$entry}" );
                    ++$deleted;
                } else {
                    $this->log( "  WARNING: Could not delete plugin folder: {$entry}" );
                }
            } elseif ( is_file( $path ) && str_ends_with( $entry, '.php' ) ) {
                // Single-file plugins sitting directly in /plugins/.
                if ( $wp_filesystem->delete( $path ) ) {
                    $this->log( "  Deleted single-file plugin: {$entry}" );
                    ++$deleted;
                } else {
                    $this->log( "  WARNING: Could not delete plugin file: {$entry}" );
                }
            }
        }

        $this->log( "  Total deleted: {$deleted} plugin(s)." );
    }

    // ── Step: Delete custom theme files ───────────────────────────────────────

    private function delete_custom_themes(): void {
        $this->log( '--- Step: Delete Custom Theme Files ---' );

        $this->init_filesystem();
        global $wp_filesystem;

        $themes_dir    = get_theme_root();
        $default_slugs = self::DEFAULT_THEME_SLUGS;

        if ( ! is_dir( $themes_dir ) ) {
            $this->log( '  Themes directory not found — skipped.' );
            return;
        }

        // ── Safety gate ───────────────────────────────────────────────────────
        // Deleting the folder of the theme that is currently active leaves the
        // site on WordPress' "The theme directory does not exist" screen. So we
        // make sure a bundled default theme is installed *and* active before a
        // single folder is removed. If that cannot be achieved we abort instead
        // of gambling with the site's front end.
        if ( empty( $this->opts['theme'] ) && ! $this->has_usable_default_theme() ) {
            $this->log( '  Active theme is a custom theme — switching to a bundled default first.' );
            $this->activate_default_theme();
        }

        if ( ! $this->has_usable_default_theme() ) {
            // Nothing bundled to fall back on yet — fetch one *before* deleting.
            $this->log( '  No usable bundled default theme — downloading one first.' );
            $this->download_default_theme();
        }

        if ( ! $this->has_usable_default_theme() ) {
            $this->log( "  ABORT: no usable bundled default theme is active (active: '" . get_stylesheet() . "')." );
            $this->log( '  Refusing to delete theme files — the site would be left without a working theme.' );
            return;
        }

        $entries       = scandir( $themes_dir );
        $deleted       = 0;
        $defaults_kept = [];

        foreach ( $entries as $entry ) {
            if ( '.' === $entry || '..' === $entry ) {
                continue;
            }

            $path = trailingslashit( $themes_dir ) . $entry;

            if ( ! is_dir( $path ) ) {
                continue;
            }

            if ( in_array( $entry, $default_slugs, true ) ) {
                $defaults_kept[] = $entry;
                $this->log( "  Kept default theme: {$entry}" );
                continue;
            }

            if ( $wp_filesystem->delete( $path, true ) ) {
                $this->log( "  Deleted custom theme: {$entry}" );
                ++$deleted;
            } else {
                $this->log( "  WARNING: Could not delete theme folder: {$entry}" );
            }
        }

        $this->log( "  Total deleted: {$deleted} custom theme(s)." );

        // ── Download a default theme if none is installed ──────────────────────
        if ( empty( $defaults_kept ) ) {
            $this->log( '  No default theme found — downloading Twenty Twenty-Five…' );
            $this->download_default_theme();
        }
    }

    // ── Step: Remove broken themes ───────────────────────────────────────────

    /**
     * Delete theme directories that WordPress reports as broken.
     *
     * A directory inside the theme root without a style.css can never be loaded
     * or activated, yet WordPress still lists it under Appearance → Themes →
     * "Broken Themes" ("Stylesheet is missing"). This sweep runs independently
     * of delete_custom_themes() so such a folder is always cleaned up — even
     * when that step aborts, or when the broken folder survived an earlier reset.
     */
    private function remove_broken_themes(): void {
        $this->log( '--- Step: Remove Broken Themes ---' );

        $this->init_filesystem();
        global $wp_filesystem;

        // Search every registered theme root — a site can have more than one
        // (WP_CONTENT_DIR/themes plus anything added via register_theme_directory).
        $roots = function_exists( 'wp_get_theme_directories' )
            ? wp_get_theme_directories()
            : [ get_theme_root() ];

        $roots = array_unique( array_filter( array_map( 'untrailingslashit', (array) $roots ) ) );
        $found = false;
        $removed = 0;

        foreach ( $roots as $themes_dir ) {
            if ( ! is_dir( $themes_dir ) ) {
                continue;
            }

            $entries = (array) scandir( $themes_dir );

            foreach ( $entries as $entry ) {
                if ( '.' === $entry || '..' === $entry || str_starts_with( $entry, '.' ) ) {
                    continue;
                }

                $path = trailingslashit( $themes_dir ) . $entry;

                if ( ! is_dir( $path ) ) {
                    continue;
                }

                $found = true;

                // WordPress decides a theme is broken when style.css is missing
                // from the theme root — keep anything that passes that test.
                if ( is_file( $path . '/style.css' ) ) {
                    continue;
                }

                if ( $wp_filesystem->delete( $path, true ) ) {
                    $this->log( "  Deleted broken theme: {$entry} (stylesheet is missing)" );
                    ++$removed;
                } else {
                    $this->log( "  WARNING: Could not delete broken theme folder: {$entry}" );
                }
            }
        }

        if ( ! $found ) {
            $this->log( '  Themes directory not found — skipped.' );
            return;
        }

        // Drop the cached directory scan so the next Themes screen load re-reads
        // the theme root instead of listing folders we just removed.
        delete_transient( 'theme_roots' );

        $this->log( "  Total broken theme(s) removed: {$removed}." );
    }

    /**
     * Download and install the latest Twenty Twenty-Five theme from WordPress.org.
     * Falls back through older defaults if the download fails.
     */
    private function download_default_theme(): void {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        // Preference list — newest first.
        $candidates = [
            'twentytwentyfive' => 'https://downloads.wordpress.org/theme/twentytwentyfive.latest-stable.zip',
            'twentytwentyfour' => 'https://downloads.wordpress.org/theme/twentytwentyfour.latest-stable.zip',
            'twentytwentythree' => 'https://downloads.wordpress.org/theme/twentytwentythree.latest-stable.zip',
        ];

        $skin     = new \Automatic_Upgrader_Skin();
        $upgrader = new \Theme_Upgrader( $skin );

        foreach ( $candidates as $slug => $url ) {
            $result = $upgrader->install( $url );

            if ( ! is_wp_error( $result ) && true === $result ) {
                $this->log( "  Downloaded and installed theme: {$slug}" );
                // Activate it so the DB points to a real theme on disk.
                switch_theme( $slug );
                $this->log( "  Activated theme: {$slug}" );
                return;
            }

            $err = is_wp_error( $result ) ? $result->get_error_message() : 'unknown error';
            $this->log( "  Download failed for {$slug}: {$err} — trying next…" );
        }

        $this->log( '  WARNING: Could not download any default theme. Site may show a theme error.' );
    }

    /**
     * Initialise the WordPress filesystem abstraction layer (direct mode in AJAX context).
     */
    private function init_filesystem(): void {
        global $wp_filesystem;

        if ( ! empty( $wp_filesystem ) ) {
            return;
        }

        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Force 'direct' so no FTP credentials are requested mid-AJAX.
        add_filter( 'filesystem_method', static fn() => 'direct' );
        WP_Filesystem();
        remove_filter( 'filesystem_method', static fn() => 'direct' );
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Append a line to the internal log.
     *
     * @param string $line Message.
     */
    private function log( string $line ): void {
        $this->log[] = $line;
    }
}
