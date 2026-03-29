<?php
/**
 * Plugin Name: Digi AI Content
 * Description: Tự động lên Content với AI, đồng bộ Google Sheets, thông báo Telegram, tối ưu Rank Math chuẩn SEO.
 * Version: 1.0.0
 * Author: ShostWP
 * Text Domain: digi-ai-content
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Require vendor autoloader if exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Custom PSR-4 autoloader for development mode mapping namespace DigiAiContent -> includes/
spl_autoload_register(function ($class) {
    $prefix = 'DigiAiContent\\';
    $base_dir = __DIR__ . '/includes/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Khởi tạo hệ thống
function digi_ai_content_init() {
    if (class_exists('DigiAiContent\\Plugin')) {
        $plugin = \DigiAiContent\Plugin::get_instance();
        $plugin->init();
    }
}
add_action('plugins_loaded', 'digi_ai_content_init');

register_activation_hook(__FILE__, 'digi_ai_content_activate');
function digi_ai_content_activate() {
    // Kích hoạt SQL tạo Database riêng
    if (class_exists('DigiAiContent\Logger')) {
        \DigiAiContent\Logger::create_table();
    }

    if (!wp_next_scheduled('digi_ai_sync_task')) {
        wp_schedule_event(time(), 'digi_every_5_mins', 'digi_ai_sync_task');
    }
}

register_deactivation_hook(__FILE__, 'digi_ai_content_deactivate');
function digi_ai_content_deactivate() {
    $timestamp = wp_next_scheduled('digi_ai_sync_task');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'digi_ai_sync_task');
    }
}
