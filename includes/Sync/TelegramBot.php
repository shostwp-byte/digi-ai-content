<?php
namespace DigiAiContent\Sync;

class TelegramBot {
    
    /**
     * Gửi tin nhắn định dạng HTML vào Group/Channel Telegram
     */
    public static function send_message($message) {
        $token = get_option('digi_telegram_bot_token');
        $chat_id = get_option('digi_telegram_chat_id');

        if (empty($token) || empty($chat_id)) {
            return false;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $response = wp_remote_post($url, [
            'body' => [
                'chat_id' => $chat_id,
                'text'    => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
             error_log('Telegram API Error: ' . $response->get_error_message());
             return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code != 200) {
            error_log('Telegram Return Bad Code: ' . $code);
            return false;
        }
        
        return true;
    }
}
