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
     * Only includes schemas that are referenced in the spec (WP_REST_Error in responses).
     *
     * @return array<string, array>
     */
    public static function get_schemas(): array {
        return [
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
     * wcBasicAuth included only when collection has WooCommerce paths (avoids "defined but never used").
     *
     * @param bool $include_woocommerce Whether collection has WooCommerce paths
     * @return array<string, array>
     * @see https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/
     */
    public static function get_security_schemes(bool $include_woocommerce = false): array {
        $schemes = [
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
        ];
        if ($include_woocommerce) {
            $schemes['wcBasicAuth'] = [
                'type'        => 'http',
                'scheme'      => 'basic',
                'description' => 'WooCommerce REST API. Consumer Key as username, Consumer Secret as password. Create in WooCommerce > Settings > Advanced > REST API. Per https://github.com/woocommerce/woocommerce-rest-api-docs',
            ];
        }
        return $schemes;
    }

}
