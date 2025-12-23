<?php
if (!defined('ABSPATH'))
    exit;
//Filter lại phòng trống theo giờ checkin checkout cho tùy loại booking( Day-use, 2N1D)
add_filter('mphb_search_available_rooms', function ($roomsAtts) {
    $check_in_date_search = $roomsAtts['from_date'] instanceof DateTime ? $roomsAtts['from_date']->format('Y-m-d') : $roomsAtts['from_date'];
    $check_out_date_search = $roomsAtts['to_date'] instanceof DateTime ? $roomsAtts['to_date']->format('Y-m-d') : $roomsAtts['to_date'];

    $locked = get_locked_rooms_and_types($check_in_date_search, $check_out_date_search);
    $GLOBALS['mphb_custom_locked_room_ids'] = $locked['locked_room_ids'];
    $GLOBALS['mphb_custom_locked_room_types'] = $locked['locked_room_types'];

    if (!empty($locked['locked_room_ids'])) {
        $roomsAtts['exclude_rooms'] = $locked['locked_room_ids'];
        error_log("EXCLUDE ROOM IDS: " . implode(',', $locked['locked_room_ids']));
    }

    if (!empty($locked['locked_room_types'])) {
        $roomsAtts['exclude_room_types'] = $locked['locked_room_types'];
        error_log("EXCLUDE ROOM TYPES: " . implode(',', $locked['locked_room_types']));
    }
    if ($check_in_date_search == $check_out_date_search) {
        $roomsAtts['skip_buffer_rules'] = true; // Bỏ qua buffer/minimum stay
    }
    return $roomsAtts;
}, 1);

add_filter('mphb_search_rooms_atts', function ($atts, $defaults) {
    $lockedRoomTypes = isset($GLOBALS['mphb_custom_locked_room_types']) ? $GLOBALS['mphb_custom_locked_room_types'] : [];

    if (!empty($lockedRoomTypes)) {
        // Get all room IDs for locked room types
        $excludeRoomIds = [];
        foreach ($lockedRoomTypes as $roomTypeId) {
            $roomIds = MPHB()->getRoomPersistence()->findAllIdsByType($roomTypeId);
            $excludeRoomIds = array_merge($excludeRoomIds, $roomIds);
        }

        if (!empty($excludeRoomIds)) {
            // Merge with existing excluded rooms
            if (!empty($atts['exclude_rooms'])) {
                $atts['exclude_rooms'] = array_unique(array_merge($atts['exclude_rooms'], $excludeRoomIds));
            } else {
                $atts['exclude_rooms'] = $excludeRoomIds;
            }

            error_log("=== Search Rooms Atts Filter ===");
            error_log("Excluded room IDs from locked types: " . implode(',', $excludeRoomIds));
        }
    }

    return $atts;
}, 10, 2);

add_filter('posts_where', function ($where, $query) {
    global $wpdb;
    // Only modify search results queries for room types
    if (!is_admin() && $query->get('post_type') === MPHB()->postTypes()->roomType()->getPostType()) {
        $lockedRoomTypes = isset($GLOBALS['mphb_custom_locked_room_types']) ? $GLOBALS['mphb_custom_locked_room_types'] : [];

        if (!empty($lockedRoomTypes)) {
            $excludeIds = implode(',', array_map('absint', $lockedRoomTypes));
            $where .= " AND {$wpdb->posts}.ID NOT IN ($excludeIds)";

            error_log("=== posts_where WHERE Filter ===");
            error_log("Added WHERE clause to exclude room types: $excludeIds");
        }
    }
    return $where;
}, 10, 2);
// Tính lại số lượng phòng trống
add_filter('mphb_max_rooms_count', function ($count, $roomType) {
    // Lấy danh sách room IDs đã bị khóa từ filter trước
    $lockedRoomIds = isset($GLOBALS['mphb_custom_locked_room_ids']) ? $GLOBALS['mphb_custom_locked_room_ids'] : [];
    // Lấy tất cả room IDs của loại phòng hiện tại
    $allRoomIds = MPHB()->getRoomPersistence()->findAllIdsByType($roomType->getOriginalId());
    // Tính số phòng trống = tất cả - đã khóa
    $availableRoomIds = array_diff($allRoomIds, $lockedRoomIds);
    return count($availableRoomIds);
}, 10, 2);
// lọc ra các rate phù hợp cho từng trang booking
add_filter('mphb_sc_checkout_allowed_rates', 'filter_rates_by_booking_type', 10, 3);
// Lọc ra các service theo ngày checkin-checkout( weekday,weekeend) 
add_filter('mphb_sc_checkout_allowed_services', 'filter_services_custom', 10, 3);
// Hiển thị cột "Danh mục" cho post type = gallery
add_filter('manage_gallery_posts_columns', function ($columns) {
    $columns['gallery_category'] = __('Danh mục', 'textdomain');
    $columns['thumbnail'] = __('Ảnh', 'textdomain');
    return $columns;
});
//preload-font-awesome
add_filter('wp_resource_hints', function ($urls, $relation_type) {
	if ('preload' === $relation_type) {
		$urls[] = [
			'href'        => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
			'as'          => 'style',
			'crossorigin' => 'anonymous',
		];
	}
	return $urls;
}, 10, 2);
if (function_exists('pll_current_language') && function_exists('pll_get_post')) {
    // 1. Dịch ID trang MPHB theo ngôn ngữ hiện tại
    add_filter('_mphb_translate_page_id', function ($page_id) {
        return pll_get_post($page_id, pll_current_language()) ?: $page_id;
    });
}