<?php
defined( 'ABSPATH' ) || exit;

/**
 * Professional Multilingual XML Sitemap Generator
 *
 * Generates Google-compliant sitemaps with full Polylang support:
 *
 * Sitemap index:  /sitemap.xml           (with XSL stylesheet)
 * Sub-sitemaps:
 *   /sitemap-pages.xml
 *   /sitemap-pages-2.xml                 (paginated for large sites)
 *   /sitemap-posts.xml
 *   /sitemap-{post_type}.xml
 *   /sitemap-taxonomies.xml
 *   /sitemap-news.xml                    (Google News sitemap)
 *   /sitemap-video.xml                   (Google Video sitemap)
 *   /sitemap-{lang}-pages.xml            (per-language sub-sitemaps)
 *   /sitemap-{lang}-posts.xml
 *   /sitemap-authors.xml                 (author archive sitemap)
 *
 * Namespaces: xhtml (hreflang), image, video, news
 */
class PLSEO_Sitemap {

    private static ?self $instance = null;

    private const MAX_URLS_PER_SITEMAP = 2500;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! PLSEO_Helpers::get_option( 'sitemap_enabled', true ) ) {
            return;
        }

        add_action( 'init',              [ $this, 'add_rewrite_rules' ] );
        add_filter( 'query_vars',        [ $this, 'register_query_vars' ] );
        add_action( 'template_redirect', [ $this, 'handle_sitemap_request' ] );
        add_action( 'plseo_regenerate_sitemap', [ $this, 'ping_search_engines' ] );

        add_action( 'publish_post', [ $this, 'schedule_ping' ] );
        add_action( 'publish_page', [ $this, 'schedule_ping' ] );

        add_filter( 'wp_sitemaps_enabled', '__return_false' );
    }

    /**
     * @param string[] $vars Registered public query vars.
     * @return string[]
     */
    public function register_query_vars( array $vars ): array {
        $vars[] = 'plseo_sitemap';
        $vars[] = 'plseo_sitemap_page';
        return $vars;
    }

    public function add_rewrite_rules(): void {
        add_rewrite_rule( '^sitemap\.xml$', 'index.php?plseo_sitemap=index', 'top' );
        add_rewrite_rule( '^sitemap-xsl\.xsl$', 'index.php?plseo_sitemap=xsl', 'top' );
        add_rewrite_rule( '^sitemap-([a-z0-9_-]+?)(?:-(\d+))?\.xml$', 'index.php?plseo_sitemap=$matches[1]&plseo_sitemap_page=$matches[2]', 'top' );
        add_rewrite_tag( '%plseo_sitemap%', '([a-z0-9_-]+)' );
        add_rewrite_tag( '%plseo_sitemap_page%', '(\d*)' );

        $this->maybe_flush();
    }

    public function handle_sitemap_request(): void {
        $type = get_query_var( 'plseo_sitemap' );
        if ( ! $type ) {
            return;
        }

        $page = max( 1, (int) get_query_var( 'plseo_sitemap_page', 1 ) );

        nocache_headers();

        if ( 'xsl' === $type ) {
            $this->render_xsl_stylesheet();
            exit;
        }

        switch ( $type ) {
            case 'index':
                $this->render_index();
                break;
            case 'pages':
                $this->render_post_type_sitemap( 'page', $page );
                break;
            case 'posts':
                $this->render_post_type_sitemap( 'post', $page );
                break;
            case 'taxonomies':
                $this->render_taxonomy_sitemap();
                break;
            case 'news':
                if ( PLSEO_Helpers::get_option( 'sitemap_news', false ) ) {
                    $this->render_news_sitemap();
                } else {
                    status_header( 404 );
                    wp_die( 'Sitemap not found.', 'Not Found', [ 'response' => 404 ] );
                }
                break;
            case 'video':
                if ( PLSEO_Helpers::get_option( 'sitemap_videos', false ) ) {
                    $this->render_video_sitemap();
                } else {
                    status_header( 404 );
                    wp_die( 'Sitemap not found.', 'Not Found', [ 'response' => 404 ] );
                }
                break;
            case 'authors':
                $this->render_author_sitemap();
                break;
            default:
                // Per-language sitemaps: /sitemap-{lang}-{post_type}.xml
                $langs = array_keys( PLSEO_Helpers::get_languages() );
                $matched_lang = false;
                foreach ( $langs as $lang ) {
                    if ( str_starts_with( $type, $lang . '-' ) ) {
                        $pt = substr( $type, strlen( $lang ) + 1 );
                        if ( post_type_exists( $pt ) ) {
                            $this->render_post_type_sitemap( $pt, $page, $lang );
                            $matched_lang = true;
                            break;
                        }
                    }
                }

                if ( ! $matched_lang ) {
                    if ( post_type_exists( $type ) ) {
                        $this->render_post_type_sitemap( $type, $page );
                    } else {
                        status_header( 404 );
                        wp_die( 'Sitemap not found.', 'Not Found', [ 'response' => 404 ] );
                    }
                }
        }
        exit;
    }

    /* ──────────────────────────────────────────────────── */
    /*  XSL Stylesheet (human-readable sitemaps)           */
    /* ──────────────────────────────────────────────────── */

    private function render_xsl_stylesheet(): void {
        header( 'Content-Type: text/xsl; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex' );

        $site_name = esc_html( get_bloginfo( 'name' ) );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        ?>
<xsl:stylesheet version="2.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
    xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
    xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
<xsl:output method="html" encoding="UTF-8" indent="yes"/>
<xsl:template match="/">
<html lang="en">
<head>
<title>XML Sitemap — <?php echo $site_name; ?></title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;margin:0;padding:40px 20px;background:#f0f2f5;color:#1a1a2e}
.wrap{max-width:1100px;margin:0 auto}
h1{font-size:24px;margin:0 0 4px;color:#1a1a2e}
p.info{color:#555;font-size:14px;margin:0 0 24px}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)}
th{background:#1a1a2e;color:#fff;padding:12px 16px;text-align:left;font-size:13px;font-weight:600}
td{padding:10px 16px;font-size:13px;border-bottom:1px solid #eee}
tr:hover td{background:#f8f9ff}
a{color:#2563eb;text-decoration:none}
a:hover{text-decoration:underline}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600}
.badge-lang{background:#e0f2fe;color:#0369a1}
.badge-img{background:#fef3c7;color:#92400e}
.count{color:#888;font-size:12px}
</style>
</head>
<body>
<div class="wrap">
<h1>XML Sitemap</h1>
<p class="info">Generated by <?php echo $site_name; ?> SEO. This sitemap contains <xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap | sitemap:urlset/sitemap:url)"/> entries.</p>

<xsl:choose>
<xsl:when test="sitemap:sitemapindex">
<table>
<tr><th>#</th><th>Sitemap</th><th>Last Modified</th></tr>
<xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
<tr>
<td><xsl:value-of select="position()"/></td>
<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
<td><xsl:value-of select="sitemap:lastmod"/></td>
</tr>
</xsl:for-each>
</table>
</xsl:when>
<xsl:otherwise>
<table>
<tr><th>#</th><th>URL</th><th>Languages</th><th>Images</th><th>Last Modified</th><th>Priority</th></tr>
<xsl:for-each select="sitemap:urlset/sitemap:url">
<tr>
<td><xsl:value-of select="position()"/></td>
<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
<td>
<xsl:for-each select="xhtml:link[@rel='alternate']">
<span class="badge badge-lang"><xsl:value-of select="@hreflang"/></span><xsl:text> </xsl:text>
</xsl:for-each>
</td>
<td>
<xsl:if test="image:image"><span class="badge badge-img"><xsl:value-of select="count(image:image)"/> img</span></xsl:if>
</td>
<td><xsl:value-of select="sitemap:lastmod"/></td>
<td><xsl:value-of select="sitemap:priority"/></td>
</tr>
</xsl:for-each>
</table>
</xsl:otherwise>
</xsl:choose>
</div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
        <?php
    }

    /* ──────────────────────────────────────────────────── */
    /*  Sitemap Index                                       */
    /* ──────────────────────────────────────────────────── */

    private function render_index(): void {
        $this->send_xml_headers();

        $sitemaps   = $this->get_sub_sitemaps();
        $xsl_url    = home_url( 'sitemap-xsl.xsl' );

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        printf( '<?xml-stylesheet type="text/xsl" href="%s"?>' . "\n", esc_url( $xsl_url ) );
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ( $sitemaps as $sitemap ) {
            echo "\t<sitemap>\n";
            printf( "\t\t<loc>%s</loc>\n", esc_url( $sitemap['url'] ) );
            printf( "\t\t<lastmod>%s</lastmod>\n", esc_xml( $sitemap['lastmod'] ) );
            echo "\t</sitemap>\n";
        }

        echo '</sitemapindex>';
    }

    private function get_sub_sitemaps(): array {
        $list  = [];

        // Post types
        if ( PLSEO_Helpers::get_option( 'sitemap_include_pages', true ) ) {
            $this->add_paginated_sitemaps( $list, 'page', 'pages' );
        }
        if ( PLSEO_Helpers::get_option( 'sitemap_include_posts', true ) ) {
            $this->add_paginated_sitemaps( $list, 'post', 'posts' );
        }

        $cpts = (array) PLSEO_Helpers::get_option( 'sitemap_include_cpt', [] );
        foreach ( $cpts as $cpt ) {
            if ( post_type_exists( $cpt ) ) {
                $this->add_paginated_sitemaps( $list, $cpt, $cpt );
            }
        }

        // Per-language sitemaps (skip default language — already covered by the main sitemaps above)
        if ( PLSEO_Helpers::get_option( 'sitemap_per_language', false ) ) {
            $langs        = array_keys( PLSEO_Helpers::get_languages() );
            $default_lang = function_exists( 'pll_default_language' ) ? (string) pll_default_language() : '';
            $post_types   = [];
            if ( PLSEO_Helpers::get_option( 'sitemap_include_posts', true ) ) {
                $post_types[] = 'post';
            }
            if ( PLSEO_Helpers::get_option( 'sitemap_include_pages', true ) ) {
                $post_types[] = 'page';
            }
            $post_types = array_merge( $post_types, $cpts );

            foreach ( $langs as $lang ) {
                if ( $lang === $default_lang ) {
                    continue;
                }
                foreach ( $post_types as $pt ) {
                    $this->add_paginated_sitemaps( $list, $pt, "{$lang}-{$pt}", $lang );
                }
            }
        }

        // Taxonomies
        if ( PLSEO_Helpers::get_option( 'sitemap_include_taxonomies', true ) ) {
            $list[] = [
                'url'     => home_url( 'sitemap-taxonomies.xml' ),
                'lastmod' => gmdate( 'c' ),
            ];
        }

        // Author sitemap
        if ( PLSEO_Helpers::get_option( 'sitemap_include_authors', false ) ) {
            $list[] = [
                'url'     => home_url( 'sitemap-authors.xml' ),
                'lastmod' => gmdate( 'c' ),
            ];
        }

        // News sitemap
        if ( PLSEO_Helpers::get_option( 'sitemap_news', false ) ) {
            $list[] = [
                'url'     => home_url( 'sitemap-news.xml' ),
                'lastmod' => gmdate( 'c' ),
            ];
        }

        // Video sitemap
        if ( PLSEO_Helpers::get_option( 'sitemap_videos', false ) ) {
            $list[] = [
                'url'     => home_url( 'sitemap-video.xml' ),
                'lastmod' => gmdate( 'c' ),
            ];
        }

        return $list;
    }

    private function add_paginated_sitemaps( array &$list, string $post_type, string $slug, string $lang = '' ): void {
        $count     = $this->get_post_count( $post_type, $lang );
        if ( $count < 1 ) {
            return;
        }
        $per_page  = $this->get_urls_per_sitemap();
        $pages     = max( 1, (int) ceil( $count / $per_page ) );
        $lastmod   = $this->get_lastmod_for_type( $post_type, $lang );

        if ( $pages === 1 ) {
            $list[] = [ 'url' => home_url( "sitemap-{$slug}.xml" ), 'lastmod' => $lastmod ];
        } else {
            for ( $i = 1; $i <= $pages; $i++ ) {
                $list[] = [ 'url' => home_url( "sitemap-{$slug}-{$i}.xml" ), 'lastmod' => $lastmod ];
            }
        }
    }

    private function get_post_count( string $post_type, string $lang = '' ): int {
        $args = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'lang'           => $lang ?: '',
            'meta_query'     => [
                'relation' => 'OR',
                [ 'key' => '_plseo_noindex', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_plseo_noindex', 'value' => '', 'compare' => '=' ],
                [ 'key' => '_plseo_noindex', 'value' => '0', 'compare' => '=' ],
            ],
        ];

        $exclude = (array) PLSEO_Helpers::get_option( 'sitemap_exclude_ids', [] );
        if ( ! empty( $exclude ) ) {
            $args['post__not_in'] = $exclude;
        }

        $query = new WP_Query( $args );
        return (int) $query->found_posts;
    }

    private function get_lastmod_for_type( string $post_type, string $lang = '' ): string {
        global $wpdb;

        $lang_join  = '';
        $lang_where = '';

        if ( $lang && function_exists( 'pll_get_post_language' ) ) {
            $term = get_term_by( 'slug', $lang, 'language' );
            if ( $term ) {
                $lang_join  = " INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id";
                $lang_where = $wpdb->prepare( " AND tr.term_taxonomy_id = %d", $term->term_taxonomy_id );
            }
        }

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(p.post_modified_gmt) FROM {$wpdb->posts} p {$lang_join}
                 WHERE p.post_type = %s AND p.post_status = 'publish' {$lang_where}",
                $post_type
            )
        );

        if ( $result ) {
            return gmdate( 'c', strtotime( $result ) );
        }

        return gmdate( 'c' );
    }

    /* ──────────────────────────────────────────────────── */
    /*  Post-type Sitemap (with pagination)                */
    /* ──────────────────────────────────────────────────── */

    private function render_post_type_sitemap( string $post_type, int $page = 1, string $lang = '' ): void {
        $this->send_xml_headers();

        $exclude   = (array) PLSEO_Helpers::get_option( 'sitemap_exclude_ids', [] );
        $per_page  = $this->get_urls_per_sitemap();
        $offset    = ( $page - 1 ) * $per_page;

        $args = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'offset'         => $offset,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'no_found_rows'  => true,
            'post__not_in'   => $exclude,
            'lang'           => $lang ?: '',
            'meta_query'     => [
                'relation' => 'OR',
                [ 'key' => '_plseo_noindex', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_plseo_noindex', 'value' => '', 'compare' => '=' ],
                [ 'key' => '_plseo_noindex', 'value' => '0', 'compare' => '=' ],
            ],
        ];

        $query = new WP_Query( $args );

        $has_hreflang = (bool) PLSEO_Helpers::get_option( 'sitemap_hreflang', true ) && empty( $lang );
        $has_images   = (bool) PLSEO_Helpers::get_option( 'sitemap_images', true );
        $langs        = PLSEO_Helpers::get_languages();
        $xsl_url      = home_url( 'sitemap-xsl.xsl' );

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        printf( '<?xml-stylesheet type="text/xsl" href="%s"?>' . "\n", esc_url( $xsl_url ) );

        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        if ( $has_hreflang ) {
            echo "\n\txmlns:xhtml=\"http://www.w3.org/1999/xhtml\"";
        }
        if ( $has_images ) {
            echo "\n\txmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\"";
        }
        echo '>' . "\n";

        $seen_sets = [];

        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();

            if ( in_array( $post_id, $exclude, true ) ) {
                continue;
            }

            if ( get_post_meta( $post_id, '_plseo_noindex', true ) ) {
                continue;
            }

            $translations = $has_hreflang ? PLSEO_Helpers::get_post_translations( $post_id ) : [];
            asort( $translations );
            $set_key = implode( '|', $translations );

            if ( $set_key && isset( $seen_sets[ $set_key ] ) ) {
                continue;
            }
            if ( $set_key ) {
                $seen_sets[ $set_key ] = true;
            }

            $this->render_url_entry(
                get_permalink( $post_id ),
                get_post_modified_time( 'c', true, $post_id ),
                $this->get_change_freq( $post_type ),
                $this->get_priority( $post_type, $post_id ),
                $has_hreflang ? $this->build_hreflang_links_for_posts( $translations, $langs ) : [],
                $has_hreflang ? $this->build_xdefault( $translations ) : '',
                $has_images ? $this->get_post_images( $post_id ) : []
            );
        }
        wp_reset_postdata();

        echo '</urlset>';
    }

    /* ──────────────────────────────────────────────────── */
    /*  Taxonomy Sitemap                                    */
    /* ──────────────────────────────────────────────────── */

    private function render_taxonomy_sitemap(): void {
        $this->send_xml_headers();

        $enabled_taxes = (array) PLSEO_Helpers::get_option( 'enabled_taxonomies', [ 'category', 'post_tag' ] );
        $has_hreflang  = (bool) PLSEO_Helpers::get_option( 'sitemap_hreflang', true );
        $langs         = PLSEO_Helpers::get_languages();
        $xsl_url       = home_url( 'sitemap-xsl.xsl' );

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        printf( '<?xml-stylesheet type="text/xsl" href="%s"?>' . "\n", esc_url( $xsl_url ) );
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        if ( $has_hreflang ) {
            echo "\n\txmlns:xhtml=\"http://www.w3.org/1999/xhtml\"";
        }
        echo '>' . "\n";

        $seen_sets = [];

        foreach ( $enabled_taxes as $taxonomy ) {
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }

            $terms = get_terms( [
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
                'lang'       => '',
            ] );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                $translations = $has_hreflang ? PLSEO_Helpers::get_term_translations( $term->term_id ) : [];
                asort( $translations );
                $set_key = implode( '|', $translations );

                if ( $set_key && isset( $seen_sets[ $set_key ] ) ) {
                    continue;
                }
                if ( $set_key ) {
                    $seen_sets[ $set_key ] = true;
                }

                $url = get_term_link( $term );
                if ( is_wp_error( $url ) ) {
                    continue;
                }

                $this->render_url_entry(
                    $url,
                    gmdate( 'c' ),
                    'weekly',
                    0.4,
                    $has_hreflang ? $this->build_hreflang_links_for_terms( $translations, $langs, $taxonomy ) : [],
                    $has_hreflang ? $this->build_xdefault_term( $translations, $taxonomy ) : ''
                );
            }
        }

        echo '</urlset>';
    }

    /* ──────────────────────────────────────────────────── */
    /*  Google News Sitemap                                 */
    /* ──────────────────────────────────────────────────── */

    private function render_news_sitemap(): void {
        $this->send_xml_headers();

        $news_post_types = (array) PLSEO_Helpers::get_option( 'sitemap_news_post_types', [ 'post' ] );
        $publication     = PLSEO_Helpers::get_option( 'sitemap_news_publication', get_bloginfo( 'name' ) );
        $xsl_url         = home_url( 'sitemap-xsl.xsl' );

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        printf( '<?xml-stylesheet type="text/xsl" href="%s"?>' . "\n", esc_url( $xsl_url ) );
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo "\txmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\"" . "\n";
        echo "\txmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\"";
        echo '>' . "\n";

        // Google News only accepts articles from the last 48 hours
        $after_date = gmdate( 'Y-m-d H:i:s', time() - ( 48 * HOUR_IN_SECONDS ) );

        $args = [
            'post_type'      => $news_post_types,
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
            'date_query'     => [
                [ 'after' => $after_date, 'inclusive' => true ],
            ],
            'lang'           => '',
        ];

        $query = new WP_Query( $args );

        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();

            if ( get_post_meta( $post_id, '_plseo_noindex', true ) ) {
                continue;
            }

            $post_lang   = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $post_id, 'locale' ) : get_locale();
            $hreflang    = PLSEO_Helpers::locale_to_hreflang( $post_lang ?: get_locale() );
            $keywords    = $this->get_news_keywords( $post_id );

            echo "\t<url>\n";
            printf( "\t\t<loc>%s</loc>\n", esc_url( get_permalink( $post_id ) ) );
            echo "\t\t<news:news>\n";
            echo "\t\t\t<news:publication>\n";
            printf( "\t\t\t\t<news:name>%s</news:name>\n", esc_xml( $publication ) );
            printf( "\t\t\t\t<news:language>%s</news:language>\n", esc_xml( substr( $hreflang, 0, 2 ) ) );
            echo "\t\t\t</news:publication>\n";
            printf( "\t\t\t<news:publication_date>%s</news:publication_date>\n", esc_xml( get_the_date( 'c', $post_id ) ) );
            printf( "\t\t\t<news:title>%s</news:title>\n", esc_xml( get_the_title( $post_id ) ) );
            if ( $keywords ) {
                printf( "\t\t\t<news:keywords>%s</news:keywords>\n", esc_xml( $keywords ) );
            }
            echo "\t\t</news:news>\n";

            // Include featured image
            if ( has_post_thumbnail( $post_id ) ) {
                $img_url = wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'full' );
                if ( $img_url ) {
                    echo "\t\t<image:image>\n";
                    printf( "\t\t\t<image:loc>%s</image:loc>\n", esc_url( $img_url ) );
                    printf( "\t\t\t<image:title>%s</image:title>\n", esc_xml( get_the_title( $post_id ) ) );
                    echo "\t\t</image:image>\n";
                }
            }

            echo "\t</url>\n";
        }
        wp_reset_postdata();

        echo '</urlset>';
    }

    private function get_news_keywords( int $post_id ): string {
        $tags = get_the_tags( $post_id );
        if ( ! $tags || is_wp_error( $tags ) ) {
            return '';
        }

        $keywords = array_map( fn( $tag ) => $tag->name, array_slice( $tags, 0, 10 ) );
        return implode( ', ', $keywords );
    }

    /* ──────────────────────────────────────────────────── */
    /*  Video Sitemap                                       */
    /* ──────────────────────────────────────────────────── */

    private function render_video_sitemap(): void {
        $this->send_xml_headers();

        $video_post_types = (array) PLSEO_Helpers::get_option( 'sitemap_video_post_types', [ 'post', 'page', 'video' ] );
        $xsl_url          = home_url( 'sitemap-xsl.xsl' );

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        printf( '<?xml-stylesheet type="text/xsl" href="%s"?>' . "\n", esc_url( $xsl_url ) );
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo "\txmlns:video=\"http://www.google.com/schemas/sitemap-video/1.1\"";
        echo '>' . "\n";

        $args = [
            'post_type'      => array_filter( $video_post_types, 'post_type_exists' ),
            'post_status'    => 'publish',
            'posts_per_page' => 500,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'no_found_rows'  => true,
            'lang'           => '',
            'meta_query'     => [
                'relation' => 'OR',
                [ 'key' => '_video_url', 'compare' => 'EXISTS' ],
                [ 'key' => '_video_embed_url', 'compare' => 'EXISTS' ],
            ],
        ];

        $query = new WP_Query( $args );

        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();

            $video_url   = get_post_meta( $post_id, '_video_url', true );
            $embed_url   = get_post_meta( $post_id, '_video_embed_url', true );

            if ( ! $video_url && ! $embed_url ) {
                continue;
            }

            $thumbnail = wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'full' );
            $duration  = get_post_meta( $post_id, '_video_duration', true );

            echo "\t<url>\n";
            printf( "\t\t<loc>%s</loc>\n", esc_url( get_permalink( $post_id ) ) );
            echo "\t\t<video:video>\n";
            printf( "\t\t\t<video:title>%s</video:title>\n", esc_xml( get_the_title( $post_id ) ) );
            printf( "\t\t\t<video:description>%s</video:description>\n", esc_xml( wp_strip_all_tags( get_the_excerpt( $post_id ) ) ) );

            if ( $thumbnail ) {
                printf( "\t\t\t<video:thumbnail_loc>%s</video:thumbnail_loc>\n", esc_url( $thumbnail ) );
            }
            if ( $video_url ) {
                printf( "\t\t\t<video:content_loc>%s</video:content_loc>\n", esc_url( $video_url ) );
            }
            if ( $embed_url ) {
                printf( "\t\t\t<video:player_loc>%s</video:player_loc>\n", esc_url( $embed_url ) );
            }
            if ( $duration ) {
                printf( "\t\t\t<video:duration>%d</video:duration>\n", absint( $this->iso_duration_to_seconds( $duration ) ) );
            }

            printf( "\t\t\t<video:publication_date>%s</video:publication_date>\n", esc_xml( get_the_date( 'c', $post_id ) ) );
            echo "\t\t\t<video:family_friendly>yes</video:family_friendly>\n";
            echo "\t\t</video:video>\n";
            echo "\t</url>\n";
        }
        wp_reset_postdata();

        echo '</urlset>';
    }

    private function iso_duration_to_seconds( string $duration ): int {
        if ( is_numeric( $duration ) ) {
            return (int) $duration;
        }

        try {
            $interval = new \DateInterval( $duration );
            return ( $interval->h * 3600 ) + ( $interval->i * 60 ) + $interval->s;
        } catch ( \Exception $e ) {
            return 0;
        }
    }

    /* ──────────────────────────────────────────────────── */
    /*  Author Sitemap                                      */
    /* ──────────────────────────────────────────────────── */

    private function render_author_sitemap(): void {
        $this->send_xml_headers();

        $xsl_url = home_url( 'sitemap-xsl.xsl' );

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        printf( '<?xml-stylesheet type="text/xsl" href="%s"?>' . "\n", esc_url( $xsl_url ) );
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        if ( ! PLSEO_Helpers::get_option( 'noindex_author', false ) ) {
            $authors = get_users( [
                'has_published_posts' => true,
                'fields'             => [ 'ID' ],
            ] );

            foreach ( $authors as $author ) {
                $url = get_author_posts_url( $author->ID );
                if ( $url ) {
                    echo "\t<url>\n";
                    printf( "\t\t<loc>%s</loc>\n", esc_url( $url ) );
                    printf( "\t\t<lastmod>%s</lastmod>\n", esc_xml( gmdate( 'c' ) ) );
                    echo "\t\t<changefreq>weekly</changefreq>\n";
                    echo "\t\t<priority>0.3</priority>\n";
                    echo "\t</url>\n";
                }
            }
        }

        echo '</urlset>';
    }

    /* ──────────────────────────────────────────────────── */
    /*  URL Entry Renderer                                  */
    /* ──────────────────────────────────────────────────── */

    private function render_url_entry(
        string $loc,
        string $lastmod,
        string $changefreq,
        float  $priority,
        array  $hreflang_links = [],
        string $x_default      = '',
        array  $images         = []
    ): void {
        echo "\t<url>\n";
        printf( "\t\t<loc>%s</loc>\n", esc_url( $loc ) );
        printf( "\t\t<lastmod>%s</lastmod>\n", esc_xml( $lastmod ) );
        printf( "\t\t<changefreq>%s</changefreq>\n", esc_xml( $changefreq ) );
        printf( "\t\t<priority>%.1f</priority>\n", (float) $priority );

        foreach ( $hreflang_links as $hreflang => $href ) {
            printf(
                "\t\t<xhtml:link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n",
                esc_attr( $hreflang ),
                esc_url( $href )
            );
        }

        if ( $x_default && PLSEO_Helpers::get_option( 'sitemap_x_default', true ) ) {
            printf(
                "\t\t<xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"%s\" />\n",
                esc_url( $x_default )
            );
        }

        // Self-referencing hreflang (required by Google for completeness)
        if ( ! empty( $hreflang_links ) ) {
            $self_found = false;
            foreach ( $hreflang_links as $href ) {
                if ( untrailingslashit( $href ) === untrailingslashit( $loc ) ) {
                    $self_found = true;
                    break;
                }
            }
            if ( ! $self_found ) {
                $current_lang = PLSEO_Helpers::current_lang();
                $languages    = PLSEO_Helpers::get_languages();
                $hreflang_tag = '';
                if ( $current_lang && isset( $languages[ $current_lang ] ) ) {
                    $hreflang_tag = PLSEO_Helpers::locale_to_hreflang( $languages[ $current_lang ]['locale'] );
                }
                if ( $hreflang_tag ) {
                    printf(
                        "\t\t<xhtml:link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n",
                        esc_attr( $hreflang_tag ),
                        esc_url( $loc )
                    );
                }
            }
        }

        foreach ( $images as $img ) {
            echo "\t\t<image:image>\n";
            printf( "\t\t\t<image:loc>%s</image:loc>\n", esc_url( $img['src'] ) );
            if ( ! empty( $img['title'] ) ) {
                printf( "\t\t\t<image:title>%s</image:title>\n", esc_xml( $img['title'] ) );
            }
            if ( ! empty( $img['alt'] ) ) {
                printf( "\t\t\t<image:caption>%s</image:caption>\n", esc_xml( $img['alt'] ) );
            }
            echo "\t\t</image:image>\n";
        }

        echo "\t</url>\n";
    }

    /* ──────────────────────────────────────────────────── */
    /*  hreflang Helpers                                    */
    /* ──────────────────────────────────────────────────── */

    private function build_hreflang_links_for_posts( array $translations, array $langs ): array {
        $links = [];
        foreach ( $translations as $slug => $pid ) {
            if ( ! isset( $langs[ $slug ] ) ) {
                continue;
            }
            $pid = (int) $pid;
            if ( get_post_meta( $pid, '_plseo_noindex', true ) ) {
                continue;
            }
            $url = get_permalink( $pid );
            if ( $url ) {
                $hreflang           = PLSEO_Helpers::locale_to_hreflang( $langs[ $slug ]['locale'] );
                $links[ $hreflang ] = $url;
            }
        }
        return $links;
    }

    private function build_hreflang_links_for_terms( array $translations, array $langs, string $taxonomy ): array {
        $links = [];
        foreach ( $translations as $slug => $tid ) {
            if ( ! isset( $langs[ $slug ] ) ) {
                continue;
            }
            $url = get_term_link( (int) $tid, $taxonomy );
            if ( ! is_wp_error( $url ) ) {
                $hreflang           = PLSEO_Helpers::locale_to_hreflang( $langs[ $slug ]['locale'] );
                $links[ $hreflang ] = $url;
            }
        }
        return $links;
    }

    private function build_xdefault( array $translations ): string {
        $default = function_exists( 'pll_default_language' ) ? pll_default_language() : '';
        if ( $default && isset( $translations[ $default ] ) ) {
            return (string) get_permalink( (int) $translations[ $default ] );
        }
        $first = reset( $translations );
        return $first ? (string) get_permalink( (int) $first ) : '';
    }

    private function build_xdefault_term( array $translations, string $taxonomy ): string {
        $default = function_exists( 'pll_default_language' ) ? pll_default_language() : '';
        if ( $default && isset( $translations[ $default ] ) ) {
            $url = get_term_link( (int) $translations[ $default ], $taxonomy );
            return is_wp_error( $url ) ? '' : $url;
        }
        $first = reset( $translations );
        if ( $first ) {
            $url = get_term_link( (int) $first, $taxonomy );
            return is_wp_error( $url ) ? '' : $url;
        }
        return '';
    }

    /* ──────────────────────────────────────────────────── */
    /*  Image Extraction                                    */
    /* ──────────────────────────────────────────────────── */

    private function get_post_images( int $post_id ): array {
        $images    = [];
        $max_images = 10;

        // Featured image
        if ( has_post_thumbnail( $post_id ) ) {
            $att_id = get_post_thumbnail_id( $post_id );
            $src    = wp_get_attachment_image_url( $att_id, 'full' );
            if ( $src ) {
                $images[] = [
                    'src'   => $src,
                    'title' => get_the_title( $att_id ),
                    'alt'   => get_post_meta( $att_id, '_wp_attachment_image_alt', true ),
                ];
            }
        }

        // Content images
        $post    = get_post( $post_id );
        $content = $post ? $post->post_content : '';
        preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches );
        $count = 0;
        foreach ( $matches[1] as $idx => $src ) {
            if ( $count >= ( $max_images - count( $images ) ) ) {
                break;
            }
            if ( isset( $images[0] ) && $images[0]['src'] === $src ) {
                continue;
            }
            // Only include images from the same domain
            $src_host  = wp_parse_url( $src, PHP_URL_HOST );
            $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
            if ( $src_host && $home_host && $src_host !== $home_host ) {
                continue;
            }

            preg_match( '/alt=["\']([^"\']*)["\']/', $matches[0][ $idx ], $alt_match );
            $images[] = [
                'src'   => $src,
                'title' => '',
                'alt'   => $alt_match[1] ?? '',
            ];
            $count++;
        }

        return $images;
    }

    /* ──────────────────────────────────────────────────── */
    /*  Helpers                                             */
    /* ──────────────────────────────────────────────────── */

    private function send_xml_headers(): void {
        header( 'Content-Type: application/xml; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex, follow' );
        header( 'Cache-Control: public, max-age=3600' );
    }

    private function get_urls_per_sitemap(): int {
        $configured = (int) PLSEO_Helpers::get_option( 'sitemap_posts_per_type', 1000 );
        return min( $configured, self::MAX_URLS_PER_SITEMAP );
    }

    private function get_change_freq( string $post_type ): string {
        return match ( $post_type ) {
            'page'  => 'monthly',
            'post'  => 'weekly',
            default => 'weekly',
        };
    }

    private function get_priority( string $post_type, int $post_id ): float {
        if ( (int) get_option( 'page_on_front' ) === $post_id ) {
            return 1.0;
        }
        return match ( $post_type ) {
            'page'  => 0.8,
            'post'  => 0.6,
            default => 0.5,
        };
    }

    public function maybe_flush(): void {
        $rules = get_option( 'rewrite_rules' );

        if ( ! is_array( $rules ) || ! isset( $rules['^sitemap\.xml$'] ) ) {
            flush_rewrite_rules( false );
        }
    }

    /**
     * Debounce pings: schedule one 30s from now to batch rapid publishes.
     */
    public function schedule_ping(): void {
        if ( ! wp_next_scheduled( 'plseo_deferred_ping' ) ) {
            wp_schedule_single_event( time() + 30, 'plseo_deferred_ping' );
        }
    }

    public function ping_search_engines(): void {
        $ping_google = PLSEO_Helpers::get_option( 'sitemap_ping_google', true );
        $ping_bing   = PLSEO_Helpers::get_option( 'sitemap_ping_bing', true );
        $index_now   = PLSEO_Helpers::get_option( 'sitemap_indexnow', false );

        if ( ! $ping_google && ! $ping_bing && ! $index_now ) {
            return;
        }

        $sitemap_url = home_url( 'sitemap.xml' );

        if ( $ping_google ) {
            wp_remote_get(
                'https://www.google.com/ping?sitemap=' . urlencode( $sitemap_url ),
                [ 'blocking' => false, 'timeout' => 5 ]
            );
        }
        if ( $ping_bing ) {
            wp_remote_get(
                'https://www.bing.com/ping?sitemap=' . urlencode( $sitemap_url ),
                [ 'blocking' => false, 'timeout' => 5 ]
            );
        }

        // IndexNow API (Bing, Yandex, Seznam, Naver)
        if ( $index_now ) {
            $this->submit_indexnow();
        }
    }

    /**
     * Submit the sitemap URL via IndexNow protocol.
     */
    private function submit_indexnow(): void {
        $key = PLSEO_Helpers::get_option( 'indexnow_api_key', '' );
        if ( ! $key ) {
            return;
        }

        $payload = [
            'host'    => wp_parse_url( home_url(), PHP_URL_HOST ),
            'key'     => $key,
            'urlList' => [ home_url( 'sitemap.xml' ) ],
        ];

        wp_remote_post( 'https://api.indexnow.org/indexnow', [
            'body'     => wp_json_encode( $payload ),
            'headers'  => [ 'Content-Type' => 'application/json; charset=utf-8' ],
            'blocking' => false,
            'timeout'  => 5,
        ] );
    }
}
