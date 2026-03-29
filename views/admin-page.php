<?php
if (!defined('ABSPATH')) {
    exit;
}

$client_id = get_option('digi_google_client_id', '');
$client_secret = get_option('digi_google_client_secret', '');
$google_token = get_option('digi_google_access_token', '');
$redirect_uri = admin_url('admin.php?page=digi-ai-content');

$openai_key = get_option('digi_openai_api_key', '');
$gemini_key = get_option('digi_gemini_api_key', '');
$bot_token = get_option('digi_telegram_bot_token', '');
$chat_id = get_option('digi_telegram_chat_id', '');

$is_connected = !empty($google_token);
$login_url = '';
if (!$is_connected && $client_id && $client_secret) {
    if (class_exists('\DigiAiContent\Admin\GoogleAuth')) {
        $login_url = \DigiAiContent\Admin\GoogleAuth::get_login_url();
    }
}
?>
<style>
    /* Ghi đè cấu hình màu chủ đạo Theme Franken UI (Xanh da trời) */
    :root {
        --primary: 221.2 83.2% 53.3%;
        --primary-foreground: 210 40% 98%;
        --background: 0 0% 100%;
        --card: 0 0% 100%;
    }
    html, body, #wpwrap, #wpcontent, #wpbody-content, .uk-container {
        background-color: hsl(var(--background)) !important; /* Ép nền màn hình quản trị của WP đổi sang Trắng sạch sẽ giống Webapp */
    }
