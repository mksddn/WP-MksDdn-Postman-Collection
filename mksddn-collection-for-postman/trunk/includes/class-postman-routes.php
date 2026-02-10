<?php

/**
 * @file: includes/class-postman-routes.php
 * @description: Build request items for Postman Collection (core entities, CPTs, options, pages).
 * @dependencies: WordPress REST API
 * @created: 2025-08-19
 */
class Postman_Routes {


    /**
     * Check if Yoast SEO plugin is active.
     *
     * @return bool
     */
    private function is_yoast_active(): bool {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return function_exists('is_plugin_active') && is_plugin_active('wordpress-seo/wp-seo.php');
    }


    /**
     * Check if WooCommerce plugin is active.
     *
     * @return bool
     */
    private function is_woocommerce_active(): bool {
        return class_exists('WooCommerce');
    }


    /**
     * Check if Polylang plugin is active.
     *
     * @return bool
     */
    private function is_polylang_active(): bool {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return function_exists('is_plugin_active') && is_plugin_active('polylang/polylang.php');
    }


    /**
     * Get default language from Polylang settings or WordPress locale.
     *
     * @return string
     */
    private function get_default_language(): string {
        // Check if Polylang is active and get default language from its settings
        if ($this->is_polylang_active() && function_exists('pll_default_language')) {
            $polylang_lang = pll_default_language();
            if ($polylang_lang) {
                return $polylang_lang;
            }
        }
        
        // Fallback to WordPress locale
        return get_locale();
    }


    /**
     * Get _fields parameter value for pages and posts, including Yoast if active.
     *
     * @return string
     */
    private function get_fields_param(): string {
        $fields = 'id,slug,title';
        if ($this->is_yoast_active()) {
            $fields .= ',yoast_head_json';
        }
        return $fields;
    }


    /**
     * Get extended _fields parameter value for posts, including additional fields.
     *
     * @return string
     */
    private function get_posts_fields_param(): string {
        $fields = 'id,slug,title,date,status,excerpt,featured_media,sticky,categories,tags';
        return $fields;
    }


    /**
     * Get _fields parameter value for categories, including additional fields.
     *
     * @return string
     */
    private function get_categories_fields_param(): string {
        return 'id,count,description,name,slug,taxonomy,parent,thumbnail,acf,meta';
    }


    /**
     * Get detailed _fields parameter value for pages and posts, including ACF and Yoast if active.
     *
     * @return string
     */
    private function get_detailed_fields_param(): string {
        $fields = 'title,acf,content';
        if ($this->is_yoast_active()) {
            $fields .= ',yoast_head_json';
        }
        return $fields;
    }


    /**
     * Get _fields parameter value for search requests, including featured image.
     *
     * @return string
     */
    private function get_search_fields_param(): string {
        return 'id,slug,title,excerpt,featured_media';
    }


    /**
     * Get default headers for GET requests, including Accept-Language (disabled by default).
     *
     * @return array
     */
    private function get_default_headers(): array {
        $language = $this->get_default_language();
        $accept_language = str_replace('_', '-', $language);

        $headers = [
            [
                'key'      => 'Accept-Language',
                'value'    => $accept_language,
                'disabled' => true,
            ],
        ];
        Postman_Param_Descriptions::enrich_headers($headers);
        return $headers;
    }


    /**
     * Get pagination query parameters (page and per_page) for list requests.
     * Per https://developer.wordpress.org/rest-api/using-the-rest-api/pagination/
     *
     * @return array
     */
    private function get_pagination_params(): array {
        $params = [
            ['key' => 'page', 'value' => '1', 'disabled' => true],
            ['key' => 'per_page', 'value' => '10', 'disabled' => true],
            ['key' => 'offset', 'value' => '0', 'disabled' => true],
        ];
        Postman_Param_Descriptions::enrich_query_params($params);
        return $params;
    }


    /**
     * Get WP REST API global params for list/collection endpoints.
     * Per https://developer.wordpress.org/rest-api/using-the-rest-api/global-parameters/
     *
     * @param string $entity Entity type (posts, pages, categories, tags, comments, users).
     * @return array
     */
    private function get_wp_list_extra_params(string $entity): array {
        $params = [
            [
                'key'       => 'context',
                'value'     => 'view',
                'disabled'  => true,
            ],
            [
                'key'       => 'search',
                'value'     => '',
                'disabled'  => true,
            ],
            [
                'key'       => 'order',
                'value'     => 'desc',
                'disabled'  => true,
            ],
            [
                'key'       => 'orderby',
                'value'     => 'date',
                'disabled'  => true,
            ],
            [
                'key'       => '_embed',
                'value'     => '',
                'disabled'  => true,
            ],
        ];
        if (in_array($entity, ['posts', 'pages'], true)) {
            array_splice($params, 2, 0, [[
                'key'      => 'status',
                'value'   => 'publish',
                'disabled' => true,
            ]]);
        }
        Postman_Param_Descriptions::enrich_query_params($params);
        return $params;
    }


    /**
     * Get WP REST API global params for single item GET (by slug/ID).
     *
     * @return array
     */
    private function get_wp_get_extra_params(): array {
        $params = [
            ['key' => 'context', 'value' => 'view', 'disabled' => true],
            ['key' => '_embed', 'value' => '', 'disabled' => true],
        ];
        Postman_Param_Descriptions::enrich_query_params($params);
        return $params;
    }


    /**
     * Get WP REST API params for DELETE (force=true bypasses Trash).
     * Per https://developer.wordpress.org/rest-api/reference/posts/#delete-a-post
     *
     * @return array
     */
    private function get_wp_delete_params(): array {
        $params = [
            ['key' => 'force', 'value' => 'false', 'disabled' => true],
        ];
        Postman_Param_Descriptions::enrich_query_params($params);
        return $params;
    }


    /**
     * Build search query params with descriptions. Type: post, page, or null for all.
     */
    private function build_search_query(?string $type): array {
        $params = [
            ['key' => 'search', 'value' => 'example'],
            ['key' => '_fields', 'value' => $this->get_search_fields_param()],
            ['key' => 'context', 'value' => 'view', 'disabled' => true],
            ['key' => '_embed', 'value' => '', 'disabled' => true],
        ];
        if ($type !== null) {
            array_unshift($params, ['key' => 'type', 'value' => $type]);
        }
        Postman_Param_Descriptions::enrich_query_params($params);
        return $params;
    }


    /**
     * Get auth headers for POST/PUT/PATCH/DELETE. X-WP-Nonce for same-origin.
     * Per https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/
     *
     * @return array
     */
    private function get_auth_headers(): array {
        $headers = [
            ['key' => 'X-WP-Nonce', 'value' => '{{wpNonce}}', 'disabled' => true],
        ];
        Postman_Param_Descriptions::enrich_headers($headers);
        return $headers;
    }


