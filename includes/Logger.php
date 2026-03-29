<?php
namespace DigiAiContent;

class Logger {
    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'digiai_logs';
    }

    /**
     * Tạo bảng lưu Database Custom thông qua Hook wp_activate
     */
    public static function create_table() {
        global $wpdb;
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            model varchar(100) NOT NULL,
            status varchar(20) NOT NULL,
            message text NOT NULL,
            post_id bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Ghi dấu vết dữ liệu vào Log
     * @param string $keyword Keyword hoặc mô tả tác vụ
     * @param string $model AI Model chạy lệnh
     * @param string $status success / failed / notice
     * @param string $message Lời nhắn từ hệ thống
     * @param int $post_id ID bài WordPress nếu thành công
     */
    public static function add_log($keyword, $model, $status, $message, $post_id = 0) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Insert record
        $wpdb->insert(
            $table_name,
            [
                'keyword'    => sanitize_text_field($keyword),
                'model'      => sanitize_text_field($model),
                'status'     => sanitize_text_field($status),
                'message'    => wp_kses_post($message),
                'post_id'    => intval($post_id),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );

        // Auto-Trim: Nếu số lượng Log vượt trần (500 dòng), tự động Delete dòng cũ nhất
        self::trim_logs();
    }

    /**
     * Xóa sạch các Logs bị cũ quá 500 bản ghi
     */
    private static function trim_logs() {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $limit = 500; // Ngưỡng an toàn

        if ($count > $limit) {
            $overflow = intval($count - $limit);
            // Xóa bớt số lượng vượt trần dựa theo ID cũ nhất (Limit theo số thừa)
            $wpdb->query("DELETE FROM $table_name ORDER BY id ASC LIMIT $overflow");
        }
    }

    /**
     * Trả về mảng danh sách lịch sử Logs cho Frontend Table
     */
    public static function get_logs($limit = 50) {
        global $wpdb;
        $table_name = self::get_table_name();
        // Fallback tạo bảng nếu admin chưa deactivate/activate plugin
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            self::create_table();
        }
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT " . intval($limit));
    }

    /**
     * Truncate Empty làm trống hoàn toàn Data API
     */
    public static function clear_all() {
        global $wpdb;
        $table_name = self::get_table_name();
        $wpdb->query("TRUNCATE TABLE $table_name");
    }
}
