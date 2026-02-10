<?php

/**
 * @file: includes/class-postman-openapi-converter.php
 * @description: Convert Postman Collection to OpenAPI 3.0 specification.
 * @dependencies: Postman_OpenAPI_Schemas
 * @created: 2025-02-10
 */
class Postman_OpenAPI_Converter {

    private const OPENAPI_VERSION = '3.0.3';

    private string $base_url;

    private array $paths = [];

    private int $operation_counter = 0;


    public function __construct(?string $base_url = null) {
        $this->base_url = $base_url ?? home_url();
    }


    /**
     * Convert Postman Collection array to OpenAPI 3.0 spec.
     *
     * @param array $collection Postman Collection v2.1.0 structure
     * @return array OpenAPI 3.0 specification
     */
    public function convert(array $collection): array {
        $this->paths = [];
        $this->operation_counter = 0;
        $variables = $this->extract_variables($collection);
        $base_url = $variables['baseUrl'] ?? $this->base_url;
        $base_url = rtrim($base_url, '/');

        $this->process_items($collection['item'] ?? [], $base_url);

        $info = [
            'title'       => $collection['info']['name'] ?? 'WordPress REST API',
            'description' => 'OpenAPI 3.0 specification for WordPress REST API. Compatible with wp/v2 endpoints (posts, pages, terms, users, etc.) and custom namespaces. Aligned with [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/). Authentication: Cookie/Nonce for same-origin, Application Passwords for external apps.',
            'version'     => '1.0.0',
            'externalDocs' => [
                'description' => 'WordPress REST API Handbook',
                'url'         => 'https://developer.wordpress.org/rest-api/',
            ],
        ];

        $servers = [
            [
                'url'         => $base_url,
                'description' => 'WordPress site URL',
            ],
        ];

        $spec = [
            'openapi' => self::OPENAPI_VERSION,
            'info'    => $info,
            'servers' => $servers,
            'paths'   => $this->paths,
            'components' => [
                'schemas'   => Postman_OpenAPI_Schemas::get_schemas(),
                'responses' => Postman_OpenAPI_Schemas::get_responses(),
                'securitySchemes' => Postman_OpenAPI_Schemas::get_security_schemes(),
            ],
        ];

        return $this->apply_filters($spec, $collection);
    }


    /**
     * @param array $spec      OpenAPI spec
     * @param array $collection Postman collection
     * @return array
     */
    private function apply_filters(array $spec, array $collection): array {
        return (array) apply_filters('mksddn_postman_openapi_spec', $spec, $collection);
    }


    private function extract_variables(array $collection): array {
        $vars = [];
        foreach ($collection['variable'] ?? [] as $v) {
            if (isset($v['key'], $v['value'])) {
                $vars[$v['key']] = $v['value'];
            }
        }
        return $vars;
    }


    /**
     * Recursively process Postman collection items.
     *
     * @param array  $items    Postman items (folders or requests)
     * @param string $base_url Base URL for resolving paths
     */
    private function process_items(array $items, string $base_url): void {
        foreach ($items as $item) {
            $item_name = $item['name'] ?? '';
            
            // Skip "Specific Pages" folder - these duplicate "by Slug" functionality
            if ($item_name === 'Specific Pages') {
                continue;
            }
            
            if (isset($item['item'])) {
                $this->process_items($item['item'], $base_url);
            } elseif (isset($item['request'])) {
                $this->convert_request($item['request'], $item_name, $base_url);
            }
        }
    }


    /**
     * Convert single Postman request to OpenAPI path operation.
     */
    private function convert_request(array $request, string $name, string $base_url): void {
        $url = $request['url'] ?? [];
        if (is_string($url)) {
            $url = ['raw' => $url];
        }

        $method = strtoupper($request['method'] ?? 'GET');
        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $path = $this->build_openapi_path($url, $base_url);
        if ($path === null) {
            return;
        }

        $operation_id = $this->generate_operation_id($name, $path, $method);
        $parameters = $this->convert_parameters($url, $request);
        
        $postman_description = trim($request['description'] ?? '');
        if ($this->is_good_description($postman_description, $name)) {
            $description = $postman_description;
        } else {
            $description = $this->generate_description_from_path($path, $method, $parameters);
        }

        $operation = [
            'operationId' => $operation_id,
            'summary'     => $name,
            'responses'   => $this->get_default_responses($method),
        ];

        if (!empty($description) && strlen($description) >= 10) {
            $operation['description'] = $description;
        }
        if ($parameters !== []) {
            $operation['parameters'] = $parameters;
        }

        if ($method === 'GET' && !str_contains($path, '{') && !str_ends_with($path, '/settings') && isset($operation['responses']['200'])) {
            $operation['responses']['200']['headers'] = $this->get_pagination_response_headers();
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $operation['security'] = [
                ['cookieAuth' => []],
                ['nonceAuth' => []],
                ['applicationPassword' => []],
            ];
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $request_body = $this->convert_request_body($request);
            if ($request_body !== null) {
                $operation['requestBody'] = $request_body;
            }
        }

        $tags = $this->extract_tags_from_path($path);
        if ($tags !== []) {
            $operation['tags'] = $tags;
        }

        if (!isset($this->paths[$path])) {
            $this->paths[$path] = [];
        }
        $method_key = strtolower($method);
        if (!isset($this->paths[$path][$method_key])) {
            $this->paths[$path][$method_key] = $operation;
        } else {
            $this->paths[$path][$method_key] = $this->merge_operations($this->paths[$path][$method_key], $operation, $path, $method);
        }
    }


