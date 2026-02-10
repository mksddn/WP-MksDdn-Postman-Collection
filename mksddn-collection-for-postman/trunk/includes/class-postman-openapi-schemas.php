<?php

/**
 * @file: includes/class-postman-openapi-schemas.php
 * @description: OpenAPI 3.0 schemas for WordPress REST API entities.
 * @dependencies: None
 * @created: 2025-02-10
 */
class Postman_OpenAPI_Schemas {

    /**
     * Get reusable OpenAPI schemas for WordPress entities.
     *
     * @return array<string, array>
     */
    public static function get_schemas(): array {
        return [
            'WP_Post' => [
                'type'       => 'object',
                'description' => 'WordPress Post object. Schema aligns with [Posts Reference](https://developer.wordpress.org/rest-api/reference/posts/).',
                'properties' => [
                    'id'             => ['type' => 'integer', 'format' => 'int64', 'description' => 'Unique identifier'],
                    'slug'           => ['type' => 'string', 'description' => 'URL-friendly slug'],
                    'link'           => ['type' => 'string', 'format' => 'uri', 'description' => 'URL to the post'],
                    'guid'           => [
                        'type'       => 'object',
                        'properties' => ['rendered' => ['type' => 'string']],
                        'description' => 'Globally unique identifier for the post',
                    ],
                    'title'          => [
                        'type'       => 'object',
                        'properties' => [
                            'rendered' => ['type' => 'string'],
                        ],
                    ],
                    'content'        => [
                        'type'       => 'object',
                        'properties' => [
                            'rendered'  => ['type' => 'string'],
                            'protected' => ['type' => 'boolean'],
                        ],
                    ],
                    'excerpt'        => [
                        'type'       => 'object',
                        'properties' => [
                            'rendered'  => ['type' => 'string'],
                            'protected' => ['type' => 'boolean'],
                        ],
                    ],
                    'date'           => ['type' => 'string', 'format' => 'date-time'],
                    'date_gmt'       => ['type' => 'string', 'format' => 'date-time'],
                    'modified'       => ['type' => 'string', 'format' => 'date-time'],
                    'modified_gmt'   => ['type' => 'string', 'format' => 'date-time'],
                    'status'         => ['type' => 'string', 'enum' => ['publish', 'future', 'draft', 'pending', 'private']],
                    'type'           => ['type' => 'string'],
                    'author'         => ['type' => 'integer'],
                    'featured_media' => ['type' => 'integer'],
                    'comment_status' => ['type' => 'string', 'enum' => ['open', 'closed']],
                    'ping_status'    => ['type' => 'string', 'enum' => ['open', 'closed']],
                    'sticky'         => ['type' => 'boolean'],
                    'format'         => ['type' => 'string'],
                    'categories'     => ['type' => 'array', 'items' => ['type' => 'integer']],
                    'tags'           => ['type' => 'array', 'items' => ['type' => 'integer']],
                    'acf'            => ['type' => 'object', 'description' => 'ACF fields when acf_format=standard'],
                    'yoast_head_json' => ['type' => 'object', 'description' => 'Yoast SEO metadata when plugin is active'],
                ],
            ],
            'WP_Page' => [
                'type'       => 'object',
                'description' => 'WordPress Page object. Schema aligns with [Pages Reference](https://developer.wordpress.org/rest-api/reference/pages/).',
                'properties' => [
                    'id'             => ['type' => 'integer', 'format' => 'int64'],
                    'slug'           => ['type' => 'string'],
                    'link'           => ['type' => 'string', 'format' => 'uri'],
                    'title'          => [
                        'type'       => 'object',
                        'properties' => ['rendered' => ['type' => 'string']],
                    ],
                    'content'        => [
                        'type'       => 'object',
                        'properties' => [
                            'rendered'  => ['type' => 'string'],
                            'protected' => ['type' => 'boolean'],
                        ],
                    ],
                    'excerpt'        => ['type' => 'object'],
                    'date'           => ['type' => 'string', 'format' => 'date-time'],
                    'modified'       => ['type' => 'string', 'format' => 'date-time'],
                    'status'         => ['type' => 'string'],
                    'type'           => ['type' => 'string'],
                    'parent'         => ['type' => 'integer'],
                    'menu_order'     => ['type' => 'integer'],
                    'template'       => ['type' => 'string'],
                    'acf'            => ['type' => 'object'],
                    'yoast_head_json' => ['type' => 'object'],
                ],
            ],
            'WP_Term' => [
                'type'       => 'object',
                'description' => 'WordPress Term object (category, tag, etc.). See [Categories](https://developer.wordpress.org/rest-api/reference/categories/), [Tags](https://developer.wordpress.org/rest-api/reference/tags/).',
                'properties' => [
                    'id'          => ['type' => 'integer', 'format' => 'int64'],
                    'slug'        => ['type' => 'string'],
                    'name'        => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'count'       => ['type' => 'integer'],
                    'parent'      => ['type' => 'integer'],
                    'taxonomy'    => ['type' => 'string'],
                ],
            ],
            'WP_User' => [
                'type'       => 'object',
                'description' => 'WordPress User object. See [Users Reference](https://developer.wordpress.org/rest-api/reference/users/).',
                'properties' => [
                    'id'          => ['type' => 'integer', 'format' => 'int64'],
                    'slug'        => ['type' => 'string'],
                    'name'        => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'avatar_urls' => ['type' => 'object'],
                ],
            ],
            'WP_Comment' => [
                'type'       => 'object',
                'description' => 'WordPress Comment object. See [Comments Reference](https://developer.wordpress.org/rest-api/reference/comments/).',
                'properties' => [
                    'id'       => ['type' => 'integer', 'format' => 'int64'],
                    'content'  => ['type' => 'object'],
                    'date'     => ['type' => 'string', 'format' => 'date-time'],
                    'parent'   => ['type' => 'integer'],
                    'post'     => ['type' => 'integer'],
                    'author'   => ['type' => 'integer'],
                    'status'   => ['type' => 'string'],
                ],
            ],
            'WP_REST_Error' => [
                'type'       => 'object',
                'description' => 'WordPress REST API error response. Per https://developer.wordpress.org/rest-api/extending-the-rest-api/controller-classes/',
                'properties' => [
                    'code'    => ['type' => 'string', 'description' => 'Error code (e.g. rest_not_logged_in, rest_post_invalid_id)'],
                    'message' => ['type' => 'string', 'description' => 'Human-readable error message'],
                    'data'    => ['type' => 'object', 'description' => 'Additional data (e.g. status HTTP code)'],
                ],
            ],
        ];
    }


