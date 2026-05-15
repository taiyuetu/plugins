<?php
/**
 * Shared header for all settings pages.
 */
defined( 'ABSPATH' ) || exit;

$tabs = [
    'polylang-seo'      => __( 'General', 'polylang-seo' ),
    'plseo-sitemap'     => __( 'Sitemap', 'polylang-seo' ),
    'plseo-social'      => __( 'Social / OG', 'polylang-seo' ),
    'plseo-schema'      => __( 'Structured Data', 'polylang-seo' ),
    'plseo-robots'      => __( 'Robots & Index', 'polylang-seo' ),
    'plseo-performance' => __( 'Performance', 'polylang-seo' ),
    'plseo-tools'       => __( 'Tools', 'polylang-seo' ),
];

$current_page = sanitize_key( $_GET['page'] ?? 'polylang-seo' );
?>
<div class="wrap plseo-wrap">
    <h1 class="plseo-headline">
        <span class="dashicons dashicons-search" style="font-size:28px;width:28px;height:28px;"></span>
        <?php esc_html_e( 'WT Multi SEO Pro', 'polylang-seo' ); ?>
        <span style="font-size:12px;color:#888;font-weight:normal;margin-left:4px;">v<?php echo esc_html( PLSEO_VERSION ); ?></span>
    </h1>

    <nav class="nav-tab-wrapper plseo-tabs">
        <?php foreach ( $tabs as $slug => $label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"
               class="nav-tab <?php echo ( $current_page === $slug ) ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>
