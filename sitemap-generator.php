<?php
/**
 * Plugin Name: wp-siteroots - Schema Sitemap Generator
 * Plugin URI:  https://github.com/jopenski99/wp-siteroots
 * Description: Generates a JSON-LD sitemap of all WordPress posts, pages, and selected custom post types in Schema.org ItemList format.
 * Version:     1.2
 * Author:      John Paul Perez
 * Author URI:  https://jopenski99.github.io/axcelion/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: schema-sitemap
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

// Add custom rewrite rule
function ssg_register_rewrite_rule() {
    add_rewrite_rule('^sitemap-schema\.json$', 'index.php?schema_sitemap=1', 'top');
}
add_action('init', 'ssg_register_rewrite_rule');

// Generate sitemap (main function)
function ssg_generate_schema_sitemap_json() {
    $cache_key = 'ssg_sitemap_cache';
    $cached = get_transient($cache_key);

    if ($cached) {
        wp_send_json($cached, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    // Retrieve selected post types
    $options = get_option('ssg_settings', []);
    $selected_post_types = !empty($options['post_types']) ? $options['post_types'] : ['post', 'page'];

    $args = [
        'post_type'      => $selected_post_types,
        'post_status'    => 'publish',
        'numberposts'    => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'suppress_filters' => false,
    ];

    $all_content = get_posts($args);

    $sitemap = [
        "@context" => "https://schema.org",
        "@type"    => "ItemList",
        "name"     => get_bloginfo('name') . " Sitemap",
        "description" => get_bloginfo('description'),
        "baseUrl"  => home_url('/'),
        "dateGenerated" => current_time('c'),
        "itemListElement" => [],
    ];

    $position = 1;
    foreach ($all_content as $item) {
        $post_type = get_post_type_object($item->post_type);
        $image = get_the_post_thumbnail_url($item->ID, 'full');

        $entry = [
            "@type" => "ListItem",
            "position" => $position,
            "url" => get_permalink($item->ID),
            "name" => get_the_title($item->ID),
            "item" => [
                "@type" => ucfirst($item->post_type),
                "headline" => get_the_title($item->ID),
                "datePublished" => get_the_date('c', $item->ID),
                "dateModified" => get_the_modified_date('c', $item->ID),
                "author" => [
                    "@type" => "Person",
                    "name" => get_the_author_meta('display_name', $item->post_author),
                ],
                "description" => wp_strip_all_tags(get_the_excerpt($item->ID)),
                "url" => get_permalink($item->ID),
                "inLanguage" => get_locale(),
            ],
        ];

        if ($image) {
            $entry['item']['image'] = [
                "@type" => "ImageObject",
                "url" => $image,
            ];
        }

        $sitemap["itemListElement"][] = $entry;
        $position++;
    }

    // Cache for 6 hours
    set_transient($cache_key, $sitemap, 6 * HOUR_IN_SECONDS);

    wp_send_json($sitemap, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// Redirect to sitemap
function ssg_template_redirect() {
    if (get_query_var('schema_sitemap')) {
        ssg_generate_schema_sitemap_json();
    }
}
add_action('template_redirect', 'ssg_template_redirect');

// Flush rewrite rules
function ssg_flush_rewrite_rules() {
    ssg_register_rewrite_rule();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ssg_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

// Register admin menu
function ssg_register_admin_menu() {
    add_options_page(
        __('Schema Sitemap Settings', 'schema-sitemap'),
        __('Schema Sitemap', 'schema-sitemap'),
        'manage_options',
        'schema-sitemap',
        'ssg_render_settings_page'
    );
}
add_action('admin_menu', 'ssg_register_admin_menu');

// Register settings
function ssg_register_settings() {
    register_setting('ssg_settings_group', 'ssg_settings');

    add_settings_section(
        'ssg_main_section',
        __('General Settings', 'schema-sitemap'),
        '__return_false',
        'schema-sitemap'
    );

    add_settings_field(
        'ssg_post_types',
        __('Included Post Types', 'schema-sitemap'),
        'ssg_render_post_types_field',
        'schema-sitemap',
        'ssg_main_section'
    );
}
add_action('admin_init', 'ssg_register_settings');

// Render post types field
function ssg_render_post_types_field() {
    $post_types = get_post_types(['public' => true], 'objects');
    $options = get_option('ssg_settings', []);
    $selected = $options['post_types'] ?? ['post', 'page'];

    foreach ($post_types as $slug => $obj) {
        echo '<label>';
        echo '<input type="checkbox" name="ssg_settings[post_types][]" value="' . esc_attr($slug) . '" ' . checked(in_array($slug, $selected), true, false) . '> ';
        echo esc_html($obj->labels->name);
        echo '</label><br>';
    }
}

// Render settings page
function ssg_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Schema Sitemap Settings', 'schema-sitemap'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ssg_settings_group');
            do_settings_sections('schema-sitemap');
            submit_button(__('Save Settings', 'schema-sitemap'));
            ?>
        </form>
        <hr>
        <p><strong>Sitemap URL:</strong> <a href="<?php echo esc_url(home_url('/sitemap-schema.json')); ?>" target="_blank"><?php echo esc_url(home_url('/sitemap-schema.json')); ?></a></p>
    </div>
    <?php
}
