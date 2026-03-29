<?php
namespace DigiAiContent\Admin;

class Ajax {

    public static function init() {
        add_action('wp_ajax_digi_save_google_client', [self::class, 'save_google_client']);
        add_action('wp_ajax_digi_save_sheet_id', [self::class, 'save_sheet_id']);
        add_action('wp_ajax_digi_save_ai_settings', [self::class, 'save_ai_settings']);
        add_action('wp_ajax_digi_save_telegram_settings', [self::class, 'save_telegram_settings']);
        add_action('wp_ajax_digi_fetch_google_sheets', [self::class, 'fetch_google_sheets']);
    }

    private static function verify_request() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        // Thêm wp_verify_nonce ở đây sau nếu cần thiết
    }

    public static function save_google_client() {
        self::verify_request();
        $client_id = isset($_POST['google_client_id']) ? sanitize_text_field($_POST['google_client_id']) : '';
        $client_secret = isset($_POST['google_client_secret']) ? sanitize_text_field($_POST['google_client_secret']) : '';
        
        update_option('digi_google_client_id', $client_id);
        update_option('digi_google_client_secret', $client_secret);

        wp_send_json_success(['message' => 'Đã lưu thông số cấu hình Google Client. Vui lòng Reload trang để hiện nút Đăng nhập.']);
    }

    public static function save_sheet_id() {
        self::verify_request();
        $sheet_id = isset($_POST['google_sheet_id']) ? sanitize_text_field($_POST['google_sheet_id']) : '';
        update_option('digi_google_sheet_id', $sheet_id);
        wp_send_json_success(['message' => 'Nguồn dữ liệu Sheet đã được chốt thành công.']);
    }

    public static function save_ai_settings() {
        self::verify_request();
        $openai_key = isset($_POST['openai_api_key']) ? sanitize_text_field($_POST['openai_api_key']) : '';
        $gemini_key = isset($_POST['gemini_api_key']) ? sanitize_text_field($_POST['gemini_api_key']) : '';
        
        update_option('digi_openai_api_key', $openai_key);
        update_option('digi_gemini_api_key', $gemini_key);

        wp_send_json_success(['message' => 'Đã cập nhật cấu hình API Trí tuệ AI.']);
    }

    public static function save_telegram_settings() {
        self::verify_request();
        $token = isset($_POST['telegram_bot_token']) ? sanitize_text_field($_POST['telegram_bot_token']) : '';
        $chat_id = isset($_POST['telegram_chat_id']) ? sanitize_text_field($_POST['telegram_chat_id']) : '';
        
        update_option('digi_telegram_bot_token', $token);
        update_option('digi_telegram_chat_id', $chat_id);

        wp_send_json_success(['message' => 'Cấu hình cảnh báo Telegram đã được lưu.']);
    }

    public static function fetch_google_sheets() {
        self::verify_request();
        
        if (!class_exists('\Google\Client')) {
            wp_send_json_error(['message' => 'Thư viện Hệ sinh thái Google chưa được cài đặt (Thiếu vendor).']);
        }

        $client = \DigiAiContent\Admin\GoogleAuth::get_client();
        if (!$client || !$client->getAccessToken()) {
            wp_send_json_error(['message' => 'Kết nối với Google đã mất, vui lòng ủy quyền lại.']);
        }

        try {
            $service = new \Google\Service\Drive($client);
            $optParams = array(
                'pageSize' => 50,
                'q' => "mimeType='application/vnd.google-apps.spreadsheet' and trashed=false",
                'fields' => 'nextPageToken, files(id, name)'
            );
            $results = $service->files->listFiles($optParams);

            $sheets = [];
            if (count($results->getFiles()) > 0) {
                foreach ($results->getFiles() as $file) {
                    $sheets[] = [
                        'id' => $file->getId(),
                        'name' => $file->getName()
                    ];
                }
            }

            wp_send_json_success(['sheets' => $sheets]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Lỗi request Drive API: ' . $e->getMessage()]);
        }
    }
}
