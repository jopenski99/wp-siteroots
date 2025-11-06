# wp-siteroots

## Plugin Overview — WP Siteroots

**Plugin Name:** WP Siteroots
**Version:** 1.8.0
**Author:** John Paul Perez
**Description:**
WP Siteroots is a WordPress plugin that creates JSON endpoints for site schema and structured data.
It provides a **Shopify-compatible schema bridge** for products, posts, pages, and custom post types — making it useful for WordPress-to-Shopify integrations or headless data sync setups.

---

##  Features

* Generates JSON-formatted sitemaps for:
  * Products (WooCommerce support included)
  * Posts
  * Pages
  * Custom post types
* Provides two custom endpoints:
  * `/sitemap-schema.json` — view live JSON schema
  * `/sitemap-schema-download.json` — downloadable JSON data
* Includes site metadata: name, URL, generation date
* Compatible with both **standard WordPress** and **WooCommerce**

---

## Installation

### Option 1: Upload via WordPress Admin

1. Download the ZIP file provided with this plugin (`wp-siteroots.zip` or similar).
2. In your WordPress dashboard, go to:
   **Plugins → Add New → Upload Plugin**
3. Upload the ZIP file and click **Install Now**.
4. After installation, click **Activate Plugin**.

### Option 2: Manual Installation (FTP / File Manager)

1. Unzip the provided file on your computer.
2. Upload the folder (e.g., `wp-siteroots`) to your site’s `/wp-content/plugins/` directory.
3. Go to **Plugins → Installed Plugins** and click **Activate**.

---

## Usage

Once activated:

1. Visit your site and append `/sitemap-schema.json` to the base URL, for example:

   <pre class="overflow-visible!" data-start="1768" data-end="1825"><div class="contain-inline-size rounded-2xl relative bg-token-sidebar-surface-primary"><div class="sticky top-9"><div class="absolute end-0 bottom-0 flex h-9 items-center pe-2"><div class="bg-token-bg-elevated-secondary text-token-text-secondary flex items-center gap-4 rounded-sm px-2 font-sans text-xs"></div></div></div><div class="overflow-y-auto p-4" dir="ltr"><code class="whitespace-pre!"><span><span>https://yourwebsite.com/sitemap-</span><span>schema</span><span>.json
   </span></span></code></div></div></pre>

   This will show the full structured JSON schema of your site.
2. To download the JSON schema file, use:

   <pre class="overflow-visible!" data-start="1937" data-end="2003"><div class="contain-inline-size rounded-2xl relative bg-token-sidebar-surface-primary"><div class="sticky top-9"><div class="absolute end-0 bottom-0 flex h-9 items-center pe-2"><div class="bg-token-bg-elevated-secondary text-token-text-secondary flex items-center gap-4 rounded-sm px-2 font-sans text-xs"></div></div></div><div class="overflow-y-auto p-4" dir="ltr"><code class="whitespace-pre!"><span><span>https://yourwebsite.com/sitemap-</span><span>schema</span><span>-download.json
   </span></span></code></div></div></pre>

You can integrate this endpoint into a Shopify store, app, or external service that needs WordPress content in JSON format.

---

## Technical Notes

* Hooks:
  * `init` — Registers custom rewrite rules and query vars.
* Uses WordPress functions:
  * `get_post_types`, `WP_Query`, `get_the_title`, `get_permalink`, etc.
* WooCommerce support:
  * Checks for `wc_get_product()` function before accessing product data.
* Output:
  * Returns schema in JSON format via `wp_send_json()` or downloadable JSON headers.

---

## Requirements

* WordPress 5.0 or later
* PHP 7.4 or later
* Optional: WooCommerce (for product schema support)

---

## License

This plugin is licensed under the **GPLv2 or later** license.

A simple plugin for wordpress to be able to generate sitemap in Schema form


| Feature                 | Description                                                      |
| ----------------------- | ---------------------------------------------------------------- |
| **Admin UI**            | You can now select which post types to include in the sitemap.   |
| **Caching**             | Uses WordPress transients to reduce DB load.                     |
| **Schema Detail**       | Adds author, publication dates, description, and featured image. |
| **Safe JSON Output**    | Uses`wp_send_json()`instead of manually setting headers.         |
| **Rewrite Management**  | Automatically flushes rewrite rules on plugin (de)activation.    |
| **Structured Metadata** | Includes`dateGenerated`,`baseUrl`, and language code.            |
