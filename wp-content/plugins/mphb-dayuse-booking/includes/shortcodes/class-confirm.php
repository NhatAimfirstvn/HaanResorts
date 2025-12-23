<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Shortcode [mphb_confirm_booking]
 * Hiển thị giao diện xác nhận đặt phòng
 */

require_once __DIR__ . '/../handlers/confirm-booking-handler.php';

class MPHB_Dayuse_Confirm_Shortcode
{
    public function __construct()
    {
        add_shortcode('mphb_confirm_booking', [$this, 'render_shortcode']);
    }

    public function render_shortcode()
    {
        ob_start();

        $check_in = isset($_GET['check_in']) ? sanitize_text_field($_GET['check_in']) : '';
        $check_out = isset($_GET['check_out']) ? sanitize_text_field($_GET['check_out']) : '';
        $rooms = isset($_GET['rooms']) ? (array) $_GET['rooms'] : [];

        if (empty($check_in) || empty($rooms)) {
            echo "<p>Không có dữ liệu đặt phòng. Vui lòng quay lại trang tìm phòng.</p>";
            return ob_get_clean();
        }

        $template = plugin_dir_path(dirname(__FILE__)) . 'templates/confirm-booking.php';

        if (file_exists($template)) {
            // Lọc chỉ giữ lại phòng được chọn
            $rooms = array_filter($rooms, function ($data) {
                return !empty($data['room_ids']) && intval($data['qty'] ?? 0) > 0;
            });

            $context = [
                'check_in' => $check_in,
                'check_out' => $check_out,
                'rooms' => $rooms
            ];
            extract($context);
            include $template;

        } else {
            echo "<p>Lỗi: Không tìm thấy template xác nhận đặt phòng.</p>";
        }

        return ob_get_clean();
    }
}
// Khởi tạo shortcode
new MPHB_Dayuse_Confirm_Shortcode();