    /**
     * Build OpenAPI path from Postman URL object.
     * Converts {{baseUrl}}/wp-json/wp/v2/posts/{{PostID}} to /wp-json/wp/v2/posts/{postId}
     */
    private function build_openapi_path(array $url, string $base_url): ?string {
        $path_parts = $url['path'] ?? [];
        if (!is_array($path_parts)) {
            return null;
        }

        $segments = [];
        foreach ($path_parts as $segment) {
            $segment = (string) $segment;
            if (str_starts_with($segment, '{{') && str_ends_with($segment, '}}')) {
                $param = substr($segment, 2, -2);
                $param = $this->to_camel_case_param($param);
                $segments[] = '{' . $param . '}';
            } else {
                $segments[] = $segment;
            }
        }

        $path = '/' . implode('/', $segments);
        return $path !== '/' ? $path : null;
    }


    private function to_camel_case_param(string $name): string {
        if (str_ends_with($name, 'ID')) {
            return strtolower(substr($name, 0, -2)) . 'Id';
        }
        return lcfirst(str_replace(['.', '-', ' '], '', ucwords($name, '._- ')));
    }


    private function convert_parameters(array $url, array $request): array {
        $params = [];

        foreach ($url['query'] ?? [] as $q) {
            if (empty($q['key'])) {
                continue;
            }
            $key = $q['key'];
            $postman_value = $q['value'] ?? null;

            $param = [
                'name'     => $key,
                'in'       => 'query',
                'required' => false,
                'schema'   => $this->get_param_schema($key, $postman_value),
            ];
            if (!empty($q['description'])) {
                $param['description'] = $q['description'];
            } else {
                $param['description'] = Postman_Param_Descriptions::get_query($key);
            }
            $params[] = $param;
        }

        $path_parts = $url['path'] ?? [];
        foreach ($path_parts as $segment) {
            $segment = (string) $segment;
            if (str_starts_with($segment, '{{') && str_ends_with($segment, '}}')) {
                $param = substr($segment, 2, -2);
                $param_id = $this->to_camel_case_param($param);
                $description = str_ends_with($param, 'ID')
                    ? sprintf('ID of the %s', str_replace('ID', '', $param))
                    : 'URL-friendly slug or identifier';
                $schema = str_ends_with($param, 'ID')
                    ? ['type' => 'integer', 'format' => 'int64']
                    : ['type' => 'string'];
                $params[] = [
                    'name'        => $param_id,
                    'in'          => 'path',
                    'required'    => true,
                    'description' => $description,
                    'schema'      => $schema,
                ];
            }
        }

        foreach ($request['header'] ?? [] as $h) {
            if (empty($h['key']) || !empty($h['disabled']) || strtolower($h['key']) === 'content-type') {
                continue;
            }
            $header_desc = $h['description'] ?? Postman_Param_Descriptions::get_header($h['key']);
            $params[] = [
                'name'        => $h['key'],
                'in'          => 'header',
                'description' => $header_desc,
                'required'    => false,
                'schema'      => [
                    'type'    => 'string',
                    'default' => $h['value'] ?? null,
                ],
            ];
        }

        return $params;
    }


