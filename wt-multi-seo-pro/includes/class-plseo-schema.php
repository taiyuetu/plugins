<?php
defined( 'ABSPATH' ) || exit;

/**
 * PLSEO_Schema - Professional GEO & SEO Schema Generator
 *
 * Produces a fully connected @graph with:
 * - Organization / Person + sameAs
 * - WebSite + SearchAction (sitelinks search box)
 * - WebPage + inLanguage + speakable (GEO)
 * - BreadcrumbList
 * - BlogPosting / Article with full author + publisher linking
 * - Product (catalog), VideoObject, FAQPage, HowToPage
 * - LocalBusiness with GeoCoordinates and structured address
 * - SiteNavigationElement for AI crawlers
 */
class PLSEO_Schema {

    private static ?self $instance = null;
    private array $graph = [];
    private string $home_url;
    private string $site_name;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! PLSEO_Helpers::get_option( 'schema_enabled', true ) ) {
            return;
        }

        $this->home_url  = trailingslashit( get_home_url() );
        $this->site_name = get_bloginfo( 'name' );

        add_action( 'wp_head', [ $this, 'output_schema' ], 8 );
    }

    public function output_schema(): void {
        $this->graph = [];

        $post_id   = 0;
        $post_type = '';

        if ( is_singular() ) {
            $post_id   = get_queried_object_id();
            $post_type = get_post_type( $post_id );

            if ( $post_id && PLSEO_Helpers::is_seo_disabled( $post_id ) ) {
                return;
            }
        }

        // 1. Core entities (always present)
        $this->add_organization_to_graph();
        $this->add_website_to_graph();

        // 2. Breadcrumbs
        if ( PLSEO_Helpers::get_option( 'schema_breadcrumb', true ) ) {
            $this->add_breadcrumb_to_graph();
        }

        // 3. Content-specific entities
        if ( is_singular() ) {
            switch ( $post_type ) {
                case 'post':
                    $this->add_article_to_graph( $post_id );
                    break;
                case 'product':
                    $this->add_product_to_graph( $post_id );
                    break;
                case 'video':
                    $this->add_video_to_graph( $post_id );
                    break;
                default:
                    $this->add_webpage_to_graph( $post_id );
                    break;
            }

            // FAQ schema (from Gutenberg FAQ block or post meta)
            $this->maybe_add_faq_to_graph( $post_id );

            // HowTo schema
            $this->maybe_add_howto_to_graph( $post_id );
        }

        // 4. Local Business
        if ( PLSEO_Helpers::get_option( 'schema_local_business', false ) ) {
            $this->add_local_business_to_graph();
        }

        // 5. Site Navigation (GEO: helps AI understand site structure)
        if ( PLSEO_Helpers::get_option( 'schema_navigation', true ) ) {
            $this->add_navigation_to_graph();
        }

        $this->graph = $this->filter_graph_values( $this->graph );

        if ( ! empty( $this->graph ) ) {
            echo "\n" . '<script type="application/ld+json" class="plseo-schema-graph">' . "\n";
            echo wp_json_encode( [
                '@context' => 'https://schema.org',
                '@graph'   => array_values( $this->graph ),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
            echo "\n</script>\n";
        }
    }

    /* ── Organization / Person ──────────────────────────── */

    private function add_organization_to_graph(): void {
        $type = PLSEO_Helpers::get_option( 'schema_org_type', 'Organization' );
        if ( ! in_array( $type, [ 'Organization', 'Person' ], true ) ) {
            $type = 'Organization';
        }

        $logo_id = get_theme_mod( 'custom_logo' );
        $logo    = $logo_id ? wp_get_attachment_image_src( $logo_id, 'full' ) : null;
        $name    = PLSEO_Helpers::get_option( 'schema_org_name', '' ) ?: $this->site_name;

        $social_profiles = array_filter( [
            PLSEO_Helpers::get_option( 'social_facebook' ),
            PLSEO_Helpers::get_option( 'social_twitter' ),
            PLSEO_Helpers::get_option( 'social_linkedin' ),
            PLSEO_Helpers::get_option( 'social_youtube' ),
            PLSEO_Helpers::get_option( 'social_instagram' ),
            PLSEO_Helpers::get_option( 'social_pinterest' ),
            PLSEO_Helpers::get_option( 'social_tiktok' ),
        ] );

        $org = [
            '@type' => $type,
            '@id'   => $this->home_url . '#organization',
            'name'  => $name,
            'url'   => PLSEO_Helpers::get_option( 'schema_org_url', '' ) ?: $this->home_url,
        ];

        if ( $logo ) {
            $org['logo'] = [
                '@type'      => 'ImageObject',
                '@id'        => $this->home_url . '#logo',
                'url'        => $logo[0],
                'contentUrl' => $logo[0],
                'width'      => $logo[1],
                'height'     => $logo[2],
                'caption'    => $name,
            ];
            $org['image'] = [ '@id' => $this->home_url . '#logo' ];
        }

        if ( ! empty( $social_profiles ) ) {
            $org['sameAs'] = array_values( $social_profiles );
        }

        $contact_type = PLSEO_Helpers::get_option( 'schema_contact_type', '' );
        $contact_phone = PLSEO_Helpers::get_option( 'schema_lb_phone', '' );
        if ( $contact_type && $contact_phone ) {
            $org['contactPoint'] = [
                '@type'       => 'ContactPoint',
                'telephone'   => $contact_phone,
                'contactType' => $contact_type,
            ];
        }

        $this->graph['organization'] = $this->filter_graph_values( $org );
    }

    /* ── WebSite + SearchAction ─────────────────────────── */

    private function add_website_to_graph(): void {
        $website = [
            '@type'     => 'WebSite',
            '@id'       => $this->home_url . '#website',
            'url'       => $this->home_url,
            'name'      => $this->site_name,
            'publisher' => [ '@id' => $this->home_url . '#organization' ],
        ];

        // Multilingual: inLanguage
        $languages = PLSEO_Helpers::get_languages();
        if ( ! empty( $languages ) ) {
            $website['inLanguage'] = array_map(
                fn( $lang ) => PLSEO_Helpers::locale_to_hreflang( $lang['locale'] ),
                $languages
            );
        }

        // SearchAction for Google sitelinks search box
        if ( PLSEO_Helpers::get_option( 'schema_search_action', true ) ) {
            $website['potentialAction'] = [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $this->home_url . '?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ];
        }

        $this->graph['website'] = $website;
    }

    /* ── WebPage ────────────────────────────────────────── */

    private function add_webpage_to_graph( int $post_id ): void {
        $permalink = get_permalink( $post_id );
        $lang      = PLSEO_Helpers::current_lang();
        $languages = PLSEO_Helpers::get_languages();
        $locale    = ( $lang && isset( $languages[ $lang ] ) )
            ? PLSEO_Helpers::locale_to_hreflang( $languages[ $lang ]['locale'] )
            : get_locale();

        $webpage = [
            '@type'           => 'WebPage',
            '@id'             => $permalink . '#webpage',
            'url'             => $permalink,
            'name'            => get_the_title( $post_id ),
            'description'     => PLSEO_Meta::get_instance()->get_description(),
            'isPartOf'        => [ '@id' => $this->home_url . '#website' ],
            'inLanguage'      => $locale,
            'datePublished'   => get_the_date( 'c', $post_id ),
            'dateModified'    => get_post_modified_time( 'c', true, $post_id ),
            'breadcrumb'      => [ '@id' => $permalink . '#breadcrumb' ],
        ];

        // GEO: Speakable for Google Assistant / AI voice search
        if ( PLSEO_Helpers::get_option( 'schema_speakable', true ) ) {
            $webpage['speakable'] = [
                '@type'       => 'SpeakableSpecification',
                'cssSelector' => [ '.entry-title', '.entry-content', '.post-content', 'article h1', 'article p' ],
            ];
        }

        // Featured image
        $img_url = $this->get_featured_image_url( $post_id );
        if ( $img_url ) {
            $webpage['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url'   => $img_url,
            ];
        }

        $this->graph['webpage'] = $this->filter_graph_values( $webpage );
    }

    /* ── Article / BlogPosting ──────────────────────────── */

    private function add_article_to_graph( int $post_id ): void {
        $permalink  = get_permalink( $post_id );
        $author_id  = (int) get_post_field( 'post_author', $post_id );
        $author_url = get_author_posts_url( $author_id );

        $lang      = PLSEO_Helpers::current_lang();
        $languages = PLSEO_Helpers::get_languages();
        $locale    = ( $lang && isset( $languages[ $lang ] ) )
            ? PLSEO_Helpers::locale_to_hreflang( $languages[ $lang ]['locale'] )
            : get_locale();

        // Author entity
        $this->graph['author'] = $this->filter_graph_values( [
            '@type'  => 'Person',
            '@id'    => $author_url . '#author',
            'name'   => get_the_author_meta( 'display_name', $author_id ),
            'url'    => $author_url,
            'image'  => get_avatar_url( $author_id, [ 'size' => 96 ] ),
            'sameAs' => array_filter( [
                get_the_author_meta( 'url', $author_id ),
                get_the_author_meta( 'twitter', $author_id ) ? 'https://twitter.com/' . get_the_author_meta( 'twitter', $author_id ) : '',
                get_the_author_meta( 'linkedin', $author_id ),
            ] ),
        ] );

        $article_type = PLSEO_Helpers::get_option( 'schema_article_type', 'BlogPosting' );
        if ( ! in_array( $article_type, [ 'Article', 'BlogPosting', 'NewsArticle', 'TechArticle' ], true ) ) {
            $article_type = 'BlogPosting';
        }

        $article = [
            '@type'            => $article_type,
            '@id'              => $permalink . '#article',
            'headline'         => get_the_title( $post_id ),
            'datePublished'    => get_the_date( 'c', $post_id ),
            'dateModified'     => get_post_modified_time( 'c', true, $post_id ),
            'author'           => [ '@id' => $author_url . '#author' ],
            'publisher'        => [ '@id' => $this->home_url . '#organization' ],
            'mainEntityOfPage' => [ '@id' => $permalink . '#webpage' ],
            'inLanguage'       => $locale,
            'image'            => $this->get_featured_image_url( $post_id ),
        ];

        // Word count for article length signals
        $post    = get_post( $post_id );
        $content = $post ? wp_strip_all_tags( $post->post_content ) : '';
        $word_count = str_word_count( $content );
        if ( $word_count > 0 ) {
            $article['wordCount'] = $word_count;
        }

        // Categories and tags
        $categories = get_the_category( $post_id );
        if ( $categories && ! is_wp_error( $categories ) ) {
            $article['articleSection'] = array_map( fn( $cat ) => $cat->name, array_slice( $categories, 0, 3 ) );
        }

        $tags = get_the_tags( $post_id );
        if ( $tags && ! is_wp_error( $tags ) ) {
            $article['keywords'] = array_map( fn( $tag ) => $tag->name, array_slice( $tags, 0, 10 ) );
        }

        // GEO: Speakable
        if ( PLSEO_Helpers::get_option( 'schema_speakable', true ) ) {
            $article['speakable'] = [
                '@type'       => 'SpeakableSpecification',
                'cssSelector' => [ '.entry-title', '.entry-content p:first-of-type', 'article h1' ],
            ];
        }

        $this->graph['article'] = $this->filter_graph_values( $article );
        $this->add_webpage_to_graph( $post_id );
    }

    /* ── Product ────────────────────────────────────────── */

    private function add_product_to_graph( int $post_id ): void {
        $product = [
            '@type'            => 'Product',
            '@id'              => get_permalink( $post_id ) . '#product',
            'name'             => get_the_title( $post_id ),
            'description'      => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
            'image'            => $this->get_featured_image_url( $post_id ),
            'sku'              => get_post_meta( $post_id, '_sku', true ) ?: null,
            'brand'            => [
                '@type' => 'Brand',
                'name'  => get_post_meta( $post_id, '_product_brand', true ) ?: $this->site_name,
            ],
            'mainEntityOfPage' => [ '@id' => get_permalink( $post_id ) . '#webpage' ],
        ];

        // If WooCommerce is active, add offers
        if ( function_exists( 'wc_get_product' ) ) {
            $wc_product = wc_get_product( $post_id );
            if ( $wc_product ) {
                $product['offers'] = [
                    '@type'         => 'Offer',
                    'price'         => $wc_product->get_price(),
                    'priceCurrency' => get_woocommerce_currency(),
                    'availability'  => $wc_product->is_in_stock()
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock',
                    'url'           => get_permalink( $post_id ),
                ];
            }
        }

        $this->graph['product'] = $this->filter_graph_values( $product );
        $this->add_webpage_to_graph( $post_id );
    }

    /* ── VideoObject ────────────────────────────────────── */

    private function add_video_to_graph( int $post_id ): void {
        $this->graph['video'] = $this->filter_graph_values( [
            '@type'         => 'VideoObject',
            '@id'           => get_permalink( $post_id ) . '#video',
            'name'          => get_the_title( $post_id ),
            'description'   => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
            'thumbnailUrl'  => $this->get_featured_image_url( $post_id ),
            'uploadDate'    => get_the_date( 'c', $post_id ),
            'duration'      => get_post_meta( $post_id, '_video_duration', true ) ?: null,
            'contentUrl'    => get_post_meta( $post_id, '_video_url', true ) ?: get_permalink( $post_id ),
            'embedUrl'      => get_post_meta( $post_id, '_video_embed_url', true ) ?: null,
            'publisher'     => [ '@id' => $this->home_url . '#organization' ],
            'inLanguage'    => PLSEO_Helpers::current_lang() ?: get_locale(),
        ] );
        $this->add_webpage_to_graph( $post_id );
    }

    /* ── FAQ Page ───────────────────────────────────────── */

    private function maybe_add_faq_to_graph( int $post_id ): void {
        $faq_items = $this->extract_faq_data( $post_id );
        if ( empty( $faq_items ) ) {
            return;
        }

        $entities = [];
        foreach ( $faq_items as $item ) {
            $entities[] = [
                '@type'          => 'Question',
                'name'           => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $item['answer'],
                ],
            ];
        }

        $this->graph['faq'] = [
            '@type'            => 'FAQPage',
            '@id'              => get_permalink( $post_id ) . '#faq',
            'mainEntity'       => $entities,
            'mainEntityOfPage' => [ '@id' => get_permalink( $post_id ) . '#webpage' ],
        ];
    }

    /**
     * Extract FAQ data from post meta, Gutenberg blocks, or content patterns.
     */
    private function extract_faq_data( int $post_id ): array {
        // 1. Explicit post meta (structured FAQ data from custom fields)
        $meta_faq = get_post_meta( $post_id, '_plseo_faq_data', true );
        if ( is_array( $meta_faq ) && ! empty( $meta_faq ) ) {
            return $meta_faq;
        }

        // 2. Parse Gutenberg FAQ blocks (e.g., Yoast FAQ, RankMath, or core heading+paragraph patterns)
        $post    = get_post( $post_id );
        $content = $post ? $post->post_content : '';
        if ( ! $content ) {
            return [];
        }

        $faq_items = [];

        // Look for Yoast-style FAQ block
        if ( has_blocks( $content ) ) {
            $blocks = parse_blocks( $content );
            foreach ( $blocks as $block ) {
                if ( 'yoast/faq-block' === ( $block['blockName'] ?? '' ) && ! empty( $block['attrs']['questions'] ) ) {
                    foreach ( $block['attrs']['questions'] as $q ) {
                        if ( ! empty( $q['jsonQuestion'] ) && ! empty( $q['jsonAnswer'] ) ) {
                            $faq_items[] = [
                                'question' => wp_strip_all_tags( $q['jsonQuestion'] ),
                                'answer'   => wp_strip_all_tags( $q['jsonAnswer'] ),
                            ];
                        }
                    }
                }
            }
        }

        // 3. Pattern: <h2/h3>Question?</h2/h3> followed by <p>Answer</p>
        if ( empty( $faq_items ) ) {
            preg_match_all(
                '#<h[23][^>]*>\s*(.+?\?)\s*</h[23]>\s*<p[^>]*>\s*(.+?)\s*</p>#si',
                $content,
                $matches,
                PREG_SET_ORDER
            );
            foreach ( array_slice( $matches, 0, 15 ) as $m ) {
                $q = wp_strip_all_tags( $m[1] );
                $a = wp_strip_all_tags( $m[2] );
                if ( mb_strlen( $q ) > 5 && mb_strlen( $a ) > 10 ) {
                    $faq_items[] = [ 'question' => $q, 'answer' => $a ];
                }
            }
        }

        return $faq_items;
    }

    /* ── HowTo ──────────────────────────────────────────── */

    private function maybe_add_howto_to_graph( int $post_id ): void {
        $howto_data = get_post_meta( $post_id, '_plseo_howto_data', true );
        if ( ! is_array( $howto_data ) || empty( $howto_data['steps'] ) ) {
            return;
        }

        $steps = [];
        foreach ( $howto_data['steps'] as $i => $step ) {
            $step_item = [
                '@type'    => 'HowToStep',
                'position' => $i + 1,
                'name'     => $step['name'] ?? '',
                'text'     => $step['text'] ?? '',
            ];
            if ( ! empty( $step['image'] ) ) {
                $step_item['image'] = $step['image'];
            }
            if ( ! empty( $step['url'] ) ) {
                $step_item['url'] = $step['url'];
            }
            $steps[] = $this->filter_graph_values( $step_item );
        }

        $howto = [
            '@type'       => 'HowTo',
            '@id'         => get_permalink( $post_id ) . '#howto',
            'name'        => $howto_data['name'] ?? get_the_title( $post_id ),
            'description' => $howto_data['description'] ?? wp_strip_all_tags( get_the_excerpt( $post_id ) ),
            'step'        => $steps,
        ];

        if ( ! empty( $howto_data['totalTime'] ) ) {
            $howto['totalTime'] = $howto_data['totalTime'];
        }
        if ( ! empty( $howto_data['estimatedCost'] ) ) {
            $howto['estimatedCost'] = [
                '@type'    => 'MonetaryAmount',
                'currency' => $howto_data['currency'] ?? 'USD',
                'value'    => $howto_data['estimatedCost'],
            ];
        }

        $this->graph['howto'] = $this->filter_graph_values( $howto );
    }

    /* ── Breadcrumbs ────────────────────────────────────── */

    private function add_breadcrumb_to_graph(): void {
        $items = PLSEO_Breadcrumbs::get_items();
        if ( empty( $items ) ) {
            return;
        }

        $list_items = [];
        foreach ( array_values( $items ) as $index => $item ) {
            $list_item = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $item['name'],
            ];
            if ( ! empty( $item['url'] ) ) {
                $list_item['item'] = $item['url'];
            }
            $list_items[] = $list_item;
        }

        $this->graph['breadcrumb'] = [
            '@type'           => 'BreadcrumbList',
            '@id'             => PLSEO_Canonical::get_instance()->get_canonical_url() . '#breadcrumb',
            'itemListElement' => $list_items,
        ];
    }

    /* ── Local Business ─────────────────────────────────── */

    private function add_local_business_to_graph(): void {
        $business_type = (string) PLSEO_Helpers::get_option( 'schema_lb_type', 'LocalBusiness' );
        $street        = (string) PLSEO_Helpers::get_option( 'schema_lb_street', '' );
        $city          = (string) PLSEO_Helpers::get_option( 'schema_lb_city', '' );
        $region        = (string) PLSEO_Helpers::get_option( 'schema_lb_region', '' );
        $postal        = (string) PLSEO_Helpers::get_option( 'schema_lb_postal', '' );
        $country       = (string) PLSEO_Helpers::get_option( 'schema_lb_country', '' );
        $phone         = (string) PLSEO_Helpers::get_option( 'schema_lb_phone', '' );
        $hours         = (string) PLSEO_Helpers::get_option( 'schema_lb_hours', '' );
        $lat           = (string) PLSEO_Helpers::get_option( 'schema_lb_lat', '' );
        $lng           = (string) PLSEO_Helpers::get_option( 'schema_lb_lng', '' );
        $price_range   = (string) PLSEO_Helpers::get_option( 'schema_lb_price_range', '' );

        $local_business = [
            '@type'     => $business_type ?: 'LocalBusiness',
            '@id'       => $this->home_url . '#localbusiness',
            'name'      => $this->site_name,
            'url'       => $this->home_url,
            'telephone' => $phone,
        ];

        if ( $street || $city || $country ) {
            $local_business['address'] = $this->filter_graph_values( [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $street,
                'addressLocality' => $city,
                'addressRegion'   => $region,
                'postalCode'      => $postal,
                'addressCountry'  => $country,
            ] );
        }

        // GEO coordinates for map positioning
        if ( $lat && $lng ) {
            $local_business['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float) $lat,
                'longitude' => (float) $lng,
            ];
        }

        if ( $hours ) {
            $local_business['openingHours'] = array_map( 'trim', explode( ',', $hours ) );
        }

        if ( $price_range ) {
            $local_business['priceRange'] = $price_range;
        }

        $this->graph['localbusiness'] = $this->filter_graph_values( $local_business );
    }

    /* ── SiteNavigationElement (GEO) ────────────────────── */

    private function add_navigation_to_graph(): void {
        $menu_locations = get_nav_menu_locations();
        $primary_menu   = null;

        foreach ( [ 'primary', 'main', 'header', 'primary-menu', 'main-menu' ] as $location ) {
            if ( isset( $menu_locations[ $location ] ) ) {
                $primary_menu = wp_get_nav_menu_items( $menu_locations[ $location ] );
                break;
            }
        }

        if ( empty( $primary_menu ) ) {
            $menus = get_terms( 'nav_menu', [ 'hide_empty' => true ] );
            if ( ! empty( $menus ) && ! is_wp_error( $menus ) ) {
                $primary_menu = wp_get_nav_menu_items( $menus[0]->term_id );
            }
        }

        if ( empty( $primary_menu ) ) {
            return;
        }

        $nav_items = [];
        foreach ( array_slice( $primary_menu, 0, 20 ) as $item ) {
            if ( (int) $item->menu_item_parent > 0 ) {
                continue;
            }
            $nav_items[] = [
                '@type' => 'SiteNavigationElement',
                'name'  => $item->title,
                'url'   => $item->url,
            ];
        }

        if ( ! empty( $nav_items ) ) {
            $this->graph['navigation'] = [
                '@type'           => 'ItemList',
                '@id'             => $this->home_url . '#navigation',
                'name'            => 'Main Navigation',
                'itemListElement' => $nav_items,
            ];
        }
    }

    /* ── Utilities ──────────────────────────────────────── */

    private function get_featured_image_url( int $post_id ): string {
        $img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
        return $img ? $img[0] : '';
    }

    private function filter_graph_values( mixed $value ): mixed {
        if ( ! is_array( $value ) ) {
            return $value;
        }

        $filtered = [];
        foreach ( $value as $key => $item ) {
            if ( is_array( $item ) ) {
                $item = $this->filter_graph_values( $item );
                if ( [] === $item ) {
                    continue;
                }
            } elseif ( null === $item || '' === $item ) {
                continue;
            }
            $filtered[ $key ] = $item;
        }

        return $filtered;
    }
}
