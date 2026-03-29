<?php
namespace DigiAiContent;

class Plugin {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() {
        // Init Admin settings and hooks
        if (is_admin()) {
            Admin\Menu::init();
            Admin\GoogleAuth::init();
            Admin\Ajax::init();
        }
    }
}
