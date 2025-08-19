<?php

/**
 * @file: includes/class-postman-routes.php
 * @description: Build request items for Postman Collection (core entities, CPTs, options, pages).
 * @dependencies: WordPress REST API
 * @created: 2025-08-19
 */
class Postman_Routes {


    public function get_basic_routes(): array {
        $basic_routes = [];

        $standard_entities = [
            'pages'      => 'Page',
            'posts'      => 'Post',
            'categories' => 'Category',
            'tags'       => 'Tag',
            'taxonomies' => 'Taxonomy',
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
                    'header'      => [],
                    'url'         => [
                        'raw'   => (
                            in_array($entity, ['pages', 'posts'])
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=id,slug,title', $entity)
                            : '{{baseUrl}}/wp-json/wp/v2/' . $entity
                        ),
                        'host'  => ['{{baseUrl}}'],
                        'path'  => ['wp-json', 'wp', 'v2', $entity],
                        'query' => (
                            in_array($entity, ['pages', 'posts'])
                            ? [
                                [
                                    'key'   => '_fields',
                                    'value' => 'id,slug,title',
                                ],
                            ]
                            : []
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
                    'header'      => [],
                    'url'         => [
                        'raw'   => (
                            in_array($entity, ['pages', 'posts'])
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s?slug=', $entity) . ($entity === 'pages' ? 'sample-page' : 'hello-world') . '&acf_format=standard&_fields=title,acf,content'
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
                                    'value' => 'title,acf,content',
                                ],
                            ]
                            : [
                                [
                                    'key'   => 'slug',
                                    'value' => ($entity === 'categories' ? 'uncategorized' : 'example'),
                                ],
                            ]
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
                    'header'      => [],
                    'url'         => [
                        'raw'   => (
                            in_array($entity, ['pages', 'posts'])
                            ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $entity) . $singular . 'ID}}?acf_format=standard&_fields=title,acf,content'
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
                                    'value' => 'title,acf,content',
                                ],
                            ]
                            : []
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
                        'raw'  => json_encode(
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
                        'raw'  => json_encode(
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

        return $basic_routes;
    }


    public function get_options_routes($options_pages, array $options_pages_data): array {
        if ($options_pages === []) {
            return [];
        }

        $options_items = [];

        // Add List of Options Pages
        $options_items[] = [
            'name'    => 'List of Options Pages',
            'request' => [
                'method'      => 'GET',
                'header'      => [],
                'url'         => [
                    'raw'  => '{{baseUrl}}/wp-json/custom/v1/options',
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'custom', 'v1', 'options'],
                ],
                'description' => 'Get list of all available options pages',
            ],
        ];

        // Add ALL Options Pages (not just selected ones)
        foreach ($options_pages as $page_slug) {
            $display_name = $options_pages_data[$page_slug]['title'] ?? ucfirst(str_replace('-', ' ', $page_slug));
            $is_example = str_starts_with((string) $page_slug, 'example-');

            // Skip example routes
            if ($is_example) {
                continue;
            }

            $options_items[] = [
                'name'    => $display_name,
                'request' => [
                    'method'      => 'GET',
                    'header'      => [],
                    'url'         => [
                        'raw'  => '{{baseUrl}}/wp-json/custom/v1/options/' . $page_slug,
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'custom', 'v1', 'options', $page_slug],
                    ],
                    'description' => 'Get options for ' . $display_name,
                ],
            ];
        }

        return $options_items;
    }


    public function get_custom_post_type_routes($custom_post_types): array {
        $custom_routes = [];

        foreach ($custom_post_types as $post_type_name => $post_type_obj) {
            $type_label = $post_type_obj->labels->name ?? ucfirst((string) $post_type_name);
            $singular_label = $post_type_obj->labels->singular_name ?? ucfirst((string) $post_type_name);
            $folder_items = [];

            // Get rest_base for post type (if exists)
            $rest_base = empty($post_type_obj->rest_base) ? $post_type_name : $post_type_obj->rest_base;

            // Special handling for Forms
            if ($post_type_name === 'forms') {
                $folder_items = $this->get_forms_routes($rest_base, $type_label);
            } else {
                $folder_items = $this->get_standard_custom_post_type_routes($post_type_name, $rest_base, $singular_label);
            }

            $custom_routes[] = [
                'name' => $type_label,
                'item' => $folder_items,
            ];
        }

        return $custom_routes;
    }


    private function get_forms_routes($rest_base, string $type_label): array {
        $folder_items = [];

        // List for Forms
        $folder_items[] = [
            'name'    => 'List of ' . $type_label,
            'request' => [
                'method'      => 'GET',
                'header'      => [],
                'url'         => [
                    'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=id,slug,title', $rest_base),
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $rest_base],
                    'query' => [
                        [
                            'key'   => '_fields',
                            'value' => 'id,slug,title',
                        ],
                    ],
                ],
                'description' => 'Get list of all ' . $type_label,
            ],
        ];

        // Add ALL Forms (not just selected ones)
        $forms = get_posts([
            'post_type'      => 'forms',
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
                    $name = $field['name'];
                    $type = $field['type'] ?? 'text';
                    // Example values by type
                    $body_fields[$name] = match ($type) {
                        'email' => 'test@example.com',
                        'tel' => '+1234567890',
                        'textarea' => 'Sample message text.',
                        'boolean' => '1',
                        'number' => '42',
                        default => 'Sample text',
                    };
                }
            }

            $folder_items[] = [
                'name' => $form_title,
                'item' => [
                    [
                        'name'    => 'Submit Form - ' . $form_title,
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
                                'raw'  => json_encode($body_fields, JSON_PRETTY_PRINT),
                            ],
                            'url'         => [
                                'raw'  => sprintf('{{baseUrl}}/wp-json/wp/v2/forms/%s/submit', $slug),
                                'host' => ['{{baseUrl}}'],
                                'path' => ['wp-json', 'wp', 'v2', 'forms', $slug, 'submit'],
                            ],
                            'description' => sprintf("Submit form data for '%s'", $form_title),
                        ],
                    ],
                ],
            ];
        }

        return $folder_items;
    }


    private function get_standard_custom_post_type_routes(string $post_type_name, $rest_base, string $singular_label): array
    {
        return [[
            'name'    => 'List of ' . ucfirst($post_type_name),
            'request' => [
                'method'      => 'GET',
                'header'      => [],
                'url'         => [
                    'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/%s?_fields=id,slug,title', $rest_base),
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $rest_base],
                    'query' => [
                        [
                            'key'   => '_fields',
                            'value' => 'id,slug,title',
                        ],
                    ],
                ],
                'description' => 'Get list of all ' . ucfirst($post_type_name),
            ],
        ], [
            'name'    => $singular_label . ' by Slug',
            'request' => [
                'method'      => 'GET',
                'header'      => [],
                'url'         => [
                    'raw'   => (
                        in_array($post_type_name, ['pages', 'posts'])
                        ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s?slug=', $post_type_name) . ($post_type_name === 'pages' ? 'sample-page' : 'hello-world') . '&acf_format=standard&_fields=title,acf,content'
                        : sprintf('{{baseUrl}}/wp-json/wp/v2/%s?slug=', $post_type_name) . ($post_type_name === 'categories' ? 'uncategorized' : 'example')
                    ),
                    'host'  => ['{{baseUrl}}'],
                    'path'  => ['wp-json', 'wp', 'v2', $post_type_name],
                    'query' => (
                        in_array($post_type_name, ['pages', 'posts'])
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
                                'value' => 'title,acf,content',
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
                'header'      => [],
                'url'         => [
                    'raw'   => (
                        in_array($post_type_name, ['pages', 'posts'])
                        ? sprintf('{{baseUrl}}/wp-json/wp/v2/%s/{{', $post_type_name) . $singular_label . 'ID}}?acf_format=standard&_fields=title,acf,content'
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
                                'value' => 'title,acf,content',
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
                    'raw'  => json_encode(
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
                    'raw'  => json_encode(
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


    public function get_individual_page_routes($selected_page_slugs): array {
        $individual_routes = [];

        foreach ($selected_page_slugs as $slug) {
            $page = get_page_by_path($slug, OBJECT, 'page');
            $page_title = $page ? $page->post_title : $slug;

            $individual_routes[] = [
                'name' => 'Page: ' . $page_title,
                'item' => [
                    [
                        'name'    => 'Page: ' . $page_title,
                        'request' => [
                            'method'      => 'GET',
                            'header'      => [],
                            'url'         => [
                                'raw'   => sprintf('{{baseUrl}}/wp-json/wp/v2/pages?slug=%s&acf_format=standard&_fields=title,acf,content', $slug),
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
                                        'value' => 'title,acf,content',
                                    ],
                                ],
                            ],
                            'description' => sprintf('Get %s by slug with ACF fields', $page_title),
                        ],
                    ],
                ],
            ];
        }

        return $individual_routes;
    }


    public function get_variables($custom_post_types): array {
        $variables = [
            [
                'key'   => 'baseUrl',
                'value' => 'http://localhost:8000',
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
