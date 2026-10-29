<?php
/**
 * Nuclear Reset — Uninstall Script
 *
 * Fired when the plugin is deleted from the Plugins screen.
 * Only removes the plugin's own options — nothing else.
 *
 * @package NuclearReset
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove any options the plugin may have stored.
delete_option( 'nuclear_reset_log' );
delete_site_option( 'nuclear_reset_log' );
