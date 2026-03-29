<?php
namespace DigiAiContent\Admin;

class GoogleAuth {

    public static function get_client() {
        if (!class_exists('\Google\Client')) {
            return null;
        }

        $client_id = get_option('digi_google_client_id');
        $client_secret = get_option('digi_google_client_secret');
        $redirect_uri = admin_url('admin.php?page=digi-ai-content');

        if (!$client_id || !$client_secret) {
            return null;
        }

        $client = new \Google\Client();
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri($redirect_uri);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Force consent to always get refresh_token
        $client->addScope('https://www.googleapis.com/auth/spreadsheets');
        $client->addScope('https://www.googleapis.com/auth/drive.readonly');

        $token = get_option('digi_google_access_token');
        if (!empty($token) && is_array($token)) {
            $client->setAccessToken($token);
            if ($client->isAccessTokenExpired()) {
                $refresh_token = $client->getRefreshToken();
                if ($refresh_token) {
                    $new_token = $client->fetchAccessTokenWithRefreshToken($refresh_token);
                    if (!isset($new_token['error'])) {
                        update_option('digi_google_access_token', $client->getAccessToken());
                    } else {
                        // Refresh token failed (e.g. revoked)
                        delete_option('digi_google_access_token');
                    }
                } else {
                     delete_option('digi_google_access_token');
                }
            }
        }

        return $client;
    }

    public static function get_login_url() {
        $client = self::get_client();
        if ($client) {
            return $client->createAuthUrl();
        }
        return '';
    }

    public static function handle_oauth_callback() {
        // Chỉ chạy khi ở đúng trang settings và có `code` từ Google
        if (!isset($_GET['page']) || $_GET['page'] !== 'digi-ai-content') {
            return;
        }

        if (isset($_GET['code'])) {
            $client = self::get_client();
            if ($client) {
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                if (!isset($token['error'])) {
                    update_option('digi_google_access_token', $token);
                    
                    // Xóa tham số code trên URL để tránh submit lại
                    wp_redirect(admin_url('admin.php?page=digi-ai-content'));
                    exit;
                }
            }
        }
    }

    public static function handle_disconnect() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $client = self::get_client();
        if ($client) {
            $client->revokeToken();
        }
        
        delete_option('digi_google_access_token');
        delete_option('digi_google_sheet_id');
        
        wp_redirect(admin_url('admin.php?page=digi-ai-content'));
        exit;
    }

    public static function init() {
        add_action('admin_init', [self::class, 'handle_oauth_callback']);
        add_action('admin_post_digi_google_disconnect', [self::class, 'handle_disconnect']);
    }
}
