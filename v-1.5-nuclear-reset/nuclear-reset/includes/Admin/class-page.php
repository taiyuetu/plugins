<?php
/**
 * Admin page registration and rendering.
 *
 * @package NuclearReset\Admin
 */

namespace NuclearReset\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Page
 */
class Page {

    private const MENU_SLUG  = 'nuclear-reset';
    private const CAPABILITY = 'manage_options';

    /**
     * Register the Tools → Nuclear Reset menu page.
     */
    public function register_menu(): void {
        add_management_page(
            __( 'Nuclear Reset', 'nuclear-reset' ),
            __( 'Nuclear Reset', 'nuclear-reset' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            [ $this, 'render' ]
        );
    }

    /**
     * Enqueue CSS + JS only on our page.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( string $hook ): void {
        if ( 'tools_page_nuclear-reset' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'nuclear-reset-admin',
            NUCLEAR_RESET_URL . 'assets/css/admin.css',
            [],
            NUCLEAR_RESET_VERSION
        );

        wp_enqueue_script(
            'nuclear-reset-admin',
            NUCLEAR_RESET_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            NUCLEAR_RESET_VERSION,
            true
        );

        wp_localize_script(
            'nuclear-reset-admin',
            'NuclearResetData',
            [
                'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
                'nonce'            => wp_create_nonce( 'nuclear_reset_execute' ),
                'confirmPhrase'    => __( 'RESET', 'nuclear-reset' ),
                'i18n'             => [
                    'resetting'    => __( 'Resetting… please wait', 'nuclear-reset' ),
                    'done'         => __( 'Reset complete! Redirecting…', 'nuclear-reset' ),
                    'error'        => __( 'An error occurred. Check the log below.', 'nuclear-reset' ),
                    'confirmError' => __( 'You must type RESET to confirm.', 'nuclear-reset' ),
                ],
            ]
        );
    }

    /**
     * Render the admin page HTML.
     */
    public function render(): void {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'nuclear-reset' ) );
        }

        $site_name = get_bloginfo( 'name' );
        $site_url  = get_bloginfo( 'url' );
        ?>
        <div class="wrap nr-wrap">

            <!-- ── Header ─────────────────────────────────────────── -->
            <div class="nr-header">
                <div class="nr-header__icon">☢️</div>
                <h1 class="nr-header__title">
                    <?php esc_html_e( 'Nuclear Reset', 'nuclear-reset' ); ?>
                </h1>
                <p class="nr-header__subtitle">
                    <?php esc_html_e( 'Restore WordPress to its original out-of-the-box state.', 'nuclear-reset' ); ?>
                </p>
            </div>

            <!-- ── Warning banner ─────────────────────────────────── -->
            <div class="nr-danger-banner">
                <span class="nr-danger-banner__icon">⚠️</span>
                <div>
                    <strong><?php esc_html_e( 'THIS ACTION IS PERMANENT AND CANNOT BE UNDONE.', 'nuclear-reset' ); ?></strong><br>
                    <?php
                    printf(
                        /* translators: 1: site name, 2: site URL */
                        esc_html__( 'You are about to nuclear-reset "%1$s" (%2$s).', 'nuclear-reset' ),
                        esc_html( $site_name ),
                        esc_html( $site_url )
                    );
                    ?>
                </div>
            </div>

            <!-- ── Two-column layout ───────────────────────────────── -->
            <div class="nr-layout">

                <!-- Left: What will happen -->
                <div class="nr-card">
                    <h2 class="nr-card__title">
                        <span>📋</span>
                        <?php esc_html_e( 'What Will Happen', 'nuclear-reset' ); ?>
                    </h2>
                    <ul class="nr-checklist">
                        <li class="nr-checklist__item nr-checklist__item--danger">
                            <span class="nr-checklist__icon">🗑️</span>
                            <div>
                                <strong><?php esc_html_e( 'All Content Deleted', 'nuclear-reset' ); ?></strong>
                                <p><?php esc_html_e( 'Posts, pages, custom post types, revisions, attachments, comments, terms, and taxonomies will be permanently removed.', 'nuclear-reset' ); ?></p>
                            </div>
                        </li>
                        <li class="nr-checklist__item nr-checklist__item--danger">
                            <span class="nr-checklist__icon">🗄️</span>
                            <div>
                                <strong><?php esc_html_e( 'Database Reset to Core Tables', 'nuclear-reset' ); ?></strong>
                                <p><?php esc_html_e( 'All plugin-created tables will be dropped. Core WordPress tables will be truncated and re-seeded with fresh defaults.', 'nuclear-reset' ); ?></p>
                            </div>
                        </li>
                        <li class="nr-checklist__item nr-checklist__item--danger">
                            <span class="nr-checklist__icon">🔌</span>
                            <div>
                                <strong><?php esc_html_e( 'All Plugins Deactivated', 'nuclear-reset' ); ?></strong>
                                <p><?php esc_html_e( 'Every plugin except Nuclear Reset itself will be deactivated (plugin files are not deleted).', 'nuclear-reset' ); ?></p>
                            </div>
                        </li>
                        <li class="nr-checklist__item nr-checklist__item--danger">
                            <span class="nr-checklist__icon">🎨</span>
                            <div>
                                <strong><?php esc_html_e( 'Default Theme Activated', 'nuclear-reset' ); ?></strong>
                                <p><?php esc_html_e( 'The latest bundled WordPress default theme (Twenty Twenty-Five, Twenty Twenty-Four, etc.) will be activated.', 'nuclear-reset' ); ?></p>
                            </div>
                        </li>
                        <li class="nr-checklist__item nr-checklist__item--warn">
                            <span class="nr-checklist__icon">⚙️</span>
                            <div>
                                <strong><?php esc_html_e( 'Site Options Reset', 'nuclear-reset' ); ?></strong>
                                <p><?php esc_html_e( 'Widget areas, menus, custom settings, and transients will be cleared. The site title and tagline return to the WordPress defaults; site URL and admin email are preserved.', 'nuclear-reset' ); ?></p>
                            </div>
                        </li>
                        <li class="nr-checklist__item nr-checklist__item--ok">
                            <span class="nr-checklist__icon">✅</span>
                            <div>
                                <strong><?php esc_html_e( 'Your Admin Account Is Kept', 'nuclear-reset' ); ?></strong>
                                <p><?php esc_html_e( 'Your current administrator user account and credentials will not be altered.', 'nuclear-reset' ); ?></p>
                            </div>
                        </li>
                        <li class="nr-checklist__item nr-checklist__item--ok">
                            <span class="nr-checklist__icon">✅</span>
                            <div>
                                <strong><?php esc_html_e( 'wp-config.php & Uploads Untouched', 'nuclear-reset' ); ?></strong>
                                <p><?php esc_html_e( 'wp-config.php and the uploads folder are never modified.', 'nuclear-reset' ); ?></p>
                            </div>
                        </li>
                        <li class="nr-checklist__item nr-checklist__item--danger">
                            <span class="nr-checklist__icon">🗂️</span>
                            <div>
                                <strong><?php esc_html_e( 'Custom Plugin Files Deleted', 'nuclear-reset' ); ?></strong>
                                <p><?php esc_html_e( 'All plugin folders except Nuclear Reset itself will be permanently removed from the filesystem.', 'nuclear-reset' ); ?></p>
                            </div>
                        </li>
                        <li class="nr-checklist__item nr-checklist__item--danger">
                            <span class="nr-checklist__icon">🎨</span>
                            <div>
                                <strong><?php esc_html_e( 'Custom Theme Files Deleted', 'nuclear-reset' ); ?></strong>
                                <p><?php esc_html_e( 'All non-default theme folders will be removed, and any broken theme folder (missing its stylesheet) is cleaned up so it no longer appears under "Broken Themes". If no bundled WordPress theme is present, the latest Twenty Twenty-Five theme will be downloaded automatically.', 'nuclear-reset' ); ?></p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Right: Confirmation form -->
                <div class="nr-card nr-card--confirm">
                    <h2 class="nr-card__title">
                        <span>🔐</span>
                        <?php esc_html_e( 'Confirm Reset', 'nuclear-reset' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'To proceed, type the word below exactly as shown:', 'nuclear-reset' ); ?></p>

                    <div class="nr-confirm-phrase">RESET</div>

                    <label for="nr-confirm-input" class="screen-reader-text">
                        <?php esc_html_e( 'Type RESET to confirm', 'nuclear-reset' ); ?>
                    </label>
                    <input
                        id="nr-confirm-input"
                        type="text"
                        class="nr-confirm-input"
                        placeholder="<?php esc_attr_e( 'Type RESET here…', 'nuclear-reset' ); ?>"
                        autocomplete="off"
                        spellcheck="false"
                    >

                    <div class="nr-options">
                        <label class="nr-option">
                            <input type="checkbox" id="nr-opt-content" checked>
                            <?php esc_html_e( 'Delete all content (posts, pages, media, comments, terms)', 'nuclear-reset' ); ?>
                        </label>
                        <label class="nr-option">
                            <input type="checkbox" id="nr-opt-db" checked>
                            <?php esc_html_e( 'Reset database (drop plugin tables, re-seed core tables)', 'nuclear-reset' ); ?>
                        </label>
                        <label class="nr-option">
                            <input type="checkbox" id="nr-opt-plugins" checked>
                            <?php esc_html_e( 'Deactivate all plugins', 'nuclear-reset' ); ?>
                        </label>
                        <label class="nr-option">
                            <input type="checkbox" id="nr-opt-theme" checked>
                            <?php esc_html_e( 'Activate default theme', 'nuclear-reset' ); ?>
                        </label>
                        <label class="nr-option">
                            <input type="checkbox" id="nr-opt-options" checked>
                            <?php esc_html_e( 'Reset site options & widgets', 'nuclear-reset' ); ?>
                        </label>
                        <label class="nr-option">
                            <input type="checkbox" id="nr-opt-del-plugins" checked>
                            <?php esc_html_e( 'Delete custom plugin files from disk', 'nuclear-reset' ); ?>
                        </label>
                        <label class="nr-option">
                            <input type="checkbox" id="nr-opt-del-themes" checked>
                            <?php esc_html_e( 'Delete custom theme files from disk (download default if missing)', 'nuclear-reset' ); ?>
                        </label>
                    </div>

                    <button id="nr-execute-btn" class="nr-btn nr-btn--danger" disabled>
                        ☢️ <?php esc_html_e( 'Execute Nuclear Reset', 'nuclear-reset' ); ?>
                    </button>

                    <!-- Progress area (hidden until reset starts) -->
                    <div id="nr-progress" class="nr-progress" hidden>
                        <div class="nr-progress__spinner"></div>
                        <p id="nr-progress__message" class="nr-progress__message">
                            <?php esc_html_e( 'Resetting… please wait', 'nuclear-reset' ); ?>
                        </p>
                    </div>

                    <!-- Log output -->
                    <div id="nr-log" class="nr-log" hidden></div>
                </div>

            </div><!-- /.nr-layout -->

        </div><!-- /.nr-wrap -->
        <?php
    }
}
