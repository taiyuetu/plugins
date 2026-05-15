<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enhanced meta tag output:
 * - Title tag with proper template hierarchy
 * - Meta description with smart auto-generation
 * - Advanced robots directives (max-image-preview, max-snippet, max-video-preview)
 * - Content language meta
 * - Resource hints (preconnect, dns-prefetch)
 * - Google site verification
 * - Bing site verification
 */
class PLSEO_Meta {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_filter( 'pre_get_document_title', [ $this, 'get_title' ], 20 );
        add_action( 'wp_head', [ $this, 'output_meta_tags' ], 2 );
        remove_action( 'wp_head', 'wp_generator' );
    }

    /* ──────────────────────────────────────────────────── */
    /*  Title Tag                                           */
    /* ──────────────────────────────────────────────────── */

    public function get_title( string $title ): string {
        if ( is_front_page() || is_home() ) {
            $custom = PLSEO_Helpers::get_option( 'homepage_title', '%%sitename%% %%sep%% %%tagline%%' );
            return PLSEO_Helpers::replace_tokens( $custom, [ 'title' => get_bloginfo( 'name' ) ] );
        }

        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            if ( $post_id && PLSEO_Helpers::is_seo_disabled( $post_id ) ) {
                return $title;
            }

            $custom = PLSEO_Helpers::get_post_seo_meta( $post_id, 'meta_title' );
            $label  = get_the_title( $post_id );

            if ( $custom ) {
                return PLSEO_Helpers::replace_tokens( $custom, [ 'title' => $label ] );
            }

            return PLSEO_Helpers::replace_tokens(
                PLSEO_Helpers::get_option( 'title_format_single', '%%title%% %%sep%% %%sitename%%' ),
                [ 'title' => $label ]
            );
        }

        if ( is_tax() || is_category() || is_tag() ) {
            $term = get_queried_object();
            if ( $term instanceof WP_Term ) {
                $custom = PLSEO_Helpers::get_term_seo_meta( $term->term_id, 'meta_title' );
                if ( $custom ) {
                    return PLSEO_Helpers::replace_tokens( $custom, [ 'title' => $term->name ] );
                }

                return PLSEO_Helpers::replace_tokens(
                    PLSEO_Helpers::get_option( 'title_format_archive', '%%title%% %%sep%% %%sitename%%' ),
                    [ 'title' => $term->name ]
                );
            }
        }

        if ( is_search() ) {
            return PLSEO_Helpers::replace_tokens(
                PLSEO_Helpers::get_option( 'title_format_search', 'Search: %%searchterm%% %%sep%% %%sitename%%' ),
                [ 'searchterm' => get_search_query() ]
            );
        }

        if ( is_404() ) {
            return PLSEO_Helpers::replace_tokens(
                PLSEO_Helpers::get_option( 'title_format_404', 'Page not found %%sep%% %%sitename%%' )
            );
        }

        if ( is_archive() ) {
            if ( is_post_type_archive() ) {
                $post_type = get_query_var( 'post_type' );
                if ( is_array( $post_type ) ) {
                    $post_type = reset( $post_type );
                }

                $archive_title = is_string( $post_type ) ? PLSEO_Helpers::get_cpt_archive_seo_meta( $post_type, 'title' ) : '';
                if ( $archive_title ) {
                    return PLSEO_Helpers::replace_tokens( $archive_title, [ 'title' => post_type_archive_title( '', false ) ] );
                }
            }

            if ( is_author() ) {
                $author = get_queried_object();
                if ( $author instanceof \WP_User ) {
                    return PLSEO_Helpers::replace_tokens(
                        PLSEO_Helpers::get_option( 'title_format_author', '%%title%% %%sep%% %%sitename%%' ),
                        [ 'title' => $author->display_name ]
                    );
                }
            }

            return PLSEO_Helpers::replace_tokens(
                PLSEO_Helpers::get_option( 'title_format_archive', '%%title%% %%sep%% %%sitename%%' ),
                [ 'title' => wp_strip_all_tags( get_the_archive_title() ) ]
            );
        }

        return $title;
    }

    /* ──────────────────────────────────────────────────── */
    /*  Meta Description                                    */
    /* ──────────────────────────────────────────────────── */

    public function get_description(): string {
        if ( is_front_page() || is_home() ) {
            $custom = PLSEO_Helpers::get_option( 'homepage_description', '' );
            return $custom ? PLSEO_Helpers::replace_tokens( $custom ) : PLSEO_Helpers::clean_text( get_bloginfo( 'description' ) );
        }

        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            if ( $post_id && PLSEO_Helpers::is_seo_disabled( $post_id ) ) {
                return '';
            }

            $custom = PLSEO_Helpers::get_post_seo_meta( $post_id, 'meta_description' );
            if ( $custom ) {
                return PLSEO_Helpers::replace_tokens( $custom );
            }

            $post = get_post( $post_id );
            if ( $post instanceof WP_Post ) {
                if ( $post->post_excerpt ) {
                    return PLSEO_Helpers::truncate( PLSEO_Helpers::clean_text( $post->post_excerpt ), 160 );
                }

                // Smart auto-generation: use the first meaningful paragraph
                $content = PLSEO_Helpers::clean_text( $post->post_content );
                if ( $content ) {
                    $sentences = preg_split( '/(?<=[.!?])\s+/', $content, 5, PREG_SPLIT_NO_EMPTY );
                    $desc      = '';
                    foreach ( $sentences as $sentence ) {
                        if ( mb_strlen( $desc . ' ' . $sentence ) > 160 ) {
                            break;
                        }
                        $desc = trim( $desc . ' ' . $sentence );
                    }
                    return $desc ?: PLSEO_Helpers::truncate( $content, 160 );
                }
            }
        }

        if ( is_tax() || is_category() || is_tag() ) {
            $term = get_queried_object();
            if ( $term instanceof WP_Term ) {
                $custom = PLSEO_Helpers::get_term_seo_meta( $term->term_id, 'meta_description' );
                if ( $custom ) {
                    return PLSEO_Helpers::replace_tokens( $custom );
                }

                return PLSEO_Helpers::truncate( PLSEO_Helpers::clean_text( $term->description ), 160 );
            }
        }

        if ( is_post_type_archive() ) {
            $post_type = get_query_var( 'post_type' );
            if ( is_array( $post_type ) ) {
                $post_type = reset( $post_type );
            }

            $archive_description = is_string( $post_type ) ? PLSEO_Helpers::get_cpt_archive_seo_meta( $post_type, 'description' ) : '';
            if ( $archive_description ) {
                return PLSEO_Helpers::replace_tokens( $archive_description );
            }
        }

        if ( is_author() ) {
            $author = get_queried_object();
            if ( $author instanceof \WP_User ) {
                $bio = get_the_author_meta( 'description', $author->ID );
                return $bio ? PLSEO_Helpers::truncate( PLSEO_Helpers::clean_text( $bio ), 160 ) : '';
            }
        }

        return '';
    }

    /* ──────────────────────────────────────────────────── */
    /*  Robots Meta Tag                                     */
    /* ──────────────────────────────────────────────────── */

    public function get_robots(): string {
        $directives = [];

        // Page-level noindex rules
        if ( is_search() && PLSEO_Helpers::get_option( 'noindex_search', true ) ) {
            return 'noindex, nofollow';
        }
        if ( is_404() && PLSEO_Helpers::get_option( 'noindex_404', true ) ) {
            return 'noindex, nofollow';
        }
        if ( is_author() && PLSEO_Helpers::get_option( 'noindex_author', false ) ) {
            return 'noindex, follow';
        }
        if ( is_date() && PLSEO_Helpers::get_option( 'noindex_date', true ) ) {
            return 'noindex, follow';
        }
        if ( is_attachment() && PLSEO_Helpers::get_option( 'noindex_attachment', true ) ) {
            return 'noindex, follow';
        }
        if ( ( is_tax() || is_category() || is_tag() ) && PLSEO_Helpers::get_option( 'noindex_empty_taxonomy', true ) ) {
            $term = get_queried_object();
            if ( $term instanceof WP_Term && 0 === (int) $term->count ) {
                return 'noindex, follow';
            }
        }

        // Paginated archive pages
        if ( is_paged() && PLSEO_Helpers::get_option( 'noindex_subpages', false ) ) {
            $directives[] = 'noindex';
            $directives[] = 'follow';
        }

        // Per-post overrides
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            if ( $post_id && PLSEO_Helpers::is_seo_disabled( $post_id ) ) {
                return 'index, follow';
            }

            if ( get_post_meta( $post_id, '_plseo_noindex', true ) ) {
                $directives[] = 'noindex';
            }
            if ( get_post_meta( $post_id, '_plseo_nofollow', true ) ) {
                $directives[] = 'nofollow';
            }
        }

        // If we have specific noindex/nofollow, complete the pair
        if ( ! empty( $directives ) ) {
            if ( ! in_array( 'noindex', $directives, true ) && ! in_array( 'index', $directives, true ) ) {
                array_unshift( $directives, 'index' );
            }
            if ( ! in_array( 'nofollow', $directives, true ) && ! in_array( 'follow', $directives, true ) ) {
                $directives[] = 'follow';
            }
        }

        // Default: index, follow + advanced directives for richer SERP
        if ( empty( $directives ) ) {
            $directives[] = 'index';
            $directives[] = 'follow';
        }

        // Advanced SERP directives
        if ( ! in_array( 'noindex', $directives, true ) ) {
            $max_snippet = PLSEO_Helpers::get_option( 'robots_max_snippet', '-1' );
            if ( '' !== $max_snippet ) {
                $directives[] = 'max-snippet:' . (int) $max_snippet;
            }

            $max_image = PLSEO_Helpers::get_option( 'robots_max_image_preview', 'large' );
            if ( $max_image ) {
                $directives[] = 'max-image-preview:' . $max_image;
            }

            $max_video = PLSEO_Helpers::get_option( 'robots_max_video_preview', '-1' );
            if ( '' !== $max_video ) {
                $directives[] = 'max-video-preview:' . (int) $max_video;
            }
        }

        return implode( ', ', $directives );
    }

    /* ──────────────────────────────────────────────────── */
    /*  Output                                              */
    /* ──────────────────────────────────────────────────── */

    public function output_meta_tags(): void {
        $post_id = is_singular() ? get_queried_object_id() : 0;
        if ( $post_id && PLSEO_Helpers::is_seo_disabled( $post_id ) ) {
            return;
        }

        $description = $this->get_description();
        if ( $description ) {
            printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
        }

        printf( '<meta name="robots" content="%s" />' . "\n", esc_attr( $this->get_robots() ) );

        // Googlebot-specific directives (useful for different crawl budgets)
        $googlebot = PLSEO_Helpers::get_option( 'robots_googlebot', '' );
        if ( $googlebot ) {
            printf( '<meta name="googlebot" content="%s" />' . "\n", esc_attr( $googlebot ) );
        }

        // Bingbot directives
        $bingbot = PLSEO_Helpers::get_option( 'robots_bingbot', '' );
        if ( $bingbot ) {
            printf( '<meta name="bingbot" content="%s" />' . "\n", esc_attr( $bingbot ) );
        }

        $lang = PLSEO_Helpers::current_lang();
        if ( $lang ) {
            printf( '<meta http-equiv="content-language" content="%s" />' . "\n", esc_attr( $lang ) );
        }

        // Site verification tags
        $google_verify = PLSEO_Helpers::get_option( 'google_site_verification', '' );
        if ( $google_verify ) {
            printf( '<meta name="google-site-verification" content="%s" />' . "\n", esc_attr( $google_verify ) );
        }

        $bing_verify = PLSEO_Helpers::get_option( 'bing_site_verification', '' );
        if ( $bing_verify ) {
            printf( '<meta name="msvalidate.01" content="%s" />' . "\n", esc_attr( $bing_verify ) );
        }

        $yandex_verify = PLSEO_Helpers::get_option( 'yandex_site_verification', '' );
        if ( $yandex_verify ) {
            printf( '<meta name="yandex-verification" content="%s" />' . "\n", esc_attr( $yandex_verify ) );
        }

        $pinterest_verify = PLSEO_Helpers::get_option( 'pinterest_site_verification', '' );
        if ( $pinterest_verify ) {
            printf( '<meta name="p:domain_verify" content="%s" />' . "\n", esc_attr( $pinterest_verify ) );
        }

        $this->output_resource_hints();
    }

    private function output_resource_hints(): void {
        foreach ( explode( "\n", (string) PLSEO_Helpers::get_option( 'preconnect_domains', '' ) ) as $domain ) {
            $domain = trim( $domain );
            if ( $domain ) {
                printf( '<link rel="preconnect" href="%s" crossorigin />' . "\n", esc_url( $domain ) );
            }
        }

        foreach ( explode( "\n", (string) PLSEO_Helpers::get_option( 'dns_prefetch', '' ) ) as $domain ) {
            $domain = trim( $domain );
            if ( $domain ) {
                printf( '<link rel="dns-prefetch" href="%s" />' . "\n", esc_url( $domain ) );
            }
        }
    }
}
