<?php
/**
 * Main plugin class.
 *
 * @package NuclearReset
 */

namespace NuclearReset;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Bootstraps all sub-components and registers hooks.
 */
final class Plugin {

    /** @var self|null Singleton instance. */
    private static ?self $instance = null;

    /** Private constructor — use get_instance(). */
    private function __construct() {
        $this->load_textdomain();
        $this->register_hooks();
    }

    /**
     * Return (and lazily create) the singleton.
     */
    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load plugin translations.
     */
    private function load_textdomain(): void {
        load_plugin_textdomain(
            'nuclear-reset',
            false,
            dirname( NUCLEAR_RESET_BASENAME ) . '/languages'
        );
    }

    /**
     * Register all WordPress hooks.
     */
    private function register_hooks(): void {
        // Admin UI
        $admin = new Admin\Page();
        add_action( 'admin_menu',             [ $admin, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts',  [ $admin, 'enqueue_assets' ] );

        // AJAX handler (admin-only, nonce-protected)
        $handler = new Ajax\ResetHandler();
        add_action( 'wp_ajax_nuclear_reset_execute', [ $handler, 'handle' ] );

        // Plugin action link
        add_filter( 'plugin_action_links_' . NUCLEAR_RESET_BASENAME, [ $this, 'action_links' ] );
    }

    /**
     * Add a quick "Settings" link on the Plugins screen.
     *
     * @param string[] $links Existing action links.
     * @return string[]
     */
    public function action_links( array $links ): array {
        $url  = admin_url( 'tools.php?page=nuclear-reset' );
        $link = sprintf(
            '<a href="%s" style="color:#c9302c;font-weight:700;">%s</a>',
            esc_url( $url ),
            esc_html__( 'Launch', 'nuclear-reset' )
        );
        array_unshift( $links, $link );
        return $links;
    }
}
