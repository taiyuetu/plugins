<?php
defined( 'ABSPATH' ) || exit;
include PLSEO_DIR . 'admin/views/header.php';
?>
<form method="post">
    <?php wp_nonce_field( 'plseo_settings' ); ?>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'Indexing Rules', 'polylang-seo' ); ?></h2>
        <table class="form-table">
            <tr><th><?php esc_html_e( 'Noindex Search Pages', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_noindex_search" <?php checked( PLSEO_Helpers::get_option( 'noindex_search', true ) ); ?> /> <?php esc_html_e( 'Prevent search result pages from being indexed', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Noindex 404 Pages', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_noindex_404" <?php checked( PLSEO_Helpers::get_option( 'noindex_404', true ) ); ?> /> <?php esc_html_e( 'Prevent 404 pages from being indexed', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Noindex Author Archives', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_noindex_author" <?php checked( PLSEO_Helpers::get_option( 'noindex_author', false ) ); ?> /> <?php esc_html_e( 'Prevent author archive indexing', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Noindex Date Archives', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_noindex_date" <?php checked( PLSEO_Helpers::get_option( 'noindex_date', true ) ); ?> /> <?php esc_html_e( 'Prevent date archive indexing', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Noindex Attachments', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_noindex_attachment" <?php checked( PLSEO_Helpers::get_option( 'noindex_attachment', true ) ); ?> /> <?php esc_html_e( 'Prevent attachment pages from being indexed', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Noindex Empty Taxonomies', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_noindex_empty_taxonomy" <?php checked( PLSEO_Helpers::get_option( 'noindex_empty_taxonomy', true ) ); ?> /> <?php esc_html_e( 'Noindex taxonomy pages with zero posts', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Noindex Paginated Pages', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_noindex_subpages" <?php checked( PLSEO_Helpers::get_option( 'noindex_subpages', false ) ); ?> /> <?php esc_html_e( 'Noindex archive pages beyond page 1 (page/2/, page/3/, etc.)', 'polylang-seo' ); ?></label></td></tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'SERP Appearance (Advanced Robots)', 'polylang-seo' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Control how Google displays your pages in search results. These directives help you get richer snippets with large images and longer text.', 'polylang-seo' ); ?></p>
        <table class="form-table">
            <tr>
                <th><label for="plseo_robots_max_snippet"><?php esc_html_e( 'Max Snippet', 'polylang-seo' ); ?></label></th>
                <td>
                    <select id="plseo_robots_max_snippet" name="plseo_robots_max_snippet">
                        <option value="-1" <?php selected( PLSEO_Helpers::get_option( 'robots_max_snippet', '-1' ), '-1' ); ?>><?php esc_html_e( 'No limit (recommended)', 'polylang-seo' ); ?></option>
                        <option value="0" <?php selected( PLSEO_Helpers::get_option( 'robots_max_snippet', '-1' ), '0' ); ?>><?php esc_html_e( 'No snippet', 'polylang-seo' ); ?></option>
                        <option value="160" <?php selected( PLSEO_Helpers::get_option( 'robots_max_snippet', '-1' ), '160' ); ?>><?php esc_html_e( '160 characters', 'polylang-seo' ); ?></option>
                        <option value="320" <?php selected( PLSEO_Helpers::get_option( 'robots_max_snippet', '-1' ), '320' ); ?>><?php esc_html_e( '320 characters', 'polylang-seo' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Maximum number of characters for the text snippet in search results.', 'polylang-seo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="plseo_robots_max_image_preview"><?php esc_html_e( 'Max Image Preview', 'polylang-seo' ); ?></label></th>
                <td>
                    <select id="plseo_robots_max_image_preview" name="plseo_robots_max_image_preview">
                        <option value="large" <?php selected( PLSEO_Helpers::get_option( 'robots_max_image_preview', 'large' ), 'large' ); ?>><?php esc_html_e( 'Large (recommended for rich results)', 'polylang-seo' ); ?></option>
                        <option value="standard" <?php selected( PLSEO_Helpers::get_option( 'robots_max_image_preview', 'large' ), 'standard' ); ?>><?php esc_html_e( 'Standard', 'polylang-seo' ); ?></option>
                        <option value="none" <?php selected( PLSEO_Helpers::get_option( 'robots_max_image_preview', 'large' ), 'none' ); ?>><?php esc_html_e( 'None', 'polylang-seo' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Maximum size of image previews shown in search results. "Large" enables Google Discover eligibility.', 'polylang-seo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="plseo_robots_max_video_preview"><?php esc_html_e( 'Max Video Preview', 'polylang-seo' ); ?></label></th>
                <td>
                    <select id="plseo_robots_max_video_preview" name="plseo_robots_max_video_preview">
                        <option value="-1" <?php selected( PLSEO_Helpers::get_option( 'robots_max_video_preview', '-1' ), '-1' ); ?>><?php esc_html_e( 'No limit (recommended)', 'polylang-seo' ); ?></option>
                        <option value="0" <?php selected( PLSEO_Helpers::get_option( 'robots_max_video_preview', '-1' ), '0' ); ?>><?php esc_html_e( 'No video preview', 'polylang-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="plseo_robots_googlebot"><?php esc_html_e( 'Googlebot Directive', 'polylang-seo' ); ?></label></th>
                <td><input type="text" class="large-text" id="plseo_robots_googlebot" name="plseo_robots_googlebot" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'robots_googlebot', '' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g., max-snippet:-1, max-image-preview:large', 'polylang-seo' ); ?>" />
                <p class="description"><?php esc_html_e( 'Optional: Googlebot-specific meta robots directive (overrides the general directive for Google only).', 'polylang-seo' ); ?></p></td>
            </tr>
            <tr>
                <th><label for="plseo_robots_bingbot"><?php esc_html_e( 'Bingbot Directive', 'polylang-seo' ); ?></label></th>
                <td><input type="text" class="large-text" id="plseo_robots_bingbot" name="plseo_robots_bingbot" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'robots_bingbot', '' ) ); ?>" /></td>
            </tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'AI Crawler Management (GEO)', 'polylang-seo' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Control how AI training crawlers access your content. This applies to robots.txt directives.', 'polylang-seo' ); ?></p>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Block All AI Bots', 'polylang-seo' ); ?></th>
                <td>
                    <label><input type="checkbox" name="plseo_robots_block_ai_bots" <?php checked( PLSEO_Helpers::get_option( 'robots_block_ai_bots', false ) ); ?> /> <?php esc_html_e( 'Block GPTBot, ChatGPT-User, Google-Extended, CCBot, anthropic-ai, Claude-Web, Bytespider, PerplexityBot, and others', 'polylang-seo' ); ?></label>
                    <p class="description"><?php esc_html_e( 'Prevents AI training crawlers from using your content. Does NOT affect regular search engine indexing.', 'polylang-seo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="plseo_robots_ai_bots_custom"><?php esc_html_e( 'Custom AI Bot Rules', 'polylang-seo' ); ?></label></th>
                <td>
                    <textarea class="large-text code" rows="6" id="plseo_robots_ai_bots_custom" name="plseo_robots_ai_bots_custom" placeholder="User-agent: GPTBot&#10;Allow: /blog/&#10;Disallow: /"><?php echo esc_textarea( (string) PLSEO_Helpers::get_option( 'robots_ai_bots_custom', '' ) ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Granular AI bot rules. Only used when "Block All AI Bots" is unchecked. Use standard robots.txt syntax.', 'polylang-seo' ); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'Canonical & Redirects', 'polylang-seo' ); ?></h2>
        <table class="form-table">
            <tr><th><?php esc_html_e( 'Enable Canonical URLs', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_canonical_enabled" <?php checked( PLSEO_Helpers::get_option( 'canonical_enabled', true ) ); ?> /> <?php esc_html_e( 'Output canonical tags', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Force HTTPS', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_canonical_force_https" <?php checked( PLSEO_Helpers::get_option( 'canonical_force_https', true ) ); ?> /> <?php esc_html_e( 'Normalize canonicals to HTTPS', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Trailing Slash', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_canonical_trailing_slash" <?php checked( PLSEO_Helpers::get_option( 'canonical_trailing_slash', true ) ); ?> /> <?php esc_html_e( 'Normalize canonicals with a trailing slash', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Strip Query Parameters', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_canonical_strip_params" <?php checked( PLSEO_Helpers::get_option( 'canonical_strip_params', true ) ); ?> /> <?php esc_html_e( 'Remove tracking parameters from canonicals', 'polylang-seo' ); ?></label></td></tr>
            <tr>
                <th><label for="plseo_hreflang_x_default"><?php esc_html_e( 'x-default Strategy', 'polylang-seo' ); ?></label></th>
                <td>
                    <select id="plseo_hreflang_x_default" name="plseo_hreflang_x_default">
                        <option value="default_language" <?php selected( PLSEO_Helpers::get_option( 'hreflang_x_default', 'default_language' ), 'default_language' ); ?>><?php esc_html_e( 'Default language version', 'polylang-seo' ); ?></option>
                        <option value="none" <?php selected( PLSEO_Helpers::get_option( 'hreflang_x_default', 'default_language' ), 'none' ); ?>><?php esc_html_e( 'Do not output x-default', 'polylang-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr><th><?php esc_html_e( 'Redirect 404 to Home', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_redirect_404_home" <?php checked( PLSEO_Helpers::get_option( 'redirect_404_home', false ) ); ?> /> <?php esc_html_e( '301 redirect all 404 pages to homepage', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Keep Old Slug Redirects', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_redirect_old_slugs" <?php checked( PLSEO_Helpers::get_option( 'redirect_old_slugs', true ) ); ?> /> <?php esc_html_e( 'Use WordPress old-slug redirects', 'polylang-seo' ); ?></label></td></tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'robots.txt', 'polylang-seo' ); ?></h2>
        <p class="description"><?php esc_html_e( 'The plugin generates a professional robots.txt automatically. Add any extra rules below.', 'polylang-seo' ); ?></p>
        <table class="form-table">
            <tr>
                <th><label for="plseo_robots_crawl_delay"><?php esc_html_e( 'Crawl Delay', 'polylang-seo' ); ?></label></th>
                <td>
                    <input type="number" id="plseo_robots_crawl_delay" name="plseo_robots_crawl_delay" min="0" max="30" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'robots_crawl_delay', '' ) ); ?>" placeholder="<?php esc_attr_e( 'None', 'polylang-seo' ); ?>" />
                    <p class="description"><?php esc_html_e( 'Seconds between requests. Leave empty for no delay (recommended for most sites).', 'polylang-seo' ); ?></p>
                </td>
            </tr>
        </table>
        <label for="plseo_robots_txt_additions"><strong><?php esc_html_e( 'Custom Rules', 'polylang-seo' ); ?></strong></label>
        <textarea class="large-text code" rows="8" id="plseo_robots_txt_additions" name="plseo_robots_txt_additions" placeholder="# Your custom rules here"><?php echo esc_textarea( (string) PLSEO_Helpers::get_option( 'robots_txt_additions', '' ) ); ?></textarea>
    </div>

    <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'polylang-seo' ); ?></button></p>
</form>
</div>