    private function convert_request_body(array $request): ?array {
        $body = $request['body'] ?? [];
        if (empty($body)) {
            return null;
        }

        if (($body['mode'] ?? '') === 'raw' && !empty($body['raw'])) {
            $raw = $body['raw'];
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return [
                        'required' => true,
                        'content'  => [
                            'application/json' => [
                                'schema'   => [
                                    'type'       => 'object',
                                    'properties' => $this->infer_schema_from_object($decoded),
                                ],
                                'example'  => $decoded,
                            ],
                        ],
                    ];
                }
            }
        }

        if (($body['mode'] ?? '') === 'formdata' && !empty($body['formdata'])) {
            $properties = [];
            $required = [];
            foreach ($body['formdata'] as $fd) {
                $key = $fd['key'] ?? '';
                if ($key === '') {
                    continue;
                }
                $is_file = ($fd['type'] ?? 'text') === 'file';
                $desc = $fd['description'] ?? ($is_file ? 'File upload' : 'Form field value');
                $properties[$key] = [
                    'type'        => 'string',
                    'description' => $desc,
                ];
                if ($is_file) {
                    $properties[$key]['format'] = 'binary';
                }
                if (!empty($fd['required'])) {
                    $required[] = $key;
                }
            }
            if ($properties !== []) {
                return [
                    'required' => !empty($required),
                    'content'  => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type'       => 'object',
                                'properties' => $properties,
                                'required'   => $required,
                            ],
                        ],
                    ],
                ];
            }
        }

        return null;
    }


    private function infer_schema_from_object(array $obj): array {
        $properties = [];
        foreach ($obj as $key => $value) {
            if (is_int($value)) {
                $properties[$key] = ['type' => 'integer'];
            } elseif (is_float($value)) {
                $properties[$key] = ['type' => 'number'];
            } elseif (is_bool($value)) {
                $properties[$key] = ['type' => 'boolean'];
            } elseif (is_array($value)) {
                $properties[$key] = ['type' => 'array', 'items' => ['type' => 'string']];
            } else {
                $properties[$key] = ['type' => 'string'];
            }
            $desc = Postman_Param_Descriptions::get_request_body($key);
            if ($desc !== '') {
                $properties[$key]['description'] = $desc;
            }
        }
        return $properties;
    }


    private function get_default_responses(string $method): array {
        $responses = [
            '200' => [
                'description' => 'Successful response',
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            'oneOf' => [
                                ['type' => 'object'],
                                ['type' => 'array', 'items' => ['type' => 'object']],
                            ],
                        ],
                    ],
                ],
            ],
            '201' => [
                'description' => 'Resource created',
                'content'     => [
                    'application/json' => [
                        'schema' => ['type' => 'object'],
                    ],
                ],
            ],
            '401' => ['$ref' => '#/components/responses/Unauthorized'],
            '403' => ['$ref' => '#/components/responses/Forbidden'],
            '404' => ['$ref' => '#/components/responses/NotFound'],
            '500' => ['$ref' => '#/components/responses/ServerError'],
        ];

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return $responses;
        }
        unset($responses['201']);
        return $responses;
    }


    /**
     * Add pagination headers for GET list endpoints (per WordPress REST API).
     * X-WP-Total: total records; X-WP-TotalPages: total pages.
     * @see https://developer.wordpress.org/rest-api/using-the-rest-api/pagination/
     */
    private function get_pagination_response_headers(): array {
        return [
            'X-WP-Total' => [
                'schema' => ['type' => 'integer', 'description' => 'Total number of records in collection'],
                'description' => 'Total number of records in the collection. Per WordPress REST API pagination.',
            ],
            'X-WP-TotalPages' => [
                'schema' => ['type' => 'integer', 'description' => 'Total number of pages'],
                'description' => 'Total number of pages encompassing all available records. Per WordPress REST API pagination.',
            ],
        ];
    }


    private function generate_operation_id(string $name, string $path, string $method): string {
        $this->operation_counter++;
        $safe = preg_replace('/[^a-zA-Z0-9]+/', '_', $name);
        $safe = trim($safe, '_');
        if ($safe === '') {
            $safe = 'operation';
        }
        return strtolower($safe) . '_' . $this->operation_counter;
    }


    private function extract_tags_from_path(string $path): array {
        if (preg_match('#/wp-json/([^/]+)/v\d+/([^/]+)#', $path, $m)) {
            return [$m[1] . ' - ' . $m[2]];
        }
        return [];
    }


    /**
     * Merge two operations for same path+method. Combines parameters, keeps first operation.
     * For GET list/by-slug: updates description to explain both use cases.
     */
    private function merge_operations(array $existing, array $incoming, string $path, string $method): array {
        $param_names = [];
        foreach ($existing['parameters'] ?? [] as $p) {
            $param_names[$p['name'] . ':' . ($p['in'] ?? '')] = $p;
        }
        foreach ($incoming['parameters'] ?? [] as $p) {
            $key = $p['name'] . ':' . ($p['in'] ?? '');
            if (!isset($param_names[$key])) {
                $param_names[$key] = $p;
            }
        }
        if ($param_names !== []) {
            $existing['parameters'] = $this->sort_parameters(array_values($param_names));
        }

        if ($method === 'GET' && !str_contains($path, '{')) {
            $existing['description'] = $this->get_list_or_by_slug_description($path);
        }

        return $existing;
    }


    /**
     * Description for GET endpoints that support both list and by-slug.
     */
    private function get_list_or_by_slug_description(string $path): string {
        $entity = $this->extract_entity_from_path($path);
        if ($entity === '') {
            return 'List all items or get a specific item by slug. Use slug parameter for a single item.';
        }
        $entity_singular = $this->get_singular_form($entity);
        return sprintf(
            'List all %s with pagination. To get a specific %s, use the slug parameter (e.g. slug=home).',
            $entity,
            $entity_singular
        );
    }


    private function extract_entity_from_path(string $path): string {
        $path_parts = array_filter(explode('/', trim($path, '/')));
        $path_parts = array_values($path_parts);
        return $path_parts[count($path_parts) - 1] ?? '';
    }


    private function sort_parameters(array $params): array {
        $order = ['path' => 0, 'query' => 1, 'header' => 2];
        usort($params, function ($a, $b) use ($order) {
            $a_in = $a['in'] ?? 'query';
            $b_in = $b['in'] ?? 'query';
            return ($order[$a_in] ?? 1) <=> ($order[$b_in] ?? 1);
        });
        return $params;
    }


    /**
     * Generate description from path, method and parameters.
     * Analyzes the API endpoint structure to create meaningful descriptions.
     */
    private function generate_description_from_path(string $path, string $method, array $parameters): string {
        $path_parts = array_filter(explode('/', trim($path, '/')));
        $path_parts = array_values($path_parts);
        
        if (empty($path_parts)) {
            return '';
        }
        
        $last_segment = end($path_parts);
        $second_last = $path_parts[count($path_parts) - 2] ?? '';
        
        // Handle special endpoints: forms submit
        if ($last_segment === 'submit' && $second_last === 'forms') {
            return match ($method) {
                'POST' => 'Submit form data',
                default => 'Perform operation on form submission',
            };
        }
        
        // Handle forms info endpoint
        if ($second_last === 'forms' && !str_starts_with($last_segment, '{')) {
            return match ($method) {
                'GET' => 'Retrieve form information',
                default => 'Perform operation on form',
            };
        }
        
        // Handle search endpoint
        if ($last_segment === 'search') {
            return match ($method) {
                'GET' => 'Search content across the site',
                default => 'Perform search operation',
            };
        }
        
        // Handle options pages
        if ($second_last === 'options' || $last_segment === 'options') {
            return match ($method) {
                'GET' => $last_segment === 'options' && empty($second_last)
                    ? 'Retrieve list of options pages'
                    : 'Retrieve options page data',
                default => 'Perform operation on options',
            };
        }
        
        // Extract entity from path
        $entity = '';
        if (str_starts_with($last_segment, '{')) {
            // Path ends with parameter: /wp-json/wp/v2/posts/{id}
            $entity = $path_parts[count($path_parts) - 2] ?? '';
        } elseif (count($path_parts) >= 2 && str_starts_with($path_parts[count($path_parts) - 2], '{')) {
            // Second to last is parameter: /wp-json/wp/v2/posts/{id}/meta
            $entity = $path_parts[count($path_parts) - 3] ?? '';
        } else {
            $entity = $last_segment;
        }
        
        if (empty($entity)) {
            return '';
        }
        
        $has_path_param = str_contains($path, '{');
        $has_slug_param = $this->has_parameter($parameters, 'slug', 'query');
        $has_search_param = $this->has_parameter($parameters, 'search', 'query');
        $has_id_param = $this->has_parameter($parameters, 'id', 'path') ||
                        $this->has_parameter($parameters, 'postId', 'path') ||
                        $this->has_parameter($parameters, 'pageId', 'path') ||
                        $this->has_parameter($parameters, 'categoryId', 'path') ||
                        $this->has_parameter($parameters, 'tagId', 'path') ||
                        $this->has_parameter($parameters, 'userId', 'path') ||
                        $this->has_parameter($parameters, 'commentId', 'path');
        
        $entity_singular = $this->get_singular_form($entity);
        
        return match ($method) {
            'GET' => $has_search_param
                ? sprintf('Search for %s', $entity)
                : ($has_path_param || $has_id_param
                    ? sprintf('Retrieve a specific %s by ID', $entity_singular)
                    : sprintf('Retrieve a list of %s', $entity)),
            'POST' => sprintf('Create a new %s', $entity_singular),
            'PUT' => sprintf('Update an existing %s', $entity_singular),
            'PATCH' => sprintf('Partially update an existing %s', $entity_singular),
            'DELETE' => sprintf('Delete a %s', $entity_singular),
            default => sprintf('Perform %s operation on %s', strtolower($method), $entity),
        };
    }


    /**
     * Check if description from Postman is good enough to use.
     */
    private function is_good_description(string $description, string $name): bool {
        if (empty($description) || $description === $name) {
            return false;
        }
        
        if (strlen($description) < 10) {
            return false;
        }
        
        // Reject descriptions with Cyrillic characters
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $description)) {
            return false;
        }
        
        $verbs = ['get', 'retrieve', 'create', 'update', 'delete', 'list', 'fetch', 'obtain', 'submit', 'search'];
        $lower = strtolower($description);
        foreach ($verbs as $verb) {
            if (str_contains($lower, $verb)) {
                return true;
            }
        }
        
        return false;
    }


    /**
     * Check if parameter exists in parameters array.
     */
    private function has_parameter(array $parameters, string $name, string $in): bool {
        foreach ($parameters as $p) {
            if (($p['name'] ?? '') === $name && ($p['in'] ?? '') === $in) {
                return true;
            }
        }
        return false;
    }


    /**
     * Get singular form of entity name.
     */
    private function get_singular_form(string $entity): string {
        $irregular = [
            'pages' => 'page',
            'posts' => 'post',
            'categories' => 'category',
            'tags' => 'tag',
            'comments' => 'comment',
            'users' => 'user',
            'settings' => 'setting',
            'media' => 'media',
            'taxonomies' => 'taxonomy',
        ];
        
        if (isset($irregular[$entity])) {
            return $irregular[$entity];
        }
        
        if (str_ends_with($entity, 'ies')) {
            return substr($entity, 0, -3) . 'y';
        }
        
        if (str_ends_with($entity, 'es')) {
            return substr($entity, 0, -2);
        }
        
        if (str_ends_with($entity, 's')) {
            return substr($entity, 0, -1);
        }
        
        return $entity;
    }


    /**
     * Infer parameter type from value.
     */
    private function infer_param_type(mixed $value): string {
        if (is_numeric($value)) {
            return str_contains((string) $value, '.') ? 'number' : 'integer';
        }
        if (is_bool($value) || $value === 'true' || $value === 'false') {
            return 'boolean';
        }
        return 'string';
    }


    /**
     * Get parameter schema per WordPress REST API specification.
     * Uses official defaults; Postman values used only as examples where no default exists.
     */
    private function get_param_schema(string $key, mixed $postman_value): array {
        $schema = match ($key) {
            'page' => [
                'type'    => 'integer',
                'default' => 1,
                'minimum' => 1,
            ],
            'per_page' => [
                'type'    => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
            ],
            'offset' => [
                'type'    => 'integer',
                'minimum' => 0,
            ],
            'context' => [
                'type'    => 'string',
                'default' => 'view',
                'enum'    => ['view', 'embed', 'edit'],
            ],
            'order' => [
                'type'    => 'string',
                'default' => 'desc',
                'enum'    => ['asc', 'desc'],
            ],
            'orderby' => [
                'type'    => 'string',
                'default' => 'date',
                'enum'    => ['author', 'date', 'id', 'include', 'modified', 'parent', 'relevance', 'slug', 'include_slugs', 'title', 'menu_order', 'comment_count', 'rand'],
            ],
            'status' => [
                'type'    => 'string',
                'default' => 'publish',
            ],
            'slug' => [
                'type' => 'string',
            ],
            '_fields' => [
                'type' => 'string',
            ],
            '_embed' => [
                'type'    => 'string',
                'example' => 'author,wp:term',
            ],
            'acf_format' => [
                'type'    => 'string',
                'default' => 'standard',
            ],
            'search' => [
                'type' => 'string',
            ],
            'type' => [
                'type' => 'string',
            ],
            'categories' => [
                'type' => 'string',
            ],
            'parent' => [
                'type' => 'string',
            ],
            'force' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            default => [
                'type' => $this->infer_param_type($postman_value ?? ''),
            ],
        };

        if (!isset($schema['default']) && !isset($schema['example']) && $postman_value !== null && $postman_value !== '') {
            $schema['example'] = is_array($postman_value) ? $postman_value : (string) $postman_value;
        }

        return $schema;
    }

}
