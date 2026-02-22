<?php

/**
 * @file: includes/class-postman-registered-routes.php
 * @description: Build Postman items from WordPress registered REST routes, grouped by namespace.
 * @dependencies: WP_REST_Server, Postman_Routes (for headers/descriptions)
 * @created: 2026-02-22
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Build "Registered Routes" folder and subfolders from rest_get_server()->get_routes().
 * Normalizes regex route paths to Postman-style :param, respects show_in_index, optional deduplication.
 */
class Postman_Registered_Routes {

    private const FOLDER_NAME = 'Registered Routes';

    /**
     * Namespaces to hide from "Add custom registered REST routes" (core, WooCommerce, block editor, etc.).
     */
    private const DEFAULT_HIDDEN_NAMESPACES = [
        'wp/v2',
        'wc/v3',
        'batch/v1',
        'wp-abilities/v1',
        'wp-block-editor/v1',
        'wp-site-health/v1',
    ];

    /**
     * Get available REST API namespaces from registered routes (on demand).
     * Respects show_in_index. Result can be cached by caller with short TTL.
     *
     * @return array<string> Sorted list of namespace strings (e.g. ['wp/v2', 'custom/v1']).
     */
    public static function get_available_namespaces(): array {
        $server = rest_get_server();
        if (!$server instanceof WP_REST_Server) {
            return [];
        }

        $routes = $server->get_routes();
        $namespaces = [];

        foreach ($routes as $pattern => $endpoints) {
            $endpoints = self::normalize_endpoints($endpoints);
            foreach ($endpoints as $endpoint) {
                if (isset($endpoint['show_in_index']) && $endpoint['show_in_index'] === false) {
                    continue;
                }
                $ns = self::get_namespace_from_route($pattern);
                if ($ns !== '' && !in_array($ns, $namespaces, true)) {
                    $namespaces[] = $ns;
                }
            }
        }

        sort($namespaces);
        return $namespaces;
    }

    /**
     * Get namespaces to show in "Add custom registered REST routes" block.
     * Excludes core (wp/v2), WooCommerce (wc/v3), and other WP internal (batch, wp-* namespaces).
     * Theme can restrict further via filter mksddn_postman_registered_routes_theme_namespaces.
     *
     * @return array<string> Sorted list of namespace strings.
     */
    public static function get_theme_or_custom_namespaces(): array {
        $all = self::get_available_namespaces();
        $candidates = array_values(array_filter($all, [self::class, 'is_custom_namespace']));
        sort($candidates);

        /**
         * Filter namespaces shown in "Add custom registered REST routes" (e.g. only theme-registered).
         *
         * @param array<string> $candidates Namespaces that are not core/internal.
         */
        $filtered = (array) apply_filters('mksddn_postman_registered_routes_theme_namespaces', $candidates);
        return array_values(array_unique(array_filter($filtered, 'is_string')));
    }

    /**
     * Whether the namespace should be shown as "custom" (exclude core and WP internal).
     */
    public static function is_custom_namespace(string $namespace): bool {
        if (in_array($namespace, self::DEFAULT_HIDDEN_NAMESPACES, true)) {
            return false;
        }
        $first = explode('/', $namespace)[0] ?? '';
        return $first !== '' && !str_starts_with($first, 'wp-');
    }

