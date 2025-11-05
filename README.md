# wp-siteroots

A simple plugin for wordpress to be able to generate sitemap in Schema form



| Feature                 | Description                                                      |
| ----------------------- | ---------------------------------------------------------------- |
| **Admin UI**            | You can now select which post types to include in the sitemap.   |
| **Caching**             | Uses WordPress transients to reduce DB load.                     |
| **Schema Detail**       | Adds author, publication dates, description, and featured image. |
| **Safe JSON Output**    | Uses`wp_send_json()`instead of manually setting headers.         |
| **Rewrite Management**  | Automatically flushes rewrite rules on plugin (de)activation.    |
| **Structured Metadata** | Includes`dateGenerated`,`baseUrl`, and language code.            |
