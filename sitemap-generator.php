<?php
/**
 * Plugin Name: Schema Sitemap Generator
 * Description: Generates a JSON-LD sitemap compatible with Shopify and search engines. Includes posts, pages, products, and detailed product variants.
 * Version: 1.5
 * Author: John Paul Perez
 */

if (!defined('ABSPATH')) exit;

/**
 * Generate JSON-LD Sitemap
 */
function ssg_generate_schema_sitemap_json() {
    // Get all published posts and pages
    $post_types = ['post', 'page'];
    if (class_exists('WooCommerce')) {
        $post_types[] = 'product';
    }

    $all_content = get_posts([
        'post_type'   => $post_types,
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'menu_order',
        'order'       => 'ASC'
    ]);

    // Define sitemap structure
    $site_name = get_bloginfo('name');
    $site_desc = get_bloginfo('description');
    $site_url  = home_url('/');

    $sitemap = [
        "@context" => "https://schema.org",
        "@type"    => "ItemList",
        "name"     => "$site_name Sitemap",
        "description" => $site_desc,
        "url"      => $site_url . "sitemap-schema.json",
        "numberOfItems" => count($all_content),
        "itemListElement" => []
    ];

    // Loop through posts and add to sitemap
    $position = 1;
    foreach ($all_content as $item) {
        $post_type  = get_post_type($item->ID);
        $post_url   = get_permalink($item->ID);
        $post_title = get_the_title($item->ID);
        $excerpt    = wp_strip_all_tags(get_the_excerpt($item->ID));
        $thumbnail  = get_the_post_thumbnail_url($item->ID, 'full');
        $modified   = get_the_modified_date(DATE_ATOM, $item->ID);
        $published  = get_the_date(DATE_ATOM, $item->ID);

        $entry = [
            "@type"        => "ListItem",
            "position"     => $position,
            "url"          => $post_url,
            "name"         => $post_title,
            "datePublished"=> $published,
            "dateModified" => $modified,
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id"   => $post_url
            ]
        ];

        if ($thumbnail) $entry["image"] = $thumbnail;
        if ($excerpt) $entry["description"] = $excerpt;

        // Add WooCommerce product schema
        if ($post_type === 'product' && class_exists('WC_Product')) {
            $product = wc_get_product($item->ID);

            if ($product) {
                $entry["@type"] = "Product";
                $entry["sku"] = $product->get_sku() ?: "SKU-" . $item->ID;
                $entry["brand"] = [
                    "@type" => "Brand",
                    "name" => $site_name
                ];
                $entry["category"] = implode(', ', wp_get_post_terms($item->ID, 'product_cat', ['fields' => 'names']));

                // Gallery images
                $gallery_ids = $product->get_gallery_image_ids();
                if (!empty($gallery_ids)) {
                    $entry["additionalImage"] = array_map('wp_get_attachment_url', $gallery_ids);
                }

                // Offers
                $offer = [
                    "@type" => "Offer",
                    "url" => $post_url,
                    "priceCurrency" => get_woocommerce_currency(),
                    "price" => $product->get_price(),
                    "availability" => $product->is_in_stock() 
                        ? "https://schema.org/InStock"
                        : "https://schema.org/OutOfStock",
                    "itemCondition" => "https://schema.org/NewCondition"
                ];

                $entry["offers"] = $offer;

                // Variants (variable products)
                if ($product->is_type('variable')) {
                    $variations = [];
                    foreach ($product->get_children() as $child_id) {
                        $variation = wc_get_product($child_id);
                        if ($variation) {
                            $variations[] = [
                                "@type" => "Product",
                                "sku" => $variation->get_sku() ?: "VAR-" . $child_id,
                                "name" => $variation->get_name(),
                                "price" => $variation->get_price(),
                                "priceCurrency" => get_woocommerce_currency(),
                                "availability" => $variation->is_in_stock()
                                    ? "https://schema.org/InStock"
                                    : "https://schema.org/OutOfStock",
                                "url" => get_permalink($child_id),
                                "attributes" => $variation->get_attributes()
                            ];
                        }
                    }
                    if (!empty($variations)) {
                        $entry["hasVariant"] = $variations;
                    }
                }
            }
        }

        $sitemap["itemListElement"][] = $entry;
        $position++;
    }

    return $sitemap;
}

/**
 * Register /sitemap-schema.json endpoint
 */
function ssg_register_rewrite_rule() {
    add_rewrite_rule('^sitemap-schema\.json/?$', 'index.php?schema_sitemap=1', 'top');
}
add_action('init', 'ssg_register_rewrite_rule', 0);

/**
 * Add custom query var
 */
add_filter('query_vars', function($vars) {
    $vars[] = 'schema_sitemap';
    return $vars;
});

/**
 * Handle sitemap output
 */
add_action('template_redirect', function() {
    if (get_query_var('schema_sitemap')) {
        status_header(200);
        nocache_headers();
        header('Content-Type: application/ld+json; charset=utf-8');

        echo json_encode(
            ssg_generate_schema_sitemap_json(),
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        exit;
    }
}, 0);

/**
 * Auto-flush rewrite rules
 */
register_activation_hook(__FILE__, function() {
    ssg_register_rewrite_rule();
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

