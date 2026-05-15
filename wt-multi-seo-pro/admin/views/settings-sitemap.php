<?php
defined( 'ABSPATH' ) || exit;
include PLSEO_DIR . 'admin/views/header.php';
?>
<form method="post">
    <?php wp_nonce_field( 'plseo_settings' ); ?>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'XML Sitemap', 'polylang-seo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Enable Sitemap', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_enabled" <?php checked( PLSEO_Helpers::get_option( 'sitemap_enabled', true ) ); ?> /> <?php esc_html_e( 'Serve sitemap index at /sitemap.xml (with XSL stylesheet for human-readable view)', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Include Posts', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_include_posts" <?php checked( PLSEO_Helpers::get_option( 'sitemap_include_posts', true ) ); ?> /> <?php esc_html_e( 'Include blog posts', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Include Pages', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_include_pages" <?php checked( PLSEO_Helpers::get_option( 'sitemap_include_pages', true ) ); ?> /> <?php esc_html_e( 'Include pages', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Include Taxonomies', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_include_taxonomies" <?php checked( PLSEO_Helpers::get_option( 'sitemap_include_taxonomies', true ) ); ?> /> <?php esc_html_e( 'Include taxonomy archives (categories, tags)', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Include Authors', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_include_authors" <?php checked( PLSEO_Helpers::get_option( 'sitemap_include_authors', false ) ); ?> /> <?php esc_html_e( 'Include author archive pages in sitemap', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Include Images', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_images" <?php checked( PLSEO_Helpers::get_option( 'sitemap_images', true ) ); ?> /> <?php esc_html_e( 'Add image sitemap entries for featured and content images (up to 10 per post)', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Custom Post Types', 'polylang-seo' ); ?></th>
                <td>
                    <?php $enabled_cpts = (array) PLSEO_Helpers::get_option( 'sitemap_include_cpt', [] ); ?>
                    <?php foreach ( get_post_types( [ 'public' => true, '_builtin' => false ], 'objects' ) as $post_type ) : ?>
                        <label class="plseo-check">
                            <input type="checkbox" name="plseo_sitemap_include_cpt[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $enabled_cpts, true ) ); ?> />
                            <span><?php echo esc_html( $post_type->label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'Google News Sitemap', 'polylang-seo' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Generate a dedicated sitemap-news.xml for Google News indexing. Only articles from the last 48 hours are included per Google specifications.', 'polylang-seo' ); ?></p>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Enable News Sitemap', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_news" <?php checked( PLSEO_Helpers::get_option( 'sitemap_news', false ) ); ?> /> <?php esc_html_e( 'Generate /sitemap-news.xml', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><label for="plseo_sitemap_news_publication"><?php esc_html_e( 'Publication Name', 'polylang-seo' ); ?></label></th>
                <td><input type="text" class="large-text" id="plseo_sitemap_news_publication" name="plseo_sitemap_news_publication" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'sitemap_news_publication', '' ) ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'News Post Types', 'polylang-seo' ); ?></th>
                <td>
                    <?php $news_types = (array) PLSEO_Helpers::get_option( 'sitemap_news_post_types', [ 'post' ] ); ?>
                    <?php foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) : ?>
                        <label class="plseo-check">
                            <input type="checkbox" name="plseo_sitemap_news_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $news_types, true ) ); ?> />
                            <span><?php echo esc_html( $post_type->label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'Video Sitemap', 'polylang-seo' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Generate /sitemap-video.xml for Google Video search. Posts must have _video_url or _video_embed_url meta fields.', 'polylang-seo' ); ?></p>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Enable Video Sitemap', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_videos" <?php checked( PLSEO_Helpers::get_option( 'sitemap_videos', false ) ); ?> /> <?php esc_html_e( 'Generate /sitemap-video.xml', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Video Post Types', 'polylang-seo' ); ?></th>
                <td>
                    <?php $video_types = (array) PLSEO_Helpers::get_option( 'sitemap_video_post_types', [ 'post', 'page', 'video' ] ); ?>
                    <?php foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) : ?>
                        <label class="plseo-check">
                            <input type="checkbox" name="plseo_sitemap_video_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $video_types, true ) ); ?> />
                            <span><?php echo esc_html( $post_type->label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'Multilingual SEO', 'polylang-seo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'hreflang Links', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_hreflang" <?php checked( PLSEO_Helpers::get_option( 'sitemap_hreflang', true ) ); ?> /> <?php esc_html_e( 'Add xhtml:link hreflang annotations in sitemap entries', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'x-default', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_x_default" <?php checked( PLSEO_Helpers::get_option( 'sitemap_x_default', true ) ); ?> /> <?php esc_html_e( 'Add x-default for the default Polylang language', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Per-Language Sitemaps', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_per_language" <?php checked( PLSEO_Helpers::get_option( 'sitemap_per_language', false ) ); ?> /> <?php esc_html_e( 'Generate separate sitemaps per language (e.g., /sitemap-en-posts.xml, /sitemap-fr-posts.xml)', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Detected Languages', 'polylang-seo' ); ?></th>
                <td>
                    <?php $langs = PLSEO_Helpers::get_languages(); ?>
                    <?php if ( ! empty( $langs ) ) : ?>
                        <?php foreach ( $langs as $slug => $language ) : ?>
                            <div><code><?php echo esc_html( $slug ); ?></code> <?php echo esc_html( $language['locale'] ); ?> &rarr; <code><?php echo esc_html( PLSEO_Helpers::locale_to_hreflang( $language['locale'] ) ); ?></code></div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <em><?php esc_html_e( 'Polylang not active or no languages configured.', 'polylang-seo' ); ?></em>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'Search Engine Notifications', 'polylang-seo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Ping Google', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_ping_google" <?php checked( PLSEO_Helpers::get_option( 'sitemap_ping_google', true ) ); ?> /> <?php esc_html_e( 'Notify Google after publishing content', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Ping Bing', 'polylang-seo' ); ?></th>
                <td><label><input type="checkbox" name="plseo_sitemap_ping_bing" <?php checked( PLSEO_Helpers::get_option( 'sitemap_ping_bing', true ) ); ?> /> <?php esc_html_e( 'Notify Bing after publishing content', 'polylang-seo' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'IndexNow', 'polylang-seo' ); ?></th>
                <td>
                    <label><input type="checkbox" name="plseo_sitemap_indexnow" <?php checked( PLSEO_Helpers::get_option( 'sitemap_indexnow', false ) ); ?> /> <?php esc_html_e( 'Use IndexNow protocol (instant indexing for Bing, Yandex, Seznam, Naver)', 'polylang-seo' ); ?></label>
                    <p class="description"><?php esc_html_e( 'Requires an API key below. The verification file is automatically served.', 'polylang-seo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="plseo_indexnow_api_key"><?php esc_html_e( 'IndexNow API Key', 'polylang-seo' ); ?></label></th>
                <td><input type="text" class="large-text" id="plseo_indexnow_api_key" name="plseo_indexnow_api_key" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'indexnow_api_key', '' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g., a1b2c3d4e5f6...', 'polylang-seo' ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="plseo_sitemap_posts_per_type"><?php esc_html_e( 'URLs Per Sitemap', 'polylang-seo' ); ?></label></th>
                <td>
                    <input type="number" id="plseo_sitemap_posts_per_type" name="plseo_sitemap_posts_per_type" min="50" max="2500" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'sitemap_posts_per_type', 1000 ) ); ?>" />
                    <p class="description"><?php esc_html_e( 'Maximum 2,500. Larger sites are automatically split into paginated sitemaps (e.g., sitemap-posts-2.xml).', 'polylang-seo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="plseo_sitemap_exclude_ids"><?php esc_html_e( 'Exclude Post IDs', 'polylang-seo' ); ?></label></th>
                <td><input type="text" class="large-text" id="plseo_sitemap_exclude_ids" name="plseo_sitemap_exclude_ids" value="<?php echo esc_attr( implode( ', ', (array) PLSEO_Helpers::get_option( 'sitemap_exclude_ids', [] ) ) ); ?>" /></td>
            </tr>
        </table>
    </div>

    <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'polylang-seo' ); ?></button></p>
</form>
</div>
