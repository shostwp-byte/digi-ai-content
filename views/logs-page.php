<?php
if (!defined('ABSPATH')) {
    exit;
}
$logs = \DigiAiContent\Logger::get_logs(50); // Mặc định lấy 50 dòng mới nhất
?>
<style>
    /* Ghi đè cấu hình màu chủ đạo Theme Franken UI */
    :root {
        --primary: 221.2 83.2% 53.3%;
        --primary-foreground: 210 40% 98%;
        --background: 0 0% 100%;
        --card: 0 0% 100%;
    }
    html, body, #wpwrap, #wpcontent, #wpbody-content, .uk-container {
        background-color: hsl(var(--background)) !important;
    }
</style>
<div class="wrap uk-container uk-container-expand uk-margin-top">
    <div class="p-6 lg:p-10">
        
        <div class="flex items-center justify-between mb-2">
            <div class="space-y-0.5">
                <h2 class="text-2xl font-bold tracking-tight flex items-center">
                    <uk-icon class="mr-3 size-6 text-primary" icon="history"></uk-icon>
                    Logs Trí Tuệ Nhân Tạo
                </h2>
                <p class="text-muted-foreground">
                    Theo dõi, giám sát sức khoẻ tiến trình tự động 24/7. Tự động xóa các logs cũ quá giới hạn 500 dòng.
                </p>
            </div>
            <button class="uk-btn uk-btn-destructive" id="btn_clear_logs">
                <uk-icon class="mr-2 size-4" icon="trash-2"></uk-icon>
                Làm sạch bộ nhớ
            </button>
        </div>
        
        <div class="border-border my-6 border-t"></div>

        <div class="uk-overflow-auto bg-card border rounded-lg shadow-sm">
            <table class="uk-table uk-table-divider uk-table-hover uk-table-middle mb-0">
                <thead>
                    <tr>
                        <th class="w-16">ID</th>
                        <th class="w-40">Thời gian</th>
                        <th class="w-64">Từ khoá / Nhiệm vụ</th>
                        <th class="w-32">Model</th>
                        <th class="w-32">Trạng thái</th>
                        <th>Thông điệp hệ thống</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" class="uk-text-center py-6 text-muted-foreground">Hiện tại chưa có bất kỳ rủi ro hay hoạt động nào được ghi lại.</td></tr>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td class="uk-text-muted">#<?php echo esc_html($log->id); ?></td>
                            <td class="uk-text-small"><?php echo esc_html(date('d/m/Y H:i:s', strtotime($log->created_at))); ?></td>
                            <td class="font-medium">
                                <?php echo esc_html($log->keyword); ?>
                                <?php if ($log->post_id > 0): ?>
                                    <br><a href="<?php echo get_edit_post_link($log->post_id); ?>" target="_blank" class="uk-text-small uk-text-primary">Xem chi tiết bài <?php echo intval($log->post_id); ?></a>
                                <?php endif; ?>
                            </td>
                            <td><span class="uk-badge uk-badge-secondary"><?php echo esc_html($log->model); ?></span></td>
                            <td>
                                <?php if ($log->status === 'success'): ?>
                                    <span class="uk-label uk-label-success">Thành công</span>
                                <?php else: ?>
                                    <span class="uk-label uk-label-danger">Thất bại</span>
                                <?php endif; ?>
                            </td>
                            <td class="uk-text-small">
                                <?php echo wp_kses_post($log->message); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnClearLogs = document.getElementById('btn_clear_logs');
    if (btnClearLogs) {
        btnClearLogs.addEventListener('click', function() {
            if (!confirm('Bạn có chắc chắn muốn xóa đi toàn bộ chứng cứ hoạt động đã lưu? Thao tác này là vĩnh viễn (Truncate)!')) {
                return;
            }

            const oldText = btnClearLogs.innerHTML;
            btnClearLogs.innerHTML = '<uk-icon icon="loader" class="uk-animation-spin mr-2 size-4"></uk-icon> Đang xoá...';
            btnClearLogs.disabled = true;

            const formData = new FormData();
            formData.append('action', 'digi_clear_logs');

            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    UIkit.notification({ message: 'Dọn dẹp thành công Database!', status: 'success', pos: 'top-center' });
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    UIkit.notification({ message: res.data && res.data.message ? res.data.message : 'Error', status: 'danger', pos: 'top-center' });
                }
            })
            .finally(() => {
                btnClearLogs.innerHTML = oldText;
                btnClearLogs.disabled = false;
            });
        });
    }
});
</script>
