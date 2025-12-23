<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('MPHB_Dayuse_Search_Shortcode')) {
    class MPHB_Dayuse_Search_Shortcode {

        public function __construct() {
            add_shortcode('mphb_custom_search_rooms', [$this, 'render']);
        }

        public function render($atts = []) {
            ob_start();

            $today = date('Y-m-d');

            $check_in_val = isset($_GET['check_in'])
                ? sanitize_text_field($_GET['check_in'])
                : date('Y-m-d', strtotime('+1 day'));

            $template_file = __DIR__ . '/../templates/search-form.php';
            if (file_exists($template_file)) {
                // Truyền dữ liệu vào template
                $check_in = $check_in_val;
                include $template_file;
            } else {
                echo "<p>Lỗi: Không tìm thấy template hiển thị kết quả.</p>";
            }

            return ob_get_clean();
        }
    }
}
