<?php
defined( 'ABSPATH' ) || exit;

/**
 * Centralised options store. Every key is stored with prefix plseo_.
 */
class PLSEO_Options {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public static function get_defaults(): array {
        return [
            // ── General ──────────────────────────────────────────────────────
            'plseo_title_separator'            => '-',
            'plseo_homepage_title'             => '%%sitename%% %%sep%% %%tagline%%',
            'plseo_homepage_description'       => '',
            'plseo_title_format_single'        => '%%title%% %%sep%% %%sitename%%',
            'plseo_title_format_archive'       => '%%title%% %%sep%% %%sitename%%',
            'plseo_title_format_search'        => 'Search: %%searchterm%% %%sep%% %%sitename%%',
            'plseo_title_format_404'           => 'Page not found %%sep%% %%sitename%%',
            'plseo_title_format_author'        => '%%title%% %%sep%% %%sitename%%',
            'plseo_noindex_subpages'           => false,

            // ── Enabled post types / taxonomies ──────────────────────────────
            'plseo_enabled_post_types'         => [ 'post', 'page' ],
            'plseo_enabled_taxonomies'         => [ 'category', 'post_tag' ],
            'plseo_cpt_archive_seo'            => [],

            // ── Sitemap ──────────────────────────────────────────────────────
            'plseo_sitemap_enabled'            => true,
            'plseo_sitemap_include_posts'      => true,
            'plseo_sitemap_include_pages'      => true,
            'plseo_sitemap_include_cpt'        => [],
            'plseo_sitemap_include_taxonomies' => true,
            'plseo_sitemap_include_authors'    => false,
            'plseo_sitemap_images'             => true,
            'plseo_sitemap_news'               => false,
            'plseo_sitemap_news_post_types'    => [ 'post' ],
            'plseo_sitemap_news_publication'   => '',
            'plseo_sitemap_videos'             => false,
            'plseo_sitemap_video_post_types'   => [ 'post', 'page', 'video' ],
            'plseo_sitemap_per_language'       => false,
            'plseo_sitemap_exclude_ids'        => [],
            'plseo_sitemap_posts_per_type'     => 1000,
            'plseo_sitemap_ping_google'        => true,
            'plseo_sitemap_ping_bing'          => true,
            'plseo_sitemap_indexnow'           => false,
            'plseo_sitemap_hreflang'           => true,
            'plseo_sitemap_x_default'          => true,

            // ── Open Graph ───────────────────────────────────────────────────
            'plseo_og_enabled'                 => true,
            'plseo_og_default_image'           => '',
            'plseo_og_type_map'                => [],
            'plseo_fb_app_id'                  => '',
            'plseo_fb_admins'                  => '',
            'plseo_social_facebook'            => '',
            'plseo_social_twitter'             => '',
            'plseo_social_linkedin'            => '',
            'plseo_social_youtube'             => '',
            'plseo_social_instagram'           => '',
            'plseo_social_pinterest'           => '',
            'plseo_social_tiktok'              => '',

            // ── Twitter / X Card ─────────────────────────────────────────────
            'plseo_twitter_enabled'            => true,
            'plseo_twitter_card_type'          => 'summary_large_image',
            'plseo_twitter_site'               => '',
            'plseo_twitter_creator'            => '',

            // ── Structured Data (JSON-LD) ────────────────────────────────────
            'plseo_schema_enabled'             => true,
            'plseo_schema_org_type'            => 'Organization',
            'plseo_schema_org_name'            => '',
            'plseo_schema_org_url'             => '',
            'plseo_schema_org_logo'            => '',
            'plseo_schema_breadcrumb'          => true,
            'plseo_schema_article'             => true,
            'plseo_schema_article_type'        => 'BlogPosting',
            'plseo_schema_search_action'       => true,
            'plseo_schema_speakable'           => true,
            'plseo_schema_navigation'          => true,
            'plseo_schema_contact_type'        => '',
            'plseo_schema_local_business'      => false,
            'plseo_schema_lb_type'             => 'LocalBusiness',
            'plseo_schema_lb_street'           => '',
            'plseo_schema_lb_city'             => '',
            'plseo_schema_lb_region'           => '',
            'plseo_schema_lb_postal'           => '',
            'plseo_schema_lb_country'          => '',
            'plseo_schema_lb_phone'            => '',
            'plseo_schema_lb_hours'            => '',
            'plseo_schema_lb_lat'              => '',
            'plseo_schema_lb_lng'              => '',
            'plseo_schema_lb_price_range'      => '',

            // ── Canonical ────────────────────────────────────────────────────
            'plseo_canonical_enabled'          => true,
            'plseo_canonical_force_https'      => true,
            'plseo_canonical_trailing_slash'   => true,
            'plseo_canonical_strip_params'     => true,

            // ── Robots / Indexing ────────────────────────────────────────────
            'plseo_noindex_search'             => true,
            'plseo_noindex_404'                => true,
            'plseo_noindex_author'             => false,
            'plseo_noindex_date'               => true,
            'plseo_noindex_attachment'         => true,
            'plseo_noindex_empty_taxonomy'     => true,
            'plseo_robots_txt_additions'       => '',
            'plseo_robots_crawl_delay'         => '',
            'plseo_robots_block_ai_bots'       => false,
            'plseo_robots_ai_bots_custom'      => '',
            'plseo_robots_max_snippet'         => '-1',
            'plseo_robots_max_image_preview'   => 'large',
            'plseo_robots_max_video_preview'   => '-1',
            'plseo_robots_googlebot'           => '',
            'plseo_robots_bingbot'             => '',

            // ── hreflang ─────────────────────────────────────────────────────
            'plseo_hreflang_x_default'         => 'default_language',

            // ── Redirects ────────────────────────────────────────────────────
            'plseo_redirect_404_home'          => false,
            'plseo_redirect_old_slugs'         => true,

            // ── Performance / UX ─────────────────────────────────────────────
            'plseo_preconnect_domains'         => '',
            'plseo_dns_prefetch'               => '',
            'plseo_remove_rsd_link'            => true,
            'plseo_remove_shortlink'           => true,
            'plseo_remove_oembed'              => false,

            // ── Site Verification ────────────────────────────────────────────
            'plseo_google_site_verification'   => '',
            'plseo_bing_site_verification'     => '',
            'plseo_yandex_site_verification'   => '',
            'plseo_pinterest_site_verification' => '',

            // ── IndexNow ─────────────────────────────────────────────────────
            'plseo_indexnow_api_key'           => '',
        ];
    }

