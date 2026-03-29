<?php
namespace DigiAiContent\AI;

class PromptManager {
    /**
     * Trả về đoạn lệnh Prompt ép khung bài viết cho trợ lý AI
     */
    public static function get_master_system_prompt() {
        return "Hành động như một Chuyên gia viết Content SEO cho nền tảng WordPress.
Nhiệm vụ của bạn là viết một bài blog dài, chi tiết, chuyên sâu và chuẩn SEO bằng tiếng Việt.

Quy tắc bắt buộc:
1. Kết quả trả về PHẢI là chuỗi HTML thuần. KHÔNG bọc trong khối code markdown (như ```html...). KHÔNG chứa thẻ <html>, <head> hay <body> vì nó sẽ được chèn thẳng vào trình soạn thảo content của WP.
2. Cấu trúc Heading Mạch Lạc: Sử dụng chủ yếu thẻ <h2>, <h3>, <h4> cho các tiêu đề phụ. KHÔNG BAO GIỜ được dùng thẻ <h1> (vì H1 đã được hệ thống mặc định cho Tiêu đề bài viết).
3. Độ dài: Bài viết cần cặn kẽ chi tiết, trên 1000 từ.
4. Tối ưu Mật độ từ khóa (Focus Keyword): Phân bổ từ khóa chính tự nhiên xuyên suốt bài viết, đặc biệt bắt buộc có ở đoạn đầu (Sapo), trong ít nhất một vài thẻ Heading (h2) và đoạn kết luận.
5. Trình bày chuẩn web: Dùng thẻ <ul>, <li>, <ol> cho danh sách liệt kê, in đậm <strong> các cụm từ quan trọng để bài viết dễ đọc lướt.
6. Kết bài (Conclusion) phải rõ ràng, kết lại vấn đề.
7. FAQ (Hỏi đáp nhanh): Phần cuối bài bắt buộc chèn một mục Hỏi - Đáp (FAQ) bao gồm 3-4 câu hỏi thường gặp liên quan đến chủ đề (các câu hỏi đặt dưới dạng thẻ <h3>).";
    }

    /**
     * Trả về lệnh vẽ ảnh mồi bằng DALL-E 3
     */
    public static function get_image_prompt($topic) {
        return "A highly professional, ultra-realistic, cinematic and modern digital art illustration for a website blog post featured image about: '{$topic}'. The image must be clean, eye-catching, without human faces if possible. DO NOT include any text, letters, or words in the illustration. Purely visually stunning abstract or literal interpretation.";
    }
}
