<?php
namespace DigiAiContent\AI;

class AiFactory {
    /**
     * @param string $model Tên model lấy từ cột 6 của Google Sheet (vd: "GPT", "Gemini")
     * @return AiProviderInterface Trả về đối tượng xử lý AI tương ứng
     * @throws \Exception
     */
    public static function get_provider($model) {
        $model = strtoupper(trim($model));

        // Mặc định ném về provider tương ứng theo chữ khóa
        if (strpos($model, 'GEMINI') !== false) {
            return new GeminiProvider();
        }

        // Ưu tiên mặc định (và nếu là GPT / OpenAI) luôn là OpenAI
        return new OpenAiProvider();
    }
}
