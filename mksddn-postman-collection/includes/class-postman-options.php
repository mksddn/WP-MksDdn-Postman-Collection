<?php

class Postman_Options {

    private const OPTIONS_API_ENDPOINT = '/custom/v1/options';

    private const OPTIONS_API_PATTERN = '/custom/v1/options/';

    private array $options_pages = [];

    private array $options_pages_data = [];

    private bool $is_loaded = false;


    public function get_options_pages(): array {
        $this->load_options_pages_if_needed();
        return $this->options_pages;
    }


    public function get_options_pages_data(): array {
        $this->load_options_pages_if_needed();
        return $this->options_pages_data;
    }


    private function load_options_pages_if_needed(): void {
        if ($this->is_loaded) {
            return;
        }

        $this->load_options_pages();
        $this->is_loaded = true;
    }


    private function load_options_pages(): void {
        $this->ensure_rest_server_loaded();

        $server = rest_get_server();
        $routes = $server->get_routes();

        $this->load_from_api($server);
        $this->load_from_routes_fallback($routes);
    }


    private function ensure_rest_server_loaded(): void {
        if (!class_exists('WP_REST_Server')) {
            require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-server.php';
        }
    }


    private function load_from_api($rest_server): void {
        $request = new WP_REST_Request('GET', self::OPTIONS_API_ENDPOINT);
        $response = $rest_server->dispatch($request);

        if ($response->get_status() !== 200) {
            return;
        }

        $options_data = $response->get_data();
        if (!is_array($options_data)) {
            return;
        }

        foreach ($options_data as $option) {
            if (isset($option['menu_slug'])) {
                $this->add_options_page($option);
            }
        }
    }


    private function load_from_routes_fallback(array $routes): void {
        if ($this->options_pages !== []) {
            return;
        }

        foreach (array_keys($routes) as $route) {
            if (!$this->is_options_route($route)) {
                continue;
            }

            $page_name = $this->extract_page_name_from_route($route);
            if ($page_name && $this->is_valid_page_name($page_name)) {
                $this->options_pages[] = $page_name;
            }
        }
    }


    private function is_options_route(string $route): bool {
        return str_contains($route, self::OPTIONS_API_ENDPOINT) &&
               str_starts_with($route, self::OPTIONS_API_PATTERN);
    }


    private function extract_page_name_from_route(string $route): ?string {
        // Extract parameter from regex
        if (preg_match('/\/custom\/v1\/options\/\(\?P<([^>]+)>[^)]+\)/', $route, $matches)) {
            return 'example-' . $matches[1];
        }

        $page_name = str_replace(self::OPTIONS_API_PATTERN, '', $route);
        if (empty($page_name)) {
            return null;
        }

        // Clean from regex and parameters
        $page_name = preg_replace('/\(\?P<[^>]+>[^)]+\)/', '', $page_name);
        $page_name = preg_replace('/\([^)]+\)/', '', (string) $page_name);

        return trim((string) $page_name, '/');
    }


    private function is_valid_page_name(string $page_name): bool {
        return $page_name !== '' &&
               $page_name !== '0' &&
               !preg_match('/[{}()\[\]]/', $page_name) &&
               !in_array($page_name, $this->options_pages);
    }


    private function add_options_page(array $option): void {
        $this->options_pages[] = $option['menu_slug'];
        $this->options_pages_data[$option['menu_slug']] = [
            'title' => $option['page_title'] ?? ucfirst(str_replace('-', ' ', $option['menu_slug'])),
            'slug'  => $option['menu_slug'],
        ];
    }


}
