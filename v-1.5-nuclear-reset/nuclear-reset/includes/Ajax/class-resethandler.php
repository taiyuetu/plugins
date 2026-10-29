<?php
/**
 * AJAX endpoint that receives the reset request from the browser.
 *
 * @package NuclearReset\Ajax
 */

namespace NuclearReset\Ajax;

defined( 'ABSPATH' ) || exit;

use NuclearReset\Reset\Resetter;

/**
 * Class ResetHandler
 */
class ResetHandler {

    /**
     * Handle the wp_ajax_nuclear_reset_execute action.
     *
     * Validates nonce + capability, then delegates to Resetter.
     */
    public function handle(): void {
        // ── Security checks ───────────────────────────────────────────────────
        if ( ! check_ajax_referer( 'nuclear_reset_execute', 'nonce', false ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Security check failed — invalid nonce.', 'nuclear-reset' ) ],
                403
            );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [ 'message' => __( 'You do not have permission to perform this action.', 'nuclear-reset' ) ],
                403
            );
        }

        // ── Parse options sent from the browser ───────────────────────────────
        $opts = [
            'content'     => ! empty( $_POST['opt_content'] ),
            'db'          => ! empty( $_POST['opt_db'] ),
            'plugins'     => ! empty( $_POST['opt_plugins'] ),
            'theme'       => ! empty( $_POST['opt_theme'] ),
            'options'     => ! empty( $_POST['opt_options'] ),
            'del_plugins' => ! empty( $_POST['opt_del_plugins'] ),
            'del_themes'  => ! empty( $_POST['opt_del_themes'] ),
        ];

        // Must reset at least one thing.
        if ( ! in_array( true, $opts, true ) ) {
            wp_send_json_error(
                [ 'message' => __( 'No reset options selected.', 'nuclear-reset' ) ]
            );
        }

        // ── Execute ───────────────────────────────────────────────────────────
        try {
            $resetter = new Resetter( $opts );
            $log      = $resetter->run();

            wp_send_json_success(
                [
                    'message' => __( 'Nuclear reset completed successfully.', 'nuclear-reset' ),
                    'log'     => $log,
                    'redirect'=> admin_url( '?nuclear_reset_done=1' ),
                ]
            );
        } catch ( \Throwable $e ) {
            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                    'trace'   => WP_DEBUG ? $e->getTraceAsString() : '',
                ]
            );
        }
    }
}
