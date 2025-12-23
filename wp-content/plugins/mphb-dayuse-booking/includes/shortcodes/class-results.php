<?php

/**
 * Class MPHB_Dayuse_Results
 * Xử lý shortcode [mphb_custom_search_result]
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPHB_Dayuse_Results_Shortcode
{
    public function __construct()
    {
        add_shortcode('mphb_custom_search_result', array($this, 'render'));
    }

    public function render()
    {
        // Validate check-in
        if (empty($_GET['check_in'])) {
            return "<p>Vui lòng chọn ngày trước khi tìm phòng.</p>";
        }

        $check_in_val = sanitize_text_field($_GET['check_in']);
        $check_out_val = sanitize_text_field($_GET['check_out'] ?? $_GET['check_in']);

        // Lấy phòng đã khóa
        $locked = get_locked_rooms_and_types($check_in_val, $check_out_val);
        $lockedRoomIds = $locked['locked_room_ids'] ?? [];

        // Lấy tất cả loại phòng
        $all_room_types = get_posts([
            'post_type' => 'mphb_room_type',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        // Polylang translation
        if (function_exists('pll_get_post') && function_exists('pll_current_language')) {
            $current_lang = pll_current_language();
            $translated_room_types = [];

            foreach ($all_room_types as $room_type) {
                $translated_id = pll_get_post($room_type->ID, $current_lang);
                if ($translated_id) {
                    $translated_room_types[] = get_post($translated_id);
                } else {
                    $translated_room_types[] = $room_type;
                }
            }

            $all_room_types = $translated_room_types;
        }

        $rooms = [];
        foreach ($all_room_types as $room_type) {
            $allRoomIds = MPHB()->getRoomPersistence()->findAllIdsByType($room_type->ID);
            $availableRoomIds = array_diff($allRoomIds, $lockedRoomIds);
            $available_count = count($availableRoomIds);

            if ($available_count <= 0) {
                continue;
            }

            // Lấy metadata
            $adults = get_post_meta($room_type->ID, 'mphb_adults_capacity', true) ?: 2;
            $children = get_post_meta($room_type->ID, 'mphb_children_capacity', true) ?: 0;
            $size = get_post_meta($room_type->ID, 'mphb_size', true) ?: 'N/A';
            $bed_type = get_post_meta($room_type->ID, 'mphb_bed', true) ?: 'N/A';
            $excerpt = !empty($room_type->post_excerpt) ? $room_type->post_excerpt : '';

            // Gallery
            $gallery_urls = [];
            $gallery_ids_raw = get_post_meta($room_type->ID, 'mphb_gallery', true);
            if (!empty($gallery_ids_raw)) {
                $gallery_ids = is_array($gallery_ids_raw) ? $gallery_ids_raw : explode(',', $gallery_ids_raw);
                foreach ($gallery_ids as $img_id) {
                    $img_id = trim($img_id);
                    if ($img_id && $url = wp_get_attachment_url($img_id)) {
                        $gallery_urls[] = $url;
                    }
                }
            }

            // Giá và rate ID
            $price = get_dayuse_price_for_room_type($room_type->ID, $check_in_val, 'day-use');
            $rate_id = get_rate_id_by_booking_type($room_type->ID, 'day-use');

            $rooms[] = [
                'id' => $room_type->ID,
                'title' => $room_type->post_title,
                'permalink' => get_permalink($room_type->ID),
                'adults' => $adults,
                'children' => $children,
                'size' => $size,
                'bed_type' => $bed_type,
                'price' => $price,
                'rate_id' => $rate_id,
                'available_count' => $available_count,
                'available_room_ids' => array_values($availableRoomIds),
                'gallery' => $gallery_urls,
                'excerpt' => $excerpt
            ];
        }

        // Render template
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/results-list.php';
        if (!file_exists($template_path)) {
            return "<p>Lỗi: Không tìm thấy template hiển thị kết quả.</p>";
        }

        ob_start();
        include $template_path;
        $output = ob_get_clean();

        return $output;
    }
}

new MPHB_Dayuse_Results_Shortcode();
