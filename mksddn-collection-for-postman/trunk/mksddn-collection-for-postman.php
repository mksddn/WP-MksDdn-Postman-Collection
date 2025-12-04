<?php
/*
Plugin Name: MksDdn Collection for Postman
Plugin URI: https://github.com/mksddn/WP-MksDdn-Postman-Collection
Description: Generate Postman Collection (v2.1.0) for WordPress REST API from admin UI.
Version: 1.0.5
Author: mksddn
Author URI: https://github.com/mksddn
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 8.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: mksddn-collection-for-postman
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Plugin constants
define('POSTMAN_PLUGIN_VERSION', '1.0.5');
define('POSTMAN_PLUGIN_PATH', __DIR__);
define('POSTMAN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('POSTMAN_PLUGIN_TEXT_DOMAIN', 'mksddn-collection-for-postman');

// Autoloader for plugin classes
spl_autoload_register(function ($class): void {
    $prefix = '';
    $base_dir = POSTMAN_PLUGIN_PATH . '/includes/';

    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Load plugin text domain for translations
// Note: load_plugin_textdomain() not required for plugins hosted on WordPress.org since 4.6.

// Initialize plugin
add_action('init', function(): void {
    new Postman_Admin();
});

// Register WP-CLI commands if available
if (defined('WP_CLI') && WP_CLI) {
    // Register command namespace; subcommands are defined as class methods (e.g., `export`).
    WP_CLI::add_command('mksddn-collection-for-postman', 'Postman_CLI');
}
