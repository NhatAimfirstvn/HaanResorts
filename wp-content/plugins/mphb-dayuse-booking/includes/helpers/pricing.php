<?php
if (!defined('ABSPATH'))
    exit;
/**
 * Lấy rate ID của loại phòng theo tên rate (ví dụ: "day-use").
 */
function get_rate_id_by_booking_type($room_type_id, $booking_type = 'day-use')
{
    $current_lang = pll_current_language();
    // Chuyển sang ID của room_type trong ngôn ngữ mặc định
    if (function_exists('pll_get_post')) {
        $default_room_type_id = pll_get_post($room_type_id, pll_default_language());
        if (!$default_room_type_id) {
            $default_room_type_id = $room_type_id; // fallback nếu không có
        }
    } else {
        $default_room_type_id = $room_type_id;
    }
    // Lấy tất cả rate của ngôn ngữ mặc định
    $rates = get_posts([
        'post_type' => 'mphb_rate',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'lang' => pll_default_language(), // chỉ lấy rate của ngôn ngữ mặc định
        'meta_query' => [
            [
                'key' => 'mphb_room_type_id',
                'value' => $default_room_type_id,
            ]
        ],
    ]);
    foreach ($rates as $rate) {
        $rate_booking_type = get_post_meta($rate->ID, 'booking_type', true);
        if (strtolower(trim($rate_booking_type)) === strtolower(trim($booking_type))) {
            $translated_rate_id = function_exists('pll_get_post') ? pll_get_post($rate->ID, $current_lang) : false;
            $translated_rate_id=$translated_rate_id ?: $rate->ID;
            return $translated_rate_id;
        }
    }
    return 0; // Không tìm thấy
}

/**
 * Lấy giá theo rate ID và ngày check-in (có xét season).
 */
function get_price_by_rate_id($rate_id, $check_in_date)
{
    $price = 0;
    $check_day = date('w', strtotime($check_in_date)); // 0=CN ... 6=T7

    $season_prices = get_post_meta($rate_id, 'mphb_season_prices', true);

    if (is_array($season_prices) && !empty($season_prices)) {
        foreach ($season_prices as $season) {
            $season_days = maybe_unserialize(get_post_meta($season['season'], 'mphb_days', true));

            if (!$season_days || in_array($check_day, $season_days)) {
                $price = $season['price']['prices'][0] ?? 0;
                break;
            }
        }
    }

    // Nếu không có giá mùa → fallback về giá gốc
    if (!$price) {
        $price = get_post_meta($rate_id, '_mphb_price', true);
    }

    return (float) $price;
}

/**
 * Hàm gốc kết hợp 2 hàm trên (giữ nguyên behavior cũ).
 */
function get_dayuse_price_for_room_type($room_type_id, $check_in_date, $rate_booking_type = 'day-use')
{
    $rate_id = get_rate_id_by_booking_type($room_type_id, $rate_booking_type);

    if (!$rate_id) {
        return 0;
    }

    return get_price_by_rate_id($rate_id, $check_in_date);
}