    public function register_settings(): void {
        foreach ( self::get_defaults() as $key => $default ) {
            $type = match ( true ) {
                is_bool( $default )  => 'boolean',
                is_int( $default )   => 'integer',
                is_array( $default ) => 'array',
                default              => 'string',
            };
            register_setting( 'plseo_options_group', $key, [
                'type'              => $type,
                'sanitize_callback' => function ( mixed $value ) use ( $key, $default ): mixed {
                    return $this->sanitize_option_value( $key, $value, $default );
                },
                'default'           => $default,
            ] );
        }
    }

    private function sanitize_option_value( string $key, mixed $value, mixed $default ): mixed {
        $url_fields = [
            'plseo_og_default_image',
            'plseo_schema_org_url',
            'plseo_schema_org_logo',
            'plseo_social_facebook',
            'plseo_social_twitter',
            'plseo_social_linkedin',
            'plseo_social_youtube',
            'plseo_social_instagram',
            'plseo_social_pinterest',
            'plseo_social_tiktok',
        ];

        if ( in_array( $key, $url_fields, true ) ) {
            return esc_url_raw( (string) $value );
        }

        if ( is_bool( $default ) ) {
            return (bool) $value;
        }

        if ( is_int( $default ) ) {
            return absint( $value );
        }

        if ( 'plseo_cpt_archive_seo' === $key ) {
            return is_array( $value ) ? $this->sanitize_recursive_text_array( $value ) : [];
        }

        if ( is_array( $value ) ) {
            return array_map( 'sanitize_text_field', $value );
        }

        return sanitize_text_field( (string) $value );
    }

    private function sanitize_recursive_text_array( array $value ): array {
        $sanitized = [];

        foreach ( $value as $array_key => $array_value ) {
            $clean_key = sanitize_key( (string) $array_key );

            if ( is_array( $array_value ) ) {
                $sanitized[ $clean_key ] = $this->sanitize_recursive_text_array( $array_value );
                continue;
            }

            $sanitized[ $clean_key ] = sanitize_text_field( (string) $array_value );
        }

        return $sanitized;
    }
}
