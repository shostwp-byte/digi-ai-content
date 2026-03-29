<?php
namespace DigiAiContent\Admin;

class Menu {
    public static function init() {
        add_action('admin_menu', [self::class, 'add_plugin_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_filter('script_loader_tag', [self::class, 'add_module_to_script'], 10, 3);
    }

    public static function add_plugin_menu() {
        add_menu_page(
            'Digi AI Content',
            'Digi AI',
            'manage_options',
            'digi-ai-content',
            [self::class, 'render_admin_page'],
            'dashicons-superhero',
            65
        );
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_digi-ai-content') {
            return;
        }

        // Tích hợp Franken UI v2.1
        wp_enqueue_style('franken-ui-style', 'https://unpkg.com/franken-ui@2.1.2/dist/css/core.min.css', [], '2.1.2');
        wp_enqueue_style('franken-ui-utilities', 'https://unpkg.com/franken-ui@2.1.2/dist/css/utilities.min.css', [], '2.1.2');
        wp_enqueue_script('franken-ui-script', 'https://unpkg.com/franken-ui@2.1.2/dist/js/core.iife.js', [], '2.1.2', true);
        wp_enqueue_script('franken-ui-icons', 'https://unpkg.com/franken-ui@2.1.2/dist/js/icon.iife.js', [], '2.1.2', true);
    }

    public static function add_module_to_script($tag, $handle, $src) {
        if (in_array($handle, ['franken-ui-script', 'franken-ui-icons'])) {
            return '<script src="' . esc_url($src) . '" type="module"></script>' . "\n";
        }
        return $tag;
    }

    public static function render_admin_page() {
        $view_file = dirname(__DIR__, 2) . '/views/admin-page.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo "<div class='wrap'><h2>View file not found.</h2></div>";
        }
    }
}
