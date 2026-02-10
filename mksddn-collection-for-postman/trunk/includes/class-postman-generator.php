<?php

/**
 * @file: includes/class-postman-generator.php
 * @description: Build and export Postman Collection JSON and OpenAPI structure.
 * @dependencies: Postman_Options, Postman_Routes, Postman_OpenAPI_Converter
 * @created: 2025-08-19
 */
class Postman_Generator {

    private const COLLECTION_SCHEMA = 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json';


    private readonly Postman_Options $options_handler;

    private readonly Postman_Routes $routes_handler;


    public function __construct() {
        $this->options_handler = new Postman_Options();
        $this->routes_handler = new Postman_Routes();
    }


    public function generate_and_download(array $selected_page_slugs, array $selected_post_slugs, array $selected_custom_slugs, array $selected_options_pages, array $selected_category_slugs = [], array $selected_custom_post_types = [], bool $acf_for_pages_list = false, bool $acf_for_posts_list = false, array $acf_for_cpt_lists = [], bool $include_woocommerce = true, string $format = 'postman'): void {
        $post_types = get_post_types(['public' => true], 'objects');
        $custom_post_types = Postman_Routes::filter_custom_post_types($post_types);

        $collection = $this->build_collection($custom_post_types, $selected_page_slugs, $selected_category_slugs, $selected_custom_post_types, $acf_for_pages_list, $acf_for_posts_list, $acf_for_cpt_lists, $include_woocommerce);

        if ($format === 'openapi') {
            $this->download_openapi($collection);
        } else {
            $this->download_collection($collection);
        }
    }


    /**
     * Build collection and return as array without sending download headers.
     * Intended for programmatic usage (e.g., WP-CLI).
     */
    public function generate_collection_array(array $selected_page_slugs, array $selected_category_slugs = [], array $selected_custom_post_types = [], bool $include_woocommerce = true): array {
        $post_types = get_post_types(['public' => true], 'objects');
        $custom_post_types = Postman_Routes::filter_custom_post_types($post_types);
        $acf_active = Postman_Routes::is_acf_or_scf_active();
        return $this->build_collection(
            $custom_post_types,
            $selected_page_slugs,
            $selected_category_slugs,
            $selected_custom_post_types,
            $acf_active,
            $acf_active,
            $acf_active ? $selected_custom_post_types : [],
            $include_woocommerce
        );
    }


    private function build_collection(array $custom_post_types, array $selected_page_slugs, array $selected_category_slugs = [], array $selected_custom_post_types = [], bool $acf_for_pages_list = false, bool $acf_for_posts_list = false, array $acf_for_cpt_lists = [], bool $include_woocommerce = true): array {
        $items = [];

        // Basic Routes
        $items[] = [
            'name' => 'Basic Routes',
            'item' => $this->routes_handler->get_basic_routes($acf_for_pages_list, $acf_for_posts_list),
        ];

        // WooCommerce REST API (when active and selected)
        if ($include_woocommerce) {
            $wc_routes = $this->routes_handler->get_woocommerce_routes();
            if ($wc_routes !== []) {
                $items = array_merge($items, $wc_routes);
            }
        }

        // Options Pages
        $options_pages = $this->options_handler->get_options_pages();
        $options_pages_data = $this->options_handler->get_options_pages_data();

        if ($options_pages !== []) {
            $options_items = $this->routes_handler->get_options_routes($options_pages, $options_pages_data);
            if ($options_items !== []) {
                $items[] = [
                    'name' => 'Options Pages',
                    'item' => $options_items,
                ];
            }
        }

        // Custom post types - filter by selected if provided
        $filtered_custom_post_types = $custom_post_types;
        if (!empty($selected_custom_post_types)) {
            $filtered_custom_post_types = [];
            foreach ($selected_custom_post_types as $selected_type) {
                if (isset($custom_post_types[$selected_type])) {
                    $filtered_custom_post_types[$selected_type] = $custom_post_types[$selected_type];
                }
            }
        }
        $custom_routes = $this->routes_handler->get_custom_post_type_routes($filtered_custom_post_types, $acf_for_cpt_lists);
        $items = array_merge($items, $custom_routes);

        // Individual selected pages
        $individual_routes = $this->routes_handler->get_individual_page_routes($selected_page_slugs);
        $items = array_merge($items, $individual_routes);

        // Posts by selected categories
        $posts_by_categories = $this->routes_handler->get_posts_by_categories_routes($selected_category_slugs);
        if ($posts_by_categories !== []) {
            $items[] = [
                'name' => 'Posts by Categories',
                'item' => $posts_by_categories,
            ];
        }

        $collection = [
            'info' => $this->get_collection_info(),
            'item' => $items,
            'variable' => $this->routes_handler->get_variables($custom_post_types),
        ];

        /**
         * Filter the final Postman collection array before it is exported.
         *
         * @param array $collection         The full collection array.
         * @param array $custom_post_types  The discovered custom post types.
         * @param array $selected_page_slugs Selected page slugs.
         */
        return (array) apply_filters('mksddn_postman_collection', $collection, $custom_post_types, $selected_page_slugs);
    }


    private function get_collection_info(): array {
        return [
            'name' => get_bloginfo('name'),
            'schema' => self::COLLECTION_SCHEMA,
        ];
    }


    private function download_collection(array $collection): void {
        $json = wp_json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            wp_die(esc_html__('Failed to generate collection.', 'mksddn-collection-for-postman'));
        }

        /**
         * Filter the exported filename for the collection download.
         *
         * @param string $filename   Default filename.
         * @param array  $collection The collection array.
         */
        $filename = (string) apply_filters('mksddn_postman_filename', 'postman_collection.json', $collection);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
        header('Content-Length: ' . strlen($json));

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is a JSON string for download.
        echo $json;
        exit;
    }


    /**
     * Convert collection to OpenAPI 3.0 spec and send download headers.
     */
    private function download_openapi(array $collection): void {
        $converter = new Postman_OpenAPI_Converter();
        $spec = $converter->convert($collection);

        $json = wp_json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            wp_die(esc_html__('Failed to generate OpenAPI specification.', 'mksddn-collection-for-postman'));
        }

        $filename = (string) apply_filters('mksddn_postman_openapi_filename', 'openapi.json', $spec, $collection);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
        header('Content-Length: ' . strlen($json));

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is a JSON string for download.
        echo $json;
        exit;
    }


    /**
     * Generate OpenAPI 3.0 spec array from collection.
     * Intended for programmatic usage (e.g., WP-CLI).
     *
     * @param array $collection Postman collection array
     * @return array OpenAPI 3.0 specification
     */
    public function generate_openapi_array(array $collection): array {
        $converter = new Postman_OpenAPI_Converter();
        return $converter->convert($collection);
    }

}
