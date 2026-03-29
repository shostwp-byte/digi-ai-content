<?php
namespace DigiAiContent\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GeminiProvider implements AiProviderInterface {
    private $api_key;
    private $client;

    public function __construct() {
        $this->api_key = get_option('digi_gemini_api_key');
        if (empty($this->api_key)) {
            throw new \Exception("Chưa cấu hình Google Gemini API Key.");
        }
        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'timeout'  => 180,
        ]);
    }

    public function generate_content($topic, $headline) {
        $system_prompt = PromptManager::get_master_system_prompt();
        $user_prompt = "Chủ đề / Từ khóa SEO chính: {$topic}\nTiêu đề SEO dự kiến: {$headline}\n\nHãy tiến hành xây dựng và viết toàn bộ nội dung HTML ngay bây giờ tuân thủ nghiêm ngặt Luật của System.";
        
        // Gọi trực tiếp Model mà Admin đã lựa chọn trên giao diện UI
        $selected_model = get_option('digi_gemini_model', 'gemini-2.5-flash');

        // Gemini chuẩn hóa API v1beta hỗ trợ mảng systemInstruction riêng
        try {
            $response = $this->client->post("models/{$selected_model}:generateContent?key=" . $this->api_key, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $system_prompt]
                        ]
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $user_prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                    ]
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $result['candidates'][0]['content']['parts'][0]['text'];
                
                // Clean markdown artifacts
                $content = preg_replace('/^```(?:html)?\s*/i', '', trim($content));
                $content = preg_replace('/\s*```$/i', '', $content);
                
                return $content;
            }
            throw new \Exception("Dữ liệu Gemini trả về rỗng.");

        } catch (GuzzleException $e) {
            throw new \Exception("Lỗi máy chủ Google Gemini: " . $e->getMessage());
        }
    }

    public function generate_image($prompt) {
        // Hiện tại Google Cloud cung cấp Imagen API riêng lẻ chứ không gộp vào Text model GenerateContent.
        // Tạm thời báo lỗi để Plugin kích hoạt tính năng Fallback về OpenAI.
        throw new \Exception("Mô hình AI Gemini hiện đang chọn không hỗ trợ vẽ hình ảnh tự động. Vui lòng chuyển cấu hình cột Sheet sang OpenAI hoặc GPT.");
    }
}
