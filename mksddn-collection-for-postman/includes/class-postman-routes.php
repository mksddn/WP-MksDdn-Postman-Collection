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
        
        // Convert WordPress locale format (ru_RU) to RFC 5646 format (ru-RU)
        $accept_language = str_replace('_', '-', $language);
        
        return [
            [
                'key'      => 'Accept-Language',
                'value'    => $accept_language,
                'disabled' => true,
            ],
        ];
    }


    public function get_basic_routes(): array {
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
            $folder_items[] = [
                'name'    => 'List of ' . ucfirst($plural),
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => (
                            $entity === 'posts'
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s&categories=1', $entity, $this->get_posts_fields_param())
                            : ($entity === 'categories'
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s', $entity, $this->get_categories_fields_param())
                            : (in_array($entity, ['pages'], true)
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s', $entity, $this->get_fields_param())
                            : '{{baseUrl}}/wp-json/wp/v2/' . $entity))
                        ),
                        'host'  => ['{{baseUrl}}'],
                        'path'  => ['wp-json', 'wp', 'v2', $entity],
                        'query' => (
                            $entity === 'posts'
                            ? [
                                [
                                    'key'   => '_fields',
                                    'value' => $this->get_posts_fields_param(),
                                ],
                                [
                                    'key'      => 'categories',
                                    'value'    => '1',
                                    'disabled' => true,
                                ],
                            ]
                            : ($entity === 'categories'
                            ? [
                                [
                                    'key'   => '_fields',
                                    'value' => $this->get_categories_fields_param(),
                                ],
                            ]
                            : (in_array($entity, ['pages'], true)
                            ? [
                                [
                                    'key'   => '_fields',
                                    'value' => $this->get_fields_param(),
                                ],
                            ]
                            : []))
                        ),
                    ],
                    'description' => 'Get list of all ' . $plural,
                ],
            ];

            // Get by Slug
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
                        'query' => (
                            in_array($entity, ['pages', 'posts'])
                            ? [
                                [
                                    'key'   => 'slug',
                                    'value' => ($entity === 'pages' ? 'sample-page' : 'hello-world'),
                                ],
                                [
                                    'key'   => 'acf_format',
                                    'value' => 'standard',
                                ],
                                [
                                    'key'   => '_fields',
                                    'value' => $this->get_detailed_fields_param(),
                                ],
                            ]
                            : ($entity === 'categories'
                            ? [
                                [
                                    'key'   => 'slug',
                                    'value' => 'uncategorized',
                                ],
                                [
                                    'key'      => 'parent',
                                    'value'    => '1',
                                    'disabled' => true,
                                ],
                            ]
                            : [
                                [
                                    'key'   => 'slug',
                                    'value' => 'example',
                                ],
                            ])
                        ),
                    ],
                    'description' => sprintf('Get specific %s by slug', $singular) . (in_array($entity, ['pages', 'posts']) ? ' with ACF fields' : ''),
                ],
            ];

            // Get by ID
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
                        'query' => (
                            in_array($entity, ['pages', 'posts'])
                            ? [
                                [
                                    'key'   => 'acf_format',
                                    'value' => 'standard',
                                ],
                                [
                                    'key'   => '_fields',
                                    'value' => $this->get_detailed_fields_param(),
                                ],
                            ]
                            : ($entity === 'categories'
                            ? [
                                [
                                    'key'      => 'parent',
                                    'value'    => '1',
                                    'disabled' => true,
                                ],
                            ]
                            : [])
                        ),
                    ],
                    'description' => sprintf('Get specific %s by ID', $singular) . (in_array($entity, ['pages', 'posts']) ? ' with ACF fields' : ''),
                ],
            ];

            // Create
            $folder_items[] = [
                'name'    => 'Create ' . $singular,
                'request' => [
                    'method'      => 'POST',
                    'header'      => [
                        [
                            'key'   => 'Content-Type',
                            'value' => 'application/json',
                        ],
                    ],
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
                    'header'      => [
                        [
                            'key'   => 'Content-Type',
                            'value' => 'application/json',
                        ],
                    ],
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
                    'header'      => [],
                    'url'         => [
                        'raw'  => sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $entity) . $singular . 'ID}}',
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $entity, '{{' . $singular . 'ID}}'],
                    ],
                    'description' => sprintf('Delete %s by ID', $singular),
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
                            'query' => [
                                [
                                    'key'   => 'search',
                                    'value' => 'example',
                                ],
                                [
                                    'key'   => 'type',
                                    'value' => 'post',
                                ],
                                [
                                    'key'   => '_fields',
                                    'value' => $this->get_search_fields_param(),
                                ],
                            ],
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
                            'query' => [
                                [
                                    'key'   => 'search',
                                    'value' => 'example',
                                ],
                                [
                                    'key'   => 'type',
                                    'value' => 'page',
                                ],
                                [
                                    'key'   => '_fields',
                                    'value' => $this->get_search_fields_param(),
                                ],
                            ],
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
                            'query' => [
                                [
                                    'key'   => 'search',
                                    'value' => 'example',
                                ],
                                [
                                    'key'   => '_fields',
                                    'value' => $this->get_search_fields_param(),
                                ],
                            ],
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


    public function get_custom_post_type_routes(array $custom_post_types): array {
        $custom_routes = [];

        foreach ($custom_post_types as $post_type_name => $post_type_obj) {
            $type_label = $post_type_obj->labels->name ?? ucfirst((string) $post_type_name);
            $singular_label = $post_type_obj->labels->singular_name ?? ucfirst((string) $post_type_name);
            $folder_items = [];

            // Get rest_base for post type (if exists)
            $rest_base = empty($post_type_obj->rest_base) ? $post_type_name : $post_type_obj->rest_base;

            // Special handling for Forms (handler CPT only)
            $is_forms_post_type = ($post_type_name === 'mksddn_fh_forms');
            if ($is_forms_post_type) {
                // Force human-friendly folder name regardless of CPT labels
                $type_label = 'Forms';
                $folder_items = $this->get_forms_routes($rest_base, $type_label);
            } else {
                $folder_items = $this->get_standard_custom_post_type_routes($post_type_name, $rest_base, $singular_label);
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
        $folder_items[] = [
            'name'    => 'List of ' . $type_label,
            'request' => [
                'method'      => 'GET',
                'header'      => $this->get_default_headers(),
                'url'         => [
                    'raw'   => '{{baseUrl}}/wp-json/mksddn-forms-handler/v1/forms/',
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'mksddn-forms-handler', 'v1', 'forms'],
                    'query' => [],
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

    private function get_standard_custom_post_type_routes(string $post_type_name, $rest_base, string $singular_label): array
    {
        return [[
            'name'    => 'List of ' . ucfirst($post_type_name),
            'request' => [
                'method'      => 'GET',
                'header'      => $this->get_default_headers(),
                'url'         => [
                    'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=%s', $rest_base, $this->get_fields_param()),
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $rest_base],
                    'query' => [
                        [
                            'key'   => '_fields',
                            'value' => $this->get_fields_param(),
                        ],
                    ],
                ],
                'description' => 'Get list of all ' . ucfirst($post_type_name),
            ],
        ], [
            'name'    => $singular_label . ' by Slug',
            'request' => [
                'method'      => 'GET',
                'header'      => $this->get_default_headers(),
                'url'         => [
                    'raw'   => (
                        in_array($post_type_name, ['pages', 'posts'], true)
                        ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s?slug=', $post_type_name) . ($post_type_name === 'pages' ? 'sample-page' : 'hello-world') . '&acf_format=standard&_fields=' . $this->get_detailed_fields_param()
                        : sprintf('{{baseUrl}}/wp-json/wp/v2/%s?slug=', $post_type_name) . ($post_type_name === 'categories' ? 'uncategorized' : 'example')
                    ),
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $post_type_name],
                    'query' => (
                        in_array($post_type_name, ['pages', 'posts'], true)
                        ? [
                            [
                                'key'   => 'slug',
                                'value' => ($post_type_name === 'pages' ? 'sample-page' : 'hello-world'),
                            ],
                            [
                                'key'   => 'acf_format',
                                'value' => 'standard',
                            ],
                            [
                                'key'   => '_fields',
                                'value' => $this->get_detailed_fields_param(),
                            ],
                        ]
                        : [
                            [
                                'key'   => 'slug',
                                'value' => ($post_type_name === 'categories' ? 'uncategorized' : 'example'),
                            ],
                        ]
                    ),
                ],
                'description' => sprintf('Get specific %s by slug', $singular_label) . (in_array($post_type_name, ['pages', 'posts']) ? ' with ACF fields' : ''),
            ],
        ], [
            'name'    => $singular_label . ' by ID',
            'request' => [
                'method'      => 'GET',
                'header'      => $this->get_default_headers(),
                'url'         => [
                    'raw'   => (
                        in_array($post_type_name, ['pages', 'posts'])
                        ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $post_type_name) . $singular_label . 'ID}}?acf_format=standard&_fields=' . $this->get_detailed_fields_param()
                        : sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $post_type_name) . $singular_label . 'ID}}'
                    ),
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $post_type_name, '{{' . $singular_label . 'ID}}'],
                    'query' => (
                        in_array($post_type_name, ['pages', 'posts'])
                        ? [
                            [
                                'key'   => 'acf_format',
                                'value' => 'standard',
                            ],
                            [
                                'key'   => '_fields',
                                'value' => $this->get_detailed_fields_param(),
                            ],
                        ]
                        : []
                    ),
                ],
                'description' => sprintf('Get specific %s by ID', $singular_label) . (in_array($post_type_name, ['pages', 'posts']) ? ' with ACF fields' : ''),
            ],
        ], [
            'name'    => 'Create ' . $singular_label,
            'request' => [
                'method'      => 'POST',
                'header'      => [
                    [
                        'key'   => 'Content-Type',
                        'value' => 'application/json',
                    ],
                ],
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
                    'raw'  => '{{baseUrl}}/wp-json/wp/v2/' . $post_type_name,
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $post_type_name],
                ],
                'description' => 'Create new ' . $singular_label,
            ],
        ], [
            'name'    => 'Update ' . $singular_label,
            'request' => [
                'method'      => 'POST',
                'header'      => [
                    [
                        'key'   => 'Content-Type',
                        'value' => 'application/json',
                    ],
                ],
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
                    'raw'  => sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $post_type_name) . $singular_label . 'ID}}',
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $post_type_name, '{{' . $singular_label . 'ID}}'],
                ],
                'description' => sprintf('Update existing %s by ID', $singular_label),
            ],
        ], [
            'name'    => 'Delete ' . $singular_label,
            'request' => [
                'method'      => 'DELETE',
                'header'      => [],
                'url'         => [
                    'raw'  => sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $post_type_name) . $singular_label . 'ID}}',
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $post_type_name, '{{' . $singular_label . 'ID}}'],
                ],
                'description' => sprintf('Delete %s by ID', $singular_label),
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

            $specific_pages_items[] = [
                'name'    => $page_title,
                'request' => [
                    'method'      => 'GET',
                    'header'      => $this->get_default_headers(),
                    'url'         => [
                        'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/pages?slug=%s&acf_format=standard&_fields=%s', $slug, $this->get_detailed_fields_param()),
                        'host'  => ['{{baseUrl}}'],
                        'path'  => ['wp-json', 'wp', 'v2', 'pages'],
                        'query' => [
                            [
                                'key'   => 'slug',
                                'value' => $slug,
                            ],
                            [
                                'key'   => 'acf_format',
                                'value' => 'standard',
                            ],
                            [
                                'key'   => '_fields',
                                'value' => $this->get_detailed_fields_param(),
                            ],
                        ],
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


    public function get_variables(array $custom_post_types): array {
        $variables = [
            [
                'key'   => 'baseUrl',
                'value' => home_url(),
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