</style>
<div class="wrap uk-container uk-container-expand uk-margin-top">
    <div class="p-6 lg:p-10">
        <div class="space-y-0.5">
            <h2 class="text-2xl font-bold tracking-tight">Digi AI Content <span class="text-muted-foreground text-lg font-normal">Settings</span></h2>
            <p class="text-muted-foreground">
                Quản lý đồng bộ nội dung tự động từ Google Sheets, thiết lập model AI, và định cấu hình tối ưu thẻ SEO Rank Math.
            </p>
        </div>
        <div class="border-border my-6 border-t"></div>
        <div class="flex flex-col space-y-8 lg:flex-row lg:space-x-12 lg:space-y-0 relative">
            
            <aside class="lg:w-1/5">
                <nav class="flex space-x-2 lg:flex-col lg:space-x-0 lg:space-y-1">
                    <ul class="uk-nav uk-nav-secondary w-full" uk-switcher="connect: #settings-content; animation: uk-animation-slide-left-small, uk-animation-slide-right-small">
                        <li class="uk-active">
                            <a href="#">
                                <uk-icon class="mr-2 size-4" icon="database"></uk-icon>
                                Nguồn Dữ Liệu
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <uk-icon class="mr-2 size-4" icon="cpu"></uk-icon>
                                Cấu hình AI
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <uk-icon class="mr-2 size-4" icon="send"></uk-icon>
                                Telegram & Log
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <uk-icon class="mr-2 size-4" icon="settings"></uk-icon>
                                Nâng cao
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <div class="flex-1">
                <ul id="settings-content" class="uk-switcher max-w-2xl">
                    
                    <!-- TAB 1: Google Sheets -->
                    <li class="uk-active space-y-6">
                        <div>
                            <h3 class="text-lg font-medium">Google Sheets Sync</h3>
                            <p class="text-muted-foreground text-sm">
                                Nơi plugin sẽ quét dữ liệu tự động định kỳ để nhận bài viết ở trạng thái "Ready".
                            </p>
                        </div>
                        <div class="border-border border-t"></div>
                        <?php if (!$is_connected): ?>
                            <form class="frm-ajax-save space-y-6">
                                <input type="hidden" name="action" value="digi_save_google_client">
                                
                                <div class="uk-alert uk-alert-primary">
                                    <div class="uk-alert-title">Cấu hình OAuth Consent Screen</div>
                                    <div class="uk-alert-description">
                                        Vui lòng sao chép URL sau và dán vào mục <b>Authorized redirect URIs</b> trên Google Cloud Console:
                                        <code class="block mt-2 p-2 bg-background rounded"><?php echo esc_url($redirect_uri); ?></code>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <label class="uk-form-label" for="google_client_id">Google Client ID</label>
                                        <input class="uk-input" id="google_client_id" name="google_client_id" type="text" value="<?php echo esc_attr($client_id); ?>" placeholder="xxx.apps.googleusercontent.com">
                                    </div>

                                    <div class="space-y-2">
                                        <label class="uk-form-label" for="google_client_secret">Google Client Secret</label>
                                        <input class="uk-input" id="google_client_secret" name="google_client_secret" type="password" value="<?php echo esc_attr($client_secret); ?>" placeholder="GOCSPX-xxx...">
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4">
                                    <button type="submit" class="uk-btn uk-btn-default">
                                        Lưu Thông Số API
                                    </button>

                                    <?php if ($login_url): ?>
                                        <a href="<?php echo esc_url($login_url); ?>" class="uk-btn uk-btn-primary flex items-center">
                                            <uk-icon class="mr-2 size-4" icon="log-in"></uk-icon>
                                            Đăng Nhập Với Google
                                        </a>
                                    <?php else:
                                        if ($client_id): ?>
                                            <span class="text-sm text-destructive">Vui lòng chạy `composer install` để thư viện Google hoạt động.</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php else:
                            $selected_sheet_id = get_option('digi_google_sheet_id', '');
                        ?>
                            <div class="uk-alert uk-alert-success">
                                <div class="uk-alert-title flex items-center">
                                    <uk-icon class="mr-2 size-5" icon="check-circle"></uk-icon>
                                    Đã kết nối Google Drive thành công!
                                </div>
                                <div class="uk-alert-description mt-2">
                                    Tài khoản của bạn đã được ủy quyền để truy xuất file Google Sheets.
                                    <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mt-3">
                                        <input type="hidden" name="action" value="digi_google_disconnect">
                                        <button type="submit" class="uk-btn uk-btn-destructive uk-btn-sm">Ngắt Kết Nối</button>
                                    </form>
                                </div>
                            </div>

                            <form class="frm-ajax-save space-y-6 mt-6">
                                <input type="hidden" name="action" value="digi_save_sheet_id">
                                <div class="space-y-2">
                                    <label class="uk-form-label" for="google_sheet_id">Chọn File Lên Lịch (Google Sheet)</label>
                                    <div class="h-10">
                                        <select class="uk-select" id="google_sheet_id" name="google_sheet_id">
                                            <option value="">-- Đang load danh sách từ Google Drive --</option>
                                            <?php if ($selected_sheet_id): ?>
                                                <option value="<?php echo esc_attr($selected_sheet_id); ?>" selected>Đang sử dụng Sheet ID: <?php echo esc_html($selected_sheet_id); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="uk-form-help text-muted-foreground">
                                        (Tính năng load danh sách Sheets Drive sẽ được nạp thông qua AJAX sau khi bạn refresh)
                                    </div>
                                </div>

                                <div>
                                    <button type="submit" class="uk-btn uk-btn-primary">
                                        Chốt Nguồn Dữ Liệu
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </li>

                    <!-- TAB 2: AI Core -->
                    <li class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium">Hệ Thống Trí Tuệ Nhân Tạo</h3>
                            <p class="text-muted-foreground text-sm">
                                Kết nối các model AI khác nhau để sinh văn bản, tạo hình ảnh và On-page SEO tự động.
                            </p>
                        </div>
                        <div class="border-border border-t"></div>
                        <form class="frm-ajax-save space-y-6">
                            <input type="hidden" name="action" value="digi_save_ai_settings">
                            <div class="space-y-2">
                                <label class="uk-form-label" for="openai_api_key">OpenAI API Key <span class="uk-badge uk-badge-primary ml-1">Recommend</span></label>
                                <input class="uk-input" id="openai_api_key" name="openai_api_key" type="password" value="<?php echo esc_attr($openai_key); ?>" placeholder="sk-...">
                                <div class="uk-form-help text-muted-foreground">
                                    Được sử dụng cho GPT-4o (Viết Content) và DALL-E 3 (Tạo ảnh).
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="uk-form-label" for="gemini_api_key">Google Gemini API Key</label>
                                <input class="uk-input" id="gemini_api_key" name="gemini_api_key" type="password" value="<?php echo esc_attr($gemini_key); ?>" placeholder="AIzaSy...">
                                <div class="uk-form-help text-muted-foreground">
                                    Sử dụng Gemini Pro để tạo nội dung cấu trúc dữ liệu nếu không muốn chạy GPT.
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" class="uk-btn uk-btn-primary">
                                    Cập nhật API
                                </button>
                            </div>
                        </form>
                    </li>

                    <!-- TAB 3: Telegram -->
                    <li class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium">Thông Báo Quản Trị Hệ Thống</h3>
                            <p class="text-muted-foreground text-sm">
                                Gửi thông báo ngay lập tức về Telegram Group/Channel mỗi khi tính năng chạy Cron hoàn thành và Post bài.
                            </p>
                        </div>
                        <div class="border-border border-t"></div>
                        <form class="frm-ajax-save space-y-6">
                            <input type="hidden" name="action" value="digi_save_telegram_settings">
                            <div class="space-y-2">
                                <label class="uk-form-label" for="telegram_bot_token">Bot Token</label>
                                <input class="uk-input" id="telegram_bot_token" name="telegram_bot_token" type="password" value="<?php echo esc_attr($bot_token); ?>" placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11">
                                <div class="uk-form-help text-muted-foreground">
                                    Mã token khởi tạo nhận từ BotFather.
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="uk-form-label" for="telegram_chat_id">Chat ID / Channel ID</label>
                                <input class="uk-input" id="telegram_chat_id" name="telegram_chat_id" type="text" value="<?php echo esc_attr($chat_id); ?>" placeholder="-1001234567890">
                                <div class="uk-form-help text-muted-foreground">
                                    Mã định danh nhóm nhận, lưu ý nếu là Channel hoặc siêu nhóm sẽ kèm dấu âm đầu tiên `-100...`
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="uk-btn uk-btn-primary">
                                    Lưu tài khoản Telegram
                                </button>
                                <button type="button" class="uk-btn uk-btn-default ml-2">
                                    Test Thử Báo Cáo
                                </button>
                            </div>
                        </form>
                    </li>

                    <!-- TAB 4: Advanced -->
                    <li class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium">Cấu hình Tối Ưu Mở Rộng</h3>
                            <p class="text-muted-foreground text-sm">
                                Các chức năng chạy ngầm tự động liên quan đến chất lượng SEO.
                            </p>
                        </div>
                        <div class="border-border border-t"></div>
                        
                        <div class="space-y-4">
                            <div class="border-border flex items-center justify-between rounded-lg border p-4">
                                <div class="space-y-0.5">
                                    <label class="text-base font-medium" for="enable_rankmath">
                                        Chuỗi Đồng Bộ Rank Math
                                    </label>
                                    <div class="uk-form-help text-muted-foreground">
                                        Ghi mã Focus Keyword, sinh Title và Meta Description trực tiếp vào field của plugin RankMath SEO.
                                    </div>
                                </div>
                                <input class="uk-toggle-switch uk-toggle-switch-primary" id="enable_rankmath" name="enable_rankmath" type="checkbox" checked>
                            </div>

                            <div class="border-border flex items-center justify-between rounded-lg border p-4">
                                <div class="space-y-0.5">
                                    <label class="text-base font-medium" for="enable_webp">
                                        Nén Ảnh Chuẩn Google WebP
                                    </label>
                                    <div class="uk-form-help text-muted-foreground">
                                        Tự động xử lý ảnh DALL-E tải xuống: chuyển sang định dạng WebP cực nhẹ và rename với từ khóa SEO.
                                    </div>
                                </div>
                                <input class="uk-toggle-switch uk-toggle-switch-primary" id="enable_webp" name="enable_webp" type="checkbox" checked>
                            </div>
                        </div>
                    </li>

                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
        
        // Cấu hình Ajax lưu các form settings
        const forms = document.querySelectorAll('.frm-ajax-save');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const btn = form.querySelector('button[type="submit"]');
                const oldText = btn.innerHTML;
                btn.innerHTML = '<uk-icon icon="loader" class="uk-animation-spin mr-2 size-4"></uk-icon> Đang xử lý...';
                btn.disabled = true;

                const formData = new FormData(form);
                formData.append('action', form.querySelector('[name="action"]').value);

                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(res => {
                    UIkit.notification({
                        message: res.data && res.data.message ? res.data.message : 'Thao tác hoàn tất',
                        status: res.success ? 'primary' : 'danger',
                        pos: 'top-center'
                    });
                })
                .catch(err => {
                    UIkit.notification('Lỗi kết nối đến máy chủ.', {status: 'danger'});
                })
                .finally(() => {
                    btn.innerHTML = oldText;
                    btn.disabled = false;
                });
            });
        });

        // Xử lý nạp danh sách Google Sheets nếu đã Đăng nhập OAuth thành công
        <?php if ($is_connected): ?>
        const sheetSelect = document.getElementById('google_sheet_id');
        if (sheetSelect) {
            const formData = new FormData();
            formData.append('action', 'digi_fetch_google_sheets');
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data.sheets) {
                    const currentSelected = '<?php echo esc_attr($selected_sheet_id); ?>';
                    let html = '<option value="">-- Chọn Bảng Tính (Cập nhật liên tục) --</option>';
                    res.data.sheets.forEach(sheet => {
                        html += `<option value="${sheet.id}" ${currentSelected === sheet.id ? 'selected' : ''}>${sheet.name}</option>`;
                    });
                    sheetSelect.innerHTML = html;
                } else if (!res.success) {
                    sheetSelect.innerHTML = `<option value="">Lỗi: ${res.data.message}</option>`;
                } else {
                    sheetSelect.innerHTML = '<option value="">Chưa có dữ liệu trả về</option>';
                }
            })
            .catch(err => {
                sheetSelect.innerHTML = '<option value="">Error API...</option>';
            });
        }
        <?php endif; ?>
    });
</script>
