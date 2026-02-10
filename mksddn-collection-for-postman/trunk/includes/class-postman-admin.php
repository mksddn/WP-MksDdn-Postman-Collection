<?php

/**
 * @file: includes/class-postman-admin.php
 * @description: Admin UI for generating and downloading Postman Collection.
 * @dependencies: Postman_Generator, Postman_Options
 * @created: 2025-08-19
 */
class Postman_Admin {

    private const MENU_SLUG = 'postman-collection-admin';

    private const NONCE_ACTION = 'generate_postman_collection';

    private const CAPABILITY = 'manage_options';

    private readonly Postman_Options $options_handler;


    public function __construct() {
        $this->options_handler = new Postman_Options();

        add_action('admin_menu', [ $this, 'add_admin_menu' ]);
        add_action('admin_post_generate_postman_collection', [ $this, 'handle_generation' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ]);
    }


    public function add_admin_menu(): void {
        add_menu_page(
            esc_html__('Postman Collection', 'mksddn-collection-for-postman'),
            esc_html__('Postman Collection', 'mksddn-collection-for-postman'),
            $this->get_required_capability(),
            self::MENU_SLUG,
            $this->admin_page(...),
            'dashicons-share-alt2',
            80
        );
    }


    public function admin_page(): void {
        if (!current_user_can($this->get_required_capability())) {
            return;
        }

        $data = $this->get_page_data();
        $this->render_admin_page($data);
    }


    private function get_page_data(): array {
        $post_types = get_post_types(['public' => true], 'objects');
        $custom_post_types = Postman_Routes::filter_custom_post_types($post_types);

        return [
            'woocommerce_active' => $this->is_woocommerce_active(),
            'pages' => $this->get_pages(),
            'posts' => $this->get_posts(),
            'custom_post_types' => $custom_post_types,
            'custom_posts' => $this->get_custom_posts($custom_post_types),
            'options_pages' => $this->options_handler->get_options_pages(),
            'options_pages_data' => $this->options_handler->get_options_pages_data(),
            'selected_page_slugs' => $this->get_selected_page_slugs(),
            'selected_post_slugs' => $this->get_selected_post_slugs(),
            'selected_custom_slugs' => $this->get_selected_custom_slugs(),
            'selected_options_pages' => $this->get_selected_options_pages(),
            'selected_custom_post_types' => $this->get_selected_custom_post_types(),
            'include_woocommerce' => $this->get_include_woocommerce(),
        ];
    }


    private function is_woocommerce_active(): bool {
        return class_exists('WooCommerce');
    }


    private function get_include_woocommerce(): bool {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_generation; used for form repopulation.
        if (!isset($_POST['include_woocommerce'])) {
            return true;
        }
        return sanitize_key((string) $_POST['include_woocommerce']) === '1';
    }


