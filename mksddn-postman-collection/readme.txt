=== MksDdn Postman Collection ===
Contributors: mksddn
Tags: rest api, postman, collection, developer-tools
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate a Postman Collection (v2.1.0) for the WordPress REST API from the admin UI.

== Description ==
MksDdn Postman Collection helps developers quickly generate a Postman Collection (v2.1.0) for WordPress REST API endpoints. The plugin automatically discovers and includes standard WordPress entities, custom post types, options pages, and individual pages. Generated collections include pre-configured requests with sample data and can be downloaded as JSON files for import into Postman.

The plugin provides comprehensive API testing capabilities with automatic generation of test data for form submissions, support for file uploads via multipart/form-data, and seamless integration with Advanced Custom Fields (ACF). Special handling is included for the mksddn-forms-handler plugin when active.

Features:
- Basic REST endpoints: pages, posts, categories, tags, taxonomies, comments, users, settings
- Custom Post Types with full CRUD operations (List, Get by Slug/ID, Create, Update, Delete)
- Special handling for Forms (mksddn-forms-handler integration)
- Options endpoints: `/wp-json/custom/v1/options/...`
- Individual pages by slug with ACF field support
- Automatic test data generation for form submissions
- Support for multipart/form-data for file uploads
- Extensible via WordPress filters
- WP-CLI integration for command-line usage

== Installation ==
1. Upload the `mksddn-postman-collection` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In the admin sidebar, open “Postman Collection” to generate and download the collection.

== Frequently Asked Questions ==
= Does it support multisite? =
Yes. It works at the site level. Network-specific screens are not required.

= Does it require ACF? =
No. It supports ACF fields if present for pages and posts, but it does not require ACF to work.

= Is there a WP-CLI command? =
Yes. The plugin includes WP-CLI integration for command-line usage:

Export to file:
`wp mksddn-postman export --file=postman_collection.json`

Print to stdout:
`wp mksddn-postman export --pages=home,about`

Export with specific pages:
`wp mksddn-postman export --file=my_collection.json --pages=home,about,contact`

= Does it support ACF fields? =
Yes. The plugin automatically includes ACF field support in requests for pages and posts. When generating requests, it adds `acf_format=standard` parameter and includes ACF fields in the `_fields` parameter.

= Does it support file uploads in forms? =
Yes. When forms contain file fields, the plugin automatically generates multipart/form-data requests with proper file handling. For forms without files, it uses standard JSON requests.

= Can I customize the generated collection? =
Yes. The plugin provides WordPress filters for customization:
- `mksddn_postman_collection` - Modify the entire collection structure
- `mksddn_postman_filename` - Customize the download filename
- `mksddn_postman_capability` - Change required user capability

== Screenshots ==
1. Admin screen with page selection and download button.

== Changelog ==
= 1.0.0 =
Initial public release.

== Upgrade Notice ==
= 1.0.0 =
Initial public release. No migration steps are required.

