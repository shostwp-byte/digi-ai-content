<?php
namespace DigiAiContent\Sync;

use DigiAiContent\AI\AiFactory;
use DigiAiContent\Logger;

class CronJob {
    
    public static function init() {
        // Đăng ký lịch chạy mới
        add_filter('cron_schedules', [self::class, 'add_cron_interval']);
        
        // Móc hàm chạy Cron
        add_action('digi_ai_sync_task', [self::class, 'run_sync']);
    }

    public static function add_cron_interval($schedules) {
        $schedules['digi_every_5_mins'] = [
            'interval' => 300,
            'display'  => 'Mỗi 5 phút (Digi AI)'
        ];
        return $schedules;
    }

    public static function run_sync() {
        // Tắt giới hạn PHP Timeout 30s mặc định của wp-cron để AI API được phép call lâu 60-90s.
        @set_time_limit(0);
        @ignore_user_abort(true);

        try {
            $sheet_manager = new GoogleSheetsManager();
            $ready_rows = $sheet_manager->get_ready_rows();
            
            if (empty($ready_rows)) {
                return; // Trống trơn, không cần ghi rác Database
            }

            // ĐỂ TRÁNH QUÁ TẢI (TIMEOUT PHP), TÔI CHỈ CẤP QUYỀN CHẠY 1 BÀI CHO 1 LẦN CRON:
            $process_row = $ready_rows[0];
            $row_index = $process_row['row_index'];
            $data = $process_row['data'];
            
            // Map Index Cột dựa vào Document 10 cột của chúng ta
            // 0:STT, 1:Keyword, 2:Headline, 3:Chuyên mục, 4:Status(Ready), 5:Model, 6:Ảnh prompt, 7:Lịch, 8:WPID, 9:Link
            $topic = isset($data[1]) ? trim($data[1]) : '';
            $headline = isset($data[2]) ? trim($data[2]) : '';
            $category_name = isset($data[3]) ? trim($data[3]) : '';
            $model_name = isset($data[5]) ? trim($data[5]) : 'GPT';
            
            if (empty($topic) || empty($headline)) {
                return; // Thiếu Input cơ bản, thoát vòng lặp.
            }

            // Kích hoạt AI dựa trên tên Cột Model
            $ai_provider = AiFactory::get_provider($model_name);
            $html_content = $ai_provider->generate_content($topic, $headline);

            if (empty($html_content)) {
                throw new \Exception("Gửi yêu cầu thành công nhưng nội dung Bot AI trả về biến rỗng.");
            }

            // TODO Giai đoạn sau: Xử lý gán dính Category theo Tên Name ($category_name)
            
            // Xử lý tạo Post WP
            $post_data = [
                'post_title'    => wp_strip_all_tags($headline),
                'post_content'  => $html_content,
                'post_status'   => 'publish', // Có thể đọc thêm cột Date(7) để hẹn giờ 'future' publish.
                'post_author'   => 1, // Admin author tĩnh
                'post_type'     => 'post'
            ];

            $post_id = wp_insert_post($post_data);
            if (is_wp_error($post_id) || $post_id == 0) {
                // Đánh dấu lỗi nếu WP chối
                throw new \Exception("Hàm wp_insert_post báo lỗi. Mã lỗi: " . $post_id->get_error_message());
            }

            // [Phase 4] Sinh lệnh vẽ hình ảnh từ AI DALL-E 3, tự động nén WebP rồi set làm Ảnh đại diện
            $image_prompt = isset($data[6]) ? trim($data[6]) : '';
            if (!empty($image_prompt)) {
                try {
                    $image_url = $ai_provider->generate_image($image_prompt);
                    if ($image_url) {
                        MediaHandler::sideload_and_convert_to_webp($image_url, $post_id, $topic);
                    }
                } catch (\Exception $ex) {
                    error_log("Lỗi tạo/tải ảnh sinh qua DALL-E: " . $ex->getMessage());
                }
            }

            // [Phase 4] Hack tự động gắn thẻ trường tuỳ biến cho Rank Math SEO
            update_post_meta($post_id, 'rank_math_focus_keyword', strtolower($topic));
            update_post_meta($post_id, 'rank_math_title', $headline);
            $clean_text = wp_strip_all_tags($html_content);
            $desc = wp_trim_words($clean_text, 25, '');
            update_post_meta($post_id, 'rank_math_description', $desc);

            // Ghi trạng thái về bảng Excel cập nhật API thành công trước
            $post_url = get_permalink($post_id);
            $sheet_manager->mark_as_published($row_index, $post_id, $post_url);

            // Ghi dữ liệu vào SQL Log AI (System 2.0 Logs)
            Logger::add_log($headline, $model_name, 'success', 'Hoàn tất Auto-post an toàn cùng tính năng nén 🌐 ảnh gốc WebP.', $post_id);

            // Push thông báo lên Telegram Group/Channel
            $msg = "🚀 <b>Đăng Bài Tự Động Thành Công!</b>\n";
            $msg .= "<b>Tiêu đề:</b> {$headline}\n";
            $msg .= "<b>AI Model được dùng:</b> {$model_name}\n";
            $msg .= "<b>Check bài viết:</b> <a href='{$post_url}'>Xem thực tế tại đây</a>";
            TelegramBot::send_message($msg);

        } catch (\Exception $e) {
            $err_topic = isset($topic) ? $topic : 'Tiến trình vòng lặp Cron AI';
            $err_model = isset($model_name) ? $model_name : 'System';
            
            Logger::add_log($err_topic, $err_model, 'failed', $e->getMessage());
            error_log('Digi AI V4 Cron Error: ' . $e->getMessage());
        }
    }
}
