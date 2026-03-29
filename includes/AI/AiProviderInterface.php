<?php
namespace DigiAiContent\AI;

interface AiProviderInterface {
    /**
     * Hàm sinh nội dung bài viết HTML chuẩn SEO
     * @param string $topic Chủ đề / Focus Keyword (Từ Cột 2)
     * @param string $headline Tiêu đề (Từ Cột 3)
     * @return string Trả về code nội dung HTML của bài viết
     */
    public function generate_content($topic, $headline);

    /**
     * Hàm sinh ảnh đại diện cho bài viết
     * @param string $prompt Miêu tả ảnh
     * @return string Trả về URL ảnh để sau Plugin tải về Host WP
     * @throws \Exception
     */
    public function generate_image($prompt);
}
