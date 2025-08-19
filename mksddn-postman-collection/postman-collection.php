<?php
/*
Plugin Name: MksDdn Postman Collection
Plugin URI: https://github.com/mksddn/WP-MksDdn-Postman-Collection
Description: Generate Postman Collection (v2.1.0) for WordPress REST API from admin UI.
Version: 1.0.0
Author: mksddn
Author URI: https://github.com/mksddn
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 8.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: mksddn-postman-collection
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Plugin constants
define('POSTMAN_PLUGIN_VERSION', '1.0.0');
define('POSTMAN_PLUGIN_PATH', __DIR__);
define('POSTMAN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('POSTMAN_PLUGIN_TEXT_DOMAIN', 'mksddn-postman-collection');

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
add_action('plugins_loaded', function (): void {
    load_plugin_textdomain(
        POSTMAN_PLUGIN_TEXT_DOMAIN,
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

// Initialize plugin
add_action('init', function(): void {
    new Postman_Admin();
});

// Register WP-CLI commands if available
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('mksddn-postman', [new Postman_CLI(), 'export']);
}