    private function get_pages(): array {
        return get_posts([
            'post_type'              => 'page',
            'posts_per_page'         => -1,
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'post_status'            => 'publish',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
    }


    private function get_posts(): array {
        return get_posts([
            'post_type'              => 'post',
            'posts_per_page'         => -1,
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'post_status'            => 'publish',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
    }

    private function get_custom_posts(array $custom_post_types): array {
        $custom_posts = [];
        $query_args = [
            'posts_per_page'         => -1,
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'post_status'            => 'publish',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];
        foreach (array_keys($custom_post_types) as $post_type_name) {
            $custom_posts[$post_type_name] = get_posts(array_merge(
                $query_args,
                ['post_type' => $post_type_name]
            ));
        }

        return $custom_posts;
    }


    private function get_selected_page_slugs(): array {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Used only to pre-fill admin form; nonce is verified on action submit and values are sanitized below.
        $slugs = isset($_POST['custom_page_slugs']) ? (array) wp_unslash($_POST['custom_page_slugs']) : [];
        return array_values(array_filter(array_map('sanitize_title', $slugs)));
    }


    private function get_selected_post_slugs(): array {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Used only to pre-fill admin form; nonce is verified on action submit and values are sanitized below.
        $slugs = isset($_POST['custom_post_slugs']) ? (array) wp_unslash($_POST['custom_post_slugs']) : [];
        return array_values(array_filter(array_map('sanitize_title', $slugs)));
    }


    private function get_selected_custom_slugs(): array {
        $result = [];
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Used only to pre-fill admin form; nonce is verified on action submit and values are sanitized below.
        $incoming = isset($_POST['custom_post_type_slugs']) ? (array) wp_unslash($_POST['custom_post_type_slugs']) : [];
        foreach ($incoming as $type => $slugs) {
            $type_key = sanitize_key((string) $type);
            $result[$type_key] = array_values(array_filter(array_map('sanitize_title', (array) $slugs)));
        }
        return $result;
    }


    private function get_selected_options_pages(): array {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Used only to pre-fill admin form; nonce is verified on action submit and values are sanitized below.
        $pages = isset($_POST['options_pages']) ? (array) wp_unslash($_POST['options_pages']) : [];
        return array_values(array_filter(array_map('sanitize_key', $pages)));
    }


    private function get_selected_custom_post_types(): array {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Used only to pre-fill admin form; nonce is verified on action submit and values are sanitized below.
        $types = isset($_POST['custom_post_types']) ? (array) wp_unslash($_POST['custom_post_types']) : [];
        return array_values(array_filter(array_map('sanitize_key', $types)));
    }


    private function get_export_format(): string {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_generation.
        $format = isset($_POST['export_format']) ? sanitize_key((string) $_POST['export_format']) : 'postman';
        return $format === 'openapi' ? 'openapi' : 'postman';
    }


    private function render_admin_page(array $data): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Generate Postman Collection', 'mksddn-collection-for-postman') . '</h1>';

        $this->render_form($data);

        echo '</div>';
    }


    private function render_form(array $data): void {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field(self::NONCE_ACTION);
        echo '<input type="hidden" name="action" value="generate_postman_collection">';

        $this->render_block_start(__('Add individual requests for pages:', 'mksddn-collection-for-postman'));
        $this->render_selection_buttons('custom_page_slugs');
        $this->render_pages_list($data['pages'], $data['selected_page_slugs']);
        $this->render_block_end();

        if (!empty($data['custom_post_types'])) {
            $this->render_block_start(__('Add requests for Custom Post Types:', 'mksddn-collection-for-postman'));
            $this->render_selection_buttons('custom_post_types');
            $this->render_custom_post_types_list($data['custom_post_types'], $data['selected_custom_post_types']);
            $this->render_block_end();
        }

        if (!empty($data['woocommerce_active'])) {
            $this->render_block_start(__('WooCommerce REST API:', 'mksddn-collection-for-postman'));
            echo '<div class="postman-admin-block__content postman-admin-block__content--options">';
            echo '<input type="hidden" name="include_woocommerce" value="0">';
            echo '<label><input type="checkbox" name="include_woocommerce" value="1"';
            checked($data['include_woocommerce'], true);
            echo '> ' . esc_html__('Include WooCommerce REST API (products, categories, orders)', 'mksddn-collection-for-postman') . '</label>';
            echo '</div>';
            echo '<p class="postman-admin-block__description">' . esc_html__('Requires WooCommerce. Auth: Consumer Key + Secret (Settings > Advanced > REST API).', 'mksddn-collection-for-postman') . '</p>';
            $this->render_block_end();
        }

        $this->render_block_start(__('Export format:', 'mksddn-collection-for-postman'));
        echo '<div class="postman-admin-block__content postman-admin-block__content--options">';
        echo '<label><input type="radio" name="export_format" value="postman" checked> ' . esc_html__('Postman Collection (JSON)', 'mksddn-collection-for-postman') . '</label>';
        echo '<label><input type="radio" name="export_format" value="openapi"> ' . esc_html__('OpenAPI 3.0 (JSON)', 'mksddn-collection-for-postman') . '</label>';
        echo '</div>';
        $this->render_block_end();

        echo '<p class="postman-admin-submit"><button class="button button-primary" name="generate_postman">' . esc_html__('Generate and download', 'mksddn-collection-for-postman') . '</button></p>';
        echo '</form>';
    }


    private function render_block_start(string $title): void {
        echo '<div class="postman-admin-block">';
        echo '<h3 class="postman-admin-block__title">' . esc_html($title) . '</h3>';
    }


    private function render_block_end(): void {
        echo '</div>';
    }


    private function render_selection_buttons(string $field_name): void {
        $escaped = esc_js($field_name);
        echo '<div class="postman-admin-block__actions">';
        echo '<button type="button" class="button" onclick="selectAll(\'' . $escaped . '\')">' . esc_html__('Select All', 'mksddn-collection-for-postman') . '</button> ';
        echo '<button type="button" class="button" onclick="deselectAll(\'' . $escaped . '\')">' . esc_html__('Deselect All', 'mksddn-collection-for-postman') . '</button>';
        echo '</div>';
    }


    private function render_pages_list(array $pages, array $selected_slugs): void {
        echo '<div class="postman-admin-block__content postman-admin-block__content--scrollable"><ul>';
        foreach ($pages as $page) {
            $slug = $page->post_name;
            echo '<li><label><input type="checkbox" name="custom_page_slugs[]" value="' . esc_attr($slug) . '"';
            checked(in_array($slug, $selected_slugs, true), true);
            echo '> ' . esc_html($page->post_title) . ' <span class="postman-admin-block__slug">(' . esc_html($slug) . ')</span></label></li>';
        }
        echo '</ul></div>';
    }


    private function render_custom_post_types_list(array $custom_post_types, array $selected_types): void {
        echo '<div class="postman-admin-block__content postman-admin-block__content--scrollable"><ul>';
        foreach ($custom_post_types as $post_type_name => $post_type_obj) {
            $type_label = isset($post_type_obj->labels->name) ? (string) $post_type_obj->labels->name : ucfirst((string) $post_type_name);
            echo '<li><label><input type="checkbox" name="custom_post_types[]" value="' . esc_attr($post_type_name) . '" class="cpt-selector" data-cpt="' . esc_attr($post_type_name) . '"';
            checked(in_array($post_type_name, $selected_types, true), true);
            echo '> ' . esc_html($type_label) . ' <span class="postman-admin-block__slug">(' . esc_html($post_type_name) . ')</span></label></li>';
        }
        echo '</ul></div>';
    }


    public function enqueue_admin_scripts(string $hook): void {
        if ($hook !== 'toplevel_page_' . self::MENU_SLUG) {
            return;
        }

        wp_enqueue_style(
            'mksddn-postman-admin',
            POSTMAN_PLUGIN_URL . 'assets/css/admin.css',
            [],
            POSTMAN_PLUGIN_VERSION
        );

        $script_content = "
        function selectAll(name) {
            document.querySelectorAll('input[name=\"' + name + '[]\"]').forEach(checkbox => checkbox.checked = true);
        }

        function deselectAll(name) {
            document.querySelectorAll('input[name=\"' + name + '[]\"]').forEach(checkbox => checkbox.checked = false);
        }

        function selectAllCustom(name) {
            document.querySelectorAll('input[name=\"custom_post_type_slugs[' + name + '][]\"]').forEach(checkbox => checkbox.checked = true);
        }

        function deselectAllCustom(name) {
            document.querySelectorAll('input[name=\"custom_post_type_slugs[' + name + '][]\"]').forEach(checkbox => checkbox.checked = false);
        }
        ";

        wp_add_inline_script('jquery', $script_content);
    }


    public function handle_generation(): void {
        if (!current_user_can($this->get_required_capability()) || !check_admin_referer(self::NONCE_ACTION)) {
            wp_die(esc_html__('Insufficient permissions or invalid nonce.', 'mksddn-collection-for-postman'));
        }
        $acf_active = Postman_Routes::is_acf_or_scf_active();
        $selected_custom_post_types = $this->get_selected_custom_post_types();

        $selected_data = [
            'page_slugs' => $this->get_selected_page_slugs(),
            'post_slugs' => $this->get_selected_post_slugs(),
            'custom_slugs' => $this->get_selected_custom_slugs(),
            'options_pages' => $this->get_selected_options_pages(),
            'custom_post_types' => $selected_custom_post_types,
            'include_woocommerce' => $this->get_include_woocommerce(),
        ];

        $generator = new Postman_Generator();
        $generator->generate_and_download(
            $selected_data['page_slugs'],
            $selected_data['post_slugs'],
            $selected_data['custom_slugs'],
            $selected_data['options_pages'],
            $selected_data['custom_post_types'],
            $acf_active,
            $acf_active,
            $acf_active ? $selected_custom_post_types : [],
            $selected_data['include_woocommerce'],
            $this->get_export_format()
        );
    }

    private function get_required_capability(): string {
        /**
         * Filter required capability for accessing plugin admin page and actions.
         *
         * @param string $capability Default capability.
         */
        return (string) apply_filters('mksddn_postman_capability', self::CAPABILITY);
    }


}
