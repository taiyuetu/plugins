<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enhanced Open Graph output:
 * - og:image with width, height, type metadata
 * - og:video for video posts
 * - article:section, article:tag for blog posts
 * - og:locale:alternate for multilingual sites
 * - og:image:alt for accessibility
 */
class PLSEO_OpenGraph {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        if ( ! PLSEO_Helpers::get_option( 'og_enabled', true ) ) {
            return;
        }

        add_action( 'wp_head', [ $this, 'output_og' ], 6 );
    }

    public function output_og(): void {
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            if ( $post_id && PLSEO_Helpers::is_seo_disabled( $post_id ) ) {
                return;
            }
        }

        $tags = $this->collect_tags();

        foreach ( $tags as $property => $content ) {
            if ( is_array( $content ) ) {
                foreach ( $content as $value ) {
                    if ( '' !== (string) $value ) {
                        printf(
                            '<meta property="%s" content="%s" />' . "\n",
                            esc_attr( $property ),
                            esc_attr( $value )
                        );
                    }
                }
                continue;
            }

            if ( '' === (string) $content ) {
                continue;
            }

            printf(
                '<meta property="%s" content="%s" />' . "\n",
                esc_attr( $property ),
                esc_attr( $content )
            );
        }
    }

    private function collect_tags(): array {
        $meta      = PLSEO_Meta::get_instance();
        $canonical = PLSEO_Canonical::get_instance();
        $languages = PLSEO_Helpers::get_languages();
        $lang      = PLSEO_Helpers::current_lang();
        $locale    = get_locale();

        if ( $lang && isset( $languages[ $lang ]['locale'] ) ) {
            $locale = $languages[ $lang ]['locale'];
        }

        $og_locale = str_replace( '-', '_', $locale );
        if ( ! str_contains( $og_locale, '_' ) ) {
            $og_locale = $og_locale . '_' . strtoupper( $og_locale );
        }

        $tags = [
            'og:site_name'   => get_bloginfo( 'name' ),
            'og:locale'      => $og_locale,
            'og:type'        => $this->get_og_type(),
            'og:title'       => wp_get_document_title(),
            'og:description' => $meta->get_description(),
            'og:url'         => $canonical->get_canonical_url(),
        ];

        // Alternate locales for multilingual
        if ( count( $languages ) > 1 ) {
            $alt_locales = [];
            foreach ( $languages as $slug => $language ) {
                if ( $slug === $lang ) {
                    continue;
                }
                $alt_locale = str_replace( '-', '_', $language['locale'] );
                if ( ! str_contains( $alt_locale, '_' ) ) {
                    $alt_locale = $alt_locale . '_' . strtoupper( $alt_locale );
                }
                $alt_locales[] = $alt_locale;
            }
            if ( ! empty( $alt_locales ) ) {
                $tags['og:locale:alternate'] = $alt_locales;
            }
        }

        // Image with full metadata
        $image_data = $this->get_image_data();
        if ( $image_data['url'] ) {
            $tags['og:image']     = $image_data['url'];
            if ( $image_data['width'] ) {
                $tags['og:image:width']  = $image_data['width'];
            }
            if ( $image_data['height'] ) {
                $tags['og:image:height'] = $image_data['height'];
            }
            if ( $image_data['type'] ) {
                $tags['og:image:type'] = $image_data['type'];
            }
            if ( $image_data['alt'] ) {
                $tags['og:image:alt'] = $image_data['alt'];
            }
        }

        if ( is_singular() ) {
            $post_id  = get_queried_object_id();
            $og_title = PLSEO_Helpers::get_post_seo_meta( $post_id, 'og_title', false );
            $og_desc  = PLSEO_Helpers::get_post_seo_meta( $post_id, 'og_description', false );
            $og_img   = PLSEO_Helpers::get_post_seo_meta( $post_id, 'og_image', false );

            if ( $og_title ) {
                $tags['og:title'] = $og_title;
            }
            if ( $og_desc ) {
                $tags['og:description'] = $og_desc;
            }
            if ( $og_img ) {
                $tags['og:image'] = $og_img;
            }

            if ( is_singular( 'post' ) ) {
                $tags['article:published_time'] = get_post_time( 'c', true, $post_id );
                $tags['article:modified_time']  = get_post_modified_time( 'c', true, $post_id );

                $author_name = get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );
                if ( $author_name ) {
                    $tags['article:author'] = $author_name;
                }

                // article:section (primary category)
                $categories = get_the_category( $post_id );
                if ( $categories && ! is_wp_error( $categories ) ) {
                    $tags['article:section'] = $categories[0]->name;
                }

                // article:tag
                $post_tags = get_the_tags( $post_id );
                if ( $post_tags && ! is_wp_error( $post_tags ) ) {
                    $tags['article:tag'] = array_map( fn( $tag ) => $tag->name, array_slice( $post_tags, 0, 5 ) );
                }
            }

            // og:video for video posts
            $video_url = get_post_meta( $post_id, '_video_url', true );
            $embed_url = get_post_meta( $post_id, '_video_embed_url', true );
            if ( $embed_url ) {
                $tags['og:video']      = $embed_url;
                $tags['og:video:type'] = 'text/html';
            } elseif ( $video_url ) {
                $tags['og:video']      = $video_url;
                $tags['og:video:type'] = $this->get_video_mime_type( $video_url );
            }

        } elseif ( is_post_type_archive() ) {
            $post_type = get_query_var( 'post_type' );
            if ( is_array( $post_type ) ) {
                $post_type = reset( $post_type );
            }

            if ( is_string( $post_type ) ) {
                $inherit_value    = PLSEO_Helpers::get_cpt_archive_seo_meta( $post_type, 'inherit_defaults' );
                $inherit_defaults = '' === $inherit_value || '1' === $inherit_value;
                $archive_title    = PLSEO_Helpers::get_cpt_archive_seo_meta( $post_type, 'title' );
                $archive_desc     = PLSEO_Helpers::get_cpt_archive_seo_meta( $post_type, 'description' );
                $archive_og_title = PLSEO_Helpers::get_cpt_archive_seo_meta( $post_type, 'og_title' );
                $archive_og_desc  = PLSEO_Helpers::get_cpt_archive_seo_meta( $post_type, 'og_description' );
                $archive_og_image = PLSEO_Helpers::get_cpt_archive_seo_meta( $post_type, 'og_image' );

                if ( $archive_og_title ) {
                    $tags['og:title'] = PLSEO_Helpers::replace_tokens( $archive_og_title, [ 'title' => post_type_archive_title( '', false ) ] );
                } elseif ( $inherit_defaults && $archive_title ) {
                    $tags['og:title'] = PLSEO_Helpers::replace_tokens( $archive_title, [ 'title' => post_type_archive_title( '', false ) ] );
                }
                if ( $archive_og_desc ) {
                    $tags['og:description'] = PLSEO_Helpers::replace_tokens( $archive_og_desc );
                } elseif ( $inherit_defaults && $archive_desc ) {
                    $tags['og:description'] = PLSEO_Helpers::replace_tokens( $archive_desc );
                }
                if ( $archive_og_image ) {
                    $tags['og:image'] = $archive_og_image;
                }
            }
        }

        $fb_app_id = PLSEO_Helpers::get_option( 'fb_app_id', '' );
        if ( $fb_app_id ) {
            $tags['fb:app_id'] = $fb_app_id;
        }

        $fb_admins = PLSEO_Helpers::get_option( 'fb_admins', '' );
        if ( $fb_admins ) {
            $tags['fb:admins'] = $fb_admins;
        }

        return $tags;
    }

    private function get_og_type(): string {
        if ( is_singular( 'post' ) ) {
            return 'article';
        }
        if ( is_singular( 'product' ) ) {
            return 'product';
        }
        if ( is_author() ) {
            return 'profile';
        }
        return 'website';
    }

    /**
     * Get image URL with dimensions and MIME type.
     */
    private function get_image_data(): array {
        $default = [ 'url' => '', 'width' => '', 'height' => '', 'type' => '', 'alt' => '' ];

        if ( is_post_type_archive() ) {
            $post_type = get_query_var( 'post_type' );
            if ( is_array( $post_type ) ) {
                $post_type = reset( $post_type );
            }
            if ( is_string( $post_type ) ) {
                $archive_og_image = PLSEO_Helpers::get_cpt_archive_seo_meta( $post_type, 'og_image' );
                if ( $archive_og_image ) {
                    return array_merge( $default, [ 'url' => $archive_og_image ] );
                }
            }
        }

        if ( is_singular() ) {
            $post_id = get_queried_object_id();

            if ( has_post_thumbnail( $post_id ) ) {
                $att_id = get_post_thumbnail_id( $post_id );
                $image  = wp_get_attachment_image_src( $att_id, 'full' );
                if ( $image ) {
                    $alt      = get_post_meta( $att_id, '_wp_attachment_image_alt', true );
                    $mime     = get_post_mime_type( $att_id );
                    return [
                        'url'    => $image[0],
                        'width'  => $image[1],
                        'height' => $image[2],
                        'type'   => $mime ?: '',
                        'alt'    => $alt ?: get_the_title( $post_id ),
                    ];
                }
            }
        }

        $fallback = (string) PLSEO_Helpers::get_option( 'og_default_image', '' );
        if ( $fallback ) {
            $att_id = attachment_url_to_postid( $fallback );
            if ( $att_id ) {
                $image = wp_get_attachment_image_src( $att_id, 'full' );
                if ( $image ) {
                    return [
                        'url'    => $image[0],
                        'width'  => $image[1],
                        'height' => $image[2],
                        'type'   => get_post_mime_type( $att_id ) ?: '',
                        'alt'    => get_bloginfo( 'name' ),
                    ];
                }
            }
            return array_merge( $default, [ 'url' => $fallback ] );
        }

        return $default;
    }

    private function get_video_mime_type( string $url ): string {
        $ext = strtolower( pathinfo( wp_parse_url( $url, PHP_URL_PATH ) ?: '', PATHINFO_EXTENSION ) );
        return match ( $ext ) {
            'mp4'  => 'video/mp4',
            'webm' => 'video/webm',
            'ogg'  => 'video/ogg',
            default => 'text/html',
        };
    }
}
