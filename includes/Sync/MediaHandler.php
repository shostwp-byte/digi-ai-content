<?php
namespace DigiAiContent\Sync;

class MediaHandler {
    /**
     * Tải ảnh từ URL (ví dụ URL của DALL-E trả về), convert sang WebP siêu nhẹ, sau đó nạp vào thư viện Media của WP
     * @param string $url URL ảnh gốc (png/jpg)
     * @param int $post_id ID bài viết để gắn Thumbnail
     * @param string $keyword Keyword làm Alt Text và tên file
     * @return int|bool Trả về ID của Attachment hoặc false nếu lỗi
     */
    public static function sideload_and_convert_to_webp($url, $post_id, $keyword) {
        // Tải ảnh về thư mục tmp của hệ thống trước
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $temp_file = download_url($url, 300); // 5 mins timeout
        if (is_wp_error($temp_file)) {
            error_log('Lỗi tải ảnh MediaHandler: ' . $temp_file->get_error_message());
            return false;
        }

        $upload_dir = wp_upload_dir();
        $safe_name = sanitize_title($keyword) . '-' . substr(md5(uniqid()), 0, 8);
        $webp_filename = $safe_name . '.webp';
        $webp_file_path = $upload_dir['path'] . '/' . $webp_filename;

        // Dùng thư viện GD của PHP để convert ảnh temp (png/jpg/webp) sang WebP chuẩn
        $image = @imagecreatefrompng($temp_file);
        if (!$image) {
            $image = @imagecreatefromjpeg($temp_file);
        }
        if (!$image) {
            $image = @imagecreatefromwebp($temp_file);
        }

        if ($image) {
            imagepalettetotruecolor($image);
            imagewebp($image, $webp_file_path, 80); // Chất lượng nén 80% tối ưu Google
            imagedestroy($image);
            @unlink($temp_file); // Xóa file temp rác
        } else {
            // Trường hợp GD library không hỗ trợ, cứ move nguyên si sang Host
            $webp_filename = $safe_name . '.png';
            $webp_file_path = $upload_dir['path'] . '/' . $webp_filename;
            rename($temp_file, $webp_file_path);
        }

        // Tạo thông tin tệp Media cho WordPress
        $filetype = wp_check_filetype(basename($webp_file_path), null);
        $attachment = [
            'guid'           => $upload_dir['url'] . '/' . basename($webp_file_path), 
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_text_field($keyword), // Lấy keyword làm tiêu đề ảnh
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        // Ensure Admin functions exist (since cron runs in background)
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        $attach_id = wp_insert_attachment($attachment, $webp_file_path, $post_id);
        
        if (!is_wp_error($attach_id)) {
            // Generate nhiều size thumb cho theme
            $attach_data = wp_generate_attachment_metadata($attach_id, $webp_file_path);
            wp_update_attachment_metadata($attach_id, $attach_data);
            
            // Cập nhật thẻ Alt của ảnh chuẩn SEO (Alt Txt)
            update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($keyword));
            
            // Set ảnh dính vào bài đăng làm Ảnh Đại Diện (Featured Image)
            set_post_thumbnail($post_id, $attach_id);
            return $attach_id;
        }

        return false;
    }
}
