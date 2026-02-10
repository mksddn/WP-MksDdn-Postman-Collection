<?php

/**
 * @file: includes/class-postman-cli.php
 * @description: WP-CLI commands to export Postman Collection JSON and OpenAPI spec.
 * @dependencies: Postman_Generator
 * @created: 2025-08-19
 */
class Postman_CLI {

    /**
     * Export Postman collection to a file or STDOUT.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : Output file path. If omitted, prints to STDOUT.
     *
     * [--pages=<slugs>]
     * : Comma-separated page slugs to include as individual requests.
     *
     * [--categories=<slugs>]
     * : Comma-separated category slugs for posts by categories.
     *
     * [--cpt=<types>]
     * : Comma-separated custom post types to include.
     *
     * [--include-woocommerce=<yes|no>]
     * : Include WooCommerce REST API when active. Default: yes.
     *
     * ## EXAMPLES
     *     wp mksddn-collection-for-postman export --file=postman_collection.json
     *     wp mksddn-collection-for-postman export --pages=home,about --include-woocommerce=no
     */
    public function export(array $args, array $assoc_args): void {
        $page_slugs = $this->parse_slugs($assoc_args['pages'] ?? '');
        $category_slugs = $this->parse_slugs($assoc_args['categories'] ?? '');
        $cpt = $this->parse_cpt($assoc_args['cpt'] ?? '');
        $include_woocommerce = $this->parse_include_woocommerce($assoc_args['include-woocommerce'] ?? 'yes');

        $generator = new Postman_Generator();
        $collection = $generator->generate_collection_array($page_slugs, $category_slugs, $cpt, $include_woocommerce);

        $json = wp_json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            WP_CLI::error('Failed to encode collection.');
            return;
        }

        if (isset($assoc_args['file']) && $assoc_args['file'] !== '') {
            $file = (string) $assoc_args['file'];
            $result = file_put_contents($file, $json);
            if ($result === false) {
                WP_CLI::error('Failed to write file.');
                return;
            }
            WP_CLI::success('Collection exported to ' . $file);
            return;
        }

        WP_CLI::line($json);
    }


    /**
     * Export OpenAPI 3.0 specification to a file or STDOUT.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : Output file path. If omitted, prints to STDOUT.
     *
     * [--pages=<slugs>]
     * : Comma-separated page slugs to include as individual requests.
     *
     * [--categories=<slugs>]
     * : Comma-separated category slugs for posts by categories.
     *
     * [--cpt=<types>]
     * : Comma-separated custom post types to include.
     *
     * [--include-woocommerce=<yes|no>]
     * : Include WooCommerce REST API when active. Default: yes.
     *
     * ## EXAMPLES
     *     wp mksddn-collection-for-postman export-openapi --file=openapi.json
     *     wp mksddn-collection-for-postman export-openapi --pages=home,about --include-woocommerce=no
     */
    public function export_openapi(array $args, array $assoc_args): void {
        $page_slugs = $this->parse_slugs($assoc_args['pages'] ?? '');
        $category_slugs = $this->parse_slugs($assoc_args['categories'] ?? '');
        $cpt = $this->parse_cpt($assoc_args['cpt'] ?? '');
        $include_woocommerce = $this->parse_include_woocommerce($assoc_args['include-woocommerce'] ?? 'yes');

        $generator = new Postman_Generator();
        $collection = $generator->generate_collection_array($page_slugs, $category_slugs, $cpt, $include_woocommerce);
        $spec = $generator->generate_openapi_array($collection);

        $json = wp_json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            WP_CLI::error('Failed to encode OpenAPI spec.');
            return;
        }

        if (isset($assoc_args['file']) && $assoc_args['file'] !== '') {
            $file = (string) $assoc_args['file'];
            $result = file_put_contents($file, $json);
            if ($result === false) {
                WP_CLI::error('Failed to write file.');
                return;
            }
            WP_CLI::success('OpenAPI spec exported to ' . $file);
            return;
        }

        WP_CLI::line($json);
    }


    private function parse_include_woocommerce(string $param): bool {
        return strtolower($param) !== 'no' && strtolower($param) !== '0';
    }


    private function parse_slugs(string $param): array {
        if ($param === '') {
            return [];
        }
        return array_values(array_filter(array_map('sanitize_title', explode(',', $param))));
    }


    private function parse_cpt(string $param): array {
        if ($param === '') {
            return [];
        }
        return array_values(array_filter(array_map('sanitize_key', explode(',', $param))));
    }
}


