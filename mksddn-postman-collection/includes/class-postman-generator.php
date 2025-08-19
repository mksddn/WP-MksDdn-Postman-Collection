<?php

class Postman_Generator {

    private const COLLECTION_SCHEMA = 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json';


    private readonly Postman_Options $options_handler;

    private readonly Postman_Routes $routes_handler;


    public function __construct() {
        $this->options_handler = new Postman_Options();
        $this->routes_handler = new Postman_Routes();
    }


    public function generate_and_download(array $selected_page_slugs, array $selected_post_slugs, array $selected_custom_slugs, array $selected_options_pages): void {
        $post_types = get_post_types(['public' => true], 'objects');
        $custom_post_types = $this->filter_custom_post_types($post_types);

        $collection = $this->build_collection($custom_post_types, $selected_page_slugs);

        $this->download_collection($collection);
    }


    /**
     * Build collection and return as array without sending download headers.
     * Intended for programmatic usage (e.g., WP-CLI).
     */
    public function generate_collection_array(array $selected_page_slugs): array {
        $post_types = get_post_types(['public' => true], 'objects');
        $custom_post_types = $this->filter_custom_post_types($post_types);
        return $this->build_collection($custom_post_types, $selected_page_slugs);
    }


    private function filter_custom_post_types(array $post_types): array {
        $custom_post_types = [];
        foreach ($post_types as $post_type) {
            if (!in_array($post_type->name, ['page', 'post', 'attachment'])) {
                $custom_post_types[$post_type->name] = $post_type;
            }
        }

        return $custom_post_types;
    }


    private function build_collection(array $custom_post_types, array $selected_page_slugs): array {
        $items = [];

        // Basic Routes
        $items[] = [
            'name' => 'Basic Routes',
            'item' => $this->routes_handler->get_basic_routes(),
        ];

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

        // Custom post types
        $custom_routes = $this->routes_handler->get_custom_post_type_routes($custom_post_types);
        $items = array_merge($items, $custom_routes);

        // Individual selected pages
        $individual_routes = $this->routes_handler->get_individual_page_routes($selected_page_slugs);
        $items = array_merge($items, $individual_routes);

        return [
            'info' => $this->get_collection_info(),
            'item' => $items,
            'variable' => $this->routes_handler->get_variables($custom_post_types),
        ];
    }


    private function get_collection_info(): array {
        return [
            'name' => get_bloginfo('name') . ' API',
            'schema' => self::COLLECTION_SCHEMA,
        ];
    }


    private function download_collection(array $collection): void {
        $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="postman_collection.json"');
        header('Content-Length: ' . strlen($json));

        echo $json;
        exit;
    }


}
