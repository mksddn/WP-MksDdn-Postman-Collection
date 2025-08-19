<?php
/*
Plugin Name: Postman Collection Admin
Description: Generate Postman collection for REST API via button in admin panel.
Version: 1.0
Author: mksddn
*/

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Plugin constants
define('POSTMAN_PLUGIN_VERSION', '1.0');
define('POSTMAN_PLUGIN_PATH', __DIR__);
define('POSTMAN_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class): void {
    $prefix = '';
    $base_dir = POSTMAN_PLUGIN_PATH . '/includes/';

    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize plugin
add_action('init', function(): void {
    new Postman_Admin();
});