    public function get_basic_routes(bool $acf_for_pages_list = false, bool $acf_for_posts_list = false): array {
        $basic_routes = [];

        $standard_entities = [
            'pages'      => 'Page',
            'posts'      => 'Post',
            'categories' => 'Category',
            'tags'       => 'Tag',
            'comments'   => 'Comment',
            'users'      => 'User',
            'settings'   => 'Setting',
        ];

        foreach ($standard_entities as $entity => $singular) {
            $plural = $entity;
            $folder_items = [];

            // List
            $query_params = [];
            
            // Add entity-specific query parameters
            if ($entity === 'posts') {
                $fields_param = $this->get_posts_fields_param();
                if ($acf_for_posts_list) {
                    $fields_param .= ',acf';
                    $query_params[] = [
                        'key'   => 'acf_format',
                        'value' => 'standard',
                    ];
                }
                $query_params[] = [
                    'key'   => '_fields',
                    'value' => $fields_param,
                ];
                $query_params[] = [
                    'key'      => 'categories',
                    'value'    => '1',
                    'disabled' => true,
                ];
            } elseif ($entity === 'categories') {
                $query_params[] = [
                    'key'   => '_fields',
                    'value' => $this->get_categories_fields_param(),
                ];
            } elseif (in_array($entity, ['pages'], true)) {
                $fields_param = $this->get_fields_param();
                if ($acf_for_pages_list) {
                    $fields_param .= ',acf';
                    $query_params[] = [
                        'key'   => 'acf_format',
                        'value' => 'standard',
                    ];
                }
                $query_params[] = [
                    'key'   => '_fields',
                    'value' => $fields_param,
                ];
            }
            
            if ($entity !== 'settings') {
                $query_params = array_merge($query_params, $this->get_wp_list_extra_params($entity));
                $query_params = array_merge($query_params, $this->get_pagination_params());
            }
            Postman_Param_Descriptions::enrich_query_params($query_params);

            // Build raw URL
            $raw_url = '{{baseUrl}}/wp-json/wp/v2/' . $entity;
            if ($entity === 'posts') {
                $fields_param = $this->get_posts_fields_param();
                if ($acf_for_posts_list) {
                    $fields_param .= ',acf';
                    $raw_url = sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s&acf_format=standard&categories=1&page=1&per_page=10', $entity, $fields_param);
                } else {
                    $raw_url = sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s&categories=1&page=1&per_page=10', $entity, $fields_param);
                }
            } elseif ($entity === 'categories') {
                $raw_url = sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s&page=1&per_page=10', $entity, $this->get_categories_fields_param());
            } elseif (in_array($entity, ['pages'], true)) {
                $fields_param = $this->get_fields_param();
                if ($acf_for_pages_list) {
                    $fields_param .= ',acf';
                    $raw_url = sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s&acf_format=standard&page=1&per_page=10', $entity, $fields_param);
                } else {
                    $raw_url = sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s&page=1&per_page=10', $entity, $fields_param);
                }
            } elseif ($entity !== 'settings') {
                $raw_url = '{{baseUrl}}/wp-json/wp/v2/' . $entity . '?page=1&per_page=10';
            }
            
            $folder_items[] = [
                'name'    => 'List of ' . ucfirst($plural),
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => $raw_url,
                        'host'  => ['{{baseUrl}}'],
                        'path'  => ['wp-json', 'wp', 'v2', $entity],
                        'query' => $query_params,
                    ],
                    'description' => 'Get list of all ' . $plural,
                ],
            ];

