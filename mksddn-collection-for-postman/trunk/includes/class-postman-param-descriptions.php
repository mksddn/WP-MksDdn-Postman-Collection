<?php

/**
 * @file: includes/class-postman-param-descriptions.php
 * @description: Centralized descriptions for query params and headers. Used by Postman_Routes and Postman_OpenAPI_Converter.
 * @dependencies: None
 * @created: 2025-02-10
 */
class Postman_Param_Descriptions {

    private const QUERY = [
        'page'       => 'Current page number. Per https://developer.wordpress.org/rest-api/using-the-rest-api/pagination/',
        'per_page'   => 'Maximum number of items per page (max 100). Per https://developer.wordpress.org/rest-api/using-the-rest-api/pagination/',
        'offset'     => 'Number of items to skip in result set',
        'slug'       => 'Limit result to item(s) with specific slug(s). Use for single item by slug.',
        '_fields'    => 'Comma-separated fields to include. Supports dot notation. Per https://developer.wordpress.org/rest-api/using-the-rest-api/global-parameters/',
        '_embed'     => 'Include linked resources (e.g. author, wp:term). Per https://developer.wordpress.org/rest-api/using-the-rest-api/global-parameters/',
        'acf_format' => 'ACF fields format when Advanced Custom Fields plugin is active (e.g. standard)',
        'search'     => 'Filter results by search string',
        'type'       => 'Content type for search: post, page, etc.',
        'categories' => 'Filter posts by category ID(s). Comma-separated for multiple.',
        'parent'     => 'Filter by parent ID. For hierarchical items (pages, categories).',
        'context'    => 'Response scope: view, embed, or edit. Determines which fields are returned. Per https://developer.wordpress.org/rest-api/using-the-rest-api/global-parameters/',
        'order'      => 'Sort order: asc or desc',
        'orderby'    => 'Field to sort by: date, title, id, slug, etc. Per WP REST API.',
        'status'     => 'Filter by post status: publish, draft, pending, private, etc.',
        'force'      => 'Bypass Trash on delete. Use force=true for permanent deletion.',
    ];

    private const HEADERS = [
        'Accept-Language' => 'Preferred language (RFC 5646). For multilingual sites (e.g. Polylang).',
        'X-WP-Nonce'      => 'Nonce for same-origin auth. Get via wp_create_nonce(\'wp_rest\'). Required for POST/PUT/PATCH/DELETE when logged in.',
    ];

    private const REQUEST_BODY = [
        'title'   => 'The title of the post/page',
        'content' => 'The content of the post/page. HTML supported.',
        'excerpt' => 'Short excerpt or summary',
        'status'  => 'Post status: publish, draft, pending, private. Per WP REST API.',
    ];

    /**
     * Get description for query parameter.
     */
    public static function get_query(string $key): string {
        return self::QUERY[$key] ?? '';
    }

    /**
     * Get description for header.
     */
    public static function get_header(string $key): string {
        return self::HEADERS[$key] ?? '';
    }

    /**
     * Get description for request body property.
     */
    public static function get_request_body(string $key): string {
        return self::REQUEST_BODY[$key] ?? '';
    }

    /**
     * Add descriptions to query params array. Mutates in place.
     */
    public static function enrich_query_params(array &$params): void {
        foreach ($params as &$p) {
            if (isset($p['key']) && empty($p['description'])) {
                $desc = self::get_query($p['key']);
                if ($desc !== '') {
                    $p['description'] = $desc;
                }
            }
        }
    }

    /**
     * Add descriptions to header params array. Mutates in place.
     */
    public static function enrich_headers(array &$headers): void {
        foreach ($headers as &$h) {
            if (isset($h['key']) && empty($h['description'])) {
                $desc = self::get_header($h['key']);
                if ($desc !== '') {
                    $h['description'] = $desc;
                }
            }
        }
    }
}
