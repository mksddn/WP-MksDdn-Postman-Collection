<?php

class Postman_Admin {

    private const MENU_SLUG = 'postman-collection-admin';

    private const NONCE_ACTION = 'generate_postman_collection';

    private const CAPABILITY = 'manage_options';

    private readonly Postman_Options $options_handler;


    public function __construct() {
        $this->options_handler = new Postman_Options();

        add_action('admin_menu', $this->add_admin_menu(...));
        add_action('admin_post_generate_postman_collection', $this->handle_generation(...));
    }


    public function add_admin_menu(): void {
        add_menu_page(
            esc_html__('Postman Collection', POSTMAN_PLUGIN_TEXT_DOMAIN),
            esc_html__('Postman Collection', POSTMAN_PLUGIN_TEXT_DOMAIN),
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
        $custom_post_types = $this->filter_custom_post_types($post_types);

        return [
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
        ];
    }


    private function filter_custom_post_types(array $post_types): array {
        $custom_post_types = [];
        foreach ($post_types as $post_type) {
            if (!in_array($post_type->name, ['page', 'post', 'attachment'])) {
                $custom_post_types[$post_type->name] = $post_type;
            }
        }

        return $custom_post_types;
    }


    private function get_pages(): array {
        return get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish',
        ]);
    }


    private function get_posts(): array {
        return get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish',
        ]);
    }


    private function get_custom_posts(array $custom_post_types): array {
        $custom_posts = [];
        foreach (array_keys($custom_post_types) as $post_type_name) {
            $custom_posts[$post_type_name] = get_posts([
                'post_type' => $post_type_name,
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            ]);
        }

        return $custom_posts;
    }


    private function get_selected_page_slugs(): array {
        $slugs = (array) ($_POST['custom_page_slugs'] ?? []);
        return array_values(array_filter(array_map('sanitize_title', $slugs)));
    }


    private function get_selected_post_slugs(): array {
        $slugs = (array) ($_POST['custom_post_slugs'] ?? []);
        return array_values(array_filter(array_map('sanitize_title', $slugs)));
    }


    private function get_selected_custom_slugs(): array {
        $result = [];
        $incoming = (array) ($_POST['custom_post_type_slugs'] ?? []);
        foreach ($incoming as $type => $slugs) {
            $type_key = sanitize_key((string) $type);
            $result[$type_key] = array_values(array_filter(array_map('sanitize_title', (array) $slugs)));
        }
        return $result;
    }


    private function get_selected_options_pages(): array {
        $pages = (array) ($_POST['options_pages'] ?? []);
        return array_values(array_filter(array_map('sanitize_key', $pages)));
    }


    private function render_admin_page(array $data): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Generate Postman Collection', POSTMAN_PLUGIN_TEXT_DOMAIN) . '</h1>';

        $this->render_form($data);
        $this->render_javascript();

        echo '</div>';
    }


    private function render_form(array $data): void {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field(self::NONCE_ACTION);
        echo '<input type="hidden" name="action" value="generate_postman_collection">';

        echo '<h3>' . esc_html__('Add individual requests for pages:', POSTMAN_PLUGIN_TEXT_DOMAIN) . '</h3>';
        $this->render_selection_buttons();
        $this->render_pages_list($data['pages'], $data['selected_page_slugs']);

        echo '<br><button class="button button-primary" name="generate_postman">' . esc_html__('Generate and download collection', POSTMAN_PLUGIN_TEXT_DOMAIN) . '</button>';
        echo '</form>';
    }


    private function render_selection_buttons(): void {
        echo '<div style="margin-bottom: 10px;">';
        echo '<button type="button" class="button" onclick="selectAll(\'custom_page_slugs\')">' . esc_html__('Select All', POSTMAN_PLUGIN_TEXT_DOMAIN) . '</button> ';
        echo '<button type="button" class="button" onclick="deselectAll(\'custom_page_slugs\')">' . esc_html__('Deselect All', POSTMAN_PLUGIN_TEXT_DOMAIN) . '</button>';
        echo '</div>';
    }


    private function render_pages_list(array $pages, array $selected_slugs): void {
        echo '<ul style="max-height:200px;overflow:auto;border:1px solid #eee;padding:10px;margin-bottom:20px;">';
        foreach ($pages as $page) {
            $slug = $page->post_name;
            $checked = in_array($slug, $selected_slugs) ? 'checked' : '';
            echo '<li><label><input type="checkbox" name="custom_page_slugs[]" value="' . esc_attr($slug) . '" ' . $checked . '> ' . esc_html($page->post_title) . ' <span style="color:#888">(' . esc_html($slug) . ')</span></label></li>';
        }

        echo '</ul>';
    }


    private function render_javascript(): void {
        echo '<script>
        function selectAll(name) {
            document.querySelectorAll("input[name=\'" + name + "[]\']").forEach(checkbox => checkbox.checked = true);
        }

        function deselectAll(name) {
            document.querySelectorAll("input[name=\'" + name + "[]\']").forEach(checkbox => checkbox.checked = false);
        }

        function selectAllCustom(name) {
            document.querySelectorAll("input[name=\'custom_post_type_slugs[" + name + "][]\']").forEach(checkbox => checkbox.checked = true);
        }

        function deselectAllCustom(name) {
            document.querySelectorAll("input[name=\'custom_post_type_slugs[" + name + "][]\']").forEach(checkbox => checkbox.checked = false);
        }
        </script>';
    }


    public function handle_generation(): void {
        if (!current_user_can($this->get_required_capability()) || !check_admin_referer(self::NONCE_ACTION)) {
            wp_die(esc_html__('Insufficient permissions or invalid nonce.', POSTMAN_PLUGIN_TEXT_DOMAIN));
        }
    
    private function get_required_capability(): string {
        /**
         * Filter required capability for accessing plugin admin page and actions.
         *
         * @param string $capability Default capability.
         */
        return (string) apply_filters('mksddn_postman_capability', self::CAPABILITY);
    }
        $selected_data = [
            'page_slugs' => $this->get_selected_page_slugs(),
            'post_slugs' => $this->get_selected_post_slugs(),
            'custom_slugs' => $this->get_selected_custom_slugs(),
            'options_pages' => $this->get_selected_options_pages(),
        ];

        $generator = new Postman_Generator();
        $generator->generate_and_download(
            $selected_data['page_slugs'],
            $selected_data['post_slugs'],
            $selected_data['custom_slugs'],
            $selected_data['options_pages']
        );
    }


}