    /**
     * Get standard response definitions for OpenAPI components.
     *
     * @return array<string, array>
     */
    public static function get_responses(): array {
        return [
            'Unauthorized' => [
                'description' => 'Authentication required',
                'content'    => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/WP_REST_Error'],
                        'example' => [
                            'code'    => 'rest_not_logged_in',
                            'message' => 'You are not currently logged in.',
                            'data'    => ['status' => 401],
                        ],
                    ],
                ],
            ],
            'NotFound' => [
                'description' => 'Resource not found',
                'content'    => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/WP_REST_Error'],
                        'example' => [
                            'code'    => 'rest_post_invalid_id',
                            'message' => 'Invalid post ID.',
                            'data'    => ['status' => 404],
                        ],
                    ],
                ],
            ],
            'Forbidden' => [
                'description' => 'Insufficient permissions',
                'content'    => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/WP_REST_Error'],
                    ],
                ],
            ],
            'ServerError' => [
                'description' => 'Internal server error',
                'content'    => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/WP_REST_Error'],
                    ],
                ],
            ],
        ];
    }


    /**
     * Security schemes per WordPress REST API authentication docs.
     *
     * @return array<string, array>
     * @see https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/
     */
    public static function get_security_schemes(): array {
        return [
            'cookieAuth' => [
                'type'        => 'apiKey',
                'in'          => 'cookie',
                'name'        => 'wordpress_logged_in',
                'description' => 'Cookie auth for logged-in users. For same-origin AJAX, prefer X-WP-Nonce header. Per https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/',
            ],
            'nonceAuth' => [
                'type'        => 'apiKey',
                'in'          => 'header',
                'name'        => 'X-WP-Nonce',
                'description' => 'Nonce for CSRF protection. Use wp_create_nonce(\'wp_rest\'). Required for same-origin authenticated requests. Per https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/',
            ],
            'applicationPassword' => [
                'type'        => 'http',
                'scheme'      => 'basic',
                'description' => 'Application Passwords (WordPress 5.6+). Create in Users > Profile > Application Passwords. Use with Basic auth over HTTPS. Per https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/',
            ],
            'wcBasicAuth' => [
                'type'        => 'http',
                'scheme'      => 'basic',
                'description' => 'WooCommerce REST API. Consumer Key as username, Consumer Secret as password. Create in WooCommerce > Settings > Advanced > REST API. Per https://github.com/woocommerce/woocommerce-rest-api-docs',
            ],
        ];
    }

}
