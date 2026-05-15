<?php
defined( 'ABSPATH' ) || exit;
include PLSEO_DIR . 'admin/views/header.php';
?>
<form method="post">
    <?php wp_nonce_field( 'plseo_settings' ); ?>
    <div class="plseo-card">
        <h2><?php esc_html_e( 'Structured Data (JSON-LD)', 'polylang-seo' ); ?></h2>
        <table class="form-table">
            <tr><th><?php esc_html_e( 'Enable Schema', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_schema_enabled" <?php checked( PLSEO_Helpers::get_option( 'schema_enabled', true ) ); ?> /> <?php esc_html_e( 'Output JSON-LD @graph schema data', 'polylang-seo' ); ?></label></td></tr>
            <tr>
                <th><label for="plseo_schema_org_type"><?php esc_html_e( 'Entity Type', 'polylang-seo' ); ?></label></th>
                <td>
                    <select id="plseo_schema_org_type" name="plseo_schema_org_type">
                        <option value="Organization" <?php selected( PLSEO_Helpers::get_option( 'schema_org_type', 'Organization' ), 'Organization' ); ?>><?php esc_html_e( 'Organization', 'polylang-seo' ); ?></option>
                        <option value="Person" <?php selected( PLSEO_Helpers::get_option( 'schema_org_type', 'Organization' ), 'Person' ); ?>><?php esc_html_e( 'Person (for personal brands/blogs)', 'polylang-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr><th><label for="plseo_schema_org_name"><?php esc_html_e( 'Name', 'polylang-seo' ); ?></label></th><td><input type="text" class="regular-text" id="plseo_schema_org_name" name="plseo_schema_org_name" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_org_name', '' ) ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" /></td></tr>
            <tr><th><label for="plseo_schema_org_url"><?php esc_html_e( 'URL', 'polylang-seo' ); ?></label></th><td><input type="url" class="large-text" id="plseo_schema_org_url" name="plseo_schema_org_url" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_org_url', '' ) ); ?>" placeholder="<?php echo esc_attr( home_url() ); ?>" /></td></tr>
            <tr><th><label for="plseo_schema_org_logo"><?php esc_html_e( 'Logo URL', 'polylang-seo' ); ?></label></th><td><input type="url" class="large-text" id="plseo_schema_org_logo" name="plseo_schema_org_logo" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_org_logo', '' ) ); ?>" /><p class="description"><?php esc_html_e( 'Leave empty to use the custom logo from Appearance > Customize.', 'polylang-seo' ); ?></p></td></tr>
            <tr>
                <th><label for="plseo_schema_article_type"><?php esc_html_e( 'Article Type', 'polylang-seo' ); ?></label></th>
                <td>
                    <select id="plseo_schema_article_type" name="plseo_schema_article_type">
                        <option value="BlogPosting" <?php selected( PLSEO_Helpers::get_option( 'schema_article_type', 'BlogPosting' ), 'BlogPosting' ); ?>><?php esc_html_e( 'BlogPosting (standard blogs)', 'polylang-seo' ); ?></option>
                        <option value="Article" <?php selected( PLSEO_Helpers::get_option( 'schema_article_type', 'BlogPosting' ), 'Article' ); ?>><?php esc_html_e( 'Article (general)', 'polylang-seo' ); ?></option>
                        <option value="NewsArticle" <?php selected( PLSEO_Helpers::get_option( 'schema_article_type', 'BlogPosting' ), 'NewsArticle' ); ?>><?php esc_html_e( 'NewsArticle (news sites)', 'polylang-seo' ); ?></option>
                        <option value="TechArticle" <?php selected( PLSEO_Helpers::get_option( 'schema_article_type', 'BlogPosting' ), 'TechArticle' ); ?>><?php esc_html_e( 'TechArticle (technical docs)', 'polylang-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="plseo_schema_contact_type"><?php esc_html_e( 'Contact Type', 'polylang-seo' ); ?></label></th>
                <td>
                    <select id="plseo_schema_contact_type" name="plseo_schema_contact_type">
                        <option value="" <?php selected( PLSEO_Helpers::get_option( 'schema_contact_type', '' ), '' ); ?>><?php esc_html_e( 'None', 'polylang-seo' ); ?></option>
                        <option value="customer service" <?php selected( PLSEO_Helpers::get_option( 'schema_contact_type', '' ), 'customer service' ); ?>><?php esc_html_e( 'Customer Service', 'polylang-seo' ); ?></option>
                        <option value="technical support" <?php selected( PLSEO_Helpers::get_option( 'schema_contact_type', '' ), 'technical support' ); ?>><?php esc_html_e( 'Technical Support', 'polylang-seo' ); ?></option>
                        <option value="sales" <?php selected( PLSEO_Helpers::get_option( 'schema_contact_type', '' ), 'sales' ); ?>><?php esc_html_e( 'Sales', 'polylang-seo' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Requires a phone number in Local Business below to output a ContactPoint.', 'polylang-seo' ); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'GEO & AI Optimization', 'polylang-seo' ); ?></h2>
        <p class="description"><?php esc_html_e( 'These settings optimize your schema for Generative Engine Optimization (GEO) — how AI systems like Google SGE, ChatGPT, and Perplexity understand your content.', 'polylang-seo' ); ?></p>
        <table class="form-table">
            <tr><th><?php esc_html_e( 'Breadcrumb Schema', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_schema_breadcrumb" <?php checked( PLSEO_Helpers::get_option( 'schema_breadcrumb', true ) ); ?> /> <?php esc_html_e( 'BreadcrumbList — helps AI understand site hierarchy', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Article Schema', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_schema_article" <?php checked( PLSEO_Helpers::get_option( 'schema_article', true ) ); ?> /> <?php esc_html_e( 'Article/BlogPosting with author, wordCount, keywords, and dateModified', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Search Action', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_schema_search_action" <?php checked( PLSEO_Helpers::get_option( 'schema_search_action', true ) ); ?> /> <?php esc_html_e( 'SearchAction — enables Google sitelinks search box', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Speakable', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_schema_speakable" <?php checked( PLSEO_Helpers::get_option( 'schema_speakable', true ) ); ?> /> <?php esc_html_e( 'SpeakableSpecification — identifies content sections optimized for voice search and AI assistants', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><?php esc_html_e( 'Site Navigation', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_schema_navigation" <?php checked( PLSEO_Helpers::get_option( 'schema_navigation', true ) ); ?> /> <?php esc_html_e( 'SiteNavigationElement — outputs your primary menu for AI site understanding', 'polylang-seo' ); ?></label></td></tr>
        </table>
    </div>

    <div class="plseo-card">
        <h2><?php esc_html_e( 'Local Business', 'polylang-seo' ); ?></h2>
        <table class="form-table">
            <tr><th><?php esc_html_e( 'Enable Local Business', 'polylang-seo' ); ?></th><td><label><input type="checkbox" name="plseo_schema_local_business" <?php checked( PLSEO_Helpers::get_option( 'schema_local_business', false ) ); ?> /> <?php esc_html_e( 'Output LocalBusiness schema with GeoCoordinates', 'polylang-seo' ); ?></label></td></tr>
            <tr><th><label for="plseo_schema_lb_type"><?php esc_html_e( 'Business Type', 'polylang-seo' ); ?></label></th>
                <td>
                    <select id="plseo_schema_lb_type" name="plseo_schema_lb_type">
                        <?php
                        $lb_types = [
                            'LocalBusiness', 'Restaurant', 'Store', 'MedicalBusiness', 'LegalService',
                            'FinancialService', 'RealEstateAgent', 'AutoRepair', 'Dentist', 'HealthClub',
                            'Hotel', 'ProfessionalService', 'SportsActivityLocation', 'BeautySalon',
                            'DayCare', 'EmergencyService', 'FoodEstablishment', 'GovernmentOffice',
                        ];
                        $current_type = PLSEO_Helpers::get_option( 'schema_lb_type', 'LocalBusiness' );
                        foreach ( $lb_types as $type ) :
                        ?>
                            <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $current_type, $type ); ?>><?php echo esc_html( $type ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr><th><label for="plseo_schema_lb_street"><?php esc_html_e( 'Street Address', 'polylang-seo' ); ?></label></th><td><input type="text" class="large-text" id="plseo_schema_lb_street" name="plseo_schema_lb_street" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_street', '' ) ); ?>" /></td></tr>
            <tr><th><label for="plseo_schema_lb_city"><?php esc_html_e( 'City', 'polylang-seo' ); ?></label></th><td><input type="text" class="regular-text" id="plseo_schema_lb_city" name="plseo_schema_lb_city" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_city', '' ) ); ?>" /></td></tr>
            <tr><th><label for="plseo_schema_lb_region"><?php esc_html_e( 'State / Region', 'polylang-seo' ); ?></label></th><td><input type="text" class="regular-text" id="plseo_schema_lb_region" name="plseo_schema_lb_region" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_region', '' ) ); ?>" /></td></tr>
            <tr><th><label for="plseo_schema_lb_postal"><?php esc_html_e( 'Postal Code', 'polylang-seo' ); ?></label></th><td><input type="text" class="regular-text" id="plseo_schema_lb_postal" name="plseo_schema_lb_postal" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_postal', '' ) ); ?>" /></td></tr>
            <tr><th><label for="plseo_schema_lb_country"><?php esc_html_e( 'Country', 'polylang-seo' ); ?></label></th><td><input type="text" class="regular-text" id="plseo_schema_lb_country" name="plseo_schema_lb_country" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_country', '' ) ); ?>" placeholder="US" /></td></tr>
            <tr><th><label for="plseo_schema_lb_phone"><?php esc_html_e( 'Phone', 'polylang-seo' ); ?></label></th><td><input type="text" class="regular-text" id="plseo_schema_lb_phone" name="plseo_schema_lb_phone" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_phone', '' ) ); ?>" placeholder="+1-555-123-4567" /></td></tr>
            <tr><th><label for="plseo_schema_lb_hours"><?php esc_html_e( 'Opening Hours', 'polylang-seo' ); ?></label></th><td><input type="text" class="large-text" id="plseo_schema_lb_hours" name="plseo_schema_lb_hours" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_hours', '' ) ); ?>" placeholder="Mo-Fr 09:00-17:00, Sa 10:00-14:00" /><p class="description"><?php esc_html_e( 'Comma-separated, e.g.: Mo-Fr 09:00-17:00, Sa 10:00-14:00', 'polylang-seo' ); ?></p></td></tr>
            <tr><th><label for="plseo_schema_lb_lat"><?php esc_html_e( 'Latitude', 'polylang-seo' ); ?></label></th><td><input type="text" class="regular-text" id="plseo_schema_lb_lat" name="plseo_schema_lb_lat" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_lat', '' ) ); ?>" placeholder="40.7128" /></td></tr>
            <tr><th><label for="plseo_schema_lb_lng"><?php esc_html_e( 'Longitude', 'polylang-seo' ); ?></label></th><td><input type="text" class="regular-text" id="plseo_schema_lb_lng" name="plseo_schema_lb_lng" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_lng', '' ) ); ?>" placeholder="-74.0060" /></td></tr>
            <tr><th><label for="plseo_schema_lb_price_range"><?php esc_html_e( 'Price Range', 'polylang-seo' ); ?></label></th><td><input type="text" class="regular-text" id="plseo_schema_lb_price_range" name="plseo_schema_lb_price_range" value="<?php echo esc_attr( (string) PLSEO_Helpers::get_option( 'schema_lb_price_range', '' ) ); ?>" placeholder="$$" /></td></tr>
        </table>
    </div>

    <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'polylang-seo' ); ?></button></p>
</form>
</div>