            // Get by Slug
            $slug_query = array_merge(
                in_array($entity, ['pages', 'posts'])
                    ? [
                        ['key' => 'slug', 'value' => ($entity === 'pages' ? 'sample-page' : 'hello-world')],
                        ['key' => 'acf_format', 'value' => 'standard'],
                        ['key' => '_fields', 'value' => $this->get_detailed_fields_param()],
                    ]
                    : ($entity === 'categories'
                        ? [
                            ['key' => 'slug', 'value' => 'uncategorized'],
                            ['key' => 'parent', 'value' => '1', 'disabled' => true],
                        ]
                        : [['key' => 'slug', 'value' => 'example']]),
                $this->get_wp_get_extra_params()
            );
            Postman_Param_Descriptions::enrich_query_params($slug_query);
            $folder_items[] = [
                'name'    => $singular . ' by Slug',
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => (
                            in_array($entity, ['pages', 'posts'], true)
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s?slug=', $entity) . ($entity === 'pages' ? 'sample-page' : 'hello-world') . '&acf_format=standard&_fields=' . $this->get_detailed_fields_param()
                            : sprintf('{{baseUrl}}/wp-json/wp/v2/%s?slug=', $entity) . ($entity === 'categories' ? 'uncategorized' : 'example')
                        ),
                        'host'  => ['{{baseUrl}}'],
                        'path'  => ['wp-json', 'wp', 'v2', $entity],
                        'query' => $slug_query,
                    ],
                    'description' => sprintf('Get specific %s by slug', $singular) . (in_array($entity, ['pages', 'posts']) ? ' with ACF fields' : ''),
                ],
            ];

            // Get by ID
            $id_query = array_merge(
                in_array($entity, ['pages', 'posts'])
                    ? [
                        ['key' => 'acf_format', 'value' => 'standard'],
                        ['key' => '_fields', 'value' => $this->get_detailed_fields_param()],
                    ]
                    : ($entity === 'categories'
                        ? [['key' => 'parent', 'value' => '1', 'disabled' => true]]
                        : []),
                $this->get_wp_get_extra_params()
            );
            Postman_Param_Descriptions::enrich_query_params($id_query);
            $folder_items[] = [
                'name'    => $singular . ' by ID',
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => (
                            in_array($entity, ['pages', 'posts'])
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $entity) . $singular . 'ID}}?acf_format=standard&_fields=' . $this->get_detailed_fields_param()
                            : sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $entity) . $singular . 'ID}}'
                        ),
                        'host'  => ['{{baseUrl}}'],
                        'path'  => ['wp-json', 'wp', 'v2', $entity, '{{' . $singular . 'ID}}'],
                        'query' => $id_query,
                    ],
                    'description' => sprintf('Get specific %s by ID', $singular) . (in_array($entity, ['pages', 'posts']) ? ' with ACF fields' : ''),
                ],
            ];

            // Create
            $folder_items[] = [
                'name'    => 'Create ' . $singular,
                'request' => [
                    'method'      => 'POST',
                    'header'      => array_merge(
                        $this->get_auth_headers(),
                        [['key' => 'Content-Type', 'value' => 'application/json']]
                    ),
                    'body'        => [
                        'mode' => 'raw',
                        'raw'  => wp_json_encode(
                            [
                                'title'   => 'Sample ' . $singular . ' Title',
                                'content' => 'Sample ' . $singular . ' content here.',
                                'excerpt' => 'Sample ' . $singular . ' excerpt.',
                                'status'  => 'draft',
                            ],
                            JSON_PRETTY_PRINT
                        ),
                    ],
                    'url'         => [
                        'raw'  => '{{baseUrl}}/wp-json/wp/v2/' . $entity,
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $entity],
                    ],
                    'description' => 'Create new ' . $singular,
                ],
            ];

            // Update
            $folder_items[] = [
                'name'    => 'Update ' . $singular,
                'request' => [
                    'method'      => 'POST',
                    'header'      => array_merge(
                        $this->get_auth_headers(),
                        [['key' => 'Content-Type', 'value' => 'application/json']]
                    ),
                    'body'        => [
                        'mode' => 'raw',
                        'raw'  => wp_json_encode(
                            [
                                'title'   => 'Updated ' . $singular . ' Title',
                                'content' => 'Updated ' . $singular . ' content here.',
                                'excerpt' => 'Updated ' . $singular . ' excerpt.',
                            ],
                            JSON_PRETTY_PRINT
                        ),
                    ],
                    'url'         => [
                        'raw'  => sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $entity) . $singular . 'ID}}',
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $entity, '{{' . $singular . 'ID}}'],
                    ],
                    'description' => sprintf('Update existing %s by ID', $singular),
                ],
            ];

            // Delete
            $folder_items[] = [
                'name'    => 'Delete ' . $singular,
                'request' => [
                    'method'      => 'DELETE',
                    'header'      => $this->get_auth_headers(),
                    'url'         => [
                        'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $entity) . $singular . 'ID}}',
                        'host'  => ['{{baseUrl}}'],
                        'path'  => ['wp-json', 'wp', 'v2', $entity, '{{' . $singular . 'ID}}'],
                        'query' => $this->get_wp_delete_params(),
                    ],
                    'description' => sprintf('Delete %s by ID. Add ?force=true to bypass Trash.', $singular),
                ],
            ];

            $basic_routes[] = [
                'name' => ucfirst($plural),
                'item' => $folder_items,
            ];
        }

        // Add Search route
        $basic_routes[] = [
            'name' => 'Search',
            'item' => [
                [
                    'name'    => 'Search Posts',
                    'request' => [
                        'method'      => 'GET',
                        'header'      => $this->get_default_headers(),
                        'url'         => [
                            'raw'   => '{{baseUrl}}/wp-json/wp/v2/search?search=example&type=post&_fields=' . $this->get_search_fields_param(),
                            'host'  => ['{{baseUrl}}'],
                            'path'  => ['wp-json', 'wp', 'v2', 'search'],
                            'query' => $this->build_search_query('post'),
                        ],
                        'description' => 'Search for posts with keyword "example"',
                    ],
                ],
                [
                    'name'    => 'Search Pages',
                    'request' => [
                        'method'      => 'GET',
                        'header'      => $this->get_default_headers(),
                        'url'         => [
                            'raw'   => '{{baseUrl}}/wp-json/wp/v2/search?search=example&type=page&_fields=' . $this->get_search_fields_param(),
                            'host'  => ['{{baseUrl}}'],
                            'path'  => ['wp-json', 'wp', 'v2', 'search'],
                            'query' => $this->build_search_query('page'),
                        ],
                        'description' => 'Search for pages with keyword "example"',
                    ],
                ],
                [
                    'name'    => 'Search All',
                    'request' => [
                        'method'      => 'GET',
                        'header'      => $this->get_default_headers(),
                        'url'         => [
                            'raw'   => '{{baseUrl}}/wp-json/wp/v2/search?search=example&_fields=' . $this->get_search_fields_param(),
                            'host'  => ['{{baseUrl}}'],
                            'path'  => ['wp-json', 'wp', 'v2', 'search'],
                            'query' => $this->build_search_query(null),
                        ],
                        'description' => 'Search across all content types with keyword "example"',
                    ],
                ],
            ],
        ];

        return $basic_routes;
    }


    public function get_options_routes(array $options_pages, array $options_pages_data): array {
        if ($options_pages === []) {
            return [];
        }

        $options_items = [];
        $has_real_pages = false;

        // Add ALL Options Pages (not just selected ones)
        foreach ($options_pages as $page_slug) {
            $display_name = $options_pages_data[$page_slug]['title'] ?? ucfirst(str_replace('-', ' ', $page_slug));
            $is_example = str_starts_with((string) $page_slug, 'example-');

            // Skip example routes
            if ($is_example) {
                continue;
            }

            $has_real_pages = true;
            $options_items[] = [
                'name'    => $display_name,
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'  => '{{baseUrl}}/wp-json/custom/v1/options/' . $page_slug,
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'custom', 'v1', 'options', $page_slug],
                    ],
                    'description' => 'Get options for ' . $display_name,
                ],
            ];
        }

        // Only add List of Options Pages if there are real pages
        if ($has_real_pages) {
            array_unshift($options_items, [
                'name'    => 'List of Options Pages',
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'  => '{{baseUrl}}/wp-json/custom/v1/options',
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'custom', 'v1', 'options'],
                    ],
                    'description' => 'Get list of all available options pages',
                ],
            ]);
        }

        return $options_items;
    }


    public function get_custom_post_type_routes(array $custom_post_types, array $acf_for_cpt_lists = []): array {
        $custom_routes = [];

        foreach ($custom_post_types as $post_type_name => $post_type_obj) {
            $type_label = $post_type_obj->labels->name ?? ucfirst((string) $post_type_name);
            $singular_label = $post_type_obj->labels->singular_name ?? ucfirst((string) $post_type_name);
            $folder_items = [];

            // Get rest_base for post type (if exists)
            $rest_base = empty($post_type_obj->rest_base) ? $post_type_name : $post_type_obj->rest_base;

            // Check if ACF should be added for this CPT list
            $add_acf = in_array($post_type_name, $acf_for_cpt_lists, true);

            // Special handling for Forms (handler CPT only)
            $is_forms_post_type = ($post_type_name === 'mksddn_fh_forms');
            if ($is_forms_post_type) {
                // Force human-friendly folder name regardless of CPT labels
                $type_label = 'Forms';
                $folder_items = $this->get_forms_routes($rest_base, $type_label);
            } else {
                $folder_items = $this->get_standard_custom_post_type_routes($post_type_name, $rest_base, $singular_label, $add_acf);
            }

            // Skip adding folder if there are no items (e.g., forms when handler plugin is inactive)
            if ($folder_items !== []) {
                $custom_routes[] = [
                    'name' => $type_label,
                    'item' => $folder_items,
                ];
            }
        }

        return $custom_routes;
    }


    private function get_forms_routes(string $rest_base, string $type_label): array {
        $folder_items = [];

        // Detect if MksDdn Forms Handler is active; if not, return no routes for forms
        $use_handler_namespace = false;
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (function_exists('is_plugin_active') && is_plugin_active('mksddn-forms-handler/mksddn-forms-handler.php')) {
            $use_handler_namespace = true;
        } else {
            return [];
        }

        // List for Forms
        $query_params = $this->get_pagination_params();
        
        $folder_items[] = [
            'name'    => 'List of ' . $type_label,
            'request' => [
                'method'      => 'GET',
                'header'      => $this->get_default_headers(),
                'url'         => [
                    'raw'   => '{{baseUrl}}/wp-json/mksddn-forms-handler/v1/forms/?page=1&per_page=10',
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'mksddn-forms-handler', 'v1', 'forms'],
                    'query' => $query_params,
                ],
                'description' => 'Get list of all ' . $type_label,
            ],
        ];

        // Add ALL Forms (not just selected ones)
        $forms = get_posts([
            'post_type'      => 'mksddn_fh_forms',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ]);

        foreach ($forms as $form) {
            $slug = $form->post_name;
            $form_title = $form->post_title;

            // Get form fields config
            $fields_config = get_post_meta($form->ID, '_fields_config', true);
            $fields = json_decode($fields_config, true);
            $body_fields = [];
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    if (!is_array($field) || empty($field['name'])) {
                        continue;
                    }
                    $name = (string) $field['name'];
                    $body_fields[$name] = $this->generate_sample_value_for_field($field);
                }
            }

            // Decide headers for submit request based on presence of file fields
            $submit_headers = $this->has_file_field($fields)
                ? []
                : [[
                    'key'   => 'Content-Type',
                    'value' => 'application/json',
                ]];

            $folder_items[] = [
                'name' => $form_title,
                'item' => [
                    [
                        'name'    => 'Get Form Info - ' . $form_title,
                        'request' => [
                            'method'      => 'GET',
                            'header'      => $this->get_default_headers(),
                            'url'         => [
                                'raw'  => sprintf('{{baseUrl}}/wp-json/mksddn-forms-handler/v1/forms/%s', $slug),
                                'host' => ['{{baseUrl}}'],
                                'path' => ['wp-json', 'mksddn-forms-handler', 'v1', 'forms', $slug],
                            ],
                            'description' => sprintf("Get form info for '%s'", $form_title),
                        ],
                    ],
                    [
                        'name'    => 'Submit Form - ' . $form_title,
                        'request' => [
                            'method'      => 'POST',
                            'header'      => $submit_headers,
                            'body'        => $this->build_form_submit_body($body_fields, $fields),
                            'url'         => [
                                'raw'  => sprintf('{{baseUrl}}/wp-json/mksddn-forms-handler/v1/forms/%s/submit', $slug),
                                'host' => ['{{baseUrl}}'],
                                'path' => ['wp-json', 'mksddn-forms-handler', 'v1', 'forms', $slug, 'submit'],
                            ],
                            'description' => sprintf("Submit form data for '%s'", $form_title),
                        ],
                    ],
                ],
            ];
        }

        return $folder_items;
    }


    /**
     * Generate sample value for a form field based on its config.
     *
     * @param array $field
     * @return mixed
     */
    private function generate_sample_value_for_field(array $field)
    {
        $type = isset($field['type']) ? (string) $field['type'] : 'text';
        $multiple = !empty($field['multiple']);

        switch ($type) {
            case 'text':
                return 'Sample Text';
            case 'email':
                return 'test@example.com';
            case 'password':
                return 'P@ssw0rd123';
            case 'tel':
                return '+1234567890';
            case 'url':
                return 'https://example.com';
            case 'number':
                $min = isset($field['min']) && is_numeric($field['min']) ? (float) $field['min'] : null;
                $max = isset($field['max']) && is_numeric($field['max']) ? (float) $field['max'] : null;
                $step = isset($field['step']) && is_numeric($field['step']) ? (float) $field['step'] : 1.0;
                $value = 42.0;
                if ($min !== null && $max !== null) {
                    $value = $min;
                } elseif ($min !== null) {
                    $value = $min;
                } elseif ($max !== null) {
                    $value = $max;
                }
                // Align to step if possible
                if ($step > 0) {
                    $value = floor($value / $step) * $step;
                }
                // Prefer int when step is integer
                return fmod($step, 1.0) === 0.0 ? (int) $value : $value;
            case 'date':
                return gmdate('Y-m-d');
            case 'time':
                return gmdate('H:i');
            case 'datetime-local':
                return gmdate('Y-m-d\TH:i');
            case 'textarea':
                return 'Sample message text.';
            case 'checkbox':
                return '1';
            case 'radio':
                $options = isset($field['options']) ? $field['options'] : [];
                $values = $this->extract_option_values($options);
                return $values[0] ?? 'option';
            case 'select':
                $options = isset($field['options']) ? $field['options'] : [];
                $values = $this->extract_option_values($options);
                if ($multiple) {
                    $sliceCount = max(1, min(2, count($values)));
                    return array_slice($values, 0, $sliceCount);
                }
                return $values[0] ?? 'option';
            case 'file':
                if ($multiple) {
                    return ['sample.pdf'];
                }
                return 'sample.pdf';
            default:
                return 'Sample text';
        }
    }

    /**
     * Extract option values from options array which can be strings or arrays with 'value'/'label'.
     *
     * @param mixed $options
     * @return array
     */
    private function extract_option_values($options): array
    {
        $values = [];
        if (is_array($options)) {
            foreach ($options as $option) {
                if (is_array($option)) {
                    if (isset($option['value'])) {
                        $values[] = (string) $option['value'];
                    } elseif (isset($option['label'])) {
                        $values[] = (string) $option['label'];
                    }
                } else {
                    $values[] = (string) $option;
                }
            }
        }
        return $values;
    }


    /**
     * Build Postman request body for form submit. If there is at least one field of type 'file',
     * the body will be multipart form-data, otherwise JSON raw.
     *
     * @param array $body_fields Key-value pairs generated for the form fields.
     * @param mixed $fields      Original fields config (decoded JSON) to detect types and multiplicity.
     * @return array             Postman request body structure.
     */
    private function build_form_submit_body(array $body_fields, $fields): array
    {
        $has_file = false;
        $field_meta = [];
        if (is_array($fields)) {
            foreach ($fields as $field) {
                if (is_array($field) && !empty($field['name'])) {
                    $name = (string) $field['name'];
                    $type = isset($field['type']) ? (string) $field['type'] : 'text';
                    $multiple = !empty($field['multiple']);
                    $field_meta[$name] = [
                        'type'     => $type,
                        'multiple' => $multiple,
                    ];
                    if ($type === 'file') {
                        $has_file = true;
                    }
                }
            }
        }

        if (!$has_file) {
            return [
                'mode' => 'raw',
                'raw'  => wp_json_encode($body_fields, JSON_PRETTY_PRINT),
                'options' => [
                    'raw' => [
                        'language' => 'json',
                    ],
                ],
            ];
        }

        $formdata = [];
        foreach ($body_fields as $key => $value) {
            $type = isset($field_meta[$key]['type']) ? (string) $field_meta[$key]['type'] : 'text';
            $multiple = !empty($field_meta[$key]['multiple']);

            if ($type === 'file') {
                if (is_array($value)) {
                    foreach ($value as $file_src) {
                        $formdata[] = [
                            'key'  => $multiple ? $key . '[]' : (string) $key,
                            'type' => 'file',
                            'src'  => (string) $file_src,
                        ];
                    }
                } else {
                    $formdata[] = [
                        'key'  => (string) $key,
                        'type' => 'file',
                        'src'  => (string) $value,
                    ];
                }
            } else {
                if (is_array($value)) {
                    foreach ($value as $text_value) {
                        $formdata[] = [
                            'key'   => $multiple ? $key . '[]' : (string) $key,
                            'type'  => 'text',
                            'value' => (string) $text_value,
                        ];
                    }
                } else {
                    $formdata[] = [
                        'key'   => (string) $key,
                        'type'  => 'text',
                        'value' => (string) $value,
                    ];
                }
            }
        }

        return [
            'mode'     => 'formdata',
            'formdata' => $formdata,
        ];
    }


    /**
     * Check if fields contain at least one file field.
     *
     * @param mixed $fields
     */
    private function has_file_field($fields): bool
    {
        if (!is_array($fields)) {
            return false;
        }
        foreach ($fields as $field) {
            if (is_array($field) && isset($field['type']) && (string) $field['type'] === 'file') {
                return true;
            }
        }
        return false;
    }

    private function get_standard_custom_post_type_routes(string $post_type_name, $rest_base, string $singular_label, bool $add_acf = false): array
    {
        $fields_param = $this->get_fields_param();
        $query_params = [];
        
        if ($add_acf) {
            $fields_param .= ',acf';
            $query_params[] = [
                'key'   => 'acf_format',
                'value' => 'standard',
            ];
        }
        
        $query_params[] = [
            'key'   => '_fields',
            'value' => $fields_param,
        ];
        $query_params = array_merge($query_params, $this->get_wp_list_extra_params($rest_base));
        $query_params = array_merge($query_params, $this->get_pagination_params());
        Postman_Param_Descriptions::enrich_query_params($query_params);

        $raw_url = sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s&page=1&per_page=10', $rest_base, $fields_param);
        if ($add_acf) {
            $raw_url = sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s&acf_format=standard&page=1&per_page=10', $rest_base, $fields_param);
        }
        
        $cpt_slug_query = array_merge(
            in_array($post_type_name, ['pages', 'posts'], true)
                ? [
                    ['key' => 'slug', 'value' => ($post_type_name === 'pages' ? 'sample-page' : 'hello-world')],
                    ['key' => 'acf_format', 'value' => 'standard'],
                    ['key' => '_fields', 'value' => $this->get_detailed_fields_param()],
                ]
                : [['key' => 'slug', 'value' => 'example']],
            $this->get_wp_get_extra_params()
        );
        Postman_Param_Descriptions::enrich_query_params($cpt_slug_query);

        $cpt_id_query = array_merge(
            in_array($post_type_name, ['pages', 'posts'], true)
                ? [
                    ['key' => 'acf_format', 'value' => 'standard'],
                    ['key' => '_fields', 'value' => $this->get_detailed_fields_param()],
                ]
                : [],
            $this->get_wp_get_extra_params()
        );
        Postman_Param_Descriptions::enrich_query_params($cpt_id_query);

        return [[
            'name'    => 'List of ' . ucfirst($post_type_name),
            'request' => [
                'method'      => 'GET',
                'header'      => $this->get_default_headers(),
                'url'         => [
                    'raw'   => $raw_url,
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $rest_base],
                    'query' => $query_params,
                ],
                'description' => 'Get list of all ' . ucfirst($post_type_name) . ($add_acf ? ' with ACF fields' : ''),
            ],
        ], [
            'name'    => $singular_label . ' by Slug',
            'request' => [
                'method'      => 'GET',
                'header'      => $this->get_default_headers(),
                'url'         => [
                    'raw'   => (
                        in_array($post_type_name, ['pages', 'posts'], true)
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s?slug=', $rest_base) . ($post_type_name === 'pages' ? 'sample-page' : 'hello-world') . '&acf_format=standard&_fields=' . $this->get_detailed_fields_param()
                            : sprintf('{{baseUrl}}/wp-json/wp/v2/%s?slug=', $rest_base) . 'example'
                    ),
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $rest_base],
                    'query' => $cpt_slug_query,
                ],
                'description' => sprintf('Get specific %s by slug', $singular_label) . (in_array($post_type_name, ['pages', 'posts'], true) ? ' with ACF fields' : ''),
            ],
        ], [
            'name'    => $singular_label . ' by ID',
            'request' => [
                'method'      => 'GET',
                'header'      => $this->get_default_headers(),
                'url'         => [
                    'raw'   => (
                        in_array($post_type_name, ['pages', 'posts'], true)
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $rest_base) . $singular_label . 'ID}}?acf_format=standard&_fields=' . $this->get_detailed_fields_param()
                            : sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $rest_base) . $singular_label . 'ID}}'
                    ),
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $rest_base, '{{' . $singular_label . 'ID}}'],
                    'query' => $cpt_id_query,
                ],
                'description' => sprintf('Get specific %s by ID', $singular_label) . (in_array($post_type_name, ['pages', 'posts'], true) ? ' with ACF fields' : ''),
            ],
        ], [
            'name'    => 'Create ' . $singular_label,
            'request' => [
                'method'      => 'POST',
                'header'      => array_merge(
                    $this->get_auth_headers(),
                    [['key' => 'Content-Type', 'value' => 'application/json']]
                ),
                'body'        => [
                    'mode' => 'raw',
                    'raw'  => wp_json_encode(
                        [
                            'title'   => 'Sample ' . $singular_label . ' Title',
                            'content' => 'Sample ' . $singular_label . ' content here.',
                            'excerpt' => 'Sample ' . $singular_label . ' excerpt.',
                            'status'  => 'draft',
                        ],
                        JSON_PRETTY_PRINT
                    ),
                ],
                'url'         => [
                    'raw'  => '{{baseUrl}}/wp-json/wp/v2/' . $rest_base,
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $rest_base],
                ],
                'description' => 'Create new ' . $singular_label,
            ],
        ], [
            'name'    => 'Update ' . $singular_label,
            'request' => [
                'method'      => 'POST',
                'header'      => array_merge(
                    $this->get_auth_headers(),
                    [['key' => 'Content-Type', 'value' => 'application/json']]
                ),
                'body'        => [
                    'mode' => 'raw',
                    'raw'  => wp_json_encode(
                        [
                            'title'   => 'Updated ' . $singular_label . ' Title',
                            'content' => 'Updated ' . $singular_label . ' content here.',
                            'excerpt' => 'Updated ' . $singular_label . ' excerpt.',
                        ],
                        JSON_PRETTY_PRINT
                    ),
                ],
                'url'         => [
                    'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $rest_base) . $singular_label . 'ID}}',
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $rest_base, '{{' . $singular_label . 'ID}}'],
                ],
                'description' => sprintf('Update existing %s by ID', $singular_label),
            ],
        ], [
            'name'    => 'Delete ' . $singular_label,
            'request' => [
                'method'      => 'DELETE',
                'header'      => $this->get_auth_headers(),
                'url'         => [
                    'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $rest_base) . $singular_label . 'ID}}',
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $rest_base, '{{' . $singular_label . 'ID}}'],
                    'query' => $this->get_wp_delete_params(),
                ],
                'description' => sprintf('Delete %s by ID. Add ?force=true to bypass Trash.', $singular_label),
            ],
        ]];
    }


    public function get_individual_page_routes(array $selected_page_slugs): array {
        if (empty($selected_page_slugs)) {
            return [];
        }

        $specific_pages_items = [];

        foreach ($selected_page_slugs as $slug) {
            $page = get_page_by_path($slug, OBJECT, 'page');
            $page_title = $page ? $page->post_title : $slug;

            $page_query = [
                ['key' => 'slug', 'value' => $slug],
                ['key' => 'acf_format', 'value' => 'standard'],
                ['key' => '_fields', 'value' => $this->get_detailed_fields_param()],
            ];
            Postman_Param_Descriptions::enrich_query_params($page_query);
            $specific_pages_items[] = [
                'name'    => $page_title,
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/pages?slug=%s&acf_format=standard&_fields=%s', $slug, $this->get_detailed_fields_param()),
                        'host'  => ['{{baseUrl}}'],
                        'path'  => ['wp-json', 'wp', 'v2', 'pages'],
                        'query' => $page_query,
                    ],
                    'description' => sprintf('Get %s by slug with ACF fields', $page_title),
                ],
            ];
        }

        return [
            [
                'name' => 'Specific Pages',
                'item' => $specific_pages_items,
            ],
        ];
    }

	/**
	 * Build routes for posts filtered by selected categories (by slug).
	 */
	public function get_posts_by_categories_routes(array $selected_category_slugs): array {
		if (empty($selected_category_slugs)) {
			return [];
		}

		$items = [];
		foreach ($selected_category_slugs as $slug) {
			$term = get_term_by('slug', $slug, 'category');
			if (!$term || is_wp_error($term)) {
				continue;
			}
			$term_id = (int) $term->term_id;
			$name = (string) $term->name;

			$cat_query = array_merge(
				[
					['key' => '_fields', 'value' => $this->get_posts_fields_param()],
					['key' => 'categories', 'value' => (string) $term_id],
				],
				$this->get_wp_list_extra_params('posts'),
				$this->get_pagination_params()
			);
			Postman_Param_Descriptions::enrich_query_params($cat_query);
			$items[] = [
				'name'    => sprintf('Posts in %s', $name),
				'request' => [
					'method'      => 'GET',
					'header'      => $this->get_default_headers(),
					'url'         => [
						'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/posts?_fields=%s&categories=%d', $this->get_posts_fields_param(), $term_id),
						'host'  => ['{{baseUrl}}'],
						'path'  => ['wp-json', 'wp', 'v2', 'posts'],
						'query' => $cat_query,
					],
					'description' => sprintf('Get posts filtered by category \"%s\" (ID %d)', $name, $term_id),
				],
			];
		}

		return $items;
	}


    /**
     * Build WooCommerce REST API routes (products, categories, orders).
     * Requires WooCommerce plugin. Uses wc/v3 namespace and Basic Auth.
     *
     * @return array Folder structure or empty if WooCommerce inactive
     */
    public function get_woocommerce_routes(): array {
        if (!$this->is_woocommerce_active()) {
            return [];
        }

        $wc_base = 'wp-json/wc/v3';
        $wc_auth = [
            'type'  => 'basic',
            'basic' => [
                ['key' => 'username', 'value' => '{{wcConsumerKey}}', 'type' => 'string'],
                ['key' => 'password', 'value' => '{{wcConsumerSecret}}', 'type' => 'string'],
            ],
        ];

        $wc_pagination = [
            ['key' => 'page', 'value' => '1', 'disabled' => true],
            ['key' => 'per_page', 'value' => '10', 'disabled' => true],
        ];
        Postman_Param_Descriptions::enrich_query_params($wc_pagination);

        $products_items = $this->build_wc_products_routes($wc_base, $wc_pagination);
        $categories_items = $this->build_wc_product_categories_routes($wc_base, $wc_pagination);
        $orders_items = $this->build_wc_orders_routes($wc_base, $wc_pagination);

        return [
            [
                'name' => 'WooCommerce',
                'auth' => $wc_auth,
                'item' => [
                    ['name' => 'Products', 'item' => $products_items],
                    ['name' => 'Product Categories', 'item' => $categories_items],
                    ['name' => 'Orders', 'item' => $orders_items],
                ],
            ],
        ];
    }


    /**
     * @param string $wc_base
     * @param array  $wc_pagination
     * @return array
     */
    private function build_wc_products_routes(string $wc_base, array $wc_pagination): array {
        $query_list = array_merge(
            $wc_pagination,
            [
                ['key' => 'context', 'value' => 'view', 'disabled' => true],
                ['key' => 'search', 'value' => '', 'disabled' => true],
                ['key' => 'after', 'value' => '', 'disabled' => true],
                ['key' => 'before', 'value' => '', 'disabled' => true],
                ['key' => 'exclude', 'value' => '', 'disabled' => true],
                ['key' => 'include', 'value' => '', 'disabled' => true],
                ['key' => 'slug', 'value' => '', 'disabled' => true],
                ['key' => 'status', 'value' => 'publish', 'disabled' => true],
                ['key' => 'type', 'value' => 'simple', 'disabled' => true],
                ['key' => 'category', 'value' => '', 'disabled' => true],
                ['key' => 'tag', 'value' => '', 'disabled' => true],
                ['key' => 'orderby', 'value' => 'date', 'disabled' => true],
                ['key' => 'order', 'value' => 'desc', 'disabled' => true],
            ]
        );
        Postman_Param_Descriptions::enrich_query_params($query_list);

        $body_create = [
            'name'         => 'Sample Product',
            'type'         => 'simple',
            'regular_price'=> '29.99',
            'description'  => 'Product description.',
            'short_description' => 'Short description.',
            'status'       => 'draft',
        ];

        return [
            [
                'name'    => 'List Products',
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => '{{baseUrl}}/' . $wc_base . '/products?page=1&per_page=10',
                        'host'  => ['{{baseUrl}}'],
                        'path'  => array_merge(['wp-json', 'wc', 'v3'], ['products']),
                        'query' => $query_list,
                    ],
                    'description' => 'List all products. WooCommerce REST API.',
                ],
            ],
            [
                'name'    => 'Product by ID',
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => '{{baseUrl}}/' . $wc_base . '/products/{{ProductID}}',
                        'host'  => ['{{baseUrl}}'],
                        'path'  => array_merge(['wp-json', 'wc', 'v3'], ['products', '{{ProductID}}']),
                        'query' => [['key' => 'context', 'value' => 'view', 'disabled' => true]],
                    ],
                    'description' => 'Get product by ID.',
                ],
            ],
            [
                'name'    => 'Create Product',
                'request' => [
                    'method'      => 'POST',
                    'header'      => [['key' => 'Content-Type', 'value' => 'application/json']],
                    'body'        => [
                        'mode' => 'raw',
                        'raw'  => wp_json_encode($body_create, JSON_PRETTY_PRINT),
                    ],
                    'url'         => [
                        'raw'  => '{{baseUrl}}/' . $wc_base . '/products',
                        'host' => ['{{baseUrl}}'],
                        'path' => array_merge(['wp-json', 'wc', 'v3'], ['products']),
                    ],
                    'description' => 'Create new product.',
                ],
            ],
            [
                'name'    => 'Update Product',
                'request' => [
                    'method'      => 'PUT',
                    'header'      => [['key' => 'Content-Type', 'value' => 'application/json']],
                    'body'        => [
                        'mode' => 'raw',
                        'raw'  => wp_json_encode([
                            'name'          => 'Updated Product Name',
                            'regular_price' => '39.99',
                        ], JSON_PRETTY_PRINT),
                    ],
                    'url'         => [
                        'raw'  => '{{baseUrl}}/' . $wc_base . '/products/{{ProductID}}',
                        'host' => ['{{baseUrl}}'],
                        'path' => array_merge(['wp-json', 'wc', 'v3'], ['products', '{{ProductID}}']),
                    ],
                    'description' => 'Update product by ID.',
                ],
            ],
            [
                'name'    => 'Delete Product',
                'request' => [
                    'method'      => 'DELETE',
                    'header'      => [],
                    'url'         => [
                        'raw'   => '{{baseUrl}}/' . $wc_base . '/products/{{ProductID}}?force=true',
                        'host'  => ['{{baseUrl}}'],
                        'path'  => array_merge(['wp-json', 'wc', 'v3'], ['products', '{{ProductID}}']),
                        'query' => [['key' => 'force', 'value' => 'true', 'disabled' => true]],
                    ],
                    'description' => 'Delete product. Use force=true for permanent delete.',
                ],
            ],
        ];
    }


    /**
     * @param string $wc_base
     * @param array  $wc_pagination
     * @return array
     */
    private function build_wc_product_categories_routes(string $wc_base, array $wc_pagination): array {
        $query_list = array_merge(
            $wc_pagination,
            [
                ['key' => 'context', 'value' => 'view', 'disabled' => true],
                ['key' => 'search', 'value' => '', 'disabled' => true],
                ['key' => 'exclude', 'value' => '', 'disabled' => true],
                ['key' => 'include', 'value' => '', 'disabled' => true],
                ['key' => 'slug', 'value' => '', 'disabled' => true],
                ['key' => 'parent', 'value' => '0', 'disabled' => true],
                ['key' => 'orderby', 'value' => 'name', 'disabled' => true],
                ['key' => 'order', 'value' => 'asc', 'disabled' => true],
            ]
        );
        Postman_Param_Descriptions::enrich_query_params($query_list);

        $body_create = [
            'name'        => 'Sample Category',
            'slug'        => 'sample-category',
            'description' => 'Category description.',
            'parent'      => 0,
        ];

        return [
            [
                'name'    => 'List Product Categories',
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => '{{baseUrl}}/' . $wc_base . '/products/categories?page=1&per_page=10',
                        'host'  => ['{{baseUrl}}'],
                        'path'  => array_merge(['wp-json', 'wc', 'v3'], ['products', 'categories']),
                        'query' => $query_list,
                    ],
                    'description' => 'List all product categories.',
                ],
            ],
            [
                'name'    => 'Product Category by ID',
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'  => '{{baseUrl}}/' . $wc_base . '/products/categories/{{ProductCategoryID}}',
                        'host' => ['{{baseUrl}}'],
                        'path' => array_merge(['wp-json', 'wc', 'v3'], ['products', 'categories', '{{ProductCategoryID}}']),
                        'query' => [['key' => 'context', 'value' => 'view', 'disabled' => true]],
                    ],
                    'description' => 'Get product category by ID.',
                ],
            ],
            [
                'name'    => 'Create Product Category',
                'request' => [
                    'method'      => 'POST',
                    'header'      => [['key' => 'Content-Type', 'value' => 'application/json']],
                    'body'        => [
                        'mode' => 'raw',
                        'raw'  => wp_json_encode($body_create, JSON_PRETTY_PRINT),
                    ],
                    'url'         => [
                        'raw'  => '{{baseUrl}}/' . $wc_base . '/products/categories',
                        'host' => ['{{baseUrl}}'],
                        'path' => array_merge(['wp-json', 'wc', 'v3'], ['products', 'categories']),
                    ],
                    'description' => 'Create new product category.',
                ],
            ],
            [
                'name'    => 'Update Product Category',
                'request' => [
                    'method'      => 'PUT',
                    'header'      => [['key' => 'Content-Type', 'value' => 'application/json']],
                    'body'        => [
                        'mode' => 'raw',
                        'raw'  => wp_json_encode(['name' => 'Updated Category', 'slug' => 'updated-category'], JSON_PRETTY_PRINT),
                    ],
                    'url'         => [
                        'raw'  => '{{baseUrl}}/' . $wc_base . '/products/categories/{{ProductCategoryID}}',
                        'host' => ['{{baseUrl}}'],
                        'path' => array_merge(['wp-json', 'wc', 'v3'], ['products', 'categories', '{{ProductCategoryID}}']),
                    ],
                    'description' => 'Update product category by ID.',
                ],
            ],
            [
                'name'    => 'Delete Product Category',
                'request' => [
                    'method'      => 'DELETE',
                    'header'      => [],
                    'url'         => [
                        'raw'   => '{{baseUrl}}/' . $wc_base . '/products/categories/{{ProductCategoryID}}?force=true',
                        'host'  => ['{{baseUrl}}'],
                        'path'  => array_merge(['wp-json', 'wc', 'v3'], ['products', 'categories', '{{ProductCategoryID}}']),
                        'query' => [['key' => 'force', 'value' => 'true', 'disabled' => true]],
                    ],
                    'description' => 'Delete product category. Use force=true for permanent delete.',
                ],
            ],
        ];
    }


    /**
     * @param string $wc_base
     * @param array  $wc_pagination
     * @return array
     */
    private function build_wc_orders_routes(string $wc_base, array $wc_pagination): array {
        $query_list = array_merge(
            $wc_pagination,
            [
                ['key' => 'context', 'value' => 'view', 'disabled' => true],
                ['key' => 'search', 'value' => '', 'disabled' => true],
                ['key' => 'after', 'value' => '', 'disabled' => true],
                ['key' => 'before', 'value' => '', 'disabled' => true],
                ['key' => 'status', 'value' => 'any', 'disabled' => true],
                ['key' => 'customer', 'value' => '', 'disabled' => true],
                ['key' => 'product', 'value' => '', 'disabled' => true],
                ['key' => 'orderby', 'value' => 'date', 'disabled' => true],
                ['key' => 'order', 'value' => 'desc', 'disabled' => true],
            ]
        );
        Postman_Param_Descriptions::enrich_query_params($query_list);

        $body_create = [
            'payment_method' => 'bacs',
            'billing'       => [
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'address_1'  => '123 Main St',
                'city'       => 'Anytown',
                'postcode'   => '12345',
                'country'    => 'US',
                'email'      => 'john@example.com',
            ],
            'line_items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
        ];

        return [
            [
                'name'    => 'List Orders',
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => '{{baseUrl}}/' . $wc_base . '/orders?page=1&per_page=10',
                        'host'  => ['{{baseUrl}}'],
                        'path'  => array_merge(['wp-json', 'wc', 'v3'], ['orders']),
                        'query' => $query_list,
                    ],
                    'description' => 'List all orders.',
                ],
            ],
            [
                'name'    => 'Order by ID',
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'  => '{{baseUrl}}/' . $wc_base . '/orders/{{OrderID}}',
                        'host' => ['{{baseUrl}}'],
                        'path' => array_merge(['wp-json', 'wc', 'v3'], ['orders', '{{OrderID}}']),
                        'query' => [['key' => 'context', 'value' => 'view', 'disabled' => true]],
                    ],
                    'description' => 'Get order by ID.',
                ],
            ],
            [
                'name'    => 'Create Order',
                'request' => [
                    'method'      => 'POST',
                    'header'      => [['key' => 'Content-Type', 'value' => 'application/json']],
                    'body'        => [
                        'mode' => 'raw',
                        'raw'  => wp_json_encode($body_create, JSON_PRETTY_PRINT),
                    ],
                    'url'         => [
                        'raw'  => '{{baseUrl}}/' . $wc_base . '/orders',
                        'host' => ['{{baseUrl}}'],
                        'path' => array_merge(['wp-json', 'wc', 'v3'], ['orders']),
                    ],
                    'description' => 'Create new order.',
                ],
            ],
            [
                'name'    => 'Update Order',
                'request' => [
                    'method'      => 'PUT',
                    'header'      => [['key' => 'Content-Type', 'value' => 'application/json']],
                    'body'        => [
                        'mode' => 'raw',
                        'raw'  => wp_json_encode(['status' => 'processing'], JSON_PRETTY_PRINT),
                    ],
                    'url'         => [
                        'raw'  => '{{baseUrl}}/' . $wc_base . '/orders/{{OrderID}}',
                        'host' => ['{{baseUrl}}'],
                        'path' => array_merge(['wp-json', 'wc', 'v3'], ['orders', '{{OrderID}}']),
                    ],
                    'description' => 'Update order by ID.',
                ],
            ],
            [
                'name'    => 'Delete Order',
                'request' => [
                    'method'      => 'DELETE',
                    'header'      => [],
                    'url'         => [
                        'raw'   => '{{baseUrl}}/' . $wc_base . '/orders/{{OrderID}}?force=true',
                        'host'  => ['{{baseUrl}}'],
                        'path'  => array_merge(['wp-json', 'wc', 'v3'], ['orders', '{{OrderID}}']),
                        'query' => [['key' => 'force', 'value' => 'true', 'disabled' => true]],
                    ],
                    'description' => 'Delete order. Use force=true for permanent delete.',
                ],
            ],
        ];
    }


    public function get_variables(array $custom_post_types): array {
        $variables = [
            [
                'key'   => 'baseUrl',
                'value' => home_url(),
            ],
            [
                'key'   => 'wpNonce',
                'value' => '',
            ],
            [
                'key'   => 'PostID',
                'value' => '1',
            ],
            [
                'key'   => 'PageID',
                'value' => '2',
            ],
            [
                'key'   => 'CommentID',
                'value' => '1',
            ],
            [
                'key'   => 'UserID',
                'value' => '1',
            ],
            [
                'key'   => 'CategoryID',
                'value' => '1',
            ],
            [
                'key'   => 'TagID',
                'value' => '1',
            ],
            [
                'key'   => 'TaxID',
                'value' => '1',
            ],
        ];

        if ($this->is_woocommerce_active()) {
            $variables[] = ['key' => 'wcConsumerKey', 'value' => ''];
            $variables[] = ['key' => 'wcConsumerSecret', 'value' => ''];
            $variables[] = ['key' => 'ProductCategoryID', 'value' => '1'];
            $variables[] = ['key' => 'OrderID', 'value' => '1'];
        }

        // Add variables for custom post types
        foreach ($custom_post_types as $post_type_name => $post_type_obj) {
            $singular_label = $post_type_obj->labels->singular_name ?? ucfirst((string) $post_type_name);
            $variables[] = [
                'key'   => $singular_label . 'ID',
                'value' => '1',
            ];
        }

        return $variables;
    }


}
