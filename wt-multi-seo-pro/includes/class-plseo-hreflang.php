<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enhanced hreflang output:
 * - Singular posts/pages
 * - Taxonomy archives
 * - Post type archives
 * - Front page / home
 * - Author pages
 * - Self-referencing hreflang (Google requirement)
 * - x-default with configurable strategy
 */
class PLSEO_Hreflang {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_head', [ $this, 'output_hreflang' ], 5 );
    }

    public function output_hreflang(): void {
        if ( is_search() || is_404() ) {
            return;
        }

        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            if ( $post_id && PLSEO_Helpers::is_seo_disabled( $post_id ) ) {
                return;
            }
            if ( $post_id && get_post_meta( $post_id, '_plseo_noindex', true ) ) {
                return;
            }
        }

        $links = $this->get_hreflang_links();
        if ( empty( $links ) ) {
            return;
        }

        echo "\n<!-- hreflang tags by WT Multi SEO Pro -->\n";

        foreach ( $links as $hreflang => $url ) {
            printf(
                '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
                esc_attr( $hreflang ),
                esc_url( $url )
            );
        }

        $x_default = $this->get_x_default( $links );
        if ( $x_default ) {
            printf(
                '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n",
                esc_url( $x_default )
            );
        }
    }

    private function get_hreflang_links(): array {
        $languages = PLSEO_Helpers::get_languages();
        if ( empty( $languages ) ) {
            return [];
        }

        $links = [];

        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            foreach ( PLSEO_Helpers::get_post_translations( $post_id ) as $slug => $pid ) {
                if ( empty( $languages[ $slug ] ) || ! $pid ) {
                    continue;
                }
                $pid = (int) $pid;

                // Skip noindex translations
                if ( get_post_meta( $pid, '_plseo_noindex', true ) ) {
                    continue;
                }

                // Skip non-published translations
                if ( 'publish' !== get_post_status( $pid ) ) {
                    continue;
                }

                $url = get_permalink( $pid );
                if ( $url ) {
                    $links[ PLSEO_Helpers::locale_to_hreflang( $languages[ $slug ]['locale'] ) ] = $url;
                }
            }
            return $links;
        }

        if ( is_tax() || is_category() || is_tag() ) {
            $term = get_queried_object();
            if ( ! $term instanceof WP_Term ) {
                return [];
            }

            foreach ( PLSEO_Helpers::get_term_translations( $term->term_id ) as $slug => $term_id ) {
                if ( empty( $languages[ $slug ] ) || ! $term_id ) {
                    continue;
                }

                $url = get_term_link( (int) $term_id, $term->taxonomy );
                if ( ! is_wp_error( $url ) ) {
                    $links[ PLSEO_Helpers::locale_to_hreflang( $languages[ $slug ]['locale'] ) ] = $url;
                }
            }
            return $links;
        }

        if ( is_post_type_archive() ) {
            $post_type = get_query_var( 'post_type' );
            if ( is_array( $post_type ) ) {
                $post_type = reset( $post_type );
            }

            if ( is_string( $post_type ) ) {
                foreach ( $languages as $slug => $language ) {
                    $url = function_exists( 'pll_home_url' )
                        ? pll_home_url( $slug )
                        : ( $language['url'] ?: home_url( '/' . $slug . '/' ) );

                    $archive_url = get_post_type_archive_link( $post_type );
                    if ( $archive_url && function_exists( 'pll_home_url' ) ) {
                        $home = pll_home_url( $slug );
                        if ( $home ) {
                            $default_home = trailingslashit( home_url() );
                            $archive_path = str_replace( $default_home, '', $archive_url );
                            $url = trailingslashit( $home ) . ltrim( $archive_path, '/' );
                        }
                    }

                    if ( $url ) {
                        $links[ PLSEO_Helpers::locale_to_hreflang( $language['locale'] ) ] = $url;
                    }
                }
            }
            return $links;
        }

        if ( is_front_page() || is_home() ) {
            foreach ( $languages as $slug => $language ) {
                $url = function_exists( 'pll_home_url' )
                    ? pll_home_url( $slug )
                    : ( $language['url'] ?: home_url( '/' . $slug . '/' ) );
                if ( $url ) {
                    $links[ PLSEO_Helpers::locale_to_hreflang( $language['locale'] ) ] = $url;
                }
            }
            return $links;
        }

        // Author archives
        if ( is_author() ) {
            $author = get_queried_object();
            if ( $author instanceof \WP_User ) {
                foreach ( $languages as $slug => $language ) {
                    $url = get_author_posts_url( $author->ID );
                    if ( $url && function_exists( 'pll_home_url' ) ) {
                        $home = pll_home_url( $slug );
                        if ( $home ) {
                            $default_home = trailingslashit( home_url() );
                            $author_path  = str_replace( $default_home, '', $url );
                            $url = trailingslashit( $home ) . ltrim( $author_path, '/' );
                        }
                    }
                    if ( $url ) {
                        $links[ PLSEO_Helpers::locale_to_hreflang( $language['locale'] ) ] = $url;
                    }
                }
            }
        }

        return $links;
    }

    private function get_x_default( array $links ): string {
        $strategy         = PLSEO_Helpers::get_option( 'hreflang_x_default', 'default_language' );
        $default_language = function_exists( 'pll_default_language' ) ? pll_default_language() : '';
        $languages        = PLSEO_Helpers::get_languages();

        if ( 'none' === $strategy ) {
            return '';
        }

        if ( $default_language && isset( $languages[ $default_language ]['locale'] ) ) {
            $hreflang = PLSEO_Helpers::locale_to_hreflang( $languages[ $default_language ]['locale'] );
            if ( isset( $links[ $hreflang ] ) ) {
                return $links[ $hreflang ];
            }
        }

        return (string) reset( $links );
    }
}
