=== MksDdn Collection for Postman ===
Contributors: mksddn
Tags: rest api, postman, collection, openapi, swagger, api documentation, developer-tools
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate Postman Collection (v2.1.0) or OpenAPI 3.0 documentation for the WordPress REST API from the admin UI.

== Description ==
MksDdn Collection for Postman helps developers quickly generate a Postman Collection (v2.1.0) or OpenAPI 3.0 documentation for WordPress REST API endpoints. The plugin automatically discovers and includes standard WordPress entities, custom post types, options pages, and individual pages. Generated collections include pre-configured requests with sample data and can be downloaded as JSON files for import into Postman. OpenAPI spec can be used with Swagger UI, Redoc, or frontend code generators.

The plugin provides comprehensive API testing capabilities with automatic generation of test data for form submissions, support for file uploads via multipart/form-data, and seamless integration with Advanced Custom Fields (ACF). Special handling is included for the mksddn-forms-handler plugin when active.

Features:
- Basic REST endpoints: pages, posts, categories, tags, taxonomies, comments, users, settings
- WooCommerce REST API (wc/v3): products, product categories, orders with full CRUD when WooCommerce is active
- Search functionality: Posts, Pages, and All content types with customizable queries
- Custom Post Types with full CRUD operations (List, Get by Slug/ID, Create, Update, Delete)
- ACF fields support for lists: pages, posts, and Custom Post Types (optional per type)
- Special handling for Forms (mksddn-forms-handler integration)
- Options endpoints: `/wp-json/custom/v1/options/...`
- Individual pages by slug with ACF field support
- Automatic test data generation for form submissions
- Support for multipart/form-data for file uploads
- Yoast SEO integration (automatic yoast_head_json inclusion)
- Multilingual support with Accept-Language headers (Polylang priority)
- OpenAPI 3.0 export for API documentation (Swagger UI, Redoc)
- Extensible via WordPress filters
- WP-CLI integration for command-line usage

== Installation ==
1. Upload the `mksddn-collection-for-postman` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In the admin sidebar, open “Postman Collection” to generate and download the collection.

== Frequently Asked Questions ==
= Does it support multisite? =
Yes. It works at the site level. Network-specific screens are not required.

= Does it support WooCommerce? =
Yes. When WooCommerce is active, the plugin adds a WooCommerce folder with products, product categories, and orders endpoints. Set collection variables wcConsumerKey and wcConsumerSecret (from WooCommerce > Settings > Advanced > REST API).

= Does it require ACF? =
No. It supports ACF fields if present for pages and posts, but it does not require ACF to work.

= Is there a WP-CLI command? =
Yes. The plugin includes WP-CLI integration for command-line usage:

Postman export:
`wp mksddn-collection-for-postman export --file=postman_collection.json`
`wp mksddn-collection-for-postman export --pages=home,about --categories=news --cpt=product`

OpenAPI export:
`wp mksddn-collection-for-postman export-openapi --file=openapi.json`
`wp mksddn-collection-for-postman export-openapi --pages=home,about`

= Does it support ACF fields? =
Yes. The plugin automatically includes ACF field support in requests for individual pages and posts. Additionally, you can optionally enable ACF fields for list endpoints (pages list, posts list, and Custom Post Types lists) through the admin interface. When generating requests, it adds `acf_format=standard` parameter and includes ACF fields in the `_fields` parameter.

= Does it support file uploads in forms? =
Yes. When forms contain file fields, the plugin automatically generates multipart/form-data requests with proper file handling. For forms without files, it uses standard JSON requests.

= Can I export OpenAPI documentation? =
Yes. Select "OpenAPI 3.0 (JSON)" in the admin export format options, or use WP-CLI: `wp mksddn-collection-for-postman export-openapi --file=openapi.json`. The generated spec is compatible with Swagger UI, Redoc, and code generators.

