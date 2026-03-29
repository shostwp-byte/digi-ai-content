<?php
namespace DigiAiContent\Sync;

class GoogleSheetsManager {
    private $service;
    private $spreadsheet_id;
    private $sheet_name = 'Sheet1';

    public function __construct() {
        if (!class_exists('\Google\Client')) {
             throw new \Exception("Nền tảng SDK Google Client bị thiếu (hãy chạy composer install).");
        }
        $client = \DigiAiContent\Admin\GoogleAuth::get_client();
        if (!$client || !$client->getAccessToken()) {
            throw new \Exception("Tài khoản chưa được uỷ quyền Google hoặc Token mất.");
        }
        
        $this->service = new \Google\Service\Sheets($client);
        $this->spreadsheet_id = get_option('digi_google_sheet_id');
        
        if (empty($this->spreadsheet_id)) {
            throw new \Exception("Chưa lựa chọn Bảng tính dữ liệu (Spreadsheet).");
        }
    }

    /**
     * Lấy mảng các dòng có Cột Status là 'Ready'
     * @return array Format: [ ['row_index' => 2, 'data' => ['STT', 'Keyword', ...]] ]
     */
    public function get_ready_rows() {
        // Lấy thông tin sheet hiện hành đầu tiên (Trang tính đầu tiên)
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheet_id);
        if (count($spreadsheet->getSheets()) > 0) {
            $this->sheet_name = $spreadsheet->getSheets()[0]->getProperties()->getTitle();
        }

        // Đọc từ cột A đến J, bắt đầu dòng số 2 (Row 2, bỏ Header)
        $range = $this->sheet_name . '!A2:J'; 
        
        $response = $this->service->spreadsheets_values->get($this->spreadsheet_id, $range);
        $values = $response->getValues();
        
        $ready_rows = [];
        if (!empty($values)) {
            foreach ($values as $index => $row) {
                // Theo Format: Cột E (Index 4) là Trạng thái
                $status = isset($row[4]) ? strtolower(trim($row[4])) : '';
                
                // Mặc định quét các dòng đánh chữ "Ready"
                if ($status === 'ready') {
                    $ready_rows[] = [
                        'row_index' => $index + 2, // Index 0 + A2 (dòng 2) = +2
                        'data'      => $row
                    ];
                }
            }
        }
        
        return $ready_rows;
    }

    /**
     * Ghi cập nhật lại 3 thông số: Status "Published", ID bài viết và Link để quản trị dễ.
     * @param int $row_index Số thứ tự của dòng chứa bài viết
     * @param int|string $post_id ID của bài đăng trong mã nguồn WP
     * @param string $post_url Link của bài
     */
    public function mark_as_published($row_index, $post_id, $post_url) {
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheet_id);
        if (count($spreadsheet->getSheets()) > 0) {
            $this->sheet_name = $spreadsheet->getSheets()[0]->getProperties()->getTitle();
        }

        $data = [];
        
        // Cần thay đổi Cột E (Status)
        $data[] = new \Google\Service\Sheets\ValueRange([
            'range' => $this->sheet_name . "!E{$row_index}",
            'values' => [['Published']]
        ]);
        
        // Ghi lên Cột I (WP ID)
        $data[] = new \Google\Service\Sheets\ValueRange([
            'range' => $this->sheet_name . "!I{$row_index}",
            'values' => [[$post_id]]
        ]);
        
        // Ghi lên Cột J (WP Permalink)
        $data[] = new \Google\Service\Sheets\ValueRange([
            'range' => $this->sheet_name . "!J{$row_index}",
            'values' => [[$post_url]]
        ]);

        $body = new \Google\Service\Sheets\BatchUpdateValuesRequest([
            'valueInputOption' => 'USER_ENTERED',
            'data' => $data
        ]);

        $this->service->spreadsheets_values->batchUpdate($this->spreadsheet_id, $body);
    }
}