    /**
     * Build top-level folder "Registered Routes" with subfolders per namespace.
     * Only includes routes from $included_namespaces. Skips routes with show_in_index false.
     *
     * @param array<string> $included_namespaces Namespaces to include (only these appear in the folder).
     * @param array<string, array<string>> $existing_keys Map of normalized_path => list of methods to skip (deduplication). Optional.
     * @return array{name: string, item: array} Folder structure for collection item.
     */
    public static function get_registered_routes_folder(array $included_namespaces = [], array $existing_keys = []): array {
        $included_namespaces = array_values(array_filter($included_namespaces, [self::class, 'is_custom_namespace']));
        if ($included_namespaces === []) {
            return ['name' => self::FOLDER_NAME, 'item' => []];
        }

        $server = rest_get_server();
        if (!$server instanceof WP_REST_Server) {
            return ['name' => self::FOLDER_NAME, 'item' => []];
        }

        $routes = $server->get_routes();
        $by_namespace = [];
        $added = [];

        foreach ($routes as $pattern => $endpoints) {
            $endpoints = self::normalize_endpoints($endpoints);
            foreach ($endpoints as $endpoint) {
                if (isset($endpoint['show_in_index']) && $endpoint['show_in_index'] === false) {
                    continue;
                }
                $ns = self::get_namespace_from_route($pattern);
                if ($ns === '' || !in_array($ns, $included_namespaces, true)) {
                    continue;
                }
                $methods = self::endpoint_methods_to_list($endpoint);
                if ($methods === []) {
                    continue;
                }
                $readable_path = self::route_pattern_to_readable_path($pattern);
                $path_with_base = '/wp-json' . $readable_path;
                foreach ($methods as $method) {
                    if (isset($existing_keys[$path_with_base]) && in_array($method, $existing_keys[$path_with_base], true)) {
                        continue;
                    }
                    $key = $path_with_base . '::' . $method;
                    if (isset($added[$key])) {
                        continue;
                    }
                    $added[$key] = true;
                    if (!isset($by_namespace[$ns])) {
                        $by_namespace[$ns] = [];
                    }
                    $by_namespace[$ns][] = self::build_request_item($path_with_base, $method, $readable_path, $ns, $endpoint);
                }
            }
        }

        $folder_items = [];
        foreach ($by_namespace as $ns => $items) {
            $folder_items[] = [
                'name' => $ns,
                'item' => $items,
            ];
        }

        usort($folder_items, static function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return [
            'name' => self::FOLDER_NAME,
            'item' => $folder_items,
        ];
    }

    /**
     * Normalize route pattern to Postman-readable path: (?P<id>[\d]+) -> :id, keep static segments.
     */
    public static function route_pattern_to_readable_path(string $pattern): string {
        $path = trim($pattern, '/');
        $path = preg_replace_callback('/\(\?P<([^>]+)>[^)]+\)/', static function ($m) {
            return ':' . $m[1];
        }, $path);
        $path = preg_replace('/\([^)]+\)/', '', $path);
        $path = preg_replace('/#.*$/', '', $path);
        return '/' . trim($path, '/');
    }

    /**
     * Extract namespace from route pattern (e.g. /wp/v2/posts -> wp/v2).
     */
    public static function get_namespace_from_route(string $pattern): string {
        $parts = array_filter(explode('/', trim($pattern, '/')));
        if (count($parts) < 2) {
            return '';
        }
        return $parts[0] . '/' . $parts[1];
    }

    /**
     * WP_REST_Server method bitmask values (when handler is [ $callback, $bitmask ]).
     */
    private const METHOD_BITMASK_GET = 1;
    private const METHOD_BITMASK_POST = 2;
    private const METHOD_BITMASK_PUT = 4;
    private const METHOD_BITMASK_PATCH = 8;
    private const METHOD_BITMASK_DELETE = 16;

    /**
     * Ensure endpoints is always array of endpoint configs (each with 'methods' key).
     * Handles WordPress formats: normalized [ 'methods' => [...] ] and raw [ $callback, $bitmask ].
     *
     * @param mixed $endpoints
     * @return array<int, array>
     */
    private static function normalize_endpoints($endpoints): array {
        if (!is_array($endpoints)) {
            return [];
        }
        if (isset($endpoints['methods']) && !isset($endpoints[0])) {
            $endpoints = [$endpoints];
        }
        $list = [];
        foreach ($endpoints as $key => $e) {
            if (!is_array($e)) {
                continue;
            }
            $methods = self::endpoint_methods_to_list($e);
            if ($methods === [] && isset($e[1]) && is_numeric($e[1])) {
                $methods = self::bitmask_to_methods((int) $e[1]);
            }
            if ($methods !== []) {
                $list[] = array_merge($e, ['methods' => $methods]);
            }
        }
        return $list;
    }

    /**
     * Convert WP_REST_Server method bitmask to list of method names.
     *
     * @return array<string>
     */
    private static function bitmask_to_methods(int $bitmask): array {
        $map = [
            self::METHOD_BITMASK_GET    => 'GET',
            self::METHOD_BITMASK_POST   => 'POST',
            self::METHOD_BITMASK_PUT    => 'PUT',
            self::METHOD_BITMASK_PATCH  => 'PATCH',
            self::METHOD_BITMASK_DELETE => 'DELETE',
        ];
        $methods = [];
        foreach ($map as $bit => $method) {
            if (($bitmask & $bit) === $bit) {
                $methods[] = $method;
            }
        }
        return $methods;
    }

    /**
     * @param array $endpoint Endpoint config with 'methods' key (string, array, or associative method=>true).
     * @return array<string> Uppercase method names.
     */
    private static function endpoint_methods_to_list(array $endpoint): array {
        $methods = $endpoint['methods'] ?? [];
        if (is_string($methods)) {
            $methods = array_map('trim', explode(',', $methods));
        }
        if (is_array($methods) && !empty($methods) && array_keys($methods) !== range(0, count($methods) - 1)) {
            $methods = array_keys($methods);
        }
        $methods = array_filter(array_map('strtoupper', (array) $methods));
        $allowed = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        return array_values(array_intersect($methods, $allowed));
    }

    private static function build_request_item(string $path_with_base, string $method, string $readable_path, string $namespace, array $endpoint = []): array {
        $routes = new Postman_Routes();
        $default_headers = $routes->get_default_headers_for_registered_routes();
        $auth_headers = $routes->get_auth_headers_for_registered_routes();
        $needs_auth = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        $headers = $needs_auth ? array_merge($default_headers, $auth_headers) : $default_headers;

        $raw_url = '{{baseUrl}}' . $path_with_base;
        $path_parts = array_filter(explode('/', trim($path_with_base, '/')));

        $name = $method . ' ' . $readable_path;
        $description = sprintf(
            'Registered route: %s %s (namespace %s).',
            $method,
            $readable_path,
            $namespace
        );

        $request = [
            'method'      => $method,
            'header'      => $headers,
            'url'         => [
                'raw'   => $raw_url,
                'host'  => ['{{baseUrl}}'],
                'path'  => $path_parts,
            ],
            'description' => $description,
        ];

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $request['header'] = array_merge(
                $request['header'],
            if ($demo_body === []) {
                $demo_body = self::builtin_demo_body_for_path($path_with_base);
            }
                [['key' => 'Content-Type', 'value' => 'application/json']]
            );
            $demo_body = self::args_to_demo_body($endpoint['args'] ?? [], $readable_path);
            $raw_body = $demo_body !== [] ? wp_json_encode($demo_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
            /**
             * Filter demo body for registered route POST/PUT/PATCH (e.g. when endpoint has no args).
             *
             * @param array<string, mixed> $demo_body Example key-value for request body.
             * @param string               $path_with_base Full path e.g. /wp-json/contents/v1/forms/career.
             * @param string               $method        HTTP method.
             * @param string               $namespace     Namespace e.g. contents/v1.
             */
            $demo_body = (array) apply_filters('mksddn_postman_registered_route_demo_body', $demo_body, $path_with_base, $method, $namespace);
            $request['body'] = [
                'mode' => 'raw',
                'raw'  => wp_json_encode($demo_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ];
        }

        return [
            'name'    => $name,
            'request' => $request,
        ];
    }

    /**
     * Build demo request body from endpoint args (type, default, enum) for POST/PUT/PATCH.
     * Skips args that are path params (e.g. slug, id when path contains :slug, :id).
     *
     * @param array<string, array> $args Endpoint args from REST route registration.
     * @param string               $path Readable path (e.g. /contents/v1/forms/career).
     * @return array<string, mixed> Object suitable for JSON body.
     */
    private static function args_to_demo_body(array $args, string $path): array {
        $path_params = [];
        if (preg_match_all('/:(\w+)/', $path, $m)) {
            $path_params = array_flip($m[1]);
        }
        $body = [];
        foreach ($args as $key => $schema) {
            if (!is_array($schema) || isset($path_params[$key])) {
                continue;
            }
            $body[$key] = self::arg_default_value($schema);
        }
        return $body;
    }

    /**
     * Single arg schema to example value (string, number, boolean, array, object, default, enum).
     *
     * @param array $schema One entry from endpoint args.
     * @return mixed
     */
    private static function arg_default_value(array $schema) {
        if (array_key_exists('default', $schema)) {
            return $schema['default'];
        }
        if (isset($schema['enum']) && is_array($schema['enum']) && $schema['enum'] !== []) {
            return $schema['enum'][0];
        }
        $type = $schema['type'] ?? 'string';
        return match ($type) {
            'integer', 'number' => 0,
            'boolean' => false,
            'array' => [],
            'object' => (object) [],
            default => '',
        };
    }
}
