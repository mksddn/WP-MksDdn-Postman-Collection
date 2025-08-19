=== MksDdn Postman Collection ===
Contributors: mksddn
Tags: rest api, postman, collection, developer-tools
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate a Postman Collection (v2.1.0) for the WordPress REST API from the admin UI.

== Description ==
MksDdn Postman Collection helps developers quickly generate a Postman Collection for standard WordPress REST endpoints, custom post types, options endpoints and selected individual pages. The collection can be downloaded as a JSON file and imported into Postman.

Features:
- Basic REST endpoints: pages, posts, categories, tags, taxonomies, comments, users, settings
- Custom Post Types (with special handling for `forms`)
- Options endpoints: `/wp-json/custom/v1/options/...`
- Individual pages by slug

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
Yes. Use:
`wp mksddn-postman export --file=postman_collection.json`
or print to stdout:
`wp mksddn-postman export --pages=home,about`

== Screenshots ==
1. Admin screen with page selection and download button.

== Changelog ==
= 1.0.0 =
Initial public release.

== Upgrade Notice ==
= 1.0.0 =
Initial public release. No migration steps are required.

