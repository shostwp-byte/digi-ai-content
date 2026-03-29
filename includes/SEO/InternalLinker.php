<?php
namespace DigiAiContent\SEO;

class InternalLinker {
    public static function init() {
        // Móc vào hàm sinh HTML giao diện hiển thị của WordPress
        add_filter('the_content', [self::class, 'auto_insert_internal_links'], 99);
        
        // Dọn cache liên kết mỗi khi admin lưu bài, hoặc plugin chạy bài mới
        add_action('save_post', [self::class, 'clear_link_cache']);
    }

    public static function clear_link_cache() {
        delete_transient('digi_ai_keyword_links');
    }

    private static function get_keyword_dictionary() { // Cache RAM/Database tránh nghẽn Hosting
        $dict = get_transient('digi_ai_keyword_links');
        if ($dict !== false) {
            return $dict;
        }

        $dict = [];
        $posts = get_posts([
            'numberposts' => 100, // Quét nhanh 100 bài mới nhất
            'post_status' => 'publish',
            'post_type'   => 'post',
            'fields'      => 'ids' // Chỉ láy ID để truy vấn siêu nhẹ
        ]);

        foreach ($posts as $post_id) {
            $keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
            if (!empty($keyword)) {
                $kws = explode(',', $keyword); // RankMath có thể chứa nhiều key ngăn bằng dấu phẩy
                foreach ($kws as $kw) {
                    $kw = trim($kw);
                    if (mb_strlen($kw) > 3) { // Bỏ qua từ quá ngắn
                        if (!isset($dict[$kw])) {
                            $dict[$kw] = get_permalink($post_id);
                        }
                    }
                }
            }
        }

        // BẮT BUỘC: Sắp xếp theo chiều dài từ khóa giảm dần (Dài thay trước, ngắn thay sau)
        // Ví dụ: thay chữ "chó alaska khổng lồ" trước khi thay chữ "chó alaska" để không cắn nhầm từ.
        uksort($dict, function($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        // Lưu vào Transient 12 tiếng chống sập máy chủ
        set_transient('digi_ai_keyword_links', $dict, 12 * HOUR_IN_SECONDS);
        return $dict;
    }

    public static function auto_insert_internal_links($content) {
        if (!is_single() || !in_the_loop() || !is_main_query()) {
            return $content; // Chỉ áp dụng ở màn hình Đọc bài Post chi tiết
        }

        $dict = self::get_keyword_dictionary();
        if (empty($dict)) {
            return $content;
        }

        $current_url = get_permalink();
        $replaced_keywords = [];

        // Logic Regex siêu cấp: Không thay thế từ nằm bên trong thuộc tính cặp thẻ như <a href=".."> hay H1/H2
        foreach ($dict as $keyword => $url) {
            if ($url === $current_url) {
                continue; // Tránh tự trỏ Link về trang chính mình đang đứng
            }
            if (in_array($keyword, $replaced_keywords)) {
                continue; 
            }

            // Negative Lookbehind & Lookahead kiểm tra chữ này không được bọc quanh bởi thẻ <a>
            $escaped_kw = preg_quote($keyword, '/');
            $pattern = '/(?!(?:[^<]+>|[^>]+<\/a>))\b(' . $escaped_kw . ')\b/iu';
            
            // Cho phép thả neo tối đa 1 lần / 1 từ khóa để tránh Spam bị Google phạt
            $count = 0;
            $content = preg_replace_callback($pattern, function($matches) use ($url) {
                // Return thẻ a style xanh chuyên nghiệp kích thích nảy Click
                return '<a href="' . esc_url($url) . '" title="Xem bài: ' . esc_attr($matches[1]) . '" style="color:#0071e3; font-weight:500; text-decoration: underline;">' . $matches[1] . '</a>';
            }, $content, 1, $count);

            if ($count > 0) {
                $replaced_keywords[] = $keyword;
                // Luật an toàn 5 links / 1 Webpage: Nếu trong bài đã cắm quá 5 link thì ngừng rải Anchortext
                if (count($replaced_keywords) >= 5) {
                    break;
                }
            }
        }

        return $content;
    }
}
