<?php
/*
Plugin Name: WP Siteroots
Plugin URI: https://example.com/wp-siteroots
Description: Schema + Shopify Data Bridge for WordPress. Provides JSON endpoints for products, posts, pages, and custom post types.
Version: 1.8.0
Author: John Paul Perez
License: GPLv2 or later
*/

if (!defined('ABSPATH')) exit;

/**
 * 🔹 Register rewrite rules for JSON endpoints
 */
add_action('init', function () {
    add_rewrite_rule('^sitemap-schema\.json$', 'index.php?schema_sitemap=1', 'top');
    add_rewrite_rule('^sitemap-schema-download\.json$', 'index.php?schema_sitemap_download=1', 'top');
    add_rewrite_tag('%schema_sitemap%', '1');
    add_rewrite_tag('%schema_sitemap_download%', '1');
});

/**
 * 🔹 Generate unified schema data
 */
function wp_siteroots_generate_schema_data() {
    $data = [
        'site' => get_bloginfo('name'),
        'url' => home_url(),
        'generated_at' => current_time('mysql'),
        'products' => [],
        'posts' => [],
        'pages' => [],
        'custom_types' => [],
    ];

    // Fetch all public post types (includes custom ones)
    $post_types = get_post_types(['public' => true], 'names');
    $query = new WP_Query([
        'post_type' => $post_types,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    foreach ($query->posts as $post) {
        $item = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'slug' => $post->post_name,
            'url' => get_permalink($post),
            'excerpt' => wp_strip_all_tags(get_the_excerpt($post)),
            'type' => $post->post_type,
            'date_published' => get_the_date('c', $post),
            'date_modified' => get_the_modified_date('c', $post),
            'featured_image' => get_the_post_thumbnail_url($post, 'full') ?: '',
        ];

        // 🔹 WooCommerce product fields
        if ($post->post_type === 'product' && function_exists('wc_get_product')) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $item['price'] = $product->get_price();
                $item['sku'] = $product->get_sku();
                $item['stock_status'] = $product->get_stock_status();
                $item['image'] = wp_get_attachment_url($product->get_image_id());
                $data['products'][] = $item;
                continue;
            }
        }

        // 🔹 Classify by type
        if ($post->post_type === 'post') {
            $data['posts'][] = $item;
        } elseif ($post->post_type === 'page') {
            $data['pages'][] = $item;
        } else {
            $data['custom_types'][] = $item;
        }
    }

    return $data;
}

/**
 * 🔹 Serve JSON and downloadable endpoints
 */
add_action('template_redirect', function () {
    if (get_query_var('schema_sitemap')) {
        status_header(200);
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(wp_siteroots_generate_schema_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (get_query_var('schema_sitemap_download')) {
        status_header(200);
        nocache_headers();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="schema-sitemap.json"');
        echo json_encode(wp_siteroots_generate_schema_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}, 0);
