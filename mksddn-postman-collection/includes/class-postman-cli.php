<?php

/**
 * WP-CLI commands for MksDdn Postman Collection.
 */
class Postman_CLI {

    /**
     * Export Postman collection to a file or STDOUT.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : Output file path. If omitted, prints to STDOUT.
     *
     * [--pages=<slugs>]
     * : Comma-separated page slugs to include as individual requests.
     *
     * ## EXAMPLES
     *     wp mksddn-postman export --file=postman_collection.json
     *     wp mksddn-postman export --pages=home,about
     */
    public function export(array $args, array $assoc_args): void {
        $pages_param = isset($assoc_args['pages']) ? (string) $assoc_args['pages'] : '';
        $page_slugs = $pages_param !== ''
            ? array_values(array_filter(array_map('sanitize_title', explode(',', $pages_param))))
            : [];

        $generator = new Postman_Generator();
        $collection = $generator->generate_collection_array($page_slugs);

        $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (isset($assoc_args['file']) && $assoc_args['file'] !== '') {
            $file = (string) $assoc_args['file'];
            $result = file_put_contents($file, $json);
            if ($result === false) {
                WP_CLI::error('Failed to write file.');
                return;
            }
            WP_CLI::success('Collection exported to ' . $file);
            return;
        }

        // Print to STDOUT
        WP_CLI::line($json);
    }
}