= Can I customize the generated collection? =
Yes. The plugin provides WordPress filters for customization:
- `mksddn_postman_collection` - Modify the entire collection structure
- `mksddn_postman_filename` - Customize the Postman download filename
- `mksddn_postman_openapi_spec` - Modify OpenAPI specification before export
- `mksddn_postman_openapi_filename` - Customize OpenAPI download filename
- `mksddn_postman_capability` - Change required user capability

== External services ==

This plugin references external services for Postman Collection schema validation:

**Postman Collection Schema Service**
- **Service**: Postman Collection Schema (schema.getpostman.com)
- **Purpose**: Used to validate and structure the generated Postman Collection JSON according to the official Postman Collection v2.1.0 specification
- **Data sent**: No data is sent to this service. The plugin only references the schema URL for validation purposes
- **When**: The schema URL is included in the generated collection metadata for Postman to validate the collection structure
- **Terms of service**: https://www.postman.com/legal/terms-of-use/
- **Privacy policy**: https://www.postman.com/legal/privacy-policy/

Note: This plugin does not send any user data to external services. The schema reference is purely for collection structure validation within the Postman application.

== Screenshots ==
1. Admin screen with page selection and download button.

== Changelog ==
= 1.2.1 =
- New: WooCommerce REST API support (products, product categories, orders)
- New: Admin checkbox to include/exclude WooCommerce routes
- New: Basic Auth (Consumer Key/Secret) for WooCommerce endpoints in Postman and OpenAPI

= 1.2.0 =
- New: OpenAPI 3.0 export for API documentation (Swagger UI, Redoc)
- New: Export format selection in admin (Postman / OpenAPI)
- New: WP-CLI command `export-openapi` for OpenAPI spec generation
- New: Filter `mksddn_postman_openapi_spec` for OpenAPI customization
- New: Filter `mksddn_postman_openapi_filename` for OpenAPI filename
- New: OpenAPI schemas for WP entities (Post, Page, Term, User, Comment)

= 1.1.0 =
- New: Custom Post Types support with full CRUD operations (List, Get by Slug/ID, Create, Update, Delete)
- New: Admin UI section for selecting Custom Post Types to include in collection
- New: ACF fields support for lists of pages, posts, and Custom Post Types
- New: Dynamic ACF checkboxes that appear/hide based on selected Custom Post Types

= 1.0.5 =
- Updated: Tested up to WordPress 6.9
- Verified compatibility with WordPress 6.9 changes (UTF-8 modernization, REST API stability)

= 1.0.4 =
- New: Category selection in admin to generate requests for posts by selected categories
- New: "Posts by Categories" folder in the collection

= 1.0.3 =
- Fixed WordPress.org Plugin Review compliance issues
- Replaced inline JavaScript with proper wp_enqueue_scripts usage
- Added documentation for external services (Postman Collection Schema)
- Fixed direct core file loading by using WordPress REST API functions
- Added proper escaping for output variables in JSON generation
- Improved code security and WordPress Coding Standards compliance

= 1.0.2 =
- Added Yoast SEO integration: automatic inclusion of yoast_head_json in _fields parameter for pages and posts
- Enhanced REST API requests to include SEO metadata when Yoast SEO plugin is active
- Added Accept-Language header support for multilingual sites with Polylang priority
- Implemented automatic language detection from Polylang settings or WordPress locale
- Added Search functionality with three search types: Posts, Pages, and All content
- Enhanced all GET requests with proper Accept-Language headers for internationalization
- Improved documentation with comprehensive multilingual and SEO support details

= 1.0.1 =
- Changed plugin name from "MksDdn Postman Collection" to "MksDdn Collection for Postman"
- Updated plugin slug from "mksddn-postman-collection" to "mksddn-collection-for-postman"
- Updated WP-CLI command from "mksddn-postman" to "mksddn-collection-for-postman"
- Updated text domain and language files

= 1.0.0 =
Initial public release.
