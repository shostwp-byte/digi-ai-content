<?php
namespace DigiAiContent\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class OpenAiProvider implements AiProviderInterface {
    private $api_key;
    private $client;

    public function __construct() {
        $this->api_key = get_option('digi_openai_api_key');
        if (empty($this->api_key)) {
            throw new \Exception("Chưa cấu hình OpenAI API Key trong thẻ Settings.");
        }
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => 180, // Sinh bài viết rất tốn thời gian nên đặt limit cao
        ]);
    }

    public function generate_content($topic, $headline) {
        $system_prompt = PromptManager::get_master_system_prompt();
        $user_prompt = "Chủ đề / Từ khóa SEO chính: {$topic}\nTiêu đề SEO dự kiến: {$headline}\n\nHãy tiến hành xây dựng và viết toàn bộ nội dung HTML ngay bây giờ.";

        try {
            $response = $this->client->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini', // Thay đổi sang bản mini cho rẻ, hoặc tự sửa option sau này (như gpt-4o)
                    'messages' => [
                        ['role' => 'system', 'content' => $system_prompt],
                        ['role' => 'user', 'content' => $user_prompt]
                    ],
                    'temperature' => 0.7,
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['choices'][0]['message']['content'])) {
                $content = $result['choices'][0]['message']['content'];
                
                // Tráng loại bỏ code syntax mà AI xuất dư
                $content = preg_replace('/^```(?:html)?\s*/i', '', trim($content));
                $content = preg_replace('/\s*```$/i', '', $content);

                return $content;
            }
            throw new \Exception("Dữ liệu OpenAI trả về không có phần message->content.");

        } catch (GuzzleException $e) {
            throw new \Exception("Lỗi máy chủ OpenAI: " . $e->getMessage());
        }
    }

    public function generate_image($prompt) {
        try {
            $image_prompt = PromptManager::get_image_prompt($prompt);

            $response = $this->client->post('images/generations', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'dall-e-3',
                    'prompt' => $image_prompt,
                    'n' => 1,
                    'size' => '1024x1024',
                    'quality' => 'standard',
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['data'][0]['url'])) {
                return $result['data'][0]['url']; // Trả về link trực tiếp (hạn 60p của OpenAI)
            }
            throw new \Exception("DALL-E 3 trả về mảng nhưng không có thông tin URL.");

        } catch (GuzzleException $e) {
            throw new \Exception("Lỗi khởi tạo ảnh DALL-E: " . $e->getMessage());
        }
    }
}
