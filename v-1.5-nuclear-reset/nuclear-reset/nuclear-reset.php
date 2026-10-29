<?php
/**
 * Plugin Name:       Nuclear Reset
 * Plugin URI:        https://github.com/your-repo/nuclear-reset
 * Description:       Safely reset your WordPress installation to its original out-of-the-box state: purge all content, reset the database to core tables, switch to the default theme, and deactivate all plugins (except this one). Irreversible — use with caution.
 * Version:           1.0.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Your Name
 * Author URI:        https://yoursite.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nuclear-reset
 * Domain Path:       /languages
 *
 * @package NuclearReset
 */

defined( 'ABSPATH' ) || exit;

// ── Plugin constants ──────────────────────────────────────────────────────────
define( 'NUCLEAR_RESET_VERSION',   '1.0.2' );
define( 'NUCLEAR_RESET_FILE',      __FILE__ );
define( 'NUCLEAR_RESET_DIR',       plugin_dir_path( __FILE__ ) );
define( 'NUCLEAR_RESET_URL',       plugin_dir_url( __FILE__ ) );
define( 'NUCLEAR_RESET_BASENAME',  plugin_basename( __FILE__ ) );

// ── Autoload classes ──────────────────────────────────────────────────────────
spl_autoload_register( function ( string $class ): void {
    $prefix = 'NuclearReset\\';
    $base   = NUCLEAR_RESET_DIR . 'includes/';

    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }

    $relative = str_replace( $prefix, '', $class );
    $parts    = explode( '\\', $relative );
    $class_name = array_pop( $parts );
    $filename   = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
    $file       = $base . ( $parts ? implode( '/', $parts ) . '/' : '' ) . $filename;

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

// ── Bootstrap ─────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', [ 'NuclearReset\\Plugin', 'get_instance' ] );